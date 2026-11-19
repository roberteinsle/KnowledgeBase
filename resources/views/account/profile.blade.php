<x-layouts.app :title="__('Account')">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Account') }}</h1>

    <section class="card mb-6 max-w-xl">
        <h2 class="mb-3 text-lg font-semibold">{{ __('Profile picture') }}</h2>
        <div class="flex flex-wrap items-center gap-4">
            <img src="{{ $user->avatarUrl() }}" alt="" class="size-20 rounded-full object-cover ring-1 ring-slate-200">
            <div class="space-y-2">
                <form method="post" action="{{ route('account.avatar.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <label for="avatar" class="sr-only">{{ __('Profile picture') }}</label>
                    <input id="avatar" name="avatar" type="file" required accept="image/png,image/jpeg,image/webp,image/gif"
                           class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                    <button type="submit" class="btn-secondary"><x-kb-icon name="upload" class="size-4" /> {{ __('Upload') }}</button>
                </form>
                <p class="text-xs text-slate-500">{{ __('PNG, JPG, WebP or GIF, at most 1 MB. Without a picture your initials are shown.') }}</p>
                @if ($user->avatar_path)
                    <form method="post" action="{{ route('account.avatar.destroy') }}" data-confirm="{{ __('Remove the profile picture?') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-700 hover:underline">{{ __('Remove picture') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
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

    @foreach (app(\App\Core\Account\AccountSectionRegistry::class)->renderFor($user) as $section)
        {{ $section }}
    @endforeach

    <p class="mt-6 text-sm"><a href="{{ route('account.security') }}" class="text-indigo-700 hover:underline">{{ __('Passkeys and two-factor authentication') }} →</a></p>
</x-layouts.app>
