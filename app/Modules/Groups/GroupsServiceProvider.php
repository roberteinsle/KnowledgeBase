<?php

namespace App\Modules\Groups;

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
    }
}
