<?php

namespace App\Modules\Pages\Dashboard;

use App\Core\Dashboard\DashboardWidget;
use App\Models\User;
use App\Modules\Pages\Services\PageOverview;
use App\Modules\Spaces\Contracts\SpaceAccess;
use Illuminate\Contracts\View\View;

class RecentPagesWidget implements DashboardWidget
{
    public function __construct(private PageOverview $overview) {}

    public function key(): string
    {
        return 'recent-pages';
    }

    public function order(): int
    {
        return 10;
    }

    public function render(User $user): ?View
    {
        return view('pages.overview-tabs', [
            'lists' => $this->overview->for($user, app(SpaceAccess::class)->spaceIdsFor($user)),
            'showSpace' => true,
        ]);
    }
}
