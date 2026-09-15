# PHP-Performance-Log

Das Logging ist standardmäßig deaktiviert. Es benötigt keine Änderung der nginx-/Apache-Konfiguration und keine Datenbankmigration.

## Auf dem Server aktivieren

1. `index.php` und `cms/classes/PerformanceLogger.php` deployen. Die lokale `index.php` enthält weitere Änderungen; beim Deployment den vollständigen Diff prüfen.
2. Ein privates Verzeichnis außerhalb des DocumentRoot anlegen, z. B. `/var/www/vhosts/euregio-klinik.de/private/performance`. Der PHP-FPM-Benutzer der Domain benötigt Schreibrechte. Verzeichnisrechte: möglichst `0700`.
3. Folgenden Eintrag in das vorhandene Konfigurationsarray der `localconfig.php` aufnehmen:

```php
'performanceLogging' => [
    'enabled' => true,
    'directory' => '/var/www/vhosts/euregio-klinik.de/private/performance',
    'retentionDays' => 14,
    'maxDailyBytes' => 50 * 1024 * 1024,
    'release' => 'baseline-2026-09-14',
],
```

`release` bei jeder Vergleichsversion ändern. Zum Abschalten `enabled` auf `false` setzen.

Eine öffentliche Seite abrufen und die neue Zeile kontrollieren:

```bash
tail -n 1 /var/www/vhosts/euregio-klinik.de/private/performance/performance-*.jsonl
```

Die Antwort enthält bei aktivem Logger `X-Request-ID`. Diese ID steht auch im JSON. nginx protokolliert diese ID erst nach einer gesonderten Konfiguration.

## Felder und Interpretation

- `timestamp`: Ende der Messung in UTC; Dateinamen ebenfalls nach UTC-Tag.
- `request_id`: zufällige ID aus dem CMS, nicht ungeprüft aus einem Client-Header übernommen.
- `path`, `method`, `status`: angefragter Pfad ohne Query-String, Methode und HTTP-Status zum Messzeitpunkt.
- `page_id`, `language`: sofern die Anfrage bereits einer Seite zugeordnet wurde.
- `release`: frei wählbarer Vergleichsstand.
- `cache`: aktuell `disabled` (HTML-Cache, nicht Twig-/Bildcache); für spätere Cache-Implementierung vorgesehen.
- `editor`, `logged_in`: boolesche Merkmale, keine Benutzer-IDs.
- `total_ms`: verstrichene Zeit ab Eintritt in `index.php` bis zum Shutdown-Logger, vor dessen Dateischreibvorgang. Enthält PHP-Wartezeiten, ist keine CPU-Zeit und keine Browser-Ladezeit.
- `phases_ms`: aufeinanderfolgende Phasen, einschließlich noch laufender Phase bei einem Abbruch.
- `slowest_elements`: bis zu 20 langsamste abgeschlossene Inhaltselemente mit ID, Klasse, Aktion und Dauer; umfasst Plugin-Aktion und Rendering zusammen. Diese Zeiten sind Teil von `content_render`, nicht zusätzlich zu addieren.
- `peak_memory_bytes`: maximal von PHP allokierter Speicher.
- `fatal`: erkannter fataler PHP-Fehler; auch separat vom Status auswerten.

Phasen: `bootstrap`, `routing`, `page_setup`, `layout_setup`, `content_queries`, `content_render`, `layout_render`, `html_processing`, `post_processing`, `output`.

`content_queries` misst die expliziten Inhaltsabfragen im Frontcontroller. Datenbankzugriffe innerhalb eines Plugins gehören zu dessen Render-Zeit. Es erfolgt kein SQL-Logging. Bei statischen Sonderrouten, Redirects und Fehlerseiten sind nicht alle Phasen vorhanden; diese können in `routing` verbleiben. Vorzeitiges `exit` schreibt den Request-Datensatz, beendet aber nicht zwingend das gerade laufende Element-Timing.

Der Logger wird nach `Front::init()` eingerichtet; ein Fehler davor ist nicht erfasst. Ein hart beendeter Prozess oder vollständiger Speichermangel kann ebenfalls einen Eintrag verhindern. nginx-Timings und Statuscodes sind die spätere Ergänzung dafür.

## Rotation und Betriebsverhalten

- Eine Datei `performance-YYYY-MM-DD.jsonl` pro UTC-Tag, neue Dateien erhalten `0600`.
- Beim ersten Schreiben einer Tagesdatei werden passende ältere Tagesdateien im selben Verzeichnis gemäß `retentionDays` entfernt. Ohne neue Requests findet keine Bereinigung statt.
- Das Tageslimit begrenzt den Speicherverbrauch. Danach werden bis zum nächsten UTC-Tag keine weiteren Zeilen geschrieben. Diese Zeiträume dürfen nicht als vollständige Stichprobe ausgewertet werden.
- Eine nicht blockierende Schreibsperre verhindert, dass parallele Logs die Seite verzögern; bei belegter Sperre kann ein Eintrag entfallen. Das Log ist somit kein revisionssicherer Request-Zähler.
- Ein ungültiges Verzeichnis deaktiviert das Logging und meldet dies im PHP-Fehlerlog. Es wird nicht automatisch ein öffentliches Ersatzverzeichnis verwendet.
- Query-Strings, Cookies, Request-Bodies, IP-Adressen, Referrer und User-Agents werden nicht gespeichert. URL-Pfade können dennoch vertrauliche Informationen enthalten; Zugriff und Aufbewahrung entsprechend begrenzen.

## Vorher/nachher vergleichen

Nach `release`, Pfad/Seiten-ID, Methode, Status und Editor-/Login-Zustand gruppieren. Median, p95, Fehlerquote und Anzahl der gemessenen Anfragen vergleichen. Die Stichprobengröße und ähnliche Last berücksichtigen. HTML-Seiten und Thumbnail-/Sonderrouten getrennt auswerten. Zunächst eine Baseline sammeln und dann nur eine Optimierung gleichzeitig aktivieren.

## Tests

```bash
php tests/performance_logger_test.php
php -l index.php
php -l cms/classes/PerformanceLogger.php
```

Die isolierten Tests benötigen weder Datenbank noch Webserver. Sie prüfen normales Logging, deaktiviertes Logging, Schutz des DocumentRoot, Tageslimit, vorzeitiges Exit, fatale Fehler, verlorenen Verzeichniszugriff, Datenschutzfilter, einfache Aufbewahrung und genau einen Eintrag pro Request.
