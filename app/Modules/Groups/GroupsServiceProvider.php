<?php

namespace App\Modules\Groups;

use App\Core\Admin\AdminMetric;
use App\Core\Admin\AdminMetricRegistry;
use App\Models\User;
use App\Modules\Groups\Contracts\GroupMembership;
use App\Modules\Groups\Models\Group;
use App\Modules\Groups\Services\GroupService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class GroupsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(GroupService::class);
        $this->app->bind(GroupMembership::class, GroupService::class);
    }

    public function boot(): void
    {
        Relation::morphMap(['user' => User::class, 'group' => Group::class]);

        $this->app->make(AdminMetricRegistry::class)->add(new AdminMetric(
            key: 'groups',
            label: 'Groups',
            value: fn () => Group::query()->count(),
            icon: 'heroicon-o-user-group',
            order: 20,
            route: 'filament.admin.resources.groups.index',
        ));
    }
}
