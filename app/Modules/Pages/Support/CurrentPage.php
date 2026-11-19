<?php

namespace App\Modules\Pages\Support;

use App\Modules\Pages\Models\Page;
use Illuminate\Http\Request;

/**
 * The page being looked at: bound as {page} on /pages/{page}/… routes, or set
 * by PageController::view() for readable URLs.
 */
final class CurrentPage
{
    private const ATTRIBUTE = 'pages.current';

    public static function set(Request $request, Page $page): void
    {
        $request->attributes->set(self::ATTRIBUTE, $page);
    }

    public static function from(Request $request): ?Page
    {
        $page = $request->attributes->get(self::ATTRIBUTE) ?? $request->route('page');

        return $page instanceof Page ? $page : null;
    }
}
