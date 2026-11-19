# Seiten

Seiten sind die eigentlichen Inhalte. Jede Seite gehört zu einem Bereich und kann Unterseiten haben.

## Seite anlegen

- Im Bereich: **Neue Seite** (Hauptaktion oben rechts) oder in der Seitenleiste.
- Unterseite: auf der Elternseite **Mehr → Neue Unterseite**.

Dafür ist mindestens die Rolle **Bearbeiten** im Bereich nötig (siehe [Bereiche, Gruppen und Rechte](Bereiche-Gruppen-und-Rechte.md)).

## Schreiben

Der Editor zeigt formatierten Text, gespeichert wird **Markdown**. Dadurch bleiben Import, Export und Versionsvergleich verlustfrei.

- Werkzeugleiste: fett, kursiv, durchgestrichen, Code, Überschriften, Listen, Aufgabenlisten, Zitat, Codeblock, Tabelle, Link, Trennlinie.
- Markdown-Kürzel funktionieren beim Tippen: `# ` für Überschriften, `- ` für Listen, `1. ` für nummerierte Listen, `> ` für Zitate, ` ``` ` für Code.
- **Markdown** (rechts in der Werkzeugleiste) schaltet auf den reinen Markdown-Text um und zurück.
- Wird Markdown-Text eingefügt (z. B. aus einem anderen Tool), wandelt der Editor ihn direkt in Formatierung um.
- Ohne JavaScript erscheint ein einfaches Markdown-Textfeld.

HTML im Text wird aus Sicherheitsgründen nicht ausgeführt, sondern als Text angezeigt. Links mit `javascript:` werden entfernt.

### Gleichzeitiges Bearbeiten

Speichert jemand anderes die Seite, während du sie bearbeitest, wird dein Speichern abgelehnt, statt die Änderungen der anderen Person zu überschreiben. Kopiere dann deinen Text, lade die Seite neu und füge deine Änderungen ein.

## Labels

Labels ordnen Seiten quer über Bereiche. Beim Bearbeiten im Feld **Labels** durch Kommas getrennt eingeben. Sie werden kleingeschrieben, Leerzeichen werden zu Bindestrichen (aus „HR Prozesse“ wird `hr-prozesse`). Ein Klick auf ein Label zeigt alle Seiten mit diesem Label, die du lesen darfst.

## Versionsverlauf

Jedes Speichern mit geändertem Titel oder Inhalt erzeugt eine neue Version.

- **Mehr → Versionsverlauf** listet alle Versionen mit Datum und Autor.
- Eine Version zeigt die **Änderungen** zur vorigen Version (grün hinzugefügt, rot entfernt) und den Inhalt.
- **Wiederherstellen** übernimmt eine alte Version als neue aktuelle Version – die bisherige bleibt im Verlauf.

## Verschieben

**Mehr → Verschieben** hängt eine Seite unter eine andere Seite desselben Bereichs oder auf die oberste Ebene. Unterseiten wandern mit. Eine Seite kann nicht unter sich selbst oder ihre eigenen Unterseiten verschoben werden.

## Anhänge

Unten auf jeder Seite: Dateien hochladen (bis 20 MB je Datei, bis 10 Dateien auf einmal). Erlaubt sind PDF, Bilder (PNG, JPG, GIF, WebP), Text, Markdown, CSV, ZIP und Office-Dokumente (docx, xlsx, pptx, odt, ods).

- PDFs und Bilder öffnen sich im Browser, alle anderen Dateien werden heruntergeladen.
- Anhänge sieht nur, wer die Seite lesen darf.
- Text-, Markdown- und CSV-Dateien werden für die [Suche](Suche.md) mit erfasst.
- Ein Bild im Text einbinden: `![Beschreibung](/attachments/123)` – die Adresse ist der Link des Anhangs.

## Papierkorb

**Mehr → In den Papierkorb** verschiebt die Seite samt Unterseiten in den Papierkorb des Bereichs (Rolle **Bereichsadmin** nötig).

Im **Papierkorb** (Seitenleiste des Bereichs):

- **Wiederherstellen** holt die Seite mit den Unterseiten zurück, die mit ihr gelöscht wurden. Liegt die Elternseite noch im Papierkorb, landet die Seite auf der obersten Ebene.
- **Endgültig löschen** entfernt Seite, Unterseiten, Versionen und Anhänge unwiderruflich.
