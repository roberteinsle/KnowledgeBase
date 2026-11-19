<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Attachment;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController
{
    public function store(Request $request, Page $page, AttachmentService $attachments): RedirectResponse
    {
        Gate::authorize('update', $page);

        $config = config('knowledgebase.attachments');
        $request->validate([
            'files' => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:'.$config['max_kb'], 'mimes:'.implode(',', $config['mimes'])],
        ]);

        foreach ($request->file('files') as $file) {
            $attachments->storeUpload($page, $file, $request->user());
        }

        return back()->with('status', __('Attachment uploaded.'));
    }

    public function show(Attachment $attachment, AttachmentService $attachments): Response
    {
        $page = $attachment->page;
        abort_if($page->trashed(), 404);
        Gate::authorize('view', $page);

        $contents = $attachments->read($attachment);
        abort_if($contents === null, 404);

        // Only PDFs and raster images inline; everything else (SVG, HTML …) as download.
        $inline = in_array($attachment->mime, config('knowledgebase.attachments.inline_mimes'), true);
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $attachment->original_name) ?: 'file';

        return response($contents, 200, [
            'Content-Type' => $inline ? $attachment->mime : 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $attachment->original_name, $fallbackName),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function destroy(Request $request, Attachment $attachment, AttachmentService $attachments): RedirectResponse
    {
        Gate::authorize('update', $attachment->page);

        $attachments->delete($attachment, $request->user());

        return back()->with('status', __('Attachment deleted.'));
    }
}
