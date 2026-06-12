<?php

namespace App\Models;

use App\Enums\DiscountType;
use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A discount code. Value is whole percent for percentage discounts and pence
 * for fixed ones.
 *
 * @property int $id
 * @property string $code
 * @property DiscountType $type
 * @property int $value
 * @property int|null $min_subtotal
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $max_uses
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'max_uses', 'used_count', 'is_active'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    /**
     * Why this code can't be used right now, or null when it can.
     */
    public function rejectionReason(int $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'That code is no longer active.',
            $this->starts_at !== null && $this->starts_at->isFuture() => "That code isn't active yet.",
            $this->ends_at !== null && $this->ends_at->isPast() => 'That code has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'That code has been fully redeemed.',
            $this->min_subtotal !== null && $subtotal < $this->min_subtotal => 'Your basket doesn\'t meet the minimum spend for that code.',
            default => null,
        };
    }

    /**
     * The discount amount for a basket subtotal, capped at the subtotal.
     */
    public function amountFor(int $subtotal): int
    {
        if ($this->rejectionReason($subtotal) !== null) {
            return 0;
        }

        $amount = $this->type === DiscountType::Percent
            ? (int) round($subtotal * min($this->value, 100) / 100)
            : $this->value;

        return min($amount, $subtotal);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
