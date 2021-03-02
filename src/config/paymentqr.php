<?php

return [
    'bcp' => [
        'host' => env('BCP_HOST',null),
        'user' => env('BCP_USER',null),
        'password' => env('BCP_PASSWORD',null),
        'public_token' => env('BCP_PUBLIC_TOKEN',null),
        'app_user_id' => env('BCP_APP_USER_ID',null),
        'business_code' => env('BCP_BUSINESS_CODE',null),
        'service_code' => env('BCP_SERVICE_CODE',null),
        'certificate_password' => env('BCP_CERTIFICATE_PASSWORD',null),
        'default_expiration' => env('BCP_DEFAULT_EXPIRATION',null),
        // El certificado de debe tener el siguiente formato certificate.pem
        'certificate_path' => env('CERTIFICATE_PATH,null')
    ]
];