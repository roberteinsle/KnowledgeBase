<?php

namespace App\Core\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * Generated avatars (initials or an emoji on a coloured square) and the
 * delivery of uploaded avatar images. Used for users and spaces, so both look
 * the same everywhere. Served from the app's own routes – no data: URIs, no
 * external avatar services.
 */
final class Avatar
{
    public const RASTER_MIMES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    private const COLORS = ['#b91c1c', '#7e22ce', '#4338ca', '#0369a1', '#0f766e', '#047857', '#b45309', '#be123c', '#334155'];

    /** "Erika Mustermann" → "EM", "Human Resources" → "HR", "Handbuch" → "HA" */
    public static function initials(string $name): string
    {
        $words = preg_split('/[\s\-_]+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];
        $letters = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($letters);
    }

    public static function color(string $seed): string
    {
        return self::COLORS[crc32($seed) % count(self::COLORS)];
    }

    /** @param string|null $symbol an emoji to show instead of the initials */
    public static function svg(string $name, ?string $symbol = null): string
    {
        $text = $symbol !== null && $symbol !== '' ? $symbol : self::initials($name);
        $size = $symbol !== null && $symbol !== '' ? 38 : 26;
        $background = $symbol !== null && $symbol !== '' ? '#f1f5f9' : self::color($name);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">'
            .'<rect width="64" height="64" fill="'.$background.'"/>'
            .'<text x="50%" y="50%" dy=".36em" text-anchor="middle" font-family="system-ui, -apple-system, Segoe UI, Roboto, sans-serif" font-weight="600" font-size="'.$size.'" fill="#fff">'
            .htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';
    }

    public static function svgResponse(string $name, ?string $symbol = null): Response
    {
        return response(self::svg($name, $symbol), 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Uploaded image, but only if its content really is a raster image –
     * anything else (e.g. an SVG with scripts) is never delivered.
     */
    public static function imageResponse(?string $contents): ?Response
    {
        if ($contents === null || $contents === '') {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        if (! in_array($mime, self::RASTER_MIMES, true)) {
            return null;
        }

        return response($contents, 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
