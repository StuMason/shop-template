<?php

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Notifications\OrderPaidNotification;
use App\Payments\Gateways\FakeGateway;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function checkoutPayload(ShippingMethod $method): array
{
    return [
        'email' => 'buyer@example.com',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'Test Buyer',
            'line1' => '1 High Street',
            'line2' => null,
            'city' => 'Bristol',
            'county' => null,
            'postcode' => 'BS1 1AA',
            'country' => 'GB',
            'phone' => null,
        ],
    ];
}

function basketWithStock(int $stock = 10, int $price = 2000, int $quantity = 2)
{
    $variant = Product::factory()
        ->published()
        ->withDefaultVariant(price: $price, stock: $stock)
        ->create()
        ->variants()
        ->first();

    test()->post(route('cart.items.store'), [
        'variant_id' => $variant->id,
        'quantity' => $quantity,
    ]);

    return $variant;
}

it('creates a pending order, decrements stock and converts the cart', function () {
    $method = ShippingMethod::factory()->create(['price' => 395]);
    $variant = basketWithStock(stock: 10, price: 2000, quantity: 2);

    $response = $this->post(route('checkout.store'), checkoutPayload($method));

    $order = Order::query()->sole();

    $response->assertRedirect();
    expect($response->headers->get('location'))->toContain("/checkout/pay/{$order->number}");

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->subtotal)->toBe(4000)
        ->and($order->shipping_total)->toBe(395)
        ->and($order->total)->toBe(4395)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->quantity)->toBe(2)
        ->and($variant->fresh()->stock)->toBe(8)
        ->and(Cart::query()->sole()->status)->toBe(CartStatus::Converted);
});

it('is idempotent: a second submit returns the same order', function () {
    $method = ShippingMethod::factory()->create();
    basketWithStock();

    $this->post(route('checkout.store'), checkoutPayload($method));
    $this->post(route('checkout.store'), checkoutPayload($method));

    expect(Order::query()->count())->toBe(1);
});

it('applies the free shipping threshold', function () {
    $method = ShippingMethod::factory()->freeOver(3000)->create(['price' => 395]);
    basketWithStock(price: 2000, quantity: 2);

    $this->post(route('checkout.store'), checkoutPayload($method));

    expect(Order::query()->sole()->shipping_total)->toBe(0);
});

it('rejects checkout when stock has gone', function () {
    $method = ShippingMethod::factory()->create();
    $variant = basketWithStock(stock: 2, quantity: 2);

    $variant->update(['stock' => 1]);

    $this->from(route('checkout.show'))
        ->post(route('checkout.store'), checkoutPayload($method))
        ->assertRedirect(route('checkout.show'))
        ->assertSessionHasErrors('basket');

    expect(Order::query()->count())->toBe(0);
});

it('completes payment through the fake gateway and marks the order paid', function () {
    Notification::fake();

    $method = ShippingMethod::factory()->create();
    basketWithStock();

    $this->post(route('checkout.store'), checkoutPayload($method));
    $order = Order::query()->sole();

    // Start the payment: the fake gateway "redirects" straight to the return URL.
    $payResponse = $this->post(route('checkout.pay.start', $order));
    $payResponse->assertRedirect();

    $returnUrl = $payResponse->headers->get('location');

    $this->get($returnUrl)->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->latestPayment->status)->toBe(PaymentStatus::Succeeded);

    Notification::assertSentOnDemand(
        OrderPaidNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'buyer@example.com',
    );
});

it('records a failed payment without paying the order', function () {
    $method = ShippingMethod::factory()->create();
    basketWithStock();

    $this->post(route('checkout.store'), checkoutPayload($method));
    $order = Order::query()->sole();

    $payResponse = $this->post(route('checkout.pay.start', $order));
    FakeGateway::willReturn($order->payments()->sole(), PaymentStatus::Failed);

    $this->get($payResponse->headers->get('location'));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->latestPayment->status)->toBe(PaymentStatus::Failed);
});

it('abandons stale pending payments and restocks the order', function () {
    $method = ShippingMethod::factory()->create();
    $variant = basketWithStock(stock: 10, quantity: 2);

    $this->post(route('checkout.store'), checkoutPayload($method));
    $order = Order::query()->sole();

    $this->post(route('checkout.pay.start', $order));
    $payment = $order->payments()->sole();
    $payment->forceFill(['created_at' => now()->subHours(3)])->save();

    FakeGateway::willReturn($payment, PaymentStatus::Failed);

    $this->artisan('payments:expire-abandoned')->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Abandoned)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($variant->fresh()->stock)->toBe(10);
});

it('still pays the order from the abandonment sweep when the gateway says succeeded', function () {
    $method = ShippingMethod::factory()->create();
    basketWithStock();

    $this->post(route('checkout.store'), checkoutPayload($method));
    $order = Order::query()->sole();

    $this->post(route('checkout.pay.start', $order));
    $order->payments()->sole()->forceFill(['created_at' => now()->subHours(3)])->save();

    $this->artisan('payments:expire-abandoned')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('redirects an empty basket away from checkout', function () {
    $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
});

it('self-heals a pending payment on the confirmation page', function () {
    $method = ShippingMethod::factory()->create();
    basketWithStock();

    $this->post(route('checkout.store'), checkoutPayload($method));
    $order = Order::query()->sole();

    $this->post(route('checkout.pay.start', $order));
    $payment = $order->payments()->sole();

    // The bank authorised but fulfilment lagged: the return visit saw pending.
    FakeGateway::willReturn($payment, PaymentStatus::Pending);
    $this->get(URL::signedRoute('checkout.complete', ['order' => $order]));
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);

    // Next poll of the confirmation page re-verifies and marks it paid.
    FakeGateway::willReturn($payment, PaymentStatus::Succeeded);
    $this->get(URL::signedRoute('checkout.complete', ['order' => $order]))
        ->assertInertia(fn ($page) => $page->where('order.status', 'paid'));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});
