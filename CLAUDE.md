# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@docs/STANDARDS.md

# Knowledgebase

Self-hosted knowledge base for teams: workspace → spaces → nested pages with versions, labels, attachments, full-text search and Markdown import/export.

The shared standards above apply in full. This file only lists what this project adds or deliberately does differently.

## Project

- Visibility: public
- UI languages: German (default), English, French, Italian, Spanish
- Reachability: public → admin area `/admin` behind Cloudflare Access
- Deploy: automatic via webhook (`AUTO_DEPLOY=true`); every push to `main` tags the image `latest` and `sha-<commit>`
- Queue and scheduler: none (`QUEUE_CONNECTION=sync`)
- Documentation language: **English** for `handbuch/`, the wiki, `README.md` and this file
- Wiki: `scripts/build-wiki.py --push` writes `handbuch/` to `<origin>.wiki.git`

## Commands

```bash
docker compose up -d db                        # PostgreSQL 18 (starts automatically in the devcontainer)
php artisan serve                              # app on :8000 (plus: npm run dev for Vite on :5173)
vendor/bin/pest                                # all tests (PostgreSQL, database app_test)
vendor/bin/pest tests/Feature/Pages/PageTest.php
vendor/bin/pest --filter="moves pages"         # single test
vendor/bin/pint                                # code style
vendor/bin/phpstan analyse --memory-limit=2G   # Larastan level 5
npm run build                                  # assets (the editor bundle is loaded lazily)
python3 scripts/build-wiki.py [--push]         # wiki from handbuch/
```

Xdebug in the devcontainer is slow and noisy: `export XDEBUG_MODE=off`. Locally, sign-in links end up in `storage/logs/laravel.log` (`MAIL_MAILER=log`).

Slash commands: `/login-audit` checks and hardens the login flow.

## Architecture

```
app/Core/             registries, activity log, middleware (SecurityHeaders, SetLocale), legacy redirects, rate limits
app/Modules/<Module>/ per module: Models, Services, Contracts, Policies, Http, Sidebar, routes.php, <Module>ServiceProvider
app/Filament/         admin UI (/admin) – writes only through the module services
resources/views/      frontend (Blade, no Livewire); layouts as anonymous components x-layouts.app / x-layouts.guest
resources/js/         app.js (Alpine), tree.js (sidebar tree, menu, drag & drop), editor.js (TipTap + live preview), passkeys.js
```

Modules: `Workspace` (settings, users, home page), `Auth` (magic link, passkeys, TOTP, account), `Groups`, `Spaces`, `Pages` (pages, versions, labels, attachments, favorites, trash, space overview, page tree), `Search`, `ImportExport`. Provider order in `bootstrap/providers.php`.

**Module boundaries** – modules never touch other modules' internals, only:
- **Registries in `app/Core`** (singletons): `NavigationRegistry` (main navigation in the header, account menu, mobile tab bar), `SidebarRegistry` (sections of the left sidebar: Spaces adds the space switcher, Pages the page tree), `MenuRegistry` (context menus `space`, `space.pages`, `page` – e.g. ImportExport adds the export entries to the page menu), `DashboardRegistry` (home page widgets), `SearchRegistry` (`SearchProvider` per content type), `ImportRegistry`/`ExportRegistry` (formats). New features hook in there, not in the layout or in other modules' controllers.
- **Menus:** `MenuItem` visibility is decided per space (`visible(User, Space)`), not per page – the `page` menu is rendered once per space as a `<template>` with the placeholder `__PAGE__` and filled in `resources/js/tree.js`. URL closures get the slug for the `space` menu and the page id for the `page` menu.
- **Contracts**: `Spaces\Contracts\CurrentSpace` provides the space of the sidebar (explicitly set, via resolvers – Spaces for `{space}`, Pages for the current page –, else the last visited, else the first readable space); state lives in request attributes, never in the service. `Spaces\Contracts\SpaceAccess` is the only place that answers "what may this user do in this space". `Groups\Contracts\GroupMembership`; `Pages\Contracts\PageWriter` for page changes from other modules (import, later MCP/Bases).
- **Events**: `Spaces\Events\SpaceDeleting` – Pages removes attachment files there.
- Other modules' models may be read (relations, queries); writes only go through their services.

