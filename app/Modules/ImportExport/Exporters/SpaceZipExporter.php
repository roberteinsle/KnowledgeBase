<?php

namespace App\Modules\ImportExport\Exporters;

use App\Core\ImportExport\Exporter;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Models\Space;
use Symfony\Component\HttpFoundation\Response;

class SpaceZipExporter implements Exporter
{
    public function __construct(private ZipBuilder $zip) {}

    public function key(): string
    {
        return 'space-zip';
    }

    public function label(): string
    {
        return 'Whole space as ZIP (Markdown + attachments)';
    }

    public function scope(): string
    {
        return 'space';
    }

    public function export(User $user, object $subject): Response
    {
        assert($subject instanceof Space);
        $pages = Page::query()->where('space_id', $subject->id)->get();
        $roots = $pages->whereNull('parent_id')->pluck('id')->all();

        return $this->zip->download($pages, $roots, MarkdownWriter::filename($subject->name).'-'.now()->format('Y-m-d').'.zip');
    }
}
