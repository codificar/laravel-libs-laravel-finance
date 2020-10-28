<?php

// Rotas do app provider
Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/finance/provider', 'middleware' => 'auth.provider_api:api'], function () {

        Route::get('/profits', 'FinanceController@getProviderProfits');
        Route::get('/financial/summary/{id}', 'FinanceController@getFinancialSummaryByTypeAndDate');
        
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