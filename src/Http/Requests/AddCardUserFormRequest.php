<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class AddCardUserFormRequest
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 */
class AddCardUserFormRequest extends FormRequest {

    public $cardHolder;
    public $cardNumber;
    public $cardCvv;
    public $cardExpMonth;
    public $cardExpYear;
    public $carDate;
    public $cardType;
    public $document;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'card_holder' => 'required',
            'card_number' => 'required',
            'card_expiration_year' => 'required',
            'card_expiration_month' => 'required',
            'card_cvv' => 'required|digits_between:3,4',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'card_holder' => '',
            'card_number' => '',
            'card_expiration_year' => '',
            'card_expiration_month' => '',
            'card_cvv' => '',
        ];
    }

    /**
     * Retorna um json caso a validação falhe.
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator) {
        throw new HttpResponseException(
        response()->json([
            'success' => false,
            'errors' => $validator->errors()->all(),
            'error_code' => \ApiErrors::REQUEST_FAILED
        ]));
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(){ 
        $replaceDocument = array(".","/","-");
        $this->cardHolder = request()->card_holder;
        $this->cardNumber = str_replace('-', '', request()->card_number);
        $this->cardCvv = request()->card_cvv;
        $this->cardExpMonth = request()->card_expiration_month;
        $this->cardExpYear = request()->card_expiration_year;
        $this->carDate = $this->cardExpMonth . '/' . $this->cardExpYear;
        $this->document = str_replace($replaceDocument,'',request()->document);

        if (request()->card_type) {
            $this->cardType = strtoupper(request()->card_type);
        } else {
            $this->cardType = detectCardType($this->cardNumber);
        }

        $this->merge([
            'cardHolder' => $this->cardHolder,
            'cardNumber' => $this->cardNumber,
            'cardExpYear' => $this->cardExpYear,
            'cardExpMonth' => $this->cardExpMonth,
            'cardCvv' =>  $this->cardCvv,
            'document' =>  $this->document,
        ]);

    }

}
