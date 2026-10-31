let sdk;
function loadSdk() {
  if (globalThis.YT?.Player) return Promise.resolve();
  if (!sdk) sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const timeout = setTimeout(() => { script.remove(); reject(new Error('sdk timeout')); }, 30000);
    const fail = () => { clearTimeout(timeout); reject(new Error('sdk')); };
    script.src = 'https://www.youtube.com/iframe_api';
    script.onerror = fail;
    const previous = globalThis.onYouTubeIframeAPIReady;
    globalThis.onYouTubeIframeAPIReady = () => { clearTimeout(timeout); previous?.(); resolve(); };
    document.head.append(script);
  });
  return sdk;
}
export async function create(container, descriptor, emit, signal) {
  await loadSdk();
  signal?.throwIfAborted();
  const slot = document.createElement('div'); container.append(slot);
  let player;
  const abort = () => { player?.destroy(); slot.remove(); };
  signal?.addEventListener('abort', abort, {once: true});
  await new Promise((resolve, reject) => {
    player = new YT.Player(slot, {
      host: 'https://www.youtube-nocookie.com', videoId: descriptor.options.reference,
      playerVars: {autoplay: 0, controls: 1, playsinline: 1, origin: location.origin,
        hl: descriptor.options.locale.split('-')[0]},
      events: {onReady: resolve, onError: () => { emit('error'); reject(new Error('provider')); },
        onStateChange: ({data}) => emit(({1: 'play', 2: 'pause', 0: 'ended', 3: 'buffering'})[data] || 'position'),
        onPlaybackRateChange: () => emit('ratechange')}
    });
  });
  return {
    sample: async () => ({position_ms: Math.round(player.getCurrentTime() * 1000),
      duration_ms: Math.round(player.getDuration() * 1000), rate_milli: Math.round(player.getPlaybackRate() * 1000),
      playing: player.getPlayerState() === 1}),
    pause: () => player.pauseVideo(),
    seek: ms => { emit('seeking'); player.seekTo(ms / 1000, true); emit('seeked'); },
    destroy: () => { signal?.removeEventListener('abort', abort); player.destroy(); }
  };
}
