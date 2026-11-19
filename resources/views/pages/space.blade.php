<x-layouts.app :title="$space->name" :space="$space" :tree="$tree">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="flex items-center gap-2 text-2xl font-semibold"><span aria-hidden="true">{{ $space->icon ?: '📁' }}</span> {{ $space->name }}</h1>
            @if ($space->description)
                <p class="mt-1 max-w-3xl text-slate-600">{{ $space->description }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @can('contribute', $space)
                <a href="{{ route('pages.create', $space) }}" class="btn-primary"><x-kb-icon name="plus" class="size-4" /> {{ __('New page') }}</a>
            @endcan
            <x-export-menu :exporters="$exporters" route="export.space" :subject="$space" />
        </div>
    </div>

    @if (empty($tree))
        <div class="card text-center">
            <p class="text-slate-600">{{ __('This space has no pages yet.') }}</p>
            @can('contribute', $space)
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('pages.create', $space) }}" class="btn-primary">{{ __('Write the first page') }}</a>
                    <a href="{{ route('import.create', $space) }}" class="btn-secondary">{{ __('Import Markdown') }}</a>
                </div>
            @endcan
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-2">
            <section>
                <h2 class="mb-3 text-lg font-semibold">{{ __('Pages') }}</h2>
                <div class="card"><x-page-tree :nodes="$tree" /></div>
            </section>
            <section>
                @include('pages.widget-recent', ['pages' => $recent])
            </section>
        </div>
    @endif
</x-layouts.app>
