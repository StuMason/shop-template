<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderDelivered;
use App\Models\Order;

class DeliverOrder
{
    public function handle(Order $order): void
    {
        $order->transitionTo(OrderStatus::Delivered);
        $order->update(['delivered_at' => now()]);

        OrderDelivered::dispatch($order);
    }
}
