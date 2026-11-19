<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Core\Navigation\MenuRegistry;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\FavoriteService;
use App\Modules\Pages\Services\LabelNormalizer;
use App\Modules\Pages\Services\MarkdownRenderer;
use App\Modules\Pages\Services\PageService;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageController
{
    private const RULES = [
        'title' => ['required', 'string', 'max:255'],
        'content' => ['nullable', 'string', 'max:1000000'],
        'labels' => ['nullable', 'string', 'max:1000'],
    ];

    public function create(Request $request, Space $space): View
    {
        Gate::authorize('contribute', $space);

        $parent = $this->parentFromRequest($request, $space);

        return view('pages.edit', [
            'space' => $space,
            'page' => null,
            'parent' => $parent,
        ]);
    }

    public function store(Request $request, Space $space, PageService $pages): RedirectResponse
    {
        Gate::authorize('contribute', $space);

        $data = $request->validate([...self::RULES, 'parent_id' => ['nullable', 'integer']]);
        $parent = $this->parentFromRequest($request, $space);

        $page = $pages->create(
            $space,
            $parent,
            $data['title'],
            $data['content'] ?? '',
            $request->user(),
            LabelNormalizer::normalize($data['labels'] ?? ''),
        );

        return redirect()->route('pages.show', $page)->with('status', __('Page created.'));
    }

    public function show(Request $request, Page $page, PageTree $tree, MarkdownRenderer $markdown, MenuRegistry $menus, FavoriteService $favorites): View
    {
        Gate::authorize('view', $page);

        $page->load(['labels', 'attachments', 'editor', 'space']);

        return view('pages.show', [
            'page' => $page,
            'space' => $page->space,
            'html' => $markdown->toHtml($page->content),
            'ancestors' => $tree->ancestors($page),
            'children' => $page->children()->get(['id', 'title', 'parent_id']),
            'menu' => $menus->for('page', $request->user(), $page->space),
            'isFavorite' => in_array($page->id, $favorites->pageIdsFor($request->user()), true),
        ]);
    }

    public function edit(Page $page): View
    {
        Gate::authorize('update', $page);

        return view('pages.edit', [
            'space' => $page->space,
            'page' => $page->load('labels'),
            'parent' => $page->parent,
        ]);
    }

    public function update(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('update', $page);

        $data = $request->validate([...self::RULES, 'version' => ['required', 'integer']]);

        // Someone else saved in the meantime: do not silently overwrite.
        if ((int) $data['version'] !== $page->version) {
            return back()->withInput()->withErrors([
                'content' => __('This page was changed by someone else while you were editing. Copy your changes, reload and try again.'),
            ]);
        }

        $pages->update($page, $data['title'], $data['content'] ?? '', $request->user(), LabelNormalizer::normalize($data['labels'] ?? ''));

        return redirect()->route('pages.show', $page)->with('status', __('Page saved.'));
    }

    public function moveForm(Page $page, PageTree $tree, PageService $pages): View
    {
        Gate::authorize('update', $page);

        $excluded = [$page->id, ...$pages->descendantIds($page)];

        return view('pages.move', [
            'page' => $page,
            'space' => $page->space,
            'targets' => array_values(array_filter($tree->flat($page->space), fn ($n) => ! in_array($n['id'], $excluded, true))),
        ]);
    }

    public function move(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('update', $page);

        $data = $request->validate(['parent_id' => ['nullable', 'integer']]);
        $parent = isset($data['parent_id'])
            ? Page::query()->where('space_id', $page->space_id)->findOrFail($data['parent_id'])
            : null;

        $pages->move($page, $parent, $request->user());

        return redirect()->route('pages.show', $page)->with('status', __('Page moved.'));
    }

    public function destroy(Request $request, Page $page, PageService $pages): RedirectResponse
    {
        Gate::authorize('delete', $page);

        $pages->trash($page, $request->user());

        return redirect()->route('spaces.show', $page->space)->with('status', __('Page moved to the trash.'));
    }

    private function parentFromRequest(Request $request, Space $space): ?Page
    {
        $parentId = $request->input('parent_id', $request->query('parent'));

        if ($parentId === null || $parentId === '') {
            return null;
        }

        return Page::query()->where('space_id', $space->id)->findOrFail((int) $parentId);
    }
}
