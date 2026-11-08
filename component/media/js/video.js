/** Local adapter modules contain no remote requests until create() after consent + server grant. */
const providerErrors = new Set(['youtube_csp', 'youtube_sdk', 'youtube_referrer', 'youtube_embed']);

export class VideoLesson {
  constructor(config, root, transport = (...args) => globalThis.fetch(...args), importer = path => import(path)) {
    this.config = config; this.root = root; this.transport = transport; this.importer = importer;
    this.queue = []; this.pending = null; this.id = 0; this.sequence = 1; this.stopped = true;
    this.chain = Promise.resolve(); this.generation = 0;
    this.status = root.querySelector('#jlms-player-status');
    this.onConsent = () => {
      if (this.allowed()) this.start();
      else { this.stop(true); this.message(this.config.policy === 'click' ? 'policy' : 'consent'); }
    };
    this.onPageShow = event => { if (event.persisted) this.start(); };
    this.onVisibility = () => { if (document.hidden) { this.player?.pause(); this.record('pause').then(() => this.flush()); } };
    this.onPageHide = () => { this.player?.pause(); this.flush(true); this.stop(false); };
    this.onOffline = () => { this.player?.pause(); this.message('offline'); };
    window.addEventListener('jlms:consent-changed', this.onConsent);
    window.addEventListener('pagehide', this.onPageHide);
    window.addEventListener('pageshow', this.onPageShow);
    window.addEventListener('offline', this.onOffline);
    document.addEventListener('visibilitychange', this.onVisibility);
  }
  allowed() {
    return this.config.provider === 'html5' || this.config.policy === 'embed' || (this.config.policy === 'site_consent'
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
    if (!this.allowed()) { this.message(this.config.policy === 'click' ? 'policy' : 'consent'); return; }
    const generation = ++this.generation; this.starting = true;
    let failure = 'startup_request';
    this.message('loading');
    try {
      const grant = await this.request('playback', {consent: '1'});
      if (!this.allowed() || generation !== this.generation) { await this.request('close', {session_id: grant.session_id}); return; }
      // The PHP registry validates keys; Joomla plugins provide this conventional module path.
      if (grant.provider !== this.config.provider || !/^[a-z][a-z0-9_]{1,63}$/.test(grant.provider)) throw new Error('provider');
      this.session = grant; this.sequence = 1; this.id = 0; this.queue = []; this.pending = null;
      this.started = performance.now(); this.previous = null; this.aborter = new AbortController();
      failure = 'startup_module';
      const moduleUrl = new URL(`../../plg_jlmsvideo_${grant.provider}/js/player.js`, import.meta.url);
      moduleUrl.search = new URL(import.meta.url).search;
      const adapter = await this.importer(moduleUrl.href);
      if (!this.allowed() || generation !== this.generation) { await this.request('close', {session_id: grant.session_id}); return; }
      this.adapter = adapter; this.stopped = false;
      failure = 'startup_provider';
      const player = await adapter.create(this.root.querySelector('#jlms-player'), grant, (event, reason) => {
        if (event === 'error') { this.message(providerErrors.has(reason) ? reason : 'startup_provider'); this.stop(false); }
        else this.record(event).then(() => { if (['pause', 'ended'].includes(event)) this.flush(); });
      }, this.aborter.signal);
      if (this.stopped || generation !== this.generation || !this.allowed()) { await player.destroy(); return; }
      this.player = player;
      if (grant.resume_ms) await player.seek(grant.resume_ms);
      await this.record('seeked');
      if (this.stopped || generation !== this.generation) return;
      this.sampler = setInterval(() => this.record('position'), 1000);
      this.schedule(); this.scheduleRenew(); this.message('ready');
    } catch (error) {
      if (generation === this.generation) {
        this.message(error.status === 401 || error.status === 403 || error.status === 404 ? 'denied' : providerErrors.has(error.message) ? error.message : failure);
        await this.stop(false);
      }
    }
    finally { this.starting = false; }
  }
  record(type) {
    this.chain = this.chain.then(async () => {
      if (this.stopped || this.replacing || !this.player) return;
      const sample = await this.player.sample();
      if (this.stopped) return;
      // YouTube returns zero until metadata loads, which can require the first play.
      if (this.config.provider === 'youtube' && sample.duration_ms === 0) return;
      if (!Number.isInteger(sample.duration_ms) || sample.duration_ms < 1000 || sample.duration_ms > 28800000
        || Math.abs(sample.duration_ms - this.session.duration_ms) > Math.max(2000, Math.min(5000, Math.ceil(this.session.duration_ms / 100)))) {
        this.message('duration'); await this.stop(false); return;
      }
      this.duration = sample.duration_ms;
      const now = Math.round(performance.now() - this.started);
      // SDKs differ in seek notifications: discontinuities also reset the server anchor.
      if (this.previous && type === 'position' && Math.abs(sample.position_ms - this.previous.position_ms
        - (now - this.previous.client_ms) * this.previous.rate_milli / 1000) > 500) type = 'seeked';
      const event = {id: ++this.id, type, position_ms: Math.max(0, Math.min(sample.duration_ms, sample.position_ms)),
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
  scheduleRenew(delay) {
    clearTimeout(this.renewTimer);
    const seconds = delay ?? this.session?.options?.renew_after_seconds;
    if (!this.stopped && Number.isFinite(seconds) && seconds > 0) this.renewTimer = setTimeout(() => this.renew(), seconds * 1000);
  }
  async renew() {
    if (this.stopped || this.renewing) return;
    if (!this.allowed()) { await this.stop(true); return; }
    this.renewing = true; const generation = this.generation;
    try {
      this.player?.pause(); await this.record('pause'); await this.flush();
      if (this.pending || this.saving) { this.scheduleRenew(5); return; }
      const grant = await this.request('renew', {session_id:this.session.session_id,
        source_revision:this.session.source_revision,consent:'1'});
      if (this.stopped || generation !== this.generation || !this.allowed()) return;
      const sample = await this.player.sample(); this.replacing = true;
      await this.player.destroy(); this.player = null;
      const player = await this.adapter.create(this.root.querySelector('#jlms-player'), grant, event => {
        if (event === 'error') { this.message('error'); this.stop(false); }
        else this.record(event);
      },this.aborter.signal);
      if (this.stopped || generation !== this.generation || !this.allowed()) { await player.destroy(); return; }
      this.player = player; this.previous = null; this.session.options = grant.options;
      await player.seek(sample.position_ms); this.replacing = false;
      await this.record('seeked'); this.message('renewed'); this.scheduleRenew();
    } catch (error) {
      if (error.status && error.status < 500 && error.status !== 429) { this.message('denied'); await this.stop(false); }
      else { this.message('offline'); this.scheduleRenew(5); }
    } finally { this.renewing = false; this.replacing = false; }
  }
  async stop(save) {
    ++this.generation; this.stopped = true; this.aborter?.abort();
    clearInterval(this.sampler); clearTimeout(this.timer); clearTimeout(this.renewTimer);
    const player = this.player; this.player = null;
    try { await player?.destroy(); } catch { /* Teardown must continue. */ }
    this.root.querySelector('#jlms-player').replaceChildren();
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
    window.removeEventListener('pageshow', this.onPageShow);
    window.removeEventListener('offline', this.onOffline);
    document.removeEventListener('visibilitychange', this.onVisibility);
  }
}
if (typeof document !== 'undefined') {
  const config = globalThis.Joomla?.getOptions('com_jlms.video');
  const root = document.getElementById('jlms-video-region');
  if (config && root) new VideoLesson(config, root).start();
}
