<?php

namespace Codificar\Finance\Http\Controllers;

use App\Http\Controllers\Controller;

// Importar models
use Codificar\Finance\Models\LibModel;

//FormRequest
use Codificar\Finance\Http\Requests\ProviderProfitsRequest;
use Codificar\Finance\Http\Requests\GetProviderSummaryByTypeAndDateFormRequest;
use Codificar\Finance\Http\Requests\GetFinancialSummaryByTypeAndDateFormRequest;
use Codificar\Finance\Http\Requests\GetCardsAndBalanceFormRequest;
use Codificar\Finance\Http\Requests\AddCreditCardBalanceFormRequest;
use Codificar\Finance\Http\Requests\AddBilletBalanceFormRequest;
use Codificar\Finance\Http\Requests\AddCardUserFormRequest;

//Resource
use Codificar\Finance\Http\Resources\ProviderProfitsResource;
use Codificar\Finance\Http\Resources\GetFinancialSummaryByTypeAndDateResource;
use Codificar\Finance\Http\Resources\GetCardsAndBalanceResource;
use Codificar\Finance\Http\Resources\AddCreditCardBalanceResource;
use Codificar\Finance\Http\Resources\AddBilletBalanceResource;

use Carbon\Carbon;
use Auth;

use Input, Validator, View, Response, Session;
use Finance, Admin, Settings, Provider, ProviderStatus, User, PaymentFactory, EmailTemplate, Transaction, Request, Payment;

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
		$providerId = $request->provider_id ? $request->provider_id : $request->id;
		$provider = Provider::where('id', $providerId)->first();
		$ledgerId = $provider->ledger->id;

		$finance = LibModel::getProviderProfitsOfWeek($provider->id);
		$totalMoney = LibModel::getProviderProfitsOfWeekMoneyValue($provider->id);
		$currentBalance = Finance::sumValueByLedgerId($ledgerId);
		$isWithdrawEnabled = LibModel::getWithDrawEnabled();
		
		return new ProviderProfitsResource([
			"finance" => $finance,
			"total_money" => $totalMoney,
			"current_balance" => $currentBalance,
			"available" => LibModel::getWeekOnlineTime($provider->id),
			"rides" => LibModel::getWeekRidesCount($provider->id),
			"is_withdraw_enabled" => $isWithdrawEnabled
		]);
    }
    
     /**
	 * API ANTIGA. DEVERA SER REMOVIDA FUTURAMENTE. NAO FOI REMOVIDA AINDA, POIS SE ATUALIZAR O PAINEL, PODERA QUEBRAR OS APPS QUE NAO FORAM ATUALIZADOS.
	 * REMOVER EM UM MOMENTO OPORTUNO.
	 *
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

	 /**
     * @api {get} /libs/finance/provider/financial/provider_summary
     * @apiDescription Permite buscar o extrato de contas com datas pré-definidas e filtros
     * @return Json
     */	
	public function getProviderSummaryByTypeAndDate(GetProviderSummaryByTypeAndDateFormRequest $request)
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

					if($start_date_created) {
						$startDate = Carbon::createFromFormat('d/m/Y', $start_date_created)->format('Y-m-d 00:00:00');
					} else {
						$startDate = date('Y-m-d', strtotime($holder->created_at));
					}

					if($end_date_created) {
						$endDate = Carbon::createFromFormat('d/m/Y', $end_date_created)->format('Y-m-d 23:59:59');
					} else {
						$endDate = date('Y-m-d 23:59:59');
					}

					$startDateCompensation = Input::has('start-date-compensation') ? date("Y-m-d 0:0:0", strtotime(Input::get('start-date-compensation'))) : date('Y-m-d 23:59:59');
					$endDateCompensation = Input::has('end-date-compensation') ? date("Y-m-d 23:59:59", strtotime(Input::get('end-date-compensation'))) : date('Y-m-d 23:59:59');
					$title = trans('finance.account_statement');
					array_push($balances, Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
				}
			}
		}

		if (count($providers->simplePaginate(20)) > 0) {				
			return View::make('finance::account_summary')
					->with('locations', $locations)
					->with('providers', $providersss)
					->with('partners', $this->partners)
					->with('currency_symbol', $currency_symbol)						
					->with('type','id')
					->with(['id' => $id, 'holder' => $holder->first_name.' '.$holder->last_name, 'ledger' => $holder, 'title' => $title, 'balances' => $balances, 'start' => $startDate, 'end' => $endDate, 'page' => 'financial'])
					->with('order',1)
					->with('balances',$balances);
		}else{
			return View::make('finance::account_summary')
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
		$providers = LibModel::providerSearch($id, $name, $email, $state, $city, $plate, null, $statusId, $order, $type, $this->partnersId, $locationId, $cnh, $phone, $start_date_compensation, $end_date_compensation, null, null, $registerStep,$providerExtract, $start_date_created, $end_date_created, $sendDocs, $orderBalance);
		
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
		$providers = LibModel::providerSearch(null, null, null, null, null, null, null, null, null, null, $this->partnersId, null, null, null, null, null, null, null, null);
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

	/**
     * @api {GET} libs/finance/user/get_cards_and_balance
     * Retorna os cartões cadastrados pelo usuário e saldo em carteira.
     * @return json
     */
    public function getCardsAndBalance(GetCardsAndBalanceFormRequest $request) {

        $userId = $request->id;
        // Retorna os cartões cadastrados pelo cliente
        $payments = LibModel::getCardsList($userId);
		$user = User::where('id', $userId)->first();
		$ledgerId = $user->ledger->id;
        $data = array();

		$data['success']    		= true;
		$data['current_balance'] 	= currency_format(LibModel::sumValueByLedgerId($ledgerId));
		$data['cards']       		= $payments;
		$data['settings']			= $this->getAddBalanceSettings();
		$data['error']      		= null; 

        return new GetCardsAndBalanceResource($data);
	}

	
	public function userPayment(Request $request)
    {
		$user_id = Auth::guard("clients")->user()->id;
        $user = User::find($user_id);
        $user_cards = $user->payments;
        $user_balance = $user->getBalance();
		
		return View::make('finance::payment.payment')
						->with('enviroment', 'user')
                        ->with('user_balance', $user_balance)
						->with('user_cards', $user_cards)
						->with('add_billet_balance_user', Settings::where('key', 'add_billet_balance_user')->first()->value)
						->with('add_balance_min', Settings::where('key', 'add_balance_min')->first()->value)
						->with('add_balance_billet_tax', Settings::where('key', 'add_balance_billet_tax')->first()->value)
						->with('add_card_balance_user', Settings::where('key', 'add_card_balance_user')->first()->value);


	}

	public function deleteUserCard() {


		$user_id = Auth::guard("clients")->user()->id;
        $user = User::find($user_id);

		$card_id = Input::get('card_id');

		$validator = Validator::make(
			array(
				'card_id' => $card_id
			), array(
				'card_id' => 'required'
			), array(
				'card_id' => trans('userController.unique_card_id_missing')
			)
		);

		if ($validator->fails()) {
			$error_messages = $validator->messages()->all();
			$response_array = array('success' => false, 'data' => null, 'error' => array('code' => \ApiErrors::BAD_REQUEST, 'messages' => $error_messages));
			$response_code = 200;
		} else {
                $payment = Payment::deleteByIdAndUserId($card_id, $user->id);
                
                $response_array = array(
                    'success' => $payment["success"],
                    'payments' => $payment["data"],
                    'error' => $payment["error"]
                );

                $response_code = 200;
				
		}

		$response = Response::json($response_array, $response_code);
		return $response;
	}
	
	public function addCreditCardBalance(AddCreditCardBalanceFormRequest $request) {

		$user = $request->user;
		$ledgerId = $user->ledger->id;
		$cardId = $request->card_id;
		//Check if the card is the user
		$payment = LibModel::getCreditCard($user->id, $cardId);
		$data = array();
		//Se nao encontrou o card, entao da erro
		if(!$payment) {
			$data['success']	= false;
			$data['error']		= 'Cartão não encontrado ou não pertence ao usuário'; 
			$data['current_balance'] = currency_format(LibModel::sumValueByLedgerId($ledgerId));
		} else {
			//Tenta realizar a cobranca com o cartao
			$gateway = PaymentFactory::createGateway();
			$return = $gateway->charge($payment, $request->value, trans('financeTrans::finance.single_credit'), true);
			
			//Se conseguiu cobrar no cartao, entao adicionar um saldo para o usuario, senao, retorna erro
			if($return['success'] && $return['captured'] == 'true') {
				$financeEntry = Finance::createCustomEntry($user->ledger->id, 'SEPARATE_CREDIT', trans('financeTrans::finance.single_credit'), $request->value, null, null);
				if($financeEntry) {
					$data['success']	= true;
					$data['error']		= null; 
					$data['current_balance'] = currency_format(LibModel::sumValueByLedgerId($ledgerId));
				}
			} else {
				$data['success']	= false;
				$data['error']		= 'O cartão foi recusado.'; 
				$data['current_balance'] = currency_format(LibModel::sumValueByLedgerId($ledgerId));
			}
		}

        return new AddCreditCardBalanceResource($data);
    }


	public function addBilletBalance(AddBilletBalanceFormRequest $request) {

		$user = $request->user;
		$ledgerId = $user->ledger->id;
		$data = array();
		
		$billetTax = (float) Settings::where('key', 'add_balance_billet_tax')->first()->value;
		$value = $request->value + $billetTax;
		$postBack = route('GatewayPostbackBillet') . "/" . $user->ledger->id;

		$billetExpiration = Carbon::now()->addDays(7)->toIso8601String();
		$gateway = PaymentFactory::createGateway();
		$payment = $gateway->billetCharge($value, $user, $postBack, $billetExpiration, "Adicionar saldo em conta.");
		
		if($payment['success']){
			$billet_link = $payment['billet_url'];
			$billet_barcode = $payment['billet_barcode'];
			$gateway_transaction_id = $payment['transaction_id'];

			$paymentTax = $gateway->getGatewayTax();
			$paymentFee = $gateway->getGatewayFee();	

			//Save the billet in transaction table (not in finance yet. In finance table is when billet is paid)
			$transaction 					= new Transaction();
			$transaction->type 				= Transaction::SINGLE_TRANSACTION;
			$transaction->status 			= 'waiting_payment';
			$transaction->gross_value 	 	= $value;
			$transaction->provider_value 	= 0;
			$transaction->gateway_tax_value = ($value * $paymentTax) + $paymentFee;
			$transaction->net_value 		= $value - $transaction->gateway_tax_value ;
			$transaction->gateway_transaction_id = $gateway_transaction_id;
			$transaction->billet_link		= $billet_link;
			$transaction->save();
			
			//send email
			try {
				$key_email = "billet_mail";
				$emailTemplate = EmailTemplate::getTemplateByKey($key_email);
				$subject = ($emailTemplate != null) ? $emailTemplate->subject : "Boleto";
				$vars = array(
					'billet_value' => currency_format(currency_converted($value)),
					'expiration' => Carbon::parse($billetExpiration)->format('d/m/y'),
					'billet_url' => $billet_link,
				);
				email_notification($user->id, 'user', $vars, $subject, $key_email, null);	
			} catch(\Exception $e){
				\Log::error("Erro ao enviar boleto por email.");
				\Log::error($e->getMessage());
			}
			
			
		} else {
			return response()->json($payment, 503);
		}

		return response()->json([
			'success' => true, 
			'billet_url' => $billet_link,
			'billet_barcode' => $billet_barcode,
			'error' => false
		]);

        return new AddBilletBalanceResource($data);
	}
	
	private function getAddBalanceSettings() {
		$data = array();
		$data['addBilletBalanceUser'] = Settings::where('key', 'add_billet_balance_user')->first()->value;
		$data['addCardBalanceUser'] = Settings::where('key', 'add_card_balance_user')->first()->value;
		$data['addBalanceMin'] = Settings::where('key', 'add_balance_min')->first()->value;
		$data['addBalanceBilletTax'] = Settings::where('key', 'add_balance_billet_tax')->first()->value;
		return $data;
	}

	public function addCreditCard(AddCardUserFormRequest $request) {
		$type = Request::segment(1);
		\Log::debug($type . " - " . Finance::TYPE_USER);
		switch($type){
			case Finance::TYPE_USER:
				$id = Auth::guard("clients")->user()->id;
				$holder = User::find($id);
			break;
		}
		
		$data = array();
		$payment = new Payment;
		$payment->user_id = $holder->id;
		$return = $payment->createCard($request->cardNumber, $request->cardExpMonth, $request->cardExpYear, $request->cardCvc, $request->cardHolder);

		if($return['success']){
            return new AddCardUserResource($payment);
		} else {
			return response()->json(['message' => $return['message'],'success'=> false, 'type' => $return['type'], 'card' => $payment]);
		}
	}
}