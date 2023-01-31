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

	//transaction status
	const PROCESSING 		= 'processing';
	const AUTHORIZED 		= 'authorized';
 	const PAID 				= 'paid';
 	const WAITING_PAYMENT 	= 'waiting_payment';
 	const PENDING_REFUND 	= 'pending_refund';
 	const REFUNDED 			= 'refunded';
 	const REFUSED 			= 'refused';
 	const ERROR 			= 'error';

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

	public static function getTransactionByRequestId($requestId)
	{
		$request = self::where('request_id', $requestId)->first();

		if ($request) {
			return $request;
		} else {
			return null;
		}
	}
}