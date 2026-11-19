<h2 class="mb-3 text-lg font-semibold">{{ __('Recently updated') }}</h2>
@if ($pages->isEmpty())
    <p class="card text-sm text-slate-600">{{ __('No pages yet.') }}</p>
@else
    <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
        @foreach ($pages as $page)
            <li>
                <a href="{{ route('pages.show', $page) }}" class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-slate-50">
                    <span class="min-w-0">
                        <span class="block truncate font-medium">{{ $page->title }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ $page->space->icon }} {{ $page->space->name }}</span>
                    </span>
                    <span class="shrink-0 text-right text-xs text-slate-500">
                        {{ \App\Core\Support\Dates::display($page->updated_at, 'relative') }}<br>{{ $page->editor?->name }}
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
