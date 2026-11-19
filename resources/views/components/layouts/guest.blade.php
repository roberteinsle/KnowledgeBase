@props(['title' => null])
@php($workspace = \App\Modules\Workspace\Models\Workspace::current())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <title>{{ $title ? $title.' · ' : '' }}{{ $workspace->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center px-4 py-12 text-slate-900 antialiased">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex flex-col items-center gap-3">
            <span class="grid size-12 place-items-center rounded-xl bg-indigo-600 text-xl font-semibold text-white">{{ mb_substr($workspace->name, 0, 1) }}</span>
            <h1 class="text-xl font-semibold">{{ $workspace->name }}</h1>
        </div>

        <div class="card p-6">
            <x-flash />
            {{ $slot }}
        </div>

        <form method="post" action="{{ route('locale.guest') }}" class="mt-6 flex justify-center">
            @csrf
            <label for="guest-locale" class="sr-only">{{ __('Language') }}</label>
            <select id="guest-locale" name="locale" class="rounded-md border-0 bg-transparent text-sm text-slate-500" data-autosubmit>
                @foreach (\App\Core\Support\Locales::all() as $code => $name)
                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $name }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="ml-2 text-sm underline">{{ __('Change') }}</button></noscript>
        </form>
    </div>
</body>
</html>
