<?php

namespace App\Modules\Pages\Search;

use App\Core\Search\SearchProvider;
use App\Core\Search\SearchQuery;
use App\Core\Search\SearchResult;
use App\Models\User;
use App\Modules\Pages\Models\Attachment;
use App\Modules\Spaces\Contracts\SpaceAccess;

class AttachmentSearchProvider implements SearchProvider
{
    public function __construct(private SpaceAccess $access) {}

    public function key(): string
    {
        return 'attachments';
    }

    public function label(): string
    {
        return 'Attachments';
    }

    public function search(User $user, SearchQuery $query, int $limit): array
    {
        $spaceIds = $this->access->spaceIdsFor($user);
        if ($spaceIds === [] || $query->isEmpty()) {
            return [];
        }

        $ts = $query->toTsQuery();

        return Attachment::query()
            ->select(['attachments.id', 'attachments.page_id', 'attachments.original_name'])
            ->selectRaw("ts_rank(attachments.search_vector, to_tsquery('simple', ?)) AS rank", [$ts])
            ->selectRaw("ts_headline('simple', coalesce(attachments.text_content, ''), to_tsquery('simple', ?), ?) AS excerpt", [$ts, PageSearchProvider::HEADLINE])
            ->join('pages', 'pages.id', '=', 'attachments.page_id')
            ->whereNull('pages.deleted_at')
            ->whereIn('pages.space_id', $spaceIds)
            ->whereRaw("attachments.search_vector @@ to_tsquery('simple', ?)", [$ts])
            ->with('page:id,title')
            ->orderByDesc('rank')
            ->limit($limit)
            ->get()
            ->map(fn (Attachment $a) => new SearchResult(
                title: $a->original_name,
                url: route('pages.show', $a->page_id).'#anhaenge',
                context: $a->page->title,
                excerpt: (string) $a->getAttribute('excerpt'),
                rank: (float) $a->getAttribute('rank'),
            ))
            ->all();
    }
}
