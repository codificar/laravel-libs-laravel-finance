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
    public $userId;
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
            'cardHolder' => 'required',
            'cardNumber' => ['required', new CardNumber],
            'cardExpYear' => ['required', new CardExpirationYear($this->cardExpMonth)],
            'cardExpMonth' => ['required', new CardExpirationMonth($this->cardExpYear)],
            'cardCvv' => ['required', new CardCvc($this->cardNumber)],
            'userId' => 'required'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages() {
        return [
            'cardHolder.required' => trans('financeTrans::finance.holder_error'),
            'cardNumber.required' => trans('financeTrans::finance.number_error'),
            'cardExpYear.required' => trans('financeTrans::finance.data_error'),
            'cardExpMonth.required' => trans('financeTrans::finance.data_error'),
            'cardCvv.required' => trans('financeTrans::finance.cvc_error'),
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
            'errors' => self::errorTrait($validator),
            'error_code' => \ApiErrors::REQUEST_FAILED
        ]));
    }

    protected function errorTrait($validator){
        if ($validator->messages()->messages()){
            return array(
                trans('financeTrans::finance.error_card')
            );
        }
        return $validator->errors()->all();
    }

    protected function prepareForValidation(){
        $holder = "";
        $number = "";
        $ccv = "";
        $cardDate = "";
        $cardExpirationMonth = "";
        $cardExpirationYear = "";
        $userId = "";
        
        if (request()->card_type) {
            $this->cardType = strtoupper(request()->card_type);
        } else {
            $this->cardType = detectCardType($this->cardNumber);
        }

        if($this->name){
            $holder = $this->name;
        }else if (request()->card_holder){
            $holder = request()->card_holder;
        }

        if($this->user_id){
            $userId = $this->user_id;
        }else if (request()->user_id){
            $userId = request()->user_id;
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

        $carDate = $cardExpirationMonth . '/' . $cardExpirationYear;
        $document = str_replace(array(".","/","-"),'',request()->document);
        

        $this->cardHolder = $holder;
        $this->cardNumber = $number;
        $this->cardCvv = $ccv;
        $this->cardExpMonth =  $cardExpirationMonth;
        $this->cardExpYear =  $cardExpirationYear;
        $this->userId = $userId ;
        $this->document = $document ;
        

        $this->merge([
            'cardHolder' => $this->cardHolder,
            'cardNumber' => $this->cardNumber,
            'cardExpYear' => $this->cardExpYear,
            'cardExpMonth' => $this->cardExpMonth,
            'cardCvv' =>  $this->cardCvv,
            'userId' =>  $this->userId,
            'document' =>  $this->document,
        ]);

    }

}
