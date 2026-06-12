<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

/**
 * The gateway's answer to "what is the true state of this payment?".
 * Always obtained by a server-side API lookback — never trusted from
 * redirects or webhook bodies.
 */
readonly class PaymentVerification
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public PaymentStatus $status,
        public ?string $gatewayTransactionId = null,
        public array $raw = [],
    ) {}
}
