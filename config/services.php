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
        'token' => env('POSTMARK_TOKEN'),
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

    'whatsapp' => [
        'enabled'             => env('WHATSAPP_ENABLED', false),
        'access_token'        => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id'     => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version'         => env('WHATSAPP_API_VERSION', 'v22.0'),
        'template_name'       => env('WHATSAPP_TEMPLATE_NAME', 'authentication'),
        'template_language'   => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en_US'),
        'template_has_button' => env('WHATSAPP_TEMPLATE_HAS_BUTTON', true),
        'template_has_variables' => env('WHATSAPP_TEMPLATE_HAS_VARIABLES', true),
        'template_variable_name' => env('WHATSAPP_TEMPLATE_VARIABLE_NAME'),
        'send_raw_text'       => env('WHATSAPP_SEND_RAW_TEXT', false),
        'timeout'             => env('WHATSAPP_TIMEOUT', 15),
        'retry_times'         => env('WHATSAPP_RETRY_TIMES', 2),
        'retry_sleep_ms'      => env('WHATSAPP_RETRY_SLEEP_MS', 200),
        'default_region'      => env('PHONE_DEFAULT_REGION', 'IN'),
    ],

];
