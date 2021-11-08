<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    Route::post('/libs/finance/postback/{transactionid}', 'GatewayPostbackController@postbackBillet');
    Route::get('/libs/finance/postback/{transactionid}', 'GatewayPostbackController@postbackBillet');
    
    // Postback do boleto e pix precisam ser os mesmos, pois tem gateway que nao permite alterar (E.g: juno)
    Route::post('/libs/finance/postback')->name('GatewayPostbackBillet');
    Route::post('/libs/finance/postback')->name('GatewayPostbackPix');
});