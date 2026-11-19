<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Core\Activity\ActivityLogger;
use App\Core\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', Rule::in(Locales::codes())],
        ]);

        $user = $request->user();
        $user->fill($data)->save();
        $activity->log('user.profile_updated', $user, $user->getChanges());

        return back()->with('status', __('Saved.'));
    }

    /** Guests may switch the language of the sign-in page. */
    public function guestLocale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(Locales::codes())]]);
        $request->session()->put('locale', $data['locale']);

        return back();
    }
}
