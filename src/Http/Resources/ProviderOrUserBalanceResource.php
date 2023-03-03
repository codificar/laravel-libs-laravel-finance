<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderOrUserBalanceResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="ProviderOrUserBalanceResource",
 *      type="object",
 *      description="Gera boleto para adicionar saldo em conta",
 *      title="Get Cards Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/ProviderOrUserBalanceResource"),
 *          @OA\Schema(
 *              required={"success"},
 *              @OA\Property(property="success", format="boolean", type="boolean"),
 *              @OA\Property(property="balance", format="string", type="string"),
 *              @OA\Property(property="error", format="string", type="string")
 *          )
 *      }
 * )
 */
class ProviderOrUserBalanceResource extends JsonResource
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
            'success'   => $this['success'],
            'balance'  => $this['balance'],
            'error'     => $this['error']
        ];
    }
}