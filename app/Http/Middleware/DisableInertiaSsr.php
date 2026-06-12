<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disables Inertia SSR for the current request. Applied to authenticated and
 * noindex surfaces (auth, settings, account, admin, checkout) so server
 * rendering is reserved for the public, indexable storefront.
 */
class DisableInertiaSsr
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        config(['inertia.ssr.enabled' => false]);

        return $next($request);
    }
}
