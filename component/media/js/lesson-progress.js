const config = globalThis.Joomla?.getOptions?.('com_jlms.lessonProgress');

if (config && Number.isInteger(Number(config.enrollment)) && Number.isInteger(Number(config.lesson))) {
  const status = document.querySelector('#jlms-lesson-progress-status');
  let feedbackTimer = 0;
  const feedback = (text, state) => {
    if (!status) return;
    if (feedbackTimer) globalThis.clearTimeout(feedbackTimer);
    feedbackTimer = 0;
    status.textContent = text;
    status.dataset.state = state;
    // Failure is never dismissed automatically: the user must know it wasn't saved.
    status.setAttribute('role', state === 'error' ? 'alert' : 'status');
    status.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
    if (state === 'saved') {
      feedbackTimer = globalThis.setTimeout(() => {
        if (status.dataset.state === 'saved') {
          status.textContent = '';
          status.dataset.state = 'idle';
        }
      }, 5000);
    }
  };
  // Manual completion is a native HTML POST followed by a server redirect.
  // Show only "saving" until that navigation confirms the result; a receipt
  // from the separate lesson.begin POST must not falsely report it as saved.
  let completing = false;
  const form = document.querySelector('#jlms-complete-form');
  if (form) {
    let submitted = false;
    const submitButton = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', event => {
      if (submitted) {
        event.preventDefault();
        return;
      }
      submitted = true;
      completing = true;
      feedback(config.labels?.pending || '', 'saving');
      if (submitButton) submitButton.disabled = true;
      // On BFCache restoration, re-enable the native form for a fresh submit.
      globalThis.addEventListener?.('pageshow', () => {
        submitted = false;
        completing = false;
        if (submitButton) submitButton.disabled = false;
        feedback('', 'idle'); // Restored page cannot know whether the POST completed.
      }, { once: true });
    });
  }

  const body = new URLSearchParams({ enrollment_id: String(config.enrollment), lesson_id: String(config.lesson),
    idempotency_key: String(config.idempotencyKey), [config.token]: '1' });

  const wait = ms => new Promise(resolve => globalThis.setTimeout(resolve, ms));
  const send = async attempt => {
    const controller = new AbortController();
    const timeout = globalThis.setTimeout(() => controller.abort(), 8000);
    let response;
    try {
      response = await fetch(config.url, { method: 'POST', body, credentials: 'same-origin', cache: 'no-store',
        headers: { Accept: 'application/json' }, signal: controller.signal });
    } catch (error) {
      if (attempt === 0) {
        await wait(500);
        return send(1);
      }
      throw error;
    } finally {
      globalThis.clearTimeout(timeout);
    }

    if ([408, 429].includes(response.status) || response.status >= 500) {
      if (attempt === 0) {
        await wait(500);
        return send(1);
      }
    }
    const result = await response.json();
    if (!response.ok || result.success !== true) throw new Error('lesson-progress');
    return result;
  };

  feedback(config.labels?.pending || '', 'saving');
  // A video playback session writes the same enrollment/progress rows. Expose the
  // bounded lesson-start receipt so video.js can avoid overlapping transactions.
  const beginReceipt = send(0).then(result => {
    const row = document.querySelector(`#jlms-classroom-lesson-${Number(config.lesson)} > span`);
    if (!completing) {
      if (row) row.textContent = result.data?.state === 'completed'
        ? (config.labels?.completed || '') : (config.labels?.inProgress || '');
      feedback(result.data?.state === 'completed'
        ? (config.labels?.completed || '') : (config.labels?.started || ''), 'saved');
    }
  }).catch(() => {
    if (!completing) feedback(config.labels?.error || '', 'error');
  });
  globalThis.__jlmsLessonBegin = {
    enrollment: Number(config.enrollment), lesson: Number(config.lesson), settled: beginReceipt
  };
}
