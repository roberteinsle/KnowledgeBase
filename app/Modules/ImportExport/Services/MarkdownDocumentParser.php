<?php

namespace App\Modules\ImportExport\Services;

use App\Core\ImportExport\ImportedDocument;
use Illuminate\Support\Str;

/**
 * Turns one Markdown text into an ImportedDocument:
 * front matter (title, tags/labels) → first "# " heading → file name.
 */
final class MarkdownDocumentParser
{
    public function parse(string $markdown, ?string $fallbackTitle = null, ?string $sourcePath = null): ImportedDocument
    {
        $markdown = $this->toUtf8($markdown);
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = preg_replace('/^\xEF\xBB\xBF/', '', $markdown) ?? $markdown;

        [$meta, $body] = $this->frontMatter($markdown);

        $title = isset($meta['title']) && is_string($meta['title']) ? trim($meta['title']) : '';

        if ($title === '' && preg_match('/\A\s*#\s+(.+?)\s*#*\s*(\n|\z)/', $body, $m) === 1) {
            $title = trim($m[1]);
            $body = ltrim(substr($body, strlen($m[0])), "\n");
        }

        if ($title === '') {
            $title = $fallbackTitle !== null && $fallbackTitle !== '' ? $fallbackTitle : (string) __('Untitled');
        }

        $labels = $meta['labels'] ?? $meta['tags'] ?? [];
        if (is_string($labels)) {
            $labels = explode(',', $labels);
        }

        return new ImportedDocument(
            title: mb_substr($title, 0, 255),
            markdown: $body,
            labels: array_values(array_filter(array_map(fn ($l) => is_string($l) ? trim($l, " \t\"'#") : '', $labels))),
            sourcePath: $sourcePath,
        );
    }

    /** "Meeting notes 3f2a…(32 hex).md" (Notion export) → "Meeting notes" */
    public static function titleFromFilename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/\s+[0-9a-f]{32}$/i', '', $name) ?? $name;

        return trim(str_replace('_', ' ', $name));
    }

    /**
     * Minimal YAML front matter: "key: value" and "- item" lists, enough for
     * title/tags from Obsidian, Jekyll, Hugo and our own export.
     *
     * @return array{0: array<string, mixed>, 1: string}
     */
    private function frontMatter(string $markdown): array
    {
        if (preg_match('/\A---\n(.*?)\n---\n?/s', $markdown, $m) !== 1) {
            return [[], $markdown];
        }

        $meta = [];
        $currentKey = null;

        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^\s*-\s+(.*)$/', $line, $item) === 1 && $currentKey !== null) {
                $meta[$currentKey] = [...(array) ($meta[$currentKey] ?? []), $this->scalar($item[1])];

                continue;
            }

            if (preg_match('/^([A-Za-z_][\w-]*)\s*:\s*(.*)$/', $line, $kv) === 1) {
                $currentKey = Str::lower($kv[1]);
                $value = trim($kv[2]);

                if ($value === '') {
                    $meta[$currentKey] = [];
                } elseif (preg_match('/^\[(.*)\]$/', $value, $list) === 1) {
                    $meta[$currentKey] = array_map(fn ($v) => $this->scalar($v), explode(',', $list[1]));
                } else {
                    $meta[$currentKey] = $this->scalar($value);
                }
            }
        }

        return [$meta, substr($markdown, strlen($m[0]))];
    }

    private function scalar(string $value): string
    {
        return trim(trim($value), "\"'");
    }

    private function toUtf8(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        return mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    }
}
