<?php

namespace App\Jobs;

use App\Models\ProductVariant;
use App\Models\StockNotification;
use App\Notifications\BackInStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendBackInStockNotifications implements ShouldQueue
{
    use Queueable;

    public function __construct(public ProductVariant $variant) {}

    public function handle(): void
    {
        if ($this->variant->stock <= 0) {
            return;
        }

        StockNotification::query()
            ->where('product_variant_id', $this->variant->id)
            ->whereNull('notified_at')
            ->each(function (StockNotification $request): void {
                Notification::route('mail', $request->email)
                    ->notify(new BackInStockNotification($this->variant));

                $request->update(['notified_at' => now()]);
            });
    }
}
