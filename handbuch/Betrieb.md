# Betrieb

## Überblick

- Laravel 13, Filament 5, PHP 8.5, PostgreSQL 18.
- GitHub Actions testet, baut das Docker-Image und schiebt es nach `ghcr.io/<konto>/knowledgebase`. Coolify holt nur das fertige Image (`deploy/docker-compose.coolify.yml`).
- PostgreSQL ist eine eigene Coolify-Ressource.
- Migrationen laufen beim Containerstart.
- Anhänge liegen im Volume `app-storage` (`storage/app/private/attachments`).

## Umgebungsvariablen

| Variable | Bedeutung |
|---|---|
| `APP_KEY` | Schlüssel für Sitzungen und verschlüsselte Felder (2FA-Geheimnisse). **Nie ändern**, sonst sind 2FA-Einrichtungen und Passkeys unbrauchbar. |
| `APP_URL` | Öffentliche Adresse, z. B. `https://wissen.example.com`. Aus ihr werden Anmeldelinks gebaut, und Passkeys sind an diese Domain gebunden. |
| `ADMIN_EMAIL` | Adresse des ersten Administrators (siehe [Administration](Administration.md)). |
| `DB_*` | Zugang zur PostgreSQL-Ressource. Der Datenbank-Benutzer braucht nur Rechte auf die eigene Datenbank. |
| `MAIL_*` | SMTP für Anmeldelinks. Ohne funktionierenden Mailversand kann sich nur anmelden, wer schon einen Passkey hat. |
| `APP_LOCALE` | Rückfallsprache, falls die Workspace-Einstellung fehlt (Standard `de`). |
| `DISPLAY_TIMEZONE` | Zeitzone für die Anzeige (Standard `Europe/Berlin`). Gespeichert wird immer UTC. |
| `PASSKEYS_USER_HANDLE_SECRET` | Optional. Standard ist `APP_KEY`. |

`APP_DEBUG` ist im Image fest auf `false`.

## Cloudflare Access für `/admin`

Die App ist öffentlich erreichbar; der Administrationsbereich liegt deshalb zusätzlich hinter Cloudflare Zero Trust:

1. In Cloudflare Zero Trust eine **Self-hosted Application** für `wissen.example.com/admin` anlegen.
2. Policy: alle Personen, die Administratoren der App sind.
3. Den Server so einrichten, dass er Web-Verkehr nur über Cloudflare annimmt (Cloudflare Tunnel oder Firewall nur für Cloudflare-IPs). Sonst lässt sich Access über die Server-IP umgehen.

Access ist eine zusätzliche Schicht: Die App prüft Anmeldung und Administratorrechte unabhängig davon selbst.

Da die App nur über den Proxy erreichbar ist, vertraut sie der weitergeleiteten Besucher-IP (für die Rate-Limits).

## Rate-Limits

| Was | Grenze |
|---|---|
| Alle Seiten | 180 Anfragen pro Minute je Benutzer bzw. IP |
| Anmeldelink anfordern | 5 je Adresse in 15 Minuten, 30 je IP pro Stunde |
| Anmeldelink einlösen | 10 je IP pro Minute |
| Zwei-Faktor-Code | 5 pro Minute je Anmeldung, 20 je IP |
| Passkey-Endpunkte | 10 pro Minute |
| Suche | 60 pro Minute |
| Uploads | 60 pro Stunde |
| Import | 20 pro Stunde und 50 pro Tag je Benutzer |
| Export | 30 pro Stunde |

## Backup

- **Datenbank:** über die Backup-Funktion der PostgreSQL-Ressource in Coolify.
- **Anhänge:** Volume `app-storage` sichern.
- **Inhalte als Markdown:** je Bereich **Export** → ZIP. Das Archiv lässt sich in jede Installation wieder importieren (siehe [Import und Export](Import-und-Export.md)).

## Updates und Rollback

Jeder Push auf `main` löst Tests, Image-Build und das Redeploy aus. Für einen Rollback in Coolify `APP_TAG` auf einen `sha-…`-Tag setzen und neu deployen.

## Sicherheit

- Anmeldelinks: 32 Byte Zufall, in der Datenbank nur als SHA-256-Hash, 15 Minuten gültig, einmalig.
- 2FA-Geheimnisse und Wiederherstellungscodes verschlüsselt gespeichert.
- Security-Header (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- Anhänge auf einer privaten Disk, Auslieferung nur nach Rechteprüfung; nur PDFs und Rasterbilder inline.
- Markdown wird mit maskiertem HTML gerendert; unsichere Links werden entfernt.
- Keine externen Ressourcen (Schriften, CDNs) – alles wird von der App selbst ausgeliefert.

## Adressen

Alle Adressen sind englisch, unabhängig von der gewählten Sprache: `/login`, `/spaces/<name>`, `/pages/<nummer>`, `/search`, `/account`, `/admin`. Die deutschen Adressen früherer Versionen (`/anmelden`, `/bereiche/…`, `/seiten/…`, `/anhaenge/…`, `/admin/benutzer` …) leiten dauerhaft auf die neuen weiter; alte Lesezeichen, bereits verschickte Anmeldelinks und Links in Seiteninhalten funktionieren also weiter.

Für Cloudflare Access ändert sich nichts: Der Admin-Bereich liegt weiter unter `/admin`.
