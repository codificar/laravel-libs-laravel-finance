<?php

namespace Codificar\Finance\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Transaction, Invoice;
use App\Jobs\SubscriptionBilletPaid;
use PaymentFactory;
use Finance;

class GatewayPostbackController extends Controller
{
    /**
     * Recebe uma notificação quando o status da transacao (boleto) muda
     */
    public function postbackBillet($ledgerid, Request $request)
    {
        $gateway = PaymentFactory::createGateway();
        $postbackTransaction = $gateway->billetVerify($request);
        
        $transaction = Transaction::getTransactionByGatewayId($postbackTransaction['transaction_id']);

        if ($ledgerid && $transaction && $postbackTransaction['success'] && $postbackTransaction['status'] == 'paid') {
            //Check if the transaction status is not paid. If is paid, so we cant add a balance value for user again
            //Check se a transaction esta com status diferente de "pago", para evitar pagar em duplicidade. 
            //Se a transaction ja esta com status pago, nao faz sentido adicionar um saldo para o usuario novamente
            if($transaction->status != "paid") {
                //Add balance for user
                $finance = Finance::createCustomEntry($ledgerid, Finance::SEPARATE_CREDIT, "Credito referente ao boleto pago", $postbackTransaction['value'], null, null);
                $transaction->status = 'paid';
                $transaction->save();
            
            }
        }
    }
}
