<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'services.x402.enabled' => true,
        'services.x402.pay_to' => '0xMERCHANTWALLET',
        'services.x402.network' => 'base',
        'services.x402.fx_rate' => 1.27,
    ]);
});

function payPage(Order $order): string
{
    return URL::signedRoute('checkout.pay', ['order' => $order]);
}

it('offers USDC checkout when x402 and a wallet project id are configured', function () {
    config(['services.x402.wallet_connect_project_id' => 'wc-proj-123']);

    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 1499]);

    $this->get(payPage($order))->assertInertia(fn (Assert $page) => $page
        ->component('checkout/pay')
        ->where('crypto.network', 'base')
        ->where('crypto.projectId', 'wc-proj-123')
        // £14.99 * 1.27 = $19.0373 -> 19,037,300 atomic USDC, shown as $19.04.
        ->where('crypto.maxAtomic', '19037300')
        ->where('crypto.usdLabel', '$19.04')
        ->has('crypto.payUrl')
        ->has('crypto.confirmUrl'),
    );
});

it('hides USDC checkout when no wallet project id is set', function () {
    config(['services.x402.wallet_connect_project_id' => '']);

    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 1499]);

    $this->get(payPage($order))->assertInertia(fn (Assert $page) => $page
        ->component('checkout/pay')
        ->where('crypto', null),
    );
});

it('hides USDC checkout when x402 is disabled', function () {
    config([
        'services.x402.enabled' => false,
        'services.x402.wallet_connect_project_id' => 'wc-proj-123',
    ]);

    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total' => 1499]);

    $this->get(payPage($order))->assertInertia(fn (Assert $page) => $page
        ->component('checkout/pay')
        ->where('crypto', null),
    );
});
