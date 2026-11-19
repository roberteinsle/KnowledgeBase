<?php

namespace App\Modules\Mcp;

use App\Core\Account\AccountSectionRegistry;
use App\Core\Admin\AdminMetric;
use App\Core\Admin\AdminMetricRegistry;
use App\Core\Admin\Checks\CoreHealthChecks;
use App\Core\Admin\HealthCheckRegistry;
use App\Core\Admin\HealthResult;
use App\Modules\Mcp\Account\ConnectedAppsSection;
use App\Modules\Mcp\Http\Controllers\ConnectionController;
use App\Modules\Mcp\Http\Middleware\EnsureActiveTokenUser;
use App\Modules\Mcp\Servers\KnowledgebaseServer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Passport;
use Laravel\Passport\Token;

/**
 * Remote MCP server at /mcp with OAuth 2.1 (Passport, PKCE, dynamic client
 * registration for claude.ai and local clients).
 */
class McpServiceProvider extends ServiceProvider
{
    public function boot(AccountSectionRegistry $account): void
    {
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::authorizationView('mcp.authorize');

        Route::middleware('throttle:oauth')->group(fn () => Mcp::oauthRoutes());

        Mcp::web('/mcp', KnowledgebaseServer::class)
            ->middleware(['auth:api', EnsureActiveTokenUser::class, 'throttle:mcp']);

        Route::middleware(['web', 'auth', 'account'])->group(function () {
            Route::delete('/account/connections/{client}', [ConnectionController::class, 'destroy'])->name('mcp.connections.destroy');
        });

        $account->add($this->app->make(ConnectedAppsSection::class));
        $this->registerAdminStatus();
    }

    private function registerAdminStatus(): void
    {
        $connected = fn () => Token::query()
            ->where('revoked', false)
            ->where('expires_at', '>', now()->subDays(30))
            ->distinct()
            ->count('client_id');

        $this->app->make(AdminMetricRegistry::class)->add(new AdminMetric(
            key: 'mcp',
            label: 'Connected AI apps',
            value: $connected,
            icon: 'heroicon-o-cpu-chip',
            order: 80,
            description: fn () => __(':users users with MCP access', [
                'users' => Token::query()->where('revoked', false)->where('expires_at', '>', now()->subDays(30))->distinct()->count('user_id'),
            ]),
        ));

        $this->app->make(HealthCheckRegistry::class)->add(CoreHealthChecks::make('mcp', 'MCP server', 50, function () use ($connected) {
            $keysFromEnv = filled(config('passport.private_key')) && filled(config('passport.public_key'));
            $keysInStorage = is_readable(storage_path('oauth-private.key')) && is_readable(storage_path('oauth-public.key'));

            if (! $keysFromEnv && ! $keysInStorage) {
                return HealthResult::error(__('OAuth keys are missing – AI apps cannot sign in.'));
            }

            if (in_array('*', (array) config('mcp.redirect_domains'), true)) {
                return HealthResult::warning(__('MCP_REDIRECT_DOMAINS allows any domain – restrict it to the AI apps you use.'));
            }

            return HealthResult::ok(__(':url · keys from :source · :count connected apps', [
                'url' => url('/mcp'),
                'source' => $keysFromEnv ? __('environment') : __('storage volume'),
                'count' => $connected(),
            ]));
        }));
    }
}
