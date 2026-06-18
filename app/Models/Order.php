<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $number
 * @property int|null $user_id
 * @property int|null $cart_id
 * @property string $email
 * @property OrderStatus $status
 * @property string $currency
 * @property int $subtotal
 * @property int $discount_total
 * @property string|null $discount_code
 * @property int $shipping_total
 * @property int $vat_total
 * @property int $total
 * @property string|null $shipping_method_name
 * @property string|null $carrier
 * @property string|null $tracking_number
 * @property int|null $printful_order_id
 * @property array<string, string|null> $shipping_address
 * @property array<string, string|null> $billing_address
 * @property string|null $customer_note
 * @property int $recovery_stage
 * @property Carbon|null $recovery_emailed_at
 * @property Carbon|null $review_requested_at
 * @property Carbon $placed_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'number', 'user_id', 'cart_id', 'email', 'status', 'currency', 'subtotal',
    'discount_total', 'discount_code',
    'shipping_total', 'vat_total', 'total', 'shipping_method_name', 'carrier',
    'tracking_number', 'printful_order_id', 'shipping_address', 'billing_address', 'customer_note',
    'placed_at', 'paid_at', 'shipped_at', 'delivered_at', 'cancelled_at',
    'recovery_stage', 'recovery_emailed_at', 'review_requested_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Customer-facing routes bind by order number, not id.
     */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * Guarded state transition; throws on an illegal move.
     */
    public function transitionTo(OrderStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Order {$this->number} cannot move from {$this->status->value} to {$target->value}.",
            );
        }

        $this->update(['status' => $target]);
    }

    public function formattedSubtotal(): string
    {
        return Money::format($this->subtotal, $this->currency);
    }

    public function formattedShippingTotal(): string
    {
        return $this->shipping_total === 0 ? 'Free' : Money::format($this->shipping_total, $this->currency);
    }

    public function formattedTotal(): string
    {
        return Money::format($this->total, $this->currency);
    }

    public function formattedVatTotal(): string
    {
        return Money::format($this->vat_total, $this->currency);
    }

    /**
     * True when every line is digital — fulfilment is instant downloads.
     */
    public function isFullyDigital(): bool
    {
        $this->loadMissing('items');

        return $this->items->isNotEmpty()
            && $this->items->every(fn (OrderItem $item): bool => $item->is_digital);
    }

    public function formattedDiscountTotal(): string
    {
        return Money::format($this->discount_total, $this->currency);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'recovery_emailed_at' => 'datetime',
            'review_requested_at' => 'datetime',
        ];
    }
}
