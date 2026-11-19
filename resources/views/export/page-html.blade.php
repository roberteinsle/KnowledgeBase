<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->title }}</title>
<style>
    body { margin: 0; background: #fff; color: #1e293b; font: 16px/1.65 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
    main { max-width: 48rem; margin: 0 auto; padding: 3rem 1.25rem; }
    h1, h2, h3, h4 { line-height: 1.25; margin: 2em 0 .6em; color: #0f172a; }
    h1 { font-size: 2rem; margin-top: 0; }
    a { color: #4338ca; }
    .heading-anchor { display: none; }
    img { max-width: 100%; border-radius: .375rem; }
    pre { background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: .5rem; overflow-x: auto; }
    code { font: .9em ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
    :not(pre) > code { background: #f1f5f9; padding: .1em .35em; border-radius: .25rem; }
    blockquote { margin: 1.5em 0; padding-left: 1em; border-left: 4px solid #cbd5e1; color: #475569; }
    table { border-collapse: collapse; width: 100%; font-size: .9em; }
    th, td { border: 1px solid #cbd5e1; padding: .4em .6em; text-align: left; vertical-align: top; }
    th { background: #f8fafc; }
    ul.table-of-contents { list-style: none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .5rem; padding: 1rem 1.25rem; }
    ul.table-of-contents ul { list-style: none; padding-left: 1rem; }
    li:has(> input[type=checkbox]) { list-style: none; margin-left: -1.25em; }
    footer { margin-top: 3rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: .85em; }
</style>
</head>
<body>
<main>
    <h1>{{ $page->title }}</h1>
    {{-- Rendered by MarkdownRenderer: HTML escaped, unsafe links removed. --}}
    {!! $html !!}
    <footer>{{ __('Version :version · last changed :date by :name', [
        'version' => $page->version,
        'date' => \App\Core\Support\Dates::display($page->updated_at),
        'name' => $page->editor?->name ?? __('unknown'),
    ]) }}</footer>
</main>
</body>
</html>
