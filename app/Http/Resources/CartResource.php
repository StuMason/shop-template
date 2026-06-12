<?php

namespace App\Http\Resources;

use App\Actions\Cart\CalculateCartTotals;
use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Basket payload shared with the storefront (drawer + basket page).
 *
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing(['items.variant.product.media', 'items.variant.optionValues']);

        $totals = app(CalculateCartTotals::class)->handle($this->resource);

        return [
            'items' => $this->items->map(fn (CartItem $item): array => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'line_total' => Money::format($item->lineTotal()),
                'max_quantity' => max($item->variant->stock, 0),
                'variant' => [
                    'id' => $item->variant->id,
                    'sku' => $item->variant->sku,
                    'price' => $item->variant->formattedPrice(),
                    'options' => $item->variant->optionValues->pluck('value')->implode(' / '),
                ],
                'product' => [
                    'name' => $item->variant->product->name,
                    'slug' => $item->variant->product->slug,
                    'image' => $item->variant->product->imagePayload('thumb'),
                ],
            ])->values()->all(),
            ...$totals->toArray(),
        ];
    }
}
