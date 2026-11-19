<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\LoginService;
use App\Modules\Auth\Services\MagicLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MagicLinkController
{
    /**
     * Mail scanners open links in advance, so the GET only shows a button and
     * never touches the database.
     */
    public function show(string $token): Response
    {
        return response()
            ->view('auth.magic-link', ['token' => $token])
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function consume(Request $request, string $token, MagicLinkService $links, LoginService $login): RedirectResponse
    {
        $result = $links->consume($token);

        if ($result === null) {
            return redirect()->route('login')
                ->withErrors(['email' => __('This sign-in link is invalid or has expired. Please request a new one.')]);
        }

        return redirect()->to($login->completeMagicLinkLogin($request, $result['user'], $result['redirect']));
    }
}
