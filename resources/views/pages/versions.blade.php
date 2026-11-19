<x-layouts.app :title="__('Version history')">
    <a href="{{ $page->url() }}" class="text-sm text-slate-500 hover:text-indigo-700">← {{ $page->title }}</a>
    <h1 class="mb-6 mt-2 text-2xl font-semibold">{{ __('Version history') }}</h1>

    <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
        @foreach ($versions as $version)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <a href="{{ route('pages.versions.show', [$page, $version->version]) }}" class="min-w-0">
                    <span class="font-medium text-indigo-700">{{ __('Version :version', ['version' => $version->version]) }}</span>
                    @if ($version->version === $page->version) <span class="ml-2 rounded bg-green-50 px-1.5 py-0.5 text-xs text-green-700">{{ __('current') }}</span> @endif
                    <span class="block text-sm text-slate-500">{{ \App\Core\Support\Dates::display($version->created_at) }} · {{ $version->user?->name ?? __('unknown') }}</span>
                </a>
                @if ($version->version !== $page->version)
                    @can('update', $page)
                        <form method="post" action="{{ route('pages.versions.restore', [$page, $version->version]) }}" data-confirm="{{ __('Restore this version? The current content is kept as an older version.') }}">
                            @csrf
                            <button type="submit" class="btn-secondary"><x-kb-icon name="restore" class="size-4" /> {{ __('Restore') }}</button>
                        </form>
                    @endcan
                @endif
            </li>
        @endforeach
    </ul>
    <div class="mt-4">{{ $versions->links() }}</div>
</x-layouts.app>
