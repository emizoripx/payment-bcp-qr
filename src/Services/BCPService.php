<?php

namespace EmizorIpx\PaymentQrBcp\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Ramsey\Uuid\Uuid;
use Throwable;

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

    public function __construct(array $config = [])
    {
        \Log::debug("INGRESANDO AO CONFIG  " , $config);
        if (empty($config)) {

            $this->host = config('paymentqr.bcp.host'). '/api/v2/Qr/Generated';
            $this->user = config('paymentqr.bcp.user');
            $this->password = config('paymentqr.bcp.password');
            $this->certificate_path = config('paymentqr.bcp.certificate_path');
            $this->certificate_password = config('paymentqr.bcp.certificate_password');
            $this->app_user_id = config('paymentqr.bcp.app_user_id');
            $this->service_code = config('paymentqr.bcp.service_code');
            $this->business_code = config('paymentqr.bcp.business_code');
            $this->public_token = config('paymentqr.bcp.public_token');
            $this->expiration = config('paymentqr.bcp.default_expiration');

        } else {

            $this->host = $config['host'];
            $this->user = $config['user'];
            $this->password = $config['password'];
            $this->certificate_path = storage_path($config['certificate_path']);
            $this->certificate_password = $config['certificate_password'];
            $this->app_user_id = $config['app_user_id'];
            $this->service_code = $config['service_code'];
            $this->business_code = $config['business_code'];
            $this->public_token = $config['public_token'];
            $this->expiration = $config['expiration'];
        }

        $this->client = new Client();

    }

    public function generate_qr($generatedId, $currency, $amount, $gloss, $expiration = null, $requestedBy = 'purchase-prepago-bags'){
        if (!$expiration) {
            $expiration = $this->expiration;
        }
        \Log::debug("ingresar a este punto depago");
        try {
            \Log::debug("ingresa a envio de payment ");
            $response = $this->client->request('POST', $this->host, [
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
                        ],
                        [
                            'name' => 'requested_by',
                            'parameter' => 'requestedBy',
                            'value' => $requestedBy,
                        ]
                    ],
                    'publicToken' => $this->public_token,
                ]
            ]);
            \Log::debug("response from payments " . $response->getBody());
        } catch (Throwable $ex) {
            \Log::debug("ERRORES IN BCP SERVICE ", [$ex->getFile(),$ex->getLine(),$ex->getMessage()]);
            $response = $ex->getResponse();
        }
        \Log::debug("the response of payment is  ", [$response]);
        return json_decode($response->getBody());
    }
}