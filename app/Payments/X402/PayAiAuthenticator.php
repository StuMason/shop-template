<?php

namespace App\Payments\X402;

use App\Payments\Contracts\FacilitatorAuthenticator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Authenticates requests to the PayAI x402 facilitator. PayAI expects an
 * EdDSA (Ed25519) JWT in the Authorization header, signed with the merchant's
 * API key secret and tagged with the key id. Tokens are short-lived (120s) and
 * minted per request.
 *
 * The secret is a PKCS#8/DER Ed25519 private key, base64-encoded, with a
 * `payai_sk_` prefix. libsodium signs from the 32-byte seed, which is the tail
 * of the 48-byte DER document.
 */
class PayAiAuthenticator implements FacilitatorAuthenticator
{
    private const SECRET_PREFIX = 'payai_sk_';

    public function __construct(
        private readonly string $keyId,
        private readonly string $keySecret,
    ) {}

    public function bearerToken(): string
    {
        $now = time();

        $header = $this->base64Url((string) json_encode([
            'alg' => 'EdDSA',
            'typ' => 'JWT',
            'kid' => $this->keyId,
        ], JSON_THROW_ON_ERROR));

        $payload = $this->base64Url((string) json_encode([
            'sub' => $this->keyId,
            'iss' => 'payai-merchant',
            'iat' => $now,
            'exp' => $now + 120,
            'jti' => (string) Str::uuid(),
        ], JSON_THROW_ON_ERROR));

        $message = "{$header}.{$payload}";
        $signature = sodium_crypto_sign_detached($message, $this->signingKey());

        return "{$message}.{$this->base64Url($signature)}";
    }

    /**
     * The 64-byte libsodium secret key derived from the PKCS#8 Ed25519 seed.
     *
     * @return non-empty-string
     */
    private function signingKey(): string
    {
        $secret = Str::after($this->keySecret, self::SECRET_PREFIX);
        $der = base64_decode($secret, strict: true);

        if ($der === false || strlen($der) < 32) {
            throw new RuntimeException('PAY_AI_SECRET is not a valid base64 Ed25519 key.');
        }

        // The 32-byte seed is the tail of the 48-byte PKCS#8 DER document.
        $keypair = sodium_crypto_sign_seed_keypair(substr($der, -32));

        return sodium_crypto_sign_secretkey($keypair);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
