<?php

use App\Enums\OrderStatus;
use App\Mcp\Servers\ShopServer;
use App\Mcp\Tools\AddToBasket;
use App\Mcp\Tools\CreateBasket;
use App\Mcp\Tools\GetOrderStatus;
use App\Mcp\Tools\GetProduct;
use App\Mcp\Tools\ListShippingMethods;
use App\Mcp\Tools\SearchProducts;
use App\Mcp\Tools\StartCheckout;
use App\Mcp\Tools\ViewBasket;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;

it('searches the catalogue', function () {
    Product::factory()->published()->withDefaultVariant()->create(['name' => 'Walnut Organiser']);
    Product::factory()->withDefaultVariant()->create(['name' => 'Draft Thing']);

    ShopServer::tool(SearchProducts::class, ['query' => 'walnut'])
        ->assertOk()
        ->assertSee('Walnut Organiser');

    ShopServer::tool(SearchProducts::class, [])
        ->assertOk()
        ->assertSee('Walnut Organiser')
        ->assertDontSee('Draft Thing');
});

it('returns full product details with variant ids', function () {
    $product = Product::factory()->published()->withDefaultVariant(price: 1999)->create();

    ShopServer::tool(GetProduct::class, ['slug' => $product->slug])
        ->assertOk()
        ->assertSee($product->variants->first()->sku)
        ->assertSee('£19.99');
});

it('walks the full agent purchase flow up to the human pay link', function () {
    $product = Product::factory()->published()->withDefaultVariant(price: 2500, stock: 5)->create();
    $variant = $product->variants->first();
    $method = ShippingMethod::factory()->create(['price' => 395]);

    $createResponse = ShopServer::tool(CreateBasket::class, [])->assertOk();
    $token = Cart::query()->sole()->token;

    ShopServer::tool(AddToBasket::class, [
        'basket_token' => $token,
        'variant_id' => $variant->id,
        'quantity' => 2,
    ])->assertOk()->assertSee('£50.00');

    ShopServer::tool(ViewBasket::class, ['basket_token' => $token])
        ->assertOk()
        ->assertSee($product->name);

    ShopServer::tool(ListShippingMethods::class, ['country' => 'GB'])
        ->assertOk()
        ->assertSee('Standard delivery');

    ShopServer::tool(StartCheckout::class, [
        'basket_token' => $token,
        'email' => 'agent-customer@example.com',
        'shipping_method_id' => $method->id,
        'shipping_address' => [
            'name' => 'Agent Customer',
            'line1' => '1 Bot Lane',
            'city' => 'London',
            'postcode' => 'E1 6AN',
            'country' => 'GB',
        ],
    ])->assertOk()->assertSee('pay_url');

    $order = Order::query()->sole();

    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->email)->toBe('agent-customer@example.com')
        ->and($order->total)->toBe(5395)
        ->and($variant->fresh()->stock)->toBe(3);
});

it('rejects unknown basket tokens', function () {
    ShopServer::tool(ViewBasket::class, ['basket_token' => 'not-a-token'])
        ->assertHasErrors();
});

it('refuses to oversell through the agent flow', function () {
    $product = Product::factory()->published()->withDefaultVariant(stock: 1)->create();

    ShopServer::tool(CreateBasket::class, [])->assertOk();
    $token = Cart::query()->sole()->token;

    ShopServer::tool(AddToBasket::class, [
        'basket_token' => $token,
        'variant_id' => $product->variants->first()->id,
        'quantity' => 5,
    ])->assertHasErrors();
});

it('reports order status with the matching email only', function () {
    $order = Order::factory()->paid()->create(['email' => 'owner@example.com']);

    ShopServer::tool(GetOrderStatus::class, [
        'order_number' => $order->number,
        'email' => 'owner@example.com',
    ])->assertOk()->assertSee('paid');

    ShopServer::tool(GetOrderStatus::class, [
        'order_number' => $order->number,
        'email' => 'wrong@example.com',
    ])->assertHasErrors();
});
