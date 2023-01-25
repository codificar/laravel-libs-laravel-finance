<?php

namespace Codificar\Finance\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Log, Response;
use Transaction, Invoice;
use App\Jobs\SubscriptionBilletPaid;
use PaymentFactory;
use Finance, Settings, Requests;
use Codificar\Finance\Events\PixUpdate;
use Codificar\LaravelSubscriptionPlan\Models\Plan;

class GatewayPostbackController extends Controller
{
    /**
     * Recebe uma notificacao quando o status da transacao boleto muda
     */
    public function postbackBillet($transactionid, Request $request)
    {
        \Log::notice('transactionid: ' 
            . $transactionid 
            .  ' - postbackBillet: ' 
            .  json_decode($request)
        );
        
        if($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        if($request->id){
           $transactionid = $request->id;
        }
        
        $gateway = PaymentFactory::createGateway();
        $billetVerify = $gateway->billetVerify($request, $transactionid);
        if($transactionid && is_numeric($transactionid)) {
            $transaction = Transaction::find($transactionid);
        } else {
            $transaction = Transaction::find($billetVerify['transaction_id']);
        }
        if(!$transaction){
            $transaction = Transaction::where(["gateway_transaction_id"=>$transactionid])->first();
        }
       
        if ($transaction && $transaction->ledger_id && $billetVerify['success'] && $billetVerify['status'] == 'paid') {
            //Check if the transaction status is not paid. If is paid, so we cant add a balance value for user again
            //Check se a transaction esta com status diferente de "pago", para evitar pagar em duplicidade. 
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                $tax = Settings::findByKey('prepaid_tax_billet');
                $tax = $tax ? (float) $tax : 0;
                //Add balance for user
                $finance = Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Credito referente ao boleto pago", $transaction->gross_value - $tax, null, null);
                $transaction->status = 'paid';
                $transaction->save();
            }
        }

        // resposta 200 para o gateway saber que deu certo
        return Response::json(["success" => true], 200);
    }

    /**
     * Recebe uma notificacao quando o status da transacao pix e alterada
     */
    public function postbackPix($transactionid, Request $request)
    {
        if($request && $request->method() == 'GET') {
            return Response::json(["success" => true], 200);
        }

        $gatewayPix = Settings::getDefaultPaymentPix();

        if($gatewayPix == 'ipag') {
            $this->postbackPixIpag($request);
        } else if($gatewayPix == 'juno'){
            $this->postbackPixJuno($transactionid, $request);
        }
    }

    /**
     * Recebe uma notificacao quando o status da transacao pix do IPag e alterada
     */
    private function postbackPixIpag(Request $request)
    {
        $request = $request->all();

        if( isset($request['id']) && !empty($request['id'])) {
            $transaction = Transaction::getTransactionByGatewayId($request['id']);
            $isSuccesss = $request['attributes']['status']['code'] == '8';
            
            if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste && $isSuccesss) {
                //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
                if($transaction->status != "paid") {
                    // Agora podemos dar baixa no pix
                    
                    // se a transacao e referente a uma request
                    if($transaction->request_id) {
                        $request = Requests::find($transaction->request_id);
                        $request->is_paid = 1;
                        $request->save();

                        //gera saldo para o motorista
                        if ($request->confirmedProvider && $request->confirmedProvider->Ledger) {
                            Finance::createRideCredit($request->confirmedProvider->Ledger->id, $transaction->provider_value * -1, $request->id);
                        }
                    }  
                    // Atualiza os dados de transação via Pix
                    else if($transaction->signature_id) {
                        $signature = \Signature::find($transaction->signature_id);
                        if($signature) {
                            $plan = Plan::find($signature->plan_id);
                             // Define a data de expiração da assinatura
                            $period = $plan->period;
                            $period = $plan->period + \Settings::getDaysForSubscriptionRecurrency();
                            $nextExpiration = \Carbon::now()->addDays($period);

                            $signature->created_at = \Carbon::now();
                            $signature->next_expiration = $nextExpiration;
                            $signature->activity = 1;
                            $signature->save();
                        }
                    }
                    // se a transacao e pre-pago (ou seja, nao e referente a uma request) entao adiciona saldo 
                    else {
                        $finance = Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Pagamento Pix", $transaction->gross_value, null, null);
                    }
                    $transaction->status = 'paid';
                    $transaction->save();

                    // disparar evento pix
                    event(new PixUpdate($transaction->id, true, false));
                }
            }
        } else {
            Log::error($request);
            Log::error('Pix IPag: Nao foi possivel identificar a transacao');
        }
    }
    /**
     * Recebe uma notificacao quando o status da transacao pix do Juno e alterada
     */
    private function postbackPixJuno($transactionid, Request $request)
    {
        $gateway = PaymentFactory::createPixGateway();
        $retrievePix = $gateway->retrievePix($transactionid, $request);
        
        $transaction = Transaction::find($retrievePix['transaction_id']);
        
        if ($transaction && $transaction->ledger_id && $transaction->pix_copy_paste && $retrievePix['success'] && $retrievePix['paid']) {
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                // Agora podemos dar baixa no pix
                
                // se a transacao e referente a uma request
                if($transaction->request_id) {
                    $request = Requests::find($transaction->request_id);
                    $request->is_paid = 1;
                    $request->save();

                    //gera saldo para o motorista
                    if ($request->confirmedProvider && $request->confirmedProvider->Ledger) {
                        Finance::createRideCredit($request->confirmedProvider->Ledger->id, $transaction->provider_value * -1, $request->id);
                    }
                } 
                // se a transacao e pre-pago (ou seja, nao e referente a uma request) entao adiciona saldo 
                else {
                    $finance = Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Pagamento Pix", $transaction->gross_value, null, null);
                }
                $transaction->status = 'paid';
                $transaction->save();

                // disparar evento pix
                event(new PixUpdate($transaction->id, true, false));
            }
        }
    }
}
