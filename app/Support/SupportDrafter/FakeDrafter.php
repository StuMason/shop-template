<?php

namespace App\Support\SupportDrafter;

use App\Models\Ticket;

class FakeDrafter implements SupportDrafter
{
    public function draft(Ticket $ticket): ?string
    {
        return "Hi {$ticket->user->name},\n\nThanks for getting in touch about \"{$ticket->subject}\" — drafted reply based on your order history.\n\nThe team";
    }
}
