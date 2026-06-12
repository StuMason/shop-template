<?php

namespace App\Actions\Checkout;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;

class ValidateCartStock
{
    /**
     * Ensure every line in the cart is still satisfiable.
     *
     * @throws InsufficientStockException
     */
    public function handle(Cart $cart): void
    {
        $cart->loadMissing('items.variant.product');

        foreach ($cart->items as $item) {
            // Digital products have no stock to run out of.
            if ($item->variant->product->is_digital) {
                continue;
            }

            if ($item->variant->stock < $item->quantity) {
                throw new InsufficientStockException($item->variant, $item->quantity);
            }
        }
    }
}
