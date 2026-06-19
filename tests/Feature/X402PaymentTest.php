<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mcp\Servers\ShopServer;
use App\Mcp\Tools\AddToBasket;
use App\Mcp\Tools\CreateBasket;
use App\Mcp\Tools\StartCheckout;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Notifications\OrderPaidNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config([
        'services.x402.enabled' => true,
        'services.x402.pay_to' => '0xMERCHANTWALLET',
        'services.x402.network' => 'base-sepolia',
        'services.x402.facilitator_url' => 'https://facilitator.test',
        'services.x402.fx_rate' => 1.25,
    ]);
});

function x402Url(Order $order): string
{
    return URL::signedRoute('agent.pay.x402', ['order' => $order]);
}

function x402Header(): array
{
    return ['X-PAYMENT' => base64_encode(json_encode([
        'x402Version' => 1,
        'scheme' => 'exact',
        'network' => 'base-sepolia',
        'payload' => ['signature' => '0xsigned', 'authorization' => ['from' => '0xAGENT']],
    ]))];
}

it('advertises USDC payment requirements with a 402', function () {
    $order = Order::factory()->create(['total' => 2000, 'currency' => 'GBP']);

    $response = $this->getJson(x402Url($order));

    $response->assertStatus(402)
        ->assertJsonPath('x402Version', 1)
        ->assertJsonPath('accepts.0.scheme', 'exact')
        ->assertJsonPath('accepts.0.network', 'base-sepolia')
        ->assertJsonPath('accepts.0.payTo', '0xMERCHANTWALLET')
        // £20.00 * 1.25 = $25.00 = 25,000,000 atomic USDC units.
        ->assertJsonPath('accepts.0.maxAmountRequired', '25000000');
});

it('settles via the facilitator and marks the order paid', function () {
    Notification::fake();

    Http::fake([
        'facilitator.test/verify' => Http::response(['isValid' => true]),
        'facilitator.test/settle' => Http::response([
            'success' => true,
            'transaction' => '0xabc123',
            'network' => 'base-sepolia',
        ]),
    ]);

    $order = Order::factory()->create(['total' => 2000]);

    $this->getJson(x402Url($order), x402Header())
        ->assertOk()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('transaction', '0xabc123');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Paid)
        ->and($order->latestPayment->gateway)->toBe('x402')
        ->and($order->latestPayment->gateway_transaction_id)->toBe('0xabc123');

    Notification::assertSentOnDemand(OrderPaidNotification::class);
});

it('authenticates facilitator calls with a PayAI bearer token when configured', function () {
    $keypair = sodium_crypto_sign_keypair();
    $seed = substr(sodium_crypto_sign_secretkey($keypair), 0, 32);

    config([
        'services.x402.payai.key_id' => 'key-123',
        'services.x402.payai.key_secret' => 'payai_sk_'.base64_encode(
            hex2bin('302e020100300506032b657004220420').$seed,
        ),
    ]);

    Http::fake([
        'facilitator.test/verify' => Http::response(['isValid' => true]),
        'facilitator.test/settle' => Http::response(['success' => true, 'transaction' => '0xabc123']),
    ]);

    $this->getJson(x402Url(Order::factory()->create(['total' => 2000])), x402Header())->assertOk();

    Http::assertSent(fn ($request) => in_array($request->url(), [
        'https://facilitator.test/verify',
        'https://facilitator.test/settle',
    ], true) && str_starts_with($request->header('Authorization')[0] ?? '', 'Bearer ey'));
});

it('returns 402 again when the facilitator rejects the payment', function () {
    Http::fake([
        'facilitator.test/verify' => Http::response(['isValid' => false, 'invalidReason' => 'insufficient_funds']),
    ]);

    $order = Order::factory()->create();

    $this->getJson(x402Url($order), x402Header())
        ->assertStatus(402)
        ->assertJsonPath('error', 'Payment could not be settled');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($order->payments()->where('status', PaymentStatus::Failed)->count())->toBe(1);

    // No settle call was made for an invalid payment.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/settle'));
});

it('rejects malformed X-PAYMENT headers without calling the facilitator', function () {
    Http::fake();

    $order = Order::factory()->create();

    $this->getJson(x402Url($order), ['X-PAYMENT' => 'not-base64-json!!'])
        ->assertStatus(402);

    Http::assertNothingSent();
});

it('is idempotent for already-paid orders', function () {
    $order = Order::factory()->paid()->create();

    $this->getJson(x402Url($order), x402Header())
        ->assertOk()
        ->assertJsonPath('message', 'This order is not awaiting payment.');
});

it('404s when the rail is disabled and requires a signed URL', function () {
    $order = Order::factory()->create();

    config(['services.x402.enabled' => false]);
    $this->getJson(x402Url($order))->assertNotFound();

    config(['services.x402.enabled' => true]);
    $this->getJson(route('agent.pay.x402', ['order' => $order]))->assertForbidden();
});

it('surfaces the x402 url through MCP start-checkout when enabled', function () {
    $method = ShippingMethod::factory()->create();
    $variant = Product::factory()->published()
        ->withDefaultVariant(price: 2000, stock: 5)
        ->create()->variants()->first();

    ShopServer::tool(CreateBasket::class, [])->assertOk();
    $token = Cart::query()->sole()->token;

    ShopServer::tool(AddToBasket::class, [
        'basket_token' => $token,
        'variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertOk();

    ShopServer::tool(StartCheckout::class, [
        'basket_token' => $token,
        'email' => 'agent@example.com',
        'shipping_method_id' => $method->id,
        'shipping_address' => [
            'name' => 'A', 'line1' => '1 Bot Lane', 'city' => 'London',
            'postcode' => 'E1 6AN', 'country' => 'GB',
        ],
    ])->assertOk()
        ->assertSee('x402_payment_url')
        ->assertSee('settle autonomously in USDC');
});
