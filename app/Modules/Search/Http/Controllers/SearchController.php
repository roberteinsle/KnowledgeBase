<?php

namespace App\Modules\Search\Http\Controllers;

use App\Core\Search\SearchQuery;
use App\Modules\Search\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController
{
    public function __invoke(Request $request, SearchService $search): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $query = SearchQuery::fromInput($request->query('q'));

        return view('search.index', [
            'query' => $query,
            'groups' => $search->search($request->user(), $query),
        ]);
    }
}
