<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentVerification;
use App\Payments\PendingPayment;
use Illuminate\Support\Facades\Cache;

/**
 * In-memory gateway for local development and tests. "Redirects" straight
 * back to the return URL; the outcome can be scripted per payment with
 * FakeGateway::willSucceed() / willFail().
 */
class FakeGateway implements PaymentGateway
{
    public function createPayment(Payment $payment, Order $order, string $returnUrl, string $webhookUrl): PendingPayment
    {
        return new PendingPayment(
            redirectUrl: $returnUrl,
            gatewayIntentId: 'fake_intent_'.$payment->idempotency_key,
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        $status = Cache::get("fake-gateway.{$payment->idempotency_key}", PaymentStatus::Succeeded);

        return new PaymentVerification(
            status: $status,
            gatewayTransactionId: 'fake_txn_'.$payment->idempotency_key,
            raw: ['fake' => true],
        );
    }

    /**
     * Script the outcome of a payment's verification (tests/local dev).
     */
    public static function willReturn(Payment $payment, PaymentStatus $status): void
    {
        Cache::put("fake-gateway.{$payment->idempotency_key}", $status, now()->addHour());
    }
}
