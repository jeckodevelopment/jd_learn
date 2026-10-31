let sdk;
async function loadSdk() {
  if (globalThis.Stream) return;
  if (!sdk) sdk = new Promise((resolve, reject) => {
    const script = document.createElement('script');
    const timer = setTimeout(() => { script.remove(); reject(new Error('sdk timeout')); }, 30000);
    script.src = 'https://embed.cloudflarestream.com/embed/sdk.latest.js';
    script.onload = () => { clearTimeout(timer); resolve(); };
    script.onerror = () => { clearTimeout(timer); reject(new Error('sdk')); };
    document.head.append(script);
  });
  await sdk;
}
export async function create(container, descriptor, emit, signal) {
  await loadSdk(); signal?.throwIfAborted();
  const {domain, token} = descriptor.options;
  if (!/^customer-[a-z0-9]{1,80}\.cloudflarestream\.com$/.test(domain)
    || typeof token !== 'string' || !/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/.test(token)) throw new Error('descriptor');
  const frame = document.createElement('iframe');
  frame.title = container.dataset.title; frame.referrerPolicy = 'no-referrer'; frame.allow = 'fullscreen; picture-in-picture'; frame.allowFullscreen = true;
  frame.src = 'https://' + domain + '/' + token + '/iframe?autoplay=false&controls=true';
  const handlers = new Map(); let player, timer, rejectReady;
  const destroy = () => {
    clearTimeout(timer); signal?.removeEventListener('abort', abort);
    if (player) for (const [event, listener] of handlers) player.removeEventListener(event, listener);
    frame.remove();
  };
  const abort = () => { destroy(); rejectReady?.(new DOMException('Playback cancelled', 'AbortError')); };
  signal?.addEventListener('abort', abort, {once:true});
  container.append(frame); player = Stream(frame);
  for (const event of ['play','pause','ended','seeking','seeked','waiting','stalled','ratechange','error']) {
    const listener = () => emit(['waiting','stalled'].includes(event) ? 'buffering' : event);
    handlers.set(event,listener); player.addEventListener(event,listener);
  }
  await new Promise((resolve,reject) => {
    rejectReady = reject;
    const ready = () => { clearTimeout(timer); player.removeEventListener('loadedmetadata',ready); handlers.delete('loadedmetadata'); resolve(); };
    handlers.set('loadedmetadata',ready); player.addEventListener('loadedmetadata',ready);
    timer = setTimeout(() => { destroy(); reject(new Error('player timeout')); },30000);
    if (Number.isFinite(player.duration) && player.duration > 0) ready();
  });
  return {
    sample: async () => ({position_ms:Math.round(player.currentTime*1000),duration_ms:Math.round(player.duration*1000),
      rate_milli:Math.round((player.playbackRate || 1)*1000),playing:!player.paused && !player.ended}),
    pause: () => player.pause(),
    seek: ms => { emit('seeking'); player.currentTime=ms/1000; emit('seeked'); },
    destroy
  };
}
