<?php

namespace App\Modules\Pages\Policies;

use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;

class PagePolicy
{
    public function __construct(private SpaceAccess $access) {}

    public function view(User $user, Page $page): bool
    {
        return $this->access->allows($user, $page->space, SpaceRole::Reader);
    }

    public function update(User $user, Page $page): bool
    {
        return ! $page->trashed() && $this->access->allows($user, $page->space, SpaceRole::Editor);
    }

    public function delete(User $user, Page $page): bool
    {
        return $this->access->allows($user, $page->space, SpaceRole::Admin);
    }

    public function restore(User $user, Page $page): bool
    {
        return $this->access->allows($user, $page->space, SpaceRole::Admin);
    }

    public function forceDelete(User $user, Page $page): bool
    {
        return $page->trashed() && $this->access->allows($user, $page->space, SpaceRole::Admin);
    }
}
