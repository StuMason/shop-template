<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
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
 * @property bool $once_per_customer
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['code', 'type', 'value', 'min_subtotal', 'starts_at', 'ends_at', 'max_uses', 'once_per_customer', 'used_count', 'is_active'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory;

    /**
     * Why this code can't be used right now, or null when it can. The
     * once-per-customer check needs an identity: it runs at apply time for
     * logged-in users and at checkout time (email known) for guests.
     */
    public function rejectionReason(int $subtotal, ?string $email = null, ?int $userId = null): ?string
    {
        return match (true) {
            ! $this->is_active => 'That code is no longer active.',
            $this->starts_at !== null && $this->starts_at->isFuture() => "That code isn't active yet.",
            $this->ends_at !== null && $this->ends_at->isPast() => 'That code has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'That code has been fully redeemed.',
            $this->min_subtotal !== null && $subtotal < $this->min_subtotal => 'Your basket doesn\'t meet the minimum spend for that code.',
            $this->once_per_customer && $this->hasBeenUsedBy($email, $userId) => "You've already used that code.",
            default => null,
        };
    }

    /**
     * Whether this customer (by email or account) has already redeemed the
     * code on a live order. Cancelled orders don't count against them.
     */
    public function hasBeenUsedBy(?string $email, ?int $userId): bool
    {
        if ($email === null && $userId === null) {
            return false;
        }

        return Order::query()
            ->where('discount_code', $this->code)
            ->whereNot('status', OrderStatus::Cancelled)
            ->where(function ($query) use ($email, $userId): void {
                $query->when($email !== null, fn ($query) => $query->where('email', strtolower($email)));

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->exists();
    }

    /**
     * The discount amount for a basket subtotal, capped at the subtotal.
     */
    public function amountFor(int $subtotal, ?string $email = null, ?int $userId = null): int
    {
        if ($this->rejectionReason($subtotal, $email, $userId) !== null) {
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
            'once_per_customer' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
