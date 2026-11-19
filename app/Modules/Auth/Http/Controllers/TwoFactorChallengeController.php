<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\LoginService;
use App\Modules\Auth\Services\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorChallengeController
{
    public function show(Request $request, LoginService $login): View|RedirectResponse
    {
        if ($login->pendingUser($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request, LoginService $login, TotpService $totp): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20']]);
        $user = $login->pendingUser($request);

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! $totp->verifyChallenge($user, $data['code'])) {
            return back()->withErrors(['code' => __('The code is invalid.')]);
        }

        return redirect()->to($login->login($request, $user, $login->pendingRedirect($request), viaMagicLink: true));
    }
}
