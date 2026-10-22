// Loaded only by the editing views; existing layouts must explicitly opt in.
const selector = 'picture[data-editable][data-uid][data-inline-upload], [data-type="Images/Background"][data-uid][data-inline-upload]';
const records = new Map();
let scheduled = false;

function endpoint(path, parameters) {
    const url = new URL(serverpath + path, location.href);
    new URLSearchParams(sessionQuery).forEach((value, key) => url.searchParams.set(key, value));
    Object.entries(parameters).forEach(([key, value]) => url.searchParams.set(key, value));
    return url.href;
}

function eligible(element) {
    return element.matches(selector) && !element.hasAttribute('data-noediting');
}

function setMessage(record, message) {
    if (record.status.textContent !== message) record.status.textContent = message;
}

function position() {
    scheduled = false;
    records.forEach((record, element) => {
        if (!element.isConnected || !eligible(element)) {
            resizeObserver.unobserve(element);
            record.overlay.remove();
            record.styles.remove();
            records.delete(element);
            return;
        }
        const rect = element.getBoundingClientRect();
        const visible = element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden';
        record.overlay.hidden = !visible;
        Object.assign(record.overlay.style, {
            left: rect.left + 'px', top: rect.top + 'px',
            width: Math.max(120, rect.width) + 'px', height: Math.max(80, rect.height) + 'px'
        });
        const empty = element.tagName === 'PICTURE'
            ? !element.querySelector('img[data-image-edited]')
            : getComputedStyle(element).backgroundImage === 'none';
        record.overlay.classList.toggle('fbx-inline-empty', empty);
        const label = empty ? 'Bild hierher ziehen oder auswählen' : 'Bild ersetzen';
        if (record.button.textContent !== label) record.button.textContent = label;
    });
}

function schedule() {
    if (!scheduled) {
        scheduled = true;
        requestAnimationFrame(position);
    }
}

function scan() {
    document.querySelectorAll(selector).forEach(element => {
        if (!eligible(element) || records.has(element)) return;
        const overlay = document.createElement('div');
        overlay.className = 'fbx-inline-upload';
        const button = document.createElement('button');
        button.type = 'button';
        const status = document.createElement('span');
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/gif,image/webp,image/avif';
        input.hidden = true;
        const styles = document.createElement('div');
        const record = { element, overlay, button, status, input, styles, busy: false, depth: 0 };
        overlay.append(button, status, input);
        records.set(element, record);
        document.body.append(overlay, styles);
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            if (!record.busy) input.click();
        });
        input.addEventListener('change', () => {
            upload(record, input.files);
            input.value = '';
        });
        resizeObserver.observe(element);
    });
    schedule();
}

async function refresh(record) {
    const element = record.element;
    const clone = element.cloneNode(true);
    // Preserve the template placeholder instead of turning the previous upload into it.
    clone.querySelectorAll('img[data-default]').forEach(img => img.setAttribute('src', img.getAttribute('data-default')));
    const background = element.tagName !== 'PICTURE';
    if (background) {
        [...clone.classList].filter(name => /^bg-file-\d+$/.test(name) || name === 'no-background').forEach(name => clone.classList.remove(name));
        clone.style.removeProperty('background-image');
        clone.removeAttribute('data-copyright');
        clone.removeAttribute('aria-label');
    }
    const response = await fetch(endpoint('cms/admin/Editor/callEditable', {
        editable: 'Frootbox/Ext/Core/Images/Editables/Image', method: 'ajaxInlineRefresh'
    }), {
        method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ type: background ? 'background' : 'image', snippet: clone.outerHTML })
    });
    if (!response.ok) throw new Error('Das Bild wurde hochgeladen, konnte aber nicht angezeigt werden. Bitte Seite neu laden.');
    const result = await response.json();
    const parsed = new DOMParser().parseFromString(result.html, 'text/html');
    const replacement = parsed.body.querySelector(selector);
    if (!replacement) throw new Error('Das Bild wurde hochgeladen. Bitte Seite neu laden.');
    record.styles.replaceChildren(...parsed.head.querySelectorAll('style'));
    resizeObserver.unobserve(element);
    element.replaceWith(replacement);
    records.delete(element);
    record.element = replacement;
    records.set(replacement, record);
    resizeObserver.observe(replacement);
    window.dispatchEvent(new Event('resize'));
}

