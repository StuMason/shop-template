<?php

namespace App\Support;

use App\Models\Order;
use Illuminate\Support\Str;

/**
 * Short, opaque order numbers like ORD-260612-K3F7. No sequence leakage;
 * the unique index on orders.number is the real guarantee, so we retry on
 * the (vanishingly rare) collision.
 */
class OrderNumber
{
    public static function generate(): string
    {
        $prefix = app(ShopSettings::class)->orderPrefix();

        do {
            $number = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('ymd'),
                strtoupper(Str::random(4)),
            );
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
