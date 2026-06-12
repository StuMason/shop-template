<?php

namespace App\Http\Controllers\Agent;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Gateways\X402Gateway;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The x402 dance for an order: GET without payment returns HTTP 402 with
 * the USDC requirements; retry with an X-PAYMENT header settles through
 * the facilitator (the server-side lookback) and marks the order paid.
 * The URL is signed — it is the agent's capability to pay this order.
 */
class X402PaymentController extends Controller
{
    public function __invoke(
        Request $request,
        Order $order,
        PaymentManager $payments,
        MarkOrderPaid $markOrderPaid,
    ): JsonResponse {
        abort_unless($payments->x402Enabled(), 404);

        if ($order->status !== OrderStatus::Pending) {
            return response()->json([
                'order' => $order->number,
                'status' => $order->status->value,
                'message' => 'This order is not awaiting payment.',
            ]);
        }

        /** @var X402Gateway $gateway */
        $gateway = $payments->driver('x402');

        $payment = $order->payments()->firstOrCreate(
            ['gateway' => 'x402', 'status' => PaymentStatus::Pending],
            [
                'amount' => $order->total,
                'currency' => $order->currency,
                'idempotency_key' => (string) Str::ulid(),
            ],
        );

        $header = (string) $request->header('X-PAYMENT');

        if ($header === '') {
            return $this->paymentRequired($payment, $gateway, 'X-PAYMENT header is required');
        }

        $decoded = json_decode(base64_decode($header, true) ?: '', true);

        if (! is_array($decoded)) {
            return $this->paymentRequired($payment, $gateway, 'X-PAYMENT header must be base64-encoded JSON');
        }

        $payment->update(['gateway_payload' => ['x_payment' => $decoded]]);

        $verification = $gateway->verify($payment);

        if ($verification->status !== PaymentStatus::Succeeded) {
            $payment->update(['status' => PaymentStatus::Failed]);

            // The next attempt creates a fresh pending payment; the
            // requirements (amount, payTo) are identical either way.
            return $this->paymentRequired($payment, $gateway, 'Payment could not be settled');
        }

        $markOrderPaid->handle($order, $payment, $verification->gatewayTransactionId);

        return response()->json([
            'order' => $order->number,
            'status' => $order->refresh()->status->value,
            'transaction' => $verification->gatewayTransactionId,
            'message' => 'Paid. A confirmation email is on its way to the buyer.',
        ]);
    }

    protected function paymentRequired(Payment $payment, X402Gateway $gateway, string $error): JsonResponse
    {
        return response()->json([
            'x402Version' => 1,
            'error' => $error,
            'accepts' => [$gateway->requirementsFor($payment)],
        ], 402);
    }
}
