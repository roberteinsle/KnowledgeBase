<?php

namespace App\Modules\Pages\Mcp;

use App\Modules\Pages\Models\Page;
use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Moves a page within its space: before/after another page, inside it as last sub-page, or to the top level (omit target_id). Sub-pages move along.')]
class MovePageTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'move_page';

    public function handle(Request $request, PageService $pages): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'target_id' => ['nullable', 'integer'],
            'placement' => ['nullable', 'in:before,after,inside'],
        ]);
        $user = $this->actor($request);

        $page = $this->findPage($user, (int) $data['id'], SpaceRole::Editor);
        if ($page === null || $page->trashed()) {
            return Response::error('Page not found or you may not move it.');
        }

        try {
            if (! isset($data['target_id'])) {
                $pages->move($page, null, $user);
            } else {
                $target = Page::query()->where('space_id', $page->space_id)->find($data['target_id']);
                if ($target === null) {
                    return Response::error('Target page not found in the same space.');
                }
                $pages->place($page, $target, $data['placement'] ?? PageService::INSIDE, $user);
            }
        } catch (ValidationException $e) {
            return Response::error(collect($e->errors())->flatten()->first() ?? 'The page could not be moved.');
        }

        return Response::json(['moved' => true, ...$this->summary($page->refresh()->load('space'))]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Page to move.')->required(),
            'target_id' => $schema->integer()->description('Reference page in the same space; omit to move to the top level.'),
            'placement' => $schema->string()->enum(['before', 'after', 'inside'])->description('Relative to target_id (default: inside).'),
        ];
    }
}
