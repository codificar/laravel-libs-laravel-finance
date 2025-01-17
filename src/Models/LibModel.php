<?php

namespace Codificar\Finance\Models;


use Codificar\PaymentGateways\Libs\PaymentFactory as LibsPaymentFactory;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Eloquent;
use Finance;
use Ledger;
use Provider;
use Payment;
use Settings;
use RequestCharging;
use DB;


class LibModel extends Eloquent
{
	const DEPOSIT_IN_ACCOUNT 		= 'DEPOSIT_IN_ACCOUNT';
	const SIMPLE_INDICATION 		= 'SIMPLE_INDICATION';
	const COMPENSATION_INDICATION 	= 'COMPENSATION_INDICATION';
	const SEPARATE_CREDIT 			= 'SEPARATE_CREDIT';
	const SEPARATE_DEBIT 			= 'SEPARATE_DEBIT';
	const WITHDRAW 					= 'WITHDRAW';
	const WITHDRAW_REJECT 			= 'WITHDRAW_REJECT';
	const WITHDRAW_REQUESTED 		= 'WITHDRAW_REQUESTED';
	const RIDE_DEBIT 				= 'RIDE_DEBIT';
	const RIDE_DEBIT_SPLIT 			= 'RIDE_DEBIT_SPLIT';
	const RIDE_DEBIT_PENDING_PIX 	= 'RIDE_DEBIT_PENDING_PIX';
	const RIDE_CREDIT_PENDING_PIX 	= 'RIDE_CREDIT_PENDING_PIX';
	const RIDE_CREDIT 				= 'RIDE_CREDIT';
	const RIDE_CREDIT_SPLIT 		= 'RIDE_CREDIT_SPLIT';
	const MACHINE_RIDE_DEBIT		= 'MACHINE_RIDE_DEBIT';
	const MACHINE_RIDE_CREDIT		= 'MACHINE_RIDE_CREDIT';
	const RIDE_CANCELLATION_DEBIT 	= 'RIDE_CANCELLATION_DEBIT';
	const RIDE_CANCELLATION_CREDIT 	= 'RIDE_CANCELLATION_CREDIT';
	const RIDE_PAYMENT 				= 'RIDE_PAYMENT';
	const CARTO_RIDE_PAYMENT		= 'CARTO_RIDE_PAYMENT';
	const RIDE_PAYMENT_FAIL_DEBIT 	= 'RIDE_PAYMENT_FAIL_DEBIT';
	const RIDE_LEDGER 				= 'RIDE_LEDGER';
	const AUTO_WITHDRAW				= 'AUTO_WITHDRAW';
	const CLEANING_FEE_DEBIT 		= 'CLEANING_FEE_DEBIT';
	const CLEANING_FEE_CREDIT 		= 'CLEANING_FEE_CREDIT';	
	const SIGNATURE_DEBIT			= 'SIGNATURE_DEBIT';
	const SIGNATURE_CREDIT			= 'SIGNATURE_CREDIT';
	const TYPE_USER 		= 'user';
	const TYPE_PROVIDER 	= 'provider';
	const TYPE_CORP 		= 'corp';
	const TYPE_ADMIN 		= 'admin';
	const TYPES = [ 
		self::SEPARATE_CREDIT, 
		self::SEPARATE_DEBIT,
		self::RIDE_DEBIT,
		self::RIDE_CREDIT,
		self::RIDE_LEDGER,
		self::SIMPLE_INDICATION,
		self::COMPENSATION_INDICATION,
		self::RIDE_CANCELLATION_DEBIT,
		self::RIDE_CANCELLATION_CREDIT,
		self::RIDE_PAYMENT,
		self::WITHDRAW,
		self::WITHDRAW_REJECT,
		self::CLEANING_FEE_DEBIT,
		self::CLEANING_FEE_CREDIT
	];

    protected $table = 'finance';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;


    public static function sumValueByLedgerId($ledgerId){
		return (double)number_format(self::where('ledger_id', $ledgerId)->where('compensation_date', '<=', date('Y-m-d H:i:s'))->sum('value'), 2, '.', '');
	}
	
	public static function sumAllValueByLedgerId($ledgerId){
		return (double)number_format(self::where('ledger_id', $ledgerId)->sum('value'), 2, '.', '');
    }


	public static function sumPositiveValueByLedgerId($ledgerId){
		return (double)number_format(
			self::where('ledger_id', $ledgerId)
				->where('value', '>=',  0)
				->sum('value')
			, 2, '.', '');
	}

	public static function sumNegativeValueByLedgerId($ledgerId){
		return (double)number_format(
			self::where('ledger_id', $ledgerId)
				->where('value', '<', 0)
				->sum('value')
			, 2, '.', '');
	}

    public static function sumValueByLedgerIdByPeriod($ledgerId, $startDate, $endDate){
		$startDateNew = Carbon::parse($startDate);
		$endDateNew = Carbon::parse($endDate);
		return (double)number_format(self::where('ledger_id', $ledgerId)->whereBetween('compensation_date', [$startDateNew, $endDateNew])->sum('value'), 2, '.', '');
	}

