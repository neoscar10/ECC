<?php

return [
    'delivery_mode' => env('OTP_DELIVERY_MODE', 'meta_whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | OTP Purpose Configuration
    |--------------------------------------------------------------------------
    |
    | Define expiry (TTL in minutes), rate limits (requests per window),
    | resend cooldowns (seconds), max verification attempts, and template names.
    |
    */

    'purposes' => [
        'signup' => [
            'ttl_minutes' => 5,
            'rate_limit' => [
                'max_attempts' => 5,
                'decay_seconds' => 900, // 15 minutes
            ],
            'resend_cooldown' => 60, // seconds
            'max_verify_attempts' => 5,
            'whatsapp_template' => env('WHATSAPP_SIGNUP_TEMPLATE', 'signup_otp'),
        ],
        'login' => [
            'ttl_minutes' => 5,
            'rate_limit' => [
                'max_attempts' => 10,
                'decay_seconds' => 900, // 15 minutes
            ],
            'resend_cooldown' => 60, // seconds
            'max_verify_attempts' => 5,
            'whatsapp_template' => env('WHATSAPP_LOGIN_TEMPLATE', 'login_otp'),
        ],
        'password_reset' => [
            'ttl_minutes' => 10,
            'rate_limit' => [
                'max_attempts' => 3,
                'decay_seconds' => 1800, // 30 minutes
            ],
            'resend_cooldown' => 120, // seconds
            'max_verify_attempts' => 5,
            'whatsapp_template' => env('WHATSAPP_PASSWORD_RESET_TEMPLATE', 'password_reset_otp'),
        ],
        'phone_change' => [
            'ttl_minutes' => 5,
            'rate_limit' => [
                'max_attempts' => 5,
                'decay_seconds' => 1800, // 30 minutes
            ],
            'resend_cooldown' => 60, // seconds
            'max_verify_attempts' => 5,
            'whatsapp_template' => env('WHATSAPP_PHONE_CHANGE_TEMPLATE', 'phone_change_otp'),
        ],
    ],
];
