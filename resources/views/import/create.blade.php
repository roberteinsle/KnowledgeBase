<x-layouts.app :title="__('Import Markdown')" :space="$space" :tree="$tree">
    <h1 class="mb-2 text-2xl font-semibold">{{ __('Import Markdown') }}</h1>
    <p class="mb-6 max-w-3xl text-sm text-slate-600">
        {{ __('Upload Markdown files or a ZIP archive (e.g. an Obsidian vault or a Notion export), or paste Markdown directly. Folders become sub-pages, images become attachments, a title from the front matter or the first heading becomes the page title.') }}
    </p>

    <form method="post" action="{{ route('import.store', $space) }}" enctype="multipart/form-data" class="max-w-3xl space-y-6"
          x-data="{ mode: '{{ old('mode', 'file') }}' }">
        @csrf

        <div>
            <label for="parent_id" class="label">{{ __('Import into') }}</label>
            <select id="parent_id" name="parent_id" class="input">
                <option value="">{{ __('Top level of :space', ['space' => $space->name]) }}</option>
                @foreach ($targets as $target)
                    <option value="{{ $target['id'] }}" @selected((int) old('parent_id', $selectedParent) === $target['id'])>{{ str_repeat('— ', $target['depth']) }}{{ $target['title'] }}</option>
                @endforeach
            </select>
        </div>

        <fieldset>
            <legend class="label">{{ __('Source') }}</legend>
            <div class="flex flex-wrap gap-4 text-sm">
                <label class="flex items-center gap-2"><input type="radio" name="mode" value="file" x-model="mode" @checked(old('mode', 'file') === 'file')> {{ __('File upload') }}</label>
                <label class="flex items-center gap-2"><input type="radio" name="mode" value="paste" x-model="mode" @checked(old('mode') === 'paste')> {{ __('Paste text') }}</label>
            </div>
        </fieldset>

        <div x-show="mode === 'file'" class="card space-y-2">
            <label for="file" class="label">{{ __('File') }}</label>
            <input id="file" name="file" type="file" accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                   class="text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm">
            <ul class="list-disc pl-5 text-xs text-slate-500">
                @foreach ($importers as $importer)
                    <li>{{ __($importer->label()) }}: {{ collect($importer->extensions())->map(fn ($e) => '.'.$e)->implode(', ') }}</li>
                @endforeach
                <li>{{ __('Maximum size: :size MB', ['size' => intdiv(config('knowledgebase.import.max_kb'), 1024)]) }}</li>
            </ul>
        </div>

        <div x-show="mode === 'paste'" x-cloak class="card space-y-4">
            <div>
                <label for="title" class="label">{{ __('Title (optional)') }}</label>
                <input id="title" name="title" type="text" maxlength="255" value="{{ old('title') }}" class="input"
                       placeholder="{{ __('Taken from the first heading if empty') }}">
            </div>
            <div>
                <label for="markdown" class="label">{{ __('Markdown') }}</label>
                <textarea id="markdown" name="markdown" rows="16" maxlength="{{ config('knowledgebase.import.max_paste_chars') }}" class="input font-mono">{{ old('markdown') }}</textarea>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn-primary"><x-kb-icon name="upload" class="size-4" /> {{ __('Import') }}</button>
            <a href="{{ route('spaces.show', $space) }}" class="btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.app>
