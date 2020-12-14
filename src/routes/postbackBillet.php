<?php

Route::group(array('namespace' => 'Codificar\Finance\Http\Controllers'), function () {
    //Sao duas rotas: uma eh a rota que possui controler, e que tem o ledgerid
    //A outra eh a rota que nomeia, para utilizar ela dentro do controller
    Route::post('/libs/finance/postback/{ledgerid}', 'GatewayPostbackController@postbackBillet');
    Route::post('/libs/finance/postback')->name('GatewayPostbackBillet');
});