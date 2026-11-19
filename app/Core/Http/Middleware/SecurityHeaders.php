<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Alpine/Livewire (Filament) evaluate expressions at runtime, hence
        // 'unsafe-eval'; everything is served from our own origin.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            // FilePond (Filament uploads) processes images in a worker created from a blob: URL.
            "worker-src 'self' blob:",
            "font-src 'self' data:",
            "connect-src 'self'".(app()->isLocal() ? ' ws: wss: http://localhost:5173 http://127.0.0.1:5173' : ''),
            "frame-ancestors 'none'",
            "form-action 'self'".$this->oauthCallbackOrigin($request),
            "base-uri 'self'",
            "object-src 'none'",
        ]);

        if (! app()->isLocal()) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'same-origin');
        }
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * On the OAuth consent page the approve/deny forms answer with a redirect
     * to the client's callback; browsers apply form-action to that redirect.
     * Passport has validated redirect_uri against the registered client before
     * the consent form is rendered.
     */
    private function oauthCallbackOrigin(Request $request): string
    {
        if (! $request->routeIs('passport.authorizations.authorize')) {
            return '';
        }

        $uri = (string) $request->query('redirect_uri');
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        $host = parse_url($uri, PHP_URL_HOST);
        $port = parse_url($uri, PHP_URL_PORT);

        if (! in_array($scheme, ['https', 'http'], true) || ! is_string($host) || preg_match('/^[a-z0-9.\-]+$/i', $host) !== 1) {
            return '';
        }

        return ' '.$scheme.'://'.$host.($port ? ':'.$port : '');
    }
}
