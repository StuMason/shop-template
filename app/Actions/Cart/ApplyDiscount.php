<?php

namespace App\Actions\Cart;

use App\Exceptions\InvalidDiscountException;
use App\Models\Cart;
use App\Models\Discount;

class ApplyDiscount
{
    public function __construct(
        private readonly CalculateCartTotals $calculateCartTotals,
    ) {}

    /**
     * Attach a discount code to the cart.
     *
     * @throws InvalidDiscountException
     */
    public function handle(Cart $cart, string $code): Discount
    {
        $discount = Discount::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();

        if ($discount === null) {
            throw new InvalidDiscountException("We don't recognise that code.");
        }

        $subtotal = $this->calculateCartTotals->handle($cart)->subtotal;

        if (($reason = $discount->rejectionReason($subtotal)) !== null) {
            throw new InvalidDiscountException($reason);
        }

        $cart->update(['discount_id' => $discount->id]);

        return $discount;
    }
}
