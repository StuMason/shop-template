<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentVerification;
use App\Payments\PendingPayment;
use GoCardlessPro\Client;

/**
 * GoCardless Instant Bank Pay via hosted Billing Request Flows: we create a
 * one-off payment request, send the customer to GoCardless' hosted page to
 * authorise it at their bank, and verify the billing request server-side.
 */
class GoCardlessGateway implements PaymentGateway
{
    public function __construct(private readonly Client $client) {}

    public function createPayment(Payment $payment, Order $order, string $returnUrl, string $webhookUrl): PendingPayment
    {
        $billingRequest = $this->client->billingRequests()->create([
            'params' => [
                'payment_request' => [
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'description' => "Order {$order->number}",
                ],
                'metadata' => [
                    'reference' => $payment->idempotency_key,
                    'order_number' => $order->number,
                ],
            ],
            'headers' => [
                'Idempotency-Key' => $payment->idempotency_key,
            ],
        ]);

        $flow = $this->client->billingRequestFlows()->create([
            'params' => [
                'redirect_uri' => $returnUrl,
                'exit_uri' => $returnUrl,
                'prefilled_customer' => [
                    'email' => $order->email,
                ],
                'links' => [
                    'billing_request' => $billingRequest->id,
                ],
            ],
        ]);

        return new PendingPayment(
            redirectUrl: $flow->authorisation_url,
            gatewayIntentId: $billingRequest->id,
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        if ($payment->gateway_intent_id === null) {
            return new PaymentVerification(status: PaymentStatus::Pending);
        }

        $billingRequest = $this->client->billingRequests()->get($payment->gateway_intent_id);

        $status = match ($billingRequest->status) {
            'fulfilled' => PaymentStatus::Succeeded,
            'cancelled' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };

        return new PaymentVerification(
            status: $status,
            gatewayTransactionId: $billingRequest->links->payment_request_payment ?? null,
            raw: (array) ($billingRequest->api_response->body ?? []),
        );
    }
}
