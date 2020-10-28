<?php

namespace Codificar\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


// Importar models
use Codificar\Finance\Models\LibModel;

//FormRequest
use Codificar\Finance\Http\Requests\ProviderProfitsRequest;
use Codificar\Finance\Http\Requests\GetFinancialSummaryByTypeAndDateFormRequest;

//Resource
use Codificar\Finance\Http\Resources\ProviderProfitsResource;
use Codificar\Finance\Http\Resources\GetFinancialSummaryByTypeAndDateResource;

use Input, Validator, View, Response;
use Provider, Settings, Ledger, Finance, Bank, LedgerBankAccount, Requests, ProviderAvail;

class FinanceController extends Controller {


    /**
     * @api {GET} /libs/finance/provider/profits
     * @description Retorna as informações financeiras por ano
     * @param ProviderProfitsRequest $request
	 * @return ProviderProfitsResource
     */
    public function getProviderProfits(ProviderProfitsRequest $request)
    {
		$ledgerId = $request->provider->ledger->id;
		$finance = Requests::getProviderProfitsOfWeek($request->provider->id);
		$totalMoney = Requests::getProviderProfitsOfWeekMoneyValue($request->provider->id);
		$currentBalance = Finance::sumValueByLedgerId($ledgerId);
		$isWithdrawEnabled = Settings::getWithDrawEnabled();
		
		return new ProviderProfitsResource([
			"finance" => $finance,
			"total_money" => $totalMoney,
			"current_balance" => $currentBalance,
			"available" => ProviderAvail::getWeekOnlineTime($request->provider->id),
			"rides" => Requests::getWeekRidesCount($request->provider->id),
			"is_withdraw_enabled" => $isWithdrawEnabled
		]);
    }
    
     /**
     * @api {get} /libs/finance/provider/provider/financial/summary/{id}
     * @apiDescription Permite buscar o extrato de contas com datas pré-definidas e filtros
     * @return Json
     */	
	public function getFinancialSummaryByTypeAndDate(GetFinancialSummaryByTypeAndDateFormRequest $request)
	{
        // Pega holder
		$holder = $request->holder;
		
        // Realiza busca do extrato
        $balance = Finance::getLedgerDetailedBalanceByPeriod(
			$holder->ledger->id, 
			$request->typeEntry, 
			$request->start_date, 
			$request->end_date, 
			$request->page,
			$request->itemsPerPage
		);

        // Retorno de dados
        return new GetFinancialSummaryByTypeAndDateResource(['balance' => $balance]);
	}

}