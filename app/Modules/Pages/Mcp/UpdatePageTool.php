<?php

namespace App\Modules\Pages\Mcp;

use App\Modules\Pages\Contracts\PageWriter;
use App\Modules\Pages\Services\LabelNormalizer;
use App\Modules\Spaces\Enums\SpaceRole;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Updates title, Markdown content and/or labels of a page. Creates a new version; older versions stay in the history. Pass expected_version (from read_page) to avoid overwriting newer changes.')]
class UpdatePageTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'update_page';

    public function handle(Request $request, PageWriter $pages): Response
    {
        $data = $request->validate([
            'id' => ['required', 'integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:1000000'],
            'labels' => ['nullable', 'array', 'max:20'],
            'labels.*' => ['string', 'max:50'],
            'expected_version' => ['nullable', 'integer'],
        ]);
        $user = $this->actor($request);

        $page = $this->findPage($user, (int) $data['id'], SpaceRole::Editor);
        if ($page === null || $page->trashed()) {
            return Response::error('Page not found or you may not edit it.');
        }

        if (isset($data['expected_version']) && (int) $data['expected_version'] !== $page->version) {
            return Response::error("The page has changed in the meantime (current version {$page->version}). Read it again before updating.");
        }

        $page = $pages->update(
            $page,
            $data['title'] ?? $page->title,
            array_key_exists('content', $data) && $data['content'] !== null ? $data['content'] : $page->content,
            $user,
            isset($data['labels']) ? LabelNormalizer::normalize($data['labels']) : null,
        );

        return Response::json(['updated' => true, ...$this->summary($page)]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Numeric page id.')->required(),
            'title' => $schema->string()->description('New title; omit to keep it.'),
            'content' => $schema->string()->description('Complete new Markdown content; omit to keep it.'),
            'labels' => $schema->array()->items($schema->string())->description('Replaces all labels; omit to keep them.'),
            'expected_version' => $schema->integer()->description('Version you based the change on.'),
        ];
    }
}
