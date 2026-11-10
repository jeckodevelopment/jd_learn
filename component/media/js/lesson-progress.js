const config = globalThis.Joomla?.getOptions?.('com_jlms.lessonProgress');

if (config && Number.isInteger(Number(config.enrollment)) && Number.isInteger(Number(config.lesson))) {
  const status = document.querySelector('#jlms-lesson-progress-status');
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

  if (status) status.textContent = config.labels?.pending || '';
  send(0).then(result => {
    const row = document.querySelector(`#jlms-classroom-lesson-${Number(config.lesson)} > span`);
    if (row) row.textContent = result.data?.state === 'completed'
      ? (config.labels?.completed || '') : (config.labels?.inProgress || '');
    if (status) status.textContent = result.data?.state === 'completed'
      ? (config.labels?.completed || '') : (config.labels?.started || '');
  }).catch(() => {
    if (status) status.textContent = config.labels?.error || '';
  });
}
