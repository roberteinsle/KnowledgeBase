{{-- Account section of the MCP module --}}
<section class="card mb-6 max-w-xl">
    <h2 class="mb-1 text-lg font-semibold">{{ __('Connected AI apps') }}</h2>
    <p class="mb-3 text-sm text-slate-600">
        {{ __('AI assistants such as Claude can search, read and edit pages with your permissions via MCP. Server address:') }}
        <code class="break-all rounded bg-slate-100 px-1 font-mono text-xs">{{ $endpoint }}</code>
    </p>

    @if ($connections->isEmpty())
        <p class="text-sm text-slate-500">{{ __('No app is connected.') }}</p>
    @else
        <ul class="divide-y divide-slate-100 rounded-md border border-slate-200">
            @foreach ($connections as $token)
                <li class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                    <span>
                        <span class="font-medium">{{ $token->client?->name ?? __('unknown') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('Connected :date', ['date' => \App\Core\Support\Dates::display($token->created_at)]) }}</span>
                    </span>
                    <form method="post" action="{{ route('mcp.connections.destroy', $token->client_id) }}" data-confirm="{{ __('Revoke access for this app?') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-sm text-red-700 hover:underline">{{ __('Revoke') }}</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</section>
