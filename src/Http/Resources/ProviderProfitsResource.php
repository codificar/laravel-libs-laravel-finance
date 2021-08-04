<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ProviderProfitsResource
 *
 * @package Uber Clone
 *
 * @OA\Schema(
 *      schema="ProviderProfitsResource",
 *      type="object",
 *      description="Retorna os ganhos a cada mês no ano informado",
 *      title="Profits Resource",
 *      allOf={
 *          @OA\Schema(ref="#/components/schemas/ProviderProfitsResource"),
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
class ProviderProfitsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        
        $total = 0;
        
		foreach ($this["finance"] as $item) {
			$total += $item['value'];
        }

        $total = number_format($total, 2, '.', '');
        $totalMoney = number_format($this["total_money"], 2, '.', '');

		return [
			"success" => true,
			"finance" => $this["finance"],
			"current_balance" => $this["current_balance"],
			"current_balance_text" => currency_format(currency_converted($this["current_balance"])),
            "total_week" => $total,
            "total_week_text" => currency_format(currency_converted($total)),
            "total_money_week" => $totalMoney,
            "total_money_week_text" => currency_format(currency_converted($totalMoney)),
            "online_time" => $this["available"],
            "online_time_text" => formatTime($this["available"]),
            "rides_count" => $this["rides"],
            "is_withdraw_enabled" => $this["is_withdraw_enabled"],
		];
    }
}
