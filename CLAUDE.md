# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@docs/STANDARDS.md

# Knowledgebase

Wissensdatenbank für Teams: Workspace → Bereiche (Spaces) → verschachtelte Seiten mit Versionen, Labels, Anhängen, Volltextsuche und Markdown-Import/-Export. Für Teams, die Dokumentation selbst hosten wollen.

Die gemeinsamen Standards oben gelten vollständig. Hier steht nur, was für dieses Projekt dazukommt oder bewusst abweicht.

## Projekt

- Sichtbarkeit: öffentlich
- Sprache der Oberfläche: Deutsch (Standard), dazu Englisch, Französisch, Italienisch, Spanisch
- Erreichbarkeit: öffentlich → Admin-Bereich `/admin` hinter Cloudflare Access
- Deploy: automatisch per Webhook (`AUTO_DEPLOY=true`)
- Queue und Scheduler: keine (`QUEUE_CONNECTION=sync`)
- Wiki: `scripts/build-wiki.py --push` schreibt nach `<origin>.wiki.git`

## Befehle

```bash
docker compose up -d db                        # PostgreSQL 18 (startet im Devcontainer automatisch)
php artisan serve                              # App :8000 (plus: npm run dev für Vite :5173)
vendor/bin/pest                                # alle Tests (PostgreSQL, Datenbank app_test)
vendor/bin/pest tests/Feature/Pages/PageTest.php
vendor/bin/pest --filter="moves pages"         # einzelner Test
vendor/bin/pint                                # Code-Stil
vendor/bin/phpstan analyse --memory-limit=2G   # Larastan Level 5
npm run build                                  # Assets (Editor-Bundle wird lazy geladen)
python3 scripts/build-wiki.py [--push]         # Wiki aus handbuch/
```

Xdebug im Devcontainer bremst und lärmt: `export XDEBUG_MODE=off`. Lokal landen Anmeldelinks in `storage/logs/laravel.log` (`MAIL_MAILER=log`).

Slash-Commands: `/login-audit` prüft und härtet den Login-Flow.

## Architektur

```
app/Core/            Registries, Aktivitätsprotokoll, Middleware (SecurityHeaders, SetLocale), Rate-Limits
app/Modules/<Modul>/ je Modul: Models, Services, Contracts, Policies, Http, routes.php, <Modul>ServiceProvider
app/Filament/        Admin-Oberfläche (/admin) – schreibt nur über die Modul-Services
resources/views/     Frontend (Blade, kein Livewire); Layouts als anonyme Komponenten x-layouts.app / x-layouts.guest
```

Module: `Workspace` (Einstellungen, Benutzer, Startseite), `Auth` (Magic Link, Passkeys, TOTP, Konto), `Groups`, `Spaces`, `Pages` (Seiten, Versionen, Labels, Anhänge, Papierkorb, Space-Startseite), `Search`, `ImportExport`. Provider-Reihenfolge in `bootstrap/providers.php`.

**Modulgrenzen** – Module greifen nie auf Interna anderer Module zu, sondern nur über:
- **Registries in `app/Core`** (Singletons): `NavigationRegistry` (Hauptnavigation in der Kopfzeile, Kontomenü, mobile Tab-Leiste), `SidebarRegistry` (Abschnitte der linken Leiste: Spaces liefert den Bereichswechsler, Pages den Seitenbaum), `MenuRegistry` (Kontextmenüs `space`, `space.pages`, `page` – z. B. hängt ImportExport „Exportieren“ ins Seitenmenü), `DashboardRegistry` (Widgets der Startseite), `SearchRegistry` (`SearchProvider` je Inhaltstyp), `ImportRegistry`/`ExportRegistry` (Formate). Neue Funktionen hängen sich dort ein, nicht im Layout oder in fremden Controllern.
- **Menüs:** `MenuItem`-Sichtbarkeit wird pro Bereich entschieden (`visible(User, Space)`), nicht pro Seite – das `page`-Menü wird je Bereich einmal als `<template>` mit Platzhalter `__PAGE__` gerendert und in `resources/js/tree.js` befüllt. URL-Closures bekommen beim `space`-Menü den Slug, beim `page`-Menü die Seiten-ID.
- **Contracts**: `Spaces\Contracts\CurrentSpace` liefert den Bereich der linken Leiste (gesetzt, per Resolver aus Routenparametern – Spaces für `{space}`, Pages für `{page}` –, sonst zuletzt besuchter, sonst erster lesbarer); Zustand liegt in den Request-Attributen, nie im Service. `Spaces\Contracts\SpaceAccess` ist die einzige Stelle für „was darf dieser Benutzer in diesem Bereich“; `Groups\Contracts\GroupMembership`; `Pages\Contracts\PageWriter` für Seitenänderungen aus anderen Modulen (Import, später MCP/Bases).
- **Events**: `Spaces\Events\SpaceDeleting` – Pages räumt dort Anhang-Dateien auf.
- Models anderer Module dürfen gelesen werden (Relationen, Abfragen); geschrieben wird nur über deren Services.

