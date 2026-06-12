<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List recent orders, optionally filtered by status or customer email. Use admin-get-order for full detail.')]
class AdminListOrders extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $status = (string) $request->get('status', '');
        $email = (string) $request->get('email', '');

        $orders = Order::query()
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($email !== '', fn (Builder $query) => $query->where('email', strtolower($email)))
            ->latest('placed_at')
            ->take(min(100, max(1, (int) $request->get('limit', 25))))
            ->get()
            ->map(fn (Order $order): array => [
                'number' => $order->number,
                'email' => $order->email,
                'status' => $order->status->value,
                'total' => $order->formattedTotal(),
                'placed_at' => $order->placed_at->toIso8601String(),
            ]);

        return Response::json($orders->all());
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter: pending, paid, processing, shipped, delivered, cancelled, refunded.'),
            'email' => $schema->string()->description('Filter by customer email.'),
            'limit' => $schema->integer()->description('Max rows (default 25, cap 100).'),
        ];
    }
}
