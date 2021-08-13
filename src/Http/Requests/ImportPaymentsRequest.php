<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Provider;

class ImportPaymentsRequest extends FormRequest
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
			'file' => ['required', 'file'],
            'delimeter' => 'required',
            'date_format' => 'required'
		];
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
            ]
        ));
    }
}