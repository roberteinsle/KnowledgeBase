<x-layouts.app :title="__('Label: :name', ['name' => $label->name])">
    <h1 class="mb-6 flex items-center gap-2 text-2xl font-semibold"><x-kb-icon name="tag" class="size-6" /> {{ $label->name }}</h1>
    @if ($pages->isEmpty())
        <p class="card text-sm text-slate-600">{{ __('No pages with this label.') }}</p>
    @else
        <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white">
            @foreach ($pages as $page)
                <li><a href="{{ route('pages.show', $page) }}" class="block px-4 py-3 hover:bg-slate-50"><span class="font-medium">{{ $page->title }}</span><span class="block text-xs text-slate-500">{{ $page->space->name }}</span></a></li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $pages->links() }}</div>
    @endif
</x-layouts.app>
