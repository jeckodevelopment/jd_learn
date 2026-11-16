import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const src = await readFile(new URL('../component/media/js/classroom-outline.js', import.meta.url), 'utf8');
const { initClassroomOutline } = await import('data:text/javascript;base64,' + Buffer.from(src).toString('base64'));

function makeFixture() {
  let width = 960;
  const handlers = {};
  const toggle = {
    hidden: true, dataset: { showLabel: 'Show curriculum', hideLabel: 'Hide curriculum' },
    attributes: { 'aria-expanded': 'true' }, textContent: 'Show curriculum',
    setAttribute(k, v) { this.attributes[k] = v; },
    getAttribute(k) { return this.attributes[k]; },
    addEventListener(k, f) { handlers[k] = f; }
  };
  const outline = { hidden: false };
  const classroom = { getBoundingClientRect: () => ({ width }) };
  const nodes = { '#jlms-classroom-outline-toggle': toggle, '#jlms-classroom-outline': outline, '#jlms-classroom': classroom };
  return { toggle, outline, classroom, handlers, documentRef: { querySelector: s => nodes[s] },
    setWidth(v) { width = v; } };
}

const f = makeFixture();
let onResize;
class Observer {
  constructor(callback) { onResize = callback; }
  observe(node) { assert.equal(node, f.classroom); }
}
initClassroomOutline({ documentRef: f.documentRef, ResizeObserverClass: Observer });
assert.equal(f.toggle.hidden, true);
assert.equal(f.outline.hidden, false);
f.setWidth(640);
onResize([{ contentRect: { width: 640 } }]);
assert.equal(f.toggle.hidden, false);
assert.equal(f.outline.hidden, true);
assert.equal(f.toggle.getAttribute('aria-expanded'), 'false');
f.handlers.click();
assert.equal(f.outline.hidden, false);
assert.equal(f.toggle.getAttribute('aria-expanded'), 'true');
assert.equal(f.toggle.textContent, 'Hide curriculum');
f.setWidth(960);
onResize([{ contentRect: { width: 960 } }]);
assert.equal(f.toggle.hidden, true);
assert.equal(f.outline.hidden, false);

const fallback = makeFixture();
let onMedia;
const media = { matches: true, addEventListener(event, cb) { onMedia = cb; } };
initClassroomOutline({ documentRef: fallback.documentRef, ResizeObserverClass: null, matchMedia: () => media });
assert.equal(fallback.toggle.hidden, false);
assert.equal(fallback.outline.hidden, true);
fallback.handlers.click();
assert.equal(fallback.outline.hidden, false);
media.matches = false; onMedia();
assert.equal(fallback.toggle.hidden, true);
assert.equal(fallback.outline.hidden, false);

let queries = 0;
initClassroomOutline({ documentRef: { querySelector() { queries++; return null; } }, ResizeObserverClass: null });
assert.equal(queries, 3);
console.log('FE-03 classroom outline ResizeObserver and media fallback: OK');
