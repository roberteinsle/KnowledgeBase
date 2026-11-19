<?php

namespace App\Core;

use App\Core\Activity\ActivityLogger;
use App\Core\Dashboard\DashboardRegistry;
use App\Core\ImportExport\ExportRegistry;
use App\Core\ImportExport\ImportRegistry;
use App\Core\Navigation\MenuRegistry;
use App\Core\Navigation\NavigationRegistry;
use App\Core\Search\SearchRegistry;
use App\Core\Sidebar\SidebarRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Registries are the only way modules plug into each other. Each module's
 * provider registers its navigation entries, search providers, importers and
 * exporters here.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NavigationRegistry::class);
        $this->app->singleton(SearchRegistry::class);
        $this->app->singleton(ImportRegistry::class);
        $this->app->singleton(ExportRegistry::class);
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(DashboardRegistry::class);
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(SidebarRegistry::class);
    }

    public function boot(): void
    {
        $this->configureRateLimits();

        // Filament talks to the server through Livewire; give that endpoint a limit too.
        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)->middleware(['web', 'throttle:livewire']);
        });
    }

    private function configureRateLimits(): void
    {
        $byUserOrIp = fn (Request $request) => $request->user()?->getAuthIdentifier() ?: $request->ip();

        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(180)->by('web:'.$byUserOrIp($request)));
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(240)->by('lw:'.$byUserOrIp($request)));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search:'.$byUserOrIp($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(60)->by('upload:'.$byUserOrIp($request)));
        RateLimiter::for('import', fn (Request $request) => Limit::perHour(20)->by('import:'.$byUserOrIp($request)));
        RateLimiter::for('export', fn (Request $request) => Limit::perHour(30)->by('export:'.$byUserOrIp($request)));
    }
}
