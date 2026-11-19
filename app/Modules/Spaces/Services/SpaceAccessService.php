<?php

namespace App\Modules\Spaces\Services;

use App\Models\User;
use App\Modules\Groups\Contracts\GroupMembership;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Models\SpacePermission;

final class SpaceAccessService implements SpaceAccess
{
    /** @var array<int, array<int, SpaceRole>> user id => [space id => role] */
    private array $roles = [];

    public function __construct(private GroupMembership $groups) {}

    public function roleFor(User $user, Space $space): ?SpaceRole
    {
        if (! $user->is_active) {
            return null;
        }

        if ($user->isAdmin()) {
            return SpaceRole::Admin;
        }

        return $this->rolesFor($user)[$space->id] ?? null;
    }

    public function allows(User $user, Space $space, SpaceRole $minimum): bool
    {
        return $this->roleFor($user, $space)?->atLeast($minimum) ?? false;
    }

    public function spaceIdsFor(User $user, SpaceRole $minimum = SpaceRole::Reader): array
    {
        if (! $user->is_active) {
            return [];
        }

        if ($user->isAdmin()) {
            return Space::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return array_keys(array_filter(
            $this->rolesFor($user),
            fn (SpaceRole $role) => $role->atLeast($minimum),
        ));
    }

    public function flush(): void
    {
        $this->roles = [];
    }

    /** @return array<int, SpaceRole> */
    private function rolesFor(User $user): array
    {
        if (isset($this->roles[$user->id])) {
            return $this->roles[$user->id];
        }

        $groupIds = $this->groups->groupIdsFor($user);

        $grants = SpacePermission::query()
            ->where(function ($query) use ($user, $groupIds) {
                $query->where(fn ($q) => $q->where('subject_type', 'user')->where('subject_id', $user->id))
                    ->orWhere(fn ($q) => $q->where('subject_type', 'group')->whereIn('subject_id', $groupIds));
            })
            ->get(['space_id', 'role']);

        $roles = [];
        foreach ($grants as $grant) {
            $current = $roles[$grant->space_id] ?? null;
            if ($current === null || $grant->role->level() > $current->level()) {
                $roles[$grant->space_id] = $grant->role;
            }
        }

        return $this->roles[$user->id] = $roles;
    }
}
