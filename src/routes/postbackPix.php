<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    Route::post('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    Route::gets('/libs/finance/postback/pix/{transactionid}', 'GatewayPostbackController@postbackPix');
    
    Route::post('/libs/finance/postback/pix')->name('GatewayPostbackPix');
});