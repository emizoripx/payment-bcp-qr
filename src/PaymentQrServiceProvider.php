<?php

namespace EmizorIpx\PaymentQrBcp;

use Illuminate\Support\ServiceProvider;

class PaymentQrServiceProvider extends ServiceProvider{
    public function boot(){

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
        $this->publishes([
            __DIR__.'/config/paymentqr.php' => config_path('paymentqr.php')
        ]);

        $this->mergeConfigFrom(__DIR__.'/config/paymentqr.php', 'paymentqr');
    }



    public function register()
    {
        
    }
}