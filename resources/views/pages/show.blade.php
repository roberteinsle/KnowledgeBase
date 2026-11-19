<x-layouts.app :title="$page->title" :space="$space" :tree="$tree" :current-page-id="$page->id">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 flex flex-wrap items-center gap-1 text-sm text-slate-500">
        <a href="{{ route('spaces.show', $space) }}" class="hover:text-indigo-700">{{ $space->name }}</a>
        @foreach ($ancestors as $ancestor)
            <x-kb-icon name="chevron-right" class="size-3.5" />
            <a href="{{ route('pages.show', $ancestor) }}" class="hover:text-indigo-700">{{ $ancestor->title }}</a>
        @endforeach
    </nav>

    <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
        <h1 class="min-w-0 break-words text-3xl font-semibold">{{ $page->title }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            @can('update', $page)
                <a href="{{ route('pages.edit', $page) }}" class="btn-primary"><x-kb-icon name="pencil" class="size-4" /> {{ __('Edit') }}</a>
            @endcan
            <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
                <button type="button" class="btn-secondary" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">{{ __('More') }} <x-kb-icon name="chevron-down" class="size-4" /></button>
                <div x-cloak x-show="open" @click.outside="open = false" role="menu" class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    @can('update', $page)
                        <a role="menuitem" href="{{ route('pages.create', [$space, 'parent' => $page->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="plus" class="size-4" /> {{ __('New sub-page') }}</a>
                        <a role="menuitem" href="{{ route('import.create', [$space, 'parent' => $page->id]) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="upload" class="size-4" /> {{ __('Import below this page') }}</a>
                        <a role="menuitem" href="{{ route('pages.move', $page) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="move" class="size-4" /> {{ __('Move') }}</a>
                    @endcan
                    <a role="menuitem" href="{{ route('pages.versions', $page) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="clock" class="size-4" /> {{ __('Version history') }}</a>
                    @foreach ($exporters as $exporter)
                        <a role="menuitem" href="{{ route('export.page', [$page, $exporter->key()]) }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-slate-50"><x-kb-icon name="download" class="size-4" /> {{ __($exporter->label()) }}</a>
                    @endforeach
                    @can('delete', $page)
                        <form method="post" action="{{ route('pages.destroy', $page) }}" data-confirm="{{ __('Move this page and all its sub-pages to the trash?') }}" class="border-t border-slate-100">
                            @csrf @method('DELETE')
                            <button type="submit" role="menuitem" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-700 hover:bg-red-50"><x-kb-icon name="trash" class="size-4" /> {{ __('Move to trash') }}</button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <p class="mb-6 text-sm text-slate-500">
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

    <article class="kb-prose prose prose-slate max-w-none prose-headings:scroll-mt-20 prose-a:text-indigo-700 prose-img:rounded-md prose-table:text-sm">
        @if (trim($page->content) === '')
            <p class="text-slate-500">{{ __('This page is empty.') }}</p>
        @else
            {{-- Rendered by MarkdownRenderer with html_input=escape and unsafe links removed. --}}
            {!! $html !!}
        @endif
    </article>

    @if ($children->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-3 text-lg font-semibold">{{ __('Sub-pages') }}</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($children as $child)
                    <li><a href="{{ route('pages.show', $child) }}" class="card flex items-center gap-2 py-3 hover:border-indigo-300"><x-kb-icon name="document" class="size-4 text-slate-400" /> {{ $child->title }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section id="anhaenge" class="mt-10">
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
