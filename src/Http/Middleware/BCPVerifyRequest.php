<?php

namespace EmizorIpx\PaymentQrBcp\Http\Middleware;

use Closure;

class BCPVerifyRequest
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
        $authorization = $request->header('Authorization');

        if(!$authorization){
            return response()->json([
                'state' => '001',
                'message' => 'Authorization header missing'
            ]);

        }
        $authorization = explode(' ', $authorization);
        $authorization = $authorization[1];
        $authorization = base64_decode($authorization);
        $credentials = explode(':', $authorization);

        if(config('paymentqr.bcp.user') != $credentials[0] || config('paymentqr.bcp.public_token') != $credentials[1]){
            return response()->json([
                'state' => '002',
                'message' => 'Invalid credentials'
            ]);
        }

        return $next($request);
    }
}
