<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FavoriteController
{
    public function toggle(Request $request, Page $page, FavoriteService $favorites): JsonResponse|RedirectResponse
    {
        Gate::authorize('view', $page);

        $isFavorite = $favorites->toggle($page, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['favorite' => $isFavorite]);
        }

        return back()->with('status', $isFavorite ? __('Added to favorites.') : __('Removed from favorites.'));
    }
}
