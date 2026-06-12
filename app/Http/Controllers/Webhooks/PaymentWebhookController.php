<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gateway-agnostic payment webhook. The body is treated as an untrusted
 * trigger only: we look the payment up by our own reference and ask the
 * gateway API for the truth before changing anything.
 */
class PaymentWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PaymentManager $payments, MarkOrderPaid $markOrderPaid): JsonResponse
    {
        $reference = $request->string('reference')->toString();

        $payment = Payment::query()->firstWhere('idempotency_key', $reference);

        if ($payment === null || $payment->isSettled()) {
            return response()->json(['ok' => true]);
        }

        $verification = $payments->driver($payment->gateway)->verify($payment);

        if ($verification->status === PaymentStatus::Succeeded) {
            $markOrderPaid->handle($payment->order, $payment, $verification->gatewayTransactionId);
        } elseif ($verification->status === PaymentStatus::Failed) {
            $payment->update(['status' => PaymentStatus::Failed]);
        }

        return response()->json(['ok' => true]);
    }
}
