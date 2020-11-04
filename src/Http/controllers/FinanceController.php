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

use Input, Validator, View, Response, Session;
use Finance, Admin, Settings, Provider, ProviderStatus;

class FinanceController extends Controller {
	use PartnerFilter;

    /**
     * @api {GET} /libs/finance/provider/profits
     * @description Retorna as informações financeiras por ano
     * @param ProviderProfitsRequest $request
	 * @return ProviderProfitsResource
     */
    public function getProviderProfits(ProviderProfitsRequest $request)
    {
		$ledgerId = $request->provider->ledger->id;
		$finance = LibModel::getProviderProfitsOfWeek($request->provider->id);
		$totalMoney = LibModel::getProviderProfitsOfWeekMoneyValue($request->provider->id);
		$currentBalance = Finance::sumValueByLedgerId($ledgerId);
		$isWithdrawEnabled = LibModel::getWithDrawEnabled();
		
		return new ProviderProfitsResource([
			"finance" => $finance,
			"total_money" => $totalMoney,
			"current_balance" => $currentBalance,
			"available" => LibModel::getWeekOnlineTime($request->provider->id),
			"rides" => LibModel::getWeekRidesCount($request->provider->id),
			"is_withdraw_enabled" => $isWithdrawEnabled
		]);
    }
    
