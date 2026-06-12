<?php

namespace App\Models;

use Database\Factories\TicketMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One message in a support thread.
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $user_id
 * @property bool $is_staff_reply
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'is_staff_reply', 'body'])]
class TicketMessage extends Model
{
    /** @use HasFactory<TicketMessageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
