<?php

namespace App\Core\Dashboard;

use App\Models\User;
use Illuminate\Contracts\View\View;

interface DashboardWidget
{
    public function key(): string;

    /** Lower comes first. */
    public function order(): int;

    /** Return null to hide the widget for this user. */
    public function render(User $user): ?View;
}
