<x-layouts.app :title="__('Search')">
    <h1 class="mb-4 text-2xl font-semibold">{{ __('Search') }}</h1>
    <form action="{{ route('search') }}" method="get" role="search" class="mb-8 flex max-w-2xl gap-2">
        <label for="q" class="sr-only">{{ __('Search term') }}</label>
        <input id="q" name="q" type="search" value="{{ $query->raw }}" maxlength="200" autofocus class="input" placeholder="{{ __('Search pages and attachments') }}">
        <button type="submit" class="btn-primary"><x-kb-icon name="search" class="size-4" /><span class="sr-only sm:not-sr-only">{{ __('Search') }}</span></button>
    </form>

    @if (! $query->isEmpty())
        @forelse ($groups as $group)
            <section class="mb-8">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __($group['label']) }} ({{ count($group['results']) }})</h2>
                <ul class="space-y-3">
                    @foreach ($group['results'] as $result)
                        <li class="card">
                            <a href="{{ $result->url }}" class="font-medium text-indigo-700 hover:underline">{{ $result->title }}</a>
                            <span class="ml-2 text-xs text-slate-500">{{ $result->context }}</span>
                            @if (trim($result->excerpt) !== '')
                                {{-- Escape first, then turn the ⟦ ⟧ markers into <mark>. --}}
                                <p class="mt-1 text-sm text-slate-600">{!! str_replace(['⟦', '⟧'], ['<mark class="bg-yellow-100 px-0.5">', '</mark>'], e($result->excerpt)) !!}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @empty
            <p class="text-slate-600">{{ __('No results for “:query”.', ['query' => $query->raw]) }}</p>
        @endforelse
    @endif
</x-layouts.app>
