<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use InvalidArgumentException;

class RecordRefund
{
    /**
     * Record a refund executed manually at the payment provider. When the
     * payment is fully refunded the order moves to refunded.
     */
    public function handle(Payment $payment, int $amount, ?string $reason, User $recordedBy): Refund
    {
        $alreadyRefunded = (int) $payment->refunds()->sum('amount');

        if ($amount <= 0 || $alreadyRefunded + $amount > $payment->amount) {
            throw new InvalidArgumentException('Refund amount exceeds the refundable balance.');
        }

        /** @var Refund $refund */
        $refund = $payment->refunds()->create([
            'amount' => $amount,
            'reason' => $reason,
            'recorded_by' => $recordedBy->id,
        ]);

        if ($alreadyRefunded + $amount === $payment->amount) {
            $payment->update(['status' => PaymentStatus::Refunded]);

            $order = $payment->order;

            if ($order->status->canTransitionTo(OrderStatus::Refunded)) {
                $order->transitionTo(OrderStatus::Refunded);
            }
        }

        return $refund;
    }
}
