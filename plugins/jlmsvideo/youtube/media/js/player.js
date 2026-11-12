let sdk;

function savedCaptionLanguage() {
  try {
    const language = globalThis.localStorage?.getItem('jdlearn.video.language.v1')?.trim();
    return language && /^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i.test(language) ? language.toLowerCase() : '';
  } catch {
    return '';
  }
}

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
  const frame = document.createElement('iframe');
  const url = new URL('https://www.youtube-nocookie.com/embed/' + encodeURIComponent(descriptor.options.reference));
  const parameters = new URLSearchParams({
    enablejsapi: '1', origin: location.origin, autoplay: '0', controls: '1',
    playsinline: '1', hl: (descriptor.options.locale || 'en').split('-')[0],
  });
  const captionLanguage = savedCaptionLanguage();
  if (captionLanguage) {
    parameters.set('cc_lang_pref', captionLanguage);
    parameters.set('cc_load_policy', '1');
  }
  url.search = parameters;
  frame.title = container.dataset.title || 'YouTube';
  frame.referrerPolicy = 'strict-origin';
  frame.allow = 'encrypted-media; fullscreen; picture-in-picture';
  frame.allowFullscreen = true;
  let player, cancelReady;
  const destroy = () => {
    signal?.removeEventListener('abort', abort);
    frame.onload = null;
    player?.destroy();
    player = null;
    frame.remove();
  };
  const abort = () => {
    cancelReady?.(new DOMException('Playback cancelled', 'AbortError'));
    destroy();
  };
  signal?.addEventListener('abort', abort, {once: true});
  try {
    await new Promise((resolve, reject) => {
      const finish = error => {
        clearTimeout(readyTimer);
        cancelReady = null;
        if (error) reject(error);
        else resolve();
      };
      const readyTimer = setTimeout(() => finish(new Error('player timeout')), 30000);
      cancelReady = finish;
      // The SDK must attach after navigation, when the iframe has its YouTube origin.
      frame.onload = () => {
        frame.onload = null;
        try {
          player = new globalThis.YT.Player(frame, {
            events: {
              onReady: () => finish(),
              onError: ({data}) => {
                const reason = data === 153 ? 'youtube_referrer'
                  : [100, 101, 150].includes(data) ? 'youtube_embed' : 'startup_provider';
                finish(new Error(reason));
                emit('error', reason);
              },
              onStateChange: ({data}) => emit(({1: 'play', 2: 'pause', 0: 'ended', 3: 'buffering'})[data] || 'position'),
              onPlaybackRateChange: () => emit('ratechange'),
            },
          });
        } catch (error) {
          finish(error);
        }
      };
      frame.src = url.href;
      container.append(frame);
    });
  } catch (error) {
    destroy();
    throw error;
  }
  return {
    playbackRates: async () => player.getAvailablePlaybackRates?.() ?? [1],
    setPlaybackRate: async rate => {
      const supported = player.getAvailablePlaybackRates?.() ?? [1];
      if (!supported.includes(rate)) throw new Error('unsupported playback rate');
      player.setPlaybackRate(rate);
      const deadline = Date.now() + 1500;
      while (Date.now() < deadline) {
        const current = player.getPlaybackRate();
        if (Math.abs(current - rate) < 0.001) return current;
        await new Promise(resolve => setTimeout(resolve, 50));
      }
      throw new Error('playback rate was not applied');
    },
    sample: async () => ({position_ms: Math.round(player.getCurrentTime() * 1000),
      duration_ms: Math.round(player.getDuration() * 1000), rate_milli: Math.round(player.getPlaybackRate() * 1000),
      playing: player.getPlayerState() === 1}),
    pause: () => player.pauseVideo(),
    seek: ms => { emit('seeking'); player.seekTo(ms / 1000, true); emit('seeked'); },
    destroy
  };
}
