<?php

return [
    'base_url' => env('SHIPROCKET_BASE_URL', 'https://apiv2.shiprocket.in/v1/external'),

    'email' => env('SHIPROCKET_EMAIL'),
    'password' => env('SHIPROCKET_PASSWORD'),

    'pickup_location' => env('SHIPROCKET_PICKUP_LOCATION'),

    'webhook_url' => env('SHIPROCKET_WEBHOOK_URL', env('APP_URL') . '/api/webhooks/logistics/tracking'),
    'webhook_token' => env('SHIPROCKET_WEBHOOK_TOKEN'),

    'timeout' => (int) env('SHIPROCKET_TIMEOUT', 30),
];
