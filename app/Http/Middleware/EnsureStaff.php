<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to admin/staff users regardless of which guard
 * authenticated them (the admin MCP authenticates via Passport).
 */
class EnsureStaff
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('api');

        abort_unless($user !== null && $user->hasAnyRole(['admin', 'staff']), 403);

        return $next($request);
    }
}
