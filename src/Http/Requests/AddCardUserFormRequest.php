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
        $this->cardHolder = request()->card_holder;
        $this->cardNumber = str_replace('-', '', request()->card_number);
        $this->cardCvv = request()->card_cvv;
        $this->cardExpMonth = request()->card_expiration_month;
        $this->cardExpYear = request()->card_expiration_year;
        $this->carDate = $this->cardExpMonth . '/' . $this->cardExpYear;

        if (request()->card_type) {
            $this->cardType = strtoupper(request()->card_type);
        } else {
            $this->cardType = detectCardType($this->cardNumber);
        }

        return [
            'card_holder' => 'required',
            'card_number' => ['required', new CardNumber],
            'expiration_year' => ['required', new CardExpirationYear($this->get('expiration_month'))],
            'card_expiration_month' => ['required', new CardExpirationMonth($this->get('expiration_year'))],
            'card_cvv' => ['required', new CardCvc($this->get('card_number'))]
        ];
    }

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

}
