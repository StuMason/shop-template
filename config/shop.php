<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shop Identity
    |--------------------------------------------------------------------------
    |
    | The defaults for the shop's public identity. Values stored via the admin
    | settings screen (shop_settings table) override these at runtime; these
    | are the fallbacks and the single place to brand a fresh clone.
    |
    */

    'name' => env('SHOP_NAME', env('APP_NAME', 'Shop')),

    'tagline' => env('SHOP_TAGLINE', 'Quality products, delivered.'),

    'description' => env('SHOP_DESCRIPTION', 'An online shop built on the shop-template starter.'),

    'contact_email' => env('SHOP_CONTACT_EMAIL', 'hello@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Commerce
    |--------------------------------------------------------------------------
    */

    'currency' => env('SHOP_CURRENCY', 'GBP'),

    'country' => env('SHOP_COUNTRY', 'GB'),

    'order_prefix' => env('SHOP_ORDER_PREFIX', 'ORD'),

    /*
    |--------------------------------------------------------------------------
    | VAT
    |--------------------------------------------------------------------------
    |
    | Prices are always entered and displayed VAT-inclusive (UK consumer
    | pricing). When the shop is VAT registered, orders snapshot the VAT
    | contained in the total and it is shown on confirmations. Products can
    | be flagged zero-rated individually (books, children's clothing, ...).
    |
    */

    'vat_registered' => env('SHOP_VAT_REGISTERED', false),

    'vat_number' => env('SHOP_VAT_NUMBER'),

    'vat_rate' => env('SHOP_VAT_RATE', 20.0),

    /*
    |--------------------------------------------------------------------------
    | SEO / Social
    |--------------------------------------------------------------------------
    |
    | og_image is a public path (or absolute URL) used as the default social
    | sharing image when a page does not provide its own.
    |
    */

    'og_image' => env('SHOP_OG_IMAGE'),

    'social' => [
        'x' => env('SHOP_SOCIAL_X'),
        'instagram' => env('SHOP_SOCIAL_INSTAGRAM'),
        'facebook' => env('SHOP_SOCIAL_FACEBOOK'),
    ],

];
