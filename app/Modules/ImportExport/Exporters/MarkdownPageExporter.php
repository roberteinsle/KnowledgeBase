<?php

namespace App\Modules\ImportExport\Exporters;

use App\Core\ImportExport\Exporter;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class MarkdownPageExporter implements Exporter
{
    public function key(): string
    {
        return 'page-md';
    }

    public function label(): string
    {
        return 'This page as Markdown (.md)';
    }

    public function scope(): string
    {
        return 'page';
    }

    public function export(User $user, object $subject): Response
    {
        assert($subject instanceof Page);
        $subject->loadMissing('labels');
        $filename = MarkdownWriter::filename($subject->title).'.md';

        return response(MarkdownWriter::document($subject), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, 'page.md'),
        ]);
    }
}
