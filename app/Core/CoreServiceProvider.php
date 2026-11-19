<?php

namespace App\Core;

use App\Core\Account\AccountSectionRegistry;
use App\Core\Activity\ActivityLogger;
use App\Core\Admin\AdminMetricRegistry;
use App\Core\Admin\Checks\CoreHealthChecks;
use App\Core\Admin\HealthCheckRegistry;
use App\Core\Dashboard\DashboardRegistry;
use App\Core\ImportExport\ExportRegistry;
use App\Core\ImportExport\ImportRegistry;
use App\Core\Mcp\McpToolRegistry;
use App\Core\Navigation\MenuRegistry;
use App\Core\Navigation\NavigationRegistry;
use App\Core\Search\SearchRegistry;
use App\Core\Sidebar\SidebarRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Features\SupportFileUploads\FilePreviewController;
use Livewire\Features\SupportFileUploads\FileUploadController;
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
        $this->app->singleton(McpToolRegistry::class);
        $this->app->singleton(AccountSectionRegistry::class);
        $this->app->singleton(AdminMetricRegistry::class);
        $this->app->singleton(HealthCheckRegistry::class);
    }

    public function boot(): void
    {
        $this->configureRateLimits();

        foreach (CoreHealthChecks::all() as $check) {
            $this->app->make(HealthCheckRegistry::class)->add($check);
        }

        // All generated URLs (sign-in links, OAuth metadata …) use APP_URL,
        // never the Host header of the request. Not locally: in Codespaces or
        // on another port the browser address differs from APP_URL.
        $appUrl = (string) config('app.url');
        if ($appUrl !== '' && ! $this->app->runningUnitTests() && ! $this->app->isLocal()) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }

        // Health check for Coolify/Docker: plain text, no external resources
        // (Laravel's default page loads fonts and scripts from CDNs).
        Route::get('/up', function () {
            try {
                DB::select('select 1');
            } catch (\Throwable) {
                return response('database unavailable', 503, ['Content-Type' => 'text/plain']);
            }

            return response('OK', 200, ['Content-Type' => 'text/plain']);
        })->name('health');

        // Only Filament (/admin) uses Livewire, so its endpoints live below /admin
        // as well – Cloudflare Access on /admin then covers every admin request.
        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/admin/livewire/update', $handle)->middleware(['web', 'throttle:livewire']);
        });
        $this->app->booted(function () {
            // Livewire's own upload/preview routes are renamed (the name lookup keeps
            // the first route of a name), so the signed URLs point to /admin.
            foreach (Route::getRoutes()->getRoutes() as $route) {
                if (in_array($route->getName(), ['livewire.upload-file', 'livewire.preview-file'], true) && str_starts_with($route->uri(), 'livewire-')) {
                    $route->setAction(['as' => 'default-'.$route->getName()] + $route->getAction());
                }
            }

            Route::post('/admin/livewire/upload-file', [FileUploadController::class, 'handle'])
                ->middleware(['web', 'throttle:uploads'])->name('livewire.upload-file');
            Route::get('/admin/livewire/preview-file/{filename}', [FilePreviewController::class, 'handle'])
                ->middleware('web')->name('livewire.preview-file');
            Route::getRoutes()->refreshNameLookups();
        });
    }

    private function configureRateLimits(): void
    {
        $byUserOrIp = fn (Request $request) => $request->user()?->getAuthIdentifier() ?: $request->ip();

        RateLimiter::for('web', fn (Request $request) => Limit::perMinute(180)->by('web:'.$byUserOrIp($request)));
        RateLimiter::for('livewire', fn (Request $request) => Limit::perMinute(240)->by('lw:'.$byUserOrIp($request)));
        RateLimiter::for('mcp', fn (Request $request) => Limit::perMinute(120)->by('mcp:'.$byUserOrIp($request)));
        RateLimiter::for('oauth', fn (Request $request) => Limit::perHour(30)->by('oauth:'.$request->ip()));
        RateLimiter::for('preview', fn (Request $request) => Limit::perMinute(120)->by('preview:'.$byUserOrIp($request)));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(60)->by('search:'.$byUserOrIp($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perHour(60)->by('upload:'.$byUserOrIp($request)));
        RateLimiter::for('import', fn (Request $request) => Limit::perHour(20)->by('import:'.$byUserOrIp($request)));
        RateLimiter::for('export', fn (Request $request) => Limit::perHour(30)->by('export:'.$byUserOrIp($request)));
    }
}
