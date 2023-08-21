<?php

return array(
	'user' 							=> 'Usuário',
	'provider' 						=> 'Motorista',
	
	'payments' 								=> 'Pagamentos',
	'ride_ledger' 							=> 'Débito de saldo referente à corrida: ',
	'ride_payment' 							=> 'Pagamento da corrida: ',
	'simple_indication' 					=> 'Crédito de indicação',
	'compensation_indication' 				=> 'Compensação de indicação do mês',
	'webhook_pix_ride_credit' 				=> 'Crédito via Pix Webhook referente à corrida: ',
	'webhook_pix_balance_credit' 			=> 'Crédito via Pix Webhook referente à saldo: ',
	'ride_debit' 							=> 'Débito referente à corrida:',
	'ride_debit_machine' 					=> 'Débito referente à corrida com maquineta:',
	'ride_credit_machine' 					=> 'Credito referente à corrida com maquineta:',
	'ride_credit' 							=> 'Crédito referente à corrida:',	
	'ride_payment' 							=> 'Pagamento referente a corrida número:',
	'ride_card_payment' 					=> 'Pagamento com cartão de crédito',
	'ride_debitCard_payment'				=> 'Pagamento com cartão de débito',
	'ride_crypt_payment' 					=> 'Pagamento com crypto moeda',
	'ride_carto_payment' 					=> 'Pagamento com cartão Carto',
	'ride_card_payment_pending' 			=> 'Pagamento de pendência com cartão de crédito',
	'ride_credit_card_payment_pending_pix'	=> 'Crédito de pendência com pix gateway',
	'ride_debit_card_payment_pending_pix'	=> 'Débito de pendência com pix gateway',
	'ride_payment_fail_debit' 				=> 'Débito por falha de pagamento com cartão',
	'ride_cancellation_fee' 				=> 'Taxa de cancelamento de corrida',
	'plural' 								=> 'Conta Corrente',
	'id' 									=> 'ID',
	'finance_date' 							=> 'Data',
	'finance_time' 							=> 'Hora',
	'transaction' 							=> 'Transação',
	'reason' 								=> 'Motivo',
	'finance_value' 						=> 'Valor',
	'ride_value' 							=> 'Valor utilizado em corridas',
	'delete_message_1' 						=> 'Este usuário está devendo',
	'delete_message_2' 						=> 'reais, deseja cobrar a divida?',
	'tax_value'								=> 'Valor da Taxa',
	

	'op_simple_indication' 			=> 'Indicação Simples',
	'op_compensation_indication' 	=> 'Remuneração por meta de indicação',
	'op_ride_payment' 				=> 'Pagamento por corrida',
	'op_ride_debit' 				=> 'Débito por corrida',
	'op_ride_debit_split' 			=> 'Débito por corrida (Split Ativo)',
	'op_ride_credit' 				=> 'Crédito por corrida',
	'op_ride_credit_split' 			=> 'Crédito por corrida (Split Ativo)',
	'op_ride_debit_pending_pix'		=> 'Débito de corrida por pagamento pendente em pix gateway',
	'op_ride_credit_pending_pix'	=> 'Crédito de corrida por pagamento pendente em pix gateway',
	'op_ride_ledger' 				=> 'Débito de saldo',
	'op_debit_cancellation' 		=> 'Débito por cancelamento de corrida',
	'op_credit_cancellation' 		=> 'Crédito por cancelamento de corrida',
	'op_ride_cancellation_debit'	=> 'Débito por cancelamento de corrida',
	'op_ride_payment_fail_debit'	=> 'Débito por falha no pagamento',
	'op_ride_cancellation_credit'	=> 'Crédito por cancelamento de corrida',
	'op_separate_credit' 			=> 'Crédito Avulso',
	'op_separate_debit' 			=> 'Débito Avulso',
	'op_withdraw' 					=> 'Saque',
	'op_cleaning_fee_debit'			=> 'Débito por taxa de limpeza',
	'op_cleaning_fee_credit'		=> 'Crédito por taxa de limpeza',
	'op_delivery_package'			=> 'Pacote de entrega',
	'op_invoice_payment'			=> 'Pagamento de fatura',
	'separate_credit' 				=> 'Crédito Avulso',
	'separate_debit' 				=> 'Débito Avulso',
	'withdraw' 						=> 'Saque',

	'datePattern' 					=> 'd/m/Y',
	'stringDatePattern' 			=> '%d/%m/%Y',
	'period' 						=> 'Período solicitado: :start a :end',
	'account_statement' 			=> 'Extrato da Conta',
	'withdrawals_report'			=> 'Relatório de Saques',
	'previous_balance' 				=> 'Saldo Anterior',
	'previous_balance_msg' 			=> 'Saldo que tinha antes da data inicial do filtro',
	'balance' 						=> 'Saldo',
	'current_balance' 				=> 'Saldo Atual',
	'current_balance_msg'			=> 'Saldo Atual é a soma de todos os valores, considerando a data de compensação até o momento atual (valores menor ou igual a data/hora de agora)',
	'period_balance'				=> 'Saldo no período',
	'period_balance_msg'			=> 'É o somatório dos valores considerando o período selecionado no filtro',
	'available_balance'				=> 'Saldo disponível',
	'required_balance'				=> 'Saldo necessário',
	'account_balance' 				=> 'Saldo Conta Corrente',
	'transaction_type' 				=> 'Tipo de transação',
	'type_required'					=> 'Você deve escolher o tipo de transação',
	'ride' 							=> 'Corrida',
	'no_entries' 					=> 'Sem lançamentos no período',
	'description'					=> 'Descrição',
	'description_req'				=> 'Você deve digitar a descrição',
	'value'							=> 'Valor',
	'value_required'				=> 'Você deve digitar um valor',
	'date_format'					=> 'O formato da data precisa ser DD/MM/YYYY',
	'minimum_value'					=> 'Valor Mínimo',
	'maximum_value'					=> 'Valor Máximo',
	'download'						=> 'Baixar relatório da conta',
	'total'							=> 'Total',
	'future_balance'				=> 'Saldo futuro',
	'future_balance_msg'			=> 'Saldo futuro é a soma de todos os valores, sem considerar a data de compensação (valores antes da data de hoje e depois da data de hoje)',
	'positive_balance_msg'			=> 'O crédito total é a soma de todos valores de crédito (positivos) do prestador.',
	'negative_balance_msg'			=> 'O débito total é a soma de todos valores de débito (negativos) do prestador.',
	'period_date'					=> 'Período',
	'to' 							=> 'a',
	'start_date'					=> 'Data de Início',
	'end_date'						=> 'Data de Término',
	'search'						=> 'Pesquisar',
	'period_balance' 				=> 'Saldo do período',
	'single_credit' 				=> 'Crédito avulso adicionado pelo cartão',
	'credit_by_debit' 				=> 'Crédito avulso pelo cartão para quitar débito.',

	'this_month' 					=> 'Este mês',
	'last_month' 					=> 'Mês passado',
	'this_year' 					=> 'Este ano',
	'last_year' 					=> 'Ano passado',

	'title_statement' 				=> 'Lançamentos no período de :start a :end',
	'vue_title_statement' 			=> 'Lançamentos no período de ',
	'vue_future_title_statement'	=> 'Lançamentos a partir de ',
	'title_future_compensation' 	=> 'Compensações futuras',
	'total_compensations'			=> 'Total a receber',
	'total_compensations_msg'		=> 'É o valor que o usuário ainda vai receber (somatório dos valores com data/hora maior que o momento atual)',

	'pix'							=> 'PIX',

	'insert_entry' 					=> 'Inserir Transação',
	'insert' 						=> 'Inserir',
	'add_new'						=> 'Cadastrar nova',
	'reason_not_found' 				=> 'Tipo de transação não encontrado',
	'balance_error' 				=> 'Saldo insuficiente para cobrar dívida!',
	'balance_success' 				=> 'Dívida cobrada com sucesso',
	'insufficient_balance' 			=> 'Saldo insuficiente',

	'transaction_add_success' 		=> 'Transação adicionada com sucesso',
	'request_withdraw'				=> 'Solicitar Saque',
	'withdraw_tax'					=> 'Taxa de Saque',
	'bank_account'					=> 'Conta bancária',
	'add_new_bank_account'			=> 'Adicionar conta bancária',
	'make_payment'					=> 'Realizar pagamento',
	'pay'							=> 'pagar',

	'bank_account_data' 			=> 'Dados da Conta',
    'bank_account' 					=> 'Conta Bancária',
    'holder_name' 					=> 'Nome do Favorecido',
	'bank' 							=> 'Banco',
	'account'						=> 'Conta',
	'agency' 						=> 'Agência',
	'agency_number'					=> 'Número da Agência',
	'agency_digit' 					=> 'Dígito da Agência',
	'digit'							=> 'Digito',
	'account_types'					=> 'Tipo da Conta',
    'account_number' 				=> 'Número da Conta',
	'number'						=> 'Número',
    'account_digit' 				=> 'Dígito Verificador',
	'holder_document'				=> 'Documento', 
	'document_number' 				=> 'Número de documento',
	'document_type' 				=> 'Tipo de documento',
	
	'holder_name_info' 				=> 'Insira o nome do favorecido tal como informado pelo banco',
	
	'bank_holder_document'			=> 'Documento do titular',
	'address_street'				=> 'Endereço',
	'period_requests'				=> 'Solicitações no período',
	'total_balance'					=> 'Saldo total',
	'positive_balance'				=> 'Crédito total',
	'negative_balance'				=> 'Débito total',
	'hit_value'						=> 'Valor do acerto',
	'provider_in_debit'				=> 'Prestador em dívida',
	'show_total_period'				=> 'Mostrando período total',
	'show_period'				    => 'Mostrando de :start à :end',

	'occurrence_date'				=> 'Ocorrência',
	'no_data_found'					=> 'Não encontramos registros',
	'try_again_later'				=> 'Experimente trocar a data nos filtros.',
	'indexing'						=> 'Mostrando',
	'items'							=> 'registros',
	'of'							=> 'de',
	
    'save'							=> 'Salvar Dados',
	'cancel'						=> 'Cancelar',
	'without'						=> 'sem',

	'value_to_pay'					=> 'Valor a Pagar',
	'change_value'					=> 'Alterar valor',
	'insert_value'					=> 'Insira o Valor',
	'new_billet'					=> 'Pagar no Boleto',
	'payment_card'					=> 'Pagar no Cartão',
	'add_balance'					=> 'Adicionar Saldo',
	'add_pix_balance'				=> 'Pagar no Pix',

	'new_card'						=> 'Novo Cartão',
	'card_number'					=> 'Número do Cartão',
	'card_holder_name'				=> 'Nome do Portador do Cartão',
	'cvv'							=> 'CVV',
	'card_declined'					=> 'Cartão Recusado',
	'card_added'					=> 'Cartão Adicionado',
	'card_removed'					=> 'Cartão Removido',
	'confirm_payment'				=> 'Confirmar Pagamento',
	'choose_card'					=> 'Escolha um cartão',
	'payment_creditcard_success'	=> 'Pagamento Realizado com Sucesso!',
	'value_cant_be_lower'			=> 'Valor não pode ser menor que',
	'confirm_create_billet'			=> 'Confirmar pagamento',
	'confirm_create_billet_msg'		=> 'Tem certeza que deseja gerar um boleto no valor de',
	'confirm'						=> 'Confirmar',
	'billet_success'				=> 'Boleto gerado com sucesso!',
	'billet_success_msg'			=> 'Para visualizar o boleto ',
	'confirm_create_pix'			=> 'Tem certeza que deseja pagar com pix no valor de',
	'pix_success'					=> 'Pix gerado com sucesso!',
	'pix_success_msg'				=> 'Código pix: ',
	'pix_info_1'					=> 'Pague com Pix e receba a confirmação na hora',
	'pix_info_2'					=> 'Abra o app da sua instituição financeira',
	'pix_info_3'					=> 'Faça um Pix lendo o QR Code ou copiando o código para pagamento',
	'pix_info_4'					=> 'Revise as informações, aguarde a confirmação e pronto!',
	'copy'							=> 'Copiar',
	'copied'						=> 'Copiado!',

	'monthNames' => array(
									'',
									'Janeiro - :y',
									'Fevereiro - :y',
									'Março - :y',
									'Abril - :y',
									'Maio - :y',
									'Junho - :y',
									'Julho - :y',
									'Agosto - :y',
									'Setembro - :y',
									'Outubro - :y',
									'Novembro - :y',
									'Dezembro - :y'
	),
	'vueMonthNames' => array(
		'Janeiro',
		'Fevereiro',
		'Março',
		'Abril',
		'Maio',
		'Junho',
		'Julho',
		'Agosto',
		'Setembro',
		'Outubro',
		'Novembro',
		'Dezembro'
	),
	
	'success_import' => 'Sucesso ao importar baixa de pagamentos!',
	'success_error' => 'Houve algum erro ao importar baixa de pagamentos!',
	'import_payment' => 'Importar baixa de pagamentos para prestadores',
	'select_file' => 'Selecionar arquivo',
	'exemple_file' => 'Baixar arquivo de exemplo',
	'import' => 'Importar',

	'consolidated_extract' => 'Extrato consolidado',
	'filter' => 'Filtros',
	'key_word' => 'Palavra chave',
	'type' => 'Tipo',
	'select' => 'Selecione...',
	'location' => 'Localização',
	'positive' => 'Positivo',
	'negative' => 'Negativo',
	'partner' => 'Parceiro',
	'ocurrency' => 'Ocorrência',
	'clients_notify' => 'Notificar clientes em débito',
	'title' => 'Título',
	'message' => 'Mensagem',
	'send_notification' => 'Enviar notificação',
	'ledger_id' => 'ID da carteira',
	'name' => 'Nome',
	'period_requests_count' => 'Solicitações no período',
	'total_ro_receive' => 'Total a receber',
	'actions' => 'Ações',
	'action_grid' => 'Ação',
	'debit_notification' => 'Notificar débito',
	'client_in_debit' => 'Cliente em débito',
	'down_report' => 'Baixar extrato',
	'corp' => 'Instituição',
	'op_deposit_in_account' => 'Depósito em conta (baixa de pagamento)',
	'delimiter' => 'Delimitador',
	'date_format' => 'Formato da data',
	'disabled_show_balance' => 'Visualização de saldo indisponível',
	'error_get_balance' => 'Erro ao tentar recuperar dados de saldo',
	'holder_error' => 'Nome incompleto ou não fornecido',
	'number_error' => 'Número incompleto ou não fornecido',
	'data_error' => 'Data incompleta ou não fornecida',
	'cvc_error' => 'Código de segurança incompleto ou não fornecido',
	'error_card' => 'Erro ao cadastrar o cartão, verifique os dados utilizados'
);