**Rules that are easy to break:**
- Every query over pages/attachments filters with `SpaceAccess::spaceIdsFor($user)` – also search, labels, dashboard widgets, exports. Spaces without permission are invisible.
- Roles: `SpaceRole` reader < editor < admin (policy abilities `view`, `contribute`, `moderate`); workspace admins are space admins everywhere. The group *Everyone* (`groups.system_key = 'everyone'`) has no stored members, it applies to everybody implicitly.
- Page content is **Markdown** and the single source (editor, search, import, export, diff). Rendered only through `MarkdownRenderer` (`html_input => escape`, table of contents for `[TOC]`/`\[TOC\]`/`[[_TOC_]]`, heading anchors `#h-<slug>`). The TipTap editor (`resources/js/editor.js`) reads/writes Markdown into the textarea that is submitted; the live preview posts to `pages.preview` and uses the same renderer.
- Every content change creates a `PageVersion` via `PageService`; the controller checks the submitted `version` (no silent overwrite). `amendLatestVersion()` only for machine fix-ups right after creation (link rewriting on import).
- Trash = SoftDeletes, always including sub-pages (`descendantIds`). Permanent delete only from the trash.
- Tree order: `PageService::place()` (drag & drop: before/after/inside) renumbers siblings without gaps; `duplicate()` copies sub-pages, labels and attachments (files are copied, links in the content rewritten).
- Full-text search: generated `tsvector` columns (`'simple'` config, language-neutral) with GIN index; search terms only via `SearchQuery::toTsQuery()` as bound parameter.
- Attachments on the private disk, delivered only via `AttachmentController` (inline only `attachments.inline_mimes`). Import only creates attachments with allowed extensions.
- Space icons: `spaces.icon_path` on the private disk (upload via Filament, raster images only), delivered only via `SpaceIconController` with a permission check; `SpaceService::replaceIcon()` deletes the old file.
- `Workspace::current()` is cached per request (scoped binding) – never cache in static properties, otherwise tests leak into each other.
- No passwords: `password.confirm` (Laravel/passkey package) points to `/confirm` (passkey or confirmation link); every sign-in counts as fresh confirmation for 15 minutes (`Login` listener in `AuthServiceProvider`). Do not put POST/DELETE routes behind `password.confirm` (the redirect back would be a GET) – protect the GET page in front instead.
- Filament resources/pages use the trait `App\Filament\Concerns\AdminOnly` and write through the services in `handleRecordCreation`/`handleRecordUpdate`.
- Test data and examples only with invented names; never take names from screenshots or real systems.

**URLs:** all paths and Filament slugs are **English**, whatever the UI language (`/spaces/{space}`, `/pages/{page}/edit`, `/admin/users/create`).
- Pages have readable URLs `/<space-slug>/<title-slug>-<id>` (route `pages.view`, built with `$page->url()` or `Pages\Support\PageUrl::to()`); the id at the end counts, stale space or title parts get a 301 to the canonical URL. The route has no prefix and is registered in `booted()` after all other routes; space slugs must not be in `Space::RESERVED_SLUGS` – add new first path segments there.
- `/pages/{id}` (route `pages.show`) is the permanent link and redirects to the readable URL. Links stored in content (import, link rewriting) use it, because it survives renames and moves.
- The former German paths redirect with 301 (`App\Core\Http\LegacyRedirects`, registered per module in its `routes.php`) – do not remove them: old sign-in links, bookmarks and links stored in content (`/seiten/…`, `/anhaenge/…`) depend on them; the ZIP export recognises both forms.

**Translations:** source strings in English in `__()`, translations in `lang/{de,en,fr,it,es}.json`. `tests/Feature/LocaleTest.php` fails if a `__('…')` key is missing in `de.json` or a language is incomplete – always add new strings to all five files. Labels translated indirectly (`NavigationItem::$label`, `MenuItem` labels/`activeLabel`/`confirm`, `label()` of importers/exporters/search providers) as well – the test also checks `new MenuItem('key', 'Label', …)`. The Laravel strings (`lang/<locale>/*.php`, the base set of the JSON files) came once from `laravel-lang/common`; the package is deliberately not installed (abandoned dependencies make `composer audit` fail) – to update, install it briefly, run `php artisan lang:update`, remove it again.

## Deviations from the standards

- URLs and Filament slugs in English instead of German: the UI has five languages, a single language in the addresses would be foreign to most users.
- Documentation (`handbuch/`, wiki, README, this file) in English instead of German, as requested for this project.
- `Referrer-Policy` is `same-origin` globally and `no-referrer` on the magic link page.
- The CSP allows `'unsafe-inline'`/`'unsafe-eval'` for scripts because Alpine/Livewire (Filament) evaluate expressions at runtime.
