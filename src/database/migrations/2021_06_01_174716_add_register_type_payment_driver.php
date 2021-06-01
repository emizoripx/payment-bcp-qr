<?php

use App\Models\Gateway;
use App\Models\GatewayType;
use Illuminate\Database\Migrations\Migration;

class AddRegisterTypePaymentDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exist = GatewayType::whereId(1000)->first();
        if (!$exist) {
            $new = new GatewayType;
            $new->id = 1000;
            $new->alias = 'qr';
            $new->name = 'Qr';
            $new->save();
        }
        Gateway::where('id', 1000)->update(['default_gateway_type_id' => 1000]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
