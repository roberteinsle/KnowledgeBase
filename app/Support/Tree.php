<?php

namespace App\Support;

final class Tree
{
    /**
     * @param  list<array{id: int, children: list<mixed>}>  $nodes
     */
    public static function contains(array $nodes, ?int $id): bool
    {
        if ($id === null) {
            return false;
        }

        foreach ($nodes as $node) {
            if ($node['id'] === $id || self::contains($node['children'], $id)) {
                return true;
            }
        }

        return false;
    }
}
