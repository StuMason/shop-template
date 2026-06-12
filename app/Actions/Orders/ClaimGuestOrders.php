<?php

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\User;

class ClaimGuestOrders
{
    /**
     * Attach guest orders placed with this user's email to their account.
     * Only runs for verified emails — the address must be proven theirs
     * before order history (and download links) become visible in-account.
     */
    public function handle(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        return Order::query()
            ->whereNull('user_id')
            ->where('email', strtolower($user->email))
            ->update(['user_id' => $user->id]);
    }
}
