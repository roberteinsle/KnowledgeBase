<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\RedirectSanitizer;
use App\Modules\Auth\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController
{
    private const SETUP_KEY = 'auth.totp_setup_secret';

    public function show(Request $request, TotpService $totp): View
    {
        $user = $request->user();
        $setupSecret = $request->session()->get(self::SETUP_KEY);

        return view('account.security', [
            'user' => $user,
            'passkeys' => $user->passkeys()->latest()->get(),
            'setupSecret' => $setupSecret,
            'qrCode' => $setupSecret ? $totp->qrCodeSvg($user, $setupSecret) : null,
            'recoveryCodes' => $request->session()->get('auth.recovery_codes'),
        ]);
    }

    public function startTotp(Request $request, TotpService $totp): RedirectResponse
    {
        $request->session()->put(self::SETUP_KEY, $totp->generateSecret());

        return redirect()->route('account.security');
    }

    public function confirmTotp(Request $request, TotpService $totp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $secret = $request->session()->get(self::SETUP_KEY);
        $user = $request->user();

        if (! is_string($secret) || ! $totp->verify($user, $secret, $data['code'])) {
            return back()->withErrors(['code' => __('The code is invalid.')]);
        }

        $codes = $totp->enable($user, $secret);
        $request->session()->forget(self::SETUP_KEY);

        return redirect()->route('account.security')
            ->with('auth.recovery_codes', $codes)
            ->with('status', __('Two-factor authentication is enabled.'));
    }

    public function disableTotp(Request $request, TotpService $totp): RedirectResponse
    {
        $totp->disable($request->user());

        return redirect()->route('account.security')->with('status', __('Two-factor authentication is disabled.'));
    }

    public function passkeyOffer(): View
    {
        return view('account.passkey-offer');
    }

    public function dismissPasskeyOffer(Request $request): RedirectResponse
    {
        $request->session()->put('passkey_offer_dismissed', true);

        return redirect()->to(RedirectSanitizer::internalPath($request->session()->pull('url.intended')) ?? route('home'));
    }
}
