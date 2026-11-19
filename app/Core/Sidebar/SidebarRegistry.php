<?php

namespace App\Core\Sidebar;

use App\Models\User;
use Illuminate\Contracts\View\View;

/**
 * The left sidebar of the app layout is assembled from sections that modules
 * register (space switcher, page tree …).
 */
final class SidebarRegistry
{
    /** @var array<string, SidebarSection> */
    private array $sections = [];

    public function add(SidebarSection $section): void
    {
        $this->sections[$section->key()] = $section;
    }

    /** @return list<View> */
    public function renderFor(User $user): array
    {
        $sections = array_values($this->sections);
        usort($sections, fn (SidebarSection $a, SidebarSection $b) => $a->order() <=> $b->order());

        return array_values(array_filter(array_map(fn (SidebarSection $s) => $s->render($user), $sections)));
    }
}