	private static function getStartDateEarningsReport() {
		$startWeek = intval(Settings::findByKey("earnings_report_weekday"));
		if(!$startWeek) {
			$startWeek = 1;
		}

		//dia da semana de hoje. Soma 1, pois o carbon comeca domingo com 0, e o mysql comeca com 1
		$dayOfWeekToday = Carbon::now()->dayOfWeek + 1;
		
		//se o dia da semana de hoje e menor que o dia da semana escolhido, a data a ser subtraida (data de inicio do relatorio de ganhos) sera (7-(escolhido - hoje))
		//Ex 1: hoje e terca (3) e o dia escolhido e quinta (5), logo: 7-(5-3) = 5, logo deve ser subtraido 5 dias
		//Ex 2: hoje e domingo (1) e o dia escolhido e sabado (7), logo 7-(7-1) = 1, logo deve ser subtraido 1 dia
		if($dayOfWeekToday < $startWeek) {
			$subDiffDays = 7 - ($startWeek - $dayOfWeekToday);
		}
		//se o dia da semana de hoje e maior que o dia da semana escolhido, a data a ser subtraida sera (hoje - escolhido)
		//Ex 1: hoje e sabado (7) e o dia escolhido e quarta (4), logo: 7-4 = 3, logo deve ser subtraido 3 dias do dia de hoje para pegar a data inicial
		//Ex 2: hoje e terca (3) e o dia escolhido e segunda (2), logo 3-2 = 1, logo deve ser subtraido 1 dia de hoje para pegar a data inicial
		else if($dayOfWeekToday > $startWeek) {
			$subDiffDays = $dayOfWeekToday - $startWeek;
		}
		//se o dia da semana de hoje e igual o dia da semana escolhido, nao deve subtrair nada, pois a semana comeca hoje
		else {
			$subDiffDays = 0;
		}
		return Carbon::now()->startOfDay()->subDays($subDiffDays);
	}

    /**
	 * Get the provider current week profits
	 * @param int $providerId
	 */
	public static function getProviderProfitsOfWeek($providerId) {
		$startDate = LibModel::getStartDateEarningsReport();
		$endDate = $startDate->copy()->addDays(6)->endOfDay();

		$query = DB::table('request')->select(DB::raw(
			"concat(DAYOFWEEK(request_finish_time)) AS day, SUM(provider_commission) AS value")
			)->whereBetween('request_finish_time', [
				$startDate->format('Y-m-d H:i:s'),
				$endDate->format('Y-m-d H:i:s')
			])
			->where('confirmed_provider', $providerId)
			->groupBy('day')
			->get();
			
			$finance = array();
			for ($i=0; $i < 7; $i++) {
				$finance[$i] = array(
					"day" => $startDate->copy()->addDays($i)->dayOfWeek + 1,
					"value" => 0.0,
					"value_text" => currency_format(0.0)
				);
				
				foreach ($query as $item) {
					if($finance[$i]['day'] == intval($item->day)) {
						$finance[$i]['value'] = round($item->value, 2);
						$finance[$i]['value_text'] = currency_format(abs(round($item->value, 2)));
					}
				}
				
			}
			return $finance;
	}
    
    /**
	 * Get the provider current week profits value, just where payment_mode is money
	 * @param int $providerId
	 */

	public static function getProviderProfitsOfWeekMoneyValue($providerId) 
	{
		$startDate = LibModel::getStartDateEarningsReport();
		$endDate = $startDate->copy()->addDays(6)->endOfDay();
		
        $query = DB::table('request')->select(DB::raw(
			"SUM(provider_commission) AS value")
			)->whereBetween('request_finish_time', [
				$startDate->format('Y-m-d H:i:s'),
				$endDate->format('Y-m-d H:i:s'),
			])
			->where('payment_mode', RequestCharging::PAYMENT_MODE_MONEY)
			->where('confirmed_provider', $providerId)
			->first();
				
			return $query->value;
    }
    
    public static function getWithDrawEnabled(){
		$settings = DB::table('settings')->where('key', 'with_draw_enabled')->first();

		if($settings)
			return $settings->value;
		else
			return false ;
    }
    

    /**
	 * Get the available time in this week
	 */
	public static function getWeekOnlineTime($providerId)
	{
		$startDate = Carbon::now()->addDay()->startOfWeek();
		$endDate = Carbon::now()->addDay()->endOfWeek();

		$time = DB::table('provider_availability')->selectRaw("TIMESTAMPDIFF(
					MINUTE,
					provider_availability.start,
					provider_availability.end) as time"
				)
				->where("provider_id", $providerId)
				->whereBetween('start', [
					$startDate->subDay()->toDateString(), 
					$endDate->subDay()->toDateString()
				])
				->get()->sum("time");

