<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Notifications\OrderCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderCancelledNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order->loadMissing('user');

        if ($order->user !== null) {
            $order->user->notify(new OrderCancelledNotification($order));
        } else {
            Notification::route('mail', $order->email)
                ->notify(new OrderCancelledNotification($order));
        }
    }
}
