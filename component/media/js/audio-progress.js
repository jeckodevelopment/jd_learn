const config = globalThis.Joomla?.getOptions?.('com_jlms.audio');
const player = document.querySelector('#jlms-audio-player');

if (config && player) {
  const status = document.querySelector('#jlms-audio-progress-status');
  let queued = null;
  let lastSent = Number(config.resumeMs || 0);
  let request = null;
  let timer = 0;

  const position = () => Math.max(0, Math.floor(player.currentTime * 1000));
  const setStatus = key => { if (status) status.textContent = config.labels?.[key] || ''; };
  const wait = ms => new Promise(resolve => globalThis.setTimeout(resolve, ms));
  const retryable = error => !error.status || error.status === 408 || error.status === 429 || error.status >= 500;

  const send = async (value, keepalive, attempt = 0) => {
    const body = new URLSearchParams({ enrollment_id: String(config.enrollment), lesson_id: String(config.lesson),
      position_ms: String(value), [config.token]: '1' });
    try {
      const response = await fetch(config.url, { method: 'POST', body, credentials: 'same-origin', cache: 'no-store',
        keepalive, headers: { Accept: 'application/json' } });
      let result;
      try { result = await response.json(); } catch { throw new Error('invalid-response'); }
      if (!response.ok || result.success !== true) {
        const error = new Error('audio-progress');
        error.status = response.status;
        throw error;
      }
      lastSent = value;
      setStatus('saved');
    } catch (error) {
      if (!keepalive && attempt === 0 && retryable(error)) {
        await wait(500);
        return send(value, keepalive, 1);
      }
      // Retain the failed point for the next player event, but don't create a retry loop.
      if (queued === null) queued = value;
      setStatus('error');
    }
  };

  const flush = keepalive => {
    if (request || queued === null) return request;
    const value = queued;
    queued = null;
    if (value === lastSent) return;
    request = send(value, keepalive).finally(() => {
      request = null;
      if (queued !== null && queued !== value) void flush(keepalive);
    });
    return request;
  };

  const queue = () => { queued = position(); void flush(false); };
  player.addEventListener('loadedmetadata', () => {
    const saved = Number(config.resumeMs || player.dataset.resumeMs || 0) / 1000;
    if (Number.isFinite(saved) && saved > 0 && (!Number.isFinite(player.duration) || saved < player.duration)) {
      try { player.currentTime = saved; } catch { /* Wait for the browser's seekable range. */ }
    }
  }, { once: true });
  player.addEventListener('timeupdate', () => {
    if (!timer) timer = window.setTimeout(() => { timer = 0; queue(); }, 12000);
  });
  for (const event of ['pause', 'seeked', 'ended']) player.addEventListener(event, queue);
  window.addEventListener('pagehide', () => {
    if (timer) { window.clearTimeout(timer); timer = 0; }
    queued = position();
    void flush(true);
  });
}
