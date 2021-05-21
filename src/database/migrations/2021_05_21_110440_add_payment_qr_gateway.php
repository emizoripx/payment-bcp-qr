<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Gateway;
use App\Utils\Ninja;
use Illuminate\Database\Eloquent\Model;

class AddPaymentQrGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Model::unguard();

        $gateway = [
            'id' => 1000,
            'name' => 'BCP QR',
            'provider' => 'Qr',
            'sort_order' => 1000,
            'key' => 'd14dd26a47cec830x11x5700bfb67b34',
            'fields' => '{ "host" : "", "user" :"", "password":"", "public_token" :"", "app_user_id" :"", "business_code" :"", "service_code" :"", "certificate_password":"", "expiration":"0/00:15", "certificate_path":""}'
        ];

        Gateway::create($gateway);

        Gateway::where('id', 1000)->update(['visible' => 1]);

        Gateway::where('id', '!=', 1000)->update(['visible' => 0]);

        Artisan::call("emizor:warm-cache");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
