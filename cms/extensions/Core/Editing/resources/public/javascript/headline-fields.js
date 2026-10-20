(function () {
    'use strict';

    // ContentEdit owns selection, text modelling and formatting. This adapter
    // supplies a small modal UI without starting the page-wide EditorApp.
    function clean(html, trim) {
        var template = document.createElement('template');
        template.innerHTML = html;

        function children(parent) {
            var result = '';
            Array.from(parent.childNodes).forEach(function (node) {
                if (node.nodeType === 3) {
                    var text = node.textContent.replace(/\r\n?/g, '\n');
                    if (node.previousSibling && node.previousSibling.nodeName === 'BR') {
                        text = text.replace(/^\n/, '');
                    }
                    result += HTMLString.String.encode(text);
                    return;
                }
                if (node.nodeType !== 1) { return; }
                var tag = node.tagName.toLowerCase();
                if (/^(script|style|head|title|iframe|object|embed|svg|math|template|noscript|img|video|audio)$/.test(tag)) { return; }
                if (tag === 'br') { result += '\n'; return; }
                var content = children(node);
                tag = { b: 'strong', i: 'em' }[tag] || tag;
                if (tag === 'strong' || tag === 'em') {
                    if (content) { result += '<' + tag + '>' + content + '</' + tag + '>'; }
                } else if (/^(p|div|h[1-6]|li|ul|ol|blockquote|pre|section|article|header|footer|table|tr|td|th)$/.test(tag)) {
                    result = result.trimEnd() + (result ? '\n' : '') + content.trim() + '\n';
                } else {
                    result += content;
                }
            });
            return result;
        }

        var result = children(template.content);
        return (trim === false ? result : result.trim()).replace(/\n/g, '<br>');
    }

    // Keep the existing page editor's b/i tools unchanged.
    var bold = Object.create(ContentTools.Tools.Bold);
    bold.tagName = 'strong';
    var italic = Object.create(ContentTools.Tools.Italic);
    italic.tagName = 'em';
    var tools = { bold: bold, italic: italic };

    class TitleText extends ContentEdit.Text {
        _onKeyDown(event) {
            if (event.isComposing) { return; }
            var key = event.key.toLowerCase();
            if ((event.ctrlKey || event.metaKey) && !event.altKey) {
                if (key === 'b' || key === 'i') {
                    event.preventDefault();
                    this.field.format(key === 'b' ? 'bold' : 'italic');
                } else if (key === 'z' || key === 'y') {
                    event.preventDefault();
                    this.field.owner.restore(key === 'y' || event.shiftKey);
                } else if (key === 'u') {
                    event.preventDefault();
                }
            } else if (event.key === 'Enter') {
                event.preventDefault();
                this.field.insert('<br>');
            }
            // Native cursor/Tab movement stays inside the modal's focus order.
            event.stopPropagation();
        }

        _onPaste(event) {
            event.preventDefault();
            event.stopPropagation();
            if (!event.clipboardData) { return; }
            this.field.insert(clean(event.clipboardData.getData('text/html') ||
                HTMLString.String.encode(event.clipboardData.getData('text/plain'))));
        }

        blur() {
            super.blur();
            // A field must remain focusable by Tab after ContentEdit blurs it.
            if (this.isMounted()) { this.domElement().setAttribute('contenteditable', 'true'); }
        }
    }
    ContentEdit.TagNames.get().register(TitleText, 'fbx-headline-field');

    window.FrootboxHeadlineFields = {
        create: function (form) {
            var fields = [];
            var regions = {};
            var history;
            var destroyed = false;
            var owner = {
                sync: function () {
                    fields.forEach(function (field) {
                        field.sync();
                        field.textarea.value = field.element.content.html();
                    });
                },
                record: function () {
                    if (!history) { return; }
                    var snapshot = history.snapshot();
                    if (fields.some(function (field) {
                        return field.element.content.html() !== snapshot.regions[field.textarea.name];
                    })) {
                        // The bundled History has no public immediate-snapshot method.
                        // Record synchronously so quick format/undo also works.
                        history._store();
                    }
                },
                restore: function (redo) {
                    owner.sync();
                    owner.record();
                    if (redo ? !history.canRedo() : !history.canUndo()) { return; }
                    var snapshot = redo ? history.redo() : history.undo();
                    fields.forEach(function (field) {
                        field.element.content = new HTMLString.String(snapshot.regions[field.textarea.name]);
                        field.element.updateInnerHTML();
                    });
                    history.restoreSelection(snapshot);
                    updateButtons();
                },
                focus: function () {
                    if (fields.length) {
                        fields[0].element.focus();
                        fields[0].element.domElement().focus();
                    }
                },
                destroy: function () {
                    if (destroyed) { return; }
                    destroyed = true;
                    document.removeEventListener('selectionchange', updateButtons);
                    fields.forEach(function (field) {
                        field.element.blur();
                        field.element.unmount();
                    });
                }
            };

            form.querySelectorAll('textarea[data-headline-field]').forEach(function (textarea) {
                var wrapper = document.createElement('div');
                wrapper.className = 'fbx-headline-field';
                var toolbar = document.createElement('div');
                toolbar.className = 'fbx-headline-toolbar';
                toolbar.setAttribute('role', 'group');
                toolbar.setAttribute('aria-label', textarea.placeholder + ' formatieren');
                wrapper.appendChild(toolbar);
                var input = document.createElement('p');
                input.id = textarea.id + '-editor';
                input.className = 'form-control fbx-headline-input';
                input.setAttribute('data-ce-tag', 'fbx-headline-field');
                input.setAttribute('contenteditable', 'true');
                input.setAttribute('role', 'textbox');
                input.setAttribute('aria-label', textarea.placeholder);
                input.setAttribute('aria-multiline', 'true');
                input.setAttribute('data-placeholder', textarea.placeholder);
                input.innerHTML = clean(textarea.value);
                wrapper.appendChild(input);
                textarea.after(wrapper);
                textarea.hidden = true;
                var label = form.querySelector('label[for="' + textarea.id + '"]');
                if (label) { label.htmlFor = input.id; }

                var fixture = new ContentEdit.Fixture(input);
                var element = fixture.children[0];
                // History stores the inline contents, never a p/h wrapper.
                fixture.html = function () { return element.content.html(); };
                regions[textarea.name] = fixture;
                var field = {
                    textarea: textarea,
                    element: element,
                    owner: owner,
                    buttons: [],
                    selection: new ContentSelect.Range(0, 0),
                    sync: function () {
                        var selection = element.selection();
                        var html = clean(element.domElement().innerHTML, false);
                        element.content = new HTMLString.String(html);
                        if (html !== element.domElement().innerHTML) {
                            element.updateInnerHTML();
                            if (document.activeElement === element.domElement()) { element.selection(selection); }
                        }
                    },
                    format: function (name) {
                        var current = window.getSelection();
                        if (current && element.domElement().contains(current.anchorNode) && element.domElement().contains(current.focusNode)) {
                            field.selection = element.selection();
                        }
                        field.sync();
                        element.focus();
                        element.selection(field.selection);
                        var tool = tools[name];
                        if (!tool.canApply(element, field.selection)) { return; }
                        owner.record();
                        tool.apply(element, field.selection, function () { owner.record(); updateButtons(); });
                    },
                    insert: function (html) {
                        field.sync();
                        owner.record();
                        var range = element.selection().get();
                        // A final br needs a second, empty line box so browsers
                        // can place the caret on the new line (as in ContentEdit).
                        var lineBreak = html === '<br>';
                        var inserted = new HTMLString.String(lineBreak && range[1] === element.content.length() ? '<br><br>' : html);
                        element.content = element.content.substring(0, range[0]).concat(inserted, element.content.substring(range[1]));
                        element.updateInnerHTML();
                        element.taint();
                        var offset = range[0] + (lineBreak ? 1 : inserted.length());
                        element.selection(new ContentSelect.Range(offset, offset));
                        owner.record();
                        updateButtons();
                    }
                };
                element.field = field;
                fields.push(field);

                ['bold', 'italic'].forEach(function (name) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm btn-light';
                    button.innerHTML = name === 'bold' ? '<strong>B</strong>' : '<em>I</em>';
                    button.title = name === 'bold' ? 'Fett (Strg/Cmd+B)' : 'Kursiv (Strg/Cmd+I)';
                    button.setAttribute('aria-label', name === 'bold' ? 'Fett' : 'Kursiv');
                    button.setAttribute('aria-pressed', 'false');
                    button.addEventListener('mousedown', function (event) { event.preventDefault(); });
                    button.addEventListener('click', function () { field.format(name); });
                    toolbar.appendChild(button);
                    field.buttons.push({ node: button, tool: tools[name] });
                });

                element.domElement().addEventListener('focus', function () { element.focus(true); updateButtons(); });
                element.domElement().addEventListener('input', function (event) {
                    if (event.isComposing) { return; }
                    field.sync(); owner.record(); updateButtons();
                });
                element.domElement().addEventListener('compositionend', function () { field.sync(); owner.record(); updateButtons(); });
                element.domElement().addEventListener('beforeinput', function (event) {
                    if (event.inputType === 'formatBold' || event.inputType === 'formatItalic') {
                        event.preventDefault();
                        field.format(event.inputType === 'formatBold' ? 'bold' : 'italic');
                    } else if (/^format/.test(event.inputType)) {
                        event.preventDefault();
                    } else if (event.inputType === 'insertParagraph' || event.inputType === 'insertLineBreak') {
                        event.preventDefault(); field.insert('<br>');
                    } else if (event.inputType === 'historyUndo' || event.inputType === 'historyRedo') {
                        event.preventDefault(); owner.restore(event.inputType === 'historyRedo');
                    }
                });
            });

            function updateButtons() {
                var selection = window.getSelection();
                fields.forEach(function (field) {
                    var node = field.element.domElement();
                    if (selection && node.contains(selection.anchorNode) && node.contains(selection.focusNode)) {
                        field.selection = field.element.selection();
                    }
                    field.buttons.forEach(function (button) {
                        button.node.disabled = field.selection.isCollapsed();
                        button.node.setAttribute('aria-pressed', String(button.tool.isApplied(field.element, field.selection)));
                    });
                });
            }

            history = new ContentTools.History(regions);
            document.addEventListener('selectionchange', updateButtons);
            updateButtons();
            return owner;
        }
    };
}());
