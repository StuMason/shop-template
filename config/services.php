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

    'support_drafter' => [
        'driver' => env('SUPPORT_DRAFTER', 'none'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
    ],

    'printful' => [
        'token' => env('PRINTFUL_API_TOKEN'),
        'store_id' => env('PRINTFUL_STORE_ID'),
        // Drafts (false) let you review in the Printful dashboard before it
        // charges + prints; true submits paid orders for fulfilment.
        'auto_confirm' => env('PRINTFUL_AUTO_CONFIRM', false),
        'webhook_secret' => env('PRINTFUL_WEBHOOK_SECRET'),
    ],

    'x402' => [
        'enabled' => env('X402_ENABLED', false),
        'facilitator_url' => env('X402_FACILITATOR_URL', 'https://x402.org/facilitator'),
        'pay_to' => env('X402_PAY_TO'),
        'network' => env('X402_NETWORK', 'base'),
        'fx_rate' => env('X402_FX_RATE', 1.0),
    ],

];
