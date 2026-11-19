<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Auth\Services\RedirectSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController
{
    public function show(Request $request): View
    {
        $next = RedirectSanitizer::internalPath($request->query('next'));
        if ($next !== null) {
            $request->session()->put('url.intended', $next);
        }

        return view('auth.login');
    }

    public function requestLink(Request $request, MagicLinkService $links): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        $email = MagicLinkService::normalizeEmail($data['email']);
        $redirect = RedirectSanitizer::internalPath($request->session()->get('url.intended'));

        // Lookup and mail happen after the response: same answer, same timing.
        defer(fn () => $links->requestFor($email, $redirect));

        return redirect()->route('login')
            ->with('status', __('If the address exists, we have sent a sign-in link to it.'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
