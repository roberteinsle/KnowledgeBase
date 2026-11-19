@php($limit = $limit ?? null)
<h2 class="mb-3 text-lg font-semibold">{{ __('Your spaces') }}</h2>
@if ($spaces->isEmpty())
    <div class="card text-sm text-slate-600">
        {{ __('You do not have access to any space yet.') }}
        @if (auth()->user()->isAdmin())
            <a class="text-indigo-700 underline" href="{{ route('filament.admin.resources.spaces.create') }}">{{ __('Create the first space') }}</a>
        @else
            {{ __('Ask an administrator to add you.') }}
        @endif
    </div>
@else
    {{-- A–Z; with a limit (home page) only the first ones, the rest behind "Show all". --}}
    <div x-data="{ all: false }">
    <ul class="grid gap-3 sm:grid-cols-2">
        @foreach ($spaces as $space)
            <li @if ($limit !== null && $loop->index >= $limit) x-show="all" x-cloak @endif>
                <a href="{{ route('spaces.show', $space) }}" class="card flex h-full gap-3 hover:border-indigo-300 hover:shadow">
                    <x-space-icon :space="$space" size="size-8" class="text-2xl" />
                    <span class="min-w-0">
                        <span class="block font-medium text-slate-900">{{ $space->name }}</span>
                        @if ($space->description)
                            <span class="mt-0.5 line-clamp-2 block text-sm text-slate-600">{{ $space->description }}</span>
                        @endif
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
    @if ($limit !== null && $spaces->count() > $limit)
        <button type="button" class="btn-secondary mt-3" x-show="!all" @click="all = true">
            {{ __('Show all spaces (:count)', ['count' => $spaces->count()]) }}
        </button>
    @endif
    </div>
@endif
