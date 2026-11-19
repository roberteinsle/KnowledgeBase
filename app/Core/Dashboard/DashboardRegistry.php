<?php

namespace App\Core\Dashboard;

use App\Models\User;
use Illuminate\Contracts\View\View;

final class DashboardRegistry
{
    /** @var array<string, DashboardWidget> */
    private array $widgets = [];

    public function add(DashboardWidget $widget): void
    {
        $this->widgets[$widget->key()] = $widget;
    }

    /** @return list<View> */
    public function renderFor(User $user): array
    {
        $widgets = array_values($this->widgets);
        usort($widgets, fn (DashboardWidget $a, DashboardWidget $b) => $a->order() <=> $b->order());

        return array_values(array_filter(array_map(fn (DashboardWidget $w) => $w->render($user), $widgets)));
    }
}
