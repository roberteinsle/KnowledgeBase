# Import und Export

Markdown ist das Austauschformat. Was exportiert wurde, lässt sich ohne Verlust wieder importieren.

## Importieren

Im Bereich: Seitenleiste → **Markdown importieren** oder auf einer Seite **Mehr → Unter dieser Seite importieren**. Nötig ist die Rolle **Bearbeiten**.

Unter **Importieren nach** wählst du, wo die neuen Seiten hängen sollen (oberste Ebene oder unter einer bestehenden Seite).

### Text einfügen (Copy & Paste)

**Text einfügen** wählen und Markdown in das Feld kopieren. Ohne eigenen Titel wird die erste Überschrift (`# …`) zum Seitentitel und aus dem Text entfernt.

### Markdown-Datei

`.md`, `.markdown` oder `.txt` hochladen. Der Titel kommt aus

1. dem Front Matter (`title:`),
2. sonst der ersten Überschrift `# …`,
3. sonst dem Dateinamen.

Labels kommen aus `tags:` oder `labels:` im Front Matter:

```markdown
---
title: Reiserichtlinie
tags:
  - personal
  - reisen
---

Text der Seite …
```

Dateien in anderer Kodierung als UTF-8 (z. B. Windows-1252) werden umgewandelt.

### ZIP-Archiv (Obsidian, Notion, eigener Export)

Ein ZIP-Archiv mit Markdown-Dateien wird als Seitenbaum importiert:

- **Ordner werden zu Unterseiten.** Den Inhalt der Ordnerseite liefert `Ordner.md` neben dem Ordner (Notion, eigener Export) oder `Ordner/index.md` bzw. `README.md`. Fehlt beides, entsteht eine leere Seite mit dem Ordnernamen.
- **Bilder und Dateien**, auf die eine Seite verlinkt (`![](bilder/logo.png)`, `[PDF](docs/plan.pdf)`), werden Anhänge dieser Seite; die Links zeigen danach auf den Anhang. Erlaubt sind dieselben Dateitypen wie beim Hochladen von Anhängen.
- **Links zwischen Markdown-Dateien** (`[Urlaub](Personal/Urlaub.md)`) zeigen danach auf die importierten Seiten.
- **Obsidian:** Wiki-Links `[[Seitentitel]]` bzw. `[[Seitentitel|Text]]` und Einbettungen `![[bild.png]]` werden umgewandelt.
- **Notion:** Die angehängten IDs in Dateinamen („Meeting notes 3f2a…“) werden entfernt.
- Ein gemeinsamer Wurzelordner (z. B. der Vault-Ordner) wird übersprungen.
- Versteckte Dateien und `__MACOSX` werden ignoriert, ebenso Pfade mit `..`.

### Grenzen

| Grenze | Wert |
|---|---|
| Dateigröße beim Upload | 20 MB |
| Entpackte Größe eines ZIP-Archivs | 50 MB |
| Dokumente je Archiv | 500 |
| Eingefügter Text | 500 000 Zeichen |
| Importe je Benutzer und Tag | 50 |

Ein Import läuft vollständig oder gar nicht: Schlägt etwas fehl, wird nichts angelegt.

## Exportieren

| Was | Wo | Ergebnis |
|---|---|---|
| Eine Seite | Seite → **Mehr → Diese Seite als Markdown** | `.md` mit Front Matter (Titel, Labels) |
| Seite mit Unterseiten | Seite → **Mehr → Diese Seite mit Unterseiten und Anhängen** | `.zip` |
| Ganzer Bereich | Bereich → **Export** | `.zip` – geeignet als Backup oder zum Umzug |

Aufbau des ZIP-Archivs:

```
Handbuch.md
Handbuch/
    Urlaub.md
    Krankheit.md
_attachments/
    17-logo.png
```

Links auf andere exportierte Seiten und auf Anhänge sind relative Pfade, damit das Archiv auch in Obsidian oder einem Git-Repository funktioniert. Exportieren darf, wer lesen darf. Seiten im Papierkorb werden nicht exportiert.
