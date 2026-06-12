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

    'gocardless' => [
        'access_token' => env('GOCARDLESS_ACCESS_TOKEN'),
        'environment' => env('GOCARDLESS_ENVIRONMENT', 'sandbox'),
        'webhook_secret' => env('GOCARDLESS_WEBHOOK_SECRET'),
    ],

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

    'address_lookup' => [
        'driver' => env('ADDRESS_LOOKUP', 'none'),
    ],

    'google_places' => [
        'api_key' => env('GOOGLE_PLACES_API_KEY'),
    ],

    'acp' => [
        'api_key' => env('ACP_API_KEY'),
        'signature_secret' => env('ACP_SIGNATURE_SECRET'),
    ],

];
