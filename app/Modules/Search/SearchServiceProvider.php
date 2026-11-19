<?php

namespace App\Modules\Search;

use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Modules\Search\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    public function boot(NavigationRegistry $navigation): void
    {
        Route::middleware(['web', 'auth', 'account', 'throttle:search'])->group(function () {
            Route::get('/suche', SearchController::class)->name('search');
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
