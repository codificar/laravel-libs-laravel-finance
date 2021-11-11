<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    Route::post('/libs/finance/postback/{transactionid}', 'GatewayPostbackController@postbackBillet');
    Route::get('/libs/finance/postback/{transactionid}', 'GatewayPostbackController@postbackBillet');
    
    Route::post('/libs/finance/postback')->name('GatewayPostbackBillet');
});