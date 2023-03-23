<?php

use EmizorIpx\PaymentQrBcp\Http\Middleware\BCPVerifyRequest;
use EmizorIpx\PaymentQrBcp\Http\Middleware\CallbackRedirect;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'api/bcp', 'namespace' => '\EmizorIpx\PaymentQrBcp\Http\Controllers'], function () {
    Route::post('callback', 'BCPWebhookController@callback')->middleware([BCPVerifyRequest::class, CallbackRedirect::class]);
});
