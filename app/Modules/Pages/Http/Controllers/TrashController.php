<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TrashController
{
    public function index(Space $space, PageTree $tree): View
    {
        Gate::authorize('moderate', $space);

        return view('pages.trash', [
            'space' => $space,
            'tree' => $tree->forSpace($space),
            'pages' => Page::onlyTrashed()->where('space_id', $space->id)->latest('deleted_at')->paginate(50),
        ]);
    }

    public function restore(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        abort_unless($page->trashed(), 404);
        Gate::authorize('restore', $page);

        $pages->restore($page, $request->user());

        return redirect()->route('pages.show', $page)->with('status', __('Page restored.'));
    }

    public function destroy(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('forceDelete', $page);

        $space = $page->space;
        $pages->destroy($page, $request->user());

        return redirect()->route('trash.index', $space)->with('status', __('Page deleted permanently.'));
    }
}
