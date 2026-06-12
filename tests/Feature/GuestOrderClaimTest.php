<?php

use App\Actions\Orders\ClaimGuestOrders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

it('claims guest orders when the email is verified', function () {
    $guestOrder = Order::factory()->paid()->create(['email' => 'newbie@example.com', 'user_id' => null]);
    Order::factory()->paid()->create(['email' => 'someone-else@example.com', 'user_id' => null]);

    $user = User::factory()->create(['email' => 'newbie@example.com']);

    event(new Verified($user));

    expect($guestOrder->fresh()->user_id)->toBe($user->id)
        ->and(Order::query()->whereNull('user_id')->count())->toBe(1);
});

it('claims later guest orders on login', function () {
    $user = User::factory()->create(['email' => 'regular@example.com']);

    // A guest purchase made after the account existed (e.g. by an AI agent).
    $order = Order::factory()->paid()->create(['email' => 'regular@example.com', 'user_id' => null]);

    $this->post(route('login'), ['email' => 'regular@example.com', 'password' => 'password']);

    expect($order->fresh()->user_id)->toBe($user->id);
});

it('never claims for unverified emails', function () {
    Order::factory()->paid()->create(['email' => 'shady@example.com', 'user_id' => null]);

    $user = User::factory()->unverified()->create(['email' => 'shady@example.com']);

    expect(app(ClaimGuestOrders::class)->handle($user))->toBe(0);
});
