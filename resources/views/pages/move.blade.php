<x-layouts.app :title="__('Move: :title', ['title' => $page->title])">
    <h1 class="mb-6 text-2xl font-semibold">{{ __('Move “:title”', ['title' => $page->title]) }}</h1>
    <form method="post" action="{{ route('pages.move.update', $page) }}" class="card max-w-xl space-y-4">
        @csrf @method('PUT')
        <div>
            <label for="parent_id" class="label">{{ __('New position') }}</label>
            <select id="parent_id" name="parent_id" class="input">
                <option value="">{{ __('Top level of the space') }}</option>
                @foreach ($targets as $target)
                    <option value="{{ $target['id'] }}" @selected($page->parent_id === $target['id'])>{{ str_repeat('— ', $target['depth']) }}{{ $target['title'] }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ __('Sub-pages move along. A page cannot be moved below itself.') }}</p>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">{{ __('Move') }}</button>
            <a href="{{ route('pages.show', $page) }}" class="btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
