<?php

namespace Codificar\Finance\Http\Controllers;

use App\Http\Controllers\Controller;

// Importar models
use Codificar\Finance\Models\LibModel;

use Codificar\Finance\Events\PixUpdate;

//FormRequest
use Codificar\Finance\Http\Requests\ProviderProfitsRequest;
use Codificar\Finance\Http\Requests\GetProviderSummaryByTypeAndDateFormRequest;
use Codificar\Finance\Http\Requests\GetCardsAndBalanceFormRequest;
use Codificar\Finance\Http\Requests\ProviderApiFormRequest;
use Codificar\Finance\Http\Requests\UserApiFormRequest;
use Codificar\Finance\Http\Requests\AddCreditCardBalanceFormRequest;
use Codificar\Finance\Http\Requests\AddBilletBalanceFormRequest;
use Codificar\Finance\Http\Requests\AddCardUserFormRequest;
use Codificar\Finance\Http\Requests\AddCreditCardBalanceWebFormRequest;
use Codificar\Finance\Http\Requests\AddBilletBalanceWebFormRequest;
use Codificar\Finance\Http\Requests\AddPixBalanceFormRequest;

//Resource
use Codificar\Finance\Http\Resources\ProviderProfitsResource;
use Codificar\Finance\Http\Resources\GetFinancialSummaryByTypeAndDateResource;
use Codificar\Finance\Http\Resources\GetCardsAndBalanceResource;
use Codificar\Finance\Http\Resources\AddCreditCardBalanceResource;
use Codificar\Finance\Http\Resources\AddBilletBalanceResource;
use Codificar\Finance\Http\Resources\AddCardUserResource;
use Codificar\Finance\Http\Resources\RetrievePixResource;


