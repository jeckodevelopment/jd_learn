let sdk;
function loadSdk() {
  if (globalThis.Vimeo?.Player) return Promise.resolve();
  if (!sdk) sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = 'https://player.vimeo.com/api/player.js';
    script.onload = resolve; script.onerror = () => reject(new Error('sdk'));
    document.head.append(script);
  });
  return sdk;
}
export async function create(container, descriptor, emit) {
  await loadSdk();
  const url = new URL('https://player.vimeo.com/video/' + descriptor.options.reference);
  url.searchParams.set('dnt', '1'); url.searchParams.set('autoplay', '0');
  if (descriptor.options.privacy_hash) url.searchParams.set('h', descriptor.options.privacy_hash);
  const frame = document.createElement('iframe'); frame.src = url.href;
  frame.title = container.dataset.title; frame.allow = 'fullscreen; picture-in-picture';
  frame.allowFullscreen = true; container.append(frame);
  const player = new Vimeo.Player(frame);
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
    destroy: async () => { for (const [event, fn] of handlers) player.off(event, fn); await player.destroy(); }
  };
}
