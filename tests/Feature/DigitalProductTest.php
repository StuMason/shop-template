<?php

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Storefront\DownloadController;
use App\Mcp\Servers\ShopServer;
use App\Mcp\Tools\AddToBasket;
use App\Mcp\Tools\CreateBasket;
use App\Mcp\Tools\StartCheckout;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Notifications\OrderPaidNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

function digitalProduct(int $price = 1500): Product
{
    $product = Product::factory()->published()
        ->withDefaultVariant(price: $price, stock: 0)
        ->create(['is_digital' => true]);

    $product->addMedia(UploadedFile::fake()->create('guide.pdf', 100, 'application/pdf'))
        ->toMediaCollection('downloads');

    return $product;
}

function checkoutDigitalBasket(?int $methodId = null): Order
{
    test()->post(route('checkout.store'), [
        'email' => 'reader@example.com',
        'shipping_method_id' => $methodId,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'R Reader', 'line1' => '1 Ebook Lane', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ]);

    return Order::query()->latest('id')->firstOrFail();
}

it('sells digital products without stock or shipping', function () {
    $product = digitalProduct(price: 1500);
    $variant = $product->variants->first();

    // Stock of 0 doesn't matter for digital.
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 1])
        ->assertSessionDoesntHaveErrors();

    $this->get(route('checkout.show'))
        ->assertInertia(fn ($page) => $page->where('requiresShipping', false));

    $order = checkoutDigitalBasket();

    expect($order->shipping_total)->toBe(0)
        ->and($order->shipping_method_name)->toBeNull()
        ->and($order->total)->toBe(1500)
        ->and($order->items->first()->is_digital)->toBeTrue()
        ->and($variant->fresh()->stock)->toBe(0);
});

it('still requires shipping for mixed baskets', function () {
    $digital = digitalProduct();
    $physical = Product::factory()->published()->withDefaultVariant(stock: 5)->create();

    $this->post(route('cart.items.store'), ['variant_id' => $digital->variants->first()->id, 'quantity' => 1]);
    $this->post(route('cart.items.store'), ['variant_id' => $physical->variants->first()->id, 'quantity' => 1]);

    $this->get(route('checkout.show'))
        ->assertInertia(fn ($page) => $page->where('requiresShipping', true));

    $this->post(route('checkout.store'), [
        'email' => 'reader@example.com',
        'shipping_method_id' => null,
        'billing_same_as_shipping' => true,
        'shipping_address' => [
            'name' => 'R', 'line1' => '1 St', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ])->assertSessionHasErrors('shipping_method_id');
});

it('auto-delivers digital orders on payment with download links in the email', function () {
    Notification::fake();

    digitalProduct();
    $product = Product::query()->sole();
    $this->post(route('cart.items.store'), ['variant_id' => $product->variants->first()->id, 'quantity' => 1]);

    $order = checkoutDigitalBasket();

    $payResponse = $this->post(route('checkout.pay.start', $order));
    $this->get($payResponse->headers->get('location'));

    $order->refresh();

    // Paid and immediately delivered — nothing to ship.
    expect($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->delivered_at)->not->toBeNull();

    Notification::assertSentOnDemand(
        OrderPaidNotification::class,
        function (OrderPaidNotification $notification, array $channels, object $notifiable): bool {
            $mail = $notification->toMail($notifiable);
            $lines = collect($mail->introLines)->implode(' ');

            return str_contains($lines, 'Your downloads')
                && str_contains($lines, '/downloads/');
        },
    );
});

it('serves downloads via signed links and enforces the limit', function () {
    digitalProduct();
    $product = Product::query()->sole();
    $this->post(route('cart.items.store'), ['variant_id' => $product->variants->first()->id, 'quantity' => 1]);

    $order = checkoutDigitalBasket();
    $item = $order->items->first();
    $url = URL::temporarySignedRoute('orders.download', now()->addDays(30), ['order' => $order, 'item' => $item]);

    // Unpaid: refused.
    $this->get($url)->assertForbidden();

    $order->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now()])->save();

    $this->get($url)
        ->assertOk()
        ->assertDownload('guide.pdf');

    expect($item->fresh()->download_count)->toBe(1);

    // Limit reached: refused with guidance.
    $item->forceFill(['download_count' => DownloadController::DOWNLOAD_LIMIT])->save();
    $this->get($url)->assertStatus(429);

    // Unsigned URL: refused.
    $this->get(route('orders.download', ['order' => $order, 'item' => $item]))->assertForbidden();
});

it('checks out digital baskets through MCP without a shipping method', function () {
    digitalProduct(price: 2500);
    $product = Product::query()->sole();

    ShopServer::tool(CreateBasket::class, [])->assertOk();
    $token = Cart::query()->sole()->token;

    ShopServer::tool(AddToBasket::class, [
        'basket_token' => $token,
        'variant_id' => $product->variants->first()->id,
        'quantity' => 1,
    ])->assertOk();

    ShopServer::tool(StartCheckout::class, [
        'basket_token' => $token,
        'email' => 'agent@example.com',
        'shipping_address' => [
            'name' => 'A', 'line1' => '1 Bot Lane', 'city' => 'London',
            'postcode' => 'E1 6AN', 'country' => 'GB',
        ],
    ])->assertOk()->assertSee('pay_url');

    expect(Order::query()->sole()->total)->toBe(2500);
});

it('completes digital ACP sessions without a fulfillment option', function () {
    config(['services.acp.api_key' => 'test-acp-key']);

    $product = digitalProduct(price: 3000);
    $sku = $product->variants->first()->sku;
    $headers = ['Authorization' => 'Bearer test-acp-key'];

    $create = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 1]],
        'buyer' => ['email' => 'agent-reader@example.com'],
        'shipping_address' => [
            'name' => 'A', 'line1' => '1 Protocol Way', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
    ], $headers);
    $create->assertStatus(201);
    $id = $create->json('id');

    $this->postJson("/acp/checkout_sessions/{$id}/complete", [], $headers)
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('totals.fulfillment', 0);
});

it('restocking a cancelled mixed order skips digital lines', function () {
    $digital = digitalProduct();
    $physical = Product::factory()->published()->withDefaultVariant(stock: 5)->create();
    $method = ShippingMethod::factory()->create();

    $this->post(route('cart.items.store'), ['variant_id' => $digital->variants->first()->id, 'quantity' => 2]);
    $this->post(route('cart.items.store'), ['variant_id' => $physical->variants->first()->id, 'quantity' => 2]);

    $order = checkoutDigitalBasket($method->id);

    expect($physical->variants->first()->fresh()->stock)->toBe(3);

    app(CancelOrder::class)->handle($order);

    expect($physical->variants->first()->fresh()->stock)->toBe(5)
        ->and($digital->variants->first()->fresh()->stock)->toBe(0);
});
