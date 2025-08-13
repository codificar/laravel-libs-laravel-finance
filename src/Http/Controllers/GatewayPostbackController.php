<?php

namespace Codificar\Finance\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log, Response;
use Finance, Settings;
use Codificar\Finance\Events\PixUpdate;
use Codificar\Finance\Models\LibModel;
use Codificar\Finance\Models\Transaction;
use Codificar\PaymentGateways\Libs\PaymentFactory as LibsPaymentFactory;

class GatewayPostbackController extends Controller
{
    /**
     * Recebe uma notificacao quando o status da transacao boleto muda
     */
    public function postbackBillet($transactionId, Request $request)
    {
        if ($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        if ($request->id) {
            $transactionId = $request->id;
        }

        // Verifica se essa transação é um pix
        $transaction = Transaction::getTransactionByGatewayId($transactionId);
        if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste) {
            return $this->postbackPix($transaction->id, $request);
        }

        $gateway = LibsPaymentFactory::createGateway();

        // Garantir estrutura de array e evitar notices em chaves ausentes
        $billetVerify = (array) $gateway->billetVerify($request, $transactionId);

        $transactionIdResolved = ($transactionId && is_numeric($transactionId))
            ? $transactionId
            : ($billetVerify['transaction_id'] ?? null);

        $transaction = $transactionIdResolved ? Transaction::find($transactionIdResolved) : null;

        if (!$transaction && $transactionId) {
            $transaction = Transaction::where(["gateway_transaction_id" => $transactionId])->first();
        }

        $success = $billetVerify['success'] ?? false;
        $status  = $billetVerify['status']  ?? null;

        if ($transaction && $transaction->ledger_id && $success && $status === 'paid') {
            // Evita pagar em duplicidade
            if ($transaction->status !== "paid") {
                $tax = Settings::findByKey('prepaid_tax_billet');
                $tax = $tax ? (float) $tax : 0;

                // Add balance para o usuário
                Finance::createCustomEntry(
                    $transaction->ledger_id,
                    Finance::SEPARATE_CREDIT,
                    "Credito referente ao boleto pago",
                    $transaction->gross_value - $tax,
                    null,
                    null
                );

                $transaction->setStatusPaid();
            }
        }

        // resposta 200 para o gateway saber que deu certo
        return Response::json(["success" => true], 200);
    }

