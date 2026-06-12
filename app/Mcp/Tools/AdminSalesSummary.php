<?php

namespace App\Mcp\Tools;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\Money;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Sales summary for a period: order counts by status, revenue (paid orders), average order value, and top products by units sold.')]
class AdminSalesSummary extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $days = max(1, (int) $request->get('days', 30));
        $since = now()->subDays($days);

        $orders = Order::query()->where('placed_at', '>=', $since)->get();
        $paid = $orders->reject(fn (Order $order): bool => in_array(
            $order->status,
            [OrderStatus::Pending, OrderStatus::Cancelled],
            true,
        ));

        $revenue = (int) $paid->sum('total');

        $topProducts = DB::table('order_items')
            ->whereIn('order_id', $paid->pluck('id'))
            ->selectRaw('product_name, sum(quantity) as units, sum(line_total) as takings')
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->take(10)
            ->get()
            ->map(fn ($row): array => [
                'product' => $row->product_name,
                'units' => (int) $row->units,
                'takings' => Money::format((int) $row->takings),
            ]);

        return Response::json([
            'period_days' => $days,
            'orders_total' => $orders->count(),
            'orders_by_status' => $orders->groupBy(fn (Order $order): string => $order->status->value)
                ->map(fn ($group): int => $group->count()),
            'revenue' => Money::format($revenue),
            'average_order_value' => $paid->isNotEmpty()
                ? Money::format((int) round($revenue / $paid->count()))
                : null,
            'discount_given' => Money::format((int) $paid->sum('discount_total')),
            'vat_collected' => Money::format((int) $paid->sum('vat_total')),
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->description('Look-back window in days (default 30).'),
        ];
    }
}
