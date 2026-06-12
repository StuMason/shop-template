<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\OrderShippedNotification;
use Database\Seeders\RolesSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

it('lists and filters orders', function () {
    Order::factory()->create(['email' => 'findme@example.com']);
    Order::factory()->paid()->create();

    $this->actingAs($this->staff)
        ->get(route('admin.orders.index', ['q' => 'findme']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('orders.data', 1));

    $this->actingAs($this->staff)
        ->get(route('admin.orders.index', ['status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('orders.data', 1));
});

it('ships a paid order and notifies the customer', function () {
    Notification::fake();

    $order = Order::factory()->paid()->create();

    $this->actingAs($this->staff)
        ->patch(route('admin.orders.status', $order->id), ['status' => 'shipped'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Shipped);

    Notification::assertSentOnDemand(OrderShippedNotification::class);
});

it('rejects illegal transitions', function () {
    $order = Order::factory()->create();

    $this->actingAs($this->staff)
        ->patch(route('admin.orders.status', $order->id), ['status' => 'delivered'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('records a partial then full refund and updates statuses', function () {
    $order = Order::factory()->paid()->create(['total' => 5000]);
    $payment = Payment::factory()->succeeded()->create([
        'order_id' => $order->id,
        'amount' => 5000,
    ]);

    $this->actingAs($this->staff)
        ->post(route('admin.payments.refunds.store', $payment), ['amount' => 2000])
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);

    $this->actingAs($this->staff)
        ->post(route('admin.payments.refunds.store', $payment), ['amount' => 3000])
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('rejects refunds beyond the refundable balance', function () {
    $payment = Payment::factory()->succeeded()->create(['amount' => 1000]);

    $this->actingAs($this->staff)
        ->post(route('admin.payments.refunds.store', $payment), ['amount' => 2000])
        ->assertSessionHasErrors('amount');
});

it('keeps customers out of order management', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($customer)
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

it('shows customers their own orders only', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('account.orders.show', $order))
        ->assertOk();

    $this->actingAs($other)
        ->get(route('account.orders.show', $order))
        ->assertNotFound();
});

it('stores tracking details when shipping and includes them in the email', function () {
    Notification::fake();

    $order = Order::factory()->paid()->create();

    $this->actingAs($this->staff)
        ->patch(route('admin.orders.status', $order->id), [
            'status' => 'shipped',
            'tracking_number' => 'AB123456789GB',
            'carrier' => 'Royal Mail',
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->tracking_number)->toBe('AB123456789GB')
        ->and($order->carrier)->toBe('Royal Mail');

    Notification::assertSentOnDemand(
        OrderShippedNotification::class,
        function (OrderShippedNotification $notification, array $channels, $notifiable): bool {
            $mail = $notification->toMail($notifiable);

            return str_contains(implode(' ', $mail->introLines), 'AB123456789GB');
        },
    );
});

it('renders a packing slip without prices', function () {
    $order = Order::factory()->paid()->create();
    $order->items()->create([
        'product_variant_id' => null,
        'product_name' => 'Enamel Mug',
        'variant_name' => 'Default',
        'sku' => 'MUG-1',
        'unit_price' => 1450,
        'quantity' => 2,
        'line_total' => 2900,
    ]);

    $this->actingAs($this->staff)
        ->get(route('admin.orders.packing-slip', $order->id))
        ->assertOk()
        ->assertSee('Packing slip')
        ->assertSee('Enamel Mug')
        ->assertSee('MUG-1')
        ->assertDontSee('£14.50')
        ->assertDontSee('£29.00');
});
