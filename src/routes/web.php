<?php


Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {

    // Rotas do app provider
    Route::group(['prefix' => 'libs/finance/provider', 'middleware' => 'auth.provider_api:api'], function () {
        Route::get('/profits', 'FinanceController@getProviderProfits');
        Route::get('/financial/summary/{id}', 'FinancialController@getFinancialSummaryByTypeAndDate');
        Route::get('/financial/provider_summary', 'FinanceController@getProviderSummaryByTypeAndDate');

        Route::get('/get_cards_and_balance', 'FinanceController@getCardsAndBalanceProvider');
        Route::get('/add_billet_balance', 'FinanceController@addBilletBalanceProvider');
        Route::get('/add_credit_card_balance', 'FinanceController@addCreditCardBalanceAppProvider');
        Route::get('/add_pix_balance', 'FinanceController@addPixBalanceProvider');
        Route::get('/retrieve_pix', 'FinanceController@retrievePix');
        Route::post('/add_credit_card', 'FinanceController@addCreditCardProvider');
        Route::get('/change_pix_payment_types', 'FinanceController@changePixPaymentTypes');
        Route::post('/change_pix_payment', 'FinanceController@changePixPayment');
    });

    // Rotas do app user
    Route::group(['prefix' => 'libs/finance/user', 'middleware' => 'auth.user_api:api'], function () {
        Route::get('/get_cards_and_balance', 'FinanceController@getCardsAndBalance');
        Route::get('/add_billet_balance', 'FinanceController@addBilletBalance');
        Route::get('/add_credit_card_balance', 'FinanceController@addCreditCardBalanceApp');
        Route::get('/add_pix_balance', 'FinanceController@addPixBalance');
        Route::get('/retrieve_pix', 'FinanceController@retrievePix');
        Route::post('/add_credit_card', 'FinanceController@addCreditCardUser');
        Route::get('/financial/user_summary', 'FinanceController@getProviderSummaryByTypeAndDate');
    });
    
    Route::group(['prefix' => 'libs/finance', 'middleware' => 'checkProviderOrUser'], function () {
        /**
		 * @OA\Post(path="/libs/finance/get_balance",
		 *      tags={"User", "Provider"},
		 *      operationId="getBalance",
		 *      description="Retorna o saldo do Provider/User",
		 *      @OA\Parameter(name="provider_id",
		 *          description="ID do provider",
		 *          in="query",
		 *          required=true,
		 *          @OA\Schema(type="integer")
		 *      ),
		 *      @OA\Parameter(name="user_id",
		 *          description="ID do passageiro",
		 *          in="query",
		 *          required=true,
		 *          @OA\Schema(type="integer")
		 *      ),
		 *      @OA\Parameter(name="token",
		 *          description="token de acesso a api provider/user",
		 *          in="query",
		 *          required=true,
		 *          @OA\Schema(type="string")
		 *      ),
		 *      @OA\Response(response="200",
		 *          description="Resource referral",
		 *          @OA\JsonContent(ref="#/components/schemas/BalanceResource")
		 *      ),
		 *      @OA\Response(
		 *          response="402",
		 *          description="Form request validation error. Invalid input."
		 *      ),
		 * )
		 */
        Route::post('/get_balance', 'FinanceController@getBalance')->name('libGetUserBallance');
    });

    // Rotas do painel web
    Route::group(['prefix' => 'admin/libs/finance', 'middleware' => 'auth.admin'], function () {
        Route::get('/provider_extract', array('as' => 'AdminProviderExtract', 'uses' => 'FinanceController@providerExtract'));
        Route::get('/provider_extract/filter', array('as' => 'AdminProviderExtractFilter', 'uses' => 'FinanceController@providerExtractFilter'));
        Route::post('/provider_extract/import_payments', array('as' => 'AdminImportPayments', 'uses' => 'FinanceController@importProviderPayments'));
        
        Route::get('/consolidated_extract', array('as' => 'AdminConsolidatedExtract', 'uses' => 'FinanceController@consolidatedExtract'));
        Route::get('/consolidated_extract/fetch', array('as' => 'AdminFetchExtract', 'uses' => 'FinanceController@consolidatedExtractFetch'));
        Route::get('/consolidated_extract/download', array('as' => 'AdminFetchExtract', 'uses' => 'FinanceController@downloadConsolidatedExtract'));

        Route::get('/user/{id}', array('as' => 'userAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/provider/{id}', array('as' => 'financeProviderAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/summary/{id}', array('as' => 'userAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/{type}/{id}/add-entry', array('as' => 'addFinancialEntry', 'uses' => 'FinancialController@addFinancialEntry'));
        Route::post('/{type}/{id}/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/{type}/{id}/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'FinancialController@createUserBankAccount'));
    });

    //Rotas do provider (web)
    Route::group(['prefix' => '/provider/libs/finance', 'middleware' => 'auth.provider'], function () {
        Route::get('/', array('as' => 'webProviderAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'providerAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'FinancialController@createUserBankAccount'));    
    
        //Pre-paid Payment Apis
        Route::get('/payment', array('as' => 'providerPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'providerRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'providerAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'providerDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_pix_balance', array('as' => 'providerAddPixBalance', 'uses' => 'FinanceController@addPixBalanceWeb'));       
        Route::post('/payment/add_credit_card', array('as' => 'providerAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
        Route::get('/payment/pix', array('as' => 'providerPixScreen', 'uses' => 'FinanceController@pixCheckout'));
        Route::get('/payment/pix/retrieve', 'FinanceController@retrievePix');

    });

    //Rotas do user (web)
    Route::group(['prefix' => '/user/libs/finance', 'middleware' => 'auth.user'], function () {
        Route::get('/', array('as' => 'webUserAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'userAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'FinancialController@createUserBankAccount'));    
        Route::get('/payment', array('as' => 'userPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'userRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'userAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/add_pix_balance', array('as' => 'userAddPixBalance', 'uses' => 'FinanceController@addPixBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'userDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_credit_card', array('as' => 'userAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
        Route::get('/payment/pix', array('as' => 'userPixScreen', 'uses' => 'FinanceController@pixCheckout'));
        Route::get('/payment/pix/retrieve', 'FinanceController@retrievePix');
    });

    //Rota de add cartão via painel
    Route::group(['prefix' => '/admin/libs/finance', 'middleware' => 'auth.admin'], function () {
        Route::post('/payment/add_credit_card', array('as' => 'adminAddCreditCardPanel', 'uses' => 'FinanceController@addCreditCardAdminUser'));
    });

    Route::group(['prefix' => '/corp/libs/finance' ,'middleware' => ['auth.corp_api', 'cors']], function (){
        Route::get('/financial-report', array('as' => 'corpAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'corpAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/financial-report/withdraw-request', array('as' => 'corpAddWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
    
        Route::get('/payment', array('as' => 'corpPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'corpRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'corpAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/add_pix_balance', array('as' => 'corpAddPixBalance', 'uses' => 'FinanceController@addPixBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'corpDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_credit_card', array('as' => 'corpAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
        Route::get('/payment/pix', array('as' => 'corpPixScreen', 'uses' => 'FinanceController@pixCheckout'));
        Route::get('/payment/pix/retrieve', 'FinanceController@retrievePix');
    });

});



/**
 * Rota para permitir utilizar arquivos de traducao do laravel (dessa lib) no vue js
 */
Route::get('/libs/finance/lang.trans/{file}', function () {

    app('debugbar')->disable();

    $fileNames = explode(',', Request::segment(4));
    $lang = config('app.locale');
    $files = array();
    foreach ($fileNames as $fileName) {
        array_push($files, __DIR__.'/../resources/lang/' . $lang . '/' . $fileName . '.php');
    }
    $strings = [];
    foreach ($files as $file) {
        $name = basename($file, '.php');
        $strings[$name] = require $file;
    }

    return response('window.lang = ' . json_encode($strings) . ';')
            ->header('Content-Type', 'text/javascript');
            
});