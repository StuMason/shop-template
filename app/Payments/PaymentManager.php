<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\FakeGateway;
use Illuminate\Support\Manager;

/**
 * Resolves the configured payment gateway. Add a provider by writing a
 * driver class and a create{Name}Driver method; select it with the
 * PAYMENT_GATEWAY env var.
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) config('payments.default', 'fake');
    }

    public function createFakeDriver(): PaymentGateway
    {
        return new FakeGateway;
    }
}
