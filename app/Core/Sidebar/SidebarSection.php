<?php

namespace App\Core\Sidebar;

use App\Models\User;
use Illuminate\Contracts\View\View;

interface SidebarSection
{
    public function key(): string;

    /** Lower comes first. */
    public function order(): int;

    /** Return null to show nothing for this user/request. */
    public function render(User $user): ?View;
}
