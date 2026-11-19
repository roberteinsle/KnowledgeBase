<?php

namespace App\Modules\Mcp;

use App\Core\Account\AccountSectionRegistry;
use App\Modules\Mcp\Account\ConnectedAppsSection;
use App\Modules\Mcp\Http\Controllers\ConnectionController;
use App\Modules\Mcp\Http\Middleware\EnsureActiveTokenUser;
use App\Modules\Mcp\Servers\KnowledgebaseServer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Passport\Passport;

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
    }
}
