@props(['space', 'tree' => [], 'currentPageId' => null])
<div {{ $attributes }}>
    <a href="{{ route('spaces.show', $space) }}" class="mb-2 flex items-center gap-2 px-2 text-sm font-semibold text-slate-900 hover:text-indigo-700">
        <span>{{ $space->icon ?: '📁' }}</span> <span class="truncate">{{ $space->name }}</span>
    </a>

    <nav aria-label="{{ __('Pages') }}" class="text-sm">
        @if (empty($tree))
            <p class="px-2 py-1 text-slate-500">{{ __('No pages yet.') }}</p>
        @else
            <x-page-tree :nodes="$tree" :current-page-id="$currentPageId" />
        @endif
    </nav>

    <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
        @can('contribute', $space)
            <a href="{{ route('pages.create', $space) }}" class="flex items-center gap-2 rounded px-2 py-1.5 text-slate-600 hover:bg-slate-100">
                <x-kb-icon name="plus" class="size-4" /> {{ __('New page') }}
            </a>
            <a href="{{ route('import.create', $space) }}" class="flex items-center gap-2 rounded px-2 py-1.5 text-slate-600 hover:bg-slate-100">
                <x-kb-icon name="upload" class="size-4" /> {{ __('Import Markdown') }}
            </a>
        @endcan
        @can('moderate', $space)
            <a href="{{ route('trash.index', $space) }}" class="flex items-center gap-2 rounded px-2 py-1.5 text-slate-600 hover:bg-slate-100">
                <x-kb-icon name="trash" class="size-4" /> {{ __('Trash') }}
            </a>
        @endcan
    </div>
</div>
