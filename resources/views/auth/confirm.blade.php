<x-layouts.guest :title="__('Confirm it is you')">
    <h2 class="mb-2 text-center text-lg font-semibold">{{ __('Confirm it is you') }}</h2>
    <p class="mb-6 text-center text-sm text-slate-600">{{ __('This action needs a fresh confirmation.') }}</p>

    @if ($hasPasskey)
        <div data-passkey-confirm data-error="{{ __('Confirmation with a passkey did not work. Please try again.') }}" hidden class="mb-4">
            <button type="button" class="btn-primary w-full" data-passkey-confirm-button><x-kb-icon name="key" class="size-5" /> {{ __('Confirm with a passkey') }}</button>
            <p class="mt-2 hidden text-center text-sm text-red-700" data-passkey-error role="alert"></p>
        </div>
    @endif

    <form method="post" action="{{ route('password.confirm.link') }}">
        @csrf
        <button type="submit" class="btn-secondary w-full"><x-kb-icon name="mail" class="size-4" /> {{ __('Send me a confirmation link') }}</button>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('home') }}" class="text-slate-500 hover:text-indigo-700">{{ __('Cancel') }}</a></p>
</x-layouts.guest>
