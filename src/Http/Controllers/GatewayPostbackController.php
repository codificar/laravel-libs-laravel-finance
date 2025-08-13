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

                            // ---------------------------
                            // SELLER & SPLIT PROCESSING
                            // ---------------------------

                            // 2.1 Garantir provider como seller no gateway (para recebimento direto)
                            if ($ride->confirmedProvider) {
                                $this->ensureProviderSeller($ride->confirmedProvider);
                            }

                            // 2.2 Sem split_rules: crédito manual para o motorista (comportamento legado)
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

                            // 2.3 Com split_rules: processar split de fato
                            if ($hasSplitRules) {
                                $gateway = LibsPaymentFactory::createPixGateway();
                                $isSplit = Settings::findByKey('auto_transfer_provider_payment');

                                if ($isSplit) {
                                    // Calcula a parcela do provider com base nas regras (percentual/valor)
                                    $providerSplitAmount = $this->calculateProviderSplitAmount(
                                        $splitRules,
                                        (float) $transaction->gross_value,
                                        (float) $transaction->provider_value,
                                        $ride
                                    );

                                    if ($providerSplitAmount <= 0) {
                                        // Fallback para provider_value se não foi possível inferir
                                        $providerSplitAmount = (float) $transaction->provider_value;
                                        Log::warning('Pix IPag: Não foi possível inferir valor de split pelas regras; usando provider_value como fallback - Transaction ID: ' . $transaction->id);
                                    }

                                    Log::info('Pix IPag: Split calculado para provider - valor=' . number_format($providerSplitAmount, 2, '.', '') . ' - Transaction ID: ' . $transaction->id);

                                    // Agenda compensação do split do provider no financeiro interno
                                    Finance::createFinanceSplitInformation(
                                        $ride->confirmedProvider->Ledger->id,
                                        $providerSplitAmount,
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

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Garante que o provider existe como "seller" no gateway Pix.
     * Tenta chamar um método da implementação do gateway (ajuste o nome se necessário).
     */
    private function ensureProviderSeller($provider): void
    {
        try {
            $gateway = LibsPaymentFactory::createPixGateway();

            // Nome do método pode variar na sua lib (ex.: ensureSellerForProvider / upsertSeller / createOrUpdateSeller)
            $possibleMethods = ['ensureSellerForProvider', 'upsertSellerForProvider', 'createOrUpdateSellerForProvider', 'ensureSeller', 'upsertSeller', 'createOrUpdateSeller'];

            $called = false;
            foreach ($possibleMethods as $method) {
                if (method_exists($gateway, $method)) {
                    $gateway->{$method}($provider);
                    $called = true;
                    Log::info('Pix IPag: Provider garantido como seller no gateway - Provider ID: ' . ($provider->id ?? 'unknown') . ' - Método: ' . $method);
                    break;
                }
            }

            if (!$called) {
                Log::warning('Pix IPag: Método para garantir seller não encontrado no gateway. Ajuste o helper ensureProviderSeller() com o método correto da sua lib.');
            }
        } catch (\Throwable $t) {
            Log::error('Pix IPag: Falha ao garantir provider como seller - ' . $t->getMessage());
        }
    }

    /**
     * Calcula o valor do split destinado ao provider com base nas split_rules do gateway.
     * Suporta tanto regras por percentual quanto por valor fixo.
     *
     * Estruturas suportadas (exemplos):
     * - ['recipient' => 'provider', 'percentage' => 80]
     * - ['recipient' => 'provider', 'percent' => 80]
     * - ['recipient' => 'provider', 'amount' => 123.45]
     * - ['seller_id' => 'abc123', 'percentage' => 70]
     *
     * @param array       $splitRules           Regras vindas do webhook
     * @param float       $grossValue          Valor bruto da transação (total)
     * @param float       $defaultProviderVal  Fallback (ex.: provider_value da transação)
     * @param \Illuminate\Database\Eloquent\Model|null $ride
     * @return float
     */
    private function calculateProviderSplitAmount(array $splitRules, float $grossValue, float $defaultProviderVal, $ride = null): float
    {
        $providerAmount = 0.0;

        // Possíveis identificadores do provider no rule:
        $providerHints = [];

        if ($ride && $ride->confirmedProvider) {
            // Se o provider tiver seller_id/documento, use como pista
            if (isset($ride->confirmedProvider->seller_id)) {
                $providerHints[] = (string) $ride->confirmedProvider->seller_id;
            }
            if (isset($ride->confirmedProvider->document)) {
                $providerHints[] = preg_replace('/\D+/', '', (string) $ride->confirmedProvider->document); // só dígitos
            }
            if (isset($ride->confirmedProvider->id)) {
                $providerHints[] = (string) $ride->confirmedProvider->id;
            }
        }
        // Padrões textuais comuns
        $providerHints = array_filter(array_unique(array_merge($providerHints, ['provider', 'driver', 'motorista', 'seller_provider'])));

        foreach ($splitRules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            // Tenta identificar se a regra é do provider
            $recipientRaw = ($rule['recipient'] ?? $rule['seller_id'] ?? $rule['payee'] ?? $rule['to'] ?? null);
            $recipient = is_string($recipientRaw) ? strtolower(trim($recipientRaw)) : $recipientRaw;

            $isProviderRule = false;

            if (is_string($recipient)) {
                foreach ($providerHints as $hint) {
                    if ($hint && strpos($recipient, strtolower((string) $hint)) !== false) {
                        $isProviderRule = true;
                        break;
                    }
                }
            }

            // Também aceita regras onde explicitamente indicam "liable_to_provider" etc.
            if (!$isProviderRule && isset($rule['recipient_type']) && in_array(strtolower((string) $rule['recipient_type']), ['provider', 'driver', 'motorista', 'seller'], true)) {
                $isProviderRule = true;
            }

            if (!$isProviderRule) {
                continue;
            }

            // Valor por percentual
            $percent = null;
            if (isset($rule['percentage'])) {
                $percent = (float) $rule['percentage'];
            } elseif (isset($rule['percent'])) {
                $percent = (float) $rule['percent'];
            }

            if ($percent !== null) {
                $providerAmount += max(0.0, ($percent / 100.0) * $grossValue);
                continue;
            }

            // Valor fixo
            if (isset($rule['amount'])) {
                $providerAmount += (float) $rule['amount'];
                continue;
            }
            if (isset($rule['value'])) {
                $providerAmount += (float) $rule['value'];
                continue;
            }
        }

        // Se não achou nada válido nas regras, retorna 0 (quem chama aplica fallback)
        // Se achou, mas deu zero por valores inconsistentes, retorna 0 para cair em fallback também.
        $providerAmount = round($providerAmount, 2);

        // Sanidade: se calculado for muito maior que o total/gross, limita
        if ($providerAmount > $grossValue) {
            Log::warning('Pix IPag: Valor de split calculado para provider excede o valor bruto; ajustando para grossValue.');
            $providerAmount = $grossValue;
        }

        // Se não achar nada, quem chama usará $defaultProviderVal
        return $providerAmount > 0 ? $providerAmount : 0.0;
    }
}
