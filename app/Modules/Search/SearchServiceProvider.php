<?php

namespace App\Modules\Search;

use App\Core\Http\LegacyRedirects;
use App\Core\Mcp\McpToolRegistry;
use App\Core\Navigation\MenuItem;
use App\Core\Navigation\MenuRegistry;
use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Modules\Search\Http\Controllers\SearchController;
use App\Modules\Search\Mcp\SearchTool;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function boot(NavigationRegistry $navigation, MenuRegistry $menus): void
    {
        $this->app->make(McpToolRegistry::class)->add(SearchTool::class);
        $menus->add('space', new MenuItem('search', 'Search', 'search', fn ($slug) => route('search', ['space' => $slug]), order: 20));

        Route::middleware(['web', 'auth', 'account', 'throttle:search'])->group(function () {
            Route::get('/search', SearchController::class)->name('search');
            LegacyRedirects::register('suche', 'search');
        });

        $navigation->add(new NavigationItem(
            key: 'search',
            label: 'Search',
            route: 'search',
            icon: 'search',
            order: 30,
            mobileTab: true,
            activeRoutes: ['search'],
        ));
    }
}
