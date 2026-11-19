<?php

use App\Core\Http\Middleware\SecurityHeaders;
use App\Core\Http\Middleware\SetLocale;
use App\Modules\Auth\Http\Middleware\EnsureAccountReady;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only reachable through Cloudflare/Coolify's proxy (see handbuch/Betrieb.md),
        // so the forwarded client IP can be trusted for rate limits.
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: [SetLocale::class, 'throttle:web']);
        $middleware->alias(['account' => EnsureAccountReady::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
