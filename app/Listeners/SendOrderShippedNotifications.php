<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Notifications\OrderShippedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderShippedNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderShipped $event): void
    {
        $order = $event->order->loadMissing('user');

        if ($order->user !== null) {
            $order->user->notify(new OrderShippedNotification($order));
        } else {
            Notification::route('mail', $order->email)
                ->notify(new OrderShippedNotification($order));
        }
    }
}
