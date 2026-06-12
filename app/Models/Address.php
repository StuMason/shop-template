<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A saved address in a customer's address book. Orders snapshot addresses
 * as JSON, so deleting one never affects order history.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $label
 * @property string $name
 * @property string $line1
 * @property string|null $line2
 * @property string $city
 * @property string|null $county
 * @property string $postcode
 * @property string $country
 * @property string|null $phone
 * @property bool $is_default_shipping
 * @property bool $is_default_billing
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['label', 'name', 'line1', 'line2', 'city', 'county', 'postcode', 'country', 'phone', 'is_default_shipping', 'is_default_billing'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The snapshot stored on orders.
     *
     * @return array<string, string|null>
     */
    public function toSnapshot(): array
    {
        return [
            'name' => $this->name,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default_shipping' => 'boolean',
            'is_default_billing' => 'boolean',
        ];
    }
}
