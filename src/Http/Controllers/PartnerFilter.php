<?php

namespace Codificar\Finance\Http\Controllers;

use Illuminate\Http\Request;

use Admin, Location, PromoCodes, Requests, Provider, User, Partner;
/**
 * Trait used to filter by partner
 * Class PartnerFilter
 */
trait PartnerFilter
{
	private $locationModel;
	private $promoCodeModel;
	private $requestModel;
	private $providersModel;
	private $userModel;
	private $partners;

	/**
	 * If is a partner profile, filter the results by a partner
	 */
	public function initPartnerFilter(){
		if (Admin::isPartnerProfile()){
			$admin = Auth::user();

			$partners = $admin->partners()->get();
			$this->partners = $partners;
			$this->partnersId =  ArrayUtils::filterArrOnlyKey($this->partners->toArray(), 'id');

			$this->locationModel = Location::byPartners($this->partnersId);
			$this->promoCodeModel = PromoCodes::byPartners($this->partnersId);
			$this->requestModel = Requests::byPartners($this->partnersId);
			$this->providersModel = Provider::byPartners($this->partnersId);
			$this->userModel = User::byPartners($this->partnersId);
		}else{
			$this->locationModel = Location::query();
			$this->promoCodeModel = PromoCodes::query();
			$this->requestModel = Requests::query();
			$this->providersModel = Provider::query();
			$this->userModel = User::query();
			$this->partners = Partner::allOrderByAlpha();
			$this->partnersId =  array();
		}
	}

}