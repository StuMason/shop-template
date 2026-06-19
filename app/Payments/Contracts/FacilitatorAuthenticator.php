<?php

namespace App\Payments\Contracts;

/**
 * Mints a bearer token for an x402 facilitator that requires authentication
 * (e.g. PayAI). Keyless facilitators (the public x402.org one) need none, so
 * the gateway treats an absent authenticator as "send no Authorization header".
 */
interface FacilitatorAuthenticator
{
    /**
     * A short-lived bearer token for the next facilitator request. Minted
     * per call, since these tokens expire quickly.
     */
    public function bearerToken(): string;
}
