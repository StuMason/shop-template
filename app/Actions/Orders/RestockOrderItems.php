<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\ProductVariant;

class RestockOrderItems
{
    /**
     * Return an order's stock to the shelves (cancellation/abandonment).
     */
    public function handle(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            // Digital items never took stock.
            if ($item->is_digital) {
                continue;
            }

            if ($item->product_variant_id !== null) {
                ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->increment('stock', $item->quantity);
            }
        }
    }
}
