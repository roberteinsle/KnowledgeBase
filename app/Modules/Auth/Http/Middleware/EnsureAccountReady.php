<?php

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Workspace\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out deactivated accounts and, when the workspace enforces MFA, sends
 * users without passkey or TOTP to the security page first.
 */
class EnsureAccountReady
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login');
        }

        if (Workspace::current()->enforce_mfa && ! $user->hasTotp() && ! $user->hasPasskeysEnabled()) {
            return redirect()->route('account.security')
                ->with('warning', __('This workspace requires a passkey or an authenticator app. Please set one up to continue.'));
        }

        return $next($request);
    }
}
