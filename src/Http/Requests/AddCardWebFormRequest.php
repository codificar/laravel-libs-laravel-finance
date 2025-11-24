<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Class AddCardWebFormRequest
 * 
 * FormRequest específico para adicionar cartão via painel web (corp/user/provider).
 * Diferente do AddCardUserFormRequest, este NÃO exige userId pois é obtido da sessão autenticada.
 * 
 * @package Codificar\Finance
 */
class AddCardWebFormRequest extends FormRequest {

    public $cardHolder;
    public $cardNumber;
    public $cardCvv;
    public $cardExpMonth;
    public $cardExpYear;
    public $cardDate;
    public $cardType;
    public $document;
    public $paymentMethodId;

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
     * Para web: userId vem da sessão, não precisa validar.
     * Valida apenas payment_method_id (Stripe) ou dados brutos (outros gateways).
     *
     * @return array
     */
    public function rules() {
        // Verificar payment_method_id em múltiplos formatos (após prepareForValidation)
        $hasPaymentMethodId = !empty($this->paymentMethodId) 
            || !empty(request()->payment_method_id) 
            || !empty(request()->paymentMethodId);
        
        $rules = [
            'cardHolder' => 'required',
            'document' => [''],
        ];

        if ($hasPaymentMethodId) {
            // Para Stripe com Payment Method ID - validar ambos formatos
            $rules['payment_method_id'] = 'required_without:paymentMethodId|string';
            $rules['paymentMethodId'] = 'required_without:payment_method_id|string';
        } else {
            // Para outros gateways (dados brutos obrigatórios)
            $rules['cardNumber'] = ['required'];
            $rules['cardExpYear'] = ['required'];
            $rules['cardExpMonth'] = ['required'];
            $rules['cardCvv'] = ['required'];
        }

        return $rules;
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
            'payment_method_id.required_without' => trans('financeTrans::finance.error_card'),
            'paymentMethodId.required_without' => trans('financeTrans::finance.error_card'),
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

    /**
     * Prepara os dados para validação.
     * Extrai e normaliza os dados do request, suportando múltiplos formatos.
     */
    protected function prepareForValidation(){
        $holder = "";
        $number = "";
        $ccv = "";
        $cardDate = "";
        $cardExpirationMonth = "";
        $cardExpirationYear = "";
        
        if (request()->card_type) {
            $this->cardType = strtoupper(request()->card_type);
        } else {
            $this->cardType = detectCardType($this->cardNumber ?? '');
        }

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

        $cardDate = $cardExpirationMonth . '/' . $cardExpirationYear;
        $document = str_replace(array(".","/","-"),'',request()->document ?? '');
        
        // Extrair payment_method_id (suporta múltiplos formatos)
        $paymentMethodId = request()->payment_method_id 
            ?? request()->paymentMethodId 
            ?? null;

        $this->cardHolder = $holder;
        $this->cardNumber = $number;
        $this->cardCvv = $ccv;
        $this->cardExpMonth =  $cardExpirationMonth;
        $this->cardExpYear =  $cardExpirationYear;
        $this->document = $document;
        $this->cardDate = $cardDate;
        $this->paymentMethodId = $paymentMethodId;

        $this->merge([
            'cardHolder' => $this->cardHolder,
            'cardNumber' => $this->cardNumber,
            'cardExpYear' => $this->cardExpYear,
            'cardExpMonth' => $this->cardExpMonth,
            'cardCvv' =>  $this->cardCvv,
            'document' =>  $this->document,
            'cardDate' =>  $this->cardDate,
            'paymentMethodId' => $this->paymentMethodId,
            'payment_method_id' => $this->paymentMethodId, // Normalizar para snake_case também
        ]);
    }
}

