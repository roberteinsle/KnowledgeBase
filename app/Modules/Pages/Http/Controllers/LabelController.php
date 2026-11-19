<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Models\Label;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController
{
    public function show(Request $request, Label $label, SpaceAccess $access): View
    {
        $pages = Page::query()
            ->whereIn('space_id', $access->spaceIdsFor($request->user()))
            ->whereHas('labels', fn ($q) => $q->whereKey($label->id))
            ->with('space')
            ->orderBy('title')
            ->paginate(50);

        return view('pages.label', ['label' => $label, 'pages' => $pages]);
    }
}
