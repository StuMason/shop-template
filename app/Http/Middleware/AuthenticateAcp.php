<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the Agentic Commerce Protocol surface. The shop issues an API key
 * to the agent platform (ACP_API_KEY); requests carry it as a Bearer token.
 * When ACP_SIGNATURE_SECRET is also set, the request body must carry a
 * base64 HMAC-SHA256 signature with a fresh RFC 3339 timestamp.
 *
 * Unset ACP_API_KEY = the whole surface 404s (feature off).
 */
class AuthenticateAcp
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) config('services.acp.api_key');

        abort_if($apiKey === '', 404);

        abort_unless(hash_equals($apiKey, (string) $request->bearerToken()), 401);

        $secret = (string) config('services.acp.signature_secret');

        if ($secret !== '') {
            $timestamp = (string) $request->header('Timestamp');
            $signature = (string) $request->header('Signature');

            abort_if($timestamp === '' || $signature === '', 401);

            $requestTime = strtotime($timestamp);
            abort_if($requestTime === false || abs(now()->getTimestamp() - $requestTime) > 300, 401);

            $expected = base64_encode(hash_hmac(
                'sha256',
                $timestamp.'.'.$request->getContent(),
                $secret,
                true,
            ));

            abort_unless(hash_equals($expected, $signature), 401);
        }

        $response = $next($request);

        // ACP requires these echoed back for client-side correlation.
        foreach (['Idempotency-Key', 'Request-Id'] as $header) {
            if ($request->hasHeader($header)) {
                $response->headers->set($header, (string) $request->header($header));
            }
        }

        return $response;
    }
}
