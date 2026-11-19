<?php

namespace App\Modules\Spaces;

use App\Core\Dashboard\DashboardRegistry;
use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Dashboard\SpacesWidget;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Policies\SpacePolicy;
use App\Modules\Spaces\Services\SpaceAccessService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SpacesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SpaceAccessService::class);
        $this->app->bind(SpaceAccess::class, SpaceAccessService::class);
    }

    public function boot(NavigationRegistry $navigation, DashboardRegistry $dashboard): void
    {
        $dashboard->add($this->app->make(SpacesWidget::class));
        Relation::morphMap(['space' => Space::class]);
        Gate::policy(Space::class, SpacePolicy::class);

        Route::middleware(['web', 'auth', 'account'])->group(__DIR__.'/routes.php');

        $navigation->add(new NavigationItem(
            key: 'spaces',
            label: 'Spaces',
            route: 'spaces.index',
            icon: 'folder',
            order: 20,
            mobileTab: true,
            activeRoutes: ['spaces.*', 'pages.*'],
        ));
    }
}
