<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class RetrievePixResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="RetrievePixResource",
 *      type="object",
 *      description="Retorna os dados do pix salvo na tabela transaction",
 *      title="Profits Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/RetrievePixResource"),
 *          @OA\Schema(
 *              required={"success"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="finance", format="array", type="array", items="object"),
 *               @OA\Property(property="current_balance", format="integer", type="integer"),
 *               @OA\Property(property="total", format="string", type="string"),
 *          )
 *      }
 * )
 */
class RetrievePixResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $success = $this['success'];
        $isTransaction = isset($this['transaction']) ? true : false;
        if(!$success && !$isTransaction) {
            return [
                'success' => $success,
                'message' => $this['message']
            ];
        }
        
        $transaction = $this['transaction'];
        return [
            'success' 								=> $success,
            'request_id'							=> $transaction->rideId,
            'transaction_id'						=> $transaction->id,
            'transaction_type'						=> $transaction->type,
            'paid'              					=> $transaction->isPaid,
            'payment_changed'						=> $transaction->paymentChanged,
            'value'             					=> $transaction->gross_value,
            'formatted_value'						=> currency_format(currency_converted($transaction->gross_value)),
            'copy_and_paste'    					=> $transaction->pix_copy_paste,
            'qr_code_base64'    					=> $transaction->pix_base64,
            'pix_expiration_date_time'  			=> $transaction->pix_expiration_date_time,
            'pix_expiration_date_time_formated'  	=> $transaction->expiratedFormated
        ];
    }
}
