<?php

namespace App\Core\Mcp;

use Laravel\Mcp\Server\Tool;

/**
 * Tools of the MCP server. Each module registers the tools for its own
 * content (Pages, Spaces, Search …); the server only exposes them.
 */
final class McpToolRegistry
{
    /** @var array<class-string<Tool>, true> */
    private array $tools = [];

    /** @param class-string<Tool> $tool */
    public function add(string $tool): void
    {
        $this->tools[$tool] = true;
    }

    /** @return list<class-string<Tool>> */
    public function all(): array
    {
        return array_keys($this->tools);
    }
}
