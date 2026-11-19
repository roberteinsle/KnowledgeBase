<?php

namespace App\Filament\Concerns;

use App\Models\User;

/**
 * Explicit rights check for every admin resource and page, independent of
 * panel access and of Cloudflare Access in front of /admin.
 */
trait AdminOnly
{
    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->is_active && $user->isAdmin();
    }
}
