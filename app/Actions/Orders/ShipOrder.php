<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Models\Order;

class ShipOrder
{
    public function handle(Order $order, ?string $trackingNumber = null, ?string $carrier = null): void
    {
        $order->transitionTo(OrderStatus::Shipped);
        $order->update([
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
        ]);

        OrderShipped::dispatch($order);
    }
}
