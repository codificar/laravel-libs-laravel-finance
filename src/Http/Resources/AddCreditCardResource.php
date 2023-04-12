<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCreditCardResource
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 *
 * @OA\Schema(
 *         schema="AddCreditCardResource",
 *         type="object",
 *         description="Retorno da criação de um cartão pelo Usuário",
 *         title="Add Card User Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/AddCreditCardResource"),
 *           @OA\Schema(
 *              required={"success", "data"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="message", format="string", type="string")
 *               @OA\Property(property="payment", format="object", type="object")
 *               @OA\Property(property="error_code", format="integer", type="integer")
 *               @OA\Property(property="error", format="string", type="string")
 *               @OA\Property(property="toRemove", format="string", type="string")
 *               @OA\Property(property="setDEfault", format="string", type="string")
 *           )
 *       }
 * )
 */
class AddCreditCardResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) 
    {
        $message = '';
        $paymentData = array();
        $errorCode = null;
        $error = '';

        if($this->resource['success']){
            $payment = $this->resource['payment'];
			$message = trans('user_provider_controller.card_add');
			$paymentData = $payment->getData();
		} else {
            $errorCode = 429;
			$error = array(trans($this->resource['message'] ?? 'error'));
		}

        return [
            'success' => $this->resource['success'],
            'message' => $message,
            'payment' => $paymentData,
            'error_code' => $errorCode,
            'error' => $error,
            'toRemove' => \URL::Route('AdminUserRemovePayment'),
            'setDefault' => \URL::Route('AdminUserDefaultPayment')
        ];
    }

}
