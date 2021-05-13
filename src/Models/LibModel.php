<?php

namespace Codificar\Finance\Models;

use Illuminate\Database\Eloquent\Relations\Model;
use Illuminate\Http\Request;
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
    public static function sumValueByLedgerIdByPeriod($ledgerId, $startDate, $endDate){
		$startDateNew = Carbon::parse($startDate);
		$endDateNew = Carbon::parse($endDate);
		return (double)number_format(self::where('ledger_id', $ledgerId)->whereBetween('created_at', [$startDateNew, $endDateNew])->where('compensation_date', '<', date('Y-m-d 23:59:59'))->sum('value'), 2, '.', '');
	}

	private function getStartDateEarningsReport() {
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
			$startDateCompensation = date("Y-m-d 00:00:00", strtotime(str_replace('/', '-', $startDateCompensation)));			
			$endDateCompensation = date("Y-m-d 23:59:59", strtotime(str_replace('/', '-', $endDateCompensation)));					
			$query->whereBetween('compensation_date', array($startDateCompensation, $endDateCompensation));
		}
		
		if($providerExtract && $startDateCreated && $endDateCreated ){
			$startDateCreated = date("Y-m-d 00:00:00", strtotime(str_replace('/', '-', $startDateCreated)));			
			$endDateCreated = date("Y-m-d 23:59:59", strtotime(str_replace('/', '-', $endDateCreated)));			
			$query->whereBetween('finance.created_at', array($startDateCreated, $endDateCreated));
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

		$query = Provider::select('finance.compensation_date','finance.created_at','provider.*', 'ledger.id as ledger_id', 'provider_status.name as status_name', DB::raw("(" . $subQuery->toSql() . ") as 'total_requests'"), DB::raw("(" . $subQuery1->toSql() . ") as 'accepted_requests'"))
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
	
}
