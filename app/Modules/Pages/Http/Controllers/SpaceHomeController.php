<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Core\ImportExport\ExportRegistry;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageTree;
use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SpaceHomeController
{
    public function show(Space $space, PageTree $tree, ExportRegistry $exports): View
    {
        Gate::authorize('view', $space);

        return view('pages.space', [
            'space' => $space,
            'tree' => $tree->forSpace($space),
            'recent' => Page::query()->where('space_id', $space->id)->with('editor')->latest('updated_at')->limit(10)->get(),
            'exporters' => $exports->forScope('space'),
        ]);
    }
}
