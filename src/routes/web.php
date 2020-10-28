<?php


// Rotas do painel 

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    
    // (View painel admin)
    Route::group(['prefix' => 'admin/libs', 'middleware' => 'auth.admin'], function () {
        Route::get('/example_vuejs', array('as' => 'webAdminFinance', 'uses' => 'FinanceController@getExampleVuejs'));
    });

});

// Rotas dos apps
Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {

    Route::group(['prefix' => 'libs/finance'], function () {

        Route::get('/example', 'FinanceController@getAppApiExample');
    
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