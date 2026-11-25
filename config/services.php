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


    // Card payments. Keys live in .env, never in the database.
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    // Courier rates — used once Aramex account details are added.
    'aramex' => [
        'username' => env('ARAMEX_USERNAME'),
        'password' => env('ARAMEX_PASSWORD'),
        'account_number' => env('ARAMEX_ACCOUNT_NUMBER'),
        'account_pin' => env('ARAMEX_ACCOUNT_PIN'),
        'account_entity' => env('ARAMEX_ACCOUNT_ENTITY', 'DXB'),
        'account_country' => env('ARAMEX_ACCOUNT_COUNTRY', 'AE'),
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret' => env('RECAPTCHA_SECRET'),
        // Below this score a v3 token is treated as a bot (0.0 = certainly a bot).
        'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    ],

];
