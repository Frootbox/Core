# CSS für die Frontend-Bearbeitungsmodals

Die Dialoge für Elemente (`#fbxEditorGenericModal`) und Seiteneinstellungen
(`#frootboxOnPageConfigModal`) tragen die gemeinsame Klasse
`.frootbox-editor-modal`. Sie bleibt beim Austausch der Inhalte per AJAX erhalten.

Die zentralen Styles stehen in
`cms/extensions/Core/Editing/resources/public/css/editor-modal.less` und werden
durch das Editor-Partial eingebunden. Trotz der Dateiendung wird die Datei wie
die übrigen Frontend-Styles mit SCSS kompiliert.

## Gemeinsamer CSS-Bereich

Alle Modal-Regeln werden unter diesem Selektor angelegt:

```css
.frootbox-editor-modal:is(#fbxEditorGenericModal, #frootboxOnPageConfigModal) {
  /* Ausschließlich die Frontend-Bearbeitungsmodals. */
}
```

Die Kombination aus ID und Klasse hat die Spezifität `(1, 1, 0)`; Selektoren für
Komponenten kommen zusätzlich hinzu. So überstimmen die Regeln übliche
Website-Regeln wie `.btn`, `body.dark td` oder `#page .btn`, auch wenn diese später
geladen werden. Textfarben werden zusätzlich auf Textelementen gesetzt, weil
eine nur am Modal gesetzte Farbe gegenüber direkt auf `td`, `label` oder `p`
gesetzten Website-Farben verliert.

Keine allgemeinen Regeln für `body`, `.modal`, `.btn` oder `table` ergänzen.
Dadurch bleiben Website-Inhalte und deren eigene Modals unverändert.
Icon-Schriften, Vorschaubilder und die Bootstrap-Logik für Öffnen, Schließen und
Tabs werden nicht zurückgesetzt.

## Farben anpassen

In einem nach den Editor-Styles geladenen Stylesheet lassen sich die Grundfarben
für beide Dialoge anpassen:

```css
.frootbox-editor-modal:is(#fbxEditorGenericModal, #frootboxOnPageConfigModal) {
  --fbx-editor-color: #333;
  --fbx-editor-background: #fff;
  --fbx-editor-border: #dee2e6;
  --fbx-editor-muted: #6c757d;
  --fbx-editor-primary: #2980b9;
  --fbx-editor-primary-hover: #20638f;
}
```

Für einzelne Komponenten denselben Bereich verwenden:

```css
.frootbox-editor-modal:is(#fbxEditorGenericModal, #frootboxOnPageConfigModal) .modal-title {
  font-size: 22px;
}
```

Dies ist eine Abgrenzung über die CSS-Kaskade, keine Shadow-DOM-Isolation.
Website-Deklarationen mit `!important`, Inline-Styles oder noch höherer
Spezifität können weiterhin gewinnen. Solche Konflikte gezielt innerhalb des
Modal-Bereichs überschreiben, gegebenenfalls mit `!important` auf der betroffenen
Eigenschaft. Ein pauschales `all: initial` oder universelle Font-Resets würden
unter anderem Icons, Vorschauelemente und Formularzustände beschädigen.
