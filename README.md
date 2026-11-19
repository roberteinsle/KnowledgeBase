# Knowledgebase

Wissensdatenbank für Teams: Bereiche mit verschachtelten Seiten, Versionsverlauf, Labels, Anhänge, Volltextsuche und Import/Export von Markdown (Datei, ZIP aus Obsidian/Notion oder Copy & Paste). Anmeldung per Passkey oder E-Mail-Link, optional mit Authenticator-App. Oberfläche auf Deutsch, Englisch, Französisch, Italienisch und Spanisch.

## Module

- **Workspace** – Einstellungen, Benutzer, Startseite
- **Bereiche und Gruppen** – Rechte je Bereich (Lesen, Bearbeiten, Bereichsadmin), Gruppe *Everyone*
- **Seiten** – Markdown-Editor, Unterseiten, Versionen, Labels, Anhänge, Papierkorb
- **Suche** – Volltext über Seiten und Anhänge (PostgreSQL)
- **Import und Export** – Markdown, ZIP, Backup je Bereich
- **Anmeldung** – Passkeys, Magic Link, TOTP

## Schnellstart

1. Codespace öffnen (Code → Codespaces → Create codespace on main).
2. `php artisan serve` und in einem zweiten Terminal `npm run dev`.
3. In `.env` `ADMIN_EMAIL` setzen, unter `/login` einen Link anfordern; der Link steht lokal in `storage/logs/laravel.log`.

Details stehen im Handbuch (`handbuch/`, Quelle des Wikis).

## Betrieb

GitHub Actions testet, baut das Image und schiebt es nach GHCR; Coolify holt es nur (`deploy/docker-compose.coolify.yml`). PostgreSQL ist eine eigene Coolify-Ressource. Der Admin-Bereich `/admin` liegt zusätzlich hinter Cloudflare Access.
