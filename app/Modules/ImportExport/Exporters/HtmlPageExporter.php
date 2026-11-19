<?php

namespace App\Modules\ImportExport\Exporters;

use App\Core\ImportExport\Exporter;
use App\Models\User;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\AttachmentService;
use App\Modules\Pages\Services\MarkdownRenderer;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * A standalone .html file with basic styles. Images that are attachments of
 * the page are embedded as data URIs so the file also works offline.
 */
class HtmlPageExporter implements Exporter
{
    private const MAX_EMBEDDED_BYTES = 10 * 1024 * 1024;

    public function __construct(
        private MarkdownRenderer $markdown,
        private AttachmentService $attachments,
    ) {}

    public function key(): string
    {
        return 'page-html';
    }

    public function label(): string
    {
        return 'This page as HTML (.html)';
    }

    public function scope(): string
    {
        return 'page';
    }

    public function export(User $user, object $subject): Response
    {
        assert($subject instanceof Page);
        $subject->loadMissing('attachments');

        $html = $this->embedImages($this->markdown->toHtml($subject->content), $subject);
        $document = view('export.page-html', ['page' => $subject, 'html' => $html, 'lang' => app()->getLocale()])->render();
        $filename = MarkdownWriter::filename($subject->title).'.html';

        return response($document, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename, 'page.html'),
        ]);
    }

    private function embedImages(string $html, Page $page): string
    {
        $inline = config('knowledgebase.attachments.inline_mimes');
        $byId = $page->attachments->keyBy('id');
        $budget = self::MAX_EMBEDDED_BYTES;

        return preg_replace_callback('#src="/(?:attachments|anhaenge)/(\d+)"#', function ($m) use ($byId, $inline, &$budget) {
            /** @var Attachment|null $attachment */
            $attachment = $byId->get((int) $m[1]);
            if ($attachment === null || ! str_starts_with($attachment->mime, 'image/') || ! in_array($attachment->mime, $inline, true) || $attachment->size > $budget) {
                return $m[0];
            }

            $contents = $this->attachments->read($attachment);
            if ($contents === null) {
                return $m[0];
            }
            $budget -= strlen($contents);

            return 'src="data:'.$attachment->mime.';base64,'.base64_encode($contents).'"';
        }, $html) ?? $html;
    }
}
