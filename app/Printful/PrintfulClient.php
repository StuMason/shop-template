<?php

namespace App\Printful;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the Printful v1 REST API. Fulfilment is optional: with
 * no token the shop falls back to manual fulfilment (admin marks shipped).
 */
class PrintfulClient
{
    private readonly ?string $token;

    private readonly ?string $storeId;

    public function __construct()
    {
        $this->token = config('services.printful.token');
        $this->storeId = config('services.printful.store_id');
    }

    public function enabled(): bool
    {
        return filled($this->token);
    }

    /**
     * Create a fulfilment order. With $confirm the order is submitted for
     * printing + shipping; without it, it's a draft to review in Printful.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOrder(array $payload, bool $confirm): array
    {
        return $this->http()
            ->post('/orders'.($confirm ? '?confirm=1' : ''), $payload)
            ->throw()
            ->json('result', []);
    }

    private function http(): PendingRequest
    {
        $request = Http::baseUrl('https://api.printful.com')
            ->timeout(30)
            ->connectTimeout(5)
            ->retry(2, 2000, throw: false)
            ->withToken((string) $this->token);

        if (filled($this->storeId)) {
            $request = $request->withHeaders(['X-PF-Store-Id' => (string) $this->storeId]);
        }

        return $request;
    }
}
