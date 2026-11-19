{{-- Page tree of the current space (Pages module). Menu entries come from the MenuRegistry. --}}
<div class="pt-3" data-page-tree data-space-id="{{ $space->id }}"
     @if ($canReorder) data-place-url="{{ route('pages.place', '__PAGE__') }}" @endif
     data-copied-label="{{ __('Link copied') }}" data-content-copied-label="{{ __('Content copied') }}" data-move-error="{{ __('The page could not be moved.') }}">
    <div class="mb-1 flex items-center justify-between px-2">
        <h2 class="text-xs font-medium text-slate-500">{{ __('Pages') }}</h2>
        <div class="flex items-center gap-1">
            @if (count($spaceMenu) > 0)
                <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" title="{{ __('More') }}"
                            class="grid size-6 place-items-center rounded border border-slate-200 bg-white text-slate-600 hover:bg-slate-100">
                        <x-kb-icon name="dots" class="size-4" /><span class="sr-only">{{ __('More') }}</span>
                    </button>
                    <div x-cloak x-show="open" @click.outside="open = false" role="menu"
                         class="absolute right-0 z-30 mt-1 w-64 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                        @foreach ($spaceMenu as $item)
                            <a role="menuitem" href="{{ $item->urlFor($space->slug) }}" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-slate-50">
                                <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @if ($canReorder)
                <a href="{{ route('pages.create', $space) }}" title="{{ __('New page') }}"
                   class="grid size-6 place-items-center rounded border border-slate-200 bg-white text-slate-600 hover:bg-slate-100">
                    <x-kb-icon name="plus" class="size-4" /><span class="sr-only">{{ __('New page') }}</span>
                </a>
            @endif
        </div>
    </div>

    @if (empty($tree))
        <p class="px-2 py-1 text-sm text-slate-500">{{ __('No pages yet.') }}</p>
    @else
        <x-page-tree :nodes="$tree" :current-page-id="$currentPageId" :favorites="$favorites" :can-reorder="$canReorder" :space="$space" />
        @if ($canReorder)
            <p class="mt-3 px-2 text-xs text-slate-400 max-md:hidden">{{ __('Drag pages to sort them or drop them onto another page.') }}</p>
        @endif
    @endif

    {{-- One "⋮" menu for all pages; __PAGE__ is replaced by the page id in resources/js/tree.js. --}}
    <template data-page-menu-template>
        <div role="menu" class="fixed z-50 w-64 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
            @foreach ($pageMenu as $item)
                @php($url = $item->urlFor('__PAGE__'))
                @php($classes = 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm '.($item->danger ? 'text-red-700 hover:bg-red-50' : 'hover:bg-slate-50'))
                @if ($item->danger)
                    <div class="my-1 border-t border-slate-100"></div>
                @endif
                @switch($item->kind)
                    @case(\App\Core\Navigation\MenuItem::COPY)
                        <button type="button" role="menuitem" data-copy-url="{{ url($url) }}" class="{{ $classes }}">
                            <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}
                        </button>
                        @break
                    @case(\App\Core\Navigation\MenuItem::COPY_CONTENT)
                        <button type="button" role="menuitem" data-copy-fetch="{{ $url }}" class="{{ $classes }}">
                            <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}
                        </button>
                        @break
                    @case(\App\Core\Navigation\MenuItem::POST)
                    @case(\App\Core\Navigation\MenuItem::DELETE)
                        <form method="post" action="{{ $url }}" @if ($item->confirm) data-confirm="{{ __($item->confirm) }}" @endif>
                            @csrf
                            @if ($item->kind === \App\Core\Navigation\MenuItem::DELETE) @method('DELETE') @endif
                            <button type="submit" role="menuitem" class="{{ $classes }}"
                                    @if ($item->activeLabel) data-active-label="{{ __($item->activeLabel) }}" @endif>
                                <x-kb-icon :name="$item->icon" class="size-4 {{ $item->danger ? '' : 'text-slate-500' }}" />
                                <span data-label>{{ __($item->label) }}</span>
                            </button>
                        </form>
                        @break
                    @default
                        <a role="menuitem" href="{{ $url }}" class="{{ $classes }}">
                            <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}
                        </a>
                @endswitch
            @endforeach
        </div>
    </template>
</div>
