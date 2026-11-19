<?php

namespace App\Core\Search;

final class SearchRegistry
{
    /** @var array<string, SearchProvider> */
    private array $providers = [];

    public function add(SearchProvider $provider): void
    {
        $this->providers[$provider->key()] = $provider;
    }

    /**
     * @return array<string, SearchProvider>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
