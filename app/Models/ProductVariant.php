<?php

namespace App\Models;

use App\Jobs\SendBackInStockNotifications;
use App\Support\Money;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * The purchasable unit. Every product has at least one variant; a "simple"
 * product is a single default variant with no option values.
 *
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property int|null $printful_variant_id
 * @property int $price
 * @property int|null $compare_at_price
 * @property int $stock
 * @property int $low_stock_threshold
 * @property bool $is_default
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['sku', 'printful_variant_id', 'price', 'compare_at_price', 'stock', 'low_stock_threshold', 'is_default', 'position'])]
class ProductVariant extends Model
{
    /**
     * Restocks fan out the "it's back" emails. Observed here so it works
     * whether stock changes via admin, an import, or a cancellation restock.
     */
    protected static function booted(): void
    {
        static::updated(function (ProductVariant $variant): void {
            if ($variant->wasChanged('stock')
                && (int) $variant->getOriginal('stock') <= 0
                && $variant->stock > 0
            ) {
                SendBackInStockNotifications::dispatch($variant);
            }
        });
    }

    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class);
    }

    /**
     * The variant's option values joined for display, e.g. "Large / Red".
     * Falls back to the product name for simple products.
     */
    public function displayName(): string
    {
        $values = $this->optionValues->pluck('value')->implode(' / ');

        return $values !== '' ? $values : 'Default';
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->low_stock_threshold;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price);
    }

    public function formattedCompareAtPrice(): ?string
    {
        return $this->compare_at_price !== null ? Money::format($this->compare_at_price) : null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
