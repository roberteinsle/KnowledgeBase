<x-layouts.app :title="__('Version :version', ['version' => $version->version])" :space="$space" :tree="$tree" :current-page-id="$page->id">
    <a href="{{ route('pages.versions', $page) }}" class="text-sm text-slate-500 hover:text-indigo-700">← {{ __('Version history') }}</a>
    <div class="mb-6 mt-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('Version :version', ['version' => $version->version]) }}: {{ $version->title }}</h1>
        @if ($version->version !== $page->version)
            @can('update', $page)
                <form method="post" action="{{ route('pages.versions.restore', [$page, $version->version]) }}">
                    @csrf
                    <button type="submit" class="btn-primary"><x-kb-icon name="restore" class="size-4" /> {{ __('Restore this version') }}</button>
                </form>
            @endcan
        @endif
    </div>
    <p class="mb-6 text-sm text-slate-500">{{ \App\Core\Support\Dates::display($version->created_at) }} · {{ $version->user?->name ?? __('unknown') }}</p>

    <div x-data="{ tab: 'diff' }">
        <div class="mb-4 flex gap-2" role="tablist">
            <button type="button" role="tab" :aria-selected="tab === 'diff'" @click="tab = 'diff'" class="btn-secondary" :class="tab === 'diff' && 'border-indigo-500 text-indigo-700'">{{ $previous ? __('Changes to version :version', ['version' => $previous->version]) : __('Changes') }}</button>
            <button type="button" role="tab" :aria-selected="tab === 'view'" @click="tab = 'view'" class="btn-secondary" :class="tab === 'view' && 'border-indigo-500 text-indigo-700'">{{ __('Content') }}</button>
        </div>

        <div x-show="tab === 'diff'" class="overflow-x-auto rounded-lg border border-slate-200 bg-white font-mono text-sm">
            @foreach ($diff as [$type, $line])
                <div @class([
                    'whitespace-pre-wrap px-4 py-0.5',
                    'bg-green-50 text-green-900' => $type === 'add',
                    'bg-red-50 text-red-900 line-through decoration-red-300' => $type === 'remove',
                    'text-slate-600' => $type === 'same',
                ])><span class="mr-2 select-none text-slate-400">{{ $type === 'add' ? '+' : ($type === 'remove' ? '−' : ' ') }}</span>{{ $line }}</div>
            @endforeach
        </div>

        <article x-cloak x-show="tab === 'view'" class="kb-prose prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-a:text-indigo-700 prose-img:rounded-md prose-table:text-sm card">{!! $html !!}</article>
    </div>
</x-layouts.app>
