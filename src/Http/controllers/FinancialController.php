<?php

namespace Codificar\Finance\Http\Controllers;

use Codificar\Finance\Models\LibModel;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use App\Http\Controllers\Controller;
use AccountController;
use Finance;
use Schema;
use User;
use Provider;
use Ledger;
use LedgerBankAccount;
use Bank;
use Settings;
use AdminInstitution;
use Codificar\Finance\Http\Requests\GetFinancialSummaryByTypeAndDateFormRequest;
use App\Http\Requests\api\v3\ProviderProfitsRequest;
use Codificar\Finance\Http\Resources\GetFinancialSummaryByTypeAndDateResource;
use App\Http\Resources\api\v3\ProviderProfitsResource;
use App\Http\Requests\FinanceFormRequest;
use App\Http\Resources\FinanceResource;
use App\Models\Institution;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use ProviderAvail;
use Requests;

class FinancialController extends Controller
{

	protected $accountController;

	public function __construct()
	{
		$this->accountController = new AccountController;
	}

	/**
	 * Return the ledger`s financial statement.
	 * 
	 * @return Response
	 */
	public function getAnalyticalBalanceByLedgerIdAndMonth()
	{

		$ledgerId = Input::get('ledger_id');
		$transactionType = Input::get('transaction_type');
		$startDate = Input::get('start_date');
		$endDate = Input::get('end_date');


		$validator = Validator::make(
			array(
				'ledger_id' => $ledgerId,
				'startDate' => $startDate,
				'endDate' 	=> $endDate
			),
			array(
				'ledger_id' 	=> 'required',
				'startDate' => 'required',
				'endDate' 	=> 'required'
			),
			array(
				'ledger_id.required' 		=> trans('accountController.unique_id_missing'),
				'startDate.required' 	=> $startDate,
				'endDate.required' 		=> $endDate
			)
		);
		if ($validator->fails()) {
			$errorMessages = $validator->messages()->all();
			$responseArray = array('success' => false, 'error' => trans('accountController.invalid_input'), 'errorCode' => 401, 'errorMessages' => $errorMessages);
			$responseCode = 200;
			$response = Response::json($responseArray, $responseCode);
		} else {
			$response = $this->accountController->getAnalyticalBalanceByLedgerIdAndMonth($ledgerId, $transactionType, $startDate, $endDate);
		}
		return $response;
	}
	// save finance
	public function store(Request $request)
	{
		// validate finance and eager objects
		$this->validate($request, $this->getRules());

		$model = new Finance();
		$finance = $model->store($request);
		return $finance;
	}
	// update finance by id
	public function update($id, Request $request)
	{
		// validate finance and eager objects
		$this->validate($request, $this->getRules($id));

		$model = new Finance();
		$finance = $model->updateModel($id, $request);
		return $finance;
	}
	// get finance by id
	public function show($id)
	{
		$model = new Finance();
		$finance = $model->show($id);
		return $finance;
	}
	// find first by field and value
	public function findByField(Request $request)
	{
		$model = new Finance();
		$finance = $model->findByField($request);
		return $finance;
	}
	// build all validation rules
	protected function getRules($id = null)
	{
		// default object rules
		$model = new Finance();
		$rules = $model::$rules;
		$finance = $model->show($id);

		// nested rules for eager objects


		return $rules;
	}
	// delete model by ids
	public function destroy($id)
	{
		$ids = explode(",", $id);
		$ids = array_unique($ids);
		$model = new Finance();
		$success = $model->destroy($ids);
		$status = array("error" => true, "message" => "Error deleting object");
		if ($success)
			$status = array("error" => false, "message" => "Object successfully deleted");
		return json_encode($status);
	}

