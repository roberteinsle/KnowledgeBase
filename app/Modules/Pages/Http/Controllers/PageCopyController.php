<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageCopyController
{
    /** "Duplicate": copy with sub-pages right after the original. */
    public function duplicate(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('update', $page);

        $copy = $pages->duplicate($page, $request->user());

        return redirect()->route('pages.show', $copy)->with('status', __('Page duplicated.'));
    }

    /** "Copy to space": choose a space (and parent) the user may contribute to. */
    public function create(Request $request, Page $page, SpaceAccess $access, PageTree $tree, CurrentSpace $current): View
    {
        Gate::authorize('view', $page);
        $current->set($page->space);

        $spaces = Space::query()
            ->whereIn('id', $access->spaceIdsFor($request->user(), SpaceRole::Editor))
            ->orderBy('name')
            ->get()
            ->map(fn (Space $space) => ['space' => $space, 'pages' => $tree->flat($space)]);

        return view('pages.copy', ['page' => $page, 'targets' => $spaces]);
    }

    public function store(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('view', $page);

        $data = $request->validate(['target' => ['required', 'string', 'regex:/^(space|page):\d+$/']]);
        [$type, $id] = explode(':', $data['target']);

        $parent = $type === 'page' ? Page::query()->findOrFail((int) $id) : null;
        $space = $parent !== null ? $parent->space : Space::query()->findOrFail((int) $id);
        Gate::authorize('contribute', $space);

        $copy = $pages->duplicate($page, $request->user(), $space, $parent);

        return redirect()->route('pages.show', $copy)->with('status', __('Page copied to :space.', ['space' => $space->name]));
    }
}
