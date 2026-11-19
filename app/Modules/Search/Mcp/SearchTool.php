<?php

namespace App\Modules\Search\Mcp;

use App\Core\Search\SearchQuery;
use App\Models\User;
use App\Modules\Search\Services\SearchService;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Full-text search over pages and attachments you can read. All words must occur; word beginnings match. Returns titles, urls, excerpts and page ids for read_page.')]
#[IsReadOnly]
class SearchTool extends Tool
{
    protected string $name = 'search';

    public function handle(Request $request, SearchService $search, SpaceAccess $access): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $data = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'space' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $spaceId = null;
        if (isset($data['space'])) {
            $space = Space::query()->where('slug', $data['space'])->first();
            if ($space === null || $access->roleFor($user, $space) === null) {
                return Response::error('Space not found.');
            }
            $spaceId = $space->id;
        }

        $query = SearchQuery::fromInput($data['query'])->inSpace($spaceId);
        $groups = $search->search($user, $query, (int) ($data['limit'] ?? 20));

        $results = [];
        foreach ($groups as $type => $group) {
            foreach ($group['results'] as $result) {
                $results[] = [
                    'type' => $type,
                    'title' => $result->title,
                    'context' => $result->context,
                    'url' => $result->url,
                    'page_id' => preg_match('#/(?:pages/)?(?:[a-z0-9-]*-)?(\d+)(?:$|[/?\#])#', $result->url, $m) === 1 ? (int) $m[1] : null,
                    'excerpt' => str_replace(['⟦', '⟧'], ['**', '**'], $result->excerpt),
                ];
            }
        }

        return Response::json(['query' => $data['query'], 'results' => $results]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search terms.')->required(),
            'space' => $schema->string()->description('Restrict to one space (slug).'),
            'limit' => $schema->integer()->description('Results per type, 1–50 (default 20).'),
        ];
    }
}
