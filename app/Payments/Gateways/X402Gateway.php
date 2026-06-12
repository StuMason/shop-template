<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentVerification;
use App\Payments\PendingPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

/**
 * x402: the HTTP 402 stablecoin rail for AI agents. The agent fetches the
 * order's pay endpoint, receives payment requirements (USDC on Base), signs
 * an EIP-3009 transfer, and retries with an X-PAYMENT header. We never touch
 * keys or chains ourselves — the facilitator's verify/settle API is the
 * server-side source of truth, same trust model as every other gateway.
 */
class X402Gateway implements PaymentGateway
{
    /**
     * USDC contract addresses per network (6 decimal places).
     */
    private const ASSETS = [
        'base' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
        'base-sepolia' => '0x036CbD53842c5426634e7929541eC2318f3dCF7e',
    ];

    public function __construct(
        private readonly string $facilitatorUrl,
        private readonly string $payTo,
        private readonly string $network,
        private readonly float $fxRate,
    ) {}

    public function createPayment(Payment $payment, Order $order, string $returnUrl, string $webhookUrl): PendingPayment
    {
        // No hosted page: the "redirect" is the 402 endpoint the agent polls.
        return new PendingPayment(
            redirectUrl: URL::signedRoute('agent.pay.x402', ['order' => $order]),
        );
    }

    public function verify(Payment $payment): PaymentVerification
    {
        $payload = $payment->gateway_payload['x_payment'] ?? null;

        if ($payload === null) {
            // Nothing submitted yet; the abandonment sweep treats this as
            // still-pending until its window closes.
            return new PaymentVerification(status: PaymentStatus::Pending);
        }

        $requirements = $this->requirementsFor($payment);

        $verify = Http::timeout(10)->connectTimeout(3)
            ->post("{$this->facilitatorUrl}/verify", [
                'x402Version' => 1,
                'paymentPayload' => $payload,
                'paymentRequirements' => $requirements,
            ]);

        if ($verify->failed() || $verify->json('isValid') !== true) {
            return new PaymentVerification(
                status: PaymentStatus::Failed,
                raw: ['verify' => $verify->json() ?? []],
            );
        }

        $settle = Http::timeout(30)->connectTimeout(3)
            ->post("{$this->facilitatorUrl}/settle", [
                'x402Version' => 1,
                'paymentPayload' => $payload,
                'paymentRequirements' => $requirements,
            ]);

        if ($settle->failed() || $settle->json('success') !== true) {
            return new PaymentVerification(
                status: PaymentStatus::Failed,
                raw: ['settle' => $settle->json() ?? []],
            );
        }

        return new PaymentVerification(
            status: PaymentStatus::Succeeded,
            gatewayTransactionId: $settle->json('transaction'),
            raw: ['settle' => $settle->json()],
        );
    }

    /**
     * The x402 payment requirements for an order payment — also what the
     * 402 response advertises in `accepts`.
     *
     * @return array<string, mixed>
     */
    public function requirementsFor(Payment $payment): array
    {
        return [
            'scheme' => 'exact',
            'network' => $this->network,
            'maxAmountRequired' => (string) $this->atomicUsdcAmount($payment->amount),
            'resource' => URL::signedRoute('agent.pay.x402', ['order' => $payment->order]),
            'description' => "Order {$payment->order->number}",
            'mimeType' => 'application/json',
            'payTo' => $this->payTo,
            'maxTimeoutSeconds' => 300,
            'asset' => self::ASSETS[$this->network] ?? self::ASSETS['base'],
            'extra' => ['name' => 'USDC', 'version' => '2'],
            // Discovery metadata: the CDP facilitator's Bazaar index catalogs
            // endpoints from settled traffic; these fields describe the shop
            // to agents browsing the index.
            'outputSchema' => [
                'description' => sprintf(
                    '%s — order payment endpoint. Browse the catalogue at %s/llms.txt or via MCP at %s/mcp/shop.',
                    config('app.name'),
                    config('app.url'),
                    config('app.url'),
                ),
            ],
        ];
    }

    /**
     * Order minor units (e.g. pence) -> USDC atomic units (6 dp) via the
     * configured FX rate. A GBP shop sets X402_FX_RATE to GBP->USD.
     */
    private function atomicUsdcAmount(int $minorUnits): int
    {
        return (int) round($minorUnits / 100 * $this->fxRate * 1_000_000);
    }
}
