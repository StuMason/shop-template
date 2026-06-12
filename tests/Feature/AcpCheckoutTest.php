<?php

use App\Models\AgentCheckoutSession;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;

beforeEach(function () {
    config(['services.acp.api_key' => 'test-acp-key']);
});

/**
 * @return array<string, string>
 */
function acpHeaders(array $extra = []): array
{
    return ['Authorization' => 'Bearer test-acp-key', ...$extra];
}

function acpVariant(int $price = 2000, int $stock = 10): string
{
    $variant = Product::factory()->published()
        ->withDefaultVariant(price: $price, stock: $stock)
        ->create()->variants()->first();

    return $variant->sku;
}

it('404s the whole surface when no API key is configured', function () {
    config(['services.acp.api_key' => null]);

    $this->postJson('/acp/checkout_sessions', [])->assertNotFound();
    $this->getJson('/acp/feed')->assertNotFound();
});

it('rejects bad bearer tokens', function () {
    $this->postJson('/acp/checkout_sessions', [], ['Authorization' => 'Bearer wrong'])
        ->assertUnauthorized();
});

it('verifies HMAC signatures when a secret is set', function () {
    config(['services.acp.signature_secret' => 'sig-secret']);

    $body = json_encode(['items' => [['id' => 'X', 'quantity' => 1]]]);
    $timestamp = now()->toRfc3339String();
    $signature = base64_encode(hash_hmac('sha256', $timestamp.'.'.$body, 'sig-secret', true));

    $this->call('POST', '/acp/checkout_sessions', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer test-acp-key',
        'HTTP_TIMESTAMP' => $timestamp,
        'HTTP_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $body)->assertStatus(201);

    $this->call('POST', '/acp/checkout_sessions', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer test-acp-key',
        'HTTP_TIMESTAMP' => $timestamp,
        'HTTP_SIGNATURE' => 'tampered',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
    ], $body)->assertUnauthorized();
});

it('creates a session with live totals and echoes the idempotency key', function () {
    $sku = acpVariant(price: 2000);
    ShippingMethod::factory()->create(['price' => 395]);

    $response = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 2]],
    ], acpHeaders(['Idempotency-Key' => 'idem-1']));

    $response->assertStatus(201)
        ->assertHeader('Idempotency-Key', 'idem-1')
        ->assertJsonPath('status', 'open')
        ->assertJsonPath('totals.subtotal', 4000)
        ->assertJsonPath('line_items.0.quantity', 2);

    // Replay with the same key returns the same session, not a duplicate.
    $replay = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 2]],
    ], acpHeaders(['Idempotency-Key' => 'idem-1']));

    expect($replay->json('id'))->toBe($response->json('id'))
        ->and(AgentCheckoutSession::query()->count())->toBe(1);
});

it('walks the full agent checkout: update, complete, pay link', function () {
    $sku = acpVariant(price: 2000);
    $method = ShippingMethod::factory()->create(['price' => 395]);

    $id = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 1]],
    ], acpHeaders())->json('id');

    // Agent fills in buyer + address + fulfillment.
    $this->postJson("/acp/checkout_sessions/{$id}", [
        'buyer' => ['email' => 'agent-buyer@example.com'],
        'shipping_address' => [
            'name' => 'Agent Buyer', 'line1' => '1 Protocol Way', 'city' => 'Bristol',
            'postcode' => 'BS1 1AA', 'country' => 'GB',
        ],
        'fulfillment_option_id' => $method->id,
    ], acpHeaders())
        ->assertOk()
        ->assertJsonPath('totals.total', 2395)
        ->assertJsonPath('fulfillment.selected_id', $method->id);

    $complete = $this->postJson("/acp/checkout_sessions/{$id}/complete", [], acpHeaders());

    $complete->assertOk()
        ->assertJsonPath('status', 'completed');

    $order = Order::query()->sole();
    expect($complete->json('order.id'))->toBe($order->number)
        ->and($complete->json('links.payment_url'))->toContain("/checkout/pay/{$order->number}")
        ->and($order->total)->toBe(2395)
        ->and($order->customer_note)->toContain('AI agent');

    // Completing again is idempotent.
    $this->postJson("/acp/checkout_sessions/{$id}/complete", [], acpHeaders())->assertOk();
    expect(Order::query()->count())->toBe(1);
});

it('refuses to complete an unready session with actionable messages', function () {
    $sku = acpVariant();

    $id = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 1]],
    ], acpHeaders())->json('id');

    $this->postJson("/acp/checkout_sessions/{$id}/complete", [], acpHeaders())
        ->assertStatus(422)
        ->assertJsonPath('messages.0', fn (string $message) => str_contains($message, 'buyer email'));
});

it('applies discount codes and surfaces invalid ones as messages', function () {
    $sku = acpVariant(price: 10000);
    Discount::factory()->create(['code' => 'AGENT10', 'value' => 10]);

    $id = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 1]],
    ], acpHeaders())->json('id');

    $this->postJson("/acp/checkout_sessions/{$id}", ['discount_code' => 'AGENT10'], acpHeaders())
        ->assertOk()
        ->assertJsonPath('totals.discount', 1000);

    $this->postJson("/acp/checkout_sessions/{$id}", ['discount_code' => 'NOPE'], acpHeaders())
        ->assertOk()
        ->assertJsonPath('messages.0', fn (string $message) => str_contains($message, "don't recognise"));
});

it('cancels open sessions and abandons their carts', function () {
    $sku = acpVariant();

    $id = $this->postJson('/acp/checkout_sessions', [
        'items' => [['id' => $sku, 'quantity' => 1]],
    ], acpHeaders())->json('id');

    $this->postJson("/acp/checkout_sessions/{$id}/cancel", [], acpHeaders())
        ->assertOk()
        ->assertJsonPath('status', 'canceled');

    expect(AgentCheckoutSession::query()->sole()->cart->status->value)->toBe('abandoned');
});

it('serves a product feed with SKU item ids', function () {
    $sku = acpVariant(price: 1450);

    $this->getJson('/acp/feed', acpHeaders())
        ->assertOk()
        ->assertJsonPath('items.0.id', $sku)
        ->assertJsonPath('items.0.price.amount', 1450)
        ->assertJsonPath('shop.checkout.protocol', 'acp');
});
