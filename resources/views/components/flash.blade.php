@if (session('status'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800" role="status">{{ __(session('status')) }}</div>
@endif
@if (session('warning'))
    <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">{{ session('warning') }}</div>
@endif
@if (is_array(session('warnings')) && session('warnings') !== [])
    <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
        <ul class="list-disc pl-5">
            @foreach (session('warnings') as $warning)
                <li>{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
