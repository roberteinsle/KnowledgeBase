<?php

namespace App\Core\Navigation;

use App\Models\User;

/**
 * Modules add their entries to the main navigation, the account menu and the
 * mobile tab bar here instead of editing the layout.
 */
final class NavigationRegistry
{
    /** @var array<string, NavigationItem> */
    private array $items = [];

    public function add(NavigationItem $item): void
    {
        $this->items[$item->key] = $item;
    }

    /**
     * @return list<NavigationItem>
     */
    public function for(User $user, string $area): array
    {
        $items = array_filter(
            $this->items,
            fn (NavigationItem $item) => $item->area === $area && $item->isVisibleFor($user),
        );

        usort($items, fn (NavigationItem $a, NavigationItem $b) => $a->order <=> $b->order);

        return $items;
    }

    /**
     * @return list<NavigationItem>
     */
    public function mobileTabs(User $user): array
    {
        $items = array_filter(
            $this->items,
            fn (NavigationItem $item) => $item->mobileTab && $item->isVisibleFor($user),
        );

        usort($items, fn (NavigationItem $a, NavigationItem $b) => $a->order <=> $b->order);

        return array_slice($items, 0, 5);
    }
}
