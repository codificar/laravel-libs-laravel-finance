<?php

return array(
	'user' 							=>'Usuario',
	'provider' 						=> 'Conductor',
	
	'payments' 						=> 'Pagos',
	'ride_ledger' 					=> 'Saldo débito de la carrera:',
	'ride_payment' 					=> 'Pago de carrera:',
	'simple_indication' 			=> 'Crédito por recomendación',
	'compensation_indication' 		=> 'Compensación de indicación de mes',
	'ride_debit' 					=> 'Deuda referente a la carrera:',
	'ride_debit_machine' 			=> 'Deuda referida a la carrera con un muñeco:',
	'ride_credit_machine' 			=> 'Crédito referido a la carrera con el dobby:',
	'ride_credit' 					=> 'Crédito de carrera:',
	'ride_payment' 					=> 'Pago por número de carrera:',
	'ride_card_payment' 			=> 'Pago con tarjeta de crédito',
	'ride_debitCard_payment'		=> 'Pago con tarjeta de débito',
	'ride_crypt_payment' 			=> 'Pago con moneda criptográfica',
	'ride_carto_payment' 			=> 'Pago con tarjeta',
	'ride_card_payment_pending' 	=> 'Pago pendiente con tarjeta de crédito',
	'ride_payment_fail_debit' 		=> 'Débito por falta de pago con tarjeta',
	'ride_cancellation_fee' 		=> 'Tarifa de cancelación de carrera',
	'plural' 						=> 'Cuenta corriente',
	'id' 							=> 'IDENTIFICACIÓN',
	'finance_date' 					=> 'Fecha',
	'finance_time' 					=> 'Hora',
	'transaction' 					=> 'Transacción',
	'reason' 						=> 'Razón',
	'finance_value' 				=> 'Valor',
	'ride_value' 					=> 'Valor utilizado en carreras',
	'delete_message_1' 				=> 'Este usuario debe',
	'delete_message_2' 				=> 'reales, ¿quieres cobrar la deuda?',
	'tax_value'						=> 'La cuota',
	

	'op_simple_indication' 			=> 'Indicación simple',
	'op_compensation_indication' 	=> 'Remuneración por objetivo de referencia',
	'op_ride_payment' 				=> 'Pago por ejecución',
	'op_ride_debit' 				=> 'Deuda por carrera',
	'op_ride_credit' 				=> 'Crédito por carrera',
	'op_ride_ledger' 				=> 'Saldo Débito',
	'op_debit_cancellation' 		=> 'Cargo por cancelación de carrera',
	'op_credit_cancellation' 		=> 'Crédito por cancelación de carrera',
	'op_ride_cancellation_debit'	=> 'Cargo por cancelación de carrera',
	'op_ride_payment_fail_debit'	=> 'Deuda por impago',
	'op_ride_cancellation_credit'	=> 'Crédito por cancelación de carrera',
	'op_separate_credit' 			=> 'Préstamos Préstamos',
	'op_separate_debit' 			=> 'Débito Débito',
	'op_withdraw' 					=> 'Retirar',
	'op_cleaning_fee_debit'			=> 'Débito por tarifa de limpieza',
	'op_cleaning_fee_credit'		=> 'Crédito de tarifa de limpieza',
	'op_delivery_package'			=> 'Paquete de entrega',
	'separate_credit' 				=> 'Préstamos Préstamos',
	'separate_debit' 				=> 'Débito Débito',
	'withdraw' 						=> 'Retirar',

	'datePattern' 					=> 'd/m/Y',
	'stringDatePattern' 			=> '%d/%m/%Y',
	'period' 						=> 'Peíodo solicitado: :start a :end',
	'account_statement' 			=> 'Estado de cuenta',
	'withdrawals_report'			=> 'Informe de retirada',
	'previous_balance' 				=> 'Saldo anterior',
	'previous_balance_msg' 			=> 'Saldo que tenías antes de la fecha de inicio del filtro',
	'balance' 						=> 'Equilibrio',
	'current_balance' 				=> 'Saldo actual',
	'current_balance_msg'			=> 'El Saldo Actual es la suma de todos los montos, considerando la fecha de compensación hasta el momento actual (valores menores o iguales a la fecha / hora actual)',
	'period_balance'				=> 'Balance en el período',
	'period_balance_msg'			=> 'Es la suma de los valores considerando el período seleccionado en el filtro',
	'available_balance'				=> 'Saldo disponible',
	'required_balance'				=> 'Balance requerido',
	'account_balance' 				=> 'Saldo de la cuenta corriente',
	'transaction_type' 				=> 'Tipo de transacción',
	'type_required'					=> 'Debes elegir el tipo de transacción',
	'ride' 							=> 'Raza',
	'no_entries' 					=> 'Sin contabilizaciones en el período',
	'description'					=> 'Descripción',
	'description_req'				=> 'Debes ingresar la descripción',
	'value'							=> 'Valor',
	'value_required'				=> 'Debes ingresar un valor',
	'date_format'					=> 'El formato de la fecha debe ser	DD/MM/YYYY',
	'minimum_value'					=> 'Valor mínimo',
	'maximum_value'					=> 'Valor máximo',
	'download'						=> 'Descargar informe de cuenta',
	'total'							=> 'Total',
	'future_balance'				=> 'Balance futuro',
	'future_balance_msg'			=> 'El saldo futuro es la suma de todos los importes, excluida la fecha de compensación (importes anteriores a la fecha de hoy y posteriores a la fecha de hoy)',
	'period_date'					=> 'Curso del tiempo',
	'to' 							=> 'La',
	'start_date'					=> 'Fecha de inicio',
	'end_date'						=> 'Fecha de finalización',
	'search'						=> 'Buscar',
	'period_balance' 				=> 'Balance del período',
	'single_credit' 				=> 'Crédito de préstamo agregado con tarjeta',

	'this_month' 					=> 'Este mes',
	'last_month' 					=> 'Mes pasado',
	'this_year' 					=> 'Este año',
	'last_year' 					=> 'Año pasado',

	'title_statement' 				=> 'Lançamentos no período de :start a :end',
	'vue_title_statement' 			=> 'Contabilizaciones en el período desde',
	'vue_future_title_statement'	=> 'Liberaciones de',
	'title_future_compensation' 	=> 'Compensación futura',
	'total_compensations'			=> 'Total de cuentas por cobrar',
	'total_compensations_msg'		=> 'Es el valor que el usuario seguirá recibiendo (suma de los valores con una fecha / hora mayor que el momento actual)',

	'pix'							=> 'PIX',

	'insert_entry' 					=> 'Insertar transacción',
	'insert' 						=> 'Insertar',
	'add_new'						=> 'Registrar nuevo',
	'reason_not_found' 				=> 'Tipo de transacción no encontrado',
	'balance_error' 				=> '¡Saldo insuficiente para cobrar deuda!',
	'balance_success' 				=> 'Deuda cobrada con éxito',
	'insufficient_balance' 			=> 'Fondos insuficientes',

	'transaction_add_success' 		=> 'Transacción agregada correctamente',
	'request_withdraw'				=> 'Solicitud de retirada',
	'withdraw_tax'					=> 'Cargo por retiro',
	'bank_account'					=> 'Cuenta bancaria',
	'add_new_bank_account'			=> 'Agregar cuenta bancaria',
	'make_payment'					=> 'Realizar pago',
	'pay'							=> 'pagar',

	'bank_account_data' 			=> 'Datos de la cuenta',
    'bank_account' 					=> 'Cuenta bancaria',
    'holder_name' 					=> 'Nombre del beneficiario',
	'bank' 							=> 'Banco',
	'account'						=> 'Cuenta',
	'agency' 						=> 'Agencia',
	'agency_number'					=> 'Número de agencia',
	'agency_digit' 					=> 'Dígito de agencia',
	'digit'							=> 'Dígito',
	'account_types'					=> 'Tipo de cuenta',
    'account_number' 				=> 'Número de cuenta',
	'number'						=> 'Número',
    'account_digit' 				=> 'Dígito de verificación',
	'holder_document'				=> 'Documento',
	'document_number' 				=> 'Número del Documento',
	'document_type' 				=> 'Tipo de documento',
	
	'holder_name_info' 				=> 'Inserte el nombre del beneficiario según lo informado por el banco',

	'bank_holder_document'			=> 'Documento del titular',
	'address_street'				=> 'Habla a',
	'period_requests'				=> 'Solicitudes en el período',
	'total_balance'					=> 'Balance total',
	'hit_value'						=> 'Valor de acierto',
	'provider_in_debit'				=> 'Proveedor destacado',
	'show_total_period'				=> 'Mostrando período total',
	'show_period'				    => 'Mostrando desde :start à :end',

	'occurrence_date'				=> 'Ocurrencia',
	'no_data_found'					=> 'No encontramos ningún registro',
	'try_again_later'				=> 'Intente cambiar la fecha en los filtros.',
	'indexing'						=> 'Demostración',
	'items'							=> 'registros',
	'of'							=> 'en',
	
    'save'							=> 'Guardar datos',
	'cancel'						=> 'Cancelar',
	'without'						=> 'sin',

	'value_to_pay'					=> 'Valor a pagar',
	'change_value'					=> 'Valor de cambio',
	'insert_value'					=> 'Ingresar valor',
	'new_billet'					=> 'Paga con Boleto',
	'payment_card'					=> 'Pagar con tarjeta',
	'add_balance'					=> 'Agregar saldo',

	'new_card'						=> 'Nueva tarjeta',
	'card_number'					=> 'Numero de tarjeta',
	'card_holder_name'				=> 'Nombre del titular de la tarjeta',
	'cvv'							=> 'CVV',
	'card_declined'					=> 'Tarjeta rechazada',
	'card_added'					=> 'Tarjeta agregada',
	'card_removed'					=> 'Tarjeta eliminada',
	'confirm_payment'				=> 'Confirmar pago',
	'choose_card'					=> 'Elegir una tarjeta',
	'payment_creditcard_success'	=> '¡Pago exitoso!',
	'value_cant_be_lower'			=> 'El valor no puede ser menor que',
	'confirm_create_billet'			=> 'Confirmar pago',
	'confirm_create_billet_msg'		=> "¿Está seguro de que desea generar un boleto para",
	'confirm'						=> 'Confirmar',
	'billet_success'				=> '¡Boleto generado con éxito!',
	'billet_success_msg'			=> 'Para ver el boleto',
	'confirm_create_pix'			=> 'Tem certeza que deseja pagar com pix no valor de',
	'pix_success'					=> 'Pix gerado com sucesso!',
	'pix_success_msg'				=> 'Código pix: ',
	'pix_info_1'					=> 'Paga con Pix y recibe la confirmación al instante',
	'pix_info_2'					=> 'Abra la aplicación de su institución financiera',
	'pix_info_3'					=> 'Haz un Pix leyendo el código QR o copiando el código de pago',
	'pix_info_4'					=> '¡Revisa la información, espera la confirmación y listo!',
	'copy'							=> 'Copia',
	'copied'						=> 'copiado!',

	'monthNames' => array(
									'',
									'Enero - :y',
									'Febrero - :y',
									'Marcha - :y',
									'Abril - :y',
									'Mayo - :y',
									'Junio - :y',
									'Mes de julio - :y',
									'Agosto - :y',
									'Septiembre - :y',
									'Octubre - :y',
									'Noviembre - :y',
									'diciembre - :y'
	),
	'vueMonthNames' => array(
		'Enero',
		'Febrero',
		'Marcha',
		'Abril',
		'Mayo',
		'Junio',
		'Mes de julio',
		'Agosto',
		'Septiembre',
		'Octubre',
		'Noviembre',
		'diciembre'
	),

	'success_import' => 'Importación exitosa de pagos de cancelación!',
	'success_error' => 'Se produjo un error al importar la cancelación del pago!',
	'import_payment' => 'Importar cancelaciones de pagos a proveedores',
	'select_file' => 'Seleccione Archivo',
	'exemple_file' => 'Descargar archivo de muestra',
	'import' => 'Importar',

	'consolidated_extract' => 'Declaración consolidada',
	'filter' => 'Filtros',
	'key_word' => 'Palabra clave',
	'type' => 'Tipo',
	'select' => 'Seleccione...',
	'location' => 'Localização',
	'positive' => 'Positivo',
	'negative' => 'Negativo',
	'partner' => 'Pareja',
	'ocurrency' => 'Ocurrencia',
	'clients_notify' => 'Notificar a los clientes endeudados',
	'title' => 'Título',
	'message' => 'Mensaje',
	'send_notification' => 'Enviar notificación',
	'ledger_id' => 'ID de billetera',
	'name' => 'Nombre',
	'period_requests_count' => 'Solicitudes en el período',
	'total_ro_receive' => 'Total de cuentas por cobrar',
	'actions' => 'Comportamiento',
	'action_grid' => 'Comportamiento',
	'debit_notification' => 'Notificar débito',
	'client_in_debit' => 'Cliente de débito',
	'down_report' => 'Baixar Declaración',
	'corp' => 'Institución',
	'op_deposit_in_account' => 'Depósito en cuenta (cancelación de pago)',
	'delimiter' => 'Delimitador',
	'date_format' => 'Formato da data'
);