<?php

namespace App\Core\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Permanent redirects from the former German URL paths to the English ones,
 * so bookmarks, already sent sign-in links and links stored in page content
 * keep working. Each module registers the paths it owns.
 */
final class LegacyRedirects
{
    /**
     * @param  string  $old  former first path segment, e.g. "seiten"
     * @param  string  $new  new first path segment, e.g. "pages"
     * @param  array<string, string>  $segments  translations for further path segments
     * @param  bool  $keepFirst  the first segment after the prefix is a user value (e.g. a space slug)
     */
    public static function register(string $old, string $new, array $segments = [], bool $keepFirst = false): void
    {
        $target = function (Request $request, ?string $rest = null) use ($new, $segments, $keepFirst) {
            $parts = $rest === null || $rest === '' ? [] : explode('/', $rest);
            foreach ($parts as $i => $part) {
                if (! ($keepFirst && $i === 0)) {
                    $parts[$i] = $segments[$part] ?? $part;
                }
            }
            $query = $request->getQueryString();

            return redirect('/'.implode('/', [$new, ...$parts]).($query !== null ? '?'.$query : ''), 301);
        };

        Route::get('/'.$old, fn (Request $request) => $target($request));
        Route::get('/'.$old.'/{rest}', $target)->where('rest', '[A-Za-z0-9_\-/.]+');
    }
}
