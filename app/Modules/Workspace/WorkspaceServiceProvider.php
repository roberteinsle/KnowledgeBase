<?php

namespace App\Modules\Workspace;

use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Models\User;
use App\Modules\Workspace\Http\Controllers\HomeController;
use App\Modules\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WorkspaceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Workspace::class.'@current', fn () => Workspace::query()->orderBy('id')->firstOrFail());
    }

    public function boot(NavigationRegistry $navigation): void
    {
        Relation::morphMap(['workspace' => Workspace::class]);

        Route::middleware(['web', 'auth', 'account'])->group(function () {
            Route::get('/', HomeController::class)->name('home');
        });

        $navigation->add(new NavigationItem(
            key: 'home',
            label: 'Home',
            route: 'home',
            icon: 'home',
            order: 10,
            mobileTab: true,
            activeRoutes: ['home'],
        ));

        $navigation->add(new NavigationItem(
            key: 'admin',
            label: 'Administration',
            route: 'filament.admin.pages.dashboard',
            icon: 'cog',
            area: 'account',
            order: 90,
            visible: fn (User $user) => $user->isAdmin(),
        ));
    }
}
