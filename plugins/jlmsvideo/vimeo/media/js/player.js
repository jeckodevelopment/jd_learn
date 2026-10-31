let sdk;
function loadSdk() {
  if (globalThis.Vimeo?.Player) return Promise.resolve();
  if (!sdk) sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const timeout = setTimeout(() => { script.remove(); reject(new Error('sdk timeout')); }, 30000);
    const fail = () => { clearTimeout(timeout); reject(new Error('sdk')); };
    script.src = 'https://player.vimeo.com/api/player.js';
    script.onload = () => { clearTimeout(timeout); resolve(); }; script.onerror = fail;
    document.head.append(script);
  });
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
  const abort = () => { player.destroy().catch(() => {}); frame.remove(); };
  signal?.addEventListener('abort', abort, {once: true});
  const handlers = new Map();
  for (const event of ['play', 'pause', 'ended', 'seeking', 'seeked', 'bufferstart', 'bufferend', 'playbackratechange', 'error']) {
    const handler = () => emit(({bufferstart: 'buffering', bufferend: 'position', playbackratechange: 'ratechange'})[event] || event);
    handlers.set(event, handler); player.on(event, handler);
  }
  await player.ready();
  return {
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
