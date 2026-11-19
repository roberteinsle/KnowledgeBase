<?php

namespace App\Modules\Pages\Support;

use Illuminate\Support\Str;

/**
 * Readable page URLs: /<space-slug>/<title-slug>-<id>. The id at the end is what
 * counts; space and title part are corrected by a redirect when they are stale.
 * /pages/<id> stays as permanent link (used in stored content and copied links
 * that must survive renames).
 */
final class PageUrl
{
    public static function slug(int $id, string $title): string
    {
        $slug = Str::limit(Str::slug($title, '-', 'de'), 80, '');
        $slug = trim($slug, '-');

        return $slug === '' ? (string) $id : $slug.'-'.$id;
    }

    public static function to(string $spaceSlug, int $id, string $title): string
    {
        return route('pages.view', ['spaceSlug' => $spaceSlug, 'pageSlug' => self::slug($id, $title)]);
    }

    /** Page id from the last URL segment ("onboarding-guide-12" → 12). */
    public static function idFrom(string $pageSlug): ?int
    {
        return preg_match('/(?:^|-)(\d+)$/', $pageSlug, $m) === 1 ? (int) $m[1] : null;
    }
}
