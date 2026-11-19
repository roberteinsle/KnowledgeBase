<h2 class="mb-3 text-lg font-semibold">{{ __('Your spaces') }}</h2>
@if ($spaces->isEmpty())
    <div class="card text-sm text-slate-600">
        {{ __('You do not have access to any space yet.') }}
        @if (auth()->user()->isAdmin())
            <a class="text-indigo-700 underline" href="{{ route('filament.admin.resources.bereiche.create') }}">{{ __('Create the first space') }}</a>
        @else
            {{ __('Ask an administrator to add you.') }}
        @endif
    </div>
@else
    <ul class="grid gap-3 sm:grid-cols-2">
        @foreach ($spaces as $space)
            <li>
                <a href="{{ route('spaces.show', $space) }}" class="card flex h-full gap-3 hover:border-indigo-300 hover:shadow">
                    <span class="text-2xl" aria-hidden="true">{{ $space->icon ?: '📁' }}</span>
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
@endif
