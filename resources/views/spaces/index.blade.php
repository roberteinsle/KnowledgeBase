<x-layouts.app :title="__('Spaces')">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('Spaces') }}</h1>
        @can('create', \App\Modules\Spaces\Models\Space::class)
            <a href="{{ route('filament.admin.resources.spaces.create') }}" class="btn-primary"><x-kb-icon name="plus" class="size-4" /> {{ __('New space') }}</a>
        @endcan
    </div>
    @include('spaces.widget', ['spaces' => $spaces])
</x-layouts.app>
