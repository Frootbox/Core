// Run with: node tests/ai_image_lightbox_test.js
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const listeners = new Map();
const document = { readyState: 'complete' };
let lastLabel = '';
const window = {
    jQuery(value) {
        if (value === document) return { on(events, callback) {
            events.split(' ').forEach(event => listeners.set(event, callback));
        } };
        assert.match(value, /data-frootbox-ai-label>(KI-generiert|Mit KI bearbeitet)/);
        lastLabel = value;
        return { appendTo(content) { content.labels++; } };
    }
};
const source = fs.readFileSync(__dirname + '/../cms/classes/View/resources/ai-image-label.js', 'utf8');
const context = { window, document };
vm.runInNewContext(source, context);
assert.deepEqual([...listeners.keys()], ['afterLoad.fb', 'beforeShow.fb']);
const content = { labels: 0, children() { return { remove: () => { this.labels = 0; } }; } };
let marked = true;
let type = '1';
const slide = { $content: content, opts: { $orig: { attr() { return marked ? type : '0'; } } } };
for (const callback of listeners.values()) {
    callback(null, {}, slide);
    assert.equal(content.labels, 1, 'Repeated lightbox events must not duplicate labels');
}
type = 'edited';
listeners.get('beforeShow.fb')(null, {}, slide);
assert.equal(content.labels, 1);
assert.match(lastLabel, /Mit KI bearbeitet/);
marked = false;
listeners.get('beforeShow.fb')(null, {}, slide);
assert.equal(content.labels, 0, 'Unmarked slides must not retain an earlier label');
listeners.get('afterLoad.fb')(null, {}, { $content: content, opts: {} });
assert.equal(content.labels, 0, 'Programmatic slides without an origin must be safe');
vm.runInNewContext(source, context);
assert.equal(listeners.size, 2);
console.log('AI lightbox: disclosure, repeated events, clearing and missing origins PASS');
