<?php

namespace Codificar\Finance\Http\Requests;

use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use User;

class BalanceFormRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        
        return [
			'token' => ['required', 'string'],
            'id'    => ['required', 'integer']
		];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->holder = null;
        $id = null;
        if($this->user_id) {
            $this->holder = \User::find($this->user_id);
            $id = $this->holder->id;
        } else if($this->provider_id) {
            $this->holder = \Provider::find($this->provider_id);
            $id = $this->holder->id;
        }
        // Retorno das regras necessárias
        $this->merge([
            'holder' => $this->holder,
            'id' => $id,
        ]);
    }

    /**
     * Verifica se a validação falhar e retorna um Json
     *
     * @return Json
     */     
    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(
        response()->json(
            [
                'success' => false,
                'errors' => $validator->errors()->all(),
                'error_code' => \ApiErrors::REQUEST_FAILED
            ], \ApiErrors::REQUEST_FAILED
        ));
    }
}