@extends('layout.master')

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
<div class="col-lg-12">
	<div class="card card-outline-info">
		<div class="card-header">
			<h4 class="m-b-0 text-white">{{ trans('finance.account_statement') }}</h4>
		</div>
		<div class="card-block">
			<div class="row">
				<div class="col-md-12">
					<div class="box box-warning">
						<div class="box-header">				
							<h3 class="box-title"><?=$holder?></h3>
						</div>
						<div class="box-body">
							<form id="filter-account-statement" method="get" action="{{ Request::url() }}">
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label for="giveName"> {{trans('finance.transaction_type') }} </label>
											<select name="type-entry" id="type-entry" class="select form-control">
												<option value=""> {{trans('finance.transaction_type') }} </option>
												<option <?php echo Input::get('type-entry') == Finance::SEPARATE_CREDIT ? "selected" : "" ?> value="<?= Finance::SEPARATE_CREDIT ?>">{{trans('finance.separate_credit') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::RIDE_CREDIT ? "selected" : "" ?> value="<?= Finance::RIDE_CREDIT ?>">{{trans('finance.op_ride_credit') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::SEPARATE_DEBIT ? "selected" : "" ?> value="<?= Finance::SEPARATE_DEBIT ?>">{{trans('finance.separate_debit') }}</option>	
												<option <?php echo Input::get('type-entry') == Finance::RIDE_LEDGER ? "selected" : "" ?> value="<?= Finance::RIDE_LEDGER ?>">{{trans('finance.op_ride_ledger') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::RIDE_DEBIT ? "selected" : "" ?> value="<?= Finance::RIDE_DEBIT ?>">{{trans('finance.op_ride_debit') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::SIMPLE_INDICATION ? "selected" : "" ?> value="<?= Finance::SIMPLE_INDICATION ?>">{{trans('finance.op_simple_indication') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::RIDE_PAYMENT ? "selected" : "" ?> value="<?= Finance::RIDE_PAYMENT ?>">{{trans('finance.op_ride_payment') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::COMPENSATION_INDICATION ? "selected" : "" ?> value="<?= Finance::COMPENSATION_INDICATION ?>">{{trans('finance.op_compensation_indication') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::RIDE_CANCELLATION_DEBIT ? "selected" : "" ?> value="<?= Finance::RIDE_CANCELLATION_DEBIT ?>">{{trans('finance.op_debit_cancellation') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::RIDE_CANCELLATION_CREDIT ? "selected" : "" ?> value="<?= Finance::RIDE_CANCELLATION_CREDIT ?>">{{trans('finance.op_credit_cancellation') }}</option>
												<option <?php echo Input::get('type-entry') == Finance::WITHDRAW ? "selected" : "" ?> value="<?= Finance::WITHDRAW ?>">{{trans('finance.withdraw') }}</option>
											</select>
										</div>
									</div>
									<!--span-->
									<div class="col-md-6">
										<div class="form-group">
											<label for="daterange"> {{trans('dashboard.period_date') }} </label>
											<div class="input-daterange input-group" id="date-range">											
												<input type="text" class="form-control" name="start-date" placeholder="{{trans('dashboard.start_date')}}" value="{{ Input::get('start-date') }}"/>
												<span class="input-group-addon bg-info b-0 text-white">{{trans('dashboard.to') }}</span>
												<input type="text" class="form-control" name="end-date" placeholder="{{trans('dashboard.end_date') }}" value="{{ Input::get('end-date') }}"/>
											</div>
										</div>
									</div>
								</div> <!--/ end-row-->
								<div class="box-footer pull-right">
									<button type="submit" name="submit" class="btn btn-info right" value="Download_Report"> <i class="mdi mdi-download"></i> {{trans('finance.download')}}</button>
									<button type="submit" class="btn btn-success right" name="submit"  value="Filter_Data"> <i class="fa fa-search"></i> {{ trans('provider.search') }}</button>
								</div>
							</form>	
							<button id="insert-entry" data-toggle="modal" data-target="#modal-entry" class="btn btn-info">
								<i class="fa fa-money"></i> {{ trans('finance.insert_entry') }}
							</button>
						</div> <!--/ box-body -->
					</div> <!--/ box-warning -->
				</div>
			</div>
		</div>
	</div>
</div>
<?php 
	$lastDate = '';
	$entries = $balance['detailed_balance'];
	$previousBalance = $balance['previous_balance'];
	$currentBalance = $balance['current_balance'];
	$date = explode(' ', $start);
	$lastDate = array('');
	$futureCompensations = array();

?>
<?php if(sizeof($entries) > 0){
	$totalizer = 0; ?>
	<div class="col-lg-12">
		<div class="card">
			<div class="card-block">	
				<h3 class="card-title">{{trans('finance.title_statement', array(
					'start' => strftime(trans('finance.stringDatePattern'), strtotime($start)),
					'end' => strftime(trans('finance.stringDatePattern'), strtotime(explode(' ', $end)[0]))
					)) }}
				</h3>
				<?php
					if($previousBalance >= 0){
						$elementValue = '<span class="text-success">R$ '.number_format($previousBalance, 2, ',', ' ').'</span>';
					}else{
						$elementValue = '<span class="text-danger">R$ '.number_format($previousBalance, 2, ',', ' ').'</span>';
					}
					$desc = trans('finance.previous_balance');
					echo
						'<div class="card-block">
							<i class="fa fa-money"></i> 
							<strong>'.$desc.': </strong> '.$elementValue.'
						</div>
						<div class="card-block"> 
							<table class="table table-bordered">
								<tr>
									<th>'.trans("finance.finance_date").' <i class="fa fa-calendar"></i></th>
									<th>'.trans("finance.finance_time").' <i class="fa fa-clock-o"></i></th> 
									<th>'.trans("finance.transaction_type").' <i class="fa fa-exchange"></i></th>
									<th>'.trans("finance.reason").' <i class="fa fa-pencil-square"></i></th>
									<th>'.trans("finance.finance_value").' <i class="fa fa-money"></i></th>
								</tr>';
					$reason=trans('finance.reason_not_found');
					foreach ($entries as $entry){						
						if($entry->compensation_date <= $end){
							$totalizer += $entry->value;
							$date = explode(' ', $entry->compensation_date);
							echo "<tr>
											<td>" . strftime('%d %b %Y', strtotime($date[0])) . "</td>";
							$lastDate = $date;
							$hour = str_split($date[1], 5);
							if($entry->value>=0)
								$elementValue = '<p class="text-success">R$ '.number_format($entry->value, 2, ',', ' ').'</p>';
							else
								$elementValue = '<p class="text-danger">R$ '.number_format($entry->value, 2, ',', ' ').'</p>';

							if($entry->request_id){
								$request = '<a target="_blank" href="'.URL::Route('AdminRequestsDetails', $entry->request_id).'" target="_blank"> '.$entry->request_id.'</a>';
							}else{
								$request = '';
							}
							switch($entry->reason){
								case Finance::SEPARATE_CREDIT :
									$reason = trans('finance.separate_credit');
									break;
								case Finance::SEPARATE_DEBIT :
									$reason = trans('finance.separate_debit');
									break;
								case Finance::RIDE_DEBIT:
									$reason = trans('finance.op_ride_debit');
									break;
								case Finance::RIDE_CREDIT:
									$reason = trans('finance.op_ride_credit');
									break;
								case Finance::RIDE_LEDGER:
									$reason = trans('finance.op_ride_ledger');
									break;
								case Finance::SIMPLE_INDICATION:
									$reason = trans('finance.op_simple_indication');
									break;
								case Finance::COMPENSATION_INDICATION:
									$reason = trans('finance.op_compensation_indication');
									break;
								case Finance::RIDE_PAYMENT:
									$reason = trans('finance.op_ride_payment');
									break;
								case Finance::RIDE_CANCELLATION_DEBIT:
									$reason = trans('finance.op_debit_cancellation');
									break;
								case Finance::RIDE_CANCELLATION_CREDIT:
									$reason = trans('finance.op_credit_cancellation');
									break;
								case Finance::WITHDRAW:
									$reason = trans('finance.withdraw');
									break;
							}
							$icon = AccountController::makeEntryIcon($entry->reason);
							($entry->insertedBy['username']) ? $inserted= $entry->insertedBy['username'] : $inserted = "";
							echo
								'	<td>'.$hour[0].'</td>									
									<td>'.$reason.'</td>
									<td>'.$entry->description.' '. $request.'</td>
									<td>'.$elementValue.'</td> 
								</tr>';
						}else{
							array_push($futureCompensations, $entry);
						}
					}
					$date = explode(' ', $end);
					if($currentBalance >= 0){
						$elementValue = '<span class="text-success">R$ '.number_format($currentBalance, 2, ',', ' ').'</span>';
					}else{
						$elementValue = '<span class="text-danger">R$ '.number_format($currentBalance, 2, ',', ' ').'</span>';
					}
					$desc = trans('finance.current_balance');
					echo
						'
						<tr style="font-weight:bold;text-align:end;">
							<td colspan = "4" style="text-align:center;">'.trans('dashboard.total').'</td>
							<td style="text-align:center;">'.number_format($totalizer, 2, ',', ' ').'</td>
						</tr>
					</table>
					</div>';
				?>
			</div>
		</div>
	</div> 
<?php } else{ ?>
	<div class="card-block">
		<i class="fa fa-money"></i>
		<strong> {{ trans('finance.current_balance') }}: </strong>
		@if($currentBalance >= 0)
			<p class="text-success"> R$ {{ number_format($currentBalance, 2, ',', ' ') }} </p>
		@else
			<p class="text-danger"> R$ {{ number_format($currentBalance, 2, ',', ' ') }} </p>
		@endif
	</div>
<?php }?>

<?php if(sizeof($futureCompensations) > 0){ $total2 = 0;?>
	<div class="col-lg-12">
		<div class="card">
			<div class="card-block">
				<div class="card-title">
					<h3>{{trans('finance.title_future_compensation') }}</h3>
				</div>		
				<table class="table table-bordered">
					<tr>
						<th>{{ trans("finance.finance_date") }} <i class="fa fa-calendar"></i></th>
						<th>{{ trans("finance.finance_time") }} <i class="fa fa-clock-o"></i></th>
						<th>{{ trans("finance.finance_value") }} <i class="fa fa-money"></i></th>
						<th>{{ trans("finance.transaction_type") }} <i class="fa fa-exchange"></i></th>
						<th>{{ trans("finance.reason") }}<i class="fa fa-pencil-square"></i></th>
					</tr>
					<?php
						$lastDate = array('');
						$reason=trans('finance.reason_not_found');;
						foreach ($futureCompensations as $entry){
							$total2 += $entry->value;
							$date = explode(' ', $entry->compensation_date);
							echo"<tr>
								<td>" . strftime('%d %b %Y', strtotime($date[0])) . "</td>";
							$lastDate = $date;
							$hour = str_split($date[1], 5);
							if($entry->value >= 0){
								$elementValue = '<p class="text-success">R$ '.number_format($entry->value, 2, ',', ' ').'</p>';
							}else{
								$elementValue = '<p class="text-danger">R$ '.number_format($entry->value, 2, ',', ' ').'</p>';
							}
							if($entry->request_id){
								$request = '<a target="_blank" href="'.URL::Route('AdminRequestsDetails', $entry->request_id).'" target="_blank"> '.$entry->request_id.'</a>';
							}else{
								$request = '';
							}
							switch($entry->reason){
								case Finance::SEPARATE_CREDIT :
									$reason = trans('finance.separate_credit');
									break;
								case Finance::SEPARATE_DEBIT :
									$reason = trans('finance.separate_debit');
									break;
								case Finance::RIDE_DEBIT:
									$reason = trans('finance.op_ride_debit');
									break;
								case Finance::RIDE_CREDIT:
									$reason = trans('finance.op_ride_credit');
									break;
								case Finance::RIDE_LEDGER:
									$reason = trans('finance.op_ride_ledger');
									break;
								case Finance::SIMPLE_INDICATION:
									$reason = trans('finance.op_simple_indication');
									break;
								case Finance::COMPENSATION_INDICATION:
									$reason = trans('finance.op_compensation_indication');
									break;
								case Finance::RIDE_PAYMENT:
									$reason = trans('finance.op_ride_payment');
									break;
								case Finance::RIDE_CANCELLATION_DEBIT:
									$reason = trans('finance.op_debit_cancellation');
									break;
								case Finance::RIDE_CANCELLATION_CREDIT:
									$reason = trans('finance.op_credit_cancellation');
									break;
								case Finance::WITHDRAW:
									$reason = trans('finance.withdraw');
									break;
							}
							$icon = AccountController::makeEntryIcon($entry->reason);
							echo
								'<td>'.$hour[0].'</td>
								<td>'.$elementValue.'</td> 
								<td> '.$reason.' </td>
								<td>'.$entry->description.' '. $request.'</td>
							</tr>';
						}
						echo '<tr style="font-weight:bold;text-align:end;">
							<td colspan = "4" style="text-align:center;">'.trans('dashboard.total').'</td>
							<td style="text-align:center;">'.number_format($total2, 2, ',', ' ').'</td>
						</tr>';
					?>
				</table>
			</div>
		</div>
	</div>
<?php }?>	
@stop

@include('financial.modal_entry')

@section('javascripts')

<script type="text/javascript">
	jQuery('#date-range').datepicker({
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