<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Checkout\StartPayment;
use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Payments\Gateways\X402Gateway;
use App\Payments\PaymentManager;
use App\Support\ShopSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PaymentController extends Controller
{
    /**
     * Order summary + "Pay with your bank" button. Signed URL so guests
     * (and agent-assisted checkouts) can reach it without an account.
     */
    public function show(Order $order, PaymentManager $payments): Response|RedirectResponse
    {
        if ($order->status !== OrderStatus::Pending) {
            return redirect()->to(URL::signedRoute('checkout.complete', ['order' => $order]));
        }

        return Inertia::render('checkout/pay', [
            'order' => $this->orderPayload($order),
            'payUrl' => route('checkout.pay.start', $order),
            'crypto' => $this->cryptoOption($order, $payments),
        ]);
    }

    /**
     * The "Pay with USDC" option for the pay page: the signed x402 resource a
     * connected wallet pays against, plus what the button needs to show. Null
     * unless x402 is enabled and a WalletConnect project id is configured.
     *
     * @return array<string, string>|null
     */
    private function cryptoOption(Order $order, PaymentManager $payments): ?array
    {
        $projectId = (string) config('services.x402.wallet_connect_project_id');

        if (! $payments->x402Enabled() || $projectId === '') {
            return null;
        }

        /** @var X402Gateway $gateway */
        $gateway = $payments->driver('x402');
        $quote = $gateway->quote($order->total);

        return [
            'payUrl' => URL::signedRoute('agent.pay.x402', ['order' => $order]),
            'confirmUrl' => URL::signedRoute('checkout.complete', ['order' => $order]),
            'projectId' => $projectId,
            'network' => (string) config('services.x402.network'),
            'maxAtomic' => $quote['atomic'],
            'usdLabel' => $quote['usd'],
            'appName' => app(ShopSettings::class)->name(),
        ];
    }

    /**
     * Start the payment at the gateway and send the customer to their bank.
     */
    public function store(Order $order, StartPayment $startPayment): SymfonyResponse
    {
        abort_unless($order->status === OrderStatus::Pending, 409);

        $pending = $startPayment->handle($order);

        return Inertia::location($pending->redirectUrl);
    }

    /**
     * Return from the bank: verify server-side, then show the outcome.
     */
    public function returnFromGateway(Payment $payment, PaymentManager $payments, MarkOrderPaid $markOrderPaid): RedirectResponse
    {
        $order = $payment->order;

        if (! $payment->isSettled()) {
            $verification = $payments->driver($payment->gateway)->verify($payment);

            if ($verification->status === PaymentStatus::Succeeded) {
                $markOrderPaid->handle($order, $payment, $verification->gatewayTransactionId);
            } elseif ($verification->status === PaymentStatus::Failed) {
                $payment->update(['status' => PaymentStatus::Failed]);
            }
        }

        return redirect()->to(URL::signedRoute('checkout.complete', ['order' => $order]));
    }

    /**
     * Confirmation page (also shown for failed/pending outcomes). Pending
     * payments are re-verified on every visit: banks fulfil a beat after
     * authorisation, and the frontend polls this page until settled — so it
     * self-heals even without a webhook (local dev, missed deliveries).
     */
    public function complete(Order $order, PaymentManager $payments, MarkOrderPaid $markOrderPaid): Response
    {
        $order->loadMissing('items', 'latestPayment');

        $payment = $order->latestPayment;

        if ($order->status === OrderStatus::Pending && $payment !== null && ! $payment->isSettled()) {
            $verification = $payments->driver($payment->gateway)->verify($payment);

            if ($verification->status === PaymentStatus::Succeeded) {
                $markOrderPaid->handle($order, $payment, $verification->gatewayTransactionId);
            } elseif ($verification->status === PaymentStatus::Failed) {
                $payment->update(['status' => PaymentStatus::Failed]);
            }

            $order->refresh()->loadMissing('items', 'latestPayment');
        }

        return Inertia::render('checkout/confirmation', [
            'order' => $this->orderPayload($order),
            'paymentStatus' => $order->latestPayment?->status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderPayload(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'number' => $order->number,
            'email' => $order->email,
            'status' => $order->status->value,
            'subtotal' => $order->formattedSubtotal(),
            'discount_total' => $order->discount_total > 0 ? $order->formattedDiscountTotal() : null,
            'discount_code' => $order->discount_code,
            'shipping_total' => $order->formattedShippingTotal(),
            'shipping_method' => $order->shipping_method_name,
            'vat_total' => $order->vat_total > 0 ? $order->formattedVatTotal() : null,
            'vat_number' => $order->vat_total > 0 ? app(ShopSettings::class)->vatNumber() : null,
            'total' => $order->formattedTotal(),
            'shipping_address' => $order->shipping_address,
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'download_url' => $item->is_digital && $order->paid_at !== null
                    ? URL::temporarySignedRoute('orders.download', now()->addDays(30), ['order' => $order, 'item' => $item])
                    : null,
                'id' => $item->id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'line_total' => $item->formattedLineTotal(),
            ])->all(),
        ];
    }
}
