<?php

namespace App\Support\SupportDrafter;

use App\Models\Ticket;

interface SupportDrafter
{
    /**
     * Draft a staff reply for the ticket, or null when no draft is possible.
     */
    public function draft(Ticket $ticket): ?string;
}
