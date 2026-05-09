<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External API Endpoints
    |--------------------------------------------------------------------------
    |
    | Keep external API host names in configuration so gateways do not hardcode
    | provider URLs and tests can swap them safely.
    |
    */

    'mercado_pago' => [
        'base_url' => env('MERCADO_PAGO_API_BASE_URL', 'https://api.mercadopago.com'),
    ],

];
