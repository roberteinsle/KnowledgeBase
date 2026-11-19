<?php

namespace App\Modules\Search\Services;

use App\Core\Search\SearchQuery;
use App\Core\Search\SearchRegistry;
use App\Core\Search\SearchResult;
use App\Models\User;

final class SearchService
{
    public function __construct(private SearchRegistry $registry) {}

    /**
     * @return array<string, array{label: string, results: list<SearchResult>}>
     */
    public function search(User $user, SearchQuery $query, int $limitPerProvider = 20): array
    {
        if ($query->isEmpty()) {
            return [];
        }

        $groups = [];
        foreach ($this->registry->all() as $key => $provider) {
            $results = $provider->search($user, $query, $limitPerProvider);
            if ($results !== []) {
                $groups[$key] = ['label' => $provider->label(), 'results' => $results];
            }
        }

        return $groups;
    }
}
