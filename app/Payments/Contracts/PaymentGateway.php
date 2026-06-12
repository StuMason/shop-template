<?php

namespace App\Payments\Contracts;

use App\Models\Order;
use App\Models\Payment;
use App\Payments\PaymentVerification;
use App\Payments\PendingPayment;

/**
 * The merchant is swappable: implement this contract, register the driver in
 * PaymentManager, set PAYMENT_GATEWAY. Checkout and order code never know
 * which provider is behind it.
 */
interface PaymentGateway
{
    /**
     * Create the payment at the provider and return the customer redirect.
     */
    public function createPayment(Payment $payment, Order $order, string $returnUrl, string $webhookUrl): PendingPayment;

    /**
     * Server-side source of truth for a payment's state.
     */
    public function verify(Payment $payment): PaymentVerification;
}
