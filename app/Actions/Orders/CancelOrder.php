<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Events\OrderCancelled;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrder
{
    public function __construct(
        private readonly RestockOrderItems $restockOrderItems,
    ) {}

    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $order->transitionTo(OrderStatus::Cancelled);
            $order->update(['cancelled_at' => now()]);

            $this->restockOrderItems->handle($order);
        });

        OrderCancelled::dispatch($order);
    }
}
