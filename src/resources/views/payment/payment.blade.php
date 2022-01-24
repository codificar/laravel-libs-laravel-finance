<?php $layout = ''; ?>
@switch($enviroment)
    @case('admin')
		<?php $layout = '.master'; ?>
	@break

	@case('corp')
		<?php $layout = '.corp.master'; ?>
	@break
	
	@case('user')
		<?php $layout = '.user.master'; ?>
	@break

	@case('provider')
		<?php $layout = '.provider.master'; ?>
	@break
	

    @default
		@break
@endswitch
@extends('layout'.$layout)


@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('finance.plural') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('dashboard.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('finance.plural') }}</li>
		</ol>
	</div>

</div>	
@stop

@section('content')
<div id="VueJs" class="col-md-12">
	<payment
		enviroment="{{ $enviroment }}"
		user_balance="{{ $user_balance }}"
		user_cards = "{{ json_encode($user_cards)}}"
		save_payment_route = "{{ URL::Route($enviroment.'AddCreditCard') }}"
		request_payment_route = "{{ URL::Route($enviroment.'RequestPayment') }}"
		add_new_billet_route = "{{ URL::Route($enviroment.'AddNewBillet') }}"
		add_new_pix_route = "{{ URL::Route($enviroment.'AddPixBalance') }}"
		pix_screen_route = " {{ URL::Route($enviroment.'PixScreen') }}"
		financial_report_route = "{{ URL::Route('corpAccountStatement') }}"
		delete_user_card = "{{ URL::Route($enviroment.'DeleteUserCard') }}"
		prepaid-settings = "{{ json_encode($prepaid_settings)}}"
		currency-symbol="{{ $currency_symbol }}"
		iframe-add-card="{{ $iframe_add_card }}"
	>
	</payment>
</div>
@stop

@section('javascripts')
<script src="/plugins/card/jquery.card.js"></script>
<script type="text/javascript" src="/js/lang.trans/finance,dashboard,keywords"></script>
<script src="/libs/finance/lang.trans/finance"> </script> 
<script src="{{ asset('vendor/codificar/finance/finance.vue.js') }}"> </script>
@stop