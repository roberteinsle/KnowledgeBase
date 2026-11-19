@php($isNew = $page === null)
<x-layouts.app :title="$isNew ? __('New page') : __('Edit: :title', ['title' => $page->title])" :space="$space" :tree="$tree" :current-page-id="$page?->id">
    <form method="post" action="{{ $isNew ? route('pages.store', $space) : route('pages.update', $page) }}" data-editor-form class="space-y-4">
        @csrf
        @unless ($isNew) @method('PUT') <input type="hidden" name="version" value="{{ old('version', $page->version) }}"> @endunless
        @if ($isNew && $parent) <input type="hidden" name="parent_id" value="{{ $parent->id }}"> @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">
                @if ($isNew)
                    {{ $parent ? __('New sub-page of “:title”', ['title' => $parent->title]) : __('New page in :space', ['space' => $space->name]) }}
                @else
                    {{ __('Editing version :version', ['version' => $page->version]) }}
                @endif
            </p>
            <div class="flex gap-2">
                <a href="{{ $isNew ? ($parent ? route('pages.show', $parent) : route('spaces.show', $space)) : route('pages.show', $page) }}" class="btn-ghost">{{ __('Cancel') }}</a>
                <button type="submit" class="btn-primary">{{ __('Save') }}</button>
            </div>
        </div>

        <div>
            <label for="title" class="sr-only">{{ __('Title') }}</label>
            <input id="title" name="title" type="text" required maxlength="255" autofocus
                   value="{{ old('title', $page?->title) }}" placeholder="{{ __('Title') }}"
                   class="w-full border-0 border-b border-slate-200 bg-transparent px-0 py-2 text-3xl font-semibold focus:border-indigo-500 focus:ring-0 focus:outline-none">
        </div>

        <div>
            <label for="labels" class="label">{{ __('Labels') }}</label>
            <input id="labels" name="labels" type="text" maxlength="1000" class="input"
                   value="{{ old('labels', $page?->labels->pluck('name')->implode(', ')) }}"
                   placeholder="{{ __('e.g. onboarding, how-to') }}">
            <p class="mt-1 text-xs text-slate-500">{{ __('Separate labels with commas.') }}</p>
        </div>

        <div class="kb-editor overflow-hidden rounded-lg border border-slate-300 bg-white" data-editor
             data-placeholder="{{ __('Start writing … Markdown shortcuts like # or - work.') }}"
             data-label-rich="{{ __('Editor') }}" data-label-source="{{ __('Markdown') }}">
            <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-1" data-editor-toolbar hidden>
                @foreach ([
                    ['bold', 'B', __('Bold')], ['italic', 'I', __('Italic')], ['strike', 'S', __('Strikethrough')], ['code', '</>', __('Code')],
                    ['h2', 'H2', __('Heading')], ['h3', 'H3', __('Subheading')], ['bulletList', '•', __('Bullet list')], ['orderedList', '1.', __('Numbered list')],
                    ['taskList', '☑', __('Task list')], ['blockquote', '❝', __('Quote')], ['codeBlock', '{ }', __('Code block')], ['table', '▦', __('Table')],
                    ['link', '🔗', __('Link')], ['hr', '—', __('Divider')],
                ] as [$cmd, $text, $title])
                    <button type="button" data-cmd="{{ $cmd }}" title="{{ $title }}" aria-label="{{ $title }}" class="min-w-8 rounded px-2 py-1 text-sm font-medium text-slate-700 hover:bg-slate-200">{{ $text }}</button>
                @endforeach
                <span class="ml-auto"></span>
                <button type="button" data-mode-toggle class="rounded px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">{{ __('Markdown') }}</button>
            </div>
            <div data-editor-mount hidden></div>
            {{-- Without JavaScript this plain Markdown field is the editor. --}}
            <label for="content" class="sr-only">{{ __('Content (Markdown)') }}</label>
            <textarea id="content" name="content" rows="24" maxlength="1000000" data-editor-source
                      class="block w-full border-0 px-4 py-3 font-mono text-sm focus:ring-0 focus:outline-none">{{ old('content', $page?->content) }}</textarea>
        </div>
    </form>
</x-layouts.app>
