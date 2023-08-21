<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class AddCardProviderFormRequest
 *
 * @package MotoboyApp
 *
 * @author  André Gustavo <andre.gustavo@codificar.com.br>
 */
class AddCardProviderFormRequest extends FormRequest {

    public $cardHolder;
    public $cardNumber;
    public $cardCvv;
    public $cardExpMonth;
    public $cardExpYear;
    public $cardDate;
    public $cardType;
    public $providerId;
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
            'cardNumber' => ['required'],
            'cardExpYear' => ['required'],
            'cardExpMonth' => ['required'],
            'cardCvv' => ['required'],
            'document' => [''],
            'providerId' => 'required'
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
        $providerId = "";
        
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

        if($this->provider_id){
            $providerId = $this->provider_id;
        }else if (request()->provider_id){
            $providerId = request()->provider_id;
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

        $cardDate = $cardExpirationMonth . '/' . $cardExpirationYear;
        $document = str_replace(array(".","/","-"),'',request()->document);
        

        $this->cardHolder = $holder;
        $this->cardNumber = $number;
        $this->cardCvv = $ccv;
        $this->cardExpMonth =  $cardExpirationMonth;
        $this->cardExpYear =  $cardExpirationYear;
        $this->providerId = $providerId;
        $this->document = $document ;
        $this->cardDate = $cardDate ;
        

        $this->merge([
            'cardHolder' => $this->cardHolder,
            'cardNumber' => $this->cardNumber,
            'cardExpYear' => $this->cardExpYear,
            'cardExpMonth' => $this->cardExpMonth,
            'cardCvv' =>  $this->cardCvv,
            'providerId' =>  $this->providerId,
            'document' =>  $this->document,
            'cardDate' =>  $this->cardDate,
        ]);

    }

}
