<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Support\Avatar;
use App\Models\User;
use App\Modules\Auth\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AvatarController
{
    /** Any signed-in user may see avatars (names are visible as page authors anyway). */
    public function show(User $user): Response
    {
        $image = $user->avatar_path !== null ? Avatar::imageResponse(Storage::disk('local')->get($user->avatar_path)) : null;

        return $image ?? Avatar::svgResponse($user->name);
    }

    public function store(Request $request, AvatarService $avatars): RedirectResponse
    {
        // Raster images only: SVG can contain scripts.
        $request->validate(['avatar' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:1024']]);

        $avatars->store($request->user(), $request->file('avatar'));

        return back()->with('status', __('Profile picture saved.'));
    }

    public function destroy(Request $request, AvatarService $avatars): RedirectResponse
    {
        $avatars->remove($request->user());

        return back()->with('status', __('Profile picture removed.'));
    }
}
