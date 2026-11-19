<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageOverview;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SpaceHomeController
{
    public function show(Request $request, Space $space, PageOverview $overview): View
    {
        Gate::authorize('view', $space);

        return view('pages.space', [
            'space' => $space,
            'empty' => ! Page::query()->where('space_id', $space->id)->exists(),
            'lists' => $overview->for($request->user(), [$space->id]),
        ]);
    }
}
