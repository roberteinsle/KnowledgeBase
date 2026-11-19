<?php

namespace App\Modules\ImportExport\Exporters;

use App\Core\ImportExport\Exporter;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use Symfony\Component\HttpFoundation\Response;

class PageTreeZipExporter implements Exporter
{
    public function __construct(private ZipBuilder $zip, private PageService $pages) {}

    public function key(): string
    {
        return 'page-zip';
    }

    public function label(): string
    {
        return 'This page with sub-pages and attachments (.zip)';
    }

    public function scope(): string
    {
        return 'page';
    }

    public function export(User $user, object $subject): Response
    {
        assert($subject instanceof Page);
        $ids = [$subject->id, ...$this->pages->descendantIds($subject)];
        $pages = Page::query()->whereIn('id', $ids)->get();

        return $this->zip->download($pages, [$subject->id], MarkdownWriter::filename($subject->title).'.zip');
    }
}
