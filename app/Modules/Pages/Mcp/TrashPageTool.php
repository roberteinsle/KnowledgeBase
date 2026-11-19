<?php

namespace App\Modules\Pages\Mcp;

use App\Modules\Pages\Services\PageService;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Moves a page with all its sub-pages to the trash of the space (can be restored in the app). Requires the Space admin role.')]
#[IsDestructive]
class TrashPageTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'trash_page';

    public function handle(Request $request, PageService $pages): Response
    {
        $data = $request->validate(['id' => ['required', 'integer']]);
        $user = $this->actor($request);

        $page = $this->findPage($user, (int) $data['id'], SpaceRole::Admin);
        if ($page === null || $page->trashed()) {
            return Response::error('Page not found or you may not delete it.');
        }

        $pages->trash($page, $user);

        return Response::json(['trashed' => true, 'id' => $page->id, 'title' => $page->title]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Page to move to the trash.')->required()];
    }
}
