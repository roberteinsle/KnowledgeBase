<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Services\MagicLinkService;
use App\Modules\Auth\Services\RedirectSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Replaces Laravel's password confirmation (there are no passwords): sensitive
 * actions need a sign-in within the last 15 minutes, a passkey confirmation or
 * a fresh link from the mailbox.
 */
class ConfirmController
{
    public function show(Request $request): View
    {
        return view('auth.confirm', ['hasPasskey' => $request->user()->hasPasskeysEnabled()]);
    }

    public function sendLink(Request $request, MagicLinkService $links): RedirectResponse
    {
        $redirect = RedirectSanitizer::internalPath($request->session()->get('url.intended')) ?? route('account.security', absolute: false);
        $links->issue($request->user(), $redirect);

        return back()->with('status', __('We have sent you a confirmation link.'));
    }
}
