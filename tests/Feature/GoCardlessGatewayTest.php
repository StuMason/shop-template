<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Gateways\GoCardlessGateway;
use App\Payments\PaymentManager;
use GoCardlessPro\Client;
use GoCardlessPro\Resources\BillingRequest;
use GoCardlessPro\Resources\BillingRequestFlow;
use GoCardlessPro\Services\BillingRequestFlowsService;
use GoCardlessPro\Services\BillingRequestsService;

function mockGoCardlessClient(): Client
{
    return Mockery::mock(Client::class);
}

it('creates a billing request and hosted flow, returning the bank redirect', function () {
    $order = Order::factory()->create(['total' => 4395]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'gateway' => 'gocardless',
        'amount' => 4395,
    ]);

    $billingRequests = Mockery::mock(BillingRequestsService::class);
    $billingRequests->shouldReceive('create')
        ->once()
        ->withArgs(function (array $args) use ($payment, $order): bool {
            return $args['params']['payment_request']['amount'] === 4395
                && $args['params']['payment_request']['currency'] === 'GBP'
                && $args['params']['metadata']['reference'] === $payment->idempotency_key
                && $args['params']['metadata']['order_number'] === $order->number
                && $args['headers']['Idempotency-Key'] === $payment->idempotency_key;
        })
        ->andReturn(new BillingRequest((object) ['id' => 'BRQ123']));

    $flows = Mockery::mock(BillingRequestFlowsService::class);
    $flows->shouldReceive('create')
        ->once()
        ->withArgs(fn (array $args): bool => $args['params']['links']['billing_request'] === 'BRQ123')
        ->andReturn(new BillingRequestFlow((object) [
            'authorisation_url' => 'https://pay.gocardless.com/flow/BRF456',
        ]));

    $client = mockGoCardlessClient();
    $client->shouldReceive('billingRequests')->andReturn($billingRequests);
    $client->shouldReceive('billingRequestFlows')->andReturn($flows);

    $pending = (new GoCardlessGateway($client))->createPayment(
        $payment,
        $order,
        'https://shop.test/return',
        'https://shop.test/webhook',
    );

    expect($pending->redirectUrl)->toBe('https://pay.gocardless.com/flow/BRF456')
        ->and($pending->gatewayIntentId)->toBe('BRQ123');
});

it('maps billing request statuses to payment statuses on verify', function (string $gcStatus, PaymentStatus $expected) {
    $payment = Payment::factory()->create([
        'gateway' => 'gocardless',
        'gateway_intent_id' => 'BRQ123',
    ]);

    $billingRequests = Mockery::mock(BillingRequestsService::class);
    $billingRequests->shouldReceive('get')
        ->once()
        ->with('BRQ123')
        ->andReturn(new BillingRequest((object) [
            'id' => 'BRQ123',
            'status' => $gcStatus,
            'links' => (object) ['payment_request_payment' => 'PM999'],
        ]));

    $client = mockGoCardlessClient();
    $client->shouldReceive('billingRequests')->andReturn($billingRequests);

    $verification = (new GoCardlessGateway($client))->verify($payment);

    expect($verification->status)->toBe($expected)
        ->and($verification->gatewayTransactionId)->toBe('PM999');
})->with([
    'fulfilled is succeeded' => ['fulfilled', PaymentStatus::Succeeded],
    'cancelled is failed' => ['cancelled', PaymentStatus::Failed],
    'pending stays pending' => ['pending', PaymentStatus::Pending],
    'fulfilling stays pending' => ['fulfilling', PaymentStatus::Pending],
]);

it('rejects webhooks with an invalid signature', function () {
    config(['services.gocardless.webhook_secret' => 'whsec_test']);

    $this->postJson(route('webhooks.gocardless'), ['events' => []], [
        'Webhook-Signature' => 'not-a-real-signature',
    ])->assertStatus(498);
});

it('marks the order paid from a signed webhook after a verify lookback', function () {
    config([
        'services.gocardless.webhook_secret' => 'whsec_test',
        'payments.default' => 'gocardless',
    ]);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'gateway' => 'gocardless',
        'gateway_intent_id' => 'BRQ123',
    ]);

    $billingRequests = Mockery::mock(BillingRequestsService::class);
    $billingRequests->shouldReceive('get')
        ->once()
        ->with('BRQ123')
        ->andReturn(new BillingRequest((object) [
            'id' => 'BRQ123',
            'status' => 'fulfilled',
            'links' => (object) ['payment_request_payment' => 'PM999'],
        ]));

    $client = mockGoCardlessClient();
    $client->shouldReceive('billingRequests')->andReturn($billingRequests);

    // Swap the resolved gocardless driver for one backed by the mocked client.
    app(PaymentManager::class)->extend('gocardless', fn () => new GoCardlessGateway($client));

    $body = json_encode([
        'events' => [
            [
                'id' => 'EV123',
                'resource_type' => 'billing_requests',
                'action' => 'fulfilled',
                'links' => ['billing_request' => 'BRQ123'],
            ],
        ],
    ]);

    $this->call(
        'POST',
        route('webhooks.gocardless'),
        [],
        [],
        [],
        [
            'HTTP_Webhook-Signature' => hash_hmac('sha256', $body, 'whsec_test'),
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    )->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->fresh()->gateway_transaction_id)->toBe('PM999');
});

it('ignores webhook events for unknown billing requests', function () {
    config(['services.gocardless.webhook_secret' => 'whsec_test']);

    $body = json_encode([
        'events' => [
            [
                'id' => 'EV123',
                'resource_type' => 'billing_requests',
                'action' => 'fulfilled',
                'links' => ['billing_request' => 'BRQ_UNKNOWN'],
            ],
        ],
    ]);

    $this->call(
        'POST',
        route('webhooks.gocardless'),
        [],
        [],
        [],
        [
            'HTTP_Webhook-Signature' => hash_hmac('sha256', $body, 'whsec_test'),
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    )->assertOk();
});
