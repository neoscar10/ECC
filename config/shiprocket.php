<?php

return [
    'base_url' => env('SHIPROCKET_BASE_URL', 'https://apiv2.shiprocket.in/v1/external'),

    'email' => env('SHIPROCKET_EMAIL'),
    'password' => env('SHIPROCKET_PASSWORD'),

    'pickup_location' => env('SHIPROCKET_PICKUP_LOCATION'),
    'pickup_pincode' => env('SHIPROCKET_PICKUP_PINCODE'),

    'webhook_url' => env('SHIPROCKET_WEBHOOK_URL', env('APP_URL') . '/api/webhooks/logistics/tracking'),
    'webhook_token' => env('SHIPROCKET_WEBHOOK_TOKEN'),

    'timeout' => (int) env('SHIPROCKET_TIMEOUT', 30),

    'cache_token_key' => env('SHIPROCKET_CACHE_TOKEN_KEY', 'shiprocket_access_token'),
    'token_ttl_minutes' => (int) env('SHIPROCKET_TOKEN_TTL_MINUTES', 13800),

    'auto_select_courier_by' => env('SHIPROCKET_AUTO_SELECT_COURIER_BY', 'rating'),
    'rate_quote_ttl_minutes' => (int) env('SHIPROCKET_RATE_QUOTE_TTL_MINUTES', 60),
];
