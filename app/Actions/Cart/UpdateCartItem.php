<?php

namespace App\Actions\Cart;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;

class UpdateCartItem
{
    /**
     * Set a line's quantity; zero removes it.
     *
     * @throws InsufficientStockException
     */
    public function handle(Cart $cart, CartItem $item, int $quantity): void
    {
        abort_unless($item->cart_id === $cart->id, 404);

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        if ($item->variant->stock < $quantity) {
            throw new InsufficientStockException($item->variant, $quantity);
        }

        $item->update(['quantity' => $quantity]);
    }
}
