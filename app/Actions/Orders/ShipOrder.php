<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Models\Order;

class ShipOrder
{
    public function handle(Order $order): void
    {
        $order->transitionTo(OrderStatus::Shipped);
        $order->update(['shipped_at' => now()]);

        OrderShipped::dispatch($order);
    }
}
