<?php

namespace App\Providers\Filament;

use App\Core\Http\LegacyRedirects;
use App\Core\Http\Middleware\SetLocale;
use App\Modules\Workspace\Models\Workspace;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Admin area under the fixed prefix /admin (protected additionally by
 * Cloudflare Access in production). No own login: unauthenticated visitors
 * are sent to the app's passkey / magic link sign-in.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName(fn () => Workspace::current()->name.' · '.__('Administration'))
            ->colors(['primary' => Color::Indigo])
            ->favicon(fn () => asset('favicon.svg'))
            ->userMenuItems([
                Action::make('knowledgebase')
                    ->label(fn () => __('Back to the knowledge base'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->url(fn () => route('home')),
            ])
            ->navigationItems([
                // Right below "Dashboard" (sort -2), outside the groups.
                NavigationItem::make('knowledgebase')
                    ->label(fn () => __('Back to the knowledge base'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->url(fn () => route('home'))
                    ->sort(-1),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
                'throttle:web',
            ])
            ->authMiddleware([
                Authenticate::class,
                'account',
            ]);
    }

    public function boot(): void
    {
        // Former German admin paths (bookmarks).
        Route::middleware('web')->prefix('admin')->group(function () {
            $words = ['neu' => 'create', 'bearbeiten' => 'edit'];
            LegacyRedirects::register('benutzer', 'admin/users', $words);
            LegacyRedirects::register('gruppen', 'admin/groups', $words);
            LegacyRedirects::register('bereiche', 'admin/spaces', $words);
            LegacyRedirects::register('protokoll', 'admin/activity-log');
            LegacyRedirects::register('einstellungen', 'admin/settings');
        });
    }
}
