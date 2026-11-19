<?php

namespace App\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Livewire always registers its endpoints below /livewire-<hash>/. The app
 * moves update, upload and preview below /admin (see CoreServiceProvider), so
 * Cloudflare Access on /admin covers them; the default paths are closed here.
 * The JavaScript/CSS asset routes stay public (static files).
 */
class BlockDefaultLivewireEndpoints
{
    public function handle(Request $request, Closure $next): Response
    {
        if (preg_match('#^livewire-[0-9a-f]+/(update|upload-file|preview-file)(/|$)#', $request->path()) === 1) {
            abort(404);
        }

        return $next($request);
    }
}
