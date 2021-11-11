<?php

namespace Codificar\Finance\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Transaction, Invoice;
use App\Jobs\SubscriptionBilletPaid;
use PaymentFactory;
use Finance, Settings;

class GatewayPostbackController extends Controller
{
    /**
     * Recebe uma notificacao quando o status da transacao (boleto ou pix) e alterado
     */
    public function postbackBillet($transactionid, Request $request)
    {
        $transaction = Transaction::find($transactionid);

        //se nao encontrou a transaction (ex: gateway juno que todos postbacks tem a mesma rota), tenta pegar ela pelos parametros enviados pelo gateway ($request)
        if(!$transaction) {
            $gateway = PaymentFactory::createGateway();
            $billetVerify = $gateway->billetVerify($request, $transactionid);
            if($billetVerify['transaction_id']) {
                $transaction = Transaction::find($billetVerify['transaction_id']);
            }
        }
        
        //se a transaction e referente a confirmacao de pagamento pix
        if($transaction && isset($transaction->pix_copy_paste) && $transaction->pix_copy_paste) {
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                $gateway = PaymentFactory::createGateway();
                $res = $gateway->retrievePix($transaction->gateway_transaction_id);
                if($res && $res['paid']) {
                    $finance = Finance::createCustomEntry($transaction->ledger_id, Finance::SEPARATE_CREDIT, "Pagamento Pix", $transaction->gross_value, null, null);
                    $transaction->status = 'paid';
                    $transaction->save();
                }
            }
        } else { //se o postback e referente a confirmacao de pagamento boleto
            $gateway = PaymentFactory::createGateway();
            $billetVerify = $gateway->billetVerify($request, $transactionid);
        
            if ($transaction && $transaction->ledger_id && $billetVerify['success'] && $billetVerify['status'] == 'paid') {
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
        }
    }
}
