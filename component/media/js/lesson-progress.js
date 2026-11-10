const config = globalThis.Joomla?.getOptions?.('com_jlms.lessonProgress');

if (config && Number.isInteger(Number(config.enrollment)) && Number.isInteger(Number(config.lesson))) {
  const status = document.querySelector('#jlms-lesson-progress-status');
  const body = new URLSearchParams({ enrollment_id: String(config.enrollment), lesson_id: String(config.lesson),
    [config.token]: '1' });

  fetch(config.url, { method: 'POST', body, credentials: 'same-origin', cache: 'no-store',
    headers: { Accept: 'application/json' } }).then(async response => {
    const result = await response.json();
    if (!response.ok || result.success !== true) throw new Error('lesson-progress');
    const row = document.querySelector(`#jlms-classroom-lesson-${Number(config.lesson)} > span`);
    if (row) row.textContent = result.data?.state === 'completed'
      ? (config.labels?.completed || '') : (config.labels?.inProgress || '');
    if (status) status.textContent = result.data?.state === 'completed'
      ? (config.labels?.completed || '') : (config.labels?.started || '');
  }).catch(() => {
    if (status) status.textContent = config.labels?.error || '';
  });
}
