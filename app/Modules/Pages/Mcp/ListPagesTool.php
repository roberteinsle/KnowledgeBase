<?php

namespace App\Modules\Pages\Mcp;

use App\Modules\Pages\Services\PageTree;
use App\Modules\Pages\Support\PageUrl;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists all pages of a space as a tree (id, title, url, children). Use list_spaces to find space slugs.')]
#[IsReadOnly]
class ListPagesTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'list_pages';

    public function handle(Request $request, PageTree $tree): Response
    {
        $data = $request->validate(['space' => ['required', 'string', 'max:100']]);
        $space = $this->findSpace($this->actor($request), $data['space']);

        if ($space === null) {
            return Response::error('Space not found.');
        }

        $map = function (array $nodes) use (&$map, $space): array {
            return array_map(fn (array $node) => [
                'id' => $node['id'],
                'title' => $node['title'],
                'url' => PageUrl::to($space->slug, $node['id'], $node['title']),
                'children' => $map($node['children']),
            ], $nodes);
        };

        return Response::json(['space' => $space->slug, 'pages' => $map($tree->forSpace($space))]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['space' => $schema->string()->description('Slug of the space, e.g. "handbook".')->required()];
    }
}
