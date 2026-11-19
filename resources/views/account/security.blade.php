<x-layouts.app :title="__('Security')">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Security') }}</h1>

    <section class="card mb-6 max-w-3xl">
        <h2 class="mb-1 flex items-center gap-2 text-lg font-semibold"><x-kb-icon name="key" class="size-5" /> {{ __('Passkeys') }}</h2>
        <p class="mb-4 text-sm text-slate-600">{{ __('Sign in with your fingerprint, face or device PIN – no password, no e-mail link. Recommended.') }}</p>

        @if ($passkeys->isEmpty())
            <p class="mb-4 text-sm text-slate-500">{{ __('No passkey set up yet.') }}</p>
        @else
            <ul class="mb-4 divide-y divide-slate-100 rounded-md border border-slate-200">
                @foreach ($passkeys as $passkey)
                    <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                        <span>
                            <span class="font-medium">{{ $passkey->name }}</span>
                            <span class="block text-xs text-slate-500">
                                {{ __('Added :date', ['date' => \App\Core\Support\Dates::display($passkey->created_at, 'date')]) }}
                                @if ($passkey->last_used_at) · {{ __('last used :date', ['date' => \App\Core\Support\Dates::display($passkey->last_used_at, 'relative')]) }} @endif
                            </span>
                        </span>
                        <form method="post" action="{{ route('passkey.destroy', $passkey) }}" data-confirm="{{ __('Remove this passkey?') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-red-600" title="{{ __('Remove') }}"><x-kb-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Remove') }}</span></button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <div data-passkey-register data-confirm-url="{{ route('password.confirm') }}"
             data-error="{{ __('The passkey could not be added.') }}" data-success="{{ __('Passkey added.') }}" hidden>
            <div class="flex flex-wrap gap-2">
                <label for="passkey-name" class="sr-only">{{ __('Name of the passkey') }}</label>
                <input id="passkey-name" type="text" maxlength="255" class="input max-w-xs" placeholder="{{ __('e.g. Laptop or phone') }}" data-passkey-name>
                <button type="button" class="btn-primary" data-passkey-register-button><x-kb-icon name="plus" class="size-4" /> {{ __('Add passkey') }}</button>
            </div>
            <p class="mt-2 hidden text-sm" data-passkey-error role="alert"></p>
        </div>
        <noscript><p class="text-sm text-amber-700">{{ __('Adding a passkey needs JavaScript.') }}</p></noscript>
    </section>

    <section class="card max-w-3xl">
        <h2 class="mb-1 flex items-center gap-2 text-lg font-semibold"><x-kb-icon name="shield" class="size-5" /> {{ __('Authenticator app (two-factor authentication)') }}</h2>
        <p class="mb-4 text-sm text-slate-600">{{ __('After signing in with an e-mail link you also enter a code from an authenticator app.') }}</p>

        @if ($recoveryCodes)
            <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4">
                <p class="mb-2 text-sm font-medium text-amber-900">{{ __('Store these recovery codes in a safe place. Each code works once. They are only shown now.') }}</p>
                <ul class="grid grid-cols-2 gap-1 font-mono text-sm">
                    @foreach ($recoveryCodes as $code) <li>{{ $code }}</li> @endforeach
                </ul>
            </div>
        @endif

        @if ($user->hasTotp())
            <p class="mb-4 text-sm text-green-700">{{ __('Two-factor authentication is enabled.') }}</p>
            <form method="post" action="{{ route('account.totp.disable') }}" data-confirm="{{ __('Disable two-factor authentication?') }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn-secondary">{{ __('Disable') }}</button>
            </form>
        @elseif ($setupSecret)
            <div class="space-y-4">
                <p class="text-sm">{{ __('Scan the QR code with your authenticator app, then enter the code it shows.') }}</p>
                <div class="inline-block rounded-md border border-slate-200 bg-white p-2">{!! $qrCode !!}</div>
                <p class="text-xs text-slate-500">{{ __('Or enter this key manually:') }} <code class="break-all font-mono">{{ $setupSecret }}</code></p>
                <form method="post" action="{{ route('account.totp.confirm') }}" class="flex flex-wrap gap-2">
                    @csrf
                    <label for="code" class="sr-only">{{ __('Code') }}</label>
                    <input id="code" name="code" type="text" required maxlength="20" inputmode="numeric" autocomplete="one-time-code" class="input max-w-40">
                    <button type="submit" class="btn-primary">{{ __('Enable') }}</button>
                </form>
            </div>
        @else
            <form method="post" action="{{ route('account.totp.start') }}">
                @csrf
                <button type="submit" class="btn-secondary">{{ __('Set up authenticator app') }}</button>
            </form>
        @endif
    </section>
</x-layouts.app>
