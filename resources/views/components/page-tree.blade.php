@props(['nodes', 'currentPageId' => null, 'favorites' => [], 'canReorder' => false, 'space' => null, 'depth' => 0])
<ul @if ($depth === 0) role="tree" aria-label="{{ __('Pages') }}" @else role="group" @endif class="space-y-px">
    @foreach ($nodes as $node)
        @php($isCurrent = $node['id'] === $currentPageId)
        @php($hasChildren = ! empty($node['children']))
        @php($open = $hasChildren && \App\Support\Tree::contains($node['children'], $currentPageId))
        <li role="treeitem" data-page-id="{{ $node['id'] }}" data-favorite="{{ in_array($node['id'], $favorites, true) ? '1' : '0' }}"
            @if ($hasChildren) aria-expanded="{{ $open ? 'true' : 'false' }}" @endif @if ($isCurrent) aria-current="page" @endif>
            <div data-tree-row @if ($canReorder) draggable="true" @endif
                 @class([
                     'group relative flex items-center gap-1 rounded-md py-1 pr-1 text-sm',
                     'bg-slate-200/70 font-medium text-slate-900' => $isCurrent,
                     'text-slate-700 hover:bg-slate-100' => ! $isCurrent,
                 ])
                 style="padding-left: {{ 0.25 + $depth * 1 }}rem">
                @if ($hasChildren)
                    <button type="button" data-tree-toggle class="grid size-5 shrink-0 place-items-center rounded text-slate-500 hover:bg-slate-200"
                            aria-label="{{ __('Show or hide sub-pages') }}">
                        <x-kb-icon name="chevron-right" class="size-3.5 transition-transform" data-chevron />
                    </button>
                @else
                    <span class="grid size-5 shrink-0 place-items-center" aria-hidden="true"><span class="size-1 rounded-full bg-slate-400"></span></span>
                @endif
                <x-kb-icon name="document" class="size-4 shrink-0 text-slate-400" />
                <a href="{{ route('pages.show', $node['id']) }}" class="min-w-0 flex-1 truncate" draggable="false">{{ $node['title'] }}</a>
                <span class="flex shrink-0 items-center opacity-0 transition group-hover:opacity-100 group-focus-within:opacity-100 max-md:opacity-100">
                    <button type="button" data-page-menu class="grid size-6 place-items-center rounded text-slate-500 hover:bg-slate-200"
                            aria-haspopup="menu" title="{{ __('Page actions') }}">
                        <x-kb-icon name="dots-vertical" class="size-4" /><span class="sr-only">{{ __('Page actions') }}: {{ $node['title'] }}</span>
                    </button>
                    @if ($canReorder)
                        <a href="{{ route('pages.create', [$space, 'parent' => $node['id']]) }}" class="grid size-6 place-items-center rounded text-slate-500 hover:bg-slate-200"
                           title="{{ __('New sub-page') }}" draggable="false">
                            <x-kb-icon name="plus" class="size-4" /><span class="sr-only">{{ __('New sub-page') }}: {{ $node['title'] }}</span>
                        </a>
                    @endif
                </span>
            </div>
            @if ($hasChildren)
                <div data-tree-children @unless ($open) hidden @endunless>
                    <x-page-tree :nodes="$node['children']" :current-page-id="$currentPageId" :favorites="$favorites" :can-reorder="$canReorder" :space="$space" :depth="$depth + 1" />
                </div>
            @endif
        </li>
    @endforeach
</ul>
