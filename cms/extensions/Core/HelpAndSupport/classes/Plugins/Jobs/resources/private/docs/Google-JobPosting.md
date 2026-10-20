# JobPosting-JSON-LD in der localconfig aktivieren

Das Jobs-Plugin kann auf den Detailseiten der Layouts `ShowJob01` und `ShowJob02`
strukturierte Daten vom Typ `JobPosting` ausgeben. Die Ausgabe wird über die
`localconfig.php` im Frootbox-Hauptverzeichnis aktiviert und ist ohne Opt-in
deaktiviert.

## Konfiguration

Den folgenden Abschnitt in das bestehende Konfigurationsarray der
`localconfig.php` integrieren. Bereits vorhandene Einträge unter `Ext`, `Core`,
`HelpAndSupport` oder `Jobs` beibehalten und um die Schlüssel ergänzen. Keine
zweiten gleichnamigen Array-Schlüssel oder eine zusätzliche `return`-Anweisung
anlegen.

```php
<?php

return [
    // Weitere vorhandene Konfiguration ...
    'Ext' => [
        'Core' => [
            'HelpAndSupport' => [
                'Jobs' => [
                    'Google' => [
                        'StructuredData' => true,
                        'HiringOrganization' => [
                            'Name' => 'Beispiel GmbH',
                            // Optional: Standard ist die aktuelle Website-Basis.
                            // 'Url' => 'https://www.beispiel.de/',
                            'Logo' => 'assets/logo.png',
                        ],
                    ],
                ],
            ],
        ],
    ],
];
```

Die Beispielwerte durch die tatsächlichen Arbeitgeberdaten und den vorhandenen
Logo-Pfad ersetzen.

| Schlüssel unter `Ext.Core.HelpAndSupport.Jobs.Google` | Bedeutung |
| --- | --- |
| `StructuredData` | Mit dem PHP-Boolean `true` aktivieren. `false` oder ein fehlender Schlüssel deaktiviert die Ausgabe. Keine Zeichenketten wie `'false'` verwenden. |
| `HiringOrganization.Name` | Name des einstellenden Unternehmens; wird als `hiringOrganization.name` ausgegeben. |
| `HiringOrganization.Url` | Optional: Vollständige Unternehmens-URL einschließlich Protokoll; wird als `hiringOrganization.sameAs` ausgegeben. Bei fehlendem oder leerem Wert wird `SERVER_PATH_PROTOCOL` verwendet. |
| `HiringOrganization.Logo` | Optionaler Pfad zu einem öffentlich erreichbaren Logo, relativ zur Website-Basis, ohne führenden Schrägstrich. |

Bei aktiviertem Opt-in muss der Abschnitt `HiringOrganization` vorhanden sein.
`Name` mit dem tatsächlichen Unternehmensnamen ausfüllen; bei fehlendem Namen
verwendet der Baustein den Platzhalter `Unknown`.

`Url` kann entfallen, wenn die aktuelle Website auch die Unternehmenswebsite
ist. Der Standardwert `SERVER_PATH_PROTOCOL` enthält Protokoll, Host und den
Basispfad der Installation, beispielsweise `https://www.beispiel.de/`. Eine
ausdrücklich konfigurierte URL hat Vorrang.

Der Logo-Pfad wird an `SERVER_PATH_PROTOCOL` angehängt. Deshalb hier keine
vollständige URL und keinen `EXT:`-Verweis eintragen. Bei einer Website-Basis von
`https://www.beispiel.de/` wird aus `assets/logo.png` die URL
`https://www.beispiel.de/assets/logo.png`. Ohne Logo den Schlüssel `Logo`
weglassen.

## Verwendete Jobdaten

Die Aktivierung gilt für alle Job-Detailseiten der Installation, deren Layout
das Opt-in auswertet. Die Core-Layouts `ShowJob01` und `ShowJob02` unterstützen
es. Kundenspezifische Overrides müssen die Einbindung ebenfalls enthalten; der
Override in `Holl/Robin2026` wurde entsprechend ergänzt.

