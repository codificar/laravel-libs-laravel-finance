<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class GetFinancialSummaryByTypeAndDateResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *         schema="GetFinancialSummaryByTypeAndDateResource",
 *         type="object",
 *         description="Retorno da busca do extrato de contas com datas pré-definidas e filtros",
 *         title="Get Financial Summary By Type And Date Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/GetFinancialSummaryByTypeAndDateResource"),
 *           @OA\Schema(
 *              required={"balance"},
 *               @OA\Property(property="balance", format="object", type="object")
 *           )
 *       }
 * )
 */
class GetFinancialSummaryByTypeAndDateResource extends JsonResource
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
            'success'           => true,
            'balance'           => $this['balance'],
            'provider_prepaid'  => $this['provider_prepaid']
        ];
    }
}