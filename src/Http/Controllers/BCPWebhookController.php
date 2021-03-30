<?php

namespace EmizorIpx\PaymentQrBcp\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use EmizorIpx\PrepagoBags\Models\PrepagoBagsPayment;
use EmizorIpx\PrepagoBags\Models\AccountPrepagoBags;
use EmizorIpx\PrepagoBags\Repository\AccountPrepagoBagsRepository;
use Carbon\Carbon;
use EmizorIpx\PrepagoBags\Traits\RechargeBagsTrait;
class BCPWebhookController extends Controller
{
    use RechargeBagsTrait;
    public function callback(){

        Log::debug('BCP-WH => ' . request()->getContent());
        $data = request()->only('CorrelationId', 'Id', 'ServiceCode', 'BusinessCode', 'IdQr', 'Eif', 'Account', 'Amount', 'Currency', 'Gloss', 'ReceiverAccount', 'ReceiverName', 
            'ReceiverDocument', 'ReceiverBank', 'ExpirationDate', 'ResponseCode', 'Status', 'Request', 'RequestDate', 'Response', 'ResponseDate', 'ResponseAch', 'ResponseAchDate', 
            'State', 'Description', 'GenerateType', 'Version', 'Collectors');

        
        $transaction_collector = collect($data['Collectors'])->firstWhere('Name', 'transaction') ?? null;
        $company_collector = collect($data['Collectors'])->firstWhere('Name', 'company') ?? null;

        \Log::debug("check value for tramsaction: " . $transaction_collector['Value']);
        \Log::debug("check value for company: " . $company_collector['Value']);

        $PrepagoBagsPayment = PrepagoBagsPayment::find($transaction_collector['Value']);

        if (!$PrepagoBagsPayment) {
            return response()->json([
                'State' => '002',
                'Message' => 'TRANSACCIÓN NO ENCONTRADA',
                'Data' => [
                    'Id' => 'E-' . $transaction_collector['Value'],
                ]
            ]);
        }
        
        $payment_data = [
            'paid_on' => Carbon::now(),
            'status_code' =>  "000",
            'extras' => [
                'bcp_request' => $data,
            ]
        ];
        
        try {
            
            $PrepagoBagsPayment->update($payment_data);
                              
            $this->rechargePrepagoBags($PrepagoBagsPayment->company_id, $PrepagoBagsPayment->prepago_bag_id);

            bitacora_info("AccountPrepagoBagService:recharge", "PrepagoBags comprado por :  " . $PrepagoBagsPayment->company_id . " con exito");

            return response()->json([
                'State' => '000',
                'Message' => 'COMPLETADO',
                'Data' => [
                    'Id' => $data['Id'],
                ]
            ]);


        } catch (ServiceException $ex) {
            Log::emergency("BCP-WH => File: " . $ex->getFile() . " Line: " . $ex->getLine() . " Message: " . $ex->getMessage());
            return response()->json([
                'State' => '001',
                'Message' => 'ERROR AL GUARDAR EL PAGO',
                'Data' => [
                    'Id' => 'E-' . $ex->getErrorCode(),
                ]
            ]);
        }
    }
}
