<?php

use App\Payments\X402\PayAiAuthenticator;

/**
 * Build a PayAI-format secret (payai_sk_ + base64 of a 48-byte PKCS#8/DER
 * Ed25519 key) from a known 32-byte seed, so we can verify the minted JWT.
 */
function payAiSecretFromSeed(string $seed): string
{
    // Fixed PKCS#8 v1 prefix for an Ed25519 private key, then the 32-byte seed.
    $der = hex2bin('302e020100300506032b657004220420').$seed;

    return 'payai_sk_'.base64_encode($der);
}

function base64UrlDecode(string $value): string
{
    return (string) base64_decode(strtr($value, '-_', '+/'), true);
}

it('mints an EdDSA JWT the facilitator can verify', function () {
    $keypair = sodium_crypto_sign_keypair();
    $seed = substr(sodium_crypto_sign_secretkey($keypair), 0, 32);
    $publicKey = sodium_crypto_sign_publickey($keypair);

    $jwt = (new PayAiAuthenticator('key-123', payAiSecretFromSeed($seed)))->bearerToken();

    [$header64, $payload64, $signature64] = explode('.', $jwt);

    // The signature covers "header.payload" and verifies against the public key.
    $signatureValid = sodium_crypto_sign_verify_detached(
        base64UrlDecode($signature64),
        "{$header64}.{$payload64}",
        $publicKey,
    );

    expect($signatureValid)->toBeTrue();

    $header = json_decode(base64UrlDecode($header64), true);
    $payload = json_decode(base64UrlDecode($payload64), true);

    expect($header)->toMatchArray(['alg' => 'EdDSA', 'typ' => 'JWT', 'kid' => 'key-123']);
    expect($payload)->toMatchArray(['sub' => 'key-123', 'iss' => 'payai-merchant']);
    expect($payload['exp'])->toBe($payload['iat'] + 120);
});

it('tolerates a secret without the payai_sk_ prefix', function () {
    $keypair = sodium_crypto_sign_keypair();
    $seed = substr(sodium_crypto_sign_secretkey($keypair), 0, 32);

    $unprefixed = str_replace('payai_sk_', '', payAiSecretFromSeed($seed));

    $jwt = (new PayAiAuthenticator('key-123', $unprefixed))->bearerToken();

    expect(substr_count($jwt, '.'))->toBe(2);
});

it('throws on a malformed secret', function () {
    (new PayAiAuthenticator('key-123', 'payai_sk_not-base64-!!'))->bearerToken();
})->throws(RuntimeException::class);
