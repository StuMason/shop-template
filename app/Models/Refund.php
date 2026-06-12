<?php

namespace App\Models;

use Database\Factories\RefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A recorded refund against a payment. Executed manually at the provider in
 * v1; this row is the shop's book-keeping.
 *
 * @property int $id
 * @property int $payment_id
 * @property int $amount
 * @property string|null $reason
 * @property string|null $gateway_refund_id
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['amount', 'reason', 'gateway_refund_id', 'recorded_by'])]
class Refund extends Model
{
    /** @use HasFactory<RefundFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
