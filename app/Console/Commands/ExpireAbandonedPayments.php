<?php

namespace App\Console\Commands;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\PaymentManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:expire-abandoned')]
#[Description('Verify stale pending payments one last time, then abandon them and restock their orders')]
class ExpireAbandonedPayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PaymentManager $payments, MarkOrderPaid $markOrderPaid, CancelOrder $cancelOrder): int
    {
        $cutoff = now()->subMinutes((int) config('payments.abandon_after_minutes', 120));

        $stale = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<', $cutoff)
            ->with('order')
            ->get();

        foreach ($stale as $payment) {
            // One last lookback in case the webhook and return were both missed.
            $verification = $payments->driver($payment->gateway)->verify($payment);

            if ($verification->status === PaymentStatus::Succeeded) {
                $markOrderPaid->handle($payment->order, $payment, $verification->gatewayTransactionId);

                continue;
            }

            $payment->update(['status' => PaymentStatus::Abandoned]);

            if ($payment->order->status === OrderStatus::Pending) {
                $cancelOrder->handle($payment->order);
            }
        }

        $this->info("Processed {$stale->count()} stale payments.");

        return self::SUCCESS;
    }
}
