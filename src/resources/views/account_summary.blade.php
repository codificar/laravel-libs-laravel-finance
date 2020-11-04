@extends('layout.master') @section('breadcrumbs')
<div class="row page-titles">
	<div class="col-md-6 col-8 align-self-center">
		<h3 class="text-themecolor m-b-0 m-t-0">{{ trans('provider.plural') }}</h3>
		<ol class="breadcrumb">
			<li class="breadcrumb-item">
				<a href="javascript:void(0)">{{ trans('dashboard.home') }}</a>
			</li>
			<li class="breadcrumb-item active">{{ trans('provider.plural') }}</li>
		</ol>
	</div>
</div>
@stop @section('content')
<div class="col-lg-12">
	<div class="card card-outline-info">
		<div class="card-header">
			<h4 class="m-b-0 text-white">{{ trans('dashboard.filter') }}</h4>
		</div>
		<div class="card-block">
			<form method="get" action="{{URL::Route('AdminProviderExtractFilter')}}" id="providersFilter">
				<div class="row">
					<div class="col-md-2 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.id_grid') }}</label>
							<input type="number" min="0" class="form-control" id="id" name="id" value="{{ Input::get('id') }}" placeholder="Id" />
						</div>
					</div>
					<!--/span-->
					<div class="col-md-5 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.name_provider') }}</label>
							<input type="text" class="form-control" id="name" name="name" value="{{ Input::get('name') }}" placeholder="{{trans('providerController.name') }}">
						</div>
					</div>
					<!--/span-->
					<div class="col-md-5 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.mail_provider') }}</label>
							<input type="email" class="form-control" id="email" name="email" value="{{ Input::get('email') }}" placeholder="Email">
						</div>
					</div>
					
				</div>
				<!--/span-->
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.state') }}</label>
							<input type="text" class="form-control" id="state" name="state" value="{{ Input::get('state') }}" placeholder="{{trans('providerController.state') }}">
						</div>
					</div>
					<!--/span-->
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.address_city') }}</label>
							<input type="text" class="form-control" id="city" name="city" value="{{ Input::get('city') }}" placeholder="{{trans('providerController.city') }}">
						</div>
					</div>	
				</div>	
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label class="control-label">{{trans('provider.occurrence') }}</label>				
							<div class="input-daterange input-group date-range">						
								<input type="text" class="form-control" name="start_date_created" value="{{ Input::get('start_date_created') }}" placeholder="{{trans('dashboard.start_date')}}" />
								<span class="input-group-addon bg-info b-0 text-white">{{trans('dashboard.to') }}</span>
								<input type="text" class="form-control" name="end_date_created" placeholder="{{trans('dashboard.end_date') }}"  value="{{ Input::get('end_date_created') }}" />
							</div>
						</div>
					</div>

					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label">{{trans('provider.balance') }}</label>				
							<select name="order_balance" class="form-control">
								<option value=""> {{trans('provider.nothing') }} </option>
								<option value="positive" <?php if (Input::get('order_balance') == 'positive') echo "selected";?>> {{trans('provider.positive') }} </option>
								<option value="negative" <?php if (Input::get('order_balance') == 'negative') echo "selected";?>> {{trans('provider.negative') }} </option>
							</select>

						</div>
					</div>

					<div class="col-md-3">
						<div class="form-group">
							<label class="control-label">{{trans('provider.status_grid') }}</label>
							<select name="status" class="form-control" data-placeholder="Status">
								<option value="">{{trans('provider.status_grid')}}</option>
								<option value="APROVADO" <?php echo Input::get( 'status')=="APROVADO" ? "selected" : "" ?> >{{trans('adminController.approved') }}</option>
								<option value="SUSPENSO" <?php echo Input::get( 'status')=="SUSPENSO" ? "selected" : "" ?>>{{trans('adminController.suspend') }}</option>
							</select>
						</div>
					</div>

				</div>												
					<!-- <div class="col-md-4 col-sm-3">
						<div class="form-group">
							{!! Form::label('institution_id', trans('document.sent_documents'), ['class'=>'control-label']) !!}
							{!! Form::select('send_docs', array('0' => trans('document.sent_documents'), 'approved' => 'Sim', 'docs' => 'Não'), 0, ['class'=> 'form-control']) !!}
						</div>
					</div> -->
				</div>
				<div class="box-footer">
					<a href="{{ URL::Route('AdminProviderExtract') }}" class="btn btn-danger">
						<i class="fa fa-trash"></i>
						{{trans('dashboard.clear_form')}}
					</a>
					<div class="pull-right">
						<button type="submit" name="submit" class="btn btn-info right" value="Download_Report">
							<i class="mdi mdi-download"></i> {{trans('dashboard.down_report')}}
						</button>
						<button type="submit" name="btnsearch" class="btn btn-success" value="Filter_Data">
							<i class="fa fa-search"></i>
							{{trans('provider.search') }}
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<div class="card card-outline-info">
		<div class="card-header">
			<h4 class="m-b-0 text-white">{{ trans('notification.debit_notification_all') }}</h4>
		</div>
		<div class="card-block">
			<form action="{{ URL::Route('notificationDebitAll') }}" method="post">
				<div class="row">
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{ trans('notification.title') }}</label>
							<input type="text" class="form-control" name="msg_title" value="" placeholder="{{ trans('notification.title') }}" />
						</div>
					</div>
					<!--/span-->
					<div class="col-md-6 col-sm-6">
						<div class="form-group">
							<label class="control-label">{{ trans('notification.message') }}</label>
							<input type="text" class="form-control" name="msg_body" value="" placeholder="{{ trans('notification.message') }}">
						</div>
					</div>
					
					<div class="col-md-12">
						<div class="pull-right">
							<input type="submit" value="{{ trans('notification.send') }}" class="btn btn-success right">
						</div>
					</div>

				</div>
			</form>
		</div>
	</div>	
