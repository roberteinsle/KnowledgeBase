<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Models\PageVersion;
use App\Modules\Pages\Services\MarkdownRenderer;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\VersionDiff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageVersionController
{
    public function index(Page $page): View
    {
        Gate::authorize('view', $page);

        return view('pages.versions', [
            'page' => $page,
            'space' => $page->space,
            'versions' => $page->versions()->with('user')->paginate(30),
        ]);
    }

    public function show(Page $page, int $version, VersionDiff $diff, MarkdownRenderer $markdown): View
    {
        Gate::authorize('view', $page);

        $current = $this->find($page, $version);
        $previous = $page->versions()->where('version', '<', $version)->first();

        return view('pages.version', [
            'page' => $page,
            'space' => $page->space,
            'version' => $current,
            'previous' => $previous,
            'diff' => $diff->lines(
                ($previous->title ?? '')."\n\n".($previous->content ?? ''),
                $current->title."\n\n".$current->content,
            ),
            'html' => $markdown->toHtml($current->content),
        ]);
    }

    public function restore(Request $request, Page $page, int $version, PageService $pages): RedirectResponse
    {
        Gate::authorize('update', $page);

        $pages->restoreVersion($page, $this->find($page, $version), $request->user());

        return redirect()->route('pages.show', $page)->with('status', __('Version :version restored.', ['version' => $version]));
    }

    private function find(Page $page, int $version): PageVersion
    {
        /** @var PageVersion */
        return $page->versions()->where('version', $version)->firstOrFail();
    }
}
