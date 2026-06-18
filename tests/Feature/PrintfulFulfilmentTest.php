<?php

use App\Actions\Orders\CreatePrintfulOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Notifications\OrderShippedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function podVariant(?int $printfulId): ProductVariant
{
    $variant = Product::factory()->published()->withDefaultVariant(stock: 10)->create()
        ->variants()->first();
    $variant->update(['printful_variant_id' => $printfulId]);

    return $variant->fresh();
}

function orderWithVariants(array $variants): Order
{
    $order = Order::factory()->paid()->create([
        'shipping_address' => [
            'name' => 'A Buyer', 'line1' => '1 Mug Lane', 'line2' => null,
            'city' => 'Bristol', 'county' => null, 'postcode' => 'BS1 1AA',
            'country' => 'GB', 'phone' => null,
        ],
    ]);

    foreach ($variants as $variant) {
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => 'Default',
            'sku' => $variant->sku,
            'unit_price' => 1499,
            'quantity' => 2,
            'line_total' => 2998,
        ]);
    }

    return $order->load('items.variant');
}

it('pushes a paid print-on-demand order to Printful', function () {
    config(['services.printful.token' => 'pf-test', 'services.printful.store_id' => '18350628']);
    Http::fake(['api.printful.com/orders*' => Http::response(['result' => ['id' => 999]])]);

    $order = orderWithVariants([podVariant(4836)]);
    app(CreatePrintfulOrder::class)->handle($order);

    expect($order->fresh()->printful_order_id)->toBe(999);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/orders')
        && $request['external_id'] === $order->number
        && $request['recipient']['address1'] === '1 Mug Lane'
        && $request['items'][0]['sync_variant_id'] === 4836
        && $request['items'][0]['quantity'] === 2);
});

it('does nothing when Printful is not configured', function () {
    config(['services.printful.token' => null]);
    Http::fake();

    $order = orderWithVariants([podVariant(4836)]);
    app(CreatePrintfulOrder::class)->handle($order);

    Http::assertNothingSent();
    expect($order->fresh()->printful_order_id)->toBeNull();
});

it('only sends the POD items, not the rest of the basket', function () {
    config(['services.printful.token' => 'pf-test']);
    Http::fake(['api.printful.com/*' => Http::response(['result' => ['id' => 1]])]);

    $order = orderWithVariants([podVariant(4836), podVariant(null)]);
    app(CreatePrintfulOrder::class)->handle($order);

    Http::assertSent(fn ($request) => count($request['items']) === 1
        && $request['items'][0]['sync_variant_id'] === 4836);
});

it('marks the order shipped from the package_shipped webhook', function () {
    Notification::fake();
    config(['services.printful.webhook_secret' => 'sec']);

    $order = Order::factory()->paid()->create(['printful_order_id' => 999]);

    $this->postJson('/webhooks/printful?token=sec', [
        'type' => 'package_shipped',
        'data' => [
            'order' => ['id' => 999, 'external_id' => $order->number],
            'shipment' => ['tracking_number' => 'PF123GB', 'carrier' => 'Royal Mail'],
        ],
    ])->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->tracking_number)->toBe('PF123GB')
        ->and($order->carrier)->toBe('Royal Mail');

    Notification::assertSentOnDemand(OrderShippedNotification::class);
});

it('guards the webhook with the URL secret', function () {
    $order = Order::factory()->paid()->create(['printful_order_id' => 999]);
    $body = ['type' => 'package_shipped', 'data' => ['order' => ['id' => 999]]];

    // No secret configured → the surface is off.
    $this->postJson('/webhooks/printful?token=anything', $body)->assertNotFound();

    config(['services.printful.webhook_secret' => 'sec']);
    $this->postJson('/webhooks/printful?token=wrong', $body)->assertUnauthorized();
});
