<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    Route::post('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    Route::get('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    
    Route::post('/libs/finance/postback/pix/ipag', 'GatewayPostbackController@postbackPixIpag');
    Route::post('/libs/finance/postback/pix')->name('GatewayPostbackPix');
});