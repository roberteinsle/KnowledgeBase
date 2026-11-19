<?php

use App\Core\Support\Locales;
use Illuminate\Support\Facades\File;

it('uses the user language', function () {
    $this->actingAs(member(['locale' => 'fr']))->get('/')->assertSee('lang="fr"', false);
});

it('lets guests switch the language', function () {
    $this->post('/sprache', ['locale' => 'es'])->assertRedirect();
    $this->get('/anmelden')->assertSee('lang="es"', false);
});

it('falls back to the browser language and then the workspace default', function () {
    $this->withHeader('Accept-Language', 'it-IT,it;q=0.9')->get('/anmelden')->assertSee('lang="it"', false);
    $this->withHeader('Accept-Language', 'ja')->get('/anmelden')->assertSee('lang="de"', false);
});

it('rejects unknown languages', function () {
    $this->post('/sprache', ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

it('has every UI string translated in all languages', function (string $locale) {
    $reference = array_keys(json_decode(file_get_contents(lang_path('de.json')), true));
    $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true);

    expect(array_diff($reference, array_keys($translations)))->toBe([]);
    expect(array_filter($translations, fn ($value) => trim((string) $value) === ''))->toBe([]);
})->with(['en', 'fr', 'it', 'es']);

it('tests exactly the configured languages', function () {
    // The dataset above is built before Laravel boots, so it is fixed; keep it in sync.
    expect(Locales::codes())->toBe(['de', 'en', 'fr', 'it', 'es']);
});

it('uses every translation key in the code or nowhere', function () {
    $keys = array_keys(json_decode(file_get_contents(lang_path('de.json')), true));
    $code = collect([app_path(), resource_path('views'), resource_path('js')])
        ->flatMap(fn ($dir) => File::allFiles($dir))
        ->map(fn ($file) => file_get_contents($file->getPathname()))
        ->implode("\n");

    $missing = [];
    preg_match_all("/__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", $code, $single);
    preg_match_all('/trans_choice\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $code, $choice);
    foreach ([...$single[1], ...$choice[1]] as $used) {
        $used = stripslashes($used);
        if (! in_array($used, $keys, true)) {
            $missing[] = $used;
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
