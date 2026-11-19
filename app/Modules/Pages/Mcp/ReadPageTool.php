<?php

namespace App\Modules\Pages\Mcp;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Reads one page: title, Markdown content, labels, version, parent, sub-pages and attachments.')]
#[IsReadOnly]
class ReadPageTool extends Tool
{
    use PageToolSupport;

    protected string $name = 'read_page';

    public function handle(Request $request): Response
    {
        $data = $request->validate(['id' => ['required', 'integer']]);
        $page = $this->findPage($this->actor($request), (int) $data['id']);

        if ($page === null) {
            return Response::error('Page not found.');
        }

        $page->load(['labels', 'attachments', 'editor']);

        return Response::json([
            ...$this->summary($page),
            'labels' => $page->labels->pluck('name')->all(),
            'updated_at' => $page->updated_at->toIso8601String(),
            'updated_by' => $page->editor?->name,
            'children' => $page->children()->get(['id', 'title'])->map(fn ($c) => ['id' => $c->id, 'title' => $c->title])->all(),
            'attachments' => $page->attachments->map(fn ($a) => ['name' => $a->original_name, 'url' => route('attachments.show', $a), 'size' => $a->size])->all(),
            'content' => $page->content,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Numeric page id (from search or list_pages).')->required()];
    }
}
