<?php

namespace App\Modules\Pages\Mcp;

use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\LabelNormalizer;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a page in a space, optionally below a parent page. Content is Markdown. Requires the Editor role in the space.')]
class CreatePageTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'create_page';

    public function handle(Request $request, PageWriter $pages): Response
    {
        $data = $request->validate([
            'space' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:1000000'],
            'parent_id' => ['nullable', 'integer'],
            'labels' => ['nullable', 'array', 'max:20'],
            'labels.*' => ['string', 'max:50'],
        ]);
        $user = $this->actor($request);

        $space = $this->findSpace($user, $data['space'], SpaceRole::Editor);
        if ($space === null) {
            return Response::error('Space not found or you may not create pages there.');
        }

        $parent = null;
        if (isset($data['parent_id'])) {
            $parent = Page::query()->where('space_id', $space->id)->find($data['parent_id']);
            if ($parent === null) {
                return Response::error('Parent page not found in this space.');
            }
        }

        $page = $pages->create($space, $parent, $data['title'], $data['content'] ?? '', $user, LabelNormalizer::normalize($data['labels'] ?? []));

        return Response::json(['created' => true, ...$this->summary($page->load('space'))]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'space' => $schema->string()->description('Slug of the space.')->required(),
            'title' => $schema->string()->description('Page title.')->required(),
            'content' => $schema->string()->description('Markdown content. A line "[TOC]" renders a table of contents.'),
            'parent_id' => $schema->integer()->description('Id of the parent page; omit for the top level.'),
            'labels' => $schema->array()->items($schema->string())->description('Labels, e.g. ["onboarding", "how-to"].'),
        ];
    }
}
