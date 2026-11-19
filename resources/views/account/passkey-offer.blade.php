<x-layouts.guest :title="__('Set up a passkey')">
    <div class="text-center">
        <x-kb-icon name="key" class="mx-auto mb-3 size-10 text-indigo-600" />
        <h2 class="mb-2 text-lg font-semibold">{{ __('Sign in faster next time') }}</h2>
        <p class="mb-6 text-sm text-slate-600">{{ __('With a passkey you sign in with your fingerprint, face or device PIN – no waiting for e-mails.') }}</p>
    </div>

    <div data-passkey-register data-redirect="{{ session('url.intended', route('home')) }}"
         data-error="{{ __('The passkey could not be added.') }}" hidden class="mb-3">
        <input type="hidden" data-passkey-name value="{{ __('Passkey') }} {{ now()->format('Y-m-d') }}">
        <button type="button" class="btn-primary w-full py-2.5" data-passkey-register-button>{{ __('Set up a passkey now') }}</button>
        <p class="mt-2 hidden text-center text-sm" data-passkey-error role="alert"></p>
    </div>

    <form method="post" action="{{ route('account.passkey-offer.dismiss') }}">
        @csrf
        <button type="submit" class="btn-ghost w-full">{{ __('Later') }}</button>
    </form>
</x-layouts.guest>
