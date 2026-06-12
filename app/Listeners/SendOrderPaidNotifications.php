<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderPaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderPaidNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->loadMissing('items', 'user');

        if ($order->user !== null) {
            $order->user->notify(new OrderPaidNotification($order));
        } else {
            Notification::route('mail', $order->email)
                ->notify(new OrderPaidNotification($order));
        }

        Notification::send(
            User::query()->whereHas('roles', fn ($query) => $query->where('name', 'admin'))->get(),
            new NewOrderNotification($order),
        );
    }
}
