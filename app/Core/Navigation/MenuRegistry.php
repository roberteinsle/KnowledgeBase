<?php

namespace App\Core\Navigation;

use App\Models\User;

/**
 * Context menus that several modules contribute to. Known menus:
 *  - "space"        links below the space switcher (context: Space)
 *  - "space.pages"  the "…" menu next to "Pages" in the sidebar (context: Space)
 *  - "page"         the "⋮" menu of a page in the tree and on the page (context: Space of the page)
 */
final class MenuRegistry
{
    /** @var array<string, array<string, MenuItem>> */
    private array $menus = [];

    public function add(string $menu, MenuItem $item): void
    {
        $this->menus[$menu][$item->key] = $item;
    }

    /**
     * @return list<MenuItem>
     */
    public function for(string $menu, User $user, object $context): array
    {
        $items = array_filter(
            $this->menus[$menu] ?? [],
            fn (MenuItem $item) => $item->isVisibleFor($user, $context),
        );

        usort($items, fn (MenuItem $a, MenuItem $b) => $a->order <=> $b->order);

        return $items;
    }
}
