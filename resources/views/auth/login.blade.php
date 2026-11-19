<x-layouts.guest :title="__('Sign in')">
    <h2 class="mb-6 text-center text-lg font-semibold">{{ __('Sign in') }}</h2>

    <div data-passkey-login data-error="{{ __('Sign-in with a passkey did not work. Please try again or use an e-mail link.') }}" hidden>
        <button type="button" class="btn-primary w-full py-2.5" data-passkey-login-button>
            <x-kb-icon name="key" class="size-5" /> {{ __('Sign in with a passkey') }}
        </button>
        <p class="mt-2 hidden text-center text-sm text-red-700" data-passkey-error role="alert"></p>
        <div class="my-6 flex items-center gap-3 text-xs uppercase text-slate-400">
            <span class="h-px flex-1 bg-slate-200"></span> {{ __('or') }} <span class="h-px flex-1 bg-slate-200"></span>
        </div>
    </div>

    <form method="post" action="{{ route('login.request-link') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="label">{{ __('E-mail address') }}</label>
            <input id="email" name="email" type="email" required maxlength="255" autocomplete="email webauthn"
                   value="{{ old('email') }}" class="input" inputmode="email">
        </div>
        <button type="submit" class="btn-secondary w-full"><x-kb-icon name="mail" class="size-4" /> {{ __('Send sign-in link') }}</button>
        <p class="text-center text-xs text-slate-500">{{ __('We will e-mail you a link that is valid for :minutes minutes.', ['minutes' => config('knowledgebase.magic_link.ttl_minutes')]) }}</p>
    </form>
</x-layouts.guest>
