/** Draft outline enhancement. Native links/forms remain usable without JavaScript. */
export function dropOrder(ids, source, target, after = false) {
    if (!ids.includes(source) || !ids.includes(target) || source === target || new Set(ids).size !== ids.length) return ids.slice();
    const next = ids.filter(id => id !== source);
    next.splice(next.indexOf(target) + Number(after), 0, source);
    return next;
}

export async function persistOrder(form, group, order, transport = fetch) {
    const fields = new URLSearchParams(new FormData(form));
    fields.set('task', 'curriculum.saveOrder');
    fields.set('format', 'json');
    fields.set('jform[kind]', group.dataset.sortKind);
    fields.set('jform[module_id]', group.dataset.moduleId);
    // One bounded JSON value avoids PHP max_input_vars truncating large sibling lists.
    fields.set('jform[order_json]', JSON.stringify(order));
    const response = await transport(form.action, {method: 'POST', body: fields, credentials: 'same-origin', headers: {Accept: 'application/json'}});
    const result = await response.json();
    if (!response.ok || result.success !== true || (!Number.isSafeInteger(result.data?.revision) || result.data.revision < 1)) throw new Error('ORDER_NOT_SAVED');
    return result.data.revision;
}

export function initOutline(root, reload = () => location.reload(), transport = fetch) {
    const form = root.querySelector('#jlms-reorder-form');
    if (!form || root.dataset.editorMode) return;
    // Reordering reloads record revisions; keep it out of a form with unsaved input.
    root.classList.add('jlms-sort-enabled');
    const status = root.querySelector('#jlms-order-status');
    let source = null, busy = false;
    const items = group => Array.from(group.children).filter(item => item.hasAttribute('data-item-id'));
    const fail = () => {
        status.textContent = status.dataset.error;
        const link = document.createElement('a');
        link.href = location.href;
        link.textContent = status.dataset.reload;
        link.className = 'ms-2';
        status.append(link);
    };
    // One normal POST form for keyboard commands; GET fallbacks expose a confirmation.
    root.addEventListener('click', event => {
        const link = event.target.closest('a[data-order-kind]');
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
        event.preventDefault();
        if (busy) return;
        form.elements.namedItem('task').value = `${link.dataset.orderKind}.reorder`;
        form.elements.namedItem('jform[id]').value = link.dataset.orderId;
        form.elements.namedItem('jform[direction]').value = link.dataset.direction;
        busy = true;
        form.submit();
    });
    root.addEventListener('dragstart', event => {
        const handle = event.target.closest('[data-drag-handle]');
        if (busy || !handle) { event.preventDefault(); return; }
        const item = handle.closest('[data-item-id]');
        source = {item, group: item.parentElement};
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', item.dataset.itemId);
        item.classList.add('jlms-dragging');
    });
    root.addEventListener('dragover', event => {
        const target = event.target.closest('[data-item-id]');
        if (source && !busy && target && target !== source.item && target.parentElement === source.group) {
            event.preventDefault(); event.dataTransfer.dropEffect = 'move';
        }
    });
    root.addEventListener('dragend', () => {
        source?.item.classList.remove('jlms-dragging'); source = null;
    });
    root.addEventListener('drop', async event => {
        const target = event.target.closest('[data-item-id]');
        if (!source || busy || !target || target === source.item || target.parentElement !== source.group) return;
        event.preventDefault();
        const {item, group} = source;
        const before = items(group), ids = before.map(row => row.dataset.itemId);
        const bounds = target.getBoundingClientRect();
        const order = dropOrder(ids, item.dataset.itemId, target.dataset.itemId, event.clientY > bounds.top + bounds.height / 2);
        if (order.every((id, index) => id === ids[index])) return;
        const byId = new Map(before.map(row => [row.dataset.itemId, row]));
        order.forEach(id => group.append(byId.get(id)));
        busy = true; root.setAttribute('aria-busy', 'true'); status.textContent = status.dataset.saving;
        try {
            await persistOrder(form, group, order, transport);
            // Reload all record revisions and server-rendered links, not just the version CAS.
            reload();
        } catch {
            before.forEach(row => group.append(row)); fail(); busy = false;
        } finally {
            root.removeAttribute('aria-busy'); item.classList.remove('jlms-dragging'); source = null;
        }
    });
}
if (typeof document !== 'undefined') {
    const root = document.getElementById('jlms-admin-curriculum');
    if (root) initOutline(root);
}
