@extends('layout.master')

@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('finance.plural') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('dashboard.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('user_provider_web.statement_account') }}</li>
		</ol>
	</div>
</div>	
@stop
@section('content')
<div id="VueJs">
	<financial-account-statements 
		:holder="{{ $holder }}"
		login-type="{{ $login_type }}"
		:finance-types="{{ json_encode($types) }}"
		:balance-data="{{ json_encode($balance) }}"
		:bank-accounts="{{ json_encode($bankaccounts) }}"
		:banks="{{ $banks }}"
		:account-types="{{ json_encode($account_types) }}"
		:with-draw-settings="{{ json_encode($withdrawsettings) }}"
		currency-symbol="{{ \Settings::getFormattedCurrency() }}"
		holder-type="{{ $holder_type }}"
	></financial-account-statements>
</div>
@stop
        
@section('javascripts')
<script type="text/javascript" src="/js/lang.trans/finance,dashboard,empty_box,keywords"></script>
<script type="text/javascript" src="/js/env.js"></script> 
<script src="{{ elixir('vendor/codificar/finance/finance.vue.js') }}"> </script>
@stop