**Regeln, die man leicht bricht:**
- Jede Abfrage über Seiten/Anhänge filtert mit `SpaceAccess::spaceIdsFor($user)` – auch Suche, Labels, Dashboard-Widgets, Exporte. Bereiche ohne Recht sind unsichtbar.
- Rollen: `SpaceRole` reader < editor < admin (Policy-Abilities `view`, `contribute`, `moderate`); Workspace-Admins sind überall Bereichsadmin. Die Gruppe *Everyone* (`groups.system_key = 'everyone'`) hat keine gespeicherten Mitglieder, sie gilt implizit für alle.
- Seiteninhalt ist **Markdown** und die einzige Quelle (Editor, Suche, Import, Export, Diff). Gerendert nur über `MarkdownRenderer` (`html_input => escape`). Der TipTap-Editor (`resources/js/editor.js`) liest/schreibt Markdown in das Textarea, das abgeschickt wird.
- Jede inhaltliche Änderung erzeugt über `PageService` eine `PageVersion`; `update()` prüft im Controller die mitgeschickte `version` (kein stilles Überschreiben). `amendLatestVersion()` nur für maschinelle Korrekturen direkt nach dem Anlegen (Link-Umschreibung beim Import).
- Papierkorb = SoftDeletes, immer samt Unterseiten (`descendantIds`). Endgültiges Löschen nur aus dem Papierkorb.
- Reihenfolge im Baum: `PageService::place()` (Drag & Drop: before/after/inside) nummeriert die Geschwister lückenlos neu; `duplicate()` kopiert Unterseiten, Labels und Anhänge (Dateien werden kopiert, Links im Inhalt umgeschrieben).
- Bereichssymbole: `spaces.icon_path` auf der privaten Disk (Upload über Filament, nur Rasterbilder), Auslieferung nur über `SpaceIconController` mit Rechteprüfung; `SpaceService::replaceIcon()` löscht die alte Datei.
- Volltextsuche: generierte `tsvector`-Spalten (`'simple'`-Config, sprachneutral) mit GIN-Index; Suchbegriffe nur über `SearchQuery::toTsQuery()` als gebundener Parameter.
- Anhänge auf der privaten Disk, Auslieferung nur über `AttachmentController` (inline nur `attachments.inline_mimes`). Import legt nur Dateien mit erlaubter Endung als Anhang an.
- `Workspace::current()` ist pro Request gecacht (scoped Binding), nie in statischen Properties cachen – sonst laufen Tests ineinander.
- Keine Passwörter: `password.confirm` (Laravel/Passkey-Paket) zeigt auf `/bestaetigen` (Passkey oder Bestätigungslink); jede Anmeldung zählt 15 Minuten als frische Bestätigung (`Login`-Listener im `AuthServiceProvider`). POST/DELETE-Routen nicht hinter `password.confirm` hängen (der Redirect zurück wäre ein GET) – stattdessen die GET-Seite davor schützen.
- Filament-Ressourcen/-Seiten nutzen den Trait `App\Filament\Concerns\AdminOnly` und schreiben über `handleRecordCreation`/`handleRecordUpdate` in die Services.
- URLs und Filament-Slugs sind immer **englisch**, unabhängig von der Sprache des Benutzers (`/spaces/{space}`, `/pages/{page}/edit`, `/admin/users/create`). Die früheren deutschen Pfade leiten per 301 weiter (`App\Core\Http\LegacyRedirects`, je Modul in dessen `routes.php` registriert) – nicht entfernen: alte Anmeldelinks, Lesezeichen und in Seiteninhalten gespeicherte Links (`/seiten/…`, `/anhaenge/…`) hängen daran; der ZIP-Export erkennt beide Formen.

**Übersetzungen:** Quelltexte englisch in `__()`, Übersetzungen in `lang/{de,en,fr,it,es}.json`. `tests/Feature/LocaleTest.php` schlägt fehl, wenn ein `__('…')`-Schlüssel in `de.json` fehlt oder eine Sprache unvollständig ist – neue Texte also immer in allen fünf Dateien ergänzen. Labels, die indirekt übersetzt werden (`NavigationItem::$label`, `MenuItem`-Labels/`activeLabel`/`confirm`, `label()` von Importern/Exportern/SearchProvidern), ebenfalls eintragen – der Test prüft auch `new MenuItem('key', 'Label', …)`. Die Laravel-Texte (`lang/<locale>/*.php`, Grundbestand der JSON-Dateien) stammen einmalig von `laravel-lang/common`; das Paket ist bewusst nicht installiert (verlassene Abhängigkeiten lassen `composer audit` scheitern) – zum Aktualisieren kurz installieren, `php artisan lang:update`, wieder entfernen.

## Abweichungen von den Standards

- URLs und Filament-Slugs englisch statt deutsch: Die Oberfläche ist fünfsprachig, eine einzelne Sprache in den Adressen wäre für die meisten Benutzer fremd.
- `Referrer-Policy` ist global `same-origin`, auf der Magic-Link-Seite `no-referrer`.
- CSP erlaubt `'unsafe-inline'`/`'unsafe-eval'` für Skripte, weil Alpine/Livewire (Filament) Ausdrücke zur Laufzeit auswerten.
