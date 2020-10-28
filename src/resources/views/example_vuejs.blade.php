<?php $layout = '.master'; ?>
       
@extends('layout'.$layout)

@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('financeTrans::finance.finance')}}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('financeTrans::finance.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('financeTrans::finance.laravel_trans_example') }}</li>
		</ol>
	</div>
</div>	
@stop


@section('content')
	<div id="VueJs">
		
		<financevuejs 
			admins-list="{{ json_encode($admins_list )}}"	
			
		>
		</financevuejs>
		
	</div>

		

	</div>

@stop

@section('javascripts')
<script src="/libs/finance/lang.trans/finance"> </script> 



<script src="{{ elixir('vendor/codificar/finance/finance.vue.js') }}"> </script> 
       
@stop
