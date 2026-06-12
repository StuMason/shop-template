<?php

namespace App\Actions\Cart;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The single way every surface (web, MCP, future API) obtains a cart.
 * Authenticated users get their active cart; guests and agents are
 * identified by the cart's ulid token.
 */
class ResolveCart
{
    /**
     * Find an existing active cart, or null if neither identity matches.
     */
    public function find(?User $user, ?string $token): ?Cart
    {
        if ($user !== null) {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', CartStatus::Active)
                ->latest()
                ->first();

            if ($cart !== null) {
                return $cart;
            }
        }

        if ($token !== null && $token !== '') {
            return Cart::query()
                ->where('token', $token)
                ->where('status', CartStatus::Active)
                ->whereNull('user_id')
                ->first();
        }

        return null;
    }

    /**
     * Find or create the active cart for the given identity.
     */
    public function handle(?User $user, ?string $token): Cart
    {
        $cart = $this->find($user, $token);

        if ($cart !== null) {
            // Adopt a guest cart when its owner has since authenticated.
            if ($user !== null && $cart->user_id === null) {
                $cart->update(['user_id' => $user->id]);
            }

            return $cart;
        }

        return Cart::create([
            'token' => (string) Str::ulid(),
            'user_id' => $user?->id,
            'status' => CartStatus::Active,
            'expires_at' => now()->addDays(30),
        ]);
    }
}
