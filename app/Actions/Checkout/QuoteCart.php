<?php

namespace App\Actions\Checkout;

use App\Actions\Cart\CalculateCartTotals;
use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Support\ShopSettings;
use App\Support\Vat;

/**
 * Pre-order totals quote, mirroring the maths CreateOrderFromCart commits:
 * free-shipping thresholds and VAT are both computed on the discounted
 * subtotal. Used by ACP sessions to show agents live totals.
 */
class QuoteCart
{
    public function __construct(
        private readonly CalculateCartTotals $calculateCartTotals,
        private readonly ShopSettings $settings,
    ) {}

    /**
     * @return array{subtotal: int, discount: int, discount_code: string|null, shipping: int, vat: int, total: int, currency: string}
     */
    public function handle(Cart $cart, ?ShippingMethod $shippingMethod): array
    {
        $totals = $this->calculateCartTotals->handle($cart);
        $discountedSubtotal = $totals->total();
        $shipping = $shippingMethod?->priceFor($discountedSubtotal) ?? 0;

        $vat = 0;

        if ($this->settings->vatRegistered()) {
            $standardRated = (int) $cart->items
                ->reject(fn ($item): bool => $item->variant->product->vat_zero_rated)
                ->sum(fn ($item): int => $item->lineTotal());

            if ($totals->subtotal > 0) {
                $standardRated -= (int) round($totals->discount * $standardRated / $totals->subtotal);
            }

            $vat = Vat::contained($standardRated + $shipping, $this->settings->vatRate());
        }

        return [
            'subtotal' => $totals->subtotal,
            'discount' => $totals->discount,
            'discount_code' => $totals->discountCode,
            'shipping' => $shipping,
            'vat' => $vat,
            'total' => $discountedSubtotal + $shipping,
            'currency' => $this->settings->currency(),
        ];
    }
}