- **Titel:** Jobtitel und Untertitel. Vorhandene HTML-Entities werden dekodiert;
  Zeilenumbrüche und mehrfacher Leerraum werden zu einfachen Leerzeichen.
- **Beschreibung in `ShowJob01`:** Teaser, optionaler Text über den Spalten,
  aktivierte Aufgaben-, Profil- und Benefits-Texte, aktivierte globale Texte
  sowie der abschließende Text unter den Spalten. Leere Textfelder werden
  übersprungen.
- **Beschreibung in `ShowJob02`:** Das Textfeld `text` des Jobs.
- **Veröffentlichungsdatum:** Das Datum des Jobs als `datePosted`.
- **Arbeitsort:** Die beim Job verknüpfte Standortadresse. Ohne zugeordneten
  Standort wird kein `jobLocation` ausgegeben. Das Land ist derzeit fest auf
  `DE` gesetzt.

Die Stellenbeschreibung und die Standortdaten müssen im CMS gepflegt sein;
die Aktivierung allein ergänzt keine fehlenden Inhalte. `validThrough` wird
derzeit nicht ausgegeben, da keine tatsächliche Bewerbungsfrist hinterlegt wird.

## Ausgabe prüfen

1. Eine Job-Detailseite mit `ShowJob01` oder `ShowJob02` öffnen.
2. Im HTML-Quelltext nach `application/ld+json` suchen. Bei aktiviertem Opt-in
   sollte genau ein JSON-LD-Block mit `"@type": "JobPosting"` vorhanden sein.
3. Arbeitgeberdaten, Beschreibung, Veröffentlichungsdatum und Standort mit der
   sichtbaren Stellenanzeige abgleichen.
4. Die öffentliche URL im [Google Rich Results Test](https://search.google.com/test/rich-results)
   prüfen.

Zum Deaktivieren `StructuredData` auf `false` setzen oder den Schlüssel entfernen.

## JSON-Ausgabe und HTML-Entities

Die HTML-Verarbeitung und die Core-Editables verwenden
`Frootbox\View\HtmlPageCrawler`. Dieser schützt den Inhalt von `<script>` beim
Einlesen und beim Serialisieren vor Änderungen durch den DOM-Parser. Das gilt
für JavaScript, JSON-LD und andere Script-Typen. Attribute wie `src`, `type` und
`nonce` bleiben zugänglich; die Bündelung externer JavaScript-Dateien funktioniert
weiterhin. Eigene Erweiterungen sollten ebenfalls diesen Crawler verwenden.
Der Schutz betrifft die DOM-Verarbeitung, nicht eigene Textersetzungen in
Templates oder Erweiterungen.

Der gemeinsame JSON-LD-Baustein verwendet zusätzlich den Viewhelper
`Frootbox\View\Viewhelper\Json`. Dieser lässt Unicode-Zeichen als JSON-Escapes
stehen und bleibt dadurch auch mit anderen HTML-Serialisierern kompatibel.

Ein `ü` erscheint im Quelltext deshalb als `\u00fc`. Ein JSON-Parser liest daraus
wieder ein normales `ü`. Anders als `&uuml;` ist das eine JSON-konforme Kodierung.
Die HTML-Beschreibung behält ihre Tags und Entities; sie wird nicht pauschal
dekodiert.

Für weitere JSON-Blöcke in Twig kann derselbe Viewhelper verwendet werden:

```twig
{% set json = view.getViewhelper('Json') %}
<script type="application/ld+json">
    {{ json.encode(data) | raw }}
</script>
```

`data` ist dabei die zu kodierende Datenstruktur, kein bereits kodierter
JSON-String. `raw` wird nur auf das fertige Ergebnis des Viewhelpers angewendet.
Der Helper maskiert HTML-relevante Zeichen wie `<`, `>` und `&` und verhindert
dadurch auch, dass ein enthaltenes `</script>` den Script-Block vorzeitig beendet.
