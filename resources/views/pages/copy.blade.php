<x-layouts.app :title="__('Copy to space')">
    <h1 class="mb-2 text-2xl font-semibold">{{ __('Copy “:title” to a space', ['title' => $page->title]) }}</h1>
    <p class="mb-6 text-sm text-slate-600">{{ __('The copy includes all sub-pages, labels and attachments. The version history starts anew.') }}</p>

    @if ($targets->isEmpty())
        <p class="card text-sm text-slate-600">{{ __('You cannot edit any space.') }}</p>
    @else
        <form method="post" action="{{ route('pages.copy.store', $page) }}" class="card max-w-xl space-y-4">
            @csrf
            <div>
                <label for="target" class="label">{{ __('Target') }}</label>
                <select id="target" name="target" class="input" required>
                    @foreach ($targets as $target)
                        <optgroup label="{{ $target['space']->name }}">
                            <option value="space:{{ $target['space']->id }}">{{ __('Top level of :space', ['space' => $target['space']->name]) }}</option>
                            @foreach ($target['pages'] as $option)
                                <option value="page:{{ $option['id'] }}">{{ str_repeat('— ', $option['depth'] + 1) }}{{ $option['title'] }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary"><x-kb-icon name="duplicate" class="size-4" /> {{ __('Copy') }}</button>
                <a href="{{ route('pages.show', $page) }}" class="btn-ghost">{{ __('Cancel') }}</a>
            </div>
        </form>
    @endif
</x-layouts.app>