</div>
<?php
	if( $order==0){
		$order = 1;
	} else if( $order==1){
		$order = 0;
	}
?>
<?php if(sizeof($providers) != 0){ ?>
	<div class="col-12 tbl-box">
<?php }else{	?>
	<div class="col-md-12 col-sm-12">
<?php } ?>
	<div class="card card-block">
		<div align="left" id="paglink">				
			<?php echo $providers->appends(
				array(
					'id' => Input::get('id'),
					'name' => Input::get('name'),
					'email' => Input::get('email'),
					'city' => Input::get('city'),
					'state' => Input::get('state'),
					'start_date_compensation' => Input::get('start_date_compensation'),
					'end_date_compensation' => Input::get('end_date_compensation'),
					'start_date_created' => Input::get('start_date_created'),
					'end_date_created' => Input::get('end_date_created'),
					'order_balance' => Input::get('order_balance'),
					'status' => Input::get('status'),
					)
				)->render();
				//d=&name=&email=&state=&city=&start_date_compensation=01%2F04%2F2018&end_date_compensation=06%2F10%2F2018&start_date_created=&end_date_created=&btnsearch=Filter_Data
			?>
		</div>
		<div class="box box-info tbl-box ">
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>{{ trans('map.id') }}</th>
						<th>{{ trans('provider.name_grid') }}</th>
						<th>{{ trans('provider.mail_grid') }}</th>
						<th>{{ trans('provider.bank_grid') }}</th>
						<th>{{ trans('provider.agency_grid') }}</th>
						<th>{{ trans('provider.account_grid') }}</th>
						<th>{{ trans('provider.total_request_grid') }}</th>
						<th>{{ trans('finance.current_balance') }}</th>
						<th>{{ trans('finance.total_compensations') }}</th>
						<th colspan="2">{{ trans('finance.total')}}</th>
						<th>{{ trans('provider.status_grid') }}</th>
						<th>{{ trans('provider.action_grid') }}</th>
					</tr>
				</thead>
				<tbody>
					<?php 
						$newStartDate = Input::get('start_date_created'); 
						$newEndDate = Input::get('end_date_created');
					?> 
					@foreach ($providers as $key=>$provider)
					<tr>
						<!-- ID -->
						<td>
							<?php echo $provider->id; ?>
						</td>
						<!-- Name -->
						<td>
							<?php echo $provider->first_name . " " . $provider->last_name; ?>
						</td>
						<!-- E-mail -->
						<td>
							<?php echo $provider->email; ?>
						</td>
						@if($bankAccount = $provider->getBankAccount())
						<!-- Bank -->
						<td>
							<?php echo $bankAccount->bank_name; ?>
						</td>
						<!-- Agency -->
						<td>
							<?php echo $bankAccount->agency_number; ?>
						</td>
						<!-- Account -->
						<td>
							<?php echo $bankAccount->account_number; ?>
						</td>
						@else
						<td>
							<span class='badge bg-red'> {{trans('provider.not_informed')}}</span>
						</td>
						<td>
							<span class='badge bg-red'> {{trans('provider.not_informed')}}</span>
						</td>
						<td>
							<span class='badge bg-red'> {{trans('provider.not_informed')}}</span>
						</td>												
						@endif						
						<!-- Request -->
						
						<td>
							<?php echo $provider->total_requests ;?>
						</td>												
						
						<?php 
							$total = 0;
						?> 

						<td style="text-align:center;" >
							<?php
							$totalizer=0;									
							$entries = $balances[$key];
								if(Input::get('start_date_created')){	
									$var = Input::get('start_date_created');
									$date = str_replace('/', '-', $var); 
									$newStartDate = date('Y-m-d', strtotime($date));
										if (Input::get('end_date_created')){
											$var = Input::get('end_date_created');
											$date = str_replace('/', '-', $var); 
											$newEndDate = date('Y-m-d', strtotime($date));
										}
									$tipoClass = ($entries['period_balance'] >= 0) ? 'text-success': "text-danger";
									echo"<span class='$tipoClass'>$currency_symbol ".number_format($entries['period_balance'], 2, ',', ' ')."</span>";
								}else{			
									$tipoClass = ($entries['period_balance'] >= 0) ? 'text-success': "text-danger";
									echo"<span class='$tipoClass'>$currency_symbol ".number_format($entries['period_balance'], 2, ',', ' ')."</span>";
								}
							?>
						</td>
						<td style="text-align:center;" >
							<?php																
								$totalizer=0;									
								$entries = $balances[$key];																												
								$totalizer = $entries['total_balance'] - $entries['current_balance'];
								$total += $totalizer;
								$tipoClass = ($totalizer >= 0) ? 'text-success': "text-danger";
								echo"<span class='$tipoClass'>$currency_symbol ".number_format($totalizer, 2, ',', ' ')."</span>"
							?>
						</td>

						<td style="white-space:nowrap;" colspan="2">
							<?php
								$entries = $balances[$key];	
								$tipoClass = ($entries['total_balance'] >= 0) ? 'text-success': "text-danger";
								echo "<span class='$tipoClass'>$currency_symbol ".number_format($entries['total_balance'], 2, ',', ' ')."</span>"
							?>
						</td>

						<td>
							<div class="btn-group">
								<?php $btnClass = '';?> 

								@if ($provider->status_name && strcmp($provider->status_name, "APROVADO") == 0 )
									<span class='btn btn-success peq'>{{ trans('provider.approved_grid') }}</span>
									<?php $btnClass = 'btn-success';?>
								@elseif ($provider->status_name && strcmp($provider->status_name, "SUSPENSO") == 0 )
									<span class='btn btn-warning peq'>{{ trans('providerController.Suspended') }}</span>
									<?php $btnClass = 'btn-warning';?>
								@endif

								<button type="button" class="btn <?= $btnClass ?> dropdown-toggle" data-toggle="dropdown">
									<span class="caret"></span>
									<span class="sr-only">Toggle Dropdown</span>
								</button>

								<ul class="dropdown-menu" role="menu">
									<!-- ALTERAÇÔES DE STATUS -->

									<!-- APROVADO -->
									@if(AuthUtils::hasPermissionByUrl('AdminProviderChangeStatus/Aprovado') && strcmp($provider->status_name, "APROVADO") != 0)
									<li role="presentation">
										<a role="menuitem" class='dropdown-item' id="approve" tabindex="-1" href="{{ URL::Route('AdminProviderChangeStatus', array('APROVADO', $provider->id)) }}"
											onclick="return confirm('{{ trans('provider.approve_message') . ' ' . $provider->first_name . ' ' . $provider->last_name . '?' }}')">{{trans('provider.approve_grid') }}</a>
									</li>
									@endif

									<!-- SUSPENSO -->
									@if(AuthUtils::hasPermissionByUrl('AdminProviderChangeStatus/Suspenso') && strcmp($provider->status_name, "SUSPENSO") != 0)
									<li role="presentation">
										<a role="menuitem" class='dropdown-item' id="suspend" tabindex="-1" href="{{ URL::Route('AdminProviderChangeStatus', array('SUSPENSO', $provider->id)) }}"
											onclick="return confirm('{{ trans('provider.suspend_message') . ' ' . $provider->first_name . ' ' . $provider->last_name . '?' }}')">{{trans('provider.suspend_grid') }}</a>
									</li>
									@endif

								</ul>
							</div>
						</td>

												
						<td>
							<div class="dropdown">
								<button class="btn btn-flat btn-info dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown"> {{trans('provider.action_grid') }}
									<span class="caret"></span>
								</button>

								<ul class="dropdown-menu dropdown-menu-right">

									@if(AuthUtils::hasPermissionByUrl('providerAccountStatement'))
										<li role="presentation">
											<a id="view_provider_doc" class="dropdown-item" href="{{ URL::Route('providerAccountStatementAuto',[$provider->id, $newStartDate, $newEndDate]) }}" >
												{{ trans('finance.account_statement') }}
											</a>
										</li>
									@endif

									@if($total < 0) 
										<li role="presentation">
											<form onsubmit="return confirm('{{ trans('notification.sure') }}') " id="debitNotificationForm" action="{{ URL::Route('notificationDebit', array($provider->id)) }}" method="post">
												<input class="dropdown-item" type="submit" value="{{ trans('notification.debit_notification') }}" style="cursor: pointer;">
											</form>
										</li>
									@endif

								</ul>
							</div>
						
							<br/>
							<div align="center">
								<?php if(AuthUtils::hasPermission(Permission::PROVIDER_DOC) || Admin::isPartnerProfile()) {
									$provider_doc = ProviderDocument::where('provider_id', $provider->id)->first();
									if ($provider_doc != NULL) { ?>
										<a id="view_provider_doc" class="btn bg-navy" href="{{ URL::Route('AdminProviderDocuments',$provider->id) }}" title="{{trans('provider.view_documents_grid') }}">
											<i class="fa fa-id-badge"></i>
										</a>
									<?php } else { ?>
									<a id="view_provider_doc" class="btn btn-warning" href="{{ URL::Route('AdminProviderDocuments', $provider->id) }}" title="{{trans('provider.no_documents_grid') }}">
										<i class="fa fa-id-badge"></i>
									</a>
									<?php }
								} ?>
							</div>
						</td>
					</tr>										
					@endforeach
					<?php if(sizeof($providers) == 0){ ?>
					<tr>
						<td colspan="10">
							<label class="col-md-12 col-sm-12 col-lg-12" align="center">
								<?php echo trans('user_provider_web.no_result'); ?>
							</label>
						</td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
			<div class="box-footer">
				<?php echo $providers->appends(
			array(
				'id' => Input::get('id'),
				'name' => Input::get('name'),
				'email' => Input::get('email'),
				'city' => Input::get('city'),
				'state' => Input::get('state'),
				'start_date_compensation' => Input::get('start_date_compensation'),
				'end_date_compensation' => Input::get('end_date_compensation'),
				'start_date_created' => Input::get('start_date_created'),
				'end_date_created' => Input::get('end_date_created'),
				'order_balance' => Input::get('order_balance'),
				'status' => Input::get('status'),
				)
			)->render(); ?>
			</div>
		</div>
	</div>
</div>
@stop @section('styles')
<style>
	.peq {
		min-width: 100px
	}
</style>
@stop @section('javascripts')
<script type="text/javascript" src="{{ elixir('js/providers.list.js') }}"> </script>
<script type="text/javascript">
	var $form = $('#providersFilter');
	$form.submit(function() {
		$('#phone_number').val($('#phone').intlTelInput("getNumber"));
	});
	$(document).ready(function () {
		$('#btnDownloadReport').click(function (evt) {
			evt.preventDefault();
			var url = '/admin/providers/download-report?' + $('#providersFilter').serialize();
			var winDownload = window.open(url, '_blank', 'location=yes,height=570,width=520,scrollbars=yes,status=yes');
		});
		$('#plate').on('keyup', function() {
			$(this).val(
				$(this).val().toUpperCase()
			);
		});
		$('#plate').mask('AAA-NNNN', {'translation': {
			A: {pattern: /[A-Za-z]/},
			N: {pattern: /[0-9]/}
		}});
	});
</script>
<script type="text/javascript">
	jQuery('.date-range').datepicker({
		format: 'dd/mm/yyyy',
		language: "pt-BR",
		changeMonth: true,
		numberOfMonths: 1,
		autoclose: true,
		todayHighlight: true,
		toggleActive: true
	});
</script>
@stop