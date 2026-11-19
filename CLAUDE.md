# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

@docs/STANDARDS.md

# Knowledgebase

<Ein bis zwei Sätze: was die App macht und für wen.>

Die gemeinsamen Standards oben gelten vollständig. Hier steht nur, was für dieses Projekt dazukommt oder bewusst abweicht.

## Stand des Repos

Das Repo ist noch die leere Vorlage `app-template`: Es gibt noch kein `composer.json`, also auch kein Laravel. Zuerst einmal `bin/init-laravel.sh` ausführen (installiert Laravel 13, Filament 5, Pest und Larastan, stellt `phpunit.xml` auf `pgsql`/`app_test` um, legt `phpstan.neon` mit Level 5 auf `app/` an, fragt interaktiv nach der Filament-Panel-ID → `admin`). Bis dahin überspringt die CI Tests und Image-Build (Job `check`). Danach diesen Abschnitt löschen.

## Projekt

- Sichtbarkeit: <öffentlich/privat>
- Sprache der Oberfläche: Deutsch
- Erreichbarkeit: <öffentlich → Admin-Bereich `/admin` hinter Cloudflare Access / nur im Tailnet>
- Deploy: automatisch per Webhook (`AUTO_DEPLOY=true`)<, oder Begründung für manuell>
- Queue und Scheduler: keine (`QUEUE_CONNECTION=sync`). Werden Jobs oder ein Scheduler gebraucht: `QUEUE_CONNECTION=database` und eigene Services `worker`/`scheduler` aus demselben Image in `deploy/docker-compose.coolify.yml`.

## Befehle

```bash
bin/init-laravel.sh                   # nur einmal (siehe oben)
docker compose up -d db               # PostgreSQL 18 lokal (startet im Devcontainer automatisch)
php artisan serve                     # App auf :8000 (plus: npm run dev für Vite auf :5173)
vendor/bin/pest                       # alle Tests (PostgreSQL, Datenbank app_test)
vendor/bin/pest tests/Feature/FooTest.php   # eine Datei
vendor/bin/pest --filter="name des tests"   # einzelner Test
vendor/bin/pint                       # Code-Stil (CI: --test)
vendor/bin/phpstan analyse            # Larastan (CI: --memory-limit=2G)
docker compose up -d --build          # App im Produktions-Image auf :8080 testen
```

Die Testdatenbank `app_test` legt `.devcontainer/setup.sh` an; fehlt sie: `docker compose exec -T db createdb -U app app_test`.

Slash-Commands: `/login-audit` prüft und härtet den Login-Flow.

## Build und Betrieb

- Image: `serversideup/php:8.5-fpm-nginx-alpine`, mehrstufig (Vite-Assets → Composer ohne Scripts → Runtime), läuft als `www-data` auf Port 8080, Healthcheck `/up`. Migrationen und `optimize` laufen beim Start über die AUTORUN-Variablen von serversideup, nicht über eigene Skripte.
- Eigene Startskripte gehören nach `docker/entrypoint.d/` (vor `50-laravel-automations`). `40-storage.sh` legt die `storage/`-Ordner im Volume an und bricht ab, wenn das Volume nicht beschreibbar ist.
- `deploy/docker-compose.coolify.yml` liest Coolify nicht aus dem Repo: Nach Änderungen den Inhalt von Hand in Coolify einfügen. Neue Env-Variablen dort **und** in `.env.example` ergänzen.
- PHP-Erweiterungen stehen an drei Stellen: `Dockerfile` (`install-php-extensions`), `.devcontainer/setup.sh` und `.github/workflows/build.yml` (`setup-php`).

## Bekannte Stolpersteine

- Der lokale Branch heißt `master`, `.github/workflows/build.yml` reagiert aber nur auf `main` (Push, Pull Request, `latest`-Tag, Auto-Deploy). Vor dem ersten Push den Branch in `main` umbenennen, sonst laufen weder CI noch Deploy.
- `scripts/build-wiki.py` (laut Standards für das Wiki aus `handbuch/`) gibt es in diesem Repo noch nicht.

## Architektur

<Module, Registries, wichtige Services und Regeln, die man leicht bricht.>

## Abweichungen von den Standards

Keine.
