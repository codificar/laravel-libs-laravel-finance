<?php

namespace Codificar\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Signature extends Model
{
    //
    /**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'signature';
	
	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
    public $timestamps = true;
    

	/**
	 * update signature data when postback pix is called
	 * @return void
	 */
	public function updatePostBackPix()
	{
		$plan = $this->plan;
		// Define a data de expiração da assinatura
	   	$period = $plan->period + \Settings::getDaysForSubscriptionRecurrency();
	   	$nextExpiration = Carbon::now()->addDays($period);
	   	$this->created_at = Carbon::now();
	   	$this->next_expiration = $nextExpiration;
	   	$this->activity = 1;
	   	$this->save();
	}

}