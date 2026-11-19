<?php

namespace App\Modules\Spaces\Mcp;

use App\Models\User;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Lists the spaces you can read, with slug, name, description and your role (reader, editor, admin).')]
#[IsReadOnly]
class ListSpacesTool extends Tool
{
    protected string $name = 'list_spaces';

    public function handle(Request $request, SpaceAccess $access): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $spaces = Space::query()->whereIn('id', $access->spaceIdsFor($user))->orderBy('name')->get()
            ->map(fn (Space $space) => [
                'slug' => $space->slug,
                'name' => $space->name,
                'description' => $space->description,
                'role' => $access->roleFor($user, $space)?->value,
                'url' => route('spaces.show', $space),
            ])->all();

        return Response::json(['spaces' => $spaces]);
    }
}
