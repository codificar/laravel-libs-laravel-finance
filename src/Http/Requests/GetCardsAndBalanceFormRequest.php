<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Rules\CheckUserToken;
use App\Rules\CheckUserId;
use User, Payment;

class GetCardsAndBalanceFormRequest extends FormRequest
{

    public $user;
    public $cardCount;

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

        $this->user = User::whereId($this->id)->whereToken($this->token)->first();
        
        return [
			'token'                 => ['required', 'string', new CheckUserToken($this->user) ],
            'id'                    => ['required', 'integer', new CheckUserId($this->user)],
            // 'card_count'            => ['required']
		];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {

        if ($this->card_id) {
			Payment::where('user_id', $this->id)->update(array('is_default' => 0));
			Payment::where('user_id', $this->id)->where('id', $this->card_id)->update(array('is_default' => 1));
        }
        
        // Retorna a quantidade de cartões cadasrados
        $this->cardCount = Payment::countUserPayments($this->id);

        if ($this->cardCount == 0) {
            $this->cardCount = '';
        }

        $this->merge([
            'card_count'   => $this->cardCount
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
            ]
        ));
    }
}