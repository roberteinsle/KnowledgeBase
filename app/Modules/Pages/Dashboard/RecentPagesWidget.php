<?php

namespace App\Modules\Pages\Dashboard;

use App\Core\Dashboard\DashboardWidget;
use App\Models\User;
use App\Modules\Pages\Models\Page;
use App\Modules\Spaces\Contracts\SpaceAccess;
use Illuminate\Contracts\View\View;

class RecentPagesWidget implements DashboardWidget
{
    public function __construct(private SpaceAccess $access) {}

    public function key(): string
    {
        return 'recent-pages';
    }

    public function order(): int
    {
        return 20;
    }

    public function render(User $user): ?View
    {
        $pages = Page::query()
            ->whereIn('space_id', $this->access->spaceIdsFor($user))
            ->with(['space:id,name,slug', 'editor:id,name'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('pages.widget-recent', ['pages' => $pages]);
    }
}
