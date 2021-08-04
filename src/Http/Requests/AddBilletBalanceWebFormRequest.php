<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\CheckUserToken;
use App\Rules\CheckUserId;
use User, Payment;

class AddBilletBalanceWebFormRequest extends FormRequest
{

    public $user;

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
            'value'                 => ['required', 'numeric']
		];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {

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