<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfLowStock implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        Notification::send(
            User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->get(),
            new LowStockNotification($event->variant),
        );
    }
}