     /**
     * @api {get} /libs/finance/provider/financial/summary/{id}
     * @apiDescription Permite buscar o extrato de contas com datas pré-definidas e filtros
     * @return Json
     */	
	public function getFinancialSummaryByTypeAndDate(GetFinancialSummaryByTypeAndDateFormRequest $request)
	{
        // Pega holder
		$holder = $request->holder;
		
        // Realiza busca do extrato
        $balance = LibModel::getLedgerDetailedBalanceByPeriod(
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




	public function providerExtract(){		
		$providers = $this->index(true);
		$providers = $providers->simplePaginate(20);
		$locations = $this->locationModel->get();
		$balances = array();
		
		foreach($providers as $provider){
			$id =  $provider->id;
			$holder = Provider::find($id);			
			if(Input::get('type-entry') != ''){
				$typeEntry = Input::get('type-entry');
			}else{
				$typeEntry = '';
			}
			if($holder && $holder->ledger){
				$startDate =  Input::has('start_date_created') ? date('Y-m-d', strtotime(Input::get('start_date_created'))) : date('Y-m-d', strtotime($holder->created_at));
				$endDate = Input::has('end_date_created') ? date("Y-m-d 23:59:59", strtotime(Input::get('end_date_created'))) : date('Y-m-d 23:59:59');
				$startDateCompensation = Input::has('start-date-compensation') ? date('Y-m-d', strtotime(Input::get('start-date-compensation'))) : date('Y-m-d', strtotime($holder->created_at));
				$endDateCompensation = Input::has('end-date-compensation') ? date("Y-m-d 23:59:59", strtotime(Input::get('end-date-compensation'))) : date('Y-m-d 23:59:59');
				$title = trans('finance.account_statement');
				array_push($balances, Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
			}else{
				$balance = array("previous_balance"=>0.0, "current_balance" => 0.0, "total_balance_by_period" => 0.0, "detailed_balance"=> array());				
				array_push($balances, $balance);
			}
		}

		// Pega o código da moeda
		$currency_code = Settings::getCurrencyCode();

		// Pega o símbolo da moeda
		$currency_symbol = Settings::getCurrencySymbol($currency_code);

		return View::make('finance::account_summary')
					->with('locations', $locations)
					->with('providers', $providers)
					->with('partners', $this->partners)
					->with('type','id')
					->with(['id' => $id, 'holder' => $holder->first_name.' '.$holder->last_name, 'ledger' => $holder, 'title' => $title, 'balances' => $balances, 'start' => $startDate, 'end' => $endDate, 'page' => 'financial'])
					->with('order',1)
					->with('currency_symbol', $currency_symbol)
					->with('balances',$balances);
	}



	public function providerExtractFilter(){
		
		$start_date_compensation = Input::get('start-date-compensation');
	  	$end_date_compensation = Input::get('end-date-compensation');
		$start_date_created = Input::get('start_date_created');
		$end_date_created = Input::get('end_date_created');
		$orderBalance = Input::get('order_balance');
		
		// Pega o código da moeda
		$currency_code = Settings::getCurrencyCode();

		// Pega o símbolo da moeda
		$currency_symbol = Settings::getCurrencySymbol($currency_code);

		$providers = $this->filter(true,$start_date_compensation, $end_date_compensation, $start_date_created, $end_date_created, $orderBalance);
		$locations = $this->locationModel->get();
		if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
			return $this->downloadExtractReport($providers);				
		}else{
			$providersss = $providers->simplePaginate(20);
			$balances = array();
			foreach($providersss as $provider){
				$id =  $provider->id;
				$holder = Provider::find($id);			
				if(Input::get('type-entry') != ''){
					$typeEntry = Input::get('type-entry');
				}else{
					$typeEntry = '';
				}
				if($holder && $holder->ledger){
					$startDate =  Input::has('start_date_created') ? 
						date('Y-m-d 00:00:00', strtotime($this->parseDate($start_date_created))) :
						date('Y-m-d', strtotime($holder->created_at));

					$endDate = Input::has('end_date_created') ? 
						date("Y-m-d 23:59:59", strtotime($this->parseDate($end_date_created))) : 
						date('Y-m-d 23:59:59');

					$startDateCompensation = Input::has('start-date-compensation') ? date("Y-m-d 0:0:0", strtotime(Input::get('start-date-compensation'))) : date('Y-m-d 23:59:59');
					$endDateCompensation = Input::has('end-date-compensation') ? date("Y-m-d 23:59:59", strtotime(Input::get('end-date-compensation'))) : date('Y-m-d 23:59:59');
					$title = trans('finance.account_statement');
					array_push($balances, Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
				}
			}
		}

		if (count($providers->simplePaginate(20)) > 0) {				
		return View::make('provider_extract.account_summary')
					->with('locations', $locations)
					->with('providers', $providersss)
					->with('partners', $this->partners)
					->with('currency_symbol', $currency_symbol)						
					->with('type','id')
					->with(['id' => $id, 'holder' => $holder->first_name.' '.$holder->last_name, 'ledger' => $holder, 'title' => $title, 'balances' => $balances, 'start' => $startDate, 'end' => $endDate, 'page' => 'financial'])
					->with('order',1)
					->with('balances',$balances);
		}else{
			return View::make('provider_extract.account_summary')
					->with('locations', $locations)
					->with('providers', $providersss)
					->with('partners', $this->partners)
					->with('currency_symbol', $currency_symbol)
					->with('type','id')						
					->with('order',1)
					->with('balances',$balances);
		}

	}


	public function parseDate($date)
	{
		try {
			$parse = DateTime::createFromFormat('d/m/Y', $date);
			return $parse->format('Y-m-d');
		} catch (\Throwable $th) {
			return date('Y-m-d');
		}
		
	}


	/**
	 * filter Providers
	 *
	 * @return void
	 */
	public function filter($providerExtract=false, $start_date_compensation=null, $end_date_compensation=null, $start_date_created=null, $end_date_created=null, $orderBalance = null){
		
		$this->initPartnerFilter();
		$id = Input::get('id');
		$name = Input::get('name');
		$email = Input::get('email');
		$phone = Input::get('phone');
		$state = Input::get('state');
		$city = Input::get('city');
		$plate = Input::get('plate');
		$status = Input::get('status');
		$order = Input::get('order');
		$type = Input::get('type');
		$partnerId = Input::get('partner_id');
		$locationId = Input::get('location_id');
		$cnh = Input::get('cnh_number');
		$sendDocs = Input::get('send_docs');
		$registerStep = Input::get('reg_step');
		$isPartnerProfile = Admin::isPartnerProfile();
		$arrPartners = $this->partners->toArray();
		$locations = $this->locationModel->get();
		$statusId = 0;

		if ($phone)
			$phone = preg_replace( "/(\W)+/", '', $phone); 

		// Getting the app language
		$language = Settings::getLocale();

		if(ProviderStatus::where('name', $status)->first())
			$statusId = ProviderStatus::where('name', $status)->first()->id;
		$providers = Provider::search($id, $name, $email, $state, $city, $plate, null, $statusId, $order, $type, $this->partnersId, $locationId, $cnh, $phone, $start_date_compensation, $end_date_compensation, null, null, $registerStep,$providerExtract, $start_date_created, $end_date_created, $sendDocs, $orderBalance);
		
		$title = ucwords(trans('customize.Provider') . " | " . trans('adminController.search_result'));
		if(!$providerExtract && Input::get('submit') && Input::get('submit') == 'Download_Report'){						
			//if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
				return $this->downloadReport($providers);							
			//}			
		}		
		else {
			if (!$providerExtract) {				
				return View::make('providers.list')
				->with('providers', $providers->paginate(20))
				->with('partners', $this->partners)
				->with('locations', $locations)
				->with('name', $name)
				->with('id', $id)
				->with('phone',$phone)
				->with('plate', $plate)
				->with('email', $email)
				->with('order', $order)
				->with('type', $type)
				->with('city', $city)
				->with('state', $state)
				->with('status', $status)
				->with('cnh_number',$cnh)
				->with('type','id')
				->with('order',1)
				->with('language', $language);
			}else{
				return $providers;
			} 
		}
	}


	/**
	 * providers list function
	 *
	 * @return void
	 */
	public function index($providerExtract=false){
		Session::forget('type');
		Session::forget('valu');
		Session::forget('che');
		$this->initPartnerFilter();
		$locations = $this->locationModel->get();
		$isPartnerProfile = Admin::isPartnerProfile();
		Session::forget("tab");
		$language = Settings::getLocale();
		$providers = Provider::search(null, null, null, null, null, null, null, null, null, null, $this->partnersId, null, null, null, null, null, null, null, null);
		if (!$providerExtract) {
			return View::make('providers.list')
					->with('locations', $locations)
					->with('providers', $providers->paginate(20))
					->with('partners', $this->partners)
					->with('type','id')
					->with('language', $language)
					->with('order',1);
		}else{			
			return $providers;
		}		
	}


	/**
	 * Download csv of provider extract report
	 *
	 * @return void
	 */	
	public function downloadExtractReport($providers){

		// Setting the output filename 
		$filename = "relatorio-prestadores-".date("Y-m-d-hms", time()).".csv";
		$handle = fopen(storage_path('tmp/').$filename, 'w+');
		fputs( $handle, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF) );
		
		// Setting the csv header
		fputcsv($handle,
			array(
				trans('map.id'),
				trans('provider.name_grid'),
				trans('dashboard.document'),
				trans('provider.address_street'),
				trans('provider.address_number'),
				trans('provider.address_complements'),
				trans('provider.address_neighbour'),
				trans('provider.zipcode'),
				trans('provider.address_city'),
				trans('provider.state'),
				trans('provider.country'),
				trans('bank_account.holder_name'),
				trans('bank_account.holder_document'),
				trans('bank_account.bank_code'),
				trans('bank_account.bank_name'),
				trans('bank_account.account_types'),
				trans('bank_account.person_type'),
				trans('bank_account.bank_agency'),
				trans('bank_account.bank_agency_dig'),
				trans('bank_account.bank_account'),
				trans('bank_account.bank_account_dig'),
				trans('provider.total_request_grid'),
				trans('finance.current_balance'),
				trans('finance.total_compensations'),
				trans('finance.total')
			),
			";"
		);

		$providers = $providers->get();
		$locations = $this->locationModel->get();
		$balances = array();		
		
		foreach ($providers as $key => $provider) {

			$bank_account = $provider->getBankAccount();
			
			$id =  $provider->id;
			$holder = Provider::find($id);			
			
			if(Input::get('type-entry') != ''){
				$typeEntry = Input::get('type-entry');
			}else{
				$typeEntry = '';
			}
			if($holder && $holder->ledger){
				$startDate =  Input::has('start_date_created') ? 
					date('Y-m-d 00:00:00', strtotime($this->parseDate(Input::get('start_date_created')))) :
					date('Y-m-d', strtotime($holder->created_at));

				$endDate = Input::has('end_date_created') ? 
					date("Y-m-d 23:59:59", strtotime($this->parseDate(Input::get('end_date_created')))) : 
					date('Y-m-d 23:59:59');
				
				$startDateCompensation = Input::has('start-date-compensation') ? date('Y-m-d', strtotime(Input::get('start-date-compensation'))) : date('Y-m-d', strtotime($holder->created_at));
				$endDateCompensation = Input::has('end-date-compensation') ? date("Y-m-d 23:59:59", strtotime(Input::get('end-date-compensation'))) : date('Y-m-d 23:59:59');
				$title = trans('finance.account_statement');
				array_push($balances, Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
			}else{
				$balance = array("previous_balance"=>0.0, "current_balance" => 0.0, "total_balance_by_period" => 0.0, "detailed_balance"=> array(), "period_balance" => 0);				
				array_push($balances, $balance);
			}
		
			$total = 0;
			$totalizer = 0;	
			
			$entries = $balances[$key];

			$total_balance_by_period = formated_value($entries['period_balance']);
			
			$totalizer=0;									
			
			$totalizer = $entries['total_balance'] - $entries['current_balance'];
			$total += $totalizer;
			
			$total_receivable = formated_value($totalizer);
			
			$total_result = formated_value($entries['total_balance']);
			
			$bankCode 		= $this->checkBankInfo($bank_account, ['code']);
			$bankName 		= $this->checkBankInfo($bank_account, ['name']);
			$bankAgency 	= $this->checkBankInfo($bank_account, 'agency');
			$bankAgencyDv 	= $this->checkBankInfo($bank_account, 'agency_digit');
			$bankAccount 	= $this->checkBankInfo($bank_account, 'account');
			$bankAccountDv 	= $this->checkBankInfo($bank_account, 'account_digit');
			$bankHolderName = $this->checkBankInfo($bank_account, 'holder');
			$bankHolderDoc  = $this->checkBankInfo($bank_account, 'document');

			$bankTrans 		= trans("bank_account");

			$accountType 	= $bank_account ? $bankTrans[$bank_account['account_type']] : '';
			if(isset($bankTrans[$bank_account['person_type']])){
				$personType		= $bank_account ? $bankTrans[$bank_account['person_type']] : '';
			}else{
				$personType = "";
			}
			
			
			// Formats the csv file
			fputcsv($handle,
				array(
					$provider->id,
					$provider->first_name." ".$provider->last_name,
					$bank_account['document'],
					$provider->address,
					$provider->address_number,
					$provider->address_complements,
					$provider->address_neighbour,
					$provider->zipcode,
					$provider->address_city,
					$provider->state,
					$provider->country,
					$bankHolderName,
					$bankHolderDoc,
					$bankCode,
					$bankName,
					$accountType,
					$personType,
					$bankAgency,
					$bankAgencyDv,
					$bankAccount,
					$bankAccountDv,
					$provider->total_requests,
					$total_balance_by_period,
					$total_receivable,
					$total_result
				),
				";"
			);
		}
		// Close the pointer file
		fclose($handle);
		$headers = array(
			'Content-Type' => 'text/csv; charset=utf-8',
			'Content-Disposition' => 'attachment; filename='. $filename,
		);
		return Response::download(storage_path('tmp/').$filename, $filename, $headers);		
	}

	public function checkBankInfo($bank, $key)
	{
		if (!$bank)
			return '';
			
		if(is_array($key) && $bank->bank)
			return $bank->bank[$key[0]];

		return $bank->$key;
	}

}