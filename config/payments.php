<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be utilized
    | when initiating payments unless a specific one is requested.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'razorpay'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This option defines the default currency used for payments across
    | the application. INR is default.
    |
    */

    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'INR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Supported payment gateways in this application.
    |
    |
    */

    'supported_gateways' => [
        'razorpay',
        'cashfree',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can configure credentials for each gateway supported.
    |
    | 'enabled' controls whether a gateway may be selected by API callers.
    | Setting a gateway's 'enabled' flag to false causes checkout to reject
    | requests for that gateway with a clean 422 response, even if the driver
    | class exists (e.g. a Phase 2 shell).
    |
    */

    'gateways' => [

        'razorpay' => [
            'driver'         => \App\Services\Payments\Gateways\RazorpayGateway::class,
            'enabled'        => env('RAZORPAY_ENABLED', true),
            'key_id'         => env('RAZORPAY_KEY_ID'),
            'key_secret'     => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'mode'           => env('RAZORPAY_MODE', 'test'), // test or live
        ],

        'cashfree' => [
            'driver'         => \App\Services\Payments\Gateways\CashfreeGateway::class,
            'enabled'        => env('CASHFREE_ENABLED', false),  // Not available until Phase 3
            'client_id'      => env('CASHFREE_CLIENT_ID'),
            'client_secret'  => env('CASHFREE_CLIENT_SECRET'),
            'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
            'mode'           => env('CASHFREE_MODE', 'sandbox'), // sandbox or live
            'api_version'    => env('CASHFREE_API_VERSION', '2023-08-01'),
            'return_url'     => env('CASHFREE_RETURN_URL'),
            'notify_url'     => env('CASHFREE_NOTIFY_URL'),
        ],

    ],

];
