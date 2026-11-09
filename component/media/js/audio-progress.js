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

  const flush = async keepalive => {
    if (request || queued === null) return request;
    const value = queued;
    queued = null;
    if (value === lastSent) return;
    const body = new URLSearchParams({ enrollment_id: String(config.enrollment), lesson_id: String(config.lesson),
      position_ms: String(value), [config.token]: '1' });
    request = fetch(config.url, { method: 'POST', body, credentials: 'same-origin', cache: 'no-store',
      keepalive, headers: { Accept: 'application/json' } }).then(async response => {
      const result = await response.json();
      if (!response.ok || result.success !== true) throw new Error('audio-progress');
      lastSent = value;
      setStatus('saved');
    }).catch(() => setStatus('error')).finally(() => {
      request = null;
      if (queued !== null) void flush(keepalive);
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
