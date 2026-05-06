<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'internal_orders' => [
        'email' => env('INTERNAL_ORDERS_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
    ],

    'mercado_pago' => [
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
        'credential_mode' => env('MERCADO_PAGO_MODE', env('MERCADO_PAGO_ENV', 'test')),
        'checkout_url_strategy' => env('MERCADO_PAGO_CHECKOUT_URL_STRATEGY', 'init_point'),
        'statement_descriptor' => env('MERCADO_PAGO_STATEMENT_DESCRIPTOR', 'GRSHOP'),
        'notification_url' => env('MERCADO_PAGO_NOTIFICATION_URL'),
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
        'webhook_signature_tolerance_seconds' => env('MERCADO_PAGO_WEBHOOK_SIGNATURE_TOLERANCE_SECONDS', 0),
        'pending_checkout_reuse_minutes' => env('MERCADO_PAGO_PENDING_CHECKOUT_REUSE_MINUTES', 30),
    ],

];
