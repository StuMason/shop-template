<?php

namespace App\Payments;

/**
 * The result of starting a payment at a gateway: where to send the customer.
 */
readonly class PendingPayment
{
    public function __construct(
        public string $redirectUrl,
        public ?string $gatewayIntentId = null,
    ) {}
}
