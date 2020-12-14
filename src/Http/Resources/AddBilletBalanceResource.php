<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddBilletBalanceResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="AddBilletBalanceResource",
 *      type="object",
 *      description="Gera boleto para adicionar saldo em conta",
 *      title="Get Cards Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/AddBilletBalanceResource"),
 *          @OA\Schema(
 *              required={"success"},
 *              @OA\Property(property="success", format="boolean", type="boolean"),
 *              @OA\Property(property="payments", format="array", type="array", items="object"),
 *              @OA\Property(property="error", format="boolean", type="boolean")
 *          )
 *      }
 * )
 */
class AddBilletBalanceResource extends JsonResource
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
            'billet_url'        => $this['billet_url'],
            'error'             => $this['error']
        ];
    }
}