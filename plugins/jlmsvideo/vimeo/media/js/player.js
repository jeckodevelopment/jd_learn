let sdk;
function loadSdk() {
  if (globalThis.Vimeo?.Player) return Promise.resolve();
  if (!sdk) sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const timeout = setTimeout(() => { script.remove(); reject(new Error('sdk timeout')); }, 30000);
    const fail = () => { clearTimeout(timeout); script.remove(); reject(new Error('sdk')); };
    script.src = 'https://player.vimeo.com/api/player.js';
    script.onload = () => { clearTimeout(timeout); resolve(); }; script.onerror = fail;
    document.head.append(script);
  }).catch(error => { sdk = null; throw error; });
  return sdk;
}
export async function create(container, descriptor, emit, signal) {
  await loadSdk();
  signal?.throwIfAborted();
  const url = new URL('https://player.vimeo.com/video/' + descriptor.options.reference);
  url.searchParams.set('dnt', '1'); url.searchParams.set('autoplay', '0');
  if (descriptor.options.privacy_hash) url.searchParams.set('h', descriptor.options.privacy_hash);
  const frame = document.createElement('iframe'); frame.src = url.href;
  frame.title = container.dataset.title; frame.allow = 'fullscreen; picture-in-picture';
  frame.allowFullscreen = true; container.append(frame);
  const player = new Vimeo.Player(frame);
  let rejectReady, readyTimer;
  const abort = () => {
    clearTimeout(readyTimer); player.destroy().catch(() => {}); frame.remove();
    rejectReady?.(new DOMException('Playback cancelled', 'AbortError'));
  };
  signal?.addEventListener('abort', abort, {once: true});
  let probingRate = false;
  const handlers = new Map();
  for (const event of ['play', 'pause', 'ended', 'seeking', 'seeked', 'bufferstart', 'bufferend', 'playbackratechange', 'error']) {
    const handler = () => { if (event === 'playbackratechange' && probingRate) return; emit(({bufferstart: 'buffering', bufferend: 'position', playbackratechange: 'ratechange'})[event] || event); };
    handlers.set(event, handler); player.on(event, handler);
  }
  await Promise.race([player.ready(), new Promise((_, reject) => {
    rejectReady = reject;
    readyTimer = setTimeout(() => { abort(); reject(new Error('player timeout')); }, 30000);
  })]);
  clearTimeout(readyTimer);
  return {
    playbackRates: async () => {
      probingRate = true;
      try {
        await player.setPlaybackRate(1.25);
        await player.setPlaybackRate(1);
        await new Promise(resolve => setTimeout(resolve, 0));
        return [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2];
      } catch {
        try { await player.setPlaybackRate(1); } catch {}
        return [1];
      } finally { probingRate = false; }
    },
    setPlaybackRate: rate => player.setPlaybackRate(rate),
    sample: async () => {
      const [position, duration, rate, paused, ended] = await Promise.all([
        player.getCurrentTime(), player.getDuration(), player.getPlaybackRate(), player.getPaused(), player.getEnded()]);
      return {position_ms: Math.round(position * 1000), duration_ms: Math.round(duration * 1000),
        rate_milli: Math.round(rate * 1000), playing: !paused && !ended};
    },
    pause: () => player.pause(), seek: ms => player.setCurrentTime(ms / 1000),
    destroy: async () => { signal?.removeEventListener('abort', abort); for (const [event, fn] of handlers) player.off(event, fn); await player.destroy(); }
  };
}
