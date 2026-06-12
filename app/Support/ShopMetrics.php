<?php

namespace App\Support;

use App\Enums\CartStatus;
use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use Carbon\CarbonInterface;

/**
 * The five numbers a solo merchant actually needs, computed straight off
 * the orders table. Feeds the admin dashboard and the weekly digest email.
 */
class ShopMetrics
{
    /**
     * @return array{revenue: int, orders: int, aov: int, abandonment_rate: float|null, repeat_rate: float|null}
     */
    public function forPeriod(CarbonInterface $since): array
    {
        $paidStatuses = [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered];

        $orders = Order::query()
            ->whereIn('status', $paidStatuses)
            ->where('placed_at', '>=', $since)
            ->get(['id', 'email', 'total']);

        $revenue = (int) $orders->sum('total');
        $count = $orders->count();

        $cartsStarted = Cart::query()
            ->where('created_at', '>=', $since)
            ->whereHas('items')
            ->count();
        $cartsConverted = Cart::query()
            ->where('created_at', '>=', $since)
            ->where('status', CartStatus::Converted)
            ->count();

        $repeatEmails = $orders->groupBy('email')->filter(fn ($group) => $group->count() > 1)->count();
        $uniqueEmails = $orders->unique('email')->count();

        return [
            'revenue' => $revenue,
            'orders' => $count,
            'aov' => $count > 0 ? (int) round($revenue / $count) : 0,
            'abandonment_rate' => $cartsStarted > 0
                ? round((1 - $cartsConverted / $cartsStarted) * 100, 1)
                : null,
            'repeat_rate' => $uniqueEmails > 0
                ? round($repeatEmails / $uniqueEmails * 100, 1)
                : null,
        ];
    }
}