	// query with search and pagination options
	public function query(Request $request)
	{
		$model = new Finance();
		$query = $model->querySearch($request);
		return $query;
	}
	// query with fields filters and pagination
	//json = {"pagination": {"actual": 1, "itensPerPage": 20}, "fields": ["name","email","cnpj"], "orderBy": "name"}
	public function queryFilters(Request $request)
	{
		$model = new Finance();
		$finance = $model->queryFilters($request);

		return $finance;
	}
	/**
	 * View the financial statement of the user or provider in their respective panels.
	 * 
	 * @return View
	 */
	public function userProviderCheckingAccount()
	{
		$type = \Request::segment(1);
		$holderType = "";
		switch ($type) {
			case Finance::TYPE_USER:
				$id = \Auth::guard("clients")->user()->id;
				$holder = User::find($id);
				$holder->full_name = $holder->getFullName();
				$page = 'finance::user_panel.userFinancial_summary';
				$notfound = 'user_panel.userLogin';
				$loginType = 'user';
				$holderType = Finance::TYPE_USER;
				break;
			case Finance::TYPE_PROVIDER:
				$id = \Auth::guard("providers")->user()->id;
				$holder = Provider::find($id);
				$holder->full_name = $holder->getFullName();
				$page = 'finance::provider_panel.financial_summary';
				$notfound = 'provider_panel.login';
				$loginType = 'provider';
				$holderType = Finance::TYPE_PROVIDER;
				break;
			case Finance::TYPE_CORP:
				$admin_id = LibModel::getGuardWebCorp();
				$admin = \Admin::find($admin_id);
				$institution = $admin->adminInstitution->institution;
				$holder = AdminInstitution::getUserByAdminId($admin_id);
				$holder->full_name = $institution->name;
				$id = $holder->id;
				$page = 'finance::corp.financial_summary';
				$notfound = 'corp.login';
				$loginType = 'corp';
				$holderType = Finance::TYPE_CORP;
				break;
		}
		$label = Input::has('label-range') ? Input::get('label-range') : trans('finance.monthNames.' . date('n') . '', array('y' => date('Y')));
		if ($holder && $holder->ledger) {
			if (Input::get('start_date') != '') {
				$startDate = Carbon::createFromFormat('d/m/Y', Input::get('start_date'))->format('Y-m-d 00:00:00');
			} else {
				$startDate = Carbon::today();
				if ($startDate->day == 1) {
					$startDate->month = $startDate->month - 1;
				} else {
					$startDate->day = 1;
				}
			}
			if (Input::get('end_date') != '') {
				$endDate = Carbon::createFromFormat('d/m/Y', Input::get('end_date'));
			} else {
				$endDate = Carbon::today();
			}
			// $startDate->setTime(0, 0, 0);
			// $endDate->setTime(23, 59, 59);
			$title = trans('finance.account_statement');
			$balance = Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, '', $startDate, $endDate, null, null);
			if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
				return $this->downloadFinancialReport($type, $holder, $balance, $startDate, $endDate);
			} else { // if (Input::get('submit') && Input::get('submit') == 'Download_Report') 
				$types = Finance::TYPES; //Prepares Finance types array to be used on vue component
				$banks  = Bank::orderBy('code', 'asc')->get(); //List of banks
				$account_types = LedgerBankAccount::getAccountTypes(); //List of AccountTypes

				// Pega o símbolo da moeda
				$currency_symbol = LibModel::getCurrencySymbol() . " ";

				$withDrawSettings = array(
					'with_draw_enabled' 	=> Settings::getWithDrawEnabled(),
					'with_draw_max_limit' 	=> Settings::getWithDrawMaxLimit(),
					'with_draw_min_limit'	=> Settings::getWithDrawMinLimit(),
					'with_draw_tax'			=> Settings::getWithDrawTax()
				);
				return View::make($page)
					->with([
						'enviroment'		=> $loginType,
						'id' 				=> $id,
						'login_type' 		=> $loginType,
						'holder' 			=> $holder,
						'title' 			=> $title,
						'balance' 			=> $balance,
						'start' 			=> $startDate->format(Settings::getDefaultDateFormat()),
						'end' 				=> $endDate->format(Settings::getDefaultDateFormat()),
						'page' 				=> 'financial',
						'types'		 		=> $types,
						'bankaccounts' 		=> $holder->ledger->bankAccounts,
						'banks' 			=> $banks,
						'account_types' 	=> $account_types,
						'withdrawsettings' 	=> $withDrawSettings,
						'currency_symbol' 	=> $currency_symbol,
						'holder_type' => $holderType
					]);
			}
		} else { //if($holder && $holder->ledger)
			return View::make($notfound)->with('title', trans('adminController.page_not_found'))->with('page', trans('adminController.page_not_found'));
		}
	}

	/**
	 * Display the user or provider financial summary.
	 * 
	 * @return View
	 */
	public function getFinancialSummary()
	{
		$path = explode('/', \Request::path());

		if ($path[0] == 'admin') {
			$id = $path[count($path) - 1];
			$type = $path[count($path) - 2];
			$loginType = 'admin';
			$holderType = "";

			/**
			 * Expected request from ../{type}/{id}
			 * $type can be user or provider
			 */
			switch ($type) {
				case Finance::TYPE_USER:
					$holder = User::find($id);
					$holder->full_name = $holder->getFullName();
					$holderType = Finance::TYPE_USER;
					break;
				case Finance::TYPE_PROVIDER:
					$holder = Provider::find($id);
					$holder->full_name = $holder->getFullName();
					$holderType = Finance::TYPE_PROVIDER;
			}
		} else if ($path[0] == Finance::TYPE_USER) {
			$id = \Auth::guard("clients")->user()->id;
			$holder = User::find($id);
			$holder->full_name = $holder->getFullName();
			$loginType = 'user';
			$holderType = Finance::TYPE_USER;
		} else if ($path[0] == Finance::TYPE_PROVIDER) {
			$id = \Auth::guard("providers")->user()->id;
			$holder = Provider::find($id);
			$holder->full_name = $holder->getFullName();
			$loginType = 'provider';
			$holderType = Finance::TYPE_PROVIDER;
		}

		if (Input::get('type_entry') != '0') {
			$typeEntry = Input::get('type_entry');
		} else {
			$typeEntry = '';
		}

		if ($holder && $holder->ledger) {
			$startDate = Input::get('start_date');
			$endDate = Input::get('end_date');

			// Define data inicial
			if ($startDate != '') {
				$startDate = Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d 00:00:00');
			} else {
				// Utiliza a data atual, caso não seja informada
				$startDate = Carbon::today();

				// Define o tempo em minutos, segundos e milésimos
				$startDate->setTime(0, 0, 0);
			}

			// Define a data final
			if ($endDate != '') {
				$endDate = Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d 23:59:59');
			} else {
				// Utiliza a data atual, caso não seja informada
				$endDate = Carbon::today();

				// Define o tempo em minutos, segundos e milésimos
				$endDate->setTime(23, 59, 59);
			}

			$title = trans('finance.account_statement');

			$banks  = Bank::orderBy('code', 'asc')->get(); //List of banks
			$account_types = LedgerBankAccount::getAccountTypes(); //List of AccountTypes
			$types = Finance::TYPES; //Prepares Finance types array to be used on vue component
			$futureCompensations = array();

			// Pega o símbolo da moeda
			$currency_symbol = LibModel::getCurrencySymbol() . " ";

			// With draw settings
			$withDrawSettings = array(
				'with_draw_enabled' 	=> Settings::getWithDrawEnabled(),
				'with_draw_max_limit' 	=> Settings::getWithDrawMaxLimit(),
				'with_draw_min_limit'	=> Settings::getWithDrawMinLimit(),
				'with_draw_tax'			=> Settings::getWithDrawTax()
			);

			// Download report
			if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
				//limit of 10000 rows in csv file
				$balance = Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate, null, 10000);
				return $this->downloadFinancialReport($type, $holder, $balance, $startDate, $endDate);
			} else {
				$balance = Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate, null, null);
				return View::make("finance::financial.account_summary")
					->with([
						'id' => $id,
						'login_type' => $loginType,
						'holder' => $holder,
						'balance' => $balance,
						'start' => $startDate->format(Settings::getDefaultDateFormat()),
						'end' => $endDate->format(Settings::getDefaultDateFormat()),
						'page' => 'financial',
						'types' => $types,
						'bankaccounts' => $holder->ledger->bankAccounts,
						'banks' => $banks,
						'account_types' => $account_types,
						'withdrawsettings' => $withDrawSettings,
						'currency_symbol' => $currency_symbol,
						'holder_type' => $holderType
					]);
			}
		} else {
			return View::make('notfound')->with('title', trans('adminController.page_not_found'))->with('page', trans('adminController.page_not_found'));
		}
	}

	public function getFinancialSummaryByDate()
	{
		$path = explode('/', \Request::path());
		if ($path[0] == 'admin') {
			$id = $path[count($path) - 3];
			$type = $path[count($path) - 4];
			$loginType = 'admin';
			$startDate = $path[count($path) - 2];
			$endDate = $path[count($path) - 1];
			$startDate = Carbon::parse($startDate);
			$endDate = Carbon::parse($endDate);
			$endDate = $endDate->addDay(1);
			/** $type can be user or provider */
			switch ($type) {
				case Finance::TYPE_USER:
					$holder = User::find($id);
					break;
				case Finance::TYPE_PROVIDER:
					$holder = Provider::find($id);
			}
		} else if ($path[0] == Finance::TYPE_USER) {
			$id = \Auth::guard("clients")->user()->id;
			$holder = User::find($id);
			$loginType = 'user';
		} else if ($path[0] == Finance::TYPE_PROVIDER) {
			$id = \Auth::guard("providers")->user()->id;
			$holder = Provider::find($id);
			$loginType = 'provider';
		}
		if (Input::get('type_entry') != '0') {
			$typeEntry = Input::get('type_entry');
		} else {
			$typeEntry = '';
		}
		if ($holder && $holder->ledger) {
			if ($startDate == '') {
				if (Input::get('start_date_created') != '') {
					$startDate = Carbon::createFromFormat('d/m/Y', Input::get('start_date_created'));
				} else {
					$startDate = Carbon::today();
					if ($startDate->day == 1) {
						$startDate->month = $startDate->month - 1;
					} else {
						$startDate->day = 1;
					}
				}
			}
			if ($endDate == '') {
				if (Input::get('end_date_created') != '') {
					$endDate = Carbon::createFromFormat('d/m/Y', Input::get('end_date_created'));
				} else {
					$endDate = Carbon::today();
				}
			}
			//$startDate->setTime(0, 0, 0);
			//$endDate->setTime(23, 59, 59);
			$title = trans('finance.account_statement');
			$balance = Finance::getLedgerDetailedBalanceByPeriod($holder->ledger->id, $typeEntry, $startDate, $endDate);
			$banks  = Bank::orderBy('code', 'asc')->get(); //List of banks
			$account_types = LedgerBankAccount::getAccountTypes(); //List of AccountTypes
			$types = Finance::TYPES; //Prepares Finance types array to be used on vue component
			$futureCompensations = array();

			$withDrawSettings = array(
				'with_draw_enabled' 	=> Settings::getWithDrawEnabled(),
				'with_draw_max_limit' 	=> Settings::getWithDrawMaxLimit(),
				'with_draw_min_limit'	=> Settings::getWithDrawMinLimit(),
				'with_draw_tax'			=> Settings::getWithDrawTax()
			);
			if (Input::get('submit') && Input::get('submit') == 'Download_Report') {
				return $this->downloadFinancialReport($type, $holder, $balance, $startDate, $endDate);
			} else {
				return View::make("financial.account_summary")
					->with([
						'id' => $id,
						'login_type' => $loginType,
						'holder' => $holder->first_name . ' ' . $holder->last_name,
						'ledger' => $holder,
						'title' => $title,
						'balance' => $balance,
						'start' => $startDate,
						'end' => $endDate,
						'page' => 'financial',
						'types' => $types,
						'bankaccounts' => $holder->ledger->bankAccounts,
						'banks' => $banks,
						'account_types' => $account_types,
						'withdrawsettings' => $withDrawSettings,
					]);
			}
		} else {
			return View::make('notfound')->with('title', trans('adminController.page_not_found'))->with('page', trans('adminController.page_not_found'));
		}
	}

	//this function creates the financial report in a csv format
	public function downloadFinancialReport($type, $holder, $balance, $startDate, $endDate)
	{
		if (isset($holder->last_name) && $holder->last_name) {
			$filename = "relatorio-conta-" . $type . "-" . $holder->first_name . "-" . $holder->last_name . ".csv";
		} else {
			$filename = "relatorio-conta-" . $type . "-" . $holder->first_name . "-" . ".csv";
		}
		$handle = fopen(storage_path("framework/views/") . $filename, 'w');
		$entries 				= $balance['current_compensations'];
		$futureCompensations 	= $balance['future_compensations'];
		$previousBalance 		= $balance['previous_balance'];
		$currentBalance 		= $balance['current_balance'];
		$totalFuture 			= 0;

		fputs($handle, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

		// This first method calls creates the csv header
		fputcsv(
			$handle,
			array(
				trans('finance.title_statement', array(
					'start' => strftime(trans('finance.stringDatePattern'), strtotime($startDate)),
					'end' => strftime(trans('finance.stringDatePattern'), strtotime(explode(' ', $endDate)[0]))
				))
			),
			";"
		);
		fputcsv($handle, array(), ";");
		fputcsv(
			$handle,
			array(
				trans("finance.finance_date"),
				trans("finance.finance_time"),
				trans("finance.reason"),
				trans("finance.transaction_type"),
				trans("finance.request_id"),
				trans("finance.finance_value"),
			),
			";"
		);
		// This second method call populates all the csv fields
		if (sizeof($entries) > 0) {
			foreach ($entries as $entry) {
				$date = explode(' ', $entry['compensation_date']);
				$hour = str_split($date[1], 5);
				fputcsv(
					$handle,
					array(
						strftime('%d %b %Y', strtotime($date[0])),
						$hour[0],
						$entry['reason'],
						$this->translateTransactionTypeForReport($entry['description']),
						$entry['request_id'],
						$entry['value'],
					),
					";"
				);
			}
			//This method creates an empty line between the entries and the balance
			fputcsv($handle, array(), ";");
			//This third method creates the balance line that appears at the end of the report
			fputcsv($handle, array(';', ';', ';', ';', trans('financeTrans::finance.current_balance'), $currentBalance), ";");
		}
		if (sizeof($futureCompensations) > 0) {
			fputcsv($handle, array(), ";");
			fputcsv($handle, array(trans('finance.title_future_compensation')), ";");
			fputcsv($handle, array(), ";");
			foreach ($futureCompensations as $entry) {
				$totalFuture += $entry['value'];
				$date = explode(' ', $entry['compensation_date']);
				$reason = trans('finance.reason_not_found');

				$hour = str_split($date[1], 5);
				fputcsv(
					$handle,
					array(
						strftime('%d %b %Y', strtotime($date[0])),
						$hour[0],
						$entry['reason'],
						$entry['description'],
						$entry['request_id'],
						$entry['value'],
					),
					";"
				);
			}
			fputcsv($handle, array(trans('finance.total'), $totalFuture), ";");
		}
		if (sizeof($futureCompensations) > 0) {
			fputcsv($handle, array(), ";");
			fputcsv($handle, array(trans('finance.future_balance'), $currentBalance + $totalFuture), ";");
		}
		fclose($handle);
		$headers = array(
			'Content-Encoding'		=>	'UTF-8',
			'Content-Type' 			=> 'text/csv; charset=utf-8',
			'Content-Disposition' 	=> 'attachment; filename=' . $filename,
		);
		return Response::download(storage_path('framework/views/') . $filename, $filename, $headers);
	}

	/**
	 * @api {get} /api/v3/{holder}/financial/summary/{id}
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
			$request->type_entry,
			$request->start_date,
			$request->end_date,
			$request->page,
			$request->itemsPerPage
		);

		// Retorno de dados
		return new GetFinancialSummaryByTypeAndDateResource(['balance' => $balance]);
	}

	public function addFinancialEntry()
	{

		$ledgerId = Input::get('ledger-id');
		$reason = Input::get('type-entry') == '0' ? '' : Input::get('type-entry');
		$description = Input::get('entry-description');
		$value = Input::get('entry-value');
		$date = Input::get('entry-date');
		if (!$date) { //if date is not selected, set today
			$date = date('d/m/Y');
		}
		$ledger = Ledger::find($ledgerId);
		$responseArray = array('success' => true, 'var' => $value);
		$validator = Validator::make(
			array(
				'ledger' => $ledger,
				trans('finance.transaction_type') => $reason,
				trans('finance.description') => $description,
				trans('finance.value') => $value,

			),
			array(
				'ledger' => 'required',
				trans('finance.transaction_type') => 'required',
				trans('finance.description') => 'required',
				trans('finance.value') => 'required',

			)
		);
		if ($validator->fails()) {
			$errorMessages = $validator->messages()->all();
			$responseArray = array('success' => false, 'error' => trans('accountController.invalid_input'), 'errorCode' => 401, 'messages' => $errorMessages);
		} else {
			//check date format
			$dateArr = explode("/", $date);
			$year = count($dateArr) == 3 ? $dateArr[2] : "";
			if (!Carbon::hasFormat($date, 'd/m/Y') || strlen($year) != 4) { //ano precisa ter 4 digitos, para proibir de inserir em anos pequenos (ex: 01/01/21 - isso e considerado literalmente ano 21 e nao 2021)
				$responseArray = array('success' => false, 'error' => trans('accountController.invalid_input'), 'errorCode' => 401, 'messages' => [trans('financeTrans::finance.date_format')]);
			} else {
				switch ($reason) {
					case Finance::SEPARATE_DEBIT:
						$value = -$value;
						break;
					case Finance::RIDE_DEBIT:
						$value = -$value;
						break;
					case Finance::WITHDRAW:
						$value = -$value;
						break;
					case Finance::RIDE_LEDGER:
						$value = -$value;
						break;
					case Finance::RIDE_CANCELLATION_DEBIT:
						$value = -$value;
						break;
					default:
						$value = $value;
				}
				$sessionId = \Auth::id();
				$return = Finance::createCustomEntry($ledgerId, $reason, $description, $value, $date, $sessionId);
				$responseArray = array('success' => true, 'return' => $return);
			}
		}
		$responseCode = 200;
		$response = Response::json($responseArray, $responseCode);
		return $response;
	}
	public function addWithDrawRequest()
	{

		$ledgerId = Input::get('ledger-id');
		$value = Input::get('with-draw-value');
		$bankAccountId = Input::get('bank-account-id');
		$ledger = Ledger::find($ledgerId);
		$totalBalance = Finance::sumAllValueByLedgerId($ledgerId);
		$responseArray = array('success' => true, 'var' => $value);
		$withDrawSettings = array(
			'with_draw_enabled' 	=> Settings::getWithDrawEnabled(),
			'with_draw_max_limit' 	=> Settings::getWithDrawMaxLimit(),
			'with_draw_min_limit'	=> Settings::getWithDrawMinLimit(),
			'with_draw_tax'			=> Settings::getWithDrawTax()
		);
		if ($withDrawSettings['with_draw_enabled'] == true) {
			/**Validate data*/
			$validator = Validator::make(
				array(
					'ledger' => $ledger,
					trans('finance.value') => $value,
					trans('finance.bank_account') => $bankAccountId
				),
				array(
					'ledger' => 'required',
					trans('finance.value') => 'required|numeric|between:' . $withDrawSettings['with_draw_min_limit'] . ',' . $withDrawSettings['with_draw_max_limit'],
					trans('finance.bank_account') => 'required'
				)
			);
			if ($validator->fails() || ($value + $withDrawSettings['with_draw_tax']) > $totalBalance) {
				$errorMessages = $validator->messages()->all();
				if ($value && ($value + $withDrawSettings['with_draw_tax']) > $totalBalance) array_push($errorMessages, trans('finance.insufficient_balance'));
				$responseArray = array('success' => false, 'error' => trans('accountController.invalid_input'), 'errorCode' => 401, 'messages' => $errorMessages);
			} else {
				$value = -$value;
				$sessionId = \Auth::id();
				$return = Finance::createWithDrawRequest($ledgerId, $value, $bankAccountId, $sessionId);
				if ($withDrawSettings['with_draw_tax'] > 0) {
					$withDrawSettings['with_draw_tax'] = -$withDrawSettings['with_draw_tax'];
					Finance::createCustomEntryWithBankAccountId($ledgerId, Finance::WITHDRAW, trans('finance.withdraw_tax'), $withDrawSettings['with_draw_tax'], $sessionId, $bankAccountId);
				}
				$responseArray = array('success' => true, 'return' => $return);
			}
		} else {
			$responseArray = array('success' => false, 'error' => trans('settings.with_draw_unabled'), 'errorCode' => 401, 'messages' => array(trans('settings.with_draw_unabled')));
		}
		$responseCode = 200;
		$response = Response::json($responseArray, $responseCode);
		return $response;
	}

	public function saveFinance($finance, $request)
	{
		//registra dados
		$finance->value = $request->finance['value'];
		$finance->reason = $request->finance['reason'];
		$finance->request_id = isset($request->finance['request_id']) && $request->finance['request_id'] ? $request->finance['request_id'] : null;
		$finance->description = $request->finance['description'];
		$finance->save();

		return $finance;
	}

	/**
	 * @api{post}/api/v3/admin/billing/finance/store
	 * @apiDescription Adiciona ou Edita a finança de uma determinada fatura
	 * @return Json
	 */
	public function addOrEdit(FinanceFormRequest $request)
	{
		//recupera fatura
		$invoice = $request->finance['invoice_id'] ? \Invoice::findOrFail($request->finance['invoice_id']) : null;

		//recupera invoice
		if (isset($request->finance['id']) && $request->edit) {

			//recupera finança
			$finance = \Finance::find($request->finance['id']);

			//retira o valor antigo
			$invoice->debit = $invoice->debit - $finance->value;

			//atualiza finança
			$finance = $this->saveFinance($finance, $request);

			//atualiza valores
			$invoice->debit = $invoice->debit + $finance->value;
			$invoice->debit_note = $invoice->debit * \Settings::getDebitNotePercentage();
			$invoice->debit_invoice = $invoice->debit - $invoice->debit_note;
			$invoice->save();
		} else {
			//cria finança
			$finance = new \Finance();

			//recupera ledger
			$institution = Institution::findOrFail($invoice->institution_id);
			$ledger = $institution ? $institution->getLedger() : null;

			if ($ledger && $invoice) {

				//cria finança
				$finance->ledger_id = $ledger->id;
				$finance->compensation_date = $invoice->competence_month;
				$finance = $this->saveFinance($finance, $request);

				//cria finança
				$invoice_finance = new \App\Models\InvoiceFinance();
				$invoice_finance->invoice_id = $invoice->id;
				$invoice_finance->finance_id = $finance->id;
				$invoice_finance->save();

				//atualiza valores
				$invoice->debit = $invoice->debit + $finance->value;
				$invoice->debit_note = $invoice->debit * \Settings::getDebitNotePercentage();
				$invoice->debit_invoice = $invoice->debit - $invoice->debit_note;
				$invoice->save();
			}
		}

		//retorno
		return new FinanceResource(['finance' => $finance]);
	}

	/**
	 * Busca dados dos detalhes da fatura para download com ou sem filtros
	 * @return
	 */
	public function download(Request $request)
	{
		//pesquisa
		$model = new \Finance();
		$query = $model->querySearchInvoice($request);

		//retorna
		return $this->downloadReport($query);
	}

	/**
	 * Gera csv com dados para download
	 * @return 
	 */
	public function downloadReport($query)
	{
		$filename = "relatorio-finances-" . date("Y-m-d-hms", time()) . ".csv";
		$handle = fopen(storage_path('framework/views/') . $filename, 'w');
		$value_sum = 0;

		fputs($handle, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

		// csv header
		fputcsv(
			$handle,
			array(
				trans('billing.institution_id'),
				trans('billing.institution_name'),
				trans('billing.invoice_id'),
				trans('billing.invoice_name'),
				trans('billing.finance_value'),
				trans('billing.request_id'),
				trans('billing.finance_compensation_date'),
				trans('billing.status'),
			),
			";"
		);

		foreach ($query as $finance) {

			$key = array_search($finance->status_invoice, array_column(Config('enum.status_invoice'), 'value'));

			fputcsv(
				$handle,
				array(
					$finance->institution_id,
					$finance->institution_name,
					$finance->id,
					$finance->name,
					currency_format($finance->value),
					$finance->request_id,
					date_format(date_create($finance->finance_compensation_date), 'd/m/Y H:i:s'),
					$key == false ? '' : trans(Config('enum.status_invoice')[$key]['name'])
				),
				";"
			);
		}

		fclose($handle);

		$headers = array(
			'Content-Encoding'        =>    'UTF-8',
			'Content-Type'             => 'text/csv; charset=utf-8',
			'Content-Disposition'     => 'attachment; filename=' . $filename,
		);

		return Response::download(storage_path('framework/views/') . $filename, $filename, $headers);
	}
	/**
	 * Traduz Campo de Razão do Relatório do usuário
	 * @return 
	 */
	public function translateTransactionTypeForReport($entry)
	{
		switch ($entry) {
			case 'SIMPLE_INDICATION':
				trans('finance.SIMPLE_INDICATION');
				break;
			case 'COMPENSATION_INDICATION':
				trans('finance.COMPENSATION_INDICATION');
				break;
			case 'SEPARATE_CREDIT':
				trans('finance.SEPARATE_CREDIT');
				break;
			case 'SEPARATE_DEBIT':
				trans('finance.SEPARATE_DEBIT');
				break;
			case 'WITHDRAW':
				trans('finance.WITHDRAW');
				break;
			case 'RIDE_DEBIT':
				trans('finance.RIDE_DEBIT');
				break;
			case 'RIDE_CREDIT':
				trans('finance.RIDE_CREDIT');
				break;
			case 'MACHINE_RIDE_DEBIT':
				trans('finance.MACHINE_RIDE_DEBIT');
				break;
			case 'MACHINE_RIDE_CREDIT':
				trans('finance.MACHINE_RIDE_CREDIT');
				break;
			case 'RIDE_CANCELLATION_DEBIT':
				trans('finance.RIDE_CANCELLATION_DEBIT');
				break;
			case 'RIDE_CANCELLATION_CREDIT':
				trans('finance.RIDE_CANCELLATION_CREDIT');
				break;
			case 'RIDE_PAYMENT':
				trans('finance.RIDE_PAYMENT');
				break;
			case 'CARTO_RIDE_PAYMENT':
				trans('finance.CARTO_RIDE_PAYMENT');
				break;
			case 'RIDE_PAYMENT_FAIL_DEBIT':
				trans('finance.RIDE_PAYMENT_FAIL_DEBIT');
				break;
			case 'RIDE_LEDGER':
				trans('finance.RIDE_LEDGER');
				break;
			case 'AUTO_WITHDRAW':
				trans('finance.AUTO_WITHDRAW');
				break;
			case 'CLEANING_FEE_DEBIT':
				trans('finance.CLEANING_FEE_DEBIT');
				break;
			case 'CLEANING_FEE_CREDIT':
				trans('finance.CLEANING_FEE_CREDIT');
				break;
			case 'SIGNATURE_DEBIT':
				trans('finance.SIGNATURE_DEBIT');
				break;
			case 'SIGNATURE_CREDIT':
				trans('finance.SIGNATURE_CREDIT');
				break;

			default:
				"TIPO_PADRÃO";
		}
	}
}
