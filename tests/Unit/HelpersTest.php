<?php

use App\Core\Search\SearchQuery;
use App\Modules\Auth\Services\RedirectSanitizer;
use App\Modules\ImportExport\Services\MarkdownDocumentParser;
use App\Modules\Pages\Services\LabelNormalizer;

it('builds a safe prefix tsquery', function () {
    expect(SearchQuery::fromInput("Urlaub & Reise:* ' OR 1=1 --")->toTsQuery())
        ->toBe('urlaub:* & reise:* & or:* & 1:*');
    expect(SearchQuery::fromInput('  ')->isEmpty())->toBeTrue();
    expect(SearchQuery::fromInput('Ärztliche Bescheinigung')->terms)->toBe(['ärztliche', 'bescheinigung']);
});

it('allows only internal redirect targets', function (?string $input, ?string $expected) {
    config(['app.url' => 'https://kb.example.com']);

    expect(RedirectSanitizer::internalPath($input))->toBe($expected);
})->with([
    ['/seiten/1', '/seiten/1'],
    ['https://kb.example.com/suche?q=a', '/suche?q=a'],
    ['https://evil.example.net/', null],
    ['//evil.example.net', null],
    ['/\\evil.example.net', null],
    ['javascript:alert(1)', null],
    ["/\nSet-Cookie: x", null],
    ['', null],
    [null, null],
]);

it('normalises labels', function () {
    expect(LabelNormalizer::normalize(' Onboarding, HR Prozesse,,onboarding , ä/ö '))->toBe(['onboarding', 'hr-prozesse', 'ä-ö']);
});

it('reads titles from front matter, headings and Notion file names', function () {
    $parser = new MarkdownDocumentParser;

    expect($parser->parse("---\ntitle: 'Aus Front Matter'\ntags:\n  - a\n  - b\n---\n# Überschrift\nText")->title)->toBe('Aus Front Matter');
    expect($parser->parse("---\ntitle: X\ntags:\n  - a\n  - b\n---\nText")->labels)->toBe(['a', 'b']);
    expect($parser->parse("\n# Überschrift #\n\nText")->title)->toBe('Überschrift');
    expect($parser->parse('Nur Text', 'Dateiname')->title)->toBe('Dateiname');
    expect(MarkdownDocumentParser::titleFromFilename('Meeting notes 0123456789abcdef0123456789abcdef.md'))->toBe('Meeting notes');
    expect($parser->parse("Gr\xFC\xDFe")->markdown)->toBe('Grüße');
});
