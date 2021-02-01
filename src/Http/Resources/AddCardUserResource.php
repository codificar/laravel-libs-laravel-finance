<?php

namespace Codificar\Finance\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AddCardUserResource
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 *
 * @OA\Schema(
 *         schema="AddCardUserResource",
 *         type="object",
 *         description="Retorno da criação de um cartão pelo Usuário",
 *         title="Add Card User Resource",
 *        allOf={
 *           @OA\Schema(ref="#/components/schemas/AddCardUserResource"),
 *           @OA\Schema(
 *              required={"success", "data"},
 *               @OA\Property(property="success", format="boolean", type="boolean"),
 *               @OA\Property(property="data", format="object", type="object")
 *           )
 *       }
 * )
 */
class AddCardUserResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'success' => true,
            'data' => array($this->getData())
        ];
    }

}
