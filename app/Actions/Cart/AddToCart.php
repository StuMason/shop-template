<?php

namespace App\Actions\Cart;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class AddToCart
{
    /**
     * Add a quantity of a variant to the cart, merging with any existing line.
     *
     * @throws InsufficientStockException
     */
    public function handle(Cart $cart, ProductVariant $variant, int $quantity = 1): CartItem
    {
        $product = $variant->product;

        abort_unless($product->isPublished(), 404);

        /** @var CartItem|null $existing */
        $existing = $cart->items()->firstWhere('product_variant_id', $variant->id);

        $targetQuantity = ($existing->quantity ?? 0) + $quantity;

        if (! $variant->product->is_digital && $variant->stock < $targetQuantity) {
            throw new InsufficientStockException($variant, $targetQuantity);
        }

        if ($existing !== null) {
            $existing->update(['quantity' => $targetQuantity]);

            return $existing;
        }

        /** @var CartItem */
        return $cart->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
        ]);
    }
}
