<?php

namespace App\Modules\Spaces\Contracts;

use App\Models\User;
use App\Modules\Spaces\Enums\SpaceRole;
use App\Modules\Spaces\Models\Space;

/**
 * The one place that answers "what may this user do in this space".
 * Pages, search and import/export depend on this contract, never on the
 * permission tables.
 */
interface SpaceAccess
{
    /** Effective role (highest of user and group grants); workspace admins are space admins everywhere. */
    public function roleFor(User $user, Space $space): ?SpaceRole;

    public function allows(User $user, Space $space, SpaceRole $minimum): bool;

    /** @return list<int> */
    public function spaceIdsFor(User $user, SpaceRole $minimum = SpaceRole::Reader): array;
}
