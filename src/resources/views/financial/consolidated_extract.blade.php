@extends('layout.master')

@section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">

		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('finance.plural') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="javascript:void(0)">{{ trans('dashboard.home') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('financeTrans::finance.consolidated_extract') }}</li>
		</ol>
	</div>
</div>	
@stop

@section('content')
<div id="VueJs">
    <consolidated-statement
		:locations="{{ json_encode($locations) }}"
		:partners="{{ json_encode($partners) }}"
	/>
</div>
@stop

@section('javascripts')
<script src="/libs/finance/lang.trans/finance"> </script> 
<script src="{{ asset('vendor/codificar/finance/finance.vue.js') }}"> </script>
@stop