		return $time;
    }
    
    /**
	 * Count rides in current week
	 * @param int $providerId
	 * @return array
	 */
	public static function getWeekRidesCount ($providerId)
	{
		$startDate = Carbon::now()->addDay();
		$endDate = Carbon::now()->addDay();

		return DB::table('request')->where("confirmed_provider", $providerId)
					->where("is_completed", true)
					->whereBetween("created_at", [
						$startDate->startOfWeek()->subDay(), 
						$endDate->endOfWeek()->subDay()
					])->count();
	}
	
	public static function getCardsList($id, $type = 'user')
	{
		$payments = array();
		$payment = array();

		if($type == 'user') {
			$payment = DB::table('payment')->where('user_id', $id)->orderBy('is_default', 'DESC')->get();
		} else if($type == 'provider') {
			$payment = DB::table('payment')->where('provider_id', $id)->orderBy('is_default', 'DESC')->get();
		}

        foreach ($payment as $value) {
            $data['id'] 			= $value->id;
			$data['user_id'] 		= $value->user_id;
			$data['provider_id'] 	= $value->provider_id;
            $data['customer_id'] 	= $value->customer_id;
            $data['last_four'] 		= $value->last_four;
            $data['card_token'] 	= $value->card_token;
            $data['card_type'] 		= $value->card_type;
            $data['card_id'] 		= $value->card_token;
			$data['is_default'] 	= $value->is_default;
			$data['is_default_text']= $value->is_default ?  "default" : "not_default";

            array_push($payments, $data);
		}
		return $payments;
	}
	
	public static function getCreditCardUser($userId, $cardId)
	{
		$payment = Payment::where('id', $cardId)
			->where('user_id', $userId)
			->first();

		return $payment;
	}
	public static function getCreditCardProvider($providerId, $cardId)
	{
		$payment = Payment::where('id', $cardId)
			->where('provider_id', $providerId)
			->first();

		return $payment;
	}

		/**
     * find by userId
     * @return Payment 
 	 **/
	  public static function findDefaultOrFirstByUserId($userId, $cardId = null) {
		if($cardId) {
			$userCard = Payment::where('user_id', $userId)->where('id', $cardId)->first();
			if($userCard) 
				return $userCard ;
		}
		return Payment::where('user_id', $userId)->orderBy('is_default', 'desc')->first();
	}

	
    public static function getBalanceBeforeDate($ledgerId, $startDate){

		if($ledger = Ledger::find($ledgerId)){
			$response = (double)number_format(self::where('ledger_id', $ledgerId)->where('compensation_date', '<', $startDate )->sum('value'), 2, '.', '');
		} else{
			$response = "Ledger not found";
		}
		return $response;
	}
    

    /**
	 * Obtém o balanço detalhado do ledger pelo período enviado
	 * 
	 * @return void
	 */
	public static function getLedgerDetailedBalanceByPeriod($ledgerId, $transactionType, $startDate, $endDate, $page=1, $itensPerPage = 25)
	{
		if(!$page) $page = 1; 
		if(!$itensPerPage) $itensPerPage = 25;

		if($ledger = DB::table('ledger')->find($ledgerId)){
			$previousBalance = self::getBalanceBeforeDate($ledgerId, $startDate);
			$currentBalance = self::sumValueByLedgerId($ledgerId);
			$currentPositiveBalance = self::sumPositiveValueByLedgerId($ledgerId);
			$currentNegativeBalance = self::sumNegativeValueByLedgerId($ledgerId);
			$totalBalance = self::sumAllValueByLedgerId($ledgerId);
			$periodBalance = self::sumValueByLedgerIdByPeriod($ledgerId, $startDate, $endDate);
			$totalBalanceByPeriod = $previousBalance + $periodBalance;
			$balance = self::orderBy('compensation_date','ASC');
			
			if($ledgerId != ''){
				$balance->where('ledger_id', $ledgerId);
			}
			if($transactionType != ''){
				$balance->where('reason', $transactionType); 
			}
			if($startDate != ''){
				$balance->where('compensation_date', '>=', $startDate);
			}
			if($endDate != ''){
				$balance->where('compensation_date', '<=', $endDate);
			}

			// resolve current page
			$currentPage = $page;

			Paginator::currentPageResolver(function () use ($currentPage) {
				return $currentPage;
			});

			$currentCompensations = $futureCompensations = array();

			$currentBalanceQuery	=	$balance;
			$futureBalanceQuery		=	clone $currentBalanceQuery;

			$currentCompensations	=	$currentBalanceQuery->where('compensation_date', '<=', date("Y-m-d 23:59:59"))->paginate($itensPerPage);
			$futureCompensations	=	$futureBalanceQuery->where('compensation_date', '>', date("Y-m-d 23:59:59"))->get();

			$paginateCount	=	$currentCompensations->count();
			$paginateTotal	=	$currentCompensations->total();

			foreach($currentCompensations as $item) {
				$item['value_formatted'] = currency_format(currency_converted($item['value']));
			}

			$response = array(
				'previous_balance' 			  => $previousBalance,
				'current_balance' 			  => $currentBalance,
				'current_balance_formatted'	  => currency_format(currency_converted($currentBalance)),
				'total_balance'				  => $totalBalance,
				'current_positive_balance' 	  => $currentPositiveBalance, 
				'current_negative_balance' 	  => $currentNegativeBalance,
				'period_balance'			  => $periodBalance,
				'period_balance_formatted'	  => currency_format(currency_converted($periodBalance)),
				'total_balance_by_period'	  => $totalBalanceByPeriod,
				'detailed_balance_count' 	  => $paginateCount,
				'detailed_balance_total'      => $paginateTotal,
				'detailed_balance' 			  => $currentCompensations,
				'current_compensations' 	  => $currentCompensations->getCollection()->toArray(),
				'future_compensations' 		  => $futureCompensations
			);
		
		} else{
			$response = "Ledger not found";
		}
		return $response;
	}


	public static function providerSearch($id, $name, $email, $state, $city, $brand, $partnerId, $statusId, $order, $type, $arrPartner, $locationId, $cnh, $phone, $startDateCompensation, $endDateCompensation, $startedBy=null, $approvedBy=null, $registerStep=null,$providerExtract=null, $startDateCreated=null, $endDateCreated=null, $sendDocs=null, $orderBalance = null)
	{
		if($partnerId != "") $arrPartner = array($partnerId);
		
		if (is_array($arrPartner) && count($arrPartner)) {
			$query = Provider::byPartners($arrPartner);
		}
		else {			
			$query = LibModel::prepareQuery();
		}

		if($providerExtract && ($startDateCompensation && $endDateCompensation) || ($startDateCreated && $endDateCreated )){
			$query = LibModel::prepareQueryExtract();
		}
		
		if($providerExtract && $startDateCompensation && $endDateCompensation ){					
			$startDateCompensation = Carbon::createFromFormat('d/m/Y', $startDateCompensation)->format('Y-m-d 00:00:00');			
			$endDateCompensation = Carbon::createFromFormat('d/m/Y', $endDateCompensation)->format('Y-m-d 00:00:00');					
			$query->whereBetween('compensation_date', array($startDateCompensation, $endDateCompensation));
		}
		
		if($providerExtract && $startDateCreated && $endDateCreated ){
			$startDateCreated = Carbon::createFromFormat('d/m/Y', $startDateCreated)->format('Y-m-d 00:00:00');			
			$endDateCreated = Carbon::createFromFormat('d/m/Y', $endDateCreated)->format('Y-m-d 23:59:59');				
			$query->whereBetween('finance.compensation_date', array($startDateCreated, $endDateCreated));
		}

		if ($locationId != 0){
			$query->where("provider.location_id", "=", $locationId);
		}
		
		if ($id != ""){
			$query->where('provider.id', '=', $id);
		}
		if ($name != ""){
			$query->where(DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $name . '%');
		}
		if ($email != ""){
			$query->where('email', 'like', '%' . $email . '%');
		}

		if($phone != ""){
			$query->where('phone','like','%' . $phone . '%');
		}

		if ($state != ""){
			$query->where('state', 'like', '%' . $state . '%');
		}

		if ($city != ""){
			$query->where('address_city', 'like', '%' . $city . '%');
		}

		if ($brand != ""){
			$query->where('car_number', 'like', '%' . $brand . '%');
		}

		if (is_array($statusId) || $statusId != 0){
			if(is_array($statusId)){
				$query->whereIn('provider_status.id', $statusId);
			} else{
				$query->where('provider_status.id', '=', $statusId);	
			}
		}
		
		if($cnh){
			$query->where('cnh_number','like','%'.$cnh.'%');
		}
		//data de inicio
		if ($startDateCompensation && !$providerExtract) {
			$startDateCompensation = date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $startDateCompensation)));
			$query = $query->where('provider.created_at', '>=', $startDateCompensation);
					
		}
		//data de fim
		if($endDateCompensation && !$providerExtract){
			$query = $query->where('provider.created_at', '<=', $endDateCompensation);
			$endDateCompensation = date("Y-m-d H:i:s", strtotime(str_replace('/', '-', $endDateCompensation)));				
		}

		if(isset($startedBy) && $startedBy != ""){
			$query->where('provider.started_by', "=", $startedBy);
		}

		if(isset($approvedBy) && $approvedBy != ""){
			$query->where('provider.approved_by', "=", $approvedBy);
		}
		
		// Verificar aqui
		if(isset($registerStep) && $registerStep != ""){
			$query->where('provider.register_step', "=", $registerStep);
		}

		if (isset($sendDocs) && $sendDocs != "") {
			$query->where('provider.all_docs', "=", $sendDocs);
		}

		// Filtro para saldo total
		if ( isset($orderBalance) && $orderBalance != "" ) {
			$query->addSelect( DB::raw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) AS total") );
		}
		
		$query->groupBy('provider.id');

		if ($order == "") {

			if ($orderBalance == "positive") {
				$query->whereRaw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) > 0");
				$query->orderBy('total', 'desc');
			} else if ($orderBalance == "negative") {
				$query->whereRaw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) < 0");
				$query->orderBy('total', 'asc');
			} else {
				$query->orderBy('provider.id', 'DESC');
			}
			
		} else {
			if ($order == 0 && $providerExtract)				
				$query->orderBy($type, 'asc');
			else if ($order == 1)
				$query->orderBy($type, 'desc');
		}
		return $query->distinct();
	}

	private static function prepareQuery()
	{
		$subQuery = DB::table('request_meta')
			->select(DB::raw('count(*)'))
			->whereRaw('provider_id = provider.id and status != 0');

		$subQuery1 = DB::table('request_meta')
			->select(DB::raw('count(*)'))
			->whereRaw('provider_id = provider.id and status = 1');

		$query = Provider::select('provider.*', 'ledger.id as ledger_id', 'provider_status.name as status_name', DB::raw("(" . $subQuery->toSql() . ") as 'total_requests'"), DB::raw("(" . $subQuery1->toSql() . ") as 'accepted_requests'"))
					->leftJoin('provider_status', 'provider.status_id', '=', 'provider_status.id');

		$query->leftJoin('ledger as ledger', 'provider.id', '=', 'ledger.provider_id');

		return $query ;
	}

	private static function prepareQueryExtract(){

		$subQuery = DB::table('request_meta')
			->select(DB::raw('count(*)'))
			->whereRaw('provider_id = provider.id and status != 0');

		$subQuery1 = DB::table('request_meta')
			->select(DB::raw('count(*)'))
			->whereRaw('provider_id = provider.id and status = 1');

		$query = Provider::select('finance.compensation_date','finance.created_at as finance_created_at','provider.*', 'ledger.id as ledger_id', 'provider_status.name as status_name', DB::raw("(" . $subQuery->toSql() . ") as 'total_requests'"), DB::raw("(" . $subQuery1->toSql() . ") as 'accepted_requests'"))
					->leftJoin('provider_status', 'provider.status_id', '=', 'provider_status.id');

		$query->leftJoin('ledger as ledger', 'provider.id', '=', 'ledger.provider_id')
				->join('finance', 'finance.ledger_id','=', 'ledger.id');

		return $query ;
	}

	public static function getGuardWebCorp()
	{
		$user = \Auth::guard('web')->user();

        if (!$user || !$user->AdminInstitution) {
            $user = \Auth::guard('web_corp')->user();

			if ($user)
				return $user->id;
        }
		
		return $user ? $user->id : null;
	}
    
	public static function getSumTotalIndication($ledger_id) {
		return Finance::sumTotalIndicationByLedgerId($ledger_id);
	}

	public static function getSumMonthlyIndication($ledger_id) {
		try {
			return Finance::sumMonthlyIndicationByLedgerId($ledger_id);
		} catch (\Throwable $th) {
			return 0;
		}
		
	}  

	public static function getCustomIndicationSettings() {
		$data = [
			'isCustomIndicationEnabled' => (boolean)Settings::findByKey('has_custom_indication'),
			'program_name'				=> Settings::findByKey('program_name')			
		];

		return $data;
	}

	public static function getCurrencySymbol()
	{		

		$internationalizationFormat= \Config::get('enum.Internationalization.CurrencyFormatting');
		// if project has CurrencyFormatting in enum (Ex: delivery - entregas)
		if($internationalizationFormat) {
			$currencyKey = \Settings::getInternationalizationCurrency();
			foreach ($internationalizationFormat as $cf) {
				if ($currencyKey == $cf['key']) {
					$chrAcronym = $cf['chrAcronym'];
				}
			}
			return $chrAcronym ? $chrAcronym : "R$";
		} 
		//others projects (motorista privado, servicos etc)
		else {
			$currency = Settings::findByKey('generic_keywords_currency');
			$currency_symbol = Settings::getCurrencySymbol($currency);
			$currency_symbol = $currency_symbol ? $currency_symbol : "R$";
			return $currency_symbol ? $currency_symbol : "R$";
		}
	}

	/**
	 * Filter consolidated statement data
	 * 
	 * @param GetConsolidatedStatementRequest $request
	 * @param boolean $download
	 * @return array
	 */
	public static function filterConsolidated($request, $download = false)
	{
		$type = $request->type != '' ? $request->type : '';
		$order = $request->balance != '' ? $request->balance : '';
		$location = $request->location != '' ? $request->location : '';
		$startDate = $request->startDate != '' ? $request->startDate : '';
		$endDate = $request->endDate != '' ? $request->endDate : '';
		$keyWord = $request->key_word != '' ? $request->key_word : '';

		$leders = Ledger::select(
				'ledger.id as ledger_id',
				'ledger.user_id as user_id',
				'ledger.provider_id',
				'user.first_name as user_firstname',
				'user.last_name as user_lastname',
				'provider.first_name as provider_firstname',
				'provider.last_name as provider_lastname',
				'institution.id as is_corp'
			)
			->where('ledger.admin_id', null)
			->leftJoin('user', 'user.id', '=', 'ledger.user_id')
			->leftJoin('provider', 'provider.id', '=', 'ledger.provider_id')
			->leftJoin('institution', 'institution.default_user_id', '=', 'ledger.user_id');

		if ($startDate != '' && $endDate != '') {
			$startDateCreated = date("Y-m-d 00:00:00", strtotime($startDate));
			$endDateCreated = date("Y-m-d 23:59:59", strtotime($endDate));
			$leders->join('finance', 'finance.ledger_id', '=', 'ledger.id');
			$leders->whereBetween('finance.compensation_date', array($startDateCreated, $endDateCreated));
		}

		if ($type == 'user') {
			$leders->where('user.id', '<>', null)
				->where('institution.id', null);
		} elseif ($type == 'provider') {
			$leders->where('provider.id', '<>', null);
		} elseif ($type == 'corp') {
			$leders->where('user.id', '<>', null)
				->where('institution.id', '<>', null);
		}

		if ($keyWord != '') {
			$leders->where(DB::raw('CONCAT_WS(" ", user.first_name, user.last_name)'), "LIKE", '%' . $keyWord . '%')
				->orWhere(DB::raw('CONCAT_WS(" ", provider.first_name, provider.last_name)'), "LIKE", '%' . $keyWord . '%')
				->orWhere('user.email', "LIKE", '%' . $keyWord . '%')
				->orWhere('provider.email', "LIKE", '%' . $keyWord . '%');
		}

		$leders->groupBy('ledger.id');

		if ($order != "") {

			$leders->addSelect( DB::raw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) AS total") );

			if ($order == "positive") {
				$leders->whereRaw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) > 0");
				$leders->orderBy('total', 'desc');
			} else if ($order == "negative") {
				$leders->whereRaw("COALESCE((SELECT SUM(value) from finance WHERE finance.ledger_id = ledger.id), 0) < 0");
				$leders->orderBy('total', 'asc');
			}
			
		} else {
			$leders = $leders->orderBy('ledger.id', 'desc');
		}

		if ($location != '') {
			if ($type == '') {
				$leders->where('user.location_id', $location)
					->orWhere('provider.location_id', $location);
			} elseif ($type == 'user' || $type == 'corp') {
				$leders->where('user.location_id', $location);
			} elseif ($type == 'provider') {
				$leders->where('provider.location_id', $location);
			}
		}

		if ($download) {
			$leders = $leders->get();
		} else {
			$leders = $leders->paginate(20);
		}

		foreach ($leders as $item) {
			$start = $startDate != '' ? date('Y-m-d H:i:s', strtotime($startDate)) : date('Y-m-d H:i:s', strtotime($item->created_at));
			$end = $endDate != '' ? date('Y-m-d 23:59:59', strtotime($endDate)) :  date('Y-m-d 23:59:59');
			
			$balances = [
				'total_balance' => self::sumAllValueByLedgerId($item->ledger_id),
				'current_balance' => self::sumValueByLedgerId($item->ledger_id),
				'period_balance' => self::sumValueByLedgerIdByPeriod($item->ledger_id, $start, $end),
				'period_request_count' => self::where('ledger_id', $item->ledger_id)
					->whereNotNull('request_id')
					->whereBetween('finance.compensation_date', array($start, $end))
					->count()
			];

			$balances['future_balance'] = $balances['total_balance'] - $balances['current_balance'];
			$balances['payment_value'] = $balances['current_balance'];

			$balances['total_balance_text'] = self::currency_format($balances['total_balance']);
			$balances['current_balance_text'] = self::currency_format($balances['current_balance']);
			$balances['future_balance_text'] = self::currency_format($balances['future_balance']);
			$balances['period_balance_text'] = self::currency_format($balances['period_balance']);
			$balances['payment_value_text'] = $balances['payment_value'] >= 0 ? self::currency_format($balances['payment_value']) : trans('financeTrans::finance.client_in_debit');

			$item->balances = $balances;
			if($item->user_id) {
				$item->is_deleted = self::isUserDeleted($item->user_id);
				
			} elseif($item->provider_id) {
				$item->is_deleted = self::isProviderDeleted($item->provider_id);
			}
			//$item->is_deleted = 
			$item->user_type = $item->is_corp != null ? 'corp' : ($item->user_id != null ? 'user' : 'provider');
			$item->user_name = $item->provider_id != null ? 
				$item->provider_firstname . ' ' . $item->provider_lastname :
				$item->user_firstname . ' ' . $item->user_lastname;
			$item->extract_url = '/admin/libs/finance/' . ($item->provider_id != null ? "provider/$item->provider_id" : "user/$item->user_id");
		}

		return $leders;
	}


	/**
	 * Verify if user is deleted
	 * @param int $userId
	 * 
	 * @return bool
	 * 
	 */
	public static function isUserDeleted(int $userId): bool
	{
		$user = \User::where('id', $userId)
			->withTrashed()
			->first();
		if ($user->deleted_at) {
			return true;
		} 
		return false;
	}

	/**
	 * Verify if provider is deleted
	 * @param int $providerId
	 * 
	 * @return bool
	 * 
	 */
	public static function isProviderDeleted(int $providerId): bool
	{
		$provider = \Provider::where('id', $providerId)
			->withTrashed()
			->first();
		if ($provider->deleted_at) {
			return true;
		} 
		return false;
	}

	public static function currency_format($fltNumber, $chrAcronym = null, $intPrecision = 2, $chrDecimal = ',', $chrThousand = '.', $currency_formatting = true)
	{
		$internationalizationFormat= \Config::get('enum.Internationalization.CurrencyFormatting');

		if (!$internationalizationFormat)
			$chrAcronym = "R$ ";

		if (!$chrAcronym) {
			$currencyKey = \Settings::getInternationalizationCurrency();
		
			if ($currency_formatting){
				foreach ($internationalizationFormat as $cf) {
					if ($currencyKey == $cf['key']) {
						$chrAcronym = $cf['chrAcronym'];
						$intPrecision = $cf['intPrecision'];
						$chrDecimal = $cf['chrDecimal'];
						$chrThousand = $cf['chrThousand'];
					}
				}
			}
		} 
		if($chrAcronym == null) $chrAcronym = "R$ ";
	
		if ($chrAcronym) $chrAcronym = $chrAcronym . ' ';
		return $chrAcronym . number_format(floatval($fltNumber), $intPrecision, $chrDecimal, $chrThousand);
	}



	/**
	 * Add user balance by paying with credit card
	 *
	 * @param Decimal $value
	 * @param User $holder
	 * @param Integer|null $cardId -
	 * @return array  $data;
	 */
	public static function addCreditCardUserBalance(float $value, \User $user, int $cardId = null) {
		$ledgerId = $user->ledger->id;
		$payment = LibModel::findDefaultOrFirstByUserId($user->id, $cardId);

		$data = array();
		//Se nao encontrou o card, então da erro
		if(!$payment) {
			$data['success']	= false;
			$data['error']		= trans('financeTrans::finance.card_not_found');
			$data['current_balance'] = currency_format(self::sumValueByLedgerId($ledgerId));
		} else {
			//Tenta realizar a cobrança com o cartão
			$gateway = LibsPaymentFactory::createGateway();
			$return = $gateway->charge($payment, $value, trans('financeTrans::finance.credit_by_debit'), true);

			//Se conseguiu cobrar no cartão, então adicionar um saldo para o usuário/prestador, senão, retorna erro
			if($return['success'] && $return['captured'] == 'true') {
				$financeEntry = Finance::createCustomEntry($ledgerId, 'SEPARATE_CREDIT', trans('financeTrans::finance.credit_by_debit'), $value, null, null);
				if($financeEntry) {
					$data['success']	= true;
					$data['error']		= null;
					$data['current_balance'] = currency_format(self::sumValueByLedgerId($ledgerId));
				}
			} else {
				$data['success']	= false;
				$data['error']		= trans('financeTrans::finance.card_refused');
				$data['current_balance'] = currency_format(self::sumValueByLedgerId($ledgerId));
			}
		}
		return $data;
	}

	/**
	 * Create Ride Credit
	 * @param int $ledgerId
	 * @param float $value
	 * @param int $rideId
	 * @param string $reason
	 * @param bool $finishByAdmin
	 * 
	 * @return void
	 */
	public static function createRideCredit(
		$ledgerId, 
		$value, 
		$rideId, 
		$reason = \Finance::RIDE_CREDIT, 
		$finishByAdmin = false,
		int|null $transactionId = null
	){
		try{
			$paymentMode = null;
			$ride = \Requests::find($rideId);
			if($ride && isset($ride->payment_mode)) {
				$paymentMode = $ride->payment_mode;
			}
			
			if(!$reason) {
				$reason = self::getStringReason($paymentMode);
			}

			$trans = self::getStringPaymentMethod($paymentMode, $finishByAdmin);
			
			$finance = new \Finance();
			$finance->ledger_id = $ledgerId;
			$finance->referral_id = null;
			$finance->value = $value * -1;
			$finance->reason = $reason;
			$finance->description = sprintf($trans, $rideId);
			$finance->request_id = $rideId;
			$finance->transaction_id = $transactionId;
			$finance->compensation_date = date('Y-m-d H:i:s');
			$finance->save();
		}catch(\Exception $e){
			throw $e;
		}
	}


	/**
	* Create a Custom Credit Debit 
	* @param int $ledgerId
	* @param string $reason
	* @param string $description
	* @param float $value
	* @param string|null $date default: null | format: 'd/m/Y'
	* @param int|null $insertedBy default: null
	* @param int|null $transactionId default: null
	* 
	* @return Finance
	*/
	public static function createCustomEntry(
		int $ledgerId, 
		string $reason, 
		string $description, 
		float $value, 
		string|null $date = null, 
		int|null $insertedBy = null, 
		int|null $transactionId = null
	): \Finance 
	{
		try{
			$finance = new \Finance();
			$finance->ledger_id = $ledgerId;
			$finance->value = $value;
			$finance->reason = $reason;
			$finance->description = $description;
			$finance->inserted_by = $insertedBy;
			$finance->transaction_id = $transactionId;
			$compensationDate = $date;
			if($compensationDate){
                $finance->compensation_date = Carbon::createFromFormat('d/m/Y', $compensationDate);
            }
            else {
                $finance->compensation_date = date('Y-m-d H:i:s');
            }
			$finance->save();
			return $finance;
		}catch(\Exception $e){
			\Log::error($e->getMessage() . $e->getTraceAsString());
			throw $e;
		}
	}

	/**
    * get string translated reason by payment Mode
    * @param int $paymentMode  
    * @return string
    */
    public static function getStringReason(int $paymentMode)
    {
		switch ($paymentMode) {
			case \RequestCharging::PAYMENT_MODE_MACHINE:
				return self::MACHINE_RIDE_CREDIT;
				break;
			default:
				return self::RIDE_CREDIT;
				break;
		}
    }


    /**
    * get string translated by $rideId and $isAdmin
    * @param int $paymentMode  
    * @param bool $isAdmin - default: false
    * @return string  
    */
    public static function getStringPaymentMethod(int $paymentMode, $isAdmin = false, $isDebit = false)
    {
		if($isDebit) {
			$arrayStringsPaymentMethod = self::getArrayPaymentModesDebit($isAdmin);
		} else {
			$arrayStringsPaymentMethod = self::getArrayPaymentModesCredit($isAdmin);
		}

        return $paymentMode && isset($arrayStringsPaymentMethod[$paymentMode])
			? $arrayStringsPaymentMethod[$paymentMode] 
			: trans('finance.ride_credit');
    }


	/**
	 * get array Translate payment modes
	 * @param bool $isAdmin
	 * @return array
	 */
	public static function getArrayPaymentModesCredit($isAdmin = false)
	{
		return !$isAdmin 
		? [
			\RequestCharging::PAYMENT_MODE_CARD => trans('finance.ride_card_payment'),
			\RequestCharging::PAYMENT_MODE_MONEY => trans('finance.ride_credit'),
			\RequestCharging::PAYMENT_MODE_CARTO => trans('finance.ride_carto_payment'),
			\RequestCharging::PAYMENT_MODE_MACHINE => trans('finance.ride_credit_machine'),
			\RequestCharging::PAYMENT_MODE_CRYPT => trans('finance.ride_crypt_payment'),
			\RequestCharging::PAYMENT_MODE_ASSOCIATION => trans('finance.ride_association_payment'),
			\RequestCharging::PAYMENT_MODE_CARD_DEBIT => trans('finance.ride_debitCard_payment'),
			\RequestCharging::PAYMENT_MODE_BALANCE => trans('finance.ride_credit_balance'),
			\RequestCharging::PAYMENT_MODE_BILLING => trans('finance.ride_credit_billing'),
			\RequestCharging::PAYMENT_MODE_GATEWAY_PIX => trans('finance.ride_credit_gateway_pix'),
			\RequestCharging::PAYMENT_MODE_DIRECT_PIX => trans('finance.ride_credit_direct_pix'),
		] : [
			\RequestCharging::PAYMENT_MODE_CARD => trans('finance.admin_ride_card_payment'),
			\RequestCharging::PAYMENT_MODE_MONEY => trans('finance.admin_ride_credit'),
			\RequestCharging::PAYMENT_MODE_CARTO => trans('finance.admin_ride_carto_payment'),
			\RequestCharging::PAYMENT_MODE_MACHINE => trans('finance.admin_ride_credit_machine'),
			\RequestCharging::PAYMENT_MODE_CRYPT => trans('finance.admin_ride_crypt_payment'),
			\RequestCharging::PAYMENT_MODE_ASSOCIATION => trans('finance.admin_ride_association_payment'),
			\RequestCharging::PAYMENT_MODE_CARD_DEBIT => trans('finance.admin_ride_debitCard_payment'),
			\RequestCharging::PAYMENT_MODE_BALANCE => trans('finance.admin_ride_credit_balance'),
			\RequestCharging::PAYMENT_MODE_BILLING => trans('finance.admin_ride_credit_billing'),
			\RequestCharging::PAYMENT_MODE_GATEWAY_PIX => trans('finance.admin_ride_credit_gateway_pix'),
			\RequestCharging::PAYMENT_MODE_DIRECT_PIX => trans('finance.admin_ride_credit_direct_pix'),
		];
	}

	/**
	 * get array Translate payment modes
	 * @param bool $isAdmin
	 * @return array
	 */
	public static function getArrayPaymentModesDebit($isAdmin = false)
	{
		return !$isAdmin 
		? [
			\RequestCharging::PAYMENT_MODE_CARD => trans('finance.debit_ride_card_payment'),
			\RequestCharging::PAYMENT_MODE_MONEY => trans('finance.debit_ride_credit'),
			\RequestCharging::PAYMENT_MODE_CARTO => trans('finance.debit_ride_carto_payment'),
			\RequestCharging::PAYMENT_MODE_MACHINE => trans('finance.debit_ride_debit_machine'),
			\RequestCharging::PAYMENT_MODE_CRYPT => trans('finance.debit_ride_crypt_payment'),
			\RequestCharging::PAYMENT_MODE_ASSOCIATION => trans('finance.debit_ride_association_payment'),
			\RequestCharging::PAYMENT_MODE_CARD_DEBIT => trans('finance.debit_ride_debitCard_payment'),
			\RequestCharging::PAYMENT_MODE_BALANCE => trans('finance.debit_ride_credit_balance'),
			\RequestCharging::PAYMENT_MODE_BILLING => trans('finance.debit_ride_credit_billing'),
			\RequestCharging::PAYMENT_MODE_GATEWAY_PIX => trans('finance.debit_ride_credit_gateway_pix'),
			\RequestCharging::PAYMENT_MODE_DIRECT_PIX => trans('finance.debit_ride_credit_direct_pix'),
		] : [
			\RequestCharging::PAYMENT_MODE_CARD => trans('finance.admin_debit_ride_card_payment'),
			\RequestCharging::PAYMENT_MODE_MONEY => trans('finance.admin_debit_ride_credit'),
			\RequestCharging::PAYMENT_MODE_CARTO => trans('finance.admin_debit_ride_carto_payment'),
			\RequestCharging::PAYMENT_MODE_MACHINE => trans('finance.admin_debit_ride_debit_machine'),
			\RequestCharging::PAYMENT_MODE_CRYPT => trans('finance.admin_debit_ride_crypt_payment'),
			\RequestCharging::PAYMENT_MODE_ASSOCIATION => trans('finance.admin_debit_ride_association_payment'),
			\RequestCharging::PAYMENT_MODE_CARD_DEBIT => trans('finance.admin_debit_ride_debitCard_payment'),
			\RequestCharging::PAYMENT_MODE_BALANCE => trans('finance.admin_debit_ride_credit_balance'),
			\RequestCharging::PAYMENT_MODE_BILLING => trans('finance.admin_debit_ride_credit_billing'),
			\RequestCharging::PAYMENT_MODE_GATEWAY_PIX => trans('finance.admin_debit_ride_credit_gateway_pix'),
			\RequestCharging::PAYMENT_MODE_DIRECT_PIX => trans('finance.admin_debit_ride_credit_direct_pix'),
		];
	}
	
}
