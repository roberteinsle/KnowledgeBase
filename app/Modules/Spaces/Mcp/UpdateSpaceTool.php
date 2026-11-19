<?php

namespace App\Modules\Spaces\Mcp;

use App\Models\User;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Services\SpaceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Changes name, description or emoji icon of a space. Only workspace administrators may do this.')]
class UpdateSpaceTool extends Tool
{
    protected string $name = 'update_space';

    public function handle(Request $request, SpaceService $spaces): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->is_active || ! $user->isAdmin()) {
            return Response::error('Only workspace administrators can change spaces.');
        }

        $data = $request->validate([
            'space' => ['required', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:16'],
        ]);

        $space = Space::query()->where('slug', $data['space'])->first();
        if ($space === null) {
            return Response::error('Space not found.');
        }

        $changes = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
        ], fn ($value) => $value !== null);

        $space = $spaces->update($space, ['name' => $space->name, ...$changes]);

        return Response::json(['updated' => true, 'slug' => $space->slug, 'name' => $space->name]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'space' => $schema->string()->description('Slug of the space.')->required(),
            'name' => $schema->string()->description('New name.'),
            'description' => $schema->string()->description('New description.'),
            'icon' => $schema->string()->description('New emoji icon.'),
        ];
    }
}
