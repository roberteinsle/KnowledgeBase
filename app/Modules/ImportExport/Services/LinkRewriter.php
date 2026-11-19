<?php

namespace App\Modules\ImportExport\Services;

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use Closure;

/**
 * Rewrites relative links of imported Markdown:
 * images/files from the archive → attachment URLs, links to other imported
 * .md files and Obsidian [[wiki links]] → page URLs. External links stay.
 */
final class LinkRewriter
{
    /** @var array<string, Attachment> */
    private array $attached = [];

    /** @var array<string, string> normalised path => original key */
    private array $assetIndex = [];

    /**
     * @param  array<string, string>  $assets
     * @param  array<string, Page>  $pagesBySource
     * @param  array<string, Page>  $pagesByTitle  lower-case title => page
     */
    public function __construct(
        private array $assets,
        private array $pagesBySource,
        private array $pagesByTitle,
    ) {
        $allowed = config('knowledgebase.attachments.mimes');

        foreach (array_keys($assets) as $path) {
            // Only file types that could also be uploaded directly.
            if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $allowed, true)) {
                continue;
            }
            $this->assetIndex[self::normalizePath($path)] = $path;
            $this->assetIndex['@'.mb_strtolower(basename($path))] ??= $path;
        }
    }

    /**
     * @param  Closure(string, string): Attachment  $attach
     */
    public function rewrite(string $markdown, ?string $sourcePath, Closure $attach): string
    {
        $baseDir = $sourcePath !== null && dirname($sourcePath) !== '.' ? dirname($sourcePath) : '';

        // Obsidian embeds ![[file.png]] and wiki links [[Title|Alias]]
        $markdown = preg_replace_callback('/(!?)\[\[([^\]|#\n]+)(?:#[^\]|\n]*)?(?:\|([^\]\n]+))?\]\]/u', function ($m) use ($attach) {
            $target = trim($m[2]);
            $label = isset($m[3]) ? trim($m[3]) : $target;

            if ($m[1] === '!') {
                $asset = $this->assetIndex['@'.mb_strtolower(basename($target))] ?? null;

                return $asset !== null ? '!['.$this->escapeLabel($label).']('.$this->attachmentUrl($asset, $attach).')' : $m[0];
            }

            $page = $this->pagesByTitle[mb_strtolower($target)] ?? null;

            return $page !== null ? '['.$this->escapeLabel($label).']('.route('pages.show', $page, false).')' : $m[0];
        }, $markdown) ?? $markdown;

        // Standard links and images: [text](target "title") / ![alt](<target>)
        return preg_replace_callback('/(!?\[[^\]\n]*\])\(\s*(<[^>\n]+>|[^)\s]+)((?:\s+"[^"\n]*")?)\s*\)/u', function ($m) use ($baseDir, $attach) {
            $target = trim($m[2], '<>');

            if ($target === '' || preg_match('#^([a-z][a-z0-9+.-]*:|//|\#|/)#i', $target)) {
                return $m[0]; // absolute, external, anchors
            }

            $fragment = '';
            if (($pos = strpos($target, '#')) !== false) {
                $fragment = substr($target, $pos);
                $target = substr($target, 0, $pos);
            }
            $target = rawurldecode(strtok($target, '?') ?: $target);
            $resolved = self::normalizePath(($baseDir !== '' ? $baseDir.'/' : '').$target);

            if (isset($this->pagesBySource[$resolved])) {
                return $m[1].'('.route('pages.show', $this->pagesBySource[$resolved], false).$fragment.$m[3].')';
            }

            if (isset($this->assetIndex[$resolved])) {
                return $m[1].'('.$this->attachmentUrl($this->assetIndex[$resolved], $attach).$m[3].')';
            }

            return $m[0];
        }, $markdown) ?? $markdown;
    }

    public static function normalizePath(string $path): string
    {
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);

                continue;
            }
            $parts[] = $segment;
        }

        return mb_strtolower(implode('/', $parts));
    }

    /**
     * @param  Closure(string, string): Attachment  $attach
     */
    private function attachmentUrl(string $assetPath, Closure $attach): string
    {
        $this->attached[$assetPath] ??= $attach($assetPath, $this->assets[$assetPath]);

        return route('attachments.show', $this->attached[$assetPath], false);
    }

    private function escapeLabel(string $label): string
    {
        return str_replace(['[', ']'], ['\[', '\]'], $label);
    }
}
