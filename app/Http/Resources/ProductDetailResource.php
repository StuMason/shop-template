<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product payload for the detail page. Expects options.values,
 * variants.optionValues, categories and media to be eager loaded.
 *
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
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
            'is_digital' => $this->is_digital,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'images' => $this->galleryPayload('large'),
            'options' => $this->options->map(fn (ProductOption $option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->all(),
            ])->all(),
            'variants' => $this->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->formattedPrice(),
                'price_amount' => number_format($variant->price / 100, 2, '.', ''),
                'compare_at_price' => $variant->formattedCompareAtPrice(),
                'in_stock' => $this->is_digital || $variant->isInStock(),
                'low_stock' => ! $this->is_digital && $variant->isLowStock(),
                'is_default' => $variant->is_default,
                'option_value_ids' => $variant->optionValues->pluck('id')->all(),
            ])->all(),
            'categories' => $this->categories->map(fn ($category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
            ])->all(),
        ];
    }
}
