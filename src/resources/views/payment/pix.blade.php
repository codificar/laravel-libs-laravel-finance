<?php $layout = ''; ?>
@switch($enviroment)
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

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('pix') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('dashboard.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('pix') }}</li>
		</ol>
	</div>

</div>	
@stop

@section('content')
<div id="VueJs" class="col-md-12">
	<pix
		enviroment="{{ $enviroment }}"
		laravel-echo-port = "{{ env('LARAVEL_ECHO_PORT', 6001) }}"
		transaction-id="{{ $transaction_id }}"
		pix-copy-paste="{{ $pix_copy_paste }}"
		pix-base64="{{ $pix_base64 }}"
		value="{{ $value }}"
	>
	</pix>
</div>
@stop

@section('javascripts')
<script src="/plugins/card/jquery.card.js"></script>
<script type="text/javascript" src="/js/lang.trans/finance,dashboard,keywords"></script>
<script src="/libs/finance/lang.trans/finance"> </script> 
<script src="{{ elixir('vendor/codificar/finance/finance.vue.js') }}"> </script>
@stop