<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An Agentic Commerce Protocol checkout session: the agent-facing state
 * wrapper around a cart. The session id is the public handle ACP clients
 * hold; everything money-related lives on the cart and, after completion,
 * the order.
 *
 * @property string $id
 * @property int $cart_id
 * @property string $status
 * @property string|null $email
 * @property array<string, string|null>|null $shipping_address
 * @property int|null $shipping_method_id
 * @property int|null $order_id
 * @property string|null $idempotency_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['cart_id', 'status', 'email', 'shipping_address', 'shipping_method_id', 'order_id', 'idempotency_key'])]
class AgentCheckoutSession extends Model
{
    use HasUlids;

    /**
     * Mirrors the column default so unsaved models report their status.
     *
     * @var array<string, string>
     */
    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ShippingMethod, $this>
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
        ];
    }
}
