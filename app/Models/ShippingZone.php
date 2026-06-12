<?php

namespace App\Models;

use Database\Factories\ShippingZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A group of countries served by a set of shipping methods.
 *
 * @property int $id
 * @property string $name
 * @property array<int, string> $countries
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'countries', 'is_active'])]
class ShippingZone extends Model
{
    /** @use HasFactory<ShippingZoneFactory> */
    use HasFactory;

    /**
     * @return HasMany<ShippingMethod, $this>
     */
    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class)->orderBy('position');
    }

    public function servesCountry(string $country): bool
    {
        return in_array(strtoupper($country), array_map('strtoupper', $this->countries), true);
    }

    /**
     * @param  Builder<ShippingZone>  $query
     * @return Builder<ShippingZone>
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
            'countries' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
