<x-layouts.app :title="__('Account')">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Account') }}</h1>
    <form method="post" action="{{ route('account.update') }}" class="card max-w-xl space-y-4">
        @csrf @method('PUT')
        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $user->name) }}" class="input" autocomplete="name">
        </div>
        <div>
            <span class="label">{{ __('E-mail address') }}</span>
            <p class="text-sm text-slate-600">{{ $user->email }}</p>
            <p class="text-xs text-slate-500">{{ __('Only an administrator can change the address.') }}</p>
        </div>
        <div>
            <label for="locale" class="label">{{ __('Language') }}</label>
            <select id="locale" name="locale" class="input">
                <option value="">{{ __('Workspace default') }}</option>
                @foreach (\App\Core\Support\Locales::all() as $code => $name)
                    <option value="{{ $code }}" @selected(old('locale', $user->locale) === $code)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">{{ __('Save') }}</button>
    </form>

    <p class="mt-6 text-sm"><a href="{{ route('account.security') }}" class="text-indigo-700 hover:underline">{{ __('Passkeys and two-factor authentication') }} →</a></p>
</x-layouts.app>
