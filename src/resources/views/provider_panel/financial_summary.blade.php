@extends('layout.provider.master')
@section('breadcrumbs')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('user_provider_web.statement_account') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
                <a href="javascript:void(0)">
                    {{ trans("dashboard.home") }}
                </a> 
            </li>
			<li class="breadcrumb-item active">{{ trans('user_provider_web.statement_account') }}</li>
		</ol>
	</div>
</div>
@stop
@section('content')
<div id="VueJs" class="col-md-12">
	<financial-account-statement 
		enviroment="{{ $enviroment }}"
		:holder="{{ $holder }}"
		login-type="{{ $login_type }}"
		finance-types="{{ json_encode($types) }}"
		:balance-data="{{ json_encode($balance) }}"
		:bank-accounts="{{ json_encode($bankaccounts) }}"
		:banks="{{ $banks }}"
		:account-types="{{ json_encode($account_types) }}"
		:with-draw-settings="{{ json_encode($withdrawsettings) }}"
		currency-symbol="{{ $currency_symbol }}"
		holder-type="{{ $holder_type }}"
	></financial-account-statement>
</div>
@stop

@section('styles')
<link rel="stylesheet" href="{{elixir('css/provider_financial.css')}}" />
@stop

@section('javascripts')
<script type="text/javascript" src="/js/lang.trans/finance,dashboard,keywords"></script>
<script src="/libs/finance/lang.trans/finance"> </script> 
<script src="{{ elixir('vendor/codificar/finance/finance.vue.js') }}"> </script>
@stop