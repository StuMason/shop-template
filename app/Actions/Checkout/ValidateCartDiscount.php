<?php

namespace App\Actions\Checkout;

use App\Actions\Cart\CalculateCartTotals;
use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;

class ValidateCartDiscount
{
    public function __construct(
        private readonly CalculateCartTotals $calculateCartTotals,
    ) {}

    /**
     * Surface a clear error when the basket's discount can't be used by this
     * customer (e.g. once-per-customer codes, now that we know the email) —
     * better than silently re-pricing the order.
     *
     * @throws InvalidDiscountException
     */
    public function handle(Cart $cart, string $email): void
    {
        $cart->loadMissing('discount');

        if ($cart->discount === null) {
            return;
        }

        $subtotal = $this->calculateCartTotals->handle($cart)->subtotal;

        $reason = $cart->discount->rejectionReason($subtotal, $email, $cart->user_id);

        if ($reason !== null) {
            throw new InvalidDiscountException(
                "{$cart->discount->code}: {$reason} Remove the code to continue.",
            );
        }
    }
}
