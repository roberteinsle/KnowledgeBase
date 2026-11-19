@props(['title' => null, 'space' => null, 'tree' => null, 'currentPageId' => null])
@php
    $user = auth()->user();
    $navigation = app(\App\Core\Navigation\NavigationRegistry::class);
    $workspace = \App\Modules\Workspace\Models\Workspace::current();
    $isActive = fn ($item) => collect($item->activeRoutes ?: [$item->route])->contains(fn ($pattern) => request()->routeIs($pattern));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ $workspace->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-slate-900 antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2">{{ __('Skip to content') }}</a>

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-14 max-w-screen-2xl items-center gap-3 px-4">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 font-semibold text-slate-900">
            <span class="grid size-8 place-items-center rounded-md bg-indigo-600 text-white">{{ mb_substr($workspace->name, 0, 1) }}</span>
            <span class="hidden sm:inline">{{ $workspace->name }}</span>
        </a>

        <form action="{{ route('search') }}" method="get" role="search" class="mx-auto hidden w-full max-w-xl md:block">
            <label for="global-search" class="sr-only">{{ __('Search') }}</label>
            <div class="relative">
                <x-kb-icon name="search" class="pointer-events-none absolute left-3 top-2.5 size-4 text-slate-400" />
                <input id="global-search" name="q" type="search" value="{{ request()->routeIs('search') ? request('q') : '' }}"
                       placeholder="{{ __('Search pages and attachments') }}" maxlength="200"
                       class="input pl-9" autocomplete="off" data-shortcut="/">
            </div>
        </form>

        <div class="ml-auto md:ml-0" x-data="{ open: false }" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu"
                    class="flex items-center gap-2 rounded-full p-1 pr-2 text-sm text-slate-700 hover:bg-slate-100">
                <span class="grid size-8 place-items-center rounded-full bg-slate-200 font-medium">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <span class="hidden max-w-40 truncate lg:inline">{{ $user->name }}</span>
                <x-kb-icon name="chevron-down" class="size-4" />
            </button>
            <div x-cloak x-show="open" @click.outside="open = false" role="menu"
                 class="absolute right-4 mt-2 w-60 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                <div class="border-b border-slate-100 px-4 py-2 text-xs text-slate-500">{{ $user->email }}</div>
                @foreach ($navigation->for($user, 'account') as $item)
                    <a href="{{ route($item->route) }}" role="menuitem" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-slate-50">
                        <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}
                    </a>
                @endforeach
                <form method="post" action="{{ route('logout') }}" class="border-t border-slate-100">
                    @csrf
                    <button type="submit" role="menuitem" class="flex w-full items-center gap-3 px-4 py-2 text-sm hover:bg-slate-50">
                        <x-kb-icon name="logout" class="size-4 text-slate-500" /> {{ __('Sign out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="mx-auto flex max-w-screen-2xl">
    <aside class="sticky top-14 hidden h-[calc(100vh-3.5rem)] w-72 shrink-0 overflow-y-auto border-r border-slate-200 bg-white px-3 py-4 md:block" aria-label="{{ __('Navigation') }}">
        <nav class="space-y-1">
            @foreach ($navigation->for($user, 'main') as $item)
                <a href="{{ route($item->route) }}" @class([
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium',
                    'bg-indigo-50 text-indigo-700' => $isActive($item),
                    'text-slate-700 hover:bg-slate-100' => ! $isActive($item),
                ]) @if ($isActive($item)) aria-current="page" @endif>
                    <x-kb-icon :name="$item->icon" class="size-5" /> {{ __($item->label) }}
                </a>
            @endforeach
        </nav>

        @if ($space)
            <x-space-sidebar :space="$space" :tree="$tree" :current-page-id="$currentPageId" class="mt-6 border-t border-slate-100 pt-4" />
        @endif
    </aside>

    <main id="main" class="min-w-0 flex-1 px-4 pb-24 pt-6 md:px-8 md:pb-10">
        @if ($space)
            <details class="mb-4 rounded-lg border border-slate-200 bg-white md:hidden">
                <summary class="flex cursor-pointer items-center gap-2 px-4 py-3 text-sm font-medium">
                    <x-kb-icon name="bars" class="size-4" /> {{ __('Pages in :space', ['space' => $space->name]) }}
                </summary>
                <x-space-sidebar :space="$space" :tree="$tree" :current-page-id="$currentPageId" class="px-3 pb-3" />
            </details>
        @endif

        <x-flash />

        {{ $slot }}
    </main>
</div>

{{-- Mobile: bottom tab bar with the main areas --}}
<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="{{ __('Main areas') }}">
    <ul class="flex">
        @foreach ($navigation->mobileTabs($user) as $item)
            <li class="flex-1">
                <a href="{{ route($item->route) }}" @class([
                    'flex flex-col items-center gap-0.5 py-2 text-xs',
                    'text-indigo-700' => $isActive($item),
                    'text-slate-500' => ! $isActive($item),
                ]) @if ($isActive($item)) aria-current="page" @endif>
                    <x-kb-icon :name="$item->icon" class="size-6" /> {{ __($item->label) }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
</body>
</html>
