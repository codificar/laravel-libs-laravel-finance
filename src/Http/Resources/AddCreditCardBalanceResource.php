<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCreditCardBalanceResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="AddCreditCardBalanceResource",
 *      type="object",
 *      description="Restorna os dados com os cartões cadastrados pelo cliente e o saldo em carteira",
 *      title="Get Cards Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/AddCreditCardBalanceResource"),
 *          @OA\Schema(
 *              required={"success"},
 *              @OA\Property(property="success", format="boolean", type="boolean"),
 *              @OA\Property(property="payments", format="array", type="array", items="object"),
 *              @OA\Property(property="error", format="boolean", type="boolean")
 *          )
 *      }
 * )
 */
class AddCreditCardBalanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'success'           => $this['success'],
            'current_balance'   => $this['current_balance'],
            'error'             => $this['error']
        ];
    }
}