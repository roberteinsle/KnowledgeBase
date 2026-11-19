<?php

namespace App\Modules\ImportExport\Importers;

use App\Core\ImportExport\ImportBundle;
use App\Core\ImportExport\ImportedDocument;
use App\Core\ImportExport\Importer;
use App\Modules\ImportExport\Services\MarkdownDocumentParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

/**
 * Folder structure becomes the page tree. "Topic.md" next to a folder
 * "Topic/" (our export, Notion) or "Topic/index.md"/"README.md" holds the
 * content of the parent page. Other files become attachments.
 */
class ZipImporter implements Importer
{
    private const MARKDOWN = ['md', 'markdown'];

    private const INDEX_NAMES = ['index', 'readme', '_index'];

    public function __construct(private MarkdownDocumentParser $parser) {}

    public function key(): string
    {
        return 'zip';
    }

    public function label(): string
    {
        return 'ZIP archive with Markdown files (Obsidian, Notion, export)';
    }

    public function extensions(): array
    {
        return ['zip'];
    }

    public function read(UploadedFile $file): ImportBundle
    {
        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath(), ZipArchive::RDONLY) !== true) {
            throw ValidationException::withMessages(['file' => __('The archive could not be opened.')]);
        }

        $maxBytes = (int) config('knowledgebase.import.max_uncompressed_mb') * 1024 * 1024;
        $maxDocuments = (int) config('knowledgebase.import.max_documents');
        $total = 0;
        $markdown = [];
        $assets = [];
        $warnings = [];

        try {
            if ($zip->numFiles > $maxDocuments * 4) {
                throw ValidationException::withMessages(['file' => __('The archive contains too many files.')]);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if ($stat === false) {
                    continue;
                }

                $path = $this->safePath($stat['name']);
                if ($path === null || str_ends_with($stat['name'], '/')) {
                    continue;
                }

                // Zip bombs: check declared size and count what is actually read.
                $total += (int) $stat['size'];
                if ($total > $maxBytes) {
                    throw ValidationException::withMessages(['file' => __('The archive is too large when unpacked.')]);
                }

                // Read at most the declared size; their sum is capped above.
                $size = (int) $stat['size'];
                $contents = $size === 0 ? '' : $zip->getFromIndex($i, $size);
                if ($contents === false) {
                    $warnings[] = __('Skipped unreadable file :file.', ['file' => $path]);

                    continue;
                }

                if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::MARKDOWN, true)) {
                    $markdown[$path] = $contents;
                } else {
                    $assets[$path] = $contents;
                }
            }
        } finally {
            $zip->close();
        }

        if (count($markdown) > $maxDocuments) {
            throw ValidationException::withMessages(['file' => __('The archive contains more than :max documents.', ['max' => $maxDocuments])]);
        }

        if ($markdown === []) {
            throw ValidationException::withMessages(['file' => __('The archive contains no Markdown files.')]);
        }

        return new ImportBundle($this->buildTree($markdown), $assets, $warnings);
    }

    /**
     * @param  array<string, string>  $files  path => markdown
     * @return list<ImportedDocument>
     */
    private function buildTree(array $files): array
    {
        $paths = array_keys($files);
        sort($paths, SORT_NATURAL | SORT_FLAG_CASE);

        // Strip a single common root folder ("export/…").
        $prefix = $this->commonRoot($paths);

        /** @var array<string, ImportedDocument> $docs  folder key => document */
        $docs = [];
        $folders = [];

        foreach ($paths as $path) {
            $relative = substr($path, strlen($prefix));
            $dir = dirname($relative) === '.' ? '' : dirname($relative);
            $name = pathinfo($relative, PATHINFO_FILENAME);

            if (in_array(strtolower($name), self::INDEX_NAMES, true) && $dir !== '') {
                $key = $dir; // folder index → content of the folder page
            } else {
                $key = ($dir === '' ? '' : $dir.'/').pathinfo($relative, PATHINFO_FILENAME);
            }

            $fallback = MarkdownDocumentParser::titleFromFilename($key === $dir ? basename($dir) : $relative);
            $docs[$key] = $this->parser->parse($files[$path], $fallback, $path);
            $this->registerFolders($dir, $folders);
        }

        // Folders without own Markdown become empty parent pages.
        foreach (array_keys($folders) as $folder) {
            $docs[$folder] ??= new ImportedDocument(MarkdownDocumentParser::titleFromFilename(basename($folder)), '');
        }

        // Attach every document to its parent folder's document (deepest first).
        $keys = array_keys($docs);
        usort($keys, fn ($a, $b) => substr_count($b, '/') <=> substr_count($a, '/') ?: strnatcasecmp($b, $a));

        $roots = [];
        foreach ($keys as $key) {
            $parentKey = $this->parentKey((string) $key);
            if ($parentKey !== null && isset($docs[$parentKey])) {
                array_unshift($docs[$parentKey]->children, $docs[$key]);
            } else {
                array_unshift($roots, $docs[$key]);
            }
        }

        return $roots;
    }

    /** "a/b/c" → "a/b" ; "a" → null */
    private function parentKey(string $key): ?string
    {
        $dir = dirname($key);

        return $dir === '.' || $dir === '' ? null : $dir;
    }

    /** @param array<string, true> $folders */
    private function registerFolders(string $dir, array &$folders): void
    {
        while ($dir !== '' && $dir !== '.') {
            $folders[$dir] = true;
            $dir = dirname($dir) === '.' ? '' : dirname($dir);
        }
    }

    /** @param list<string> $paths */
    private function commonRoot(array $paths): string
    {
        $first = explode('/', $paths[0])[0];
        if (count(explode('/', $paths[0])) < 2) {
            return '';
        }

        foreach ($paths as $path) {
            if (! str_starts_with($path, $first.'/')) {
                return '';
            }
        }

        return $first.'/';
    }

    /** Rejects traversal, absolute paths and OS metadata. */
    private function safePath(string $name): ?string
    {
        $name = str_replace('\\', '/', $name);

        if ($name === '' || str_starts_with($name, '/') || preg_match('#(^|/)\.\.(/|$)#', $name) || preg_match('/[\x00-\x1F]/', $name)) {
            return null;
        }

        foreach (explode('/', $name) as $segment) {
            if ($segment === '__MACOSX' || str_starts_with($segment, '.')) {
                return null;
            }
        }

        return $name;
    }
}
