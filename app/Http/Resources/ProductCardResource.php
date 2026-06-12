<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compact product payload for grids and carousels. Expects the product to be
 * loaded with the defaultVariant and media relations.
 *
 * @mixin Product
 */
class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->defaultVariant?->formattedPrice(),
            'compare_at_price' => $this->defaultVariant?->formattedCompareAtPrice(),
            'in_stock' => $this->is_digital || $this->variants->contains(fn ($variant): bool => $variant->isInStock()),
            'image' => $this->imagePayload('thumb'),
        ];
    }
}
