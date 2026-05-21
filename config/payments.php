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
    */

    'gateways' => [

        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
            'mode' => env('RAZORPAY_MODE', 'test'), // test or live
        ],

        'cashfree' => [
            'client_id' => env('CASHFREE_CLIENT_ID'),
            'client_secret' => env('CASHFREE_CLIENT_SECRET'),
            'webhook_secret' => env('CASHFREE_WEBHOOK_SECRET'),
            'mode' => env('CASHFREE_MODE', 'test'), // test or live
        ],

    ],

];
