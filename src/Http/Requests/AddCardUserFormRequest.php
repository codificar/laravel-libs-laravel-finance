<?php

namespace Codificar\Finance\Http\Requests;

use LVR\CreditCard\CardCvc as CardCvc;
use LVR\CreditCard\CardNumber as CardNumber;
use LVR\CreditCard\CardExpirationYear as CardExpirationYear;
use LVR\CreditCard\CardExpirationMonth as CardExpirationMonth;
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
            'cardHolder' => 'required',
            'cardNumber' => ['required', new CardNumber],
            'cardExpYear' => ['required', new CardExpirationYear($this->cardExpMonth)],
            'cardExpMonth' => ['required', new CardExpirationMonth($this->cardExpYear)],
            'cardCvv' => ['required', new CardCvc($this->cardNumber)]
        ];
    }

    public function messages() {
        return [
            'cardHolder' => '',
            'cardNumber' => '',
            'cardExpYear' => '',
            'cardExpMonth' => '',
            'cardCvv' => '',
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

    protected function prepareForValidation(){
        $holder = "";
        $number = "";
        $ccv = "";
        $cardDate = "";
        $cardExpirationMonth = "";
        $cardExpirationYear = "";

        if($this->name){
            $holder = $this->name;
        }else if (request()->card_holder){
            $holder = request()->card_holder;
        }

        if($this->number){
            $number = $this->number;
        }else if (request()->card_number){
            $number = str_replace('-', '', request()->card_number);
        }

        if($this->cvc){
            $ccv = $this->cvc;
        }else if (request()->card_cvv){
            $ccv = request()->card_cvv;
        }

        if($this->expiry){
            $cardDate = str_replace(' ', '',$this->expiry);
            if(strpos($cardDate, '/') > 0){
                list($cardExpirationMonth, $cardExpirationYear) = explode('/', $cardDate);
                $cardExpirationMonth = intval($cardExpirationMonth);
                $cardExpirationYear = intval($cardExpirationYear);
            }
        }else if (request()->card_expiration_month){
            $cardExpirationMonth = request()->card_expiration_month;
            $cardExpirationYear = request()->card_expiration_year;
        }

        $this->cardHolder = $holder;
        $this->cardNumber = $number;
        $this->cardCvv = $ccv;
        $this->cardExpMonth =  $cardExpirationMonth;
        $this->cardExpYear =  $cardExpirationYear;
        $this->carDate = $this->cardExpMonth . '/' . $this->cardExpYear;

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
        ]);
    }

}
