<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Support\SupportDrafter\SupportDrafterManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DraftTicketReply implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function handle(SupportDrafterManager $drafter): void
    {
        if (! $drafter->enabled()) {
            return;
        }

        $draft = $drafter->driver()->draft($this->ticket);

        if ($draft !== null) {
            $this->ticket->update(['draft_reply' => $draft]);
        }
    }
}
