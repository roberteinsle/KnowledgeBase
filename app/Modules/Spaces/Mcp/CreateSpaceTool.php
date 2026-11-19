<?php

namespace App\Modules\Spaces\Mcp;

use App\Models\User;
use App\Modules\Spaces\Services\SpaceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Creates a space. Only workspace administrators may do this; permissions for other users are granted in the administration area.')]
class CreateSpaceTool extends Tool
{
    protected string $name = 'create_space';

    public function handle(Request $request, SpaceService $spaces): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->is_active || ! $user->isAdmin()) {
            return Response::error('Only workspace administrators can create spaces.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:16'],
        ]);

        $space = $spaces->create($data, $user);

        return Response::json(['created' => true, 'slug' => $space->slug, 'name' => $space->name, 'url' => route('spaces.show', $space)]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Name of the space.')->required(),
            'description' => $schema->string()->description('Short description.'),
            'icon' => $schema->string()->description('Optional emoji used as icon.'),
        ];
    }
}