    /**
     * Recebe uma notificacao quando o status da transacao pix e alterada
     */
    public function postbackPix($transactionId, Request $request)
    {
        if ($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        $gatewayPix = Settings::getDefaultPaymentPix();

        if ($gatewayPix == 'ipag') {
            return $this->postbackPixIpag($request);
        } elseif ($gatewayPix == 'juno') {
            return $this->postbackPixJuno($transactionId, $request);
        }

        return Response::json(['success' => true], 200);
    }

    /**
     * Recebe uma notificação quando o status da transação pix do IPag e alterada
     */
    private function postbackPixIpag(Request $request)
    {
        try {
            $webhookRequest = $request->all();

            // Log para debug da estrutura do webhook
            Log::info('Pix IPag Webhook: ' . json_encode($webhookRequest));

            if (isset($webhookRequest['id']) && !empty($webhookRequest['id'])) {
                $transaction = Transaction::getTransactionByGatewayId($webhookRequest['id']);

                // 1. Validação de status com priorização e fallbacks
                $statusCode   = null;
                $statusSource = null;

                // Priorizar status no nível raiz
                if (isset($webhookRequest['status']['code'])) {
                    $statusCode = $webhookRequest['status']['code'];
                    $statusSource = 'root';
                }
                // Fallback 1: attributes.status.code
                elseif (isset($webhookRequest['attributes']['status']['code'])) {
                    $statusCode = $webhookRequest['attributes']['status']['code'];
                    $statusSource = 'attributes';
                    Log::warning('Pix IPag: Usando fallback para status em attributes - Transaction ID: ' . $webhookRequest['id']);
                }
                // Fallback 2: data.status.code
                elseif (isset($webhookRequest['data']['status']['code'])) {
                    $statusCode = $webhookRequest['data']['status']['code'];
                    $statusSource = 'data';
                    Log::warning('Pix IPag: Usando fallback para status em data - Transaction ID: ' . $webhookRequest['id']);
                }
                // Se nenhum existir, logar erro e retornar 200 (evita reentrega infinita)
                else {
                    Log::error('Pix IPag: Estrutura de status inválida - Transaction ID: ' . ($webhookRequest['id'] ?? 'unknown') . ' - Payload: ' . json_encode($webhookRequest));
                    return Response::json(['success' => true], 200);
                }

                $isCaptured = Transaction::isWebhookCaptured($statusCode);

                if ($isCaptured && $transaction && $transaction->ledger_id && $transaction->pix_copy_paste) {
                    // Se a transação já está paga, não faz sentido crédito de novo
                    if (!$transaction->isPaid()) {
                        // Transação referente a corrida
                        if ($transaction->request_id) {
                            $ride = $transaction->ride;
                            $ride->setIsPaid();

                            // 2. Busca de split_rules com priorização e fallbacks
                            $splitRules  = null;
                            $splitSource = null;

                            // Priorizar split_rules no nível raiz
                            if (isset($webhookRequest['split_rules']) && !empty($webhookRequest['split_rules'])) {
                                $splitRules = $webhookRequest['split_rules'];
                                $splitSource = 'root';
                            }
                            // Fallback 1: attributes.split_rules
                            elseif (isset($webhookRequest['attributes']['split_rules']) && !empty($webhookRequest['attributes']['split_rules'])) {
                                $splitRules = $webhookRequest['attributes']['split_rules'];
                                $splitSource = 'attributes';
                                Log::warning('Pix IPag: Usando fallback para split_rules em attributes - Transaction ID: ' . ($transaction->id ?? $webhookRequest['id']));
                            }
                            // Fallback 2: data.attributes.split_rules
                            elseif (isset($webhookRequest['data']['attributes']['split_rules']) && !empty($webhookRequest['data']['attributes']['split_rules'])) {
                                $splitRules = $webhookRequest['data']['attributes']['split_rules'];
                                $splitSource = 'data.attributes';
                                Log::warning('Pix IPag: Usando fallback para split_rules em data.attributes - Transaction ID: ' . ($transaction->id ?? $webhookRequest['id']));
                            }
                            // Fallback 3: data.split_rules
                            elseif (isset($webhookRequest['data']['split_rules']) && !empty($webhookRequest['data']['split_rules'])) {
                                $splitRules = $webhookRequest['data']['split_rules'];
                                $splitSource = 'data';
                                Log::warning('Pix IPag: Usando fallback para split_rules em data - Transaction ID: ' . ($transaction->id ?? $webhookRequest['id']));
                            }

                            $hasSplitRules = !empty($splitRules);

                            // Log para debug do split
                            if ($hasSplitRules) {
                                Log::info(
                                    'Pix IPag Split Rules encontradas - Transaction ID: ' .
                                    ($transaction->id ?? $webhookRequest['id']) .
                                    ' - Source: ' . $splitSource .
                                    ' - Rules: ' . json_encode($splitRules)
                                );
                            }

                            // gera saldo para o motorista (sem split)
                            if ($ride->confirmedProvider && $ride->confirmedProvider->Ledger && !$hasSplitRules) {
                                $reason = trans('financeTrans::finance.webhook_pix_ride_credit') . $ride->id;
                                LibModel::createRideCredit(
                                    $ride->confirmedProvider->Ledger->id,
                                    $transaction->provider_value * -1,
                                    $ride->id,
                                    $reason,
                                    false,
                                    $transaction->id
                                );

                                Log::info('Pix IPag: Crédito criado para motorista (sem split) - Transaction ID: ' . $transaction->id . ' - Ride ID: ' . $ride->id);
                            }

                            // Processa split se existir
                            if ($hasSplitRules) {
                                $gateway = LibsPaymentFactory::createPixGateway();
                                $isSplit = Settings::findByKey('auto_transfer_provider_payment');

                                if ($isSplit) {
                                    Log::info('Pix IPag: Processando split para transação - Transaction ID: ' . $transaction->id . ' - Ride ID: ' . $ride->id);

                                    // Aqui mantemos o comportamento atual: refletir "houve split" no gateway,
                                    // e agendar compensação para o provider_value no nosso financeiro.
                                    // (Se quiser aplicar as regras exatas de $splitRules, tratar aqui.)
                                    Finance::createFinanceSplitInformation(
                                        $ride->confirmedProvider->Ledger->id,
                                        $transaction->provider_value,
                                        $ride->id,
                                        $gateway->getNextCompensationDate(),
                                        trans('finance.ride_pix_payment'),
                                        trans('finance.ride_pix_payment'),
                                        Finance::RIDE_CREDIT_PIX_SPLIT,
                                        Finance::RIDE_DEBIT_PIX_SPLIT
                                    );

                                    Log::info('Pix IPag: Split processado com sucesso - Transaction ID: ' . $transaction->id . ' - Ride ID: ' . $ride->id);
                                } else {
                                    Log::warning('Pix IPag: Split desabilitado nas configurações - fazendo fallback para crédito manual - Transaction ID: ' . $transaction->id);

                                    // FALLBACK: creditar motorista manualmente quando split estiver desabilitado
                                    if ($ride->confirmedProvider && $ride->confirmedProvider->Ledger) {
                                        $reason = trans('financeTrans::finance.webhook_pix_ride_credit') . $ride->id;
                                        LibModel::createRideCredit(
                                            $ride->confirmedProvider->Ledger->id,
                                            $transaction->provider_value * -1,
                                            $ride->id,
                                            $reason,
                                            false,
                                            $transaction->id
                                        );

                                        Log::info('Pix IPag: Crédito (fallback) criado para motorista - Transaction ID: ' . $transaction->id . ' - Ride ID: ' . $ride->id);
                                    } else {
                                        Log::warning('Pix IPag: Não foi possível aplicar fallback de crédito (sem Ledger) - Transaction ID: ' . $transaction->id);
                                    }
                                }
                            }
                        }
                        // Atualiza assinatura via Pix
                        elseif ($transaction->signature_id) {
                            $signature = $transaction->signature;
                            if ($signature) {
                                $signature->updatePostBackPix();
                                Log::info('Pix IPag: Assinatura atualizada via PIX - Transaction ID: ' . $transaction->id . ' - Signature ID: ' . $transaction->signature_id);
                            }
                        }
                        // Transação pré-paga: adiciona saldo
                        else {
                            if (!$transaction->finance) {
                                $description = trans('financeTrans::finance.webhook_pix_balance_credit') . $webhookRequest['id'];
                                LibModel::createCustomEntry(
                                    $transaction->ledger_id,
                                    Finance::SEPARATE_CREDIT,
                                    $description,
                                    $transaction->gross_value,
                                    null,
                                    null,
                                    $transaction->id
                                );

                                Log::info('Pix IPag: Saldo pré-pago adicionado - Transaction ID: ' . $transaction->id . ' - Ledger ID: ' . $transaction->ledger_id);
                            }
                        }

                        $transaction->setStatusPaid();

                        // disparar evento pix
                        event(new PixUpdate($transaction->id, true, false));

                        Log::info('Pix IPag: Transação processada com sucesso - Transaction ID: ' . $transaction->id . ' - Status Source: ' . $statusSource);

                        return Response::json(['success' => true], 200);
                    }

                    // Já estava paga
                    Log::info('Pix IPag: Transação já estava paga - Transaction ID: ' . $transaction->id);
                    return Response::json(['success' => true], 200);
                }

                // Não capturada ou inconsistências
                $tidLog = $transaction->id ?? ($webhookRequest['id'] ?? 'unknown');
                if (!$isCaptured) {
                    Log::info('Pix IPag: Transação não capturada - Transaction ID: ' . $tidLog . ' - Status Code: ' . $statusCode);
                } elseif (!$transaction) {
                    Log::warning('Pix IPag: Transação não encontrada - Transaction ID: ' . ($webhookRequest['id'] ?? 'unknown'));
                } elseif (!$transaction->ledger_id) {
                    Log::warning('Pix IPag: Transação sem ledger_id - Transaction ID: ' . $tidLog);
                } elseif (!$transaction->pix_copy_paste) {
                    Log::warning('Pix IPag: Transação sem pix_copy_paste - Transaction ID: ' . $tidLog);
                }

                return Response::json(['success' => true], 200);
            }

            Log::error('Pix IPag: Nao foi possível identificar a transação - Payload: ' . json_encode($webhookRequest));
            return Response::json(['success' => true], 200);
        } catch (\Exception $e) {
            \Log::error('Pix IPag: Erro no processamento - ' . $e->getMessage() . ' - Stack: ' . $e->getTraceAsString());
            return Response::json(['success' => true], 200);
        }
    }

    /**
     * Recebe uma notificacao quando o status da transacao pix do Juno e alterada
     */
    private function postbackPixJuno($transactionId, Request $ride)
    {
        $gateway = LibsPaymentFactory::createPixGateway();
        $retrievePix = (array) $gateway->retrievePix($transactionId, $ride);

        $transactionIdResolved = $retrievePix['transaction_id'] ?? null;
        $transaction = $transactionIdResolved ? Transaction::find($transactionIdResolved) : null;

        $success = $retrievePix['success'] ?? false;
        $paid    = $retrievePix['paid'] ?? false;

        if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste && $success && $paid) {
            // Evita duplicidade
            if ($transaction->status !== "paid") {
                // Transação referente a request
                if ($transaction->request_id) {
                    $rideModel = $transaction->ride;
                    $rideModel->setIsPaid();

                    // gera saldo para o motorista
                    if ($rideModel->confirmedProvider && $rideModel->confirmedProvider->Ledger) {
                        $providerValue = $transaction->provider_value * -1;
                        Finance::createRideCredit($rideModel->confirmedProvider->Ledger->id, $providerValue, $rideModel->id);
                    }
                }
                // Pré-pago
                else {
                    Finance::createCustomEntry(
                        $transaction->ledger_id,
                        Finance::SEPARATE_CREDIT,
                        "Pagamento Pix",
                        $transaction->gross_value,
                        null,
                        null
                    );
                }

                $transaction->setStatusPaid();

                // disparar evento pix
                event(new PixUpdate($transaction->id, true, false));
            }
        }

        return Response::json(['success' => true], 200);
    }
}
