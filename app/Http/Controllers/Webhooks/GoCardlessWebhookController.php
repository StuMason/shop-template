<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\PaymentManager;
use GoCardlessPro\Core\Exception\InvalidSignatureException;
use GoCardlessPro\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GoCardless webhooks ARE signed (Webhook-Signature header, HMAC-SHA256), so
 * the signature is verified first. The event body is still treated as a
 * trigger only — a verify() API lookback decides the payment's fate.
 */
class GoCardlessWebhookController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PaymentManager $payments, MarkOrderPaid $markOrderPaid): JsonResponse
    {
        try {
            $events = Webhook::parse(
                $request->getContent(),
                $request->header('Webhook-Signature', ''),
                (string) config('services.gocardless.webhook_secret'),
            );
        } catch (InvalidSignatureException) {
            return response()->json(['error' => 'Invalid signature.'], 498);
        }

        foreach ($events as $event) {
            $billingRequestId = $event->links->billing_request ?? null;

            if ($billingRequestId === null) {
                continue;
            }

            $payment = Payment::query()->firstWhere('gateway_intent_id', $billingRequestId);

            if ($payment === null || $payment->isSettled()) {
                continue;
            }

            $verification = $payments->driver($payment->gateway)->verify($payment);

            if ($verification->status === PaymentStatus::Succeeded) {
                $markOrderPaid->handle($payment->order, $payment, $verification->gatewayTransactionId);
            } elseif ($verification->status === PaymentStatus::Failed) {
                $payment->update(['status' => PaymentStatus::Failed]);
            }
        }

        return response()->json(['ok' => true]);
    }
}
