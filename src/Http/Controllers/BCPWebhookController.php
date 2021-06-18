<?php

namespace EmizorIpx\PaymentQrBcp\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use EmizorIpx\PrepagoBags\Models\PrepagoBagsPayment;
use Carbon\Carbon;
use EmizorIpx\PrepagoBags\Traits\RechargeBagsTrait;
use App\Models\PaymentHash;
use App\Models\CompanyGateway;
use App\Models\Invoice;
use App\Utils\Traits\MakesHash;
class BCPWebhookController extends Controller
{
    use RechargeBagsTrait;
    use MakesHash;
    public function callback()
    {
        $data = request()->only(
            'CorrelationId',
            'Id',
            'ServiceCode',
            'BusinessCode',
            'IdQr',
            'Eif',
            'Account',
            'Amount',
            'Currency',
            'Gloss',
            'ReceiverAccount',
            'ReceiverName',
            'ReceiverDocument',
            'ReceiverBank',
            'ExpirationDate',
            'ResponseCode',
            'Status',
            'Request',
            'RequestDate',
            'Response',
            'ResponseDate',
            'ResponseAch',
            'ResponseAchDate',
            'State',
            'Description',
            'GenerateType',
            'Version',
            'Collectors'
        );


        $transaction_collector = collect($data['Collectors'])->firstWhere('Name', 'transaction') ?? null;
        $requestedBy = collect($data['Collectors'])->firstWhere('Name', 'requested_by');
        \Log::debug("[PAYMENT-QR]check value for tramsaction: ", [$transaction_collector]);
        \Log::debug("[PAYMENT-QR]check value for requested by : ", [$requestedBy]);

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
                bitacora_info("PAYMENT-QR","PAYMENT HASH ". $transaction_collector['Value']);
                $payment_hash = PaymentHash::whereRaw('BINARY `hash`= ?', [$transaction_collector['Value']])->first();

                $invoice = $payment_hash->fee_invoice;
                if (!empty($payment_hash)) {

                    try {

                        $gateway = CompanyGateway::whereCompanyId($invoice->company_id)->whereGatewayKey('d14dd26a47cec830x11x5700bfb67b34')->first();
                        // bitacora_info("PAYMENT-QR", "RECEIVED in callback for invoice #" . $invoice->number . " payment for " . $invoice->amount . " in company_id = " . $invoice->company_id);
                        $gateway
                            ->driver($invoice->client)
                            ->setPaymentMethod(1000) // gatewattype qr = 1000
                            ->setPaymentHash($payment_hash)
                            ->processPaymentCallback("Pago con QR");

                        
                        
                        foreach ($payment_hash->data->invoices as $i) {
                            $invoice_id = $this->decodePrimaryKey($i->invoice_id);
                            $invoice = Invoice::find($invoice_id);
                            if ($invoice) {
                                $fel_invoice = $invoice->fel_invoice;
                                if ($invoice->fresh()->balance == 0 & !empty($fel_invoice)  && is_null($fel_invoice->cuf)) {
                                    $invoice->service()->emit();
                                    \Log::debug("EMITIENDO FACTURA $invoice->number");
                                    bitacora_info("PAYMENT-QR", $transaction_collector['Value'] . " - INVOICE $invoice->number was succesfully emitted");
                                }
                            }
                        }
                        
                    } catch (\Throwable $th) {
                        bitacora_error("PAYMENT-QR", " PAYMENT ERROR " . $th->getMessage());
                    }
                } else {
                    bitacora_error("PAYMENT-QR", " PAYMENT HASH " . $transaction_collector['Value'] . " WAS NOT FOUND");
                }
            }
            bitacora_info("PAYMENT-QR", "COMPLETADO");

            return response()->json([
                'State' => '000',
                'Message' => 'COMPLETADO',
                'Data' => [
                    'Id' => $data['Id'],
                ]
            ]);
        } catch (ServiceException $ex) {
            bitacora_error("PAYMENT-QR", "ServiceException ". "BCP-WH => File: " . $ex->getFile() . " Line: " . $ex->getLine() . " Message: " . $ex->getMessage());
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
