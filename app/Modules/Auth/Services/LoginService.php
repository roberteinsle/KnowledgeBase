<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LoginService
{
    public const PENDING_KEY = 'auth.pending_two_factor';

    /**
     * Finishes a magic-link sign-in. Accounts with TOTP get the second step
     * first; passkey sign-ins never come through here (already multi-factor).
     */
    public function completeMagicLinkLogin(Request $request, User $user, ?string $redirect): string
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->regenerate();

        if ($user->hasTotp()) {
            $request->session()->put(self::PENDING_KEY, [
                'user_id' => $user->id,
                'redirect' => $redirect,
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ]);

            return route('two-factor.show');
        }

        return $this->login($request, $user, $redirect, viaMagicLink: true);
    }

    public function login(Request $request, User $user, ?string $redirect, bool $viaMagicLink = false): string
    {
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_KEY);

        // After a magic link: offer a passkey once.
        if ($viaMagicLink && ! $user->hasPasskeysEnabled() && ! $request->session()->get('passkey_offer_dismissed')) {
            $request->session()->put('url.intended', RedirectSanitizer::internalPath($redirect) ?? route('home'));

            return route('account.passkey-offer');
        }

        return RedirectSanitizer::internalPath($redirect) ?? route('home');
    }

    public function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::PENDING_KEY);

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->getTimestamp()) {
            $request->session()->forget(self::PENDING_KEY);

            return null;
        }

        $user = User::query()->find($pending['user_id'] ?? 0);

        return $user?->is_active ? $user : null;
    }

    public function pendingRedirect(Request $request): ?string
    {
        return $request->session()->get(self::PENDING_KEY.'.redirect');
    }
}
