<?php

namespace App\Modules\Mcp\Servers;

use App\Core\Mcp\McpToolRegistry;
use App\Modules\Workspace\Models\Workspace;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * MCP server of the knowledge base. Tools come from the modules through the
 * McpToolRegistry; every tool acts as the signed-in user with that user's
 * permissions.
 */
#[Name('Knowledgebase')]
#[Version('1.0.0')]
class KnowledgebaseServer extends Server
{
    protected function boot(): void
    {
        $this->tools = app(McpToolRegistry::class)->all();
        $this->instructions = 'Knowledge base "'.Workspace::current()->name.'". Content is organised in spaces '
            .'(identified by their slug) with nested pages (identified by their numeric id). Page content is Markdown. '
            .'Use search or list_spaces/list_pages to find content, read_page to get the full Markdown, and '
            .'create_page/update_page to write. Every change creates a new page version, so nothing is lost. '
            .'Pass the version you read as expected_version to update_page to avoid overwriting newer edits. '
            .'A line "[TOC]" in the content renders a table of contents. Links to other pages: /pages/<id>.';
    }
}
