<?php

namespace App\Core\Search;

/**
 * Normalised search input. The tsquery string is built from letters and digits
 * only and is always passed to PostgreSQL as a bound parameter.
 */
final readonly class SearchQuery
{
    /**
     * @param  list<string>  $terms
     */
    private function __construct(
        public string $raw,
        public array $terms,
        /** Restricts the search to one space; providers must still apply the user's rights. */
        public ?int $spaceId = null,
    ) {}

    public function inSpace(?int $spaceId): self
    {
        return new self($this->raw, $this->terms, $spaceId);
    }

    /**
     * @param  list<int>  $readableSpaceIds
     * @return list<int>
     */
    public function restrictSpaces(array $readableSpaceIds): array
    {
        if ($this->spaceId === null) {
            return $readableSpaceIds;
        }

        return in_array($this->spaceId, $readableSpaceIds, true) ? [$this->spaceId] : [];
    }

    public static function fromInput(?string $input): self
    {
        $raw = mb_substr(trim((string) $input), 0, 200);

        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($raw), $matches);
        $terms = array_values(array_unique(array_slice($matches[0], 0, 10)));

        return new self($raw, $terms);
    }

    public function isEmpty(): bool
    {
        return $this->terms === [];
    }

    /** e.g. "foo:* & bar:*" — prefix match on every term. */
    public function toTsQuery(): string
    {
        return implode(' & ', array_map(fn (string $term) => $term.':*', $this->terms));
    }
}
