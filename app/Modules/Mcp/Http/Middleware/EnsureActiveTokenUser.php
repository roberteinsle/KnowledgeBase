<?php

namespace App\Modules\Mcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Deactivated accounts lose MCP access immediately, even with a valid token. */
class EnsureActiveTokenUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_active) {
            return response()->json(['error' => 'account_inactive'], 403);
        }

        return $next($request);
    }
}
