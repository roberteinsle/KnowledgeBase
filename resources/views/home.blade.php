<x-layouts.app :title="__('Home')">
    <div class="mb-8">
        <h1 class="text-2xl font-semibold">{{ __('Welcome, :name', ['name' => auth()->user()->name]) }}</h1>
        @if ($workspace->description)
            <p class="mt-1 text-slate-600">{{ $workspace->description }}</p>
        @endif
    </div>

    <form action="{{ route('search') }}" method="get" role="search" class="mb-8 md:hidden">
        <label for="home-search" class="sr-only">{{ __('Search') }}</label>
        <input id="home-search" name="q" type="search" class="input" maxlength="200" placeholder="{{ __('Search pages and attachments') }}">
    </form>

    <div class="space-y-10">
        @foreach ($widgets as $widget)
            <section>{{ $widget }}</section>
        @endforeach
    </div>
</x-layouts.app>
