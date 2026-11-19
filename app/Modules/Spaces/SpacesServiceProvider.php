<?php

namespace App\Modules\Spaces;

use App\Core\Dashboard\DashboardRegistry;
use App\Core\Mcp\McpToolRegistry;
use App\Core\Navigation\MenuItem;
use App\Core\Navigation\MenuRegistry;
use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Core\Sidebar\SidebarRegistry;
use App\Models\User;
use App\Modules\Spaces\Contracts\CurrentSpace;
use App\Modules\Spaces\Contracts\SpaceAccess;
use App\Modules\Spaces\Dashboard\SpacesWidget;
use App\Modules\Spaces\Mcp\CreateSpaceTool;
use App\Modules\Spaces\Mcp\ListSpacesTool;
use App\Modules\Spaces\Mcp\UpdateSpaceTool;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Policies\SpacePolicy;
use App\Modules\Spaces\Services\CurrentSpaceService;
use App\Modules\Spaces\Services\SpaceAccessService;
use App\Modules\Spaces\Sidebar\SpaceSwitcherSection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SpacesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(SpaceAccessService::class);
        $this->app->bind(SpaceAccess::class, SpaceAccessService::class);
        $this->app->singleton(CurrentSpace::class, CurrentSpaceService::class);
    }

    public function boot(NavigationRegistry $navigation, DashboardRegistry $dashboard, SidebarRegistry $sidebar, MenuRegistry $menus): void
    {
        $sidebar->add($this->app->make(SpaceSwitcherSection::class));
        foreach ([ListSpacesTool::class, CreateSpaceTool::class, UpdateSpaceTool::class] as $tool) {
            $this->app->make(McpToolRegistry::class)->add($tool);
        }
        $this->app->make(CurrentSpace::class)->resolveUsing(
            fn (Request $request) => $request->route('space') instanceof Space ? $request->route('space') : null,
        );

        $menus->add('space', new MenuItem(
            key: 'space-settings',
            label: 'Space settings',
            icon: 'cog',
            url: fn ($slug) => route('filament.admin.resources.spaces.edit', $slug),
            order: 30,
            visible: fn (User $user) => $user->isAdmin(),
        ));

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
