import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const source = await readFile(new URL('../component/media/js/lesson-progress.js', import.meta.url), 'utf8');
const flush = async () => { for (let i = 0; i < 5; i++) await new Promise(resolve => setImmediate(resolve)); };

async function scenario(result, hasCompleteForm = false) {
  const status = {
    dataset: {}, attributes: {}, textContent: '',
    setAttribute(name, value) { this.attributes[name] = value; }
  };
  const row = { textContent: '' };
  const completionHandlers = {};
  const completionButton = { disabled: false };
  const completeForm = {
    addEventListener(name, callback) { completionHandlers[name] = callback; },
    querySelector(selector) { return selector === 'button[type="submit"]' ? completionButton : null; }
  };
  const globalHandlers = {};
  const delays = new Map();
  let nextTimer = 1;
  let requestCount = 0;
  const config = {
    enrollment: 7, lesson: 12, idempotencyKey: 'same-key', token: 'csrf',
    url: '/index.php?option=com_jlms',
    labels: { pending: 'Saving', started: 'Started', completed: 'Completed',
      inProgress: 'In progress', error: 'Not saved' }
  };
  const context = {
    Joomla: { getOptions: () => config },
    document: { querySelector(selector) { return selector === '#jlms-lesson-progress-status' ? status :
      selector === '#jlms-classroom-lesson-12 > span' ? row :
      selector === '#jlms-complete-form' && hasCompleteForm ? completeForm : null; } },
    URLSearchParams,
    AbortController,
    addEventListener(name, callback) { globalHandlers[name] = callback; },
    setTimeout(callback, delay) {
      if (delay === 500) { queueMicrotask(callback); return nextTimer++; }
      const id = nextTimer++;
      delays.set(id, { callback, delay });
      return id;
    },
    clearTimeout(id) { delays.delete(id); },
    async fetch(url, options) {
      requestCount++;
      assert.equal(options.method, 'POST');
      assert.equal(options.body.get('idempotency_key'), 'same-key');
      return { ok: result === 'success', status: result === 'success' ? 200 : 400,
        async json() { return result === 'success' ? { success: true, data: { state: 'started' } } : { success: false }; } };
    }
  };
  context.globalThis = context;
  vm.runInNewContext(source, context);
  assert.equal(status.dataset.state, 'saving');
  await flush();
  return { status, row, delays, requestCount, completionHandlers, completionButton, globalHandlers };
}

const success = await scenario('success');
assert.equal(success.requestCount, 1);
assert.equal(success.status.textContent, 'Started');
assert.equal(success.status.dataset.state, 'saved');
assert.equal(success.status.attributes.role, 'status');
const timer = [...success.delays.values()].find(t => t.delay === 5000);
assert.ok(timer, 'Success has a five-second auto-clear');
timer.callback();
assert.equal(success.status.textContent, '');
assert.equal(success.status.dataset.state, 'idle');

const failure = await scenario('failure');
assert.equal(failure.requestCount, 1, 'Non-transient 400 is not retried');
assert.equal(failure.status.textContent, 'Not saved');
assert.equal(failure.status.dataset.state, 'error');
assert.equal(failure.status.attributes.role, 'alert');
assert.equal(failure.status.attributes['aria-live'], 'assertive');
assert.equal([...failure.delays.values()].some(t => t.delay === 5000), false);

const completion = await scenario('success', true);
assert.equal(typeof completion.completionHandlers.submit, 'function');
let prevented = 0;
completion.completionHandlers.submit({ preventDefault() { prevented++; } });
assert.equal(completion.status.dataset.state, 'saving', 'HTML completion is pending, not saved');
assert.equal(completion.status.textContent, 'Saving');
assert.equal(completion.completionButton.disabled, true);
completion.completionHandlers.submit({ preventDefault() { prevented++; } });
assert.equal(prevented, 1, 'Duplicate native completion submission must be blocked');
completion.globalHandlers.pageshow();
assert.equal(completion.completionButton.disabled, false, 'BFCache return restores manual completion');
assert.equal(completion.status.dataset.state, 'idle', 'BFCache restoration never claims a completed save');
completion.completionHandlers.submit({ preventDefault() { prevented++; } });
assert.equal(prevented, 1, 'Native completion can be retried after returning');

console.log('FE-04 lesson progress save feedback: success/error and native manual completion behavior OK');
