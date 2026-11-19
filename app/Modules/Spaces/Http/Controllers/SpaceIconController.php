<?php

namespace App\Modules\Spaces\Http\Controllers;

use App\Core\Support\Avatar;
use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Space icon: the uploaded raster image, otherwise a generated avatar with the
 * emoji or the initials of the name (same look as user avatars).
 */
class SpaceIconController
{
    public function __invoke(Space $space): Response
    {
        Gate::authorize('view', $space);

        $image = $space->hasImage() ? Avatar::imageResponse(Storage::disk('local')->get((string) $space->icon_path)) : null;

        return $image ?? Avatar::svgResponse($space->name, $space->icon);
    }
}
