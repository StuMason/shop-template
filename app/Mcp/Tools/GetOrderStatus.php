<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Check an order\'s status. Requires the order number AND the email it was placed with.')]
class GetOrderStatus extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $order = Order::query()
            ->where('number', (string) $request->get('order_number'))
            ->where('email', strtolower((string) $request->get('email')))
            ->first();

        if ($order === null) {
            return Response::error('No order matches that number and email.');
        }

        return Response::json([
            'order_number' => $order->number,
            'status' => $order->status->value,
            'total' => $order->formattedTotal(),
            'placed_at' => $order->placed_at->toIso8601String(),
            'paid_at' => $order->paid_at?->toIso8601String(),
            'shipped_at' => $order->shipped_at?->toIso8601String(),
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
            'order_number' => $schema->string()->description('The order number, e.g. ORD-260612-AB3D.')->required(),
            'email' => $schema->string()->description('The email address the order was placed with.')->required(),
        ];
    }
}
