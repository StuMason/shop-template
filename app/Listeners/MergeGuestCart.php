<?php

namespace App\Listeners;

use App\Actions\Cart\MergeCarts;
use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Session;

class MergeGuestCart
{
    public function __construct(private readonly MergeCarts $mergeCarts) {}

    /**
     * When a user logs in with a guest basket in their session, fold it into
     * their account basket (or adopt it if they don't have one).
     */
    public function handle(Login $event): void
    {
        $token = Session::get('cart_token');

        if (! is_string($token) || ! $event->user instanceof User) {
            return;
        }

        $guestCart = Cart::query()
            ->where('token', $token)
            ->where('status', CartStatus::Active)
            ->whereNull('user_id')
            ->first();

        if ($guestCart === null) {
            return;
        }

        $userCart = Cart::query()
            ->where('user_id', $event->user->id)
            ->where('status', CartStatus::Active)
            ->latest()
            ->first();

        if ($userCart === null) {
            $guestCart->update(['user_id' => $event->user->id]);
        } else {
            $this->mergeCarts->handle($guestCart, $userCart);
        }

        Session::forget('cart_token');
    }
}
