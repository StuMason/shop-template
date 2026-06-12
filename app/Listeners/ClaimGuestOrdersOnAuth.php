<?php

namespace App\Listeners;

use App\Actions\Orders\ClaimGuestOrders;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;

class ClaimGuestOrdersOnAuth
{
    public function __construct(private readonly ClaimGuestOrders $claimGuestOrders) {}

    /**
     * Guest orders join the account at the two moments ownership of the
     * email is fresh: verification (covers registration) and login (covers
     * guest purchases made after the account already existed — including
     * by AI agents, which always check out as guests).
     */
    public function handle(Login|Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->claimGuestOrders->handle($event->user);
        }
    }
}
