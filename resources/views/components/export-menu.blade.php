@props(['exporters', 'route', 'subject'])
@if (count($exporters) > 0)
    <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
        <button type="button" class="btn-secondary" @click="open = !open" :aria-expanded="open" aria-haspopup="menu">
            <x-kb-icon name="download" class="size-4" /> {{ __('Export') }}
        </button>
        <div x-cloak x-show="open" @click.outside="open = false" role="menu"
             class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
            @foreach ($exporters as $exporter)
                <a role="menuitem" href="{{ route($route, [$subject, $exporter->key()]) }}" class="block px-4 py-2 text-sm hover:bg-slate-50">{{ __($exporter->label()) }}</a>
            @endforeach
        </div>
    </div>
@endif
