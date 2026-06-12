<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The merchant behind the checkout. Drivers live in app/Payments/Gateways
    | and are registered in App\Payments\PaymentManager. The fake driver
    | succeeds every payment and is intended for local development.
    |
    | Supported: "fake", "gocardless"
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Abandonment
    |--------------------------------------------------------------------------
    |
    | Pending payments older than this many minutes are verified one last
    | time and then abandoned (cancelling and restocking their order).
    |
    */

    'abandon_after_minutes' => env('PAYMENT_ABANDON_AFTER', 120),

];
