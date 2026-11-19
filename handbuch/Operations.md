# Operations

## Overview

- Laravel 13, Filament 5, PHP 8.5, PostgreSQL 18.
- GitHub Actions tests, builds the Docker image and pushes it to `ghcr.io/<owner>/knowledgebase`. Coolify only pulls the finished image (`deploy/docker-compose.coolify.yml`).
- PostgreSQL is a separate Coolify resource.
- Migrations run when the container starts.
- Attachments, space icons and profile pictures are stored in the volume `app-storage` (`storage/app/private`).

## Environment variables

| Variable | Meaning |
|---|---|
| `APP_KEY` | Key for sessions and encrypted fields (two-factor secrets). **Never change it**, otherwise two-factor setups and passkeys become unusable. |
| `APP_URL` | Public address, e.g. `https://kb.example.com`. Sign-in links are built from it, and passkeys are bound to this domain. |
| `ADMIN_EMAIL` | Address of the first administrator (see [Administration](Administration.md)). |
| `DB_*` | Access to the PostgreSQL resource. The database user only needs rights on its own database. |
| `MAIL_*` | SMTP for sign-in links (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`). Leave `MAIL_SCHEME` empty: port 465 uses `smtps`, other ports `smtp` with STARTTLS. Without working mail only users with a passkey can sign in. |
| `APP_LOCALE` | Fallback language if the workspace setting is missing (default `de`). |
| `DISPLAY_TIMEZONE` | Time zone for display (default `Europe/Berlin`). Times are always stored in UTC. |
| `PASSKEYS_USER_HANDLE_SECRET` | Optional. Defaults to `APP_KEY`. |

`APP_DEBUG` is fixed to `false` in the image.

## Addresses

All addresses are English: `/login`, `/spaces/<name>`, `/<space>/<title>-<number>` for pages, `/search`, `/account`, `/admin`. The German addresses of earlier versions (`/anmelden`, `/bereiche/…`, `/seiten/…`, `/anhaenge/…`, `/admin/benutzer` …) forward permanently to the new ones, so old bookmarks, sign-in links already sent and links in page content keep working. Links stored in content use the permanent form `/pages/<number>`, which survives renaming and moving.

## Cloudflare Access for `/admin`

The app is publicly reachable; the administration area is therefore additionally protected by Cloudflare Zero Trust:

1. Create a **self-hosted application** for `kb.example.com/admin` in Cloudflare Zero Trust.
2. Policy: everybody who is an administrator of the app.
3. Configure the server to accept web traffic only through Cloudflare (Cloudflare Tunnel or a firewall allowing only Cloudflare IPs). Otherwise Access can be bypassed via the server IP.

Access is an additional layer: the app checks sign-in and administrator rights on its own regardless.

As the app is only reachable through the proxy, it trusts the forwarded visitor IP (for rate limits).

## Rate limits

| What | Limit |
|---|---|
| All pages | 180 requests per minute per user or IP |
| Requesting a sign-in link | 5 per address in 15 minutes, 30 per IP per hour |
| Redeeming a sign-in link | 10 per IP per minute |
| Two-factor code | 5 per minute per sign-in, 20 per IP |
| Passkey endpoints | 10 per minute |
| Search | 60 per minute |
| Editor preview | 120 per minute |
| Uploads | 60 per hour |
| Import | 20 per hour and 50 per day per user |
| Export | 30 per hour |

## Backup

- **Database:** with the backup function of the PostgreSQL resource in Coolify.
- **Attachments, icons and profile pictures:** back up the volume `app-storage`.
- **Content as Markdown:** per space **Export** → ZIP. The archive can be imported into any installation (see [Import and Export](Import-and-Export.md)).

## Updates and rollback

Every push to `main` triggers tests, the image build and the redeploy; the image gets the tags `latest` and `sha-<commit>`. The running version is shown at the bottom of the account menu (top right, e.g. "Version c76fb8f") – this is how you check that a deploy has arrived. For a rollback set `APP_TAG` in Coolify to a `sha-…` tag and redeploy.

## Security

- Sign-in links: 32 bytes of randomness, stored only as SHA-256 hash, valid for 15 minutes, single use.
- Two-factor secrets and recovery codes are stored encrypted.
- Security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- Attachments, space icons and profile pictures on a private disk, delivered only after a permission check; only PDFs and raster images inline. Uploaded avatars are delivered only if their content really is a raster image.
- Markdown is rendered with escaped HTML; unsafe links are removed.
- No external resources (fonts, CDNs, avatar services) – everything is served by the app itself; avatars in the administration area are generated locally.
- The Content Security Policy only allows the app's own origin; `blob:` workers are allowed for image processing during uploads.
