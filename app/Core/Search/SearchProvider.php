<?php

namespace App\Core\Search;

use App\Models\User;

interface SearchProvider
{
    /** Stable key, e.g. "pages". */
    public function key(): string;

    /** Untranslated label for the result group. */
    public function label(): string;

    /**
     * Must only return results the user is allowed to read.
     *
     * @return list<SearchResult>
     */
    public function search(User $user, SearchQuery $query, int $limit): array;
}
