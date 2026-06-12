<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One payment attempt at a gateway. The idempotency_key doubles as the
 * external reference sent to the provider.
 *
 * @property int $id
 * @property int $order_id
 * @property string $gateway
 * @property PaymentStatus $status
 * @property int $amount
 * @property string $currency
 * @property string $idempotency_key
 * @property string|null $gateway_intent_id
 * @property string|null $gateway_transaction_id
 * @property array<string, mixed>|null $gateway_payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['gateway', 'status', 'amount', 'currency', 'idempotency_key', 'gateway_intent_id', 'gateway_transaction_id', 'gateway_payload'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'gateway_payload' => 'array',
        ];
    }
}