function upload(record, files) {
    if (record.busy) return;
    if (files.length !== 1) {
        setMessage(record, 'Bitte genau ein Bild auswählen.');
        return;
    }
    const file = files[0];
    if (!/^image\/(jpeg|png|gif|webp|avif)$/.test(file.type)) {
        setMessage(record, 'Bitte ein JPG-, PNG-, GIF-, WebP- oder AVIF-Bild auswählen.');
        return;
    }
    record.busy = true;
    record.button.disabled = true;
    record.overlay.classList.add('fbx-inline-busy');
    setMessage(record, 'Bild wird hochgeladen …');
    const element = record.element;
    // Match the background variant currently shown by the existing renderer.
    const desktop = element.tagName !== 'PICTURE' && matchMedia('(min-width: 768px)').matches && element.getAttribute('data-focus-file-desktop');
    const uid = element.getAttribute('data-uid') + (desktop ? '-desktop' : '');
    const request = new XMLHttpRequest();
    request.open('POST', endpoint('static/Ext/Core/FileManager/Editor/upload', { uid, inlineImage: '1' }));
    request.setRequestHeader('Accept', 'application/json');
    request.timeout = 120000;
    const finish = () => {
        record.busy = false;
        record.button.disabled = false;
        record.overlay.classList.remove('fbx-inline-busy');
        schedule();
    };
    request.upload.onprogress = event => {
        if (event.lengthComputable) setMessage(record, 'Upload: ' + Math.round(100 * event.loaded / event.total) + ' %');
    };
    request.onload = async () => {
        try {
            let result;
            try { result = JSON.parse(request.responseText); } catch (_) { throw new Error('Unerwartete Antwort. Bitte Anmeldung prüfen und erneut versuchen.'); }
            if (request.status < 200 || request.status >= 300 || result.error) {
                throw new Error(result.error || 'Upload fehlgeschlagen. Bitte erneut versuchen.');
            }
            await refresh(record);
            setMessage(record, 'Bild gespeichert.');
        } catch (error) {
            setMessage(record, error.message);
        } finally { finish(); }
    };
    request.onerror = request.ontimeout = request.onabort = () => {
        setMessage(record, 'Upload unterbrochen. Bitte erneut versuchen.');
        finish();
    };
    const body = new FormData();
    body.append('file', file);
    request.send(body);
}

function targetRecord(event) {
    if (!(event.target instanceof Element)) return null;
    const target = event.target.closest(selector);
    if (target && records.has(target)) return records.get(target);
    const overlay = event.target.closest('.fbx-inline-upload');
    return [...records.values()].find(record => record.overlay === overlay);
}

const resizeObserver = new ResizeObserver(schedule);
function start() {
    const style = document.createElement('style');
    style.textContent = `
        .fbx-inline-upload { position: fixed; z-index: 1040; pointer-events: none; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; padding: 8px; gap: 4px; font: 13px/1.4 sans-serif; }
        .fbx-inline-upload[hidden] { display: none; }
        .fbx-inline-upload button { pointer-events: auto; cursor: pointer; border: 1px solid #fff; border-radius: 4px; padding: 7px 10px; background: #243447; color: #fff; font: inherit; }
        .fbx-inline-upload button:focus-visible { outline: 3px solid #37a7ff; outline-offset: 2px; }
        .fbx-inline-upload button:disabled { cursor: wait; }
        .fbx-inline-upload span:not(:empty) { padding: 4px 8px; border-radius: 4px; background: #fff; color: #243447; text-align: center; }
        .fbx-inline-empty, .fbx-inline-drag { justify-content: center; border: 2px dashed #55758c; background: rgba(240,246,250,.85); }
        .fbx-inline-drag { border-color: #0074bf; background: rgba(210,236,255,.9); }
    `;
    document.head.append(style);
    scan();
    new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
    window.addEventListener('scroll', schedule, true);
    window.addEventListener('resize', schedule);
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(type => {
        document.addEventListener(type, event => {
            if (!event.dataTransfer || !Array.from(event.dataTransfer.types).includes('Files')) return;
            const record = targetRecord(event);
            if (!record) return;
            event.preventDefault();
            event.stopPropagation();
            if (type === 'dragenter') record.depth++;
            if (type === 'dragover') event.dataTransfer.dropEffect = record.busy ? 'none' : 'copy';
            if (type === 'dragleave') record.depth = Math.max(0, record.depth - 1);
            if (type === 'drop') { record.depth = 0; upload(record, event.dataTransfer.files); }
            record.overlay.classList.toggle('fbx-inline-drag', record.depth > 0);
        }, true);
    });
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
else start();
