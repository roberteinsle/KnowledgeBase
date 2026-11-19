<x-layouts.app :title="$space->name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <x-space-icon :space="$space" size="size-10" class="text-xl" />
            <div class="min-w-0">
                <h1 class="truncate text-2xl font-semibold">{{ $space->name }}</h1>
                @if ($space->description)
                    <p class="mt-0.5 max-w-3xl text-slate-600">{{ $space->description }}</p>
                @endif
            </div>
        </div>
        @can('contribute', $space)
            <a href="{{ route('pages.create', $space) }}" class="btn-primary"><x-kb-icon name="plus" class="size-4" /> {{ __('New page') }}</a>
        @endcan
    </div>

    @if ($empty)
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
        @include('pages.overview-tabs', ['lists' => $lists, 'showSpace' => false])
    @endif
</x-layouts.app>
