<!DOCTYPE html>
<!-- Modal to add or edit notification -->
<div id="modal-entry" class="modal fade" role="dialog">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<form id="form-insert-entry" method="post" target="popup" data-toggle="validator" action="{{ URL::Route('addFinancialEntry') }}">
				@if($page == 'financial')
					<input type="hidden" id="ledger-id" name="ledger-id" value="{{ $ledger->ledger->id }}">
				@else
					<input type="hidden" id="ledger-id" name="ledger-id">
				@endif
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<h4 class="modal-title">{{ trans('finance.insert_entry') }}</h4>
				</div>
				<div class="modal-body">
					<div class="box-body">
						@if($page == 'financial')
						<div class="row">
							<div class="col-sm-4"> <!-- Adicionando foto genérica -->
							<?php if($ledger->picture == "") {?>
								<img src="http://www.enactus.ufscar.br/wp-content/uploads/2016/09/boneco-gen%C3%A9rico.png" style="width:80px" alt="">
							<?php }
							else {
							?>
								<img class="profile-user-img" src="{{ $ledger->picture }}" style="width: 80px" alt="">
								<?php } ?>
							</div>
							<div class="col-sm-8" style="word-wrap: break-word;">
					
								<div class="profile-username">{{ $ledger->first_name.' '.$ledger->last_name }}</div>
								<div class="text-muted profile-phone">{{ $ledger->phone }}</div>
								<div class="text-muted profile-email">{{ $ledger->email }}</div>
							</div>
						</div><br>
						@else
						<div class="row">
							<div class="col-sm-4">
								<img class="profile-user-img">
							</div>
							<div class="col-sm-8" style="word-wrap: break-word;">
								<div class="profile-username"></div>
								<div class="text-muted profile-phone"></div>
								<div class="text-muted profile-email"></div>
							</div>
						</div>
						@endif
						<div id="field-errors" class="alert alert-danger alert-dismissable" style="display:none;margin-left:0;"></div>
						@if($page != 'financial')
						<div class="form-group">
							@if($page == 'users')
							<div class="dropdown">
								<input id="users-search" type="search" class="form-control" data-toggle="dropdown" aria-expanded="false" placeholder="{{trans('notification.user') }}"/>
								<ul id="users-list" class="dropdown-menu" aria-labelledby="users"></ul>
							</div>
							@elseif($page == 'providers')
							<div class="dropdown">
								<input id="providers-search" type="search" class="form-control" data-toggle="dropdown" aria-expanded="false" placeholder="{{trans('notification.provider') }}"/>
								<ul id="providers-list" class="dropdown-menu" role="menu" aria-labelledby="providers"></ul>
							</div>
							@endif
						</div>
						@endif
						<div class="form-group">
							<select name="type-entry" class="form-control" required data-error="{{trans('finance.type_required') }}" >
								<option>{{trans('finance.transaction_type') }}</option>								
								<option <?php echo Input::get('type-entry') == Finance::SEPARATE_CREDIT ? "selected" : "" ?> value="<?= Finance::SEPARATE_CREDIT ?>">{{trans('finance.separate_credit') }}</option>
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
							<div class="help-block with-errors"></div>
						</div>
						<div class="form-group">
							<input type="text" class="form-control" name="entry-description" required data-error="{{trans('finance.description_req')}}" placeholder="{{trans('finance.description')}} *" maxlength="100">
							<div class="help-block with-errors"></div>
						</div>
						<div class="form-group">
							<input type="number" class="form-control" id="entry-value" name="entry-value" maxlength="60" placeholder="{{trans('finance.value')}} *" required data-error="{{trans('finance.value_required') }}">
							<div class="help-block with-errors"></div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="pull-rigth">
						<button type="submit" class="btn btn-success">{{ trans('finance.insert') }}</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
	var isLoading = false;
	/** Element to show if no one found */
	var nothingFound = '<label style="font-weight: normal;padding: 10px 10px;">{{trans('adminController.notFound') }}</label>';
	/** Search user or provider using ajax */
	function search(name, type){
		/** clear list and events*/
		$('#providers-list').empty();
		var url = "{{ URL::Route('AdminSearchReferral') }}";
		$.ajax({
			type: "get",
			url: url,
			data: {name: name, type: type},
			success: function( response ) {
				isLoading = false;
				if(response['success']){
					var ledgers = response['referrals'];
					if(ledgers.length > 0){
						ledgers.forEach(function (ledger){
							var targetId = 0;
							var element = '<li role="presentation">' +
												'<a class="choose-ledger" data-id="' + ledger['id'] + '" data-phone="' + ledger['phone'] + '" data-email="' + ledger['email'] + '" tabindex="-1" href="#">' +
													'<div class="form-inline" style="color:#505050">' +
														'<img style="height:40px;margin:4px" src="' + ledger['picture'] + '" alt="">' +
														'<label style="font-weight:normal;">' + ledger['first_name'] + ' ' + ledger['last_name'] + '</label>' +
													'</div>' +
												'</a>' +
											'</li>';
							if(type === 0){
								$("#users-list").append($(element));
							} else if(type === 1) {
								$("#providers-list").append($(element));
							}
						});
					} else {
						/** If nothing found */
						if(type === 0){
							$('#users-list').empty();
							$("#users-list").append($(nothingFound));
						} else if(type === 1) {
							$('#providers-list').empty();
							$("#providers-list").append($(nothingFound));
						}
					}
					/** Insert events */
					$("#users-list li .choose-ledger").on('click', function(event){
						event.preventDefault();
						var ledger = $( this ),
							ledgerId = ledger.attr("data-id"),
							ledgerName = ledger.find("div").text(),
							ledgerPicture = ledger.find("img").prop('src'),
							ledgerPhone = ledger.attr("data-phone"),
							ledgerEmail = ledger.attr("data-email");

						$('#users-search').val(ledgerName);
						$('#ledger-id').val(ledgerId);
						$('.profile-user-img').attr('src', ledgerPicture);
						$('.profile-username').html(ledgerName);
						$('.profile-phone').html(ledgerPhone);
						$('.profile-email').html(ledgerEmail);
					});
					$("#providers-list li .choose-ledger").on('click', function(event){
						event.preventDefault();
						var ledger = $( this ),
							ledgerId = ledger.attr("data-id"),
							ledgerName = ledger.find("div").text(),
							ledgerPicture = ledger.find("img").prop('src'),
							ledgerPhone = ledger.attr("data-phone"),
							ledgerEmail = ledger.attr("data-email");

						$('#providers-search').val(ledgerName);
						$('#ledger-id').val(ledgerId);
						$('.profile-user-img').attr('src', ledgerPicture);
						$('.profile-username').html(ledgerName);
						$('.profile-phone').html(ledgerPhone);
						$('.profile-email').html(ledgerEmail);
					});
				} else {
					alert(response['message']);
				}
			},
			error: function (xhr, ajaxOptions, thrownError){
				//console.log(xhr.status);
			//	console.log(thrownError);
			}
		});
	}
	$(document).ready(function(){
		/** Search users by name */
		$("#users-search").keyup(function(){
			$("#users-list").empty()
			if(isLoading == false){
				var input = $(this), name = input.val(), open = input.attr( "aria-expanded" );
				if(name.length > 2){
					isLoading = true;
					if(open == "false"){
						input.dropdown('toggle');
					}
					search(name, 0);/** 0 for user | 1 for providers */
				}
			}
		});

		/** Search providers by name */
		$("#providers-search").keyup(function(){
			$("#providers-list").empty()
			if(isLoading == false){
				var input = $(this), name = input.val(), open = input.attr( "aria-expanded" );
				if(name.length > 2){
					isLoading = true;
					if(open == "false"){
						input.dropdown("toggle");
					}
					search(name, 1);/** 0 for user | 1 for providers */
				}
			}
		});
	});

	$("#form-insert-entry").submit(function(event) {
		/** Stop form from submitting normally**/
		event.preventDefault();
		$('#field-errors').text('');
		$('#field-errors').css( "display", "none");
		var redirect = "{{ Request::url() }}";
		var form = $( this ), url = form.attr( "action" );
		$('#btn-send').prop("disabled",true);
		$('#btn-send').addClass( "m-progress" );
		$('#btn-cancel').prop("disabled",true);
		form.serialize();
		//console.log(form.serialize()); -- Olhar depois
		$.ajax({
			type: "post",
			url: url,
			data: form.serialize(),
			success: function( response ) {
				//	console.log(response);
				if(response['success']){
					$("#modal-entry").modal("hide");
					setTimeout(function() {
						location.reload();
					}, 300);
				} else {
					// realiza o tratamento de errors
					var messages = response['messages'];
					var error = '';
					messages.forEach(function (message){
						error += '<p>'+message+'</p>';
					});
					$('#field-errors').append(error);
					$('#field-errors').css( "display", "block");
				}
			},
			error: function (xhr, ajaxOptions, thrownError){
				xhr.status;
				thrownError;
			//	console.log(xhr.status);
			//	console.log(thrownError);
			}
		});
		$('#btn-send').prop("disabled",false);
		$('#btn-send').removeClass( "m-progress" );
		$('#btn-cancel').prop("disabled",false);
	});

	$("#insert-entry").click(function(event){
		event.preventDefault();;
		$("#modal-entry").modal("show");
	});
	/** Clean modal on hidden */
	$("#modal-entry").on('hidden.bs.modal', function () {
		$(this).find('form').trigger("reset");
	});
	var options =  {
		reverse: true,
		onChange: function(strValue, event, currentField, options){
		//	console.log(currentField.val());
			var nValue = currentField.val().replace(/[.]/g, '').replace(/[,]/g, '.');
			currentField.prev().val(nValue);
		}
	};
	//$('#string-value').mask('#.##0,00',options);
	
</script