<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddBancardCreditCardProviderFormRequest extends FormRequest
{

    public $providerId;

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
    public function rules() {
        return [
            'providerId' => 'required'
        ];
    }

    /**
     * If validation fails, returns error items
     * 
     * @return Json
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        $response = [
            'success' => false,
            'error' => 'Validation failed',
            'error_code' => \ApiErrors::BAD_REQUEST,
            'error_details' => $errors,
        ];

        throw new HttpResponseException(response()->json($response));
    }

    protected function prepareForValidation(){

        $providerId = "";

        if($this->provider_id){
            $providerId = $this->provider_id;
        }else if (request()->provider_id){
            $providerId = request()->provider_id;
        }

        $this->providerId = $providerId;
        
        $this->merge([
            'providerId' =>  $this->providerId,
        ]);

    }
}
