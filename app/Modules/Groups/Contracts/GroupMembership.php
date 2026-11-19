<?php

namespace App\Modules\Groups\Contracts;

use App\Models\User;

interface GroupMembership
{
    /**
     * All group ids the user belongs to, including the implicit "Everyone" group.
     *
     * @return list<int>
     */
    public function groupIdsFor(User $user): array;
}
