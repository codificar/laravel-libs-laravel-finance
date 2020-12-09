<?php

namespace Codificar\Finance\Http\Requests;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use App\Rules\CheckHolder;
use Finance;
use User;
use Provider;
use Input;

class GetProviderSummaryByTypeAndDateFormRequest extends FormRequest
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
            'holder_type' => 'required|in:user,provider,corp',
        ];
    }

    public function messages() 
    {
        return [
            'holder_type' => trans('finance.holder_type_is_required'),
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $holder_id = Input::get('id') ? Input::get('id') : Input::get('provider_id');

        // Verifica onde buscar de acordo com o caminho
        switch ($this->holder_type) {
            case Finance::TYPE_USER:
                $this->holder = User::find($holder_id);
                break;
            case Finance::TYPE_PROVIDER:
                $this->holder = Provider::find($holder_id);
                break;
            case Finance::TYPE_CORP:
                $this->holder = \AdminInstitution::getUserByAdminId(\Auth::guard("web")->user()->id);
                break;
            default:
                $this->holder = null;
        }

        // Verificar filtro para "reason" da finance
        if ($this->type_entry != '0') 
            $this->type_entry = $this->type_entry;
        else 
            $this->type_entry = '';        

        // Define data inicial
        if ($this->start_date != '') {
            $this->start_date = Carbon::createFromFormat('d/m/Y', $this->start_date)->format('Y-m-d 00:00:00');
        } else {
            // Utiliza a data atual, caso não seja informada
            $this->start_date = Carbon::today();

            // Define o tempo em minutos, segundos e milésimos
			$this->start_date->setTime(0, 0, 0);
        }

        // Define a data final
        if ($this->end_date != '') {
            $this->end_date = Carbon::createFromFormat('d/m/Y', $this->end_date)->format('Y-m-d 23:59:59');
        } else {
            // Utiliza a data atual, caso não seja informada
            $this->end_date = Carbon::today();

            // Define o tempo em minutos, segundos e milésimos
            $this->end_date->setTime(23, 59, 59);
        }

		// Obtém a página enviada
		$this->page = $this->page ? $this->page : 1;
		
		// Obtém os itens à serem exibidos por página
        $this->itemsPerPage = $this->itemsPerPage ? $this->itemsPerPage : 20;   

        // Retorno das regras necessárias
        $this->merge([
            'holder_id' => $holder_id,
            'holder' => $this->holder,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'type_entry' => $this->type_entry,
            'page' => $this->page,
            'itemsPerPage' => $this->itemsPerPage,
        ]);
    }    

    /**
     * Caso a validação falhe, retorna os itens de erro
     * 
     * @return Json
     */
    protected function failedValidation(Validator $validator) 
    {   
        // Pega as mensagens de erro     
        $error_messages = $validator->errors()->all();

        // Exibe os parâmetros de erro
        throw new HttpResponseException(
        response()->json(
            [
                'success' => false,
                'error' => $error_messages[0],
                'error_code' => \ApiErrors::REQUEST_FAILED,
                'error_messages' => $error_messages,
            ]
        ));
    }
}