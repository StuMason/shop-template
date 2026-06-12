<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    /**
     * The customer's order history.
     */
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->whereBelongsTo($request->user())
            ->latest('placed_at')
            ->paginate(10)
            ->through(fn (Order $order): array => [
                'number' => $order->number,
                'status' => $order->status->value,
                'total' => $order->formattedTotal(),
                'placed_at' => $order->placed_at->format('j M Y'),
            ]);

        return Inertia::render('account/orders/index', [
            'orders' => $orders,
        ]);
    }

    /**
     * A single order, owned by the current user.
     */
    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->loadMissing('items', 'latestPayment');

        return Inertia::render('account/orders/show', [
            'order' => [
                'number' => $order->number,
                'status' => $order->status->value,
                'placed_at' => $order->placed_at->format('j M Y, H:i'),
                'subtotal' => $order->formattedSubtotal(),
                'shipping_total' => $order->formattedShippingTotal(),
                'shipping_method' => $order->shipping_method_name,
                'carrier' => $order->carrier,
                'tracking_number' => $order->tracking_number,
                'vat_total' => $order->vat_total > 0 ? $order->formattedVatTotal() : null,
                'total' => $order->formattedTotal(),
                'shipping_address' => $order->shipping_address,
                'billing_address' => $order->billing_address,
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->formattedUnitPrice(),
                    'line_total' => $item->formattedLineTotal(),
                ])->all(),
            ],
        ]);
    }
}
