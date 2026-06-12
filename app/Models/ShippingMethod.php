<?php

namespace App\Models;

use App\Support\Money;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A flat-rate shipping option within a zone, with an optional free-shipping
 * threshold.
 *
 * @property int $id
 * @property int $shipping_zone_id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property int|null $free_over
 * @property bool $is_active
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'price', 'free_over', 'is_active', 'position'])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ShippingZone, $this>
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    /**
     * The shipping cost for a basket subtotal, honouring free_over.
     */
    public function priceFor(int $subtotal): int
    {
        if ($this->free_over !== null && $subtotal >= $this->free_over) {
            return 0;
        }

        return $this->price;
    }

    public function formattedPriceFor(int $subtotal): string
    {
        $price = $this->priceFor($subtotal);

        return $price === 0 ? 'Free' : Money::format($price);
    }

    /**
     * @param  Builder<ShippingMethod>  $query
     * @return Builder<ShippingMethod>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
