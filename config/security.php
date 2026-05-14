<?php

return [
    'rate_limits' => [
        'public_cart_mutations_per_minute' => (int) env('PUBLIC_CART_MUTATIONS_PER_MINUTE', 60),
        'public_order_creations_per_minute' => (int) env('PUBLIC_ORDER_CREATIONS_PER_MINUTE', 10),
        'public_order_creations_per_session_minute' => (int) env('PUBLIC_ORDER_CREATIONS_PER_SESSION_MINUTE', 3),
        'mercado_pago_webhooks_per_minute' => (int) env('MERCADO_PAGO_WEBHOOKS_PER_MINUTE', 60),
    ],

    'mercado_pago_webhook_max_bytes' => (int) env('MERCADO_PAGO_WEBHOOK_MAX_BYTES', 65536),
    'mercado_pago_webhook_retention_days' => (int) env('MERCADO_PAGO_WEBHOOK_RETENTION_DAYS', 90),

    'admin_mfa' => [
        'required' => (bool) env('ADMIN_MFA_REQUIRED', false),
        'challenge_ttl_seconds' => (int) env('ADMIN_MFA_CHALLENGE_TTL_SECONDS', 300),
        'max_attempts' => (int) env('ADMIN_MFA_MAX_ATTEMPTS', 5),
    ],
];
