<?php

namespace EmizorIpx\PaymentQrBcp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class BCPWebhookController extends Controller
{
    public function callback(){

        $data = request()->only('CorrelationId', 'Id', 'ServiceCode', 'BusinessCode', 'IdQr', 'Eif', 'Account', 'Amount', 'Currency', 'Gloss',  'ReceiverAccount','ReceiverName', 
        'ReceiverDocument', 'ReceiverBank', 'ExpirationDate', 'ResponseCode', 'Status', 'Request', 'RequestDate', 'Response', 'ResponseDate', 'ResponseAch', 'ResponseAchDate', 
        'State', 'Description', 'GenerateType', 'Version', 'Collectors');

        Log::debug('Bcp Response '. $data);
    }
}
