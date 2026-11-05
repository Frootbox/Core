# Core/FrootboxDe

Verbindet eine Website mit Frootbox 1 mit frootbox.de (`https://account.frootbox.de`).
Eine Agentur sieht dort alle ihre Websites an einem Ort: erreichbar, Zertifikat,
Backup, Platz und Versionen. Die Extension spricht dasselbe Protokoll
„frootbox.de Connect v1“ wie Frootbox 2 (`documentation/concepts/hub.md` im Repo
von Frootbox 2). Von den drei Schaltern gibt es hier nur „Status“; Pakete und
Marketplace sind nicht für Frootbox 1, die Anmeldung über frootbox.de kommt später.

## Voraussetzungen

- PHP ab 8.2 mit `sodium` und `curl`
- PHP darf Anfragen nach außen schicken (zu `account.frootbox.de`)
- Der Ordner mit `localconfig.php` ist für PHP beschreibbar

## Installation

Unter „Erweiterungen“ `Core/FrootboxDe` installieren und danach „Neu laden“, damit
die Route zu den übrigen Routen kommt. Die App „frootbox.de“ erscheint in der
Seitenleiste, nur für SuperAdmins.

## Verbinden

- **Mit frootbox.de verbinden** öffnet eine Anfrage und schickt den Browser zu
  frootbox.de. Dort anmelden, Organisation wählen, bestätigen; zurück im Backend
  **Verbindung abschließen**.
- **Mit Code verbinden**: Inhaber und Admins erzeugen auf frootbox.de unter
  „Websites“ einen Code `XXXX-XXXX` (15 Minuten gültig), der hier eingelöst wird.
  frootbox.de prüft die Adresse eine Minute später.

Lokale Adressen (`localhost`, `.test`, private IPs) verbinden sich nicht mit
frootbox.de im Internet. Für einen Dienst auf dem eigenen Rechner in
`localconfig.php`:

```php
'Ext' => ['Core' => ['FrootboxDe' => [
    'hubUrl' => 'http://127.0.0.1:8000',
]]],
```

Weitere Schlüssel: `siteUrl` (Adresse der Website, falls sie hinter einem Proxy
anders aussieht) und `name` (Name der Website auf frootbox.de, sonst der Host).

## Adressen

Alle unter `/.well-known/frootbox/`, sie laufen am globalen Passwort-Schutz vorbei:

| Adresse | Zweck |
|---|---|
| `POST verify` | frootbox.de prüft, dass die Website ihren Schlüssel hat |
| `POST disconnect` | frootbox.de trennt |
| `POST keys` | frootbox.de tauscht seine Schlüssel |
| `GET status` | Statusdokument, nur mit dem Bearer-Token der Verbindung |

`verify`, `disconnect` und `keys` sind nach Standard Webhooks (`v1a`, Ed25519)
signiert, höchstens fünf Minuten alt, und jede Nachricht zählt einmal.

## Was frootbox.de sieht

Das Statusdokument Version 1 wie in Frootbox 2: Versionen von Frootbox, PHP,
Datenbank und Extensions, letztes Backup (neueste Datei in
`files/backup/database/`, Frootbox 1 sichert bei der Anmeldung im Backend), freier
Platz und letzte Änderung an Seiten und Inhalten. Zeitsteuerung, Warteschlange,
Entwürfe und Kommentare kennt Frootbox 1 nicht, sie gehen als `null` hinaus und
erscheinen auf frootbox.de als „nicht verfügbar“. Keine Namen, E-Mail-Adressen,
Inhalte oder Adressen von Seiten. Die App zeigt genau dieses Dokument.

## Schlüssel und Verbindung

Schlüsselpaar, Schlüssel von frootbox.de und Verbindung liegen in
`frootbox-de.key.php` neben `localconfig.php`, nicht in der Datenbank. Die Datei
beginnt mit `<?php exit; ?>`, im Browser zeigt sie nichts, und sie steht in
`.gitignore`. Sie gehört zur Installation, nicht zum Inhalt: Eine Kopie der
Website auf einem anderen Server darf sie nicht mitnehmen, sonst teilen sich zwei
Installationen einen Schlüssel. Dort in der App **Schlüssel vergessen** oder die
Datei löschen. Zum Umzug auf einen neuen Server gehört sie dagegen dazu, wie
`localconfig.php`.

Vom Token für den Status speichert die Website nur den Hash. Status abschalten
oder Trennen macht ihn sofort ungültig, auch wenn frootbox.de gerade nicht
erreichbar ist.
