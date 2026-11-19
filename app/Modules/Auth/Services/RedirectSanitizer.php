<?php

namespace App\Modules\Auth\Services;

/**
 * Only internal, absolute paths are allowed as redirect targets (open redirect).
 */
final class RedirectSanitizer
{
    public static function internalPath(?string $target): ?string
    {
        if ($target === null || $target === '' || strlen($target) > 255) {
            return null;
        }

        // Absolute URLs pointing at our own APP_URL are reduced to their path.
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($target, $appUrl.'/')) {
            $target = substr($target, strlen($appUrl));
        }

        if (! str_starts_with($target, '/')
            || str_starts_with($target, '//')
            || str_contains($target, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $target)
            || parse_url($target, PHP_URL_SCHEME) !== null
            || parse_url($target, PHP_URL_HOST) !== null) {
            return null;
        }

        return $target;
    }
}
