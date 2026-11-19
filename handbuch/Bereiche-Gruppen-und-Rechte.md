# Bereiche, Gruppen und Rechte

## Bereiche

Ein Bereich bündelt Seiten, Rechte und Beteiligte für ein Team, ein Projekt oder einen Zweck. Bereiche legen Administratoren unter **Administration → Bereiche** an: Name, Symbol (Emoji), URL-Name (z. B. `personal` → `/spaces/personal`) und Beschreibung.

Das Löschen eines Bereichs entfernt alle Seiten, Versionen und Anhänge endgültig.

## Rollen in einem Bereich

| Rolle | Darf |
|---|---|
| **Lesen** | Seiten, Versionen und Anhänge ansehen, suchen, exportieren |
| **Bearbeiten** | zusätzlich Seiten anlegen, bearbeiten, verschieben, Versionen wiederherstellen, importieren, Anhänge hochladen und löschen |
| **Bereichsadmin** | zusätzlich Seiten in den Papierkorb legen, wiederherstellen und endgültig löschen |

**Administratoren des Workspaces** haben in allen Bereichen volle Rechte und verwalten Bereiche, Gruppen und Benutzer.

Ohne Berechtigung ist ein Bereich unsichtbar: Er erscheint weder in Listen noch in der Suche.

## Rechte vergeben

In **Administration → Bereiche → (Bereich) → Berechtigungen** je Zeile:

- **Art:** Gruppe oder Benutzer
- **Gruppe oder Benutzer**
- **Rolle**

Hat jemand mehrere Berechtigungen (direkt und über Gruppen), gilt die höchste Rolle.

## Gruppen

Statt jedem Benutzer einzeln Rechte zu geben, vergibst du sie an Gruppen (**Administration → Gruppen**).

- **Everyone:** Diese Gruppe gibt es immer. Alle Benutzer gehören automatisch dazu, auch neue. Sie lässt sich nicht löschen und hat keine Mitgliederliste. Beispiel: Einen Bereich „Allgemeines“ mit *Everyone → Lesen* sehen alle.
- Eigene Gruppen (z. B. „Redaktion“, „Personalabteilung“) bekommen Mitglieder in der Gruppe oder beim Benutzer.

Beispiel:

| Bereich | Berechtigung |
|---|---|
| Allgemeines | Everyone → Lesen, Redaktion → Bearbeiten |
| Personal | Personalabteilung → Bereichsadmin |
| IT | IT-Team → Bearbeiten, Everyone → Lesen |
