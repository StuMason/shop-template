<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Support\CartTotals;

class CalculateCartTotals
{
    public function handle(Cart $cart): CartTotals
    {
        $cart->loadMissing('items.variant');

        return new CartTotals(
            subtotal: $cart->items->sum(fn ($item): int => $item->lineTotal()),
            itemCount: (int) $cart->items->sum('quantity'),
        );
    }
}
