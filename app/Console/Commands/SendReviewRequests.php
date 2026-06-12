<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\ReviewRequestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendReviewRequests extends Command
{
    protected $signature = 'shop:send-review-requests';

    protected $description = 'Ask for a review a few days after delivery';

    public function handle(): int
    {
        $orders = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereNull('review_requested_at')
            ->where('delivered_at', '<=', now()->subDays(3))
            ->with('items.variant.product')
            ->get();

        foreach ($orders as $order) {
            Notification::route('mail', $order->email)
                ->notify(new ReviewRequestNotification($order));

            $order->update(['review_requested_at' => now()]);
        }

        $this->info("Requested reviews for {$orders->count()} orders.");

        return self::SUCCESS;
    }
}
