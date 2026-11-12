/** Local adapter modules contain no remote requests until create() after consent + server grant. */
const providerErrors = new Set(['youtube_csp', 'youtube_sdk', 'youtube_referrer', 'youtube_embed']);

export class VideoLesson {
  constructor(config, root, transport = (...args) => globalThis.fetch(...args), importer = path => import(path)) {
    this.config = config; this.root = root; this.transport = transport; this.importer = importer;
    this.queue = []; this.pending = null; this.id = 0; this.sequence = 1; this.stopped = true; this.tracking = true;
    this.chain = Promise.resolve(); this.generation = 0;
    this.status = root.querySelector('#jlms-player-status');
    this.rateSelect = root.querySelector('#jlms-video-speed');
    this.languageSelect = root.querySelector('#jlms-video-language');
    this.onRateChange = () => this.changePlaybackRate();
    this.onLanguageChange = () => this.changeCaptionLanguage();
    this.rateSelect?.addEventListener('change', this.onRateChange);
    this.languageSelect?.addEventListener('change', this.onLanguageChange);
    this.onConsent = () => {
      if (this.allowed()) this.start();
      else { this.stop(true); this.message(this.config.policy === 'click' ? 'policy' : 'consent'); }
    };
    this.onPageShow = event => { if (event.persisted) this.start(); };
    this.onVisibility = () => { if (document.hidden) { this.player?.pause(); if (!this.config.preview && this.tracking) this.record('pause').then(() => this.flush()); } };
    this.onPageHide = () => { this.player?.pause(); if (!this.config.preview && this.tracking) this.flush(true); this.stop(false); };
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
  message(key) { if (this.status) this.status.textContent = this.config.labels[key] || this.config.labels.error; }
  async configurePlaybackSpeed(player) {
    const control = this.root.querySelector('#jlms-video-speed-control');
    const select = this.rateSelect;
    if (!control || !select || typeof player.playbackRates !== 'function' || typeof player.setPlaybackRate !== 'function') return;
    try {
      const rates = [...new Set((await player.playbackRates()).map(Number)
        .filter(rate => Number.isFinite(rate) && rate >= 0.5 && rate <= 2))].sort((a, b) => a - b);
      if (rates.length < 2 || !rates.includes(1)) { control.hidden = true; return; }
      for (const option of select.options) option.hidden = !rates.includes(Number(option.value));
      let rate = this.currentRate ?? 1;
      if (!this.ratePreferenceLoaded) {
        this.ratePreferenceLoaded = true;
        try {
          const saved = Number(globalThis.localStorage?.getItem('jdlearn.video.speed.v1'));
          if (rates.includes(saved)) rate = saved;
        } catch { /* Playback remains available when browser storage is disabled. */ }
      }
      if (!rates.includes(rate)) rate = 1;
      try {
        if (rate !== 1) {
          const applied = Number(await player.setPlaybackRate(rate));
          if (!Number.isFinite(applied) || Math.abs(applied - rate) > 0.001) rate = 1;
        }
      } catch { rate = 1; }
      this.currentRate = rate;
      select.value = String(rate);
      control.hidden = false;
    } catch {
      control.hidden = true;
    }
  }
  async changePlaybackRate() {
    if (!this.player || !this.rateSelect || typeof this.player.setPlaybackRate !== 'function') return;
    const previous = this.currentRate ?? 1, requested = Number(this.rateSelect.value);
    try {
      const applied = Number(await this.player.setPlaybackRate(requested));
      if (!Number.isFinite(applied) || Math.abs(applied - requested) > 0.001) throw new Error('unsupported');
      this.currentRate = applied;
      try { globalThis.localStorage?.setItem('jdlearn.video.speed.v1', String(applied)); } catch { /* Optional preference. */ }
      await this.record('ratechange');
    } catch {
      this.rateSelect.value = String(previous);
      this.message('rate_unavailable');
    }
  }
  async configureCaptionLanguage(player) {
    const control = this.root.querySelector('#jlms-video-language-control');
    const select = this.languageSelect;
    if (!control || !select || typeof player.textTracks !== 'function' || typeof player.setTextTrack !== 'function') return;
    try {
      const tracks = (await player.textTracks()).filter(track => track && typeof track.language === 'string' && track.language.trim());
      if (!tracks.length) { control.hidden = true; return; }
      const byLanguage = new Map();
      for (const track of tracks) {
        const language = track.language.trim().toLowerCase();
        if (!byLanguage.has(language)) byLanguage.set(language, track);
      }
      let saved = '';
      try { saved = globalThis.localStorage?.getItem('jdlearn.video.language.v1')?.toLowerCase() || ''; } catch { /* Optional preference. */ }
      const selected = saved && (byLanguage.has(saved) ? saved : [...byLanguage.keys()].find(language => language.split('-')[0] === saved.split('-')[0]))
        || [...byLanguage].find(([, track]) => track.active)?.[0] || '';
      select.replaceChildren();
      const off = document.createElement('option'); off.value = ''; off.textContent = this.config.labels.captions_off || 'Off'; select.append(off);
      for (const [language, track] of byLanguage) {
        const option = document.createElement('option'); option.value = language;
        option.textContent = track.label || language; select.append(option);
      }
      if (selected && selected !== [...byLanguage].find(([, track]) => track.active)?.[0]) await player.setTextTrack(byLanguage.get(selected).id);
      select.value = selected;
      control.hidden = false;
    } catch { control.hidden = true; }
  }
  async changeCaptionLanguage() {
    if (!this.player || !this.languageSelect || typeof this.player.setTextTrack !== 'function') return;
    const language = this.languageSelect.value;
    try {
      const tracks = await this.player.textTracks();
      const track = tracks.find(item => item.language.trim().toLowerCase() === language);
      await this.player.setTextTrack(track?.id ?? '');
      try {
        if (language) globalThis.localStorage?.setItem('jdlearn.video.language.v1', language);
        else globalThis.localStorage?.removeItem('jdlearn.video.language.v1');
      } catch { /* Optional browser preference. */ }
    } catch { this.message('captions_unavailable'); }
  }
  async request(task, values, keepalive = false) {
    if (this.config.preview) {
      const body = new URLSearchParams({course_id: this.config.course, version_id: this.config.version,
        lesson_id: this.config.lesson, [this.config.token]: '1'});
      const response = await this.transport(this.config.previewUrl, {method: 'POST', body,
        credentials: 'same-origin', cache: 'no-store', keepalive, headers: {'Accept': 'application/json'}});
      const json = await response.json();
      if (!response.ok || json.success !== true) { const error = new Error('request'); error.status = response.status; throw error; }
      return json.data;
    }
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
      if (!this.allowed() || generation !== this.generation) { if (!this.config.preview && grant.session_id) await this.request('close', {session_id: grant.session_id}); return; }
      // The PHP registry validates keys; Joomla plugins provide this conventional module path.
      if (grant.provider !== this.config.provider || !/^[a-z][a-z0-9_]{1,63}$/.test(grant.provider)) throw new Error('provider');
      this.tracking = !this.config.preview && grant.tracking !== false;
      this.session = this.tracking ? grant : null; this.sequence = 1; this.id = 0; this.queue = []; this.pending = null;
      this.started = performance.now(); this.previous = null; this.aborter = new AbortController();
      failure = 'startup_module';
      const moduleUrl = new URL(`../../plg_jlmsvideo_${grant.provider}/js/player.js`, import.meta.url);
      moduleUrl.search = new URL(import.meta.url).search;
      const adapter = await this.importer(moduleUrl.href);
      if (!this.allowed() || generation !== this.generation) { if (!this.config.preview && grant.session_id) await this.request('close', {session_id: grant.session_id}); return; }
      this.adapter = adapter; this.stopped = false;
      failure = 'startup_provider';
      const player = await adapter.create(this.root.querySelector('#jlms-player'), grant, (event, reason) => {
        if (event === 'error') { this.message(providerErrors.has(reason) ? reason : 'startup_provider'); this.stop(false); }
        else if (this.tracking) this.record(event).then(() => { if (['pause', 'ended'].includes(event)) this.flush(); });
      }, this.aborter.signal);
      if (this.stopped || generation !== this.generation || !this.allowed()) { await player.destroy(); return; }
      this.player = player;
      await this.configurePlaybackSpeed(player);
      await this.configureCaptionLanguage(player);
      if (this.tracking && grant.resume_ms) await player.seek(grant.resume_ms);
      if (this.tracking) await this.record('seeked');
      if (this.stopped || generation !== this.generation) return;
      if (this.tracking) { this.sampler = setInterval(() => this.record('position'), 1000); this.schedule(); this.scheduleRenew(); }
      this.message('ready');
    } catch (error) {
      if (generation === this.generation) {
        this.message(error.status === 401 || error.status === 403 || error.status === 404 ? 'denied' : providerErrors.has(error.message) ? error.message : failure);
        await this.stop(false);
      }
    }
    finally { this.starting = false; }
  }
  record(type) {
    if (!this.tracking) return Promise.resolve();
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
      if (type === 'ratechange' && this.rateSelect) this.rateSelect.value = String(sample.rate_milli / 1000);
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
    this.message('saving');
    if (!this.pending) this.pending = {session_id: this.session.session_id, source_revision: this.session.source_revision,
      sequence: this.sequence, duration_ms: this.duration, events_json: JSON.stringify(this.queue.splice(0))};
    try {
      const result = await this.request('save', this.pending, keepalive);
      this.sequence = result.next_sequence; this.pending = null;
      this.message(result.lesson_completed ? 'completed' : 'saved');
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
      await this.configurePlaybackSpeed(player);
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
    this.tracking = false;
    const rateControl = this.root.querySelector('#jlms-video-speed-control');
    if (rateControl) rateControl.hidden = true;
    // On explicit stop save already observed samples, then expire the server session.
    if (save && !this.saving && this.session) {
      await this.flush();
      if (!this.pending) { try { await this.request('close', {session_id: this.session.session_id}); } catch {} }
    }
  }
  async destroy() {
    await this.stop(true);
    this.rateSelect?.removeEventListener('change', this.onRateChange);
    this.languageSelect?.removeEventListener('change', this.onLanguageChange);
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
