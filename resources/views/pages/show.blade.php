<x-layouts.app :title="$page->title">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 flex flex-wrap items-center gap-1 text-sm text-slate-500">
        <a href="{{ route('spaces.show', $space) }}" class="hover:text-indigo-700">{{ $space->name }}</a>
        @foreach ($ancestors as $ancestor)
            <x-kb-icon name="chevron-right" class="size-3.5" />
            <a href="{{ \App\Modules\Pages\Support\PageUrl::to($space->slug, $ancestor->id, $ancestor->title) }}" class="hover:text-indigo-700">{{ $ancestor->title }}</a>
        @endforeach
    </nav>

    <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
        <h1 class="flex min-w-0 items-center gap-2 break-words text-3xl font-semibold">{{ $page->title }}@if ($isFavorite)<x-kb-icon name="star" class="size-5 shrink-0 fill-amber-400 text-amber-500" /><span class="sr-only">{{ __('Favorite') }}</span>@endif</h1>
        <div class="flex flex-wrap items-center gap-2">
            @can('update', $page)
                <a href="{{ route('pages.edit', $page) }}" class="btn-primary"><x-kb-icon name="pencil" class="size-4" /> {{ __('Edit') }}</a>
            @endcan
            <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
                <button type="button" class="btn-secondary" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">{{ __('More') }} <x-kb-icon name="chevron-down" class="size-4" /></button>
                <div x-cloak x-show="open" @click.outside="open = false" role="menu" class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    @can('update', $page)
                        <a role="menuitem" href="{{ route('pages.create', [$space, 'parent' => $page->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="plus" class="size-4 text-slate-500" /> {{ __('New sub-page') }}</a>
                        <a role="menuitem" href="{{ route('import.create', [$space, 'parent' => $page->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="upload" class="size-4 text-slate-500" /> {{ __('Import below this page') }}</a>
                    @endcan
                    {{-- Same entries as the "⋮" menu in the tree (MenuRegistry "page"). --}}
                    @foreach ($menu as $item)
                        @php($url = $item->urlFor($page->id))
                        @php($classes = 'flex w-full items-center gap-2 px-4 py-2 text-left text-sm '.($item->danger ? 'text-red-700 hover:bg-red-50' : 'hover:bg-slate-50'))
                        @if ($item->danger) <div class="my-1 border-t border-slate-100"></div> @endif
                        @if ($item->kind === \App\Core\Navigation\MenuItem::COPY)
                            <button type="button" role="menuitem" class="{{ $classes }}" x-data="{ done: false }"
                                    @click="navigator.clipboard?.writeText(@js($page->url())).then(() => done = true)">
                                <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" />
                                <span x-text="done ? @js(__('Link copied')) : @js(__($item->label))">{{ __($item->label) }}</span>
                            </button>
                        @elseif ($item->kind === \App\Core\Navigation\MenuItem::COPY_CONTENT)
                            <button type="button" role="menuitem" class="{{ $classes }}" x-data="{ done: false }"
                                    @click="fetch(@js($url), { headers: { Accept: 'text/plain' } }).then((r) => r.text()).then((t) => navigator.clipboard.writeText(t)).then(() => done = true)">
                                <x-kb-icon :name="$item->icon" class="size-4 text-slate-500" />
                                <span x-text="done ? @js(__('Content copied')) : @js(__($item->label))">{{ __($item->label) }}</span>
                            </button>
                        @elseif (in_array($item->kind, [\App\Core\Navigation\MenuItem::POST, \App\Core\Navigation\MenuItem::DELETE], true))
                            <form method="post" action="{{ $url }}" @if ($item->confirm) data-confirm="{{ __($item->confirm) }}" @endif>
                                @csrf
                                @if ($item->kind === \App\Core\Navigation\MenuItem::DELETE) @method('DELETE') @endif
                                <button type="submit" role="menuitem" class="{{ $classes }}">
                                    <x-kb-icon :name="$item->icon" class="size-4 {{ $item->danger ? '' : 'text-slate-500' }}" />
                                    {{ __($isFavorite && $item->activeLabel ? $item->activeLabel : $item->label) }}
                                </button>
                            </form>
                        @else
                            <a role="menuitem" href="{{ $url }}" class="{{ $classes }}"><x-kb-icon :name="$item->icon" class="size-4 text-slate-500" /> {{ __($item->label) }}</a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <p class="mb-6 flex items-center gap-2 text-sm text-slate-500">
        @if ($page->editor)
            <img src="{{ $page->editor->avatarUrl() }}" alt="" class="size-5 rounded-full object-cover">
        @endif
        {{ __('Version :version · last changed :date by :name', [
            'version' => $page->version,
            'date' => \App\Core\Support\Dates::display($page->updated_at),
            'name' => $page->editor?->name ?? __('unknown'),
        ]) }}
    </p>

    @if ($page->labels->isNotEmpty())
        <ul class="mb-6 flex flex-wrap gap-2" aria-label="{{ __('Labels') }}">
            @foreach ($page->labels as $label)
                <li><a href="{{ route('labels.show', $label) }}" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"><x-kb-icon name="tag" class="size-3" /> {{ $label->name }}</a></li>
            @endforeach
        </ul>
    @endif

    @can('update', $page)
        {{-- Inline editing: click into the text; "/" opens the block menu (resources/js/inline-editor.js). --}}
        <div data-inline-bar hidden class="sticky top-14 z-20 -mx-4 mb-4 flex flex-wrap items-center justify-between gap-2 border-y border-indigo-100 bg-indigo-50/95 px-4 py-2 backdrop-blur md:-mx-8 md:px-8">
            <p class="text-sm text-indigo-900" data-inline-status>{{ __('Editing – type / for blocks. Ctrl+S saves, Esc cancels.') }}</p>
            <div class="flex gap-2">
                <button type="button" class="btn-ghost" data-inline-cancel>{{ __('Cancel') }}</button>
                <button type="button" class="btn-primary" data-inline-save>{{ __('Save') }}</button>
            </div>
        </div>
        <p data-inline-hint class="mb-3 hidden text-xs text-slate-400 md:block">{{ __('Click into the text to edit it directly – type / for blocks.') }}</p>
    @endcan

    <div class="flex gap-10">
    <div data-inline-mount hidden class="min-w-0 flex-1 rounded-lg ring-2 ring-indigo-200 ring-offset-8"></div>
    <article data-code-copy data-copy-label="{{ __('Copy') }}" data-copied-label="{{ __('Copied') }}"
             @can('update', $page)
                 data-inline-edit
                 data-markdown-url="{{ route('pages.markdown', $page) }}"
                 data-update-url="{{ route('pages.update', $page) }}"
                 data-version="{{ $page->version }}"
                 data-title="{{ $page->title }}"
                 data-labels="{{ $page->labels->pluck('name')->implode(', ') }}"
                 data-placeholder="{{ __('Start writing … type / for blocks.') }}"
                 data-slash-labels="{{ json_encode(\App\Modules\Pages\Support\EditorLabels::slash()) }}"
                 data-msg-saving="{{ __('Saving …') }}"
                 data-msg-conflict="{{ __('This page was changed by someone else while you were editing. Copy your changes, reload and try again.') }}"
                 data-msg-error="{{ __('The page could not be saved.') }}"
                 data-msg-discard="{{ __('Discard your changes?') }}"
             @endcan class="min-w-0 flex-1 kb-prose prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-a:text-indigo-700 prose-img:rounded-md prose-table:text-sm">
        @if (trim($page->content) === '')
            <p class="text-slate-500">{{ __('This page is empty.') }}</p>
        @else
            {{-- Rendered by MarkdownRenderer with html_input=escape and unsafe links removed. --}}
            {!! $html !!}
        @endif
    </article>

    {{-- "On this page": headings H2/H3, only on wide screens and for longer pages --}}
    @if (count($outline) >= 3)
        <nav class="sticky top-20 hidden max-h-[calc(100vh-6rem)] w-56 shrink-0 self-start overflow-y-auto text-sm xl:block" aria-label="{{ __('On this page') }}">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('On this page') }}</p>
            <ul class="space-y-1 border-l border-slate-200">
                @foreach ($outline as $item)
                    <li><a href="#{{ $item['id'] }}" @class(['-ml-px block border-l border-transparent py-0.5 text-slate-600 hover:border-slate-400 hover:text-slate-900', 'pl-3' => $item['level'] === 2, 'pl-6' => $item['level'] === 3])>{{ $item['text'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    @endif
    </div>

    @if ($children->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-3 text-lg font-semibold">{{ __('Sub-pages') }}</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($children as $child)
                    <li><a href="{{ \App\Modules\Pages\Support\PageUrl::to($space->slug, $child->id, $child->title) }}" class="card flex items-center gap-2 py-3 hover:border-indigo-300"><x-kb-icon name="document" class="size-4 text-slate-400" /> {{ $child->title }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section id="attachments" class="mt-10">
        <h2 class="mb-3 flex items-center gap-2 text-lg font-semibold"><x-kb-icon name="paperclip" class="size-5" /> {{ __('Attachments') }}</h2>
        @if ($page->attachments->isEmpty())
            <p class="text-sm text-slate-500">{{ __('No attachments.') }}</p>
        @else
            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
                @foreach ($page->attachments as $attachment)
                    <li class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
                        <a href="{{ $attachment->url() }}" class="min-w-0 truncate text-indigo-700 hover:underline">{{ $attachment->original_name }}</a>
                        <span class="flex shrink-0 items-center gap-3 text-xs text-slate-500">
                            {{ \Illuminate\Support\Number::fileSize($attachment->size) }}
                            @can('update', $page)
                                <form method="post" action="{{ route('attachments.destroy', $attachment) }}" data-confirm="{{ __('Delete this attachment?') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-red-600" title="{{ __('Delete') }}"><x-kb-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Delete') }}</span></button>
                                </form>
                            @endcan
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @can('update', $page)
            <form method="post" action="{{ route('attachments.store', $page) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-center gap-2">
                @csrf
                <label for="files" class="sr-only">{{ __('Files') }}</label>
                <input id="files" name="files[]" type="file" multiple required class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
                <button type="submit" class="btn-secondary"><x-kb-icon name="upload" class="size-4" /> {{ __('Upload') }}</button>
            </form>
        @endcan
    </section>
</x-layouts.app>
