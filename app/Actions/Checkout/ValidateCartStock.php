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
        $cart->loadMissing('items.variant');

        foreach ($cart->items as $item) {
            if ($item->variant->stock < $item->quantity) {
                throw new InsufficientStockException($item->variant, $item->quantity);
            }
        }
    }
}
