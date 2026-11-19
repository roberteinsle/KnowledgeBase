<?php

namespace App\Core\Search;

final readonly class SearchResult
{
    public function __construct(
        public string $title,
        public string $url,
        public string $context,
        /** Plain-text excerpt; hits are wrapped in ⟦ ⟧ markers (never HTML), see search.blade.php. */
        public string $excerpt,
        public float $rank,
    ) {}
}
