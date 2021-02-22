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
        Route::post('/add_credit_card', 'FinanceController@addCreditCardProvider');
    });

    // Rotas do app user
    Route::group(['prefix' => 'libs/finance/user', 'middleware' => 'auth.user_api:api'], function () {
        Route::get('/get_cards_and_balance', 'FinanceController@getCardsAndBalance');
        Route::get('/add_billet_balance', 'FinanceController@addBilletBalance');
        Route::get('/add_credit_card_balance', 'FinanceController@addCreditCardBalanceApp');
        Route::post('/add_credit_card', 'FinanceController@addCreditCardUser');
    });

    // Rotas do painel web
    Route::group(['prefix' => 'admin/libs/finance', 'middleware' => 'auth.admin'], function () {
        Route::get('/provider_extract', array('as' => 'AdminProviderExtract', 'uses' => 'FinanceController@providerExtract'));
        Route::get('/provider_extract/filter', array('as' => 'AdminProviderExtractFilter', 'uses' => 'FinanceController@providerExtractFilter'));
        
        Route::get('/user/{id}', array('as' => 'userAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/provider/{id}', array('as' => 'financeProviderAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/summary/{id}', array('as' => 'userAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/{type}/{id}/add-entry', array('as' => 'addFinancialEntry', 'uses' => 'FinancialController@addFinancialEntry'));
        Route::post('/{type}/{id}/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/{type}/{id}/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'LedgerBankAccountController@createUserBankAccount'));
    });

    //Rotas do provider (web)
    Route::group(['prefix' => '/provider/libs/finance', 'middleware' => 'auth.provider'], function () {
        Route::get('/', array('as' => 'webProviderAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'providerAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'LedgerBankAccountController@createUserBankAccount'));    
    
        //Pre-paid Payment Apis
        Route::get('/payment', array('as' => 'providerPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'providerRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'providerAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'providerDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_credit_card', array('as' => 'providerAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
    });

    //Rotas do user (web)
    Route::group(['prefix' => '/user/libs/finance', 'middleware' => 'auth.user'], function () {
        Route::get('/', array('as' => 'webUserAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'userAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'LedgerBankAccountController@createUserBankAccount'));    
        Route::get('/payment', array('as' => 'userPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'userRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'userAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'userDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_credit_card', array('as' => 'userAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
    });

    Route::group(['prefix' => '/corp/libs/finance' ,'middleware' => ['auth.corp_api', 'cors']], function (){
        Route::get('/financial-report', array('as' => 'corpAccountStatement', 'uses' => 'FinancialController@userProviderCheckingAccount'));
        Route::get('/summary/{id}', array('as' => 'corpAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/financial-report/withdraw-request', array('as' => 'corpAddWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
    
        Route::get('/payment', array('as' => 'corpPayment', 'uses' => 'FinanceController@userPayment'));
        Route::post('/payment/add_credit_card_balance', array('as' => 'corpRequestPayment', 'uses' => 'FinanceController@addCreditCardBalanceWeb'));
        Route::post('/payment/add_billet_balance', array('as' => 'corpAddNewBillet', 'uses' => 'FinanceController@addBilletBalanceWeb'));
        Route::post('/payment/deleteusercard', array('as' => 'corpDeleteUserCard', 'uses' => 'FinanceController@deleteUserCard'));
        Route::post('/payment/add_credit_card', array('as' => 'corpAddCreditCard', 'uses' => 'FinanceController@addCreditCard'));
    });

});



/**
 * Rota para permitir utilizar arquivos de traducao do laravel (dessa lib) no vue js
 */
Route::get('/libs/finance/lang.trans/{file}', function () {
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

    header('Content-Type: text/javascript');
    return ('window.lang = ' . json_encode($strings) . ';');
    exit();
})->name('assets.lang');