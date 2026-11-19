<?php

namespace App\Modules\ImportExport\Exporters;

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\AttachmentService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Layout (the same the ZIP importer reads):
 *   Title.md, Title/Child.md … and _attachments/<id>-<name>
 * Links to attachments and exported pages become relative paths.
 */
final class ZipBuilder
{
    /** @var array<int, string> page id => path inside the archive */
    private array $pagePaths = [];

    public function __construct(private AttachmentService $attachments) {}

    /**
     * @param  EloquentCollection<int, Page>  $pages  all pages to export (live only)
     * @param  list<int>  $rootIds
     */
    public function download(EloquentCollection $pages, array $rootIds, string $filename): BinaryFileResponse
    {
        $pages->loadMissing(['labels', 'attachments']);
        $byParent = [];
        foreach ($pages->sortBy('position') as $page) {
            $byParent[$page->parent_id ?? 0][] = $page;
        }

        $roots = $pages->whereIn('id', $rootIds)->sortBy('position')->values()->all();
        $this->assignPaths($roots, '', $byParent);

        $tmp = tempnam(sys_get_temp_dir(), 'kb-export');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);

        foreach ($pages as $page) {
            if (! isset($this->pagePaths[$page->id])) {
                continue;
            }

            $path = $this->pagePaths[$page->id];
            $zip->addFromString($path, MarkdownWriter::document($page, $this->relativeLinks($page, $path, $pages)));

            foreach ($page->attachments as $attachment) {
                $contents = $this->attachments->read($attachment);
                if ($contents !== null) {
                    $zip->addFromString($this->attachmentPath($attachment), $contents);
                }
            }
        }

        if ($zip->numFiles === 0) {
            $zip->addFromString('README.md', "# Export\n\n(empty)\n");
        }
        $zip->close();

        return response()->download($tmp, $filename, ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    /**
     * @param  list<Page>  $level
     * @param  array<int, list<Page>>  $byParent
     */
    private function assignPaths(array $level, string $dir, array $byParent): void
    {
        $used = [];
        foreach ($level as $page) {
            $base = MarkdownWriter::filename($page->title);
            $name = $base;
            $i = 2;
            while (isset($used[mb_strtolower($name)])) {
                $name = $base.' ('.$i++.')';
            }
            $used[mb_strtolower($name)] = true;

            $this->pagePaths[$page->id] = $dir.$name.'.md';

            if (isset($byParent[$page->id])) {
                $this->assignPaths($byParent[$page->id], $dir.$name.'/', $byParent);
            }
        }
    }

    /** @param Collection<int, Page> $pages */
    private function relativeLinks(Page $page, string $path, Collection $pages): string
    {
        $depth = substr_count($path, '/');
        $up = str_repeat('../', $depth);
        $attachmentIds = $pages->flatMap(fn (Page $p) => $p->attachments)->keyBy('id');

        $content = preg_replace_callback('#\((/anhaenge/(\d+))\)#', function ($m) use ($attachmentIds, $up) {
            $attachment = $attachmentIds->get((int) $m[2]);

            return $attachment ? '(<'.$up.$this->attachmentPath($attachment).'>)' : $m[0];
        }, $page->content) ?? $page->content;

        return preg_replace_callback('#\((/seiten/(\d+))([^)\s]*)\)#', function ($m) use ($up) {
            $target = $this->pagePaths[(int) $m[2]] ?? null;

            return $target ? '(<'.$up.$target.'>'.')' : $m[0];
        }, $content) ?? $content;
    }

    private function attachmentPath(Attachment $attachment): string
    {
        return '_attachments/'.$attachment->id.'-'.MarkdownWriter::filename($attachment->original_name);
    }
}
