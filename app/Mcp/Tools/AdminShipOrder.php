<?php

namespace App\Mcp\Tools;

use App\Actions\Orders\ShipOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Mark a paid/processing order as shipped, optionally with carrier and tracking number. The customer is emailed (including tracking).')]
class AdminShipOrder extends Tool
{
    public function __construct(private readonly ShipOrder $shipOrder) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $order = Order::query()->firstWhere('number', (string) $request->get('order_number'));

        if ($order === null) {
            return Response::error('No order with that number.');
        }

        if (! $order->status->canTransitionTo(OrderStatus::Shipped)) {
            return Response::error("Order is {$order->status->value} and can't be marked shipped.");
        }

        $this->shipOrder->handle(
            $order,
            ($tracking = trim((string) $request->get('tracking_number'))) !== '' ? $tracking : null,
            ($carrier = trim((string) $request->get('carrier'))) !== '' ? $carrier : null,
        );

        return Response::json([
            'number' => $order->number,
            'status' => $order->refresh()->status->value,
            'tracking_number' => $order->tracking_number,
            'carrier' => $order->carrier,
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
            'order_number' => $schema->string()->description('The order number.')->required(),
            'tracking_number' => $schema->string()->description('Optional tracking number for the dispatch email.'),
            'carrier' => $schema->string()->description('Optional carrier name, e.g. Royal Mail.'),
        ];
    }
}
