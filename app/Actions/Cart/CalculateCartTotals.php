<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Support\CartTotals;

class CalculateCartTotals
{
    public function handle(Cart $cart): CartTotals
    {
        $cart->loadMissing('items.variant', 'discount');

        $subtotal = (int) $cart->items->sum(fn ($item): int => $item->lineTotal());

        // A discount that has become invalid (expired, fully redeemed, below
        // minimum spend) silently contributes nothing rather than blocking.
        $discount = $cart->discount?->amountFor($subtotal, $cart->user?->email, $cart->user_id) ?? 0;

        return new CartTotals(
            subtotal: $subtotal,
            itemCount: (int) $cart->items->sum('quantity'),
            discount: $discount,
            discountCode: $discount > 0 ? $cart->discount->code : null,
        );
    }
}
