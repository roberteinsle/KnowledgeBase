<?php

namespace App\Modules\Pages\Mcp;

use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use Laravel\Mcp\Request;

/**
 * Shared lookups for the page tools. Pages and spaces the user cannot see are
 * reported as "not found", never as "forbidden", so nothing leaks.
 */
trait PageToolSupport
{
    protected function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_active, 403);

        return $user;
    }

    protected function findPage(User $user, int $id, SpaceRole $minimum = SpaceRole::Reader): ?Page
    {
        $page = Page::query()->with('space')->find($id);

        return $page !== null && app(SpaceAccess::class)->allows($user, $page->space, $minimum) ? $page : null;
    }

    protected function findSpace(User $user, string $slug, SpaceRole $minimum = SpaceRole::Reader): ?Space
    {
        $space = Space::query()->where('slug', $slug)->first();

        return $space !== null && app(SpaceAccess::class)->allows($user, $space, $minimum) ? $space : null;
    }

    /** @return array<string, mixed> */
    protected function summary(Page $page): array
    {
        return [
            'id' => $page->id,
            'title' => $page->title,
            'space' => $page->space->slug,
            'parent_id' => $page->parent_id,
            'version' => $page->version,
            'url' => $page->url(),
        ];
    }
}
