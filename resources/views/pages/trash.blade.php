<x-layouts.app :title="__('Trash')">
    <h1 class="mb-2 text-2xl font-semibold">{{ __('Trash') }}</h1>
    <p class="mb-6 text-sm text-slate-600">{{ __('Pages in the trash can be restored together with their sub-pages, or deleted permanently.') }}</p>

    @if ($pages->isEmpty())
        <p class="card text-sm text-slate-600">{{ __('The trash is empty.') }}</p>
    @else
        <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
            @foreach ($pages as $page)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <span>
                        <span class="font-medium">{{ $page->title }}</span>
                        <span class="block text-xs text-slate-500">{{ __('Deleted :date', ['date' => \App\Core\Support\Dates::display($page->deleted_at)]) }}</span>
                    </span>
                    <span class="flex gap-2">
                        <form method="post" action="{{ route('trash.restore', $page) }}">
                            @csrf
                            <button type="submit" class="btn-secondary"><x-kb-icon name="restore" class="size-4" /> {{ __('Restore') }}</button>
                        </form>
                        <form method="post" action="{{ route('trash.destroy', $page) }}" data-confirm="{{ __('Delete this page, its sub-pages and attachments permanently? This cannot be undone.') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-danger"><x-kb-icon name="trash" class="size-4" /> {{ __('Delete permanently') }}</button>
                        </form>
                    </span>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $pages->links() }}</div>
    @endif
</x-layouts.app>
