# Auswahl von Layout-Blöcken

Die Einstellungen liegen in `localconfig.php` unter
`Ext.Core.System.Editables.Block`:

```php
'Ext' => [
    'Core' => [
        'System' => [
            'Editables' => [
                'Block' => [
                    'OnlyUsedBlocks' => true,
                    'AllowedCategories' => [],
                    'AllowedBlocks' => [
                        'DeinVendor/DeineExtension/DeinBlock',
                    ],
                ],
            ],
        ],
    ],
],
```

- `OnlyUsedBlocks`: Bei `true` werden nur bereits verwendete Blocktypen sowie
  die unten genannten Freigaben angeboten. Als verwendet gilt ein Blocktyp,
  sobald ein Datensatz mit derselben Kombination aus `vendorId`, `extensionId`
  und `blockId` in der Tabelle `blocks` existiert, unabhängig von Seite,
  Sprache oder Sichtbarkeit. Nach Löschen der letzten Instanz entfällt diese
  Freigabe.
- `AllowedCategories`: Gibt zusätzlich ganze Extensions im Format
  `Vendor/Extension` frei. Der Name bezeichnet keine Anzeigenkategorie.
- `AllowedBlocks`: Gibt zusätzlich einzelne Blocktypen im Format
  `Vendor/Extension/Block` frei. Die Schreibweise muss den IDs entsprechen.
- Blöcke aktiver Extensions vom Typ `Template` bleiben unabhängig von diesen
  Einstellungen verfügbar. Inaktive Extensions werden dadurch nicht aktiviert.
- Für `SuperAdmin` gelten die Auswahlfilter nicht.

Die Freigaben werden vereinigt: Bei `OnlyUsedBlocks = true` sind verwendete
Blöcke, aktive Template-Blöcke und explizite Freigaben verfügbar. Ohne zusätzliche
Freigaben beide Listen leer lassen. Bei `OnlyUsedBlocks = false` schränken
nichtleere Freigabelisten die Auswahl auf die genannten Extensions bzw. Blöcke
ein; aktive Template-Blöcke und die bisherige Liste verwendeter Blöcke unter
„Template“ bleiben erhalten. Sind beide Listen leer und ist `OnlyUsedBlocks`
deaktiviert, bleibt die Auswahl uneingeschränkt.

Diese Einstellungen steuern den Auswahldialog. Sie verändern keine vorhandenen
Inhalte und sind keine Zugriffsprüfung für direkte Erstellungsanfragen.
Bestehende Einschränkungen eines Blockbereichs (`data-restrict`) und die bisherige
Sonderbehandlung verwendeter Blöcke unter „Template“ bleiben unverändert.
