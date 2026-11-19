<?php

namespace App\Modules\Spaces\Sidebar;

use App\Core\Navigation\MenuRegistry;
use App\Core\Sidebar\SidebarSection;
use App\Models\User;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Illuminate\Contracts\View\View;

class SpaceSwitcherSection implements SidebarSection
{
    public function __construct(private MenuRegistry $menus) {}

    public function key(): string
    {
        return 'space-switcher';
    }

    public function order(): int
    {
        return 10;
    }

    public function render(User $user): ?View
    {
        // Resolved per render: the current space is request state.
        $space = app(CurrentSpace::class)->for($user);

        return view('spaces.sidebar', [
            'current' => $space,
            'spaces' => Space::query()->whereIn('id', app(SpaceAccess::class)->spaceIdsFor($user))->orderBy('name')->get(),
            'links' => $space ? $this->menus->for('space', $user, $space) : [],
        ]);
    }
}
