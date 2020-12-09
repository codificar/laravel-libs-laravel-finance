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
		return (double)number_format(self::where('ledger_id', $ledgerId)->where('compensation_date', '<', date('Y-m-d H:i:s'))->sum('value'), 2, '.', '');
	}
	
	public static function sumAllValueByLedgerId($ledgerId){
		return (double)number_format(self::where('ledger_id', $ledgerId)->sum('value'), 2, '.', '');
    }
    public static function sumValueByLedgerIdByPeriod($ledgerId, $startDate, $endDate){
		$startDateNew = Carbon::parse($startDate);
		$endDateNew = Carbon::parse($endDate);
		return (double)number_format(self::where('ledger_id', $ledgerId)->whereBetween('created_at', [$startDateNew, $endDateNew])->where('compensation_date', '<', date('Y-m-d 23:59:59'))->sum('value'), 2, '.', '');
	}

    /**
	 * Get the provider current week profits
	 * @param int $providerId
	 */
	public static function getProviderProfitsOfWeek($providerId) 
	{
		$startDate = Carbon::now()->addDay();
		$endDate = Carbon::now()->addDay();
		
        $query = DB::table('request')->select(DB::raw(
			"concat(DAYOFWEEK(request_finish_time)) AS day, SUM(provider_commission) AS value")
			)->whereBetween('request_finish_time', [
				$startDate->startOfWeek()->subDay(), 
				$endDate->endOfWeek()->subDay()
			])
			->where('confirmed_provider', $providerId)
			->groupBy('day');

			return $query;
	}
    
    /**
	 * Get the provider current week profits value, just where payment_mode is money
	 * @param int $providerId
	 */

	public static function getProviderProfitsOfWeekMoneyValue($providerId) 
	{
		$startDate = Carbon::now()->addDay();
		$endDate = Carbon::now()->addDay();
		
        $query = DB::table('request')->select(DB::raw(
			"SUM(provider_commission) AS value")
			)->whereBetween('request_finish_time', [
				$startDate->startOfWeek()->subDay(), 
				$endDate->endOfWeek()->subDay()
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
			$balance = self::orderBy('created_at','ASC');
			
			if($ledgerId != ''){
				$balance->where('ledger_id', $ledgerId);
			}
			if($transactionType != ''){
				$balance->where('reason', $transactionType); 
			}
			if($startDate != ''){
				$balance->where('created_at', '>=', $startDate);
			}
			if($endDate != ''){
				$balance->where('created_at', '<=', $endDate);
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

			$response = array(
				'previous_balance' 			  => $previousBalance ,
				'current_balance' 			  => $currentBalance ,
				'total_balance'				  => $totalBalance,
				'period_balance'			  => $periodBalance,
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
				$query->having('total', ">", 0);
				$query->orderBy('total', 'desc');
			} else if ($orderBalance == "negative") {
				$query->having('total', "<", 0);
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
    
}
