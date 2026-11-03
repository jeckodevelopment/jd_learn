let sdk;

function loadSdk() {
  if (globalThis.YT?.Player) return Promise.resolve();
  if (sdk) return sdk;
  sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const previous = globalThis.onYouTubeIframeAPIReady;
    let settled = false;
    const finish = error => {
      if (settled) return;
      settled = true;
      clearTimeout(timeout);
      document.removeEventListener('securitypolicyviolation', blocked);
      if (globalThis.onYouTubeIframeAPIReady === ready) {
        globalThis.onYouTubeIframeAPIReady = previous;
      }
      if (error) {
        script.remove();
        reject(new Error(error));
      } else {
        resolve();
      }
    };
    const blocked = event => {
      if (event.disposition === 'enforce' && /^https:\/\/(?:www\.)?youtube\.com(?:\/|$)/.test(event.blockedURI)) {
        finish('youtube_csp');
      }
    };
    const ready = () => {
      finish();
      if (typeof previous === 'function') previous();
    };
    const timeout = setTimeout(() => finish('youtube_sdk'), 30000);
    globalThis.onYouTubeIframeAPIReady = ready;
    document.addEventListener('securitypolicyviolation', blocked);
    script.onerror = () => finish('youtube_sdk');
    try {
      script.src = 'https://www.youtube.com/iframe_api';
      document.head.append(script);
    } catch {
      finish('youtube_sdk');
    }
  }).catch(error => {
    sdk = null;
    throw error;
  });
  return sdk;
}

export async function create(container, descriptor, emit, signal) {
  await loadSdk();
  signal?.throwIfAborted();
  const slot = document.createElement('div'); container.append(slot);
  let player, rejectReady, readyTimer;
  const abort = () => {
    clearTimeout(readyTimer); player?.destroy(); slot.remove();
    rejectReady?.(new DOMException('Playback cancelled', 'AbortError'));
  };
  signal?.addEventListener('abort', abort, {once: true});
  await new Promise((resolve, reject) => {
    rejectReady = reject;
    readyTimer = setTimeout(() => { abort(); reject(new Error('player timeout')); }, 30000);
    player = new YT.Player(slot, {
      host: 'https://www.youtube-nocookie.com', videoId: descriptor.options.reference,
      playerVars: {autoplay: 0, controls: 1, playsinline: 1, origin: location.origin,
        hl: (descriptor.options.locale || 'en').split('-')[0]},
      events: {onReady: () => { clearTimeout(readyTimer); resolve(); }, onError: ({data}) => {
          clearTimeout(readyTimer);
          const reason = data === 153 ? 'youtube_referrer'
            : [100, 101, 150].includes(data) ? 'youtube_embed' : 'startup_provider';
          emit('error', reason);
          reject(new Error(reason));
        },
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
