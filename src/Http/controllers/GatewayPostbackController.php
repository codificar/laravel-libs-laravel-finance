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
     * Recebe uma notificacao quando o status da transacao boleto muda
     */
    public function postbackBillet($transactionid, Request $request)
    {
        $gateway = PaymentFactory::createGateway();
        $billetVerify = $gateway->billetVerify($request, $transactionid);
        
        if($billetVerify['transaction_id']) {
            $transaction = Transaction::find($billetVerify['transaction_id']);
        } else {
            $transaction = Transaction::find($transactionid);
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
    }

    /**
     * Recebe uma notificacao quando o status da transacao pix muda
     * Obs: alguns gateways (ex: juno) o postback do pix eh o mesmo do boleto, pois o gateway nao permite ter endpoints diferentes. Por isso, nesses gateways, o postack do pix nao e chamado aqui, e sim no metodo postbackBillet
     */
    public function postbackPix($transactionid, Request $request)
    {
        #todo
        
    }
}
