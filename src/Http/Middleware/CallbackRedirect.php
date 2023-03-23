<?php

namespace EmizorIpx\PaymentQrBcp\Http\Middleware;

use Closure;
use GuzzleHttp\Client;

class CallbackRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \Log::debug('Callback Request >>>>>>>>>>>>>> ');
        $collectors = $request->only('Collectors');

        $redirect_data = collect($collectors['Collectors'])->firstWhere('Name', 'redirect') ?? null;

        \Log::debug('Redirect Data: ' . json_encode($redirect_data));
        if( $redirect_data && isset( $redirect_data['Value'] ) ){
            \Log::debug("PAYMENT QR CALLBACK - Redirecting to: " . $redirect_data['Value']);

            try {
                $data = $request->all();
                $headers = $request->header();

                $client = new Client();

                $response = $client->post($redirect_data['Value'], ['headers' => $headers, 'json' => $data]);

                \Log::debug('BCP PAYMENT QR CALLBACK: Response Redirect ' . json_encode($response));

                return response()->json([
                    'State' => '000',
                    'Message' => 'COMPLETADO'
                ]);
            } catch( \Throwable $ex ) {
                \Log::debug('BCP PAYMENT QR CALLBACK: Redirect error: ' . $ex->getMessage());

                return response()->json([
                    'State' => '002',
                    'Message' => 'ERROR - NOTIFICAR PAGO'
                ]);
            }
        }
        \Log::debug("NOT REDIRECT CALLBACK");

        return $next($request);
    }
}
