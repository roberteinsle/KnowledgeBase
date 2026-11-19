<?php

namespace App\Modules\Pages\Sidebar;

use App\Core\Navigation\MenuRegistry;
use App\Core\Sidebar\SidebarSection;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\FavoriteService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Contracts\View\View;

/**
 * "Pages" heading with the space menu, and the page tree with the "⋮" menu
 * and drag & drop for editors.
 */
class PageTreeSection implements SidebarSection
{
    public function __construct(private MenuRegistry $menus) {}

    public function key(): string
    {
        return 'page-tree';
    }

    public function order(): int
    {
        return 20;
    }

    public function render(User $user): ?View
    {
        $space = app(CurrentSpace::class)->for($user);
        if ($space === null) {
            return null;
        }

        $current = request()->route('page');

        return view('pages.sidebar', [
            'space' => $space,
            'tree' => app(PageTree::class)->forSpace($space),
            'currentPageId' => $current instanceof Page && $current->space_id === $space->id ? $current->id : null,
            'favorites' => app(FavoriteService::class)->pageIdsFor($user),
            'canReorder' => app(SpaceAccess::class)->allows($user, $space, SpaceRole::Editor),
            'spaceMenu' => $this->menus->for('space.pages', $user, $space),
            'pageMenu' => $this->menus->for('page', $user, $space),
        ]);
    }
}
