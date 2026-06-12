<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\DeliverOrder;
use App\Actions\Orders\MarkOrderPaid;
use App\Actions\Orders\RecordRefund;
use App\Actions\Orders\ShipOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class OrderController extends Controller
{
    /**
     * Order management list.
     */
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();

        $orders = Order::query()
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query
                ->whereLike('number', "%{$search}%", caseSensitive: false)
                ->orWhereLike('email', "%{$search}%", caseSensitive: false))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'email' => $order->email,
                'status' => $order->status->value,
                'total' => $order->formattedTotal(),
                'placed_at' => $order->placed_at->format('j M Y, H:i'),
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => ['status' => $status, 'q' => $search],
            'statuses' => array_map(fn (OrderStatus $case): string => $case->value, OrderStatus::cases()),
        ]);
    }

    /**
     * Order detail with payment history and available transitions.
     */
    public function show(Order $order): Response
    {
        $order->loadMissing('items', 'payments.refunds', 'user');

        return Inertia::render('admin/orders/show', [
            'order' => [
                'id' => $order->id,
                'number' => $order->number,
                'email' => $order->email,
                'customer' => $order->user?->name,
                'status' => $order->status->value,
                'placed_at' => $order->placed_at->format('j M Y, H:i'),
                'customer_note' => $order->customer_note,
                'subtotal' => $order->formattedSubtotal(),
                'shipping_total' => $order->formattedShippingTotal(),
                'shipping_method' => $order->shipping_method_name,
                'carrier' => $order->carrier,
                'tracking_number' => $order->tracking_number,
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
                'payments' => $order->payments->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'gateway' => $payment->gateway,
                    'status' => $payment->status->value,
                    'amount' => $payment->amount,
                    'refunded' => (int) $payment->refunds->sum('amount'),
                    'created_at' => $payment->created_at?->format('j M Y, H:i'),
                ])->all(),
                'available_transitions' => array_values(array_filter(
                    ['paid', 'shipped', 'delivered', 'cancelled'],
                    fn (string $status): bool => $order->status->canTransitionTo(OrderStatus::from($status)),
                )),
            ],
        ]);
    }

    /**
     * Move an order through its lifecycle.
     */
    public function updateStatus(
        Request $request,
        Order $order,
        MarkOrderPaid $markOrderPaid,
        ShipOrder $shipOrder,
        DeliverOrder $deliverOrder,
        CancelOrder $cancelOrder,
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['paid', 'shipped', 'delivered', 'cancelled'])],
            'tracking_number' => ['nullable', 'string', 'max:128'],
            'carrier' => ['nullable', 'string', 'max:64'],
        ]);

        $target = OrderStatus::from($validated['status']);

        if (! $order->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => "Cannot move this order from {$order->status->value} to {$target->value}.",
            ]);
        }

        match ($target) {
            // Manual mark-paid covers bank transfers arranged outside the gateway.
            OrderStatus::Paid => $markOrderPaid->handle(
                $order,
                $order->payments()->firstOrCreate(
                    ['status' => PaymentStatus::Pending],
                    [
                        'gateway' => 'manual',
                        'amount' => $order->total,
                        'currency' => $order->currency,
                        'idempotency_key' => (string) Str::ulid(),
                    ],
                ),
            ),
            OrderStatus::Shipped => $shipOrder->handle(
                $order,
                $validated['tracking_number'] ?? null,
                $validated['carrier'] ?? null,
            ),
            OrderStatus::Delivered => $deliverOrder->handle($order),
            OrderStatus::Cancelled => $cancelOrder->handle($order),
            default => null,
        };

        return back()->with('success', "Order marked {$target->value}.");
    }

    /**
     * Record a manual refund against a payment.
     */
    public function storeRefund(Request $request, Payment $payment, RecordRefund $recordRefund): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $recordRefund->handle($payment, (int) $validated['amount'], $validated['reason'] ?? null, $request->user());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['amount' => $exception->getMessage()]);
        }

        return back()->with('success', 'Refund recorded.');
    }
}
