/** Native media element; source URL is an authorised same-origin controller, never a file path. */
export async function create(container, descriptor, emit, signal) {
  signal?.throwIfAborted();
  const url = new URL(descriptor.options.url, location.href);
  if (url.origin !== location.origin || !['http:', 'https:'].includes(url.protocol)) throw new Error('media origin');
  const video = document.createElement('video'); video.controls = true; video.autoplay = false;
  video.preload = 'metadata'; video.playsInline = true; video.setAttribute('aria-label', container.dataset.title);
  video.setAttribute('controlsList', 'nodownload');
  const handlers = new Map(); let rejectReady, timer;
  const destroy = () => {
    clearTimeout(timer); signal?.removeEventListener('abort', abort);
    for (const [event, handler] of handlers) video.removeEventListener(event, handler);
    video.pause(); video.removeAttribute('src'); video.load(); video.remove();
  };
  const abort = () => { destroy(); rejectReady?.(new DOMException('Playback cancelled', 'AbortError')); };
  signal?.addEventListener('abort', abort, {once:true});
  for (const event of ['play','pause','ended','seeking','seeked','waiting','stalled','ratechange','error']) {
    const handler = () => emit(['waiting','stalled'].includes(event) ? 'buffering' : event);
    handlers.set(event,handler); video.addEventListener(event,handler);
  }
  const ready = new Promise((resolve,reject) => {
    rejectReady = reject;
    const loaded = () => { clearTimeout(timer); resolve(); };
    handlers.set('loadedmetadata',loaded); video.addEventListener('loadedmetadata',loaded);
    const failed = () => { destroy(); reject(new Error('media unavailable')); };
    handlers.set('abort',failed); video.addEventListener('abort',failed);
    timer=setTimeout(()=>{destroy();reject(new Error('media timeout'));},30000);
  });
  container.append(video); video.src = url.href;
  await ready; signal?.throwIfAborted();
  return {
    sample: async () => ({position_ms:Math.round(video.currentTime*1000),duration_ms:Math.round(video.duration*1000),
      rate_milli:Math.round(video.playbackRate*1000),playing:!video.paused && !video.ended}),
    pause:()=>video.pause(), seek:ms=>{video.currentTime=ms/1000;}, destroy
  };
}
