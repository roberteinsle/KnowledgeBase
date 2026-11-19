<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Drag & drop in the sidebar tree.
 */
class PageTreeController
{
    public function place(Request $request, Page $page, PageService $pages): JsonResponse
    {
        Gate::authorize('update', $page);

        $data = $request->validate([
            'target_id' => ['required', 'integer'],
            'placement' => ['required', 'in:before,after,inside'],
        ]);

        $target = Page::query()->where('space_id', $page->space_id)->findOrFail($data['target_id']);
        $pages->place($page, $target, $data['placement'], $request->user());

        return response()->json(['ok' => true, 'parent_id' => $page->parent_id]);
    }
}
