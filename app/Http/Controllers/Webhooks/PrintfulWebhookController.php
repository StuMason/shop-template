<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Orders\ShipOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Printful fulfilment webhook. Printful doesn't sign its webhooks, so the
 * endpoint is gated by a shared secret in the URL (?token=...). The shipment
 * event marks our order shipped (with the carrier's tracking) via the same
 * ShipOrder action the admin uses — so the customer gets the dispatch email.
 */
class PrintfulWebhookController extends Controller
{
    public function __invoke(Request $request, ShipOrder $shipOrder): JsonResponse
    {
        $secret = (string) config('services.printful.webhook_secret');

        abort_if($secret === '', 404);
        abort_unless(hash_equals($secret, (string) $request->query('token')), 401);

        if ($request->input('type') === 'package_shipped') {
            $this->handleShipment($request, $shipOrder);
        }

        // Always 200 so Printful doesn't retry events we don't act on.
        return response()->json(['received' => true]);
    }

    private function handleShipment(Request $request, ShipOrder $shipOrder): void
    {
        $printfulOrderId = $request->input('data.order.id');
        $externalId = $request->input('data.order.external_id');

        $order = Order::query()
            ->when($printfulOrderId !== null, fn ($query) => $query->where('printful_order_id', $printfulOrderId))
            ->when($printfulOrderId === null, fn ($query) => $query->where('number', $externalId))
            ->first();

        if ($order === null || ! $order->status->canTransitionTo(OrderStatus::Shipped)) {
            return;
        }

        $shipOrder->handle(
            $order,
            $request->input('data.shipment.tracking_number'),
            $request->input('data.shipment.carrier'),
        );
    }
}
