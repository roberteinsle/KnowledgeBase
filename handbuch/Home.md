# Knowledgebase – Handbuch

Knowledgebase ist eine Wissensdatenbank für Teams: Dokumentation in **Bereichen** mit verschachtelten **Seiten**, Versionsverlauf, Labels, Anhängen, Volltextsuche und Import/Export von **Markdown**.

## Für Benutzer

- [Anmeldung](Anmeldung.md) – Passkey, Anmeldelink per E-Mail, Zwei-Faktor-Authentifizierung
- [Seiten](Seiten.md) – schreiben, verschachteln, Versionen, Labels, Anhänge, Papierkorb
- [Import und Export](Import-und-Export.md) – Markdown-Dateien, ZIP-Archive (Obsidian, Notion), Einfügen per Copy & Paste, Backups
- [Suche](Suche.md)

## Für Administratoren und Betreiber

- [Bereiche, Gruppen und Rechte](Bereiche-Gruppen-und-Rechte.md)
- [Administration](Administration.md) – Benutzer, Gruppen, Bereiche, Labels, Protokoll, Einstellungen
- [Betrieb](Betrieb.md) – Installation, Umgebungsvariablen, Cloudflare Access, Updates, Backup

## Aufbau

| Ebene | Bedeutung |
|---|---|
| **Workspace** | Die ganze Installation mit Name, Standardsprache und Sicherheitseinstellungen. |
| **Bereich** (Space) | Ein abgegrenzter Teil für ein Team, ein Projekt oder eine Abteilung – mit eigenen Rechten. |
| **Seite** (Page) | Ein Dokument in einem Bereich. Seiten können Unterseiten haben. |
| **Gruppe** | Mehrere Benutzer, denen gemeinsam Rechte gegeben werden. Alle Benutzer gehören automatisch zur Gruppe **Everyone**. |

## Navigation

- **Kopfzeile:** Logo und Startseite links, Suche in der Mitte (Taste <kbd>/</kbd>), Konto rechts.
- **Linke Leiste:** oben der **Bereichswechsler** (mit Suchfeld), darunter *Überblick*, *Suche* (nur in diesem Bereich), *Bereichseinstellungen* (nur Administratoren) und *Neue Seite*. Es folgt der **Seitenbaum** des Bereichs. Ohne geöffneten Bereich zeigt die Leiste den zuletzt besuchten.
- **Handy:** untere Leiste mit Start, Bereiche, Suche und Konto; die linke Leiste öffnet sich über **Bereiche und Seiten**.
- **Startseite und Überblick eines Bereichs:** Reiter *Zuletzt geändert*, *Favoriten* und *Von mir erstellt*.

## Sprachen

Die Oberfläche gibt es auf Deutsch, Englisch, Französisch, Italienisch und Spanisch. Jeder Benutzer wählt seine Sprache unter **Konto**; ohne Auswahl gilt die Sprache des Browsers, sonst die Standardsprache des Workspaces.

## Geplant

Datenbanken (Bases), Kommentare und ein MCP-Server für KI-Assistenten (z. B. claude.ai) folgen in späteren Versionen.
