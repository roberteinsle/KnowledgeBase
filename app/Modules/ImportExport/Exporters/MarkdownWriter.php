<?php

namespace App\Modules\ImportExport\Exporters;

use App\Modules\Pages\Models\Page;

final class MarkdownWriter
{
    /** Front matter + content; re-importable without loss of title and labels. */
    public static function document(Page $page, ?string $content = null): string
    {
        $labels = $page->labels->pluck('name')->all();
        $front = "---\ntitle: ".self::yamlString($page->title)."\n";
        if ($labels !== []) {
            $front .= "tags:\n".implode('', array_map(fn ($l) => '  - '.self::yamlString($l)."\n", $labels));
        }
        $front .= "---\n\n";

        return $front.($content ?? $page->content)."\n";
    }

    public static function filename(string $title): string
    {
        $name = preg_replace('/[\/\\\\:*?"<>|\x00-\x1F]+/u', '-', $title) ?? 'page';
        $name = trim($name, ' .-');

        return mb_substr($name !== '' ? $name : 'page', 0, 120);
    }

    private static function yamlString(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
}
