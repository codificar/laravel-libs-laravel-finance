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
use RequestCharging;

class GatewayPostbackController extends Controller
{
    /**
     * Recebe uma notificacao quando o status da transacao boleto muda
     */
    public function postbackBillet($transactionId, Request $request)
    {   
        if($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        if($request->id){
           $transactionId = $request->id;
        }

        //Verifica se essa transação é um pix
        $transaction = Transaction::getTransactionByGatewayId($transactionId);
        if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste) {
            return $this->postbackPix($transaction->id, $request);
        }
        
        $gateway = LibsPaymentFactory::createGateway();
        $billetVerify = $gateway->billetVerify($request, $transactionId);
        if($transactionId && is_numeric($transactionId)) {
            $transaction = Transaction::find($transactionId);
        } else {
            $transaction = Transaction::find($billetVerify['transaction_id']);
        }
        if(!$transaction){
            $transaction = Transaction::where(["gateway_transaction_id"=>$transactionId])->first();
        }
       
        if ($transaction && $transaction->ledger_id && $billetVerify['success'] && $billetVerify['status'] == 'paid') {
            //Check if the transaction status is not paid. If is paid, so we cant add a balance value for user again
            //Check se a transaction esta com status diferente de "pago", para evitar pagar em duplicidade. 
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                $tax = Settings::findByKey('prepaid_tax_billet');
                $tax = $tax ? (float) $tax : 0;
                //Add balance for user
                Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Credito referente ao boleto pago", $transaction->gross_value - $tax, null, null);
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
        if($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        $gatewayPix = Settings::getDefaultPaymentPix();

        if($gatewayPix == 'ipag') {
            $this->postbackPixIpag($request);
        } else if($gatewayPix == 'juno'){
            $this->postbackPixJuno($transactionId, $request);
        }
    }

    /**
     * Recebe uma notificação quando o status da transação pix do IPag e alterada
     */
    private function postbackPixIpag(Request $request)
    {
        try {
            $webhookRequest = $request->all();

            if( isset($webhookRequest['id']) && !empty($webhookRequest['id'])) {
                $transaction = Transaction::getTransactionByGatewayId($webhookRequest['id']);
                $isCaptured = Transaction::isWebhookCaptured($webhookRequest['attributes']['status']['code']);
                
                if ($isCaptured && $transaction && $transaction->ledger_id && $transaction->pix_copy_paste ) {
                    //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuário novamente
                    if(!$transaction->isPaid()) {
                        // Agora podemos dar baixa no pix
                        // se a transação e referente a uma corrida
                        if($transaction->request_id) {
                            $ride = $transaction->ride;
                            $ride->setIsPaid();

                            //gera saldo para o motorista
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
                            }
                        }  
                        // Atualiza os dados de transação via Pix
                        else if($transaction->signature_id) {
                            $signature = $transaction->signature;
                            if($signature) {
                                $signature->updatePostBackPix();
                            }
                        }
                        // se a transação é pre-pago (ou seja, nao e referente a uma request nem a uma assinatura) então adiciona saldo 
                        else {

                            if(!$transaction->finance) {
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
                            }
                        }
                        $transaction->setStatusPaid();

                        // disparar evento pix
                        event(new PixUpdate($transaction->id, true, false));
                    }
                }
            } else {
                Log::error('Pix IPag: Nao foi possível identificar a transação: ' . json_encode($webhookRequest));
            }
        } catch (\Exception $e) {
            \Log::error($e->getMessage() . $e->getTraceAsString());
        }
    }
    /**
     * Recebe uma notificacao quando o status da transacao pix do Juno e alterada
     */
    private function postbackPixJuno($transactionId, Request $ride)
    {
        $gateway = LibsPaymentFactory::createPixGateway();
        $retrievePix = $gateway->retrievePix($transactionId, $ride);
        
        $transaction = Transaction::find($retrievePix['transaction_id']);
        
        if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste && $retrievePix['success'] && $retrievePix['paid']) {
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                // Agora podemos dar baixa no pix
                
                // se a transacao e referente a uma request
                if($transaction->request_id) {
                    $ride = $transaction->ride;
                    $ride->setIsPaid();

                    //gera saldo para o motorista
                    if ($ride->confirmedProvider && $ride->confirmedProvider->Ledger) {
                        $providerValue = $transaction->provider_value * -1;
                        Finance::createRideCredit($ride->confirmedProvider->Ledger->id, $providerValue, $ride->id);
                    }
                } 
                // se a transacao e pre-pago (ou seja, nao e referente a uma request) entao adiciona saldo 
                else {
                    Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Pagamento Pix", $transaction->gross_value, null, null);
                }

                $transaction->setStatusPaid();

                // disparar evento pix
                event(new PixUpdate($transaction->id, true, false));
            }
        }
    }
}
