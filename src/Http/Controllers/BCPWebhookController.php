<?php

namespace EmizorIpx\PaymentQrBcp\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use EmizorIpx\PrepagoBags\Models\PrepagoBagsPayment;
use Carbon\Carbon;
use EmizorIpx\PrepagoBags\Traits\RechargeBagsTrait;
use App\Factory\PaymentFactory;
use App\Models\PaymentHash;

class BCPWebhookController extends Controller
{
    use RechargeBagsTrait;
    public function callback()
    {

        Log::debug('[PAYMENT-QR] BCP-WH => ' . request()->getContent());
        $data = request()->only('CorrelationId', 'Id', 'ServiceCode', 'BusinessCode', 'IdQr', 'Eif', 'Account', 'Amount', 'Currency', 'Gloss', 'ReceiverAccount', 'ReceiverName', 
            'ReceiverDocument', 'ReceiverBank', 'ExpirationDate', 'ResponseCode', 'Status', 'Request', 'RequestDate', 'Response', 'ResponseDate', 'ResponseAch', 'ResponseAchDate', 
            'State', 'Description', 'GenerateType', 'Version', 'Collectors');

        
        $transaction_collector = collect($data['Collectors'])->firstWhere('Name', 'transaction') ?? null;
        $requestedBy = collect($data['Collectors'])->firstWhere('Name', 'requested_by');
        \Log::debug("[PAYMENT-QR]check value for tramsaction: " , [$transaction_collector]);
        \Log::debug("[PAYMENT-QR]check value for requested by : " , [$requestedBy]);

        try {

            if ($requestedBy['Value'] == 'purchase-prepago-bags') {

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
                $PrepagoBagsPayment->update($payment_data);
                
                $PrepagoBagsPayment->company->rechargePrepagoBags($PrepagoBagsPayment->prepago_bag_id);
    
                bitacora_info("AccountPrepagoBagService:recharge", "PrepagoBags comprado por :  " . $PrepagoBagsPayment->company_id . " con exito");

            } else {

                $payment_hash = PaymentHash::whereRaw('BINARY `hash`= ?', [$transaction_collector['Value']])->first();

                if(!empty($payment_hash)) {

                    try {
                        
                        $invoice = $payment_hash->fee_invoice;
    
                        \Log::info("[PAYMENT-QR] RECEIVED in callback for invoice #". $invoice->number. " payment for ". $invoice->amount. " in company_id = " . $invoice->company_id);
                        
                        $payment = PaymentFactory::create($invoice->company_id, $invoice->user_id);
                        $payment->client_id = $invoice->client_id;
                        $payment->save();
                        
                        $payment_hash->payment_id = $payment->id;
                        $payment_hash->save();
                        
                        $payment = $payment->service()->applyCredits($payment_hash)->save();
                        
                        $fel_invoice = $invoice->fel_invoice;
                        
                        if (! empty($fel_invoice)  && ! is_null($fel_invoice->cuf) ) {
                            \Log::info("[PAYMENT-QR] emitting invoice for SIN");
                            $invoice->emit(true);
                            \Log::info("[PAYMENT-QR] INVOICE was succesfully emitted");
                        }
                        $payment->service()->sendEmail();
                        
                    } catch (\Throwable $th) {
                        \Log::error("[PAYMENT-QR] PAYMENT ERROR " . $th->getMessage());    
                    }
                } else {
                    \Log::error("[PAYMENT-QR] PAYMENT HASH " . $transaction_collector['Value']. " WAS NOT FOUND");
                }
            }


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
