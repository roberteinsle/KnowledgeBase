<?php

namespace App\Modules\Auth;

use App\Core\Activity\Activity;
use App\Core\Activity\ActivityLogger;
use App\Core\Admin\AdminMetric;
use App\Core\Admin\AdminMetricRegistry;
use App\Core\Admin\Checks\CoreHealthChecks;
use App\Core\Admin\HealthCheckRegistry;
use App\Core\Navigation\NavigationItem;
use App\Core\Navigation\NavigationRegistry;
use App\Models\User;
use App\Modules\Auth\Services\LoginService;
use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Auth\Services\MailCheckService;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(NavigationRegistry $navigation): void
    {
        Route::middleware('web')->group(__DIR__.'/routes.php');

        $this->configureRateLimits();

        $this->app->make(AdminMetricRegistry::class)->add(new AdminMetric(
            key: 'sign-in',
            label: 'Sign-ins (7 days)',
            value: fn () => Activity::query()->where('action', 'auth.login')->where('created_at', '>=', now()->subDays(7))->count(),
            icon: 'heroicon-o-key',
            order: 70,
            description: fn () => __(':passkeys with passkey · :totp with authenticator app', [
                'passkeys' => User::query()->whereHas('passkeys')->count(),
                'totp' => User::query()->whereNotNull('totp_confirmed_at')->count(),
            ]),
        ));
        $this->app->make(HealthCheckRegistry::class)->add(CoreHealthChecks::make('mail', 'E-mail (SMTP)', 40, fn () => $this->app->make(MailCheckService::class)->status()));

        Passkeys::authorizeLoginUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
            if (! $user instanceof User || ! $user->is_active) {
                throw ValidationException::withMessages(['credential' => [__('Unable to sign in with this account.')]]);
            }

            return true;
        });

        // Every successful sign-in (passkey, magic link, 2FA) counts as a fresh
        // confirmation for sensitive actions during the next 15 minutes.
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;
            if (! $user instanceof User) {
                return;
            }

            $user->forceFill(['last_login_at' => now()])->saveQuietly();
            request()->session()->passwordConfirmed();
            request()->session()->forget(LoginService::PENDING_KEY);
            app(ActivityLogger::class)->log('auth.login', $user, userId: $user->id);
        });

        $navigation->add(new NavigationItem(
            key: 'account',
            label: 'Account',
            route: 'account.edit',
            icon: 'user',
            area: 'account',
            order: 10,
            mobileTab: true,
            activeRoutes: ['account.*'],
        ));

        $navigation->add(new NavigationItem(
            key: 'security',
            label: 'Security',
            route: 'account.security',
            icon: 'key',
            area: 'account',
            order: 20,
        ));
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('magic-link-request', function (Request $request) {
            $email = MagicLinkService::normalizeEmail((string) $request->input('email'));

            return [
                Limit::perMinutes(15, 5)->by('ml-email:'.hash('sha256', $email)),
                Limit::perHour(30)->by('ml-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('magic-link-consume', fn (Request $request) => Limit::perMinute(10)->by('ml-consume:'.$request->ip()));

        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by('2fa:'.($request->session()->get(LoginService::PENDING_KEY.'.user_id') ?? $request->user()?->getAuthIdentifier() ?? $request->ip())),
            Limit::perMinute(20)->by('2fa-ip:'.$request->ip()),
        ]);

        RateLimiter::for('confirm-link', fn (Request $request) => Limit::perMinutes(15, 3)->by('confirm:'.$request->user()?->getAuthIdentifier()));
    }
}
