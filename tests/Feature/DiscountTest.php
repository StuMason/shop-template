<?php

use App\Enums\OrderStatus;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Database\Seeders\RolesSeeder;

function discountBasket(int $price = 10000, int $quantity = 1): void
{
    $variant = Product::factory()->published()->withDefaultVariant(price: $price)->create()
        ->variants()->first();

    test()->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => $quantity]);
}

function discountCheckout(ShippingMethod $method): Order
{
    test()->post(route('checkout.store'), [
        'email' => 'deal@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'D Hunter', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ]);

    return Order::query()->sole();
}

it('applies a percent code to the basket totals', function () {
    Discount::factory()->create(['code' => 'SAVE10', 'value' => 10]);
    discountBasket(price: 10000);

    $this->post(route('cart.discount.store'), ['code' => 'save10'])->assertRedirect();

    $this->get(route('cart.show'))->assertInertia(fn ($page) => $page
        ->where('basket.discount', 1000)
        ->where('basket.total', 9000)
        ->where('basket.discount_code', 'SAVE10'));
});

it('rejects invalid codes with a reason', function (callable $factory, string $code) {
    $factory();
    discountBasket();

    $this->post(route('cart.discount.store'), ['code' => $code])
        ->assertSessionHasErrors('code');
})->with([
    'unknown' => [fn () => null, 'NOPE'],
    'expired' => [fn () => Discount::factory()->expired()->create(['code' => 'OLD']), 'OLD'],
    'inactive' => [fn () => Discount::factory()->create(['code' => 'OFF', 'is_active' => false]), 'OFF'],
    'fully redeemed' => [fn () => Discount::factory()->create(['code' => 'GONE', 'max_uses' => 1, 'used_count' => 1]), 'GONE'],
    'below minimum' => [fn () => Discount::factory()->create(['code' => 'BIG', 'min_subtotal' => 99999]), 'BIG'],
]);

it('snapshots the discount on the order and counts the use', function () {
    $method = ShippingMethod::factory()->create(['price' => 500]);
    $discount = Discount::factory()->fixed(2500)->create(['code' => 'FIVER25']);
    discountBasket(price: 10000);

    $this->post(route('cart.discount.store'), ['code' => 'FIVER25']);
    $order = discountCheckout($method);

    expect($order->discount_total)->toBe(2500)
        ->and($order->discount_code)->toBe('FIVER25')
        ->and($order->total)->toBe(8000)
        ->and($discount->fresh()->used_count)->toBe(1);
});

it('evaluates free shipping on the discounted subtotal', function () {
    $method = ShippingMethod::factory()->freeOver(9000)->create(['price' => 500]);
    Discount::factory()->fixed(2000)->create(['code' => 'CUT20']);
    discountBasket(price: 10000);

    $this->post(route('cart.discount.store'), ['code' => 'CUT20']);
    $order = discountCheckout($method);

    // 8000 discounted subtotal < 9000 threshold, so shipping is charged.
    expect($order->shipping_total)->toBe(500)
        ->and($order->total)->toBe(8500);
});

it('removes a discount from the basket', function () {
    Discount::factory()->create(['code' => 'SAVE10']);
    discountBasket();

    $this->post(route('cart.discount.store'), ['code' => 'SAVE10']);
    $this->delete(route('cart.discount.destroy'))->assertRedirect();

    $this->get(route('cart.show'))->assertInertia(fn ($page) => $page
        ->where('basket.discount', 0));
});

it('reduces the vat snapshot proportionally', function () {
    config(['shop.vat_registered' => true, 'shop.vat_rate' => 20.0]);

    $method = ShippingMethod::factory()->create(['price' => 0]);
    Discount::factory()->fixed(1200)->create(['code' => 'TENNER']);
    discountBasket(price: 12000);

    $this->post(route('cart.discount.store'), ['code' => 'TENNER']);
    $order = discountCheckout($method);

    // (12000 - 1200) * 20/120 = 1800
    expect($order->vat_total)->toBe(1800);
});

it('lets admins manage discount codes', function () {
    $this->seed(RolesSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('admin.discounts.store'), [
            'code' => 'welcome10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ])
        ->assertRedirect();

    $discount = Discount::query()->sole();
    expect($discount->code)->toBe('WELCOME10');

    $this->actingAs($admin)
        ->delete(route('admin.discounts.destroy', $discount))
        ->assertRedirect();

    expect(Discount::query()->count())->toBe(0);
});

it('enforces once-per-customer codes by email for guests', function () {
    $method = ShippingMethod::factory()->create();
    Discount::factory()->create(['code' => 'WELCOME', 'once_per_customer' => true]);

    // First order with the code succeeds.
    discountBasket();
    $this->post(route('cart.discount.store'), ['code' => 'WELCOME']);
    discountCheckout($method);

    // Second basket, same email: checkout is blocked with a clear error.
    test()->flushSession();
    discountBasket();
    $this->post(route('cart.discount.store'), ['code' => 'WELCOME'])->assertRedirect();

    $this->post(route('checkout.store'), [
        'email' => 'deal@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'D Hunter', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ])->assertSessionHasErrors('basket');

    expect(Order::query()->count())->toBe(1);
});

it('rejects once-per-customer codes at apply time for logged-in repeat customers', function () {
    $user = User::factory()->create();
    Discount::factory()->create(['code' => 'WELCOME', 'once_per_customer' => true]);
    Order::factory()->paid()->create(['user_id' => $user->id, 'discount_code' => 'WELCOME']);

    $this->actingAs($user);
    discountBasket();

    $this->post(route('cart.discount.store'), ['code' => 'WELCOME'])
        ->assertSessionHasErrors('code');
});

it('lets a customer reuse a once-per-customer code after a cancelled order', function () {
    Discount::factory()->create(['code' => 'WELCOME', 'once_per_customer' => true]);
    Order::factory()->create([
        'email' => 'deal@example.com',
        'discount_code' => 'WELCOME',
        'status' => OrderStatus::Cancelled,
    ]);

    $method = ShippingMethod::factory()->create();
    discountBasket();
    $this->post(route('cart.discount.store'), ['code' => 'WELCOME']);
    $this->post(route('checkout.store'), [
        'email' => 'deal@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'D Hunter', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ]);

    expect(Order::query()->latest('id')->first()->discount_code)->toBe('WELCOME');
});
