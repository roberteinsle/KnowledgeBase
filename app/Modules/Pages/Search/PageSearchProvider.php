<?php

namespace App\Modules\Pages\Search;

use App\Core\Search\SearchProvider;
use App\Core\Search\SearchQuery;
use App\Core\Search\SearchResult;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;

class PageSearchProvider implements SearchProvider
{
    public const HEADLINE = 'StartSel=⟦, StopSel=⟧, MaxWords=35, MinWords=12, MaxFragments=2, FragmentDelimiter=" … "';

    public function __construct(private SpaceAccess $access) {}

    public function key(): string
    {
        return 'pages';
    }

    public function label(): string
    {
        return 'Pages';
    }

    public function search(User $user, SearchQuery $query, int $limit): array
    {
        $spaceIds = $this->access->spaceIdsFor($user);
        if ($spaceIds === [] || $query->isEmpty()) {
            return [];
        }

        $ts = $query->toTsQuery();

        return Page::query()
            ->select(['pages.id', 'pages.title', 'pages.space_id'])
            ->selectRaw("ts_rank(search_vector, to_tsquery('simple', ?)) AS rank", [$ts])
            ->selectRaw("ts_headline('simple', content, to_tsquery('simple', ?), ?) AS excerpt", [$ts, self::HEADLINE])
            ->whereIn('space_id', $spaceIds)
            ->whereRaw("search_vector @@ to_tsquery('simple', ?)", [$ts])
            ->with('space:id,name,slug')
            ->orderByDesc('rank')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (Page $page) => new SearchResult(
                title: $page->title,
                url: route('pages.show', $page),
                context: $page->space->name,
                excerpt: (string) $page->getAttribute('excerpt'),
                rank: (float) $page->getAttribute('rank'),
            ))
            ->all();
    }
}
