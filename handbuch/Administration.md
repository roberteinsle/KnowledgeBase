# Administration

Der Administrationsbereich liegt unter `/admin` und ist nur für Administratoren des Workspaces. Erreichbar über das Menü oben rechts → **Administration**.

## Benutzer

**Administration → Benutzer**

- **Neu:** Name, E-Mail-Adresse, Rolle (*Mitglied* oder *Administrator*), Sprache, Gruppen. Der Benutzer meldet sich danach selbst per Anmeldelink an.
- **Anmeldelink senden:** schickt dem Benutzer direkt einen Link (z. B. zur Einladung).
- **Deaktivieren** (Schalter *Aktiv*): Der Benutzer wird bei der nächsten Anfrage abgemeldet und kann sich nicht mehr anmelden – weder per Link noch per Passkey. Seine Seiten bleiben erhalten.
- **Löschen:** entfernt das Konto. Seiten bleiben erhalten; als Autor erscheint „unbekannt“.

Es muss immer mindestens ein aktiver Administrator bleiben; der letzte lässt sich weder herabstufen noch deaktivieren oder löschen.

### Erster Administrator

Bei der Installation gibt es noch keinen Benutzer. Die Adresse aus der Umgebungsvariable `ADMIN_EMAIL` kann sich trotzdem anmelden: Beim ersten Anfordern eines Anmeldelinks wird das Konto als Administrator angelegt.

## Gruppen

**Administration → Gruppen**: Name, Beschreibung, Mitglieder. Siehe [Bereiche, Gruppen und Rechte](Bereiche-Gruppen-und-Rechte.md).

## Bereiche

**Administration → Bereiche**: anlegen, Berechtigungen vergeben, löschen. **Öffnen** springt in den Bereich.

## Labels

**Administration → Labels**: alle Labels mit Anzahl der Seiten. Umbenennen oder löschen (die Seiten bleiben, nur das Label verschwindet).

## Aktivitätsprotokoll

**Administration → Protokoll** zeigt, wer wann was geändert hat: Anmeldungen, Seiten (angelegt, geändert, verschoben, gelöscht), Anhänge, Importe, Exporte, Bereiche, Gruppen, Benutzer und Einstellungen. Seiteninhalte, Anmeldelinks und Geheimnisse werden nicht protokolliert.

## Workspace-Einstellungen

**Administration → Workspace**

- **Name** und **Beschreibung** (erscheinen in der Kopfzeile, auf der Startseite und in E-Mails)
- **Standardsprache** für alle, die keine eigene Sprache gewählt haben und deren Browsersprache nicht unterstützt wird
- **Passkey oder Authenticator-App für alle verlangen**
