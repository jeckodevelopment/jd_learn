/** Local adapter modules contain no remote requests until create() after consent + server grant. */
export class VideoLesson {
  constructor(config, root, transport = fetch, importer = path => import(path)) {
    this.config = config; this.root = root; this.transport = transport; this.importer = importer;
    this.queue = []; this.pending = null; this.id = 0; this.sequence = 1; this.stopped = true;
    this.chain = Promise.resolve(); this.generation = 0;
    this.status = root.querySelector('#jlms-player-status');
    this.load = root.querySelector('#jlms-video-load'); this.stopButton = root.querySelector('#jlms-video-stop');
    this.load.addEventListener('click', () => this.start());
    this.stopButton.addEventListener('click', () => this.stop(true));
    this.onConsent = () => { if (!this.allowed()) this.stop(true); };
    this.onVisibility = () => { if (document.hidden) { this.player?.pause(); this.record('pause').then(() => this.flush()); } };
    this.onPageHide = () => { this.player?.pause(); this.flush(true); this.stop(false); };
    this.onOffline = () => { this.player?.pause(); this.message('offline'); };
    window.addEventListener('jlms:consent-changed', this.onConsent);
    window.addEventListener('pagehide', this.onPageHide);
    window.addEventListener('offline', this.onOffline);
    document.addEventListener('visibilitychange', this.onVisibility);
  }
  allowed() {
    return this.config.policy === 'click' || (this.config.policy === 'site_consent'
      && window.JlmsConsent?.isAllowed(this.config.provider) === true);
  }
  message(key) { this.status.textContent = this.config.labels[key] || this.config.labels.error; }
  async request(task, values, keepalive = false) {
    const body = new URLSearchParams({...values, enrollment_id: this.config.enrollment,
      lesson_id: this.config.lesson, [this.config.token]: '1'});
    const response = await this.transport(this.config.urls[task], {method: 'POST', body,
      credentials: 'same-origin', cache: 'no-store', keepalive, headers: {'Accept': 'application/json'}});
    const json = await response.json();
    if (!response.ok || json.success !== true) {
      const error = new Error('request'); error.status = response.status; throw error;
    }
    return json.data;
  }
  async start() {
    if (!this.stopped || this.starting) return;
    if (!this.allowed()) { this.message('consent'); return; }
    const generation = ++this.generation; this.starting = true; this.load.disabled = true;
    try {
      const grant = await this.request('playback', {consent: '1'});
      if (!this.allowed() || generation !== this.generation) { await this.request('close', {session_id: grant.session_id}); return; }
      const paths = {youtube: '../../plg_jlmsvideo_youtube/js/player.js', vimeo: '../../plg_jlmsvideo_vimeo/js/player.js'};
      if (grant.provider !== this.config.provider || !paths[grant.provider]) throw new Error('provider');
      this.session = grant; this.sequence = 1; this.id = 0; this.queue = []; this.pending = null;
      this.started = performance.now(); this.previous = null;
      const adapter = await this.importer(new URL(paths[grant.provider], import.meta.url).href);
      if (!this.allowed() || generation !== this.generation) { await this.request('close', {session_id: grant.session_id}); return; }
      this.stopped = false;
      const player = await adapter.create(this.root.querySelector('#jlms-player'), grant, event => {
        if (event === 'error') { this.message('error'); this.stop(false); }
        else this.record(event).then(() => { if (['pause', 'ended'].includes(event)) this.flush(); });
      });
      if (this.stopped || generation !== this.generation || !this.allowed()) { await player.destroy(); return; }
      this.player = player;
      if (grant.resume_ms) await player.seek(grant.resume_ms);
      await this.record('seeked');
      this.sampler = setInterval(() => this.record('position'), 1000);
      this.schedule(); this.stopButton.hidden = false; this.message('ready');
    } catch { this.message('error'); await this.stop(false); }
    finally { this.starting = false; this.load.disabled = !this.stopped; }
  }
  record(type) {
    this.chain = this.chain.then(async () => {
      if (this.stopped || !this.player) return;
      const sample = await this.player.sample();
      if (this.stopped) return;
      if (!Number.isInteger(sample.duration_ms) || sample.duration_ms < 1000
        || Math.abs(sample.duration_ms - this.session.duration_ms) > Math.max(1000, this.session.duration_ms / 200)) {
        this.message('duration'); await this.stop(false); return;
      }
      this.duration = sample.duration_ms;
      const now = Math.round(performance.now() - this.started);
      // SDKs differ in seek notifications: discontinuities also reset the server anchor.
      if (this.previous && type === 'position' && Math.abs(sample.position_ms - this.previous.position_ms
        - (now - this.previous.client_ms) * this.previous.rate_milli / 1000) > 500) type = 'seeked';
      const event = {id: ++this.id, type, position_ms: Math.max(0, Math.min(this.session.duration_ms, sample.position_ms)),
        client_ms: now, rate_milli: sample.rate_milli, playing: sample.playing};
      if (this.queue.length >= 80) { this.player.pause(); this.message('offline'); return; }
      this.queue.push(event); this.previous = event;
    }).catch(() => { this.message('error'); this.stop(false); });
    return this.chain;
  }
  schedule() {
    clearTimeout(this.timer);
    if (!this.stopped) this.timer = setTimeout(() => this.flush(), 16000 + Math.random() * 8000);
  }
  async flush(keepalive = false) {
    if (this.saving || !this.session || (!this.pending && !this.queue.length)) return;
    this.saving = true;
    if (!this.pending) this.pending = {session_id: this.session.session_id, source_revision: this.session.source_revision,
      sequence: this.sequence, duration_ms: this.duration, events_json: JSON.stringify(this.queue.splice(0))};
    try {
      const result = await this.request('save', this.pending, keepalive);
      this.sequence = result.next_sequence; this.pending = null;
      if (result.lesson_completed) this.message('completed');
      const meter = document.getElementById('jlms-progress-meter');
      if (meter) meter.value = result.course_percent;
    } catch (error) {
      if (error.status && error.status !== 429 && error.status < 500) {
        this.message(error.status === 409 ? 'conflict' : 'denied'); await this.stop(false);
      } else { this.player?.pause(); this.message('offline'); }
    } finally { this.saving = false; this.schedule(); }
  }
  async stop(save) {
    ++this.generation; this.stopped = true;
    clearInterval(this.sampler); clearTimeout(this.timer);
    const player = this.player; this.player = null;
    try { await player?.destroy(); } catch { /* Teardown must continue. */ }
    this.root.querySelector('#jlms-player').replaceChildren();
    this.stopButton.hidden = true; this.load.disabled = false;
    // On explicit stop save already observed samples, then expire the server session.
    if (save && !this.saving && this.session) {
      await this.flush();
      if (!this.pending) { try { await this.request('close', {session_id: this.session.session_id}); } catch {} }
    }
  }
  async destroy() {
    await this.stop(true);
    window.removeEventListener('jlms:consent-changed', this.onConsent);
    window.removeEventListener('pagehide', this.onPageHide);
    window.removeEventListener('offline', this.onOffline);
    document.removeEventListener('visibilitychange', this.onVisibility);
  }
}
if (typeof document !== 'undefined') {
  const config = globalThis.Joomla?.getOptions('com_jlms.video');
  const root = document.getElementById('jlms-video-region');
  if (config && root) new VideoLesson(config, root);
}
