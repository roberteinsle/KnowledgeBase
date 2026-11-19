<?php

namespace App\Modules\Search\Http\Controllers;

use App\Core\Search\SearchQuery;
use App\Modules\Search\Services\SearchService;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Models\Space;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController
{
    public function __invoke(Request $request, SearchService $search, CurrentSpace $current): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'space' => ['nullable', 'string', 'max:100'],
        ]);

        // Optional restriction to one space the user can read.
        $space = $request->filled('space') ? Space::query()->where('slug', $request->query('space'))->first() : null;
        if ($space !== null && ! $request->user()->can('view', $space)) {
            $space = null;
        }
        if ($space !== null) {
            $current->set($space);
        }

        $query = SearchQuery::fromInput($request->query('q'))->inSpace($space?->id);

        return view('search.index', [
            'query' => $query,
            'space' => $space,
            'groups' => $search->search($request->user(), $query),
        ]);
    }
}
