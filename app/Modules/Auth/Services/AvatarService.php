<?php

namespace App\Modules\Auth\Services;

use App\Core\Activity\ActivityLogger;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AvatarService
{
    public function __construct(private ActivityLogger $activity) {}

    public function store(User $user, UploadedFile $file): void
    {
        $path = 'avatars/'.$user->id.'-'.Str::random(16).'.'.strtolower($file->extension() ?: 'png');
        Storage::disk('local')->put($path, (string) file_get_contents($file->getRealPath()));

        $this->replace($user, $path);
        $this->activity->log('user.avatar_updated', $user);
    }

    public function remove(User $user): void
    {
        $this->replace($user, null);
        $this->activity->log('user.avatar_removed', $user);
    }

    private function replace(User $user, ?string $path): void
    {
        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        if ($old !== null && $old !== $path) {
            Storage::disk('local')->delete($old);
        }
    }
}
