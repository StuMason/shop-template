<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;

class MarkOrderPaid
{
    /**
     * Settle a payment and move its order to paid. Idempotent: replayed
     * webhooks and double return-visits are no-ops.
     */
    public function handle(Order $order, Payment $payment, ?string $gatewayTransactionId = null): void
    {
        if ($payment->status !== PaymentStatus::Succeeded) {
            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'gateway_transaction_id' => $gatewayTransactionId ?? $payment->gateway_transaction_id,
            ]);
        }

        if ($order->status !== OrderStatus::Pending) {
            return;
        }

        $order->transitionTo(OrderStatus::Paid);
        $order->update(['paid_at' => now()]);

        OrderPaid::dispatch($order);

        // Digital-only orders fulfil themselves: the paid email carries the
        // download links, so there is nothing left to ship.
        if ($order->isFullyDigital()) {
            $order->transitionTo(OrderStatus::Delivered);
            $order->update(['delivered_at' => now()]);
        }
    }
}
