@props(['lists', 'showSpace' => true])
{{-- Recently updated / Favorites / Created by me --}}
@php($tabs = ['recent' => [__('Recently updated'), 'clock'], 'favorites' => [__('Favorites'), 'star'], 'mine' => [__('Created by me'), 'user']])
<div x-data="{ tab: 'recent' }">
    <div class="mb-1 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist">
        @foreach ($tabs as $key => [$label, $icon])
            <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'" @click="tab = '{{ $key }}'"
                    class="-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2.5 text-sm font-medium"
                    :class="tab === '{{ $key }}' ? 'border-slate-900 text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'">
                <x-kb-icon :name="$icon" class="size-4" /> {{ $label }}
            </button>
        @endforeach
    </div>

    @foreach ($tabs as $key => [$label])
        <div role="tabpanel" x-show="tab === '{{ $key }}'" @unless ($key === 'recent') x-cloak @endunless>
            @if ($lists[$key]->isEmpty())
                <p class="px-3 py-6 text-sm text-slate-500">
                    {{ match ($key) {
                        'favorites' => __('No favorites yet. Add pages via the ⋮ menu in the page tree.'),
                        'mine' => __('You have not created any pages yet.'),
                        default => __('No pages yet.'),
                    } }}
                </p>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($lists[$key] as $page)
                        <li>
                            <a href="{{ $page->url() }}" class="flex items-center gap-3 px-3 py-3 hover:bg-slate-50">
                                <x-kb-icon name="document" class="size-4 shrink-0 text-slate-400" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium text-slate-900">{{ $page->title }}</span>
                                    @if ($showSpace)
                                        <span class="flex items-center gap-1 text-xs text-slate-500"><x-space-icon :space="$page->space" size="size-3.5" /> {{ $page->space->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-xs text-slate-500" title="{{ \App\Core\Support\Dates::display($page->updated_at) }}">{{ \App\Core\Support\Dates::display($page->updated_at) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</div>
