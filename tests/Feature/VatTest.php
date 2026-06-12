<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Support\Vat;

it('extracts the vat contained in an inclusive amount', function () {
    expect(Vat::contained(12000, 20.0))->toBe(2000)
        ->and(Vat::contained(2395, 20.0))->toBe(399)
        ->and(Vat::contained(1000, 0.0))->toBe(0);
});

function vatCheckout(int $price = 12000): Order
{
    $method = ShippingMethod::factory()->create(['price' => 600]);
    $variant = Product::factory()->published()->withDefaultVariant(price: $price)->create()
        ->variants()->first();

    test()->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1]);
    test()->post(route('checkout.store'), [
        'email' => 'vat@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'V Test', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ]);

    return Order::query()->sole();
}

it('snapshots zero vat when the shop is not registered', function () {
    $order = vatCheckout();

    expect($order->vat_total)->toBe(0);
});

it('snapshots the vat contained in goods and delivery when registered', function () {
    config(['shop.vat_registered' => true, 'shop.vat_rate' => 20.0]);

    $order = vatCheckout(price: 12000);

    // (12000 + 600 shipping) * 20/120 = 2100
    expect($order->vat_total)->toBe(2100)
        ->and($order->total)->toBe(12600);
});

it('excludes zero-rated products from the vat snapshot', function () {
    config(['shop.vat_registered' => true, 'shop.vat_rate' => 20.0]);

    $method = ShippingMethod::factory()->create(['price' => 0]);
    $product = Product::factory()->published()->withDefaultVariant(price: 6000)->create([
        'vat_zero_rated' => true,
    ]);

    $this->post(route('cart.items.store'), [
        'variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ]);
    $this->post(route('checkout.store'), [
        'email' => 'vat@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'V Test', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ]);

    expect(Order::query()->sole()->vat_total)->toBe(0);
});
