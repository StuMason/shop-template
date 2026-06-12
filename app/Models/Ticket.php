<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A customer support thread.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property string $subject
 * @property TicketStatus $status
 * @property Carbon $last_message_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['order_id', 'subject', 'status', 'last_message_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<TicketMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status === TicketStatus::Open;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'last_message_at' => 'datetime',
        ];
    }
}
