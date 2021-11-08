<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddPixBalanceResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="AddPixBalanceResource",
 *      type="object",
 *      description="Gera pix para adicionar saldo em conta",
 *      title="new pix",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/AddPixBalanceResource"),
 *          @OA\Schema(
 *              required={"success"},
 *              @OA\Property(property="success", format="boolean", type="boolean"),
 *              @OA\Property(property="copy_and_paste", format="array", type="array", items="object"),
 *              @OA\Property(property="qr_code_base64", format="array", type="array", items="object")
 *          )
 *      }
 * )
 */
class AddPixBalanceResource extends JsonResource
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
            'copy_and_paste'    => $this['copy_and_paste'],
            'qr_code_base64'    => $this['qr_code_base64']
        ];
    }
}