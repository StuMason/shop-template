<?php

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;

class MergeCarts
{
    /**
     * Merge a guest cart into a user cart (summing quantities, capped at
     * available stock) and retire the guest cart.
     */
    public function handle(Cart $guestCart, Cart $userCart): Cart
    {
        $guestCart->items()->with('variant')->get()->each(function ($guestItem) use ($userCart): void {
            $existing = $userCart->items()->firstWhere('product_variant_id', $guestItem->product_variant_id);

            $quantity = min(
                ($existing->quantity ?? 0) + $guestItem->quantity,
                max($guestItem->variant->stock, 0),
            );

            if ($quantity <= 0) {
                return;
            }

            if ($existing !== null) {
                $existing->update(['quantity' => $quantity]);
            } else {
                $userCart->items()->create([
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity' => $quantity,
                ]);
            }
        });

        $guestCart->update(['status' => CartStatus::Abandoned]);

        return $userCart;
    }
}
