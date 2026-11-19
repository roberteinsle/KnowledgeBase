<?php

namespace App\Core\Http\Middleware;

use App\Core\Support\Locales;
use App\Modules\Workspace\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Order: user setting → session (guest choice) → browser → workspace default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if (! Locales::isSupported($locale)) {
            $locale = $request->hasSession() ? $request->session()->get('locale') : null;
        }

        if (! Locales::isSupported($locale)) {
            $locale = $request->getPreferredLanguage(Locales::codes());
        }

        if (! Locales::isSupported($locale)) {
            $locale = Workspace::current()->default_locale;
        }

        app()->setLocale(Locales::isSupported($locale) ? $locale : config('app.locale'));

        return $next($request);
    }
}
