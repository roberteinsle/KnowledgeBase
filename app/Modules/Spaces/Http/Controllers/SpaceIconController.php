<?php

namespace App\Modules\Spaces\Http\Controllers;

use App\Modules\Spaces\Models\Space;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SpaceIconController
{
    private const RASTER = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public function __invoke(Space $space): Response
    {
        Gate::authorize('view', $space);
        abort_unless($space->hasImage(), 404);

        $disk = Storage::disk('local');
        $contents = $disk->get((string) $space->icon_path);
        abort_if($contents === null, 404);

        // Detected from the content; anything but a raster image is refused.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        abort_unless(in_array($mime, self::RASTER, true), 404);

        return response($contents, 200, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
