<?php

namespace App\Actions\Checkout;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentManager;
use App\Payments\PendingPayment;
use Illuminate\Support\Str;

/**
 * Creates a payment row and starts the payment at the configured gateway,
 * returning the URL the customer must be sent to. Reuses an unsettled
 * pending payment so refreshing the pay page doesn't create duplicates.
 */
class StartPayment
{
    public function __construct(
        private readonly PaymentManager $payments,
    ) {}

    public function handle(Order $order): PendingPayment
    {
        /** @var Payment $payment */
        $payment = $order->payments()
            ->where('status', PaymentStatus::Pending)
            ->whereNull('gateway_intent_id')
            ->first() ?? $order->payments()->create([
                'gateway' => $this->payments->getDefaultDriver(),
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
                'currency' => $order->currency,
                'idempotency_key' => (string) Str::ulid(),
            ]);

        $pending = $this->payments->driver()->createPayment(
            $payment,
            $order,
            returnUrl: route('checkout.return', $payment),
            webhookUrl: route('webhooks.payments'),
        );

        $payment->update(['gateway_intent_id' => $pending->gatewayIntentId]);

        return $pending;
    }
}
