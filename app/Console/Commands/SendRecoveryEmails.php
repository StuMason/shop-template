<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\AbandonedCheckoutNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendRecoveryEmails extends Command
{
    protected $signature = 'shop:send-recovery-emails';

    protected $description = 'Nudge unpaid orders: stage 1 after an hour, stage 2 after a day';

    public function handle(): int
    {
        $sent = 0;

        // Stage 2 first so a long-stale order doesn't get both in one run.
        foreach ([2 => now()->subDay(), 1 => now()->subHour()] as $stage => $cutoff) {
            $orders = Order::query()
                ->where('status', OrderStatus::Pending)
                ->where('recovery_stage', '<', $stage)
                ->where('placed_at', '<=', $cutoff)
                ->get();

            foreach ($orders as $order) {
                Notification::route('mail', $order->email)
                    ->notify(new AbandonedCheckoutNotification($order, $stage));

                $order->update([
                    'recovery_stage' => $stage,
                    'recovery_emailed_at' => now(),
                ]);

                $sent++;
            }
        }

        $this->info("Sent {$sent} recovery emails.");

        return self::SUCCESS;
    }
}
