<?php

namespace App\Mcp\Tools;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Full detail for one order: items, addresses, payments, refunds, tracking.')]
class AdminGetOrder extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $order = Order::query()
            ->with(['items', 'payments.refunds'])
            ->firstWhere('number', (string) $request->get('order_number'));

        if ($order === null) {
            return Response::error('No order with that number.');
        }

        return Response::json([
            'number' => $order->number,
            'status' => $order->status->value,
            'email' => $order->email,
            'placed_at' => $order->placed_at->toIso8601String(),
            'subtotal' => $order->formattedSubtotal(),
            'discount' => $order->discount_total > 0 ? $order->formattedDiscountTotal() : null,
            'discount_code' => $order->discount_code,
            'shipping' => $order->formattedShippingTotal(),
            'vat' => $order->vat_total > 0 ? $order->formattedVatTotal() : null,
            'total' => $order->formattedTotal(),
            'shipping_method' => $order->shipping_method_name,
            'carrier' => $order->carrier,
            'tracking_number' => $order->tracking_number,
            'shipping_address' => $order->shipping_address,
            'customer_note' => $order->customer_note,
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'sku' => $item->sku,
                'product' => $item->product_name,
                'variant' => $item->variant_name,
                'quantity' => $item->quantity,
                'line_total' => $item->formattedLineTotal(),
            ]),
            'payments' => $order->payments->map(fn (Payment $payment): array => [
                'gateway' => $payment->gateway,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'refunded' => (int) $payment->refunds->sum('amount'),
            ]),
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
        ];
    }
}
