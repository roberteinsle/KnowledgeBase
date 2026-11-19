<div align="center">
<img src="docs/logo.svg" width="110" alt="Knowledgebase"/>

# Knowledgebase
### The self-hosted knowledge base for teams

[![Wiki](https://img.shields.io/badge/Docs-Wiki-1b2645)](../../wiki)
[![Releases](https://img.shields.io/badge/Releases-GitHub-4f46e5)](../../releases)
![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-336791)

</div>

---

Knowledgebase keeps your team's documentation in one place: **spaces** for teams and projects, nested **pages** written in a rich editor that stores plain **Markdown**, version history, full-text search, import from Obsidian, Notion or any Markdown files and an MCP server for AI assistants like Claude – on your own server. Sign-in works without passwords, via passkey or e-mail link.

## Quick start

Knowledgebase runs as a ready-made Docker image (`ghcr.io/<owner>/knowledgebase`) with a PostgreSQL database. You need a small server, a domain and a mailbox for sign-in e-mails.

1. Create a **PostgreSQL** database and the **app** from `deploy/docker-compose.coolify.yml` (e.g. in Coolify).
2. Set the environment variables – at least `APP_KEY`, `APP_URL`, `ADMIN_EMAIL`, `DB_*` and `MAIL_*` ([Operations](../../wiki/Operations)).
3. Open the app, request a sign-in link for the `ADMIN_EMAIL` address and create your first space.

Updates are a new image tag; the database is migrated automatically on start.

## Features

| | |
|---|---|
| [**Spaces & permissions**](../../wiki/Spaces-Groups-and-Permissions) | Spaces with icon, roles Reader / Editor / Space admin for users and groups |
| [**Pages**](../../wiki/Pages) | Page tree with drag & drop, rich editor on Markdown with live preview, table of contents, labels, attachments, favorites, trash |
| [**Version history**](../../wiki/Pages#version-history) | Every change is a version – compare, restore, no silent overwrites |
| [**Import & export**](../../wiki/Import-and-Export) | Markdown files, ZIP archives (Obsidian, Notion), copy & paste; export as Markdown, HTML or ZIP |
| [**Search**](../../wiki/Search) | Full-text search over pages and attachments, per space or everywhere |
| [**MCP**](../../wiki/MCP) | Connect Claude and other AI assistants: search, read, create and update pages with your permissions (OAuth) |
| [**Sign-in**](../../wiki/Sign-in) | Passkeys, magic links, authenticator app with recovery codes |
| [**Administration**](../../wiki/Administration) | Users, groups, spaces, labels, activity log, workspace settings |

The interface is available in German, English, French, Italian and Spanish. Everything else – every page, role and setting – is described in the **[wiki](../../wiki)**.

## Privacy & security

Your content stays on your server: no telemetry, no external fonts or CDNs. Permissions are checked on every request, attachments are delivered only to users who may read them, and every change ends up in the activity log. Each change to the code runs through the tests, static analysis, a secret scan and a vulnerability scan of the image.

## Development

Laravel 13, Filament 5, Tailwind 4, TipTap, PostgreSQL 18, tests with Pest. The repository opens ready to use in the devcontainer or GitHub Codespaces; `php artisan serve` and `npm run dev` start the app, `vendor/bin/pest` runs the tests. Set `ADMIN_EMAIL` in `.env` – locally the sign-in link appears in `storage/logs/laravel.log`. Architecture notes are in [CLAUDE.md](CLAUDE.md), the full manual in [`handbuch/`](handbuch), from which the wiki is generated.

Ideas and bugs are welcome as [issues](../../issues).
