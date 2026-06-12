<?php

use App\Enums\OrderStatus;
use App\Models\Order;

it('allows only legal status transitions', function (OrderStatus $from, OrderStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'pending -> paid' => [OrderStatus::Pending, OrderStatus::Paid, true],
    'pending -> cancelled' => [OrderStatus::Pending, OrderStatus::Cancelled, true],
    'pending -> shipped' => [OrderStatus::Pending, OrderStatus::Shipped, false],
    'paid -> shipped' => [OrderStatus::Paid, OrderStatus::Shipped, true],
    'paid -> refunded' => [OrderStatus::Paid, OrderStatus::Refunded, true],
    'shipped -> delivered' => [OrderStatus::Shipped, OrderStatus::Delivered, true],
    'shipped -> cancelled' => [OrderStatus::Shipped, OrderStatus::Cancelled, false],
    'delivered -> refunded' => [OrderStatus::Delivered, OrderStatus::Refunded, true],
    'cancelled -> paid' => [OrderStatus::Cancelled, OrderStatus::Paid, false],
    'refunded -> anything' => [OrderStatus::Refunded, OrderStatus::Pending, false],
]);

it('throws on an illegal transition', function () {
    $order = Order::factory()->create();

    $order->transitionTo(OrderStatus::Delivered);
})->throws(InvalidArgumentException::class);
