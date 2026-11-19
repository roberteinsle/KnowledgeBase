@props(['nodes', 'currentPageId' => null, 'depth' => 0])
<ul @class(['space-y-0.5', 'ml-3 border-l border-slate-100 pl-2' => $depth > 0])>
    @foreach ($nodes as $node)
        @php($isCurrent = $node['id'] === $currentPageId)
        <li>
            @if (! empty($node['children']))
                <details @if ($depth < 1 || $isCurrent || \App\Support\Tree::contains($node['children'], $currentPageId)) open @endif class="group">
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded py-1 pr-2 hover:bg-slate-100 [&::-webkit-details-marker]:hidden">
                        <x-kb-icon name="chevron-right" class="size-3.5 shrink-0 text-slate-400 transition group-open:rotate-90" />
                        <a href="{{ route('pages.show', $node['id']) }}" @class(['truncate', 'font-semibold text-indigo-700' => $isCurrent, 'text-slate-700' => ! $isCurrent]) @if ($isCurrent) aria-current="page" @endif>{{ $node['title'] }}</a>
                    </summary>
                    <x-page-tree :nodes="$node['children']" :current-page-id="$currentPageId" :depth="$depth + 1" />
                </details>
            @else
                <a href="{{ route('pages.show', $node['id']) }}" @class([
                    'flex items-center gap-1 truncate rounded py-1 pl-[1.125rem] pr-2 hover:bg-slate-100',
                    'font-semibold text-indigo-700' => $isCurrent,
                    'text-slate-700' => ! $isCurrent,
                ]) @if ($isCurrent) aria-current="page" @endif>{{ $node['title'] }}</a>
            @endif
        </li>
    @endforeach
</ul>
