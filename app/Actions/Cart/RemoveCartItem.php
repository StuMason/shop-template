<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;

class RemoveCartItem
{
    /**
     * Remove a line from the cart.
     */
    public function handle(Cart $cart, CartItem $item): void
    {
        abort_unless($item->cart_id === $cart->id, 404);

        $item->delete();
    }
}
