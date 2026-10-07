<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'sonicpesa' => [
        'api_key' => env('SONICPESA_API_KEY'),
        'api_secret' => env('SONICPESA_API_SECRET'),
        'base_url' => rtrim(env('SONICPESA_BASE_URL', 'https://api.sonicpesa.com'), '/'),
        'webhook_secret' => env('SONICPESA_WEBHOOK_SECRET'),
        'sandbox' => env('SONICPESA_SANDBOX', true),
        'timeout' => (int) env('SONICPESA_TIMEOUT', 30),
    ],

];
