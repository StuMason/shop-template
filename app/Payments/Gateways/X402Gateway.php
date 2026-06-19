<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\FacilitatorAuthenticator;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentVerification;
use App\Payments\PendingPayment;
use Illuminate\Http\Client\PendingRequest;
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
     * USDC per network: the contract address (6 decimal places) plus the
     * EIP-712 domain name and version the token uses for
     * transferWithAuthorization. The domain name MUST match the on-chain
     * contract or the signature recovers to the wrong address and settlement
     * fails — base mainnet USDC is "USD Coin", testnet USDC is "USDC".
     *
     * @var array<string, array{address: string, name: string, version: string}>
     */
    private const ASSETS = [
        'base' => [
            'address' => '0x833589fCD6eDb6E08f4c7C32D4f71b54bdA02913',
            'name' => 'USD Coin',
            'version' => '2',
        ],
        'base-sepolia' => [
            'address' => '0x036CbD53842c5426634e7929541eC2318f3dCF7e',
            'name' => 'USDC',
            'version' => '2',
        ],
    ];

    public function __construct(
        private readonly string $facilitatorUrl,
        private readonly string $payTo,
        private readonly string $network,
        private readonly float $fxRate,
        private readonly ?FacilitatorAuthenticator $authenticator = null,
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

        $body = [
            'x402Version' => 1,
            'paymentPayload' => $payload,
            'paymentRequirements' => $requirements,
        ];

        $verify = $this->facilitator(timeout: 10)
            ->post("{$this->facilitatorUrl}/verify", $body);

        if ($verify->failed() || $verify->json('isValid') !== true) {
            return new PaymentVerification(
                status: PaymentStatus::Failed,
                raw: ['verify' => $verify->json() ?? []],
            );
        }

        $settle = $this->facilitator(timeout: 30)
            ->post("{$this->facilitatorUrl}/settle", $body);

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
     * A facilitator HTTP client, carrying a fresh bearer token when the
     * facilitator requires authentication (e.g. PayAI). Keyless facilitators
     * get no Authorization header.
     */
    private function facilitator(int $timeout): PendingRequest
    {
        $request = Http::timeout($timeout)->connectTimeout(3);

        if ($this->authenticator !== null) {
            $request = $request->withToken($this->authenticator->bearerToken());
        }

        return $request;
    }

    /**
     * The x402 payment requirements for an order payment — also what the
     * 402 response advertises in `accepts`.
     *
     * @return array<string, mixed>
     */
    public function requirementsFor(Payment $payment): array
    {
        $asset = self::ASSETS[$this->network] ?? self::ASSETS['base'];

        return [
            'scheme' => 'exact',
            'network' => $this->network,
            'maxAmountRequired' => (string) $this->atomicUsdcAmount($payment->amount),
            'resource' => URL::signedRoute('agent.pay.x402', ['order' => $payment->order]),
            'description' => "Order {$payment->order->number}",
            'mimeType' => 'application/json',
            'payTo' => $this->payTo,
            'maxTimeoutSeconds' => 300,
            'asset' => $asset['address'],
            'extra' => ['name' => $asset['name'], 'version' => $asset['version']],
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
     * A human-facing USDC quote for an order total: the atomic amount the
     * wallet must authorise, plus a formatted USD label for the pay button.
     *
     * @return array{atomic: string, usd: string}
     */
    public function quote(int $minorUnits): array
    {
        $atomic = $this->atomicUsdcAmount($minorUnits);

        return [
            'atomic' => (string) $atomic,
            'usd' => '$'.number_format($atomic / 1_000_000, 2),
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
