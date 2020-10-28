<?php

namespace Codificar\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


// Importar models
use Codificar\Finance\Models\Finance;

// Importar Resource
use Codificar\Finance\Http\Resources\TesteResource;


use Input, Validator, View, Response;
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount;

class FinanceController extends Controller {

    

    /**
     * View the finance report
     * 
     * @return View
     */
    public function getExampleVuejs() {

        $adminsList = Finance::getAdminList();

        return View::make('finance::example_vuejs')
                    ->with([
                        'admins_list' => $adminsList
                    ]);
    
    }


    public function getAppApiExample()
    {
        $teste = "Variavel teste";
        
        // Return data
		return new TesteResource([
			'teste' => $teste
		]);
    }

}