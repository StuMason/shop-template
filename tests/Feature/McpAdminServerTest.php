<?php

use App\Mcp\Servers\AdminServer;
use App\Mcp\Tools\AdminGetOrder;
use App\Mcp\Tools\AdminListOrders;
use App\Mcp\Tools\AdminLowStock;
use App\Mcp\Tools\AdminSalesSummary;
use App\Mcp\Tools\AdminSearchCustomers;
use App\Mcp\Tools\AdminShipOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderShippedNotification;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;

beforeEach(function () {
    // The Passport guard needs RSA keys even to reject a request; they're
    // gitignored, so generate them when absent (fresh clones, CI).
    if (! file_exists(storage_path('oauth-public.key'))) {
        $this->artisan('passport:keys');
    }
});

it('summarises sales for a period', function () {
    Order::factory()->paid()->create(['total' => 5000, 'subtotal' => 5000, 'shipping_total' => 0]);
    Order::factory()->paid()->create(['total' => 3000, 'subtotal' => 3000, 'shipping_total' => 0]);
    Order::factory()->create(); // pending — excluded from revenue

    AdminServer::tool(AdminSalesSummary::class, ['days' => 7])
        ->assertOk()
        ->assertSee('£80.00')
        ->assertSee('£40.00');
});

it('lists and fetches orders', function () {
    $order = Order::factory()->paid()->create(['email' => 'find-me@example.com']);

    AdminServer::tool(AdminListOrders::class, ['email' => 'find-me@example.com'])
        ->assertOk()
        ->assertSee($order->number);

    AdminServer::tool(AdminGetOrder::class, ['order_number' => $order->number])
        ->assertOk()
        ->assertSee('find-me@example.com');
});

it('ships an order with tracking and emails the customer', function () {
    Notification::fake();

    $order = Order::factory()->paid()->create();

    AdminServer::tool(AdminShipOrder::class, [
        'order_number' => $order->number,
        'tracking_number' => 'AB123456789GB',
        'carrier' => 'Royal Mail',
    ])->assertOk()->assertSee('shipped');

    expect($order->fresh()->tracking_number)->toBe('AB123456789GB');

    Notification::assertSentOnDemand(OrderShippedNotification::class);
});

it('refuses to ship orders in the wrong state', function () {
    $order = Order::factory()->create(); // pending

    AdminServer::tool(AdminShipOrder::class, ['order_number' => $order->number])
        ->assertHasErrors();

    expect($order->fresh()->status->value)->toBe('pending');
});

it('reports low stock variants', function () {
    Product::factory()->published()->withDefaultVariant(stock: 2)->create(['name' => 'Nearly Gone']);
    Product::factory()->published()->withDefaultVariant(stock: 50)->create(['name' => 'Plenty Left']);

    AdminServer::tool(AdminLowStock::class, [])
        ->assertOk()
        ->assertSee('Nearly Gone')
        ->assertDontSee('Plenty Left');
});

it('searches customers across accounts and guest orders', function () {
    $user = User::factory()->create(['name' => 'Repeat Riley', 'email' => 'riley@example.com']);
    Order::factory()->paid()->create(['user_id' => $user->id, 'total' => 4000]);
    Order::factory()->paid()->create(['email' => 'guest-riley@example.com', 'total' => 1500]);

    AdminServer::tool(AdminSearchCustomers::class, ['query' => 'riley'])
        ->assertOk()
        ->assertSee('Repeat Riley')
        ->assertSee('guest-riley@example.com');
});

it('keeps unauthenticated and non-staff users out of the admin endpoint', function () {
    $this->seed(RolesSeeder::class);

    $this->postJson('/mcp/admin', [])->assertUnauthorized();

    $customer = User::factory()->create();
    $customer->assignRole('customer');
    Passport::actingAs($customer, ['mcp:use'], 'api');

    $this->postJson('/mcp/admin', [])->assertForbidden();
});

it('lets staff through the admin endpoint', function () {
    $this->seed(RolesSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('staff');
    Passport::actingAs($staff, ['mcp:use'], 'api');

    // Auth + role pass; an empty body is a JSON-RPC problem, not an authz one.
    $this->postJson('/mcp/admin', [])->assertSuccessful();
});
