<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\FakeGateway;
use App\Payments\Gateways\GoCardlessGateway;
use GoCardlessPro\Client;
use GoCardlessPro\Environment;
use Illuminate\Support\Manager;

/**
 * Resolves the configured payment gateway. Add a provider by writing a
 * driver class and a create{Name}Driver method; select it with the
 * PAYMENT_GATEWAY env var.
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) config('payments.default', 'fake');
    }

    public function createX402Driver(): PaymentGateway
    {
        return new Gateways\X402Gateway(
            facilitatorUrl: rtrim((string) $this->config->get('services.x402.facilitator_url'), '/'),
            payTo: (string) $this->config->get('services.x402.pay_to'),
            network: (string) $this->config->get('services.x402.network'),
            fxRate: (float) $this->config->get('services.x402.fx_rate'),
        );
    }

    /**
     * Whether the agent stablecoin rail runs alongside the human gateway.
     */
    public function x402Enabled(): bool
    {
        return (bool) $this->config->get('services.x402.enabled')
            && (string) $this->config->get('services.x402.pay_to') !== '';
    }

    public function createFakeDriver(): PaymentGateway
    {
        return new FakeGateway;
    }

    public function createGocardlessDriver(): PaymentGateway
    {
        return new GoCardlessGateway(new Client([
            'access_token' => (string) config('services.gocardless.access_token'),
            'environment' => config('services.gocardless.environment') === 'live'
                ? Environment::LIVE
                : Environment::SANDBOX,
        ]));
    }
}
