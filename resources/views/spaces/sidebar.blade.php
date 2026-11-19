{{-- Space switcher (Spaces module) and the space links other modules register in the "space" menu. --}}
<div class="border-b border-slate-200 pb-3">
    @if ($current)
        <div class="relative" x-data="{ open: false, q: '' }" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open; $nextTick(() => open && $refs.filter.focus())" :aria-expanded="open" aria-haspopup="listbox"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left hover:bg-slate-100">
                <x-space-icon :space="$current" size="size-6" />
                <span class="min-w-0 flex-1 truncate text-[0.95rem] font-semibold text-slate-900">{{ $current->name }}</span>
                <x-kb-icon name="chevron-down" class="size-4 text-slate-500 transition" x-bind:class="open && 'rotate-180'" />
            </button>

            <div x-cloak x-show="open" @click.outside="open = false"
                 class="absolute inset-x-0 z-30 mt-1 rounded-lg border border-slate-200 bg-white shadow-lg">
                <div class="border-b border-slate-100 p-2">
                    <label for="space-filter" class="sr-only">{{ __('Search spaces') }}</label>
                    <input id="space-filter" x-ref="filter" x-model="q" type="search" class="input" placeholder="{{ __('Search spaces') }}" autocomplete="off">
                </div>
                <ul class="max-h-80 overflow-y-auto py-1" role="listbox" aria-label="{{ __('Spaces') }}">
                    @foreach ($spaces as $space)
                        <li x-show="q === '' || @js(mb_strtolower($space->name)).includes(q.toLowerCase())" role="option" @if ($space->is($current)) aria-selected="true" @endif>
                            <a href="{{ route('spaces.show', $space) }}" @class([
                                'flex items-center gap-2 px-3 py-1.5 text-sm hover:bg-slate-50',
                                'font-semibold text-indigo-700' => $space->is($current),
                            ])>
                                <x-space-icon :space="$space" size="size-5" /> <span class="truncate">{{ $space->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('spaces.index') }}" class="block border-t border-slate-100 px-3 py-2 text-xs text-slate-500 hover:bg-slate-50">{{ __('All spaces') }}</a>
            </div>
        </div>

        <nav class="mt-1 space-y-0.5" aria-label="{{ __('Space') }}">
            @foreach ($links as $link)
                @php($url = $link->urlFor($current->slug))
                <a href="{{ $url }}" @class([
                    'flex items-center gap-3 rounded-md px-2 py-1.5 text-sm',
                    'bg-slate-100 font-medium text-slate-900' => url()->current() === strtok($url, '?'),
                    'text-slate-700 hover:bg-slate-100' => url()->current() !== strtok($url, '?'),
                ])>
                    <x-kb-icon :name="$link->icon" class="size-4 text-slate-500" /> {{ __($link->label) }}
                </a>
            @endforeach
        </nav>
    @else
        <p class="px-2 py-2 text-sm text-slate-500">{{ __('You do not have access to any space yet.') }}</p>
    @endif
</div>
