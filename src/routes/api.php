<?php

use EmizorIpx\PaymentQrBcp\Http\Middleware\BCPVerifyRequest;

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'bcp', 'namespace' => '\EmizorIpx\PaymentQrBcp\http\Controllers'], function () {
    Route::post('callback', 'BCPWebhookController@callback')->middleware(BCPVerifyRequest::class);
});