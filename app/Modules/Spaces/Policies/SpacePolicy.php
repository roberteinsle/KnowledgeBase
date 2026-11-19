<?php

namespace App\Modules\Spaces\Policies;

use App\Models\User;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;

class SpacePolicy
{
    public function __construct(private SpaceAccess $access) {}

    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Space $space): bool
    {
        return $this->access->allows($user, $space, SpaceRole::Reader);
    }

    /** Create, edit, move pages; import; upload attachments. */
    public function contribute(User $user, Space $space): bool
    {
        return $this->access->allows($user, $space, SpaceRole::Editor);
    }

    /** Delete pages, manage the trash. */
    public function moderate(User $user, Space $space): bool
    {
        return $this->access->allows($user, $space, SpaceRole::Admin);
    }

    /** Space settings and permissions are managed by workspace admins in /admin. */
    public function create(User $user): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function update(User $user, Space $space): bool
    {
        return $user->is_active && $user->isAdmin();
    }

    public function delete(User $user, Space $space): bool
    {
        return $user->is_active && $user->isAdmin();
    }
}
