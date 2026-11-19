# Anmeldung

Es gibt keine Passwörter. Angemeldet wird mit einem **Passkey** oder einem **Anmeldelink per E-Mail**, optional mit einem zweiten Faktor aus einer Authenticator-App.

## Mit Passkey (empfohlen)

Ein Passkey ist ein Schlüssel, der sicher auf deinem Gerät (oder im Passwortmanager) liegt. Du entsperrst ihn mit Fingerabdruck, Gesicht oder Geräte-PIN.

1. Auf der Anmeldeseite **Mit Passkey anmelden** wählen.
2. Den Passkey im Dialog des Browsers bestätigen.

Ein Passkey gilt bereits als zwei Faktoren (Gerät + Biometrie/PIN); eine zusätzliche Code-Abfrage gibt es dabei nicht.

### Passkey einrichten

- Nach der ersten Anmeldung per E-Mail-Link bietet die App an, sofort einen Passkey einzurichten.
- Jederzeit unter **Konto → Sicherheit → Passkey hinzufügen**. Vergib einen Namen (z. B. „Laptop“), damit du ihn später wiedererkennst.
- Du kannst mehrere Passkeys haben (Laptop, Handy, Sicherheitsschlüssel) und einzelne wieder entfernen.

## Mit Anmeldelink per E-Mail

1. E-Mail-Adresse eingeben und **Anmeldelink senden** wählen.
2. Die Seite zeigt immer: „Falls die Adresse existiert, wurde ein Anmeldelink an sie gesendet.“ – auch bei unbekannten Adressen, damit niemand ausprobieren kann, welche Adressen ein Konto haben.
3. In der E-Mail auf **Anmelden** klicken und auf der folgenden Seite **Jetzt anmelden** bestätigen.

Der Link

- ist **15 Minuten** gültig und funktioniert **nur einmal**,
- wird ungültig, sobald du einen neuen anforderst,
- lässt alle anderen offenen Links verfallen, sobald du dich angemeldet hast.

Der Bestätigungsknopf ist Absicht: Manche E-Mail-Programme öffnen Links vorab zur Prüfung. Erst der Klick auf den Knopf meldet an.

Nach zu vielen Versuchen in kurzer Zeit (je Adresse und je Internetanschluss) wird das Anfordern kurz gesperrt.

## Zwei-Faktor-Authentifizierung (Authenticator-App)

Wer sich per E-Mail-Link anmeldet, kann zusätzlich einen Code aus einer Authenticator-App verlangen (z. B. Aegis, 2FAS, Google Authenticator, 1Password).

**Einrichten:** Konto → Sicherheit → **Authenticator-App einrichten**, QR-Code scannen, angezeigten Code eingeben. Danach erscheinen **Wiederherstellungscodes** – einmal sicher aufbewahren. Jeder Code funktioniert genau einmal und ersetzt den App-Code, falls das Handy fehlt.

**Abschalten:** Konto → Sicherheit → **Deaktivieren**.

Administratoren können unter **Administration → Workspace** festlegen, dass alle einen Passkey oder eine Authenticator-App haben müssen. Wer beides nicht hat, wird nach der Anmeldung zur Sicherheitsseite geleitet.

## Erneute Bestätigung

Die Sicherheitsseite (Passkeys, Zwei-Faktor) verlangt eine Anmeldung, die höchstens 15 Minuten zurückliegt. Sonst bestätigst du mit deinem Passkey oder lässt dir einen Bestätigungslink schicken.

## Abmelden

Oben rechts auf deinen Namen → **Abmelden**.

## Häufige Fragen

**Der Link ist abgelaufen.** Einfach einen neuen anfordern; alte Links funktionieren nicht mehr.

**Ich habe mein Handy mit der Authenticator-App verloren.** Mit einem Wiederherstellungscode anmelden und unter Konto → Sicherheit die App neu einrichten. Ohne Codes kann ein Administrator nicht helfen, ohne das Konto neu anzulegen – deshalb einen Passkey als zweiten Weg einrichten.

**Ich bekomme keine E-Mail.** Spam-Ordner prüfen. Ist das Konto deaktiviert oder unbekannt, wird bewusst keine E-Mail verschickt.