use Carbon\Carbon;
use Auth;
use Codificar\Finance\Http\Requests\AddCardProviderFormRequest;
use Codificar\Finance\Http\Requests\BalanceFormRequest;
use Codificar\Finance\Http\Requests\GetConsolidatedStatementRequest;
use Codificar\Finance\Http\Requests\ImportPaymentsRequest;
use Codificar\Finance\Http\Requests\changePixPaymentRequest;
use Codificar\Finance\Http\Resources\BalanceResource;
use Codificar\Finance\Http\Resources\AddCreditCardResource;
use Codificar\Finance\Imports\PaymentsImport;
use Codificar\Finance\Models\Transaction;
use Codificar\PaymentGateways\Libs\PagarmeApi;
use Codificar\PaymentGateways\Libs\PaymentFactory as LibsPaymentFactory;
use Input, Validator, View, Response, Session;
use Finance, Admin, Settings, Provider, ProviderStatus, User, EmailTemplate, Request, Payment, AdminInstitution, Ledger, URL, RequestCharging, Requests;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Redirect;
use DB;
use Illuminate\Http\Response as HttpResponse;
use ScheduledRequests;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

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

		$providerPrepaid = false;
		if((bool)Settings::findByKey("payment_prepaid") && (
			(bool) Settings::findByKey("prepaid_billet_provider") ||
			(bool) Settings::findByKey("prepaid_card_provider") ||
			(bool) Settings::findByKey("prepaid_pix_provider")
		)) {
			$providerPrepaid = true;
		}
		$balance['provider_prepaid'] = $providerPrepaid;

        // Retorno de dados
        return new GetFinancialSummaryByTypeAndDateResource(['balance' => $balance]);
	}

    /**
     * Get the provider data and filter to make their balance and use this on account_summary view
	 * 
     * @return View account_summary
     */
	public function providerExtract(){
		$providers = $this->index(true);
		$providers = $providers->paginate(20);
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

		$currency_symbol = LibModel::getCurrencySymbol() . " ";

		return View::make('finance::account_summary')
					->with('locations', $locations)
					->with('providers', $providers)
					->with('partners', $this->partners)
					->with('type','id')
					->with('order',1)
					->with('currency_symbol', $currency_symbol)
					->with('balances',$balances);
	}
 
	/**
     * Get the provider data and filter to make their balance and use this on account_summary view, but this time using filters
	 * 
     * @return View account_summary
     */
	public function providerExtractFilter(){

		$start_date_compensation = Input::get('start-date-compensation');
	  	$end_date_compensation = Input::get('end-date-compensation');
		$start_date_created = Input::get('start_date_created');
		$end_date_created = Input::get('end_date_created');
		$orderBalance = Input::get('order_balance');

		$currency_symbol = LibModel::getCurrencySymbol() . " ";

		$providers = $this->filter(true,$start_date_compensation, $end_date_compensation, $start_date_created, $end_date_created, $orderBalance);
		$locations = $this->locationModel->get();
		if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
			return $this->downloadExtractReport($providers);
		}else{
			$providersss = $providers->paginate(20);
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
					array_push($balances, LibModel::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
				}
			}
		}

		if (count($providers->paginate(20)) > 0) {
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

	/**
     * Get a date and parse it to the necessar format
     *
     * @return DateTime $date
     */
	public function parseDate($date)
	{
		try {
			$parse = \DateTime::createFromFormat('d/m/Y', $date);
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
	 * @para
	 * @return void
	 */
	public function downloadExtractReport($providers){

		// Setting the output filename
		$filename = "relatorio-prestadores-".date("Y-m-d-hms", time()).".csv";
		$handle = fopen(storage_path('tmp/').$filename, 'w+');
		fputs( $handle, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF) );

		// Setting the csv header
		$vars = array(
					trans('map.id'),
					trans('provider.name_grid'),
					trans('financeTrans::finance.holder_document'),
					trans('financeTrans::finance.address_street'),
					trans('provider.address_number'),
					trans('provider.address_complements'),
					trans('provider.address_neighbour'),
					trans('provider.zipcode'),
					trans('provider.address_city'),
					trans('provider.state'),
					trans('provider.country'),
					trans('bank_account.holder_name'),
					trans('financeTrans::finance.bank_holder_document'),
					trans('bank_account.bank_code'),
					trans('bank_account.bank_name'),
					trans('bank_account.account_types'),
					trans('bank_account.person_type'),
					trans('bank_account.bank_agency'),
					trans('bank_account.bank_agency_dig'),
					trans('bank_account.bank_account'),
					trans('bank_account.bank_account_dig'),
					trans('financeTrans::finance.period_requests'),
					trans('financeTrans::finance.period_balance'),
					trans('financeTrans::finance.total_balance'),
					trans('financeTrans::finance.hit_value')
				);

		if((config('app.locale') == 'pt-br') && Settings::findByKey("show_pix_information") == 1)
		{
			array_push($vars , ' PIX ');// add pix column only if is pt-br language
			array_push($vars , ' Chave PIX ');
		}
		// Setting the csv header
		fputcsv($handle, $vars, ";" );

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
				$startDate =  !empty(Input::get('start_date_created')) ?
					Carbon::createFromFormat('d/m/Y', Input::get('start_date_created'))->format('Y-m-d 00:00:00') :
					$holder->created_at->format('Y-m-d H:i:s');
				$endDate = !empty(Input::get('end_date_created')) ?
					Carbon::createFromFormat('d/m/Y', Input::get('end_date_created'))->format('Y-m-d 23:59:59') :
					date('Y-m-d 23:59:59');

				$startDateCompensation = Input::has('start-date-compensation') ? date('Y-m-d', strtotime(Input::get('start-date-compensation'))) : date('Y-m-d', strtotime($holder->created_at));
				$endDateCompensation = Input::has('end-date-compensation') ? date("Y-m-d 23:59:59", strtotime(Input::get('end-date-compensation'))) : date('Y-m-d 23:59:59');
				$title = trans('finance.account_statement');
				array_push($balances, LibModel::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate) );
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
			$personType  	= $this->checkBankInfo($bank_account, 'person_type');

			$personTypeTrans 		= trans("bank_account.".$personType);

			$accountType 	= $bank_account ? "" : '';

			$vars = array(
						$provider->id,
						$provider->first_name." ".$provider->last_name,
						$bankHolderDoc,
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
						$personTypeTrans,
						$bankAgency,
						$bankAgencyDv,
						$bankAccount,
						$bankAccountDv,
						$provider->total_requests,
						$total_balance_by_period,
						$total_result,
						$total_result >= 0 ? $total_result : trans('financeTrans::finance.provider_in_debit')
					);
			if((config('app.locale') == 'pt-br') && method_exists($provider, 'getPix') && method_exists($provider, 'getPixType') &&  Settings::findByKey("show_pix_information") == 1)
			{
				$pix = $provider->getPixType();
				if($pix == Provider::PIX_EMAIL){
					$pix = 'E-mail';
				}else if($pix == Provider::PIX_PHONE){
					$pix = 'Celular';
				}else if($pix ==  Provider::PIX_CPF){
					$pix = 'CPF';
				}else if($pix ==  Provider::PIX_CNPJ){
					$pix = 'CNPJ';
				} else{
					$pix = '';
				}
				array_push($vars , $pix);
				array_push($vars , $provider->getPix());
			}
			// Formats the csv file
			fputcsv($handle, $vars ,";");
		}
		// Close the pointer file
		fclose($handle);
		$headers = array(
			'Content-Type' => 'text/csv; charset=utf-8',
			'Content-Disposition' => 'attachment; filename='. $filename,
		);
		return Response::download(storage_path('tmp/').$filename, $filename, $headers);
	}

	/**
	 * Verify ig there is a bank key and return it
	 *
	 * @param Object $bank 
	 * @param Array $key 
	 * @return String $bank->$key || ''
	 */
	public function checkBankInfo($bank, $key)
	{
		if (!$bank || !$bank->bank)
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
        $payments = LibModel::getCardsList($userId, 'user');
		$user = User::where('id', $userId)->first();
		$ledgerId = $user->ledger->id;

        $data = array();

		$currency_symbol = LibModel::getCurrencySymbol() . " ";
		$current_balance = LibModel::sumValueByLedgerId($ledgerId);
		$current_balance = $current_balance < 0 ? $current_balance * -1 : $current_balance;
		$balance = currency_format($current_balance);
		if (strpos($balance, $currency_symbol) === false) {
			$balance = $currency_symbol . $balance;
		}

		$data['success']    				= true;
		$data['current_balance'] 			= currency_format(LibModel::sumValueByLedgerId($ledgerId));
		$data['current_balance_formated'] 	= $balance;
		$data['cards']       				= $payments;
		$data['settings']					= $this->getAddBalanceSettings();
		$data['error']      				= null;
		$data['referral_balance']			= currency_format(LibModel::getSumTotalIndication($ledgerId));
		$data['cumulated_balance_monthly']	= currency_format(LibModel::getSumMonthlyIndication($ledgerId));

		//juno gateway is webview to add card
		$data['add_card_is_webview']		= Settings::findByKey('default_payment') == 'juno' ? true : false;

        return new GetCardsAndBalanceResource($data);
	}

	/**
     * @api {GET} libs/finance/provider/get_cards_and_balance
     * Retorna os cartões cadastrados pelo usuário e saldo em carteira.
     * @return json
     */
    public function getCardsAndBalanceProvider(ProviderApiFormRequest $request) {

        $provider_id = $request->id;
        // Retorna os cartões cadastrados pelo cliente
        $payments = LibModel::getCardsList($provider_id, 'provider');
		$provider = Provider::where('id', $provider_id)->first();
		$ledgerId = $provider->ledger->id;
        $data = array();

		$currency_symbol = LibModel::getCurrencySymbol() . " ";
		$current_balance = LibModel::sumValueByLedgerId($ledgerId);
		$current_balance = $current_balance < 0 ? $current_balance * -1 : $current_balance;
		$balance = currency_format($current_balance);
		if (strpos($balance, $currency_symbol) === false) {
			$balance = $currency_symbol . $balance;
		}

		$data['success']    				= true;
		$data['current_balance'] 			= currency_format(LibModel::sumValueByLedgerId($ledgerId));
		$data['current_balance_formated'] 	= $balance;
		$data['cards']       		= $payments;
		$data['settings']			= $this->getAddBalanceSettings();
		$data['error']      		= null;
		$data['referral_balance']			= currency_format(LibModel::getSumTotalIndication($ledgerId));
		$data['cumulated_balance_monthly']	= currency_format(LibModel::getSumMonthlyIndication($ledgerId));

		//juno gateway is webview to add card
		$data['add_card_is_webview']		= Settings::findByKey('default_payment') == 'juno' ? true : false;

        return new GetCardsAndBalanceResource($data);
	}

	/**
	 * Get payment information of the user.
	 *
	 * @param Request $request
	 * @return View
	 */
	public function userPayment(Request $request)
    {
		$enviroment = $this->getEnviroment();

        $user_cards = $enviroment['holder']->payments;
        $user_balance = $enviroment['holder']->getBalance();

		$currency_symbol = LibModel::getCurrencySymbol() . " ";
		$currency = Settings::findByKey('generic_keywords_currency');

		$iframe_add_card = null;
		//if gateway is juno, the add card is iframe
		if(Settings::findByKey('default_payment') == 'juno') {
			$envtype = $enviroment['type'] == 'provider' ? 'provider' : 'user'; //corp is user for add card in iframe
			$iframe_add_card = URL::Route('addCardJuno') . '?holder_type=' . $envtype . '&holder_id=' . $enviroment['holder']->id . '&holder_token=' . $enviroment['holder']->token;
		}

		return View::make('finance::payment.payment')
						->with('enviroment', $enviroment['type'])
						->with('user_balance', $user_balance)
						->with('user_cards', $user_cards)
						->with('prepaid_settings', $this->getAddBalanceSettings())
						->with('currency_symbol', $currency_symbol)
						->with('iframe_add_card', $iframe_add_card)
						->with('currency', $currency);



	}

	/**
	 * Delete user card
	 *
	 * @return Array
	 */
	public function deleteUserCard() {
		$enviroment = $this->getEnviroment();
		$card_id = Input::get('card_id');

		$validator = Validator::make(
			array('card_id' => $card_id),
			array('card_id' => 'required'),
			array('card_id' => trans('userController.unique_card_id_missing'))
		);

		if ($validator->fails()) {
			$error_messages = $validator->messages()->all();
			$response_array = array('success' => false, 'data' => null, 'error' => array('code' => \ApiErrors::BAD_REQUEST, 'messages' => $error_messages));
		} else {

			if($enviroment['type'] == 'user' || $enviroment['type'] == 'corp')
				$payment = Payment::deleteByIdAndUserId($card_id, $enviroment['holder']->id);
			else
				$payment = Payment::deleteByIdAndProviderId($card_id, $enviroment['holder']->id);

			$response_array = array(
				'success' => $payment["success"],
				'payments' => $payment["data"],
				'error' => $payment["error"]
			);

		}

		$response = Response::json($response_array, 200);
		return $response;
	}

	/**
	* Add balance by paying with credit card
	*
	* @param float $value
	* @param Object $holder
	* @param Integer $cardId
	* @param String $envType
	* 
	* @return AddCreditCardBalanceResource ($data);
	*/
	private function addCreditCardBalance($value, $holder, $cardId, $envType) {
		$ledgerId = $holder->ledger->id;

		if($envType == 'provider')
			$payment = LibModel::getCreditCardProvider($holder->id, $cardId);
		else
			$payment = LibModel::getCreditCardUser($holder->id, $cardId);

		$data = array();
		//Se nao encontrou o card, então da erro
		if(!$payment) {
			$data['success']	= false;
			$data['error']		= 'Cartão não encontrado ou não pertence ao usuário';
			$data['current_balance'] = currency_format(LibModel::sumValueByLedgerId($ledgerId));
		} else {
			//Tenta realizar a cobrança com o cartão
			$gateway = LibsPaymentFactory::createGateway();
			$return = $gateway->charge($payment, $value, trans('financeTrans::finance.single_credit'), true);

			$data = array(
				'success' => false,
				'error' => 'O cartão foi recusado.',
				'current_balance' => currency_format(LibModel::sumValueByLedgerId($ledgerId))
			);

			//Se conseguiu cobrar no cartão, então adicionar um saldo para o usuário/prestador, senão, retorna erro
			if($return['success'] && $return['captured'] && $return['transaction_id']) {
				$transaction = Transaction::createTransactionAddBalance(
					$return['status'],
					$value,
					$return['transaction_id']
				);
				if($transaction) {
					$financeEntry = LibModel::createCustomEntry(
						$holder->ledger->id, 
						'SEPARATE_CREDIT', 
						trans('financeTrans::finance.single_credit') . " - gateway ID: " . $transaction->gateway_transaction_id, 
						$value, 
						null, 
						null,
						$transaction->id
					);
					if($financeEntry) {
						$data['success']	= true;
						$data['error']		= null;
						$data['current_balance'] = currency_format(LibModel::sumValueByLedgerId($ledgerId));
					}
				}
			}
		}

        return new AddCreditCardBalanceResource($data);
	}

	/**
	 * Add balance by paying with credit card
	 *
	 * @param AddCreditCardBalanceWebFormRequest  $request
	 * @return addCreditCardBalance ($request->value, $enviroment['holder'], $request->card_id, $enviroment['type']);
	 */
	public function addCreditCardBalanceWeb(AddCreditCardBalanceWebFormRequest $request) {

		$enviroment = $this->getEnviroment();
		return $this->addCreditCardBalance($request->value, $enviroment['holder'], $request->card_id, $enviroment['type']);
	}

	/**
	 * Add balance by paying with credit card
	 *
	 * @param AddCreditCardBalanceWebFormRequest  $request
	 * @return addCreditCardBalance ($request->value, User::find($request->id), $request->card_id, 'user');
	 */
	public function addCreditCardBalanceApp(AddCreditCardBalanceFormRequest $request) {
		return $this->addCreditCardBalance($request->value, User::find($request->id), $request->card_id, 'user');
	}

	/**
	 * Add balance by paying with credit card
	 *
	 * @param AddCreditCardBalanceWebFormRequest  $request
	 * @return addCreditCardBalance ($request->value, Provider::find($request->id), $request->card_id, 'provider');
	 */
	public function addCreditCardBalanceAppProvider(AddCreditCardBalanceFormRequest $request) {
		return $this->addCreditCardBalance($request->value, Provider::find($request->id), $request->card_id, 'provider');
	}

	/**
	 * Generate a new billet
	 *
	 * @param Decimal  $value
	 * @param Object  $holder
	 * @param String $envType
	 * @return addCreditCardBalance ($request->value, Provider::find($request->id), $request->card_id, 'provider');
	 */
	private function newBillet($value, $holder, $envType) {

		$data = array();

		$billetTax = (float) Settings::findByKey('prepaid_tax_billet');
		$value = $value + $billetTax;

		//cria a transaction, para colocar o id dela no postback. Se der erro, deleta essa transaction, senao, atualiza elas com os dados do gateway
		$transaction 					= new Transaction();
		$transaction->type 				= Transaction::SINGLE_TRANSACTION;
		$transaction->status 			= 'waiting_payment';
		$transaction->gross_value 	 	= $value;
		$transaction->provider_value 	= 0;
		$transaction->gateway_tax_value = 0;
		$transaction->net_value 		= 0;
		$transaction->save();

		try {

			$postBack = route('GatewayPostbackBillet') . "/" . $transaction->id;
			$billetExpiration = Carbon::now()->addDays(7)->toIso8601String();
			$gateway = LibsPaymentFactory::createGateway();
			$payment = $gateway->billetCharge($value, $holder, $postBack, $billetExpiration, "Adicionar saldo em conta.");

			if($payment['success']){
				$billet_link = $payment['billet_url'];
				$digitable_line = isset($payment['digitable_line']) ? $payment['digitable_line'] : '';
				$gateway_transaction_id = $payment['transaction_id'];

				$paymentTax = floatval($gateway->getGatewayTax());
				$paymentFee = floatval($gateway->getGatewayFee());

				//Save the billet in transaction table (not in finance yet. In finance table is when billet is paid)
				$transaction->type 				= Transaction::SINGLE_TRANSACTION;
				$transaction->status 			= 'waiting_payment';
				$transaction->gross_value 	 	= $value;
				$transaction->provider_value 	= 0;
				$transaction->gateway_tax_value = ($value * $paymentTax) + $paymentFee;
				$transaction->net_value 		= $value - $transaction->gateway_tax_value ;
				$transaction->gateway_transaction_id = $gateway_transaction_id;
				$transaction->billet_link		= $billet_link;
				$transaction->ledger_id			= $holder->ledger->id;
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
					email_notification(
						$holder->id,
						$envType == 'provider' ? 'provider' : 'user',
						$vars,
						$subject,
						$key_email,
						null
					);
				} catch(\Exception $e){
					\Log::error("Erro ao enviar boleto por email.");
					\Log::error($e->getMessage());
				}

			}
			//Se deu erro, deleta a transaction do boleto
			else {
				$transaction->delete();
				return response()->json($payment, 400);
			}

			return response()->json([
				'success' => true,
				'billet_url' => $billet_link,
				'digitable_line' => $digitable_line,
				'error' => false
			]);

			return new AddBilletBalanceResource($data);

		} catch (\Throwable $th) {
			$transaction->delete();
			return response()->json(["error" => "Erro ao gerar boleto"], 503);
		}
	}

	/**
	 * Add balance by paying with billet
	 *
	 * @param AddBilletBalanceWebFormRequest $request
	 * @return NewBillet ($request->value, $enviroment['holder'], $enviroment['type'])
	 */
	public function addBilletBalanceWeb(AddBilletBalanceWebFormRequest $request) {
		$enviroment = $this->getEnviroment();
		return $this->newBillet($request->value, $enviroment['holder'], $enviroment['type']);
	}

	/**
	 * Add balance by paying with billet
	 *
	 * @param AddBilletBalanceFormRequest $request
	 * @return NewBillet ($request->value, $user, 'user')
	 */
	public function addBilletBalance(AddBilletBalanceFormRequest $request) {

		$user = User::find($request->id);
		$ledgerId = $user->ledger->id;
		return $this->newBillet($request->value, $user, 'user');
	}

	/**
	 * Add balance by paying with billet
	 *
	 * @param AddBilletBalanceFormRequest $request
	 * @return NewBillet ($request->value, $provider, 'provider')
	 */
	public function addBilletBalanceProvider(AddBilletBalanceFormRequest $request) {

		$provider = Provider::find($request->id);
		$ledgerId = $provider->ledger->id;
		return $this->newBillet($request->value, $provider, 'provider');
	}

	/**
	 * Get payment options defined by admin
	 *
	 * @return Array $data
	 */
	private function getAddBalanceSettings() {
		$data = array();

		$data['prepaid_min_billet_value']		= Settings::findByKey('prepaid_min_billet_value');
		$data['prepaid_tax_billet'] 			= Settings::findByKey('prepaid_tax_billet');
		$data['prepaid_billet_user'] 			= Settings::findByKey('prepaid_billet_user');
		$data['prepaid_billet_provider']		= Settings::findByKey('prepaid_billet_provider');
		$data['prepaid_billet_corp'] 			= Settings::findByKey('prepaid_billet_corp');
		$data['prepaid_card_user']				= Settings::findByKey('prepaid_card_user');
		$data['prepaid_card_provider'] 			= Settings::findByKey('prepaid_card_provider');
		$data['prepaid_card_corp']				= Settings::findByKey('prepaid_card_corp');
		$data['prepaid_pix_user']				= Settings::findByKey('prepaid_pix_user');
		$data['prepaid_pix_corp'] 				= Settings::findByKey('prepaid_pix_corp');
		$data['prepaid_pix_provider']			= Settings::findByKey('prepaid_pix_provider');
		$data['with_draw_enabled']				= Settings::findByKey('with_draw_enabled', false);
		$data['indication_settings']			= LibModel::getCustomIndicationSettings();

		return $data;
	}

	/**
	 * Add a new credit card
	 * @deprecated use now addCreditCardProvider or addCreditCardUser
	 * 
	 * @param AddCardUserFormRequest $request
	 * @return NewCreditCard ($enviroment['holder'], $enviroment['type'], $request)
	 */
	public function addCreditCard(AddCardUserFormRequest $request) {
		$enviroment = $this->getEnviroment();
		return $this->newCreditCard($enviroment['holder'], $enviroment['type'], $request);
	}

	/**
	 * Add a new credit card
	 *
	 * @param AddCardProviderFormRequest $request
	 * @return AddCreditCardResource
	 */
	public function addCreditCardProvider(AddCardProviderFormRequest $request) 
	{	
		$response = Payment::providerCreateCardByGateway(
			$request->providerId, 
			$request->cardNumber, 
			$request->cardHolder, 
			$request->cardExpMonth, 
			$request->cardExpYear, 
			$request->cardCvv,
			null,
			null,
			$request->document ?? null
		);
		return new AddCreditCardResource($response);
	}

	/**
	 * Add a new credit card
	 *
	 * @param AddCardUserFormRequest $request
	 * @return AddCreditCardResource
	 */
	public function addCreditCardUser(AddCardUserFormRequest $request) {
		$response = Payment::createCardByGateway(
			$request->userId, 
			$request->cardNumber, 
			$request->cardHolder, 
			$request->cardExpMonth, 
			$request->cardExpYear, 
			$request->cardCvv,
			null,
			null,
			$request->document ?? null
		);
		return new AddCreditCardResource($response);
	}

	/**
	 * Add a new credit card
	 *
	 * @param AddCardUserFormRequest $request
	 * @return AddCreditCardResource
	 */
	public function addCreditCardAdminUser(AddCardUserFormRequest $request) 
	{
		$response = Payment::createCardByGateway(
			$request->userId, $request->cardNumber, 
			$request->cardHolder, 
			$request->cardExpMonth, 
			$request->cardExpYear, 
			$request->cardCvv,
			null,
			null,
			$request->document ?? null
		);
		return new AddCreditCardResource($response);
	}

	/**
	 * Get the typo of user
	 *
	 * @return Array ('type' => $type,'id' => $id,'holder' => $holder);
	 */
	private function getEnviroment() {
		$type = Request::segment(1);
		switch($type){
			case Finance::TYPE_USER:
				$id = Auth::guard("clients")->user()->id;
				$holder = User::find($id);
				$type = 'user';
			break;
			case Finance::TYPE_CORP:
				$admin_id = LibModel::getGuardWebCorp();
				$holder = AdminInstitution::getUserByAdminId($admin_id);
				$id = $holder->id;
				$type = 'corp';
			break;
			case Finance::TYPE_PROVIDER:
				$id = \Auth::guard("providers")->user()->id;
				$holder = Provider::find($id);
				$type = 'provider';
			break;
		}
		return array(
			'type' => $type,
			'id' => $id,
			'holder' => $holder
		);
	}

	/**
	 * Importa baixa de pagamentos para prestadores
	 *
	 * @param ImportPaymentsRequest $request
	 * @return Redirect
	 */
	public function importProviderPayments(ImportPaymentsRequest $request)
	{
		Excel::import(new PaymentsImport($request->delimeter, $request->date_format), $request->file);

		return Redirect::route('AdminProviderExtract');
	}

	/**
	 * Render consolidated statement blade
	 *
	 * @return view
	 */
	public function consolidatedExtract()
	{
		$locations = \Location::select('id', 'name')->get()->toArray();
		$partners = [];

		return view('finance::financial.consolidated_extract', [
			'locations' => $locations,
			'partners' => $partners
		]);
	}

	/**
	 * Download the consolidated statement
	 *
	 * @param GetConsolidatedStatementRequest $request
	 * @return Response
	 */
	public function downloadConsolidatedExtract(GetConsolidatedStatementRequest $request)
	{
		$filename = "extrato-consolidado".date("Y-m-d-hms", time()).".csv";
		$handle = fopen(storage_path('tmp/').$filename, 'w+');
		fputs( $handle, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF) );

		$ledgers = LibModel::filterConsolidated($request, true);

		$vars = [
			trans('financeTrans::finance.ledger_id'),
			trans('financeTrans::finance.name'),
			trans('financeTrans::finance.type'),
			trans('financeTrans::finance.period_requests_count'),
			trans('financeTrans::finance.period_balance'),
			trans('financeTrans::finance.total_ro_receive'),
			trans('financeTrans::finance.future_balance'),
			trans('financeTrans::finance.current_balance'),
			trans('financeTrans::finance.hit_value')
		];

		fputcsv($handle, $vars, ";" );

		foreach ($ledgers as $item) {
			$vars = [
				$item->ledger_id,
				$item->user_name,
				trans('financeTrans::finance.' . $item->user_type),
				$item['balances']['period_request_count'],
				$item['balances']['period_balance_text'],
				$item['balances']['total_balance_text'],
				$item['balances']['future_balance_text'],
				$item['balances']['current_balance_text'],
				$item['balances']['payment_value_text'],
			];

			fputcsv($handle, $vars ,";");
		}

		fclose($handle);
		$headers = array(
			'Content-Type' => 'text/csv; charset=utf-8',
			'Content-Disposition' => 'attachment; filename='. $filename,
		);
		return Response::download(storage_path('tmp/').$filename, $filename, $headers);
	}

	/**
	 * Fetch the consolidated statement data
	 *
	 * @param GetConsolidatedStatementRequest $request
	 * @return json
	 */
	public function consolidatedExtractFetch(GetConsolidatedStatementRequest $request)
	{
		return response()->json([
			'consolidated' => LibModel::filterConsolidated($request)
		]);
	}

	/**
	 * Get pix data save in transaction table, by transactionId or request_id
	 * @param int $transaction_id or $request_id
	 * 
	 * @return RetrievePixResource
	 */
	public function retrievePix()
    {
		$paymentChanged = false;
		$ride = null;
		$success = true;

		$type = Input::get('type');
		
		$debit = Finance::find(Input::get('debit_id'));
		if(Input::get('transaction_id')) {
			$transaction = Transaction::find(Input::get('transaction_id'));
		} else if(Input::get('request_id')) {
			$transaction = Transaction::getTransactionByRequestId(Input::get('request_id'));
		}

		if(!$transaction) {
			return (new RetrievePixResource([
				'success' 			=> false,
				'message'			=> 'transaction not found'
			]))->response()->setStatusCode(HttpResponse::HTTP_NOT_FOUND);
		}
		
		$ride = $transaction->ride;
		$expiratedFormated = date('d/m/Y H:i:s', strtotime($transaction->pix_expiration_date_time));
		$isPaid = $transaction->status == 'paid' ? true : false;
		if(!$isPaid && $ride && $ride->is_paid) {
			$isPaid = true;
		}

		if($transaction->status == 'error') {
			$success = false;
		}

		if($ride && $ride->payment_mode != RequestCharging::PAYMENT_MODE_GATEWAY_PIX){
			$paymentChanged = true;
		}

		$transaction->ride = $ride; 
		$transaction->isPaid = $isPaid; 
		
		if ($transaction->status == 'paid') {
			$request = Requests::find($transaction->request_id) ?? Requests::where('scheduled_id', $transaction->scheduled_id)->first();
			
			if (isset($request)) {
				$schedule = ScheduledRequests::find($request->scheduled_id);

				$schedule->is_paid = 1;
				$schedule->save();
				$request->is_paid = 1;
				$request->save();
			}
			if (isset($debit)) {
				$debit->transaction_id = $transaction->id;
				$debit->is_paid = 1;
				$debit->value = 0;
				$debit->save();
			}
		}

		$transaction->paymentChanged = $paymentChanged; 
		$transaction->expiratedFormated = $expiratedFormated; 

		return (new RetrievePixResource([
			'success' 			=> $success,
			'transaction' 		=> $transaction
			]))->response()->setStatusCode(HttpResponse::HTTP_OK);
	}

	/**
	 * Get pix data save in transaction table, by transactionId or request_id
	 * @param BalanceFormRequest $request
	 * @return GetBalanceResource
	 */
	public function getBalance(BalanceFormRequest $request)
    {
		$success = false;
		$error = '';
		try {
			$balance = $request->holder->getBalance();
			$success = true;
		} catch(\Exception $e) {
			\Log::error($e->getMessage() . $e->getTraceAsString());
			$error = trans('financeTrans::finance.error_get_balance');
		}
		return new BalanceResource([
			'success' 		=> $success,
			'balance' 		=> currency_format(currency_converted($balance)),
			'balance_value' => currency_converted($balance),
			'error'			=> $error
		]);
	}
	
	/**
	 * Check if transaction is from the holder
	 *
	 * @return View
	 */
	public function pixCheckout()
    {
		$transaction_id = Input::get('id');
		$enviroment = $this->getEnviroment();
		$transaction = Transaction::find($transaction_id);

		//check if trasaction is from the holder
		if($transaction && $transaction->ledger_id == $enviroment['holder']->ledger->id) {
			return View::make('finance::payment.pix')
			->with('enviroment', $enviroment['type'])
			->with('transaction_id', $transaction_id)
			->with('pix_copy_paste', $transaction->pix_copy_paste)
			->with('pix_base64', $transaction->pix_base64)
			->with('value', currency_format(currency_converted($transaction->gross_value)));
		} else {
			abort(404);
		}
	}

	/**
	 * Add balance by paying with pix
	 *
	 * @param AddPixBalanceFormRequest $request
	 * @return NewPix ($request->value, $enviroment['holder'], $enviroment['type'])
	 */
	public function addPixBalanceWeb(AddPixBalanceFormRequest $request) {
		$enviroment = $this->getEnviroment();
		return $this->newPix($request->value, $enviroment['holder'], $enviroment['type']);
	}


	/**
	 * Add balance by paying with pix
	 *
	 * @param AddPixBalanceFormRequest $request
	 * @return NewPix ($request->value, $user, 'user')
	 */
	public function addPixBalance(AddPixBalanceFormRequest $request) {
		$user = User::find($request->id);
		return $this->newPix($request->value, $user, 'user');
	}

	/**
	 * Add balance by paying with pix
	 *
	 * @param AddPixBalanceFormRequest $request
	 * @return NewPix ($request->value, $provider, 'provider')
	 */
	public function addPixBalanceProvider(AddPixBalanceFormRequest $request) {
		$provider = Provider::find($request->id);
		return $this->newPix($request->value, $provider, 'provider');
	}

	/**
	 * Create transaction to put her ID in PostBack.If you make an error, delete this transaction, senation, updates them with gateway data
	 *
	 * @param Decimal $value
	 * @param Object $holder		
	 * @param String $envType
	 * @return NewPix ($request->value, $provider, 'provider')
	 */
	private function newPix($value, $holder, $envType) {

		//cria a transaction, para colocar o id dela no postback. Se der erro, deleta essa transaction, senao, atualiza elas com os dados do gateway
		$transaction 					= new Transaction();
		$transaction->type 				= Transaction::SINGLE_TRANSACTION;
		$transaction->status 			= 'waiting_payment';
		$transaction->gross_value 	 	= $value;
		$transaction->provider_value 	= 0;
		$transaction->gateway_tax_value = 0;
		$transaction->net_value 		= 0;
		$transaction->ledger_id			= $holder->ledger->id;
		$transaction->save();

		try {
			$postBack = route('GatewayPostbackPix') . "/" . $transaction->id;
			$gateway = LibsPaymentFactory::createPixGateway();
			$payment = $gateway->pixCharge($value, $holder);

			if($payment['success']){
				$transaction->gateway_transaction_id = $payment['transaction_id'];
				$transaction->pix_base64 = $payment['qr_code_base64'];
				$transaction->pix_copy_paste = $payment['copy_and_paste'];
				$transaction->save();

				return response()->json([
					'success' => true,
					'copy_and_paste' => $payment['copy_and_paste'],
					'qr_code_base64' => $payment['qr_code_base64'],
					'transaction_id' => $transaction->id
				]);
			}
			//Se deu erro, deleta a transaction do pix
			else {
				$transaction->delete();
				return response()->json($payment, 503);
			}

		} catch (\Throwable $th) {
			$transaction->delete();
			\Log::error($th->getMessage());

			return response()->json(["error" => "Erro ao gerar pix"], 503);
		}
	}

	/**
	 * Change the pix keys
	 *
	 * @return json	(array(
	 * 		'money' 			=> (bool)Settings::getPaymentMoney(),
	 *		'money_code' 		=> RequestCharging::PAYMENT_MODE_MONEY,
	 *
	 *		'direct_pix' 		=> (bool)Settings::getPaymentDirectPix(),
	 *		'direct_pix_code' 	=> RequestCharging::PAYMENT_MODE_DIRECT_PIX,
	 *
	 *		'machine' 			=> (bool)Settings::getPaymentMachine(),
	 *		'machine_code' 		=> RequestCharging::PAYMENT_MODE_MACHINE
	 *	));
	 */
	public function changePixPaymentTypes() {
		return response()->json(array(
			'money' 			=> (bool)Settings::getPaymentMoney(),
			'money_code' 		=> RequestCharging::PAYMENT_MODE_MONEY,

			'direct_pix' 		=> (bool)Settings::getPaymentDirectPix(),
			'direct_pix_code' 	=> RequestCharging::PAYMENT_MODE_DIRECT_PIX,

			'machine' 			=> (bool)Settings::getPaymentMachine(),
			'machine_code' 		=> RequestCharging::PAYMENT_MODE_MACHINE,

			'card' 				=> (bool)Settings::getPaymentCard(),
			'card_code' 		=> RequestCharging::PAYMENT_MODE_CARD
		));
	}

	/**
	 * Change the pix payment form during the request
	 *
	 * @param changePixPaymentRequest $request
	 * @return json	(array(
	 * 		'success' => true,
	 *		'bill' => $req->getBill()
	 *	));
	 */
	public function changePixPayment(changePixPaymentRequest $request) {
		try {
			$providerId = $request->provider_id ? $request->provider_id : $request->id;
			$req = Requests::find($request->request_id);
			if (!$req) {
				$req = Requests::where('scheduled_id', $request->request_id)->first();
			}
			if (Settings::changePaymentByUser() == 1) {
				if ($request->new_payment_mode == RequestCharging::PAYMENT_MODE_CARD) {
					$req->payment_mode = $request->new_payment_mode;
					$req->save();


					//dispara eveneto para o usuario
					event(new PixUpdate($req->request_price_transaction_id, false, true));

					$response = RequestCharging::chargeNoCapture($req->user_id, $req->total, $req->provider_commission, $req->confirmed_provider, $request->new_payment_mode);
					if ($response->status == 'paid') {
						$req->is_paid = 1;
						$req->save();
						return response()->json([
							'success' => true,
							'bill' => $req->getBill()
						]);
					}
					return response()->json([
						'success' => false,
						'bill' => null
					]);
				} else {
					if($req->payment_mode == RequestCharging::PAYMENT_MODE_GATEWAY_PIX) {
						// troca a forma de pagamento
						$req->payment_mode = $request->new_payment_mode;
						$req->save();
	
						//faz a logica da cobranca com a nova forma de pagamento
						\RequestCharging::requestCompleteCharge($req->id);
					}
					//dispara eveneto para o usuario
					event(new PixUpdate($req->request_price_transaction_id, false, true));
	
					return response()->json([
						'success' => true,
						'bill' => $req->getBill()
					]);
				}
			}
			if($req && $req->confirmed_provider == $providerId && Settings::changePaymentByUser() == 0) {
				if($req->payment_mode == RequestCharging::PAYMENT_MODE_GATEWAY_PIX) {
					// troca a forma de pagamento
					$req->payment_mode = $request->new_payment_mode;
					$req->save();

					//faz a logica da cobranca com a nova forma de pagamento
					\RequestCharging::requestCompleteCharge($req->id);
				}
				//dispara eveneto para o usuario
				event(new PixUpdate($req->request_price_transaction_id, false, true));

				return response()->json([
					'success' => true,
					'bill' => $req->getBill()
				]);
			} else { // this request is not of the auth provider
				return response()->json([
					'success' => false,
					'bill' => null
				]);
			}

		} catch (\Exception $e) {
			\Log::error($e->getMessage() . $e->getTraceAsString());
			return response()->json([
				'success' => false,
				'bill' => null
			]);
		}
	}
}