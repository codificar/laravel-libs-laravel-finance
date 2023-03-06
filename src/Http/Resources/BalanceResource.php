<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BalanceResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="BalanceResource",
 *      type="object",
 *      description="Gera boleto para adicionar saldo em conta",
 *      title="Get Cards Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/BalanceResource"),
 *          @OA\Schema(
 *              required={"success"},
 *              @OA\Property(property="success", format="boolean", type="boolean"),
 *              @OA\Property(property="balance", format="string", type="string"),
 *              @OA\Property(property="error", format="string", type="string")
 *          )
 *      }
 * )
 */
class BalanceResource extends JsonResource
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
            'success'       => $this['success'],
            'balance'       => $this['balance'],
            'balance_value' => $this['balance'] ?? currency_format(currency_converted($this['balance'])),
            'error'         => $this['error']
        ];
    }
}