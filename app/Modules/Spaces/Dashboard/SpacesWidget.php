<?php

namespace App\Modules\Spaces\Dashboard;

use App\Core\Dashboard\DashboardWidget;
use App\Models\User;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Models\Space;
use Illuminate\Contracts\View\View;

class SpacesWidget implements DashboardWidget
{
    public function __construct(private SpaceAccess $access) {}

    public function key(): string
    {
        return 'spaces';
    }

    public function order(): int
    {
        return 10;
    }

    public function render(User $user): ?View
    {
        $spaces = Space::query()->whereIn('id', $this->access->spaceIdsFor($user))->orderByRaw('lower(name)')->orderBy('id')->get();

        return view('spaces.widget', ['spaces' => $spaces, 'limit' => 6]);
    }
}
