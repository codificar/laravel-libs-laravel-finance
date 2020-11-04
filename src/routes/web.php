<?php


Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {

    // Rotas do app provider
    Route::group(['prefix' => 'libs/finance/provider', 'middleware' => 'auth.provider_api:api'], function () {
        Route::get('/profits', 'FinanceController@getProviderProfits');
        Route::get('/financial/summary/{id}', 'FinanceController@getFinancialSummaryByTypeAndDate');
    });

    // Rotas do painel web
    Route::group(['prefix' => 'admin/libs/finance/', 'middleware' => 'auth.admin'], function () {
        Route::get('/provider_extract', array('as' => 'AdminProviderExtract', 'uses' => 'FinanceController@providerExtract'));
        Route::get('/provider_extract/filter', array('as' => 'AdminProviderExtractFilter', 'uses' => 'FinanceController@providerExtractFilter'));
        
        Route::get('/user/{id}', array('as' => 'userAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/provider/{id}', array('as' => 'providerAccountStatement', 'uses' => 'FinancialController@getFinancialSummary'));
        Route::get('/provider/{id}/{start_date}/{end_date}', array('as' => 'providerAccountStatementAuto', 'uses' => 'FinancialController@getFinancialSummaryByDate'));
        Route::get('/user/{id}/summary', array('as' => 'userAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::get('/provider/{id}/summary', array('as' => 'providerAccountStatementByTypeAndDate', 'uses' => 'FinancialController@getFinancialSummaryByTypeAndDate'));
        Route::post('/{type}/{id}/add-entry', array('as' => 'addFinancialEntry', 'uses' => 'FinancialController@addFinancialEntry'));
        Route::post('/{type}/{id}/withdraw-request', array('as' => 'addWithDrawRequest', 'uses' => 'FinancialController@addWithDrawRequest'));
        Route::post('/{type}/{id}/create-user-bank-account', array('as' => 'createUserBankAccount', 'uses' => 'LedgerBankAccountController@createUserBankAccount'));
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