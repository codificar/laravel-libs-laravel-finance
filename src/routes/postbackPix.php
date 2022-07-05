<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    Route::post('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    Route::get('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    
    Route::post('/libs/finance/postback/pix', 'GatewayPostbackController@postbackPix')->name('GatewayPostbackPix');
    Route::get('/libs/finance/postback/pix', 'GatewayPostbackController@postbackPix');
});