<?php

namespace App\Modules\Spaces\Http\Controllers;

use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceController
{
    public function index(Request $request, SpaceAccess $access): View
    {
        $spaces = Space::query()
            ->whereIn('id', $access->spaceIdsFor($request->user()))
            ->orderByRaw('lower(name)')->orderBy('id')
            ->get();

        return view('spaces.index', ['spaces' => $spaces]);
    }
}
