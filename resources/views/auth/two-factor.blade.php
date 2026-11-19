<x-layouts.guest :title="__('Two-factor authentication')">
    <h2 class="mb-2 text-center text-lg font-semibold">{{ __('Two-factor authentication') }}</h2>
    <p class="mb-6 text-center text-sm text-slate-600">{{ __('Enter the code from your authenticator app or one of your recovery codes.') }}</p>
    <form method="post" action="{{ route('two-factor.verify') }}" class="space-y-4">
        @csrf
        <div>
            <label for="code" class="label">{{ __('Code') }}</label>
            <input id="code" name="code" type="text" required maxlength="20" autocomplete="one-time-code" inputmode="numeric" autofocus class="input text-center text-lg tracking-widest">
        </div>
        <button type="submit" class="btn-primary w-full">{{ __('Continue') }}</button>
    </form>
</x-layouts.guest>
