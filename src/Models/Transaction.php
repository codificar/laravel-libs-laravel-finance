<?php
namespace Codificar\Finance\Models;

use Eloquent;

class Transaction extends Eloquent
{
	//transaction type
	const BASE_TAX 		= 'base_tax';
	const CANCEL_TAX 	= 'cancel_tax';
	const REQUEST_PRICE = 'request_price';
	const FINANCE_VALUE = 'finance_value';
	const SIGNATURE_VALUE = 'signature_value';
	const SUBSCRIPTION_TRANSACTION = 'subscription_transaction';
	const SINGLE_TRANSACTION = 'request_single_transaction';
	const BALANCE_ADD_TRANSACTION = 'balance_add_transaction';

	//transaction status
	const PROCESSING 		= 'processing';
	const AUTHORIZED 		= 'authorized';
 	const PAID 				= 'paid';
 	const WAITING_PAYMENT 	= 'waiting_payment';
 	const PENDING_REFUND 	= 'pending_refund';
 	const REFUNDED 			= 'refunded';
 	const REFUSED 			= 'refused';
 	const ERROR 			= 'error';
	
	//transaction status code 
	const CODE_CREATED 			= 1;
	const CODE_WAITING_PAYMENT 	= 2;
	const CODE_CANCELED 		= 3;
	const CODE_IN_ANALISYS 		= 4;
	const CODE_PRE_AUTHORIZED 	= 5;
 	const CODE_PARTIAL_CAPTURED = 6;
 	const CODE_DECLINED 		= 7;
 	const CODE_CAPTURED 		= 8;
 	const CODE_CHARGEBACK 		= 9;
 	const CODE_IN_DISPUTE 		= 10;

	const MapStatus 		= array(
		'processing'		=> self::PROCESSING ,
		'authorized'		=> self::AUTHORIZED ,
		'paid'				=> self::PAID ,
		'waiting_payment'	=> self::WAITING_PAYMENT ,
		'pending_refund'	=> self::PENDING_REFUND ,
		'refunded'			=> self::REFUNDED ,
		'refused'			=> self::REFUSED ,
		'error'				=> self::ERROR ,
		'succeeded'			=> self::PAID ,
		'pending'			=> self::WAITING_PAYMENT ,
		'failed'			=> self::ERROR ,
	);

 	//split status
 	const SPLIT_WAITING_FUNDS = 'waiting_funds';
 	const SPLIT_PAID = 'paid';

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'transaction';
	protected $fillable = ['request_id'];
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * get Ledger by Provider Id
	 * @return Signature | null
	 **/
	public function signature()
	{
		return $this->hasOne('Signature', 'transaction_id', 'id');
	}

	/**
	 * get Ledger by Provider Id
	 * @return Signature | null
	 **/
	public function finance()
	{
		return $this->hasOne('Finance', 'transaction_id', 'id');
	}

	/**
	 * get latest ride by Id
	 * @return Requests | null
	 **/
	public function ride()
	{
		return $this->hasOne('Requests', 'id', 'request_id');
	}
	   
    /**
     * Update transaction status to paid
	 * @param bool $paid
	 * @return void
     */
    public function setStatusPaid()
    {
        $this->status = 'paid';
        $this->save();
    }
    
	/**
     * verify status to paid
	 * 
	 * @return bool
     */
    public function isPaid(): bool
    {
		return $this->status == self::PAID;
    }
    
	/**
     * verify if webhook status is captured
	 * @param int $status
	 * @return bool
     */
    public static function isWebhookCaptured(int $status): bool
    {
		return $status == self::CODE_CAPTURED;
    }

	public static function getTransactionByRequestId($requestId)
	{
		$request = self::where('request_id', $requestId)->first();

		if ($request) {
			return $request;
		} else {
			return null;
		}
	}

	public static function getTransactionByGatewayId($gatewayId)
	{
		$gatewayTransactionId = self::where('gateway_transaction_id', $gatewayId)->first();

		if ($gatewayTransactionId) {
			return $gatewayTransactionId;
		} else {
			return null;
		}
	}

	/**
	 * Create a new transaction in db to register when add new ballance
	 * 
	 * @param string $status
	 * @param float $value
	 * @param $gatewayTransactionId
	 * @param string $paymentGatewayError - default: null
	 * 
	 * @return Transaction|null
	 */
	public static function createTransactionAddBalance(
		string $status, 
		float $value, 
		$gatewayTransactionId, 
		string $paymentGatewayError = null
	): Transaction|null
	{
		try {
			$transaction = new Transaction();
	
			$transaction->type = self::BALANCE_ADD_TRANSACTION;
			$transaction->status = $status;
			$transaction->gross_value = $value;
			$transaction->net_value = 0;
			$transaction->provider_value = 0;
			$transaction->gateway_tax_value = 0;
			$transaction->gateway_transaction_id = $gatewayTransactionId;
			$transaction->payment_gateway_error = $paymentGatewayError;
			$transaction->request_id = null ;
	
			$transaction->save();
	
			return $transaction;

		} catch(\Exception $e) {
			\Log::error($e->getMessage() . $e->getTraceAsString());
			return null;
		}
	}
}