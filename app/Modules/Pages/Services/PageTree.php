<?php

namespace App\Modules\Pages\Services;

use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Models\Space;

/**
 * Loads all live pages of a space once and builds the navigation tree.
 */
final class PageTree
{
    /** @var array<int, array<int, list<array{id: int, title: string, parent_id: int|null}>>> */
    private array $cache = [];

    /**
     * @return list<array{id: int, title: string, parent_id: int|null, children: list<mixed>}>
     */
    public function forSpace(Space $space): array
    {
        $byParent = $this->byParent($space);

        $build = function (int $parentId) use (&$build, $byParent): array {
            return array_map(
                fn (array $node) => [...$node, 'children' => $build($node['id'])],
                $byParent[$parentId] ?? [],
            );
        };

        return $build(0);
    }

    /**
     * Flat list with depth, e.g. for "move to" selects.
     *
     * @return list<array{id: int, title: string, depth: int}>
     */
    public function flat(Space $space): array
    {
        $byParent = $this->byParent($space);
        $result = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$result, $byParent): void {
            foreach ($byParent[$parentId] ?? [] as $node) {
                $result[] = ['id' => $node['id'], 'title' => $node['title'], 'depth' => $depth];
                $walk($node['id'], $depth + 1);
            }
        };
        $walk(0, 0);

        return $result;
    }

    /**
     * @return list<Page> from the root down to the direct parent
     */
    public function ancestors(Page $page): array
    {
        $ancestors = [];
        $current = $page->parent;
        $guard = 0;

        while ($current !== null && $guard++ < 100) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /** @return array<int, list<array{id: int, title: string, parent_id: int|null}>> */
    private function byParent(Space $space): array
    {
        if (isset($this->cache[$space->id])) {
            return $this->cache[$space->id];
        }

        $rows = Page::query()
            ->where('space_id', $space->id)
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'title', 'parent_id']);

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[$row->parent_id ?? 0][] = ['id' => $row->id, 'title' => $row->title, 'parent_id' => $row->parent_id];
        }

        return $this->cache[$space->id] = $byParent;
    }
}
