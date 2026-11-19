<?php

namespace App\Modules\Pages\Services;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;

final class VersionDiff
{
    /**
     * Line diff as a list of [type, text]; type is 'add', 'remove' or 'same'.
     * Rendered in Blade with {{ }}, so the text is always escaped.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function lines(string $old, string $new): array
    {
        $differ = new Differ(new DiffOnlyOutputBuilder(''));
        $result = [];

        foreach ($differ->diffToArray($old, $new) as [$text, $type]) {
            if (! in_array($type, [Differ::OLD, Differ::ADDED, Differ::REMOVED], true)) {
                continue; // line-ending warnings
            }

            $result[] = [
                match ($type) {
                    Differ::ADDED => 'add',
                    Differ::REMOVED => 'remove',
                    default => 'same',
                },
                rtrim((string) $text, "\n"),
            ];
        }

        return $result;
    }
}
