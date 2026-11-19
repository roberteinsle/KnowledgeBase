<x-layouts.guest :title="__('Sign in')">
    <h2 class="mb-2 text-center text-lg font-semibold">{{ __('Confirm sign-in') }}</h2>
    <p class="mb-6 text-center text-sm text-slate-600">{{ __('Click the button to finish signing in.') }}</p>
    <form method="post" action="{{ route('magic-link.consume', ['token' => $token]) }}">
        @csrf
        <button type="submit" class="btn-primary w-full py-2.5">{{ __('Sign in now') }}</button>
    </form>
</x-layouts.guest>
