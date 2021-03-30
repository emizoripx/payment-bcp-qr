<?php

namespace EmizorIpx\PaymentQrBcp\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;


class BCPService {
    protected $host;
    protected $user;
    protected $password;
    protected $certificate_path;
    protected $certificate_password;
    protected $app_user_id;
    protected $service_code;
    protected $business_code;
    protected $public_token;

    protected $client;

    public function __construct()
    {
        $this->host = config('paymentqr.bcp.host');
        $this->user = config('paymentqr.bcp.user');
        $this->password = config('paymentqr.bcp.password');
        $this->certificate_path = config('paymentqr.bcp.certificate_path');
        $this->certificate_password = config('paymentqr.bcp.certificate_password');
        $this->app_user_id = config('paymentqr.bcp.app_user_id');
        $this->service_code = config('paymentqr.bcp.service_code');
        $this->business_code = config('paymentqr.bcp.business_code');
        $this->public_token = config('paymentqr.bcp.public_token');

        $this->client = new Client();
    }

    public function generate_qr($generatedId, $currency, $amount, $gloss, $expiration = null){
        if (!$expiration) {
            $expiration = config('paymentqr.bcp.default_expiration');
        }

        try {
            $response = $this->client->request('POST', $this->host . '/api/v2/Qr/Generated', [
                'auth' => [$this->user, $this->password],
                'cert' => [$this->certificate_path, $this->certificate_password],
                'headers' => [
                    'Correlation-Id' => Uuid::uuid4()->toString(),
                ],
                'json' => [
                    'appUserId' => $this->app_user_id,
                    'currency' => $currency,
                    'amount' => $amount,
                    'gloss' => $gloss,
                    'expiration' => $expiration,
                    'serviceCode' => $this->service_code,
                    'businessCode' => $this->business_code,
                    'collectors' => [
                        [
                            'name'=> 'transaction',
                            'parameter'=> 'generatedId',
                            'value'=> $generatedId,
                        ]
                    ],
                    'publicToken' => $this->public_token,
                ]
            ]);
        } catch (ClientException $ex) {
            $response = $ex->getResponse();
        }

        return json_decode($response->getBody());
    }
}