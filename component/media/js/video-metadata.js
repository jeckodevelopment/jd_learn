/** Authoring only: official provider preview, no student session or progress writes. */
export const validDuration = value => Number.isInteger(value) && value >= 1000 && value <= 28800000;
const providerFailures = new Set(['youtube_csp', 'youtube_sdk', 'youtube_referrer', 'youtube_embed', 'youtube_timeout']);
export class VideoMetadataEditor {
  constructor(form, config, host = window, transport = (...args) => fetch(...args), importer = url => import(url)) {
    Object.assign(this, {form, config, host, transport, importer}); this.generation = 0;
    this.field = name => form.elements.namedItem(`jform[${name}]`);
    this.root = form.querySelector('#jlms-video-metadata');
    this.status = this.root.querySelector('[role="status"]'); this.container = this.root.querySelector('[data-preview]');
    this.change = () => this.schedule();
    for (const name of ['kind','provider_key','source_reference','privacy_hash','duration_mode']) { this.field(name)?.addEventListener('input', this.change); this.field(name)?.addEventListener('change', this.change); }
    this.root.querySelector('button').addEventListener('click', this.change);
    this.consent = () => this.schedule(); host.addEventListener('jlms:consent-changed', this.consent);
    this.hide = () => this.clear(); host.addEventListener('pagehide', this.hide);
    this.show = event => { if (event.persisted) this.schedule(); }; host.addEventListener('pageshow', this.show);
    this.schedule();
  }
  message(key) { this.status.textContent = this.config.labels[key] || this.config.labels.failed; }
  clear() {
    ++this.generation; clearTimeout(this.timer); clearInterval(this.poller); this.abort?.abort();
    const player = this.player; this.player = null; Promise.resolve(player?.destroy()).catch(() => {});
    this.container.replaceChildren(); this.container.hidden = true;
    this.field('duration_ticket').value = ''; this.field('sdk_duration_ms').value = '';
  }
  allowed(provider) { return this.config.policy === 'embed' || (this.config.policy === 'site_consent' && this.host.JlmsConsent?.isAllowed(provider) === true); }
  schedule() {
    this.clear(); this.root.hidden = this.field('kind').value !== 'video'; if (this.root.hidden) return;
    const provider = this.field('provider_key').value;
    if (provider === 'html5') {
      const duration = Math.round(Number(this.field('duration_seconds').value) * 1000);
      if (validDuration(duration)) this.status.textContent = this.config.labels.detected.replace('%s', String(duration / 1000));
      else this.message('local');
      return;
    }
    if (this.field('duration_mode').value === 'manual') { this.message('manual'); return; }
    if (!this.allowed(provider)) { this.message('consent'); return; }
    if (!this.field('source_reference').value.trim()) { this.message('source'); return; }
    this.message('loading'); const generation = this.generation;
    this.timer = setTimeout(() => this.probe(generation), 650);
  }
  async probe(generation) {
    const provider = this.field('provider_key').value;
    if (!['youtube','vimeo','cloudflarestream'].includes(provider) || !this.allowed(provider)) return;
    this.abort = new AbortController();
    let stage = 'request';
    try {
      const names = new Set(['course_id','version_id','expected_version_revision','module_id','id','revision','provider_key','source_reference','privacy_hash'].map(name => `jform[${name}]`));
      const body = new URLSearchParams();
      for (const [name, value] of new FormData(this.form)) if (names.has(name) || /^[a-f0-9]{32}$/.test(name)) body.append(name, value);
      body.set('task', 'lesson.metadata');
      const response = await this.transport(this.config.url, {method:'POST', body,
        credentials:'same-origin', cache:'no-store', signal:this.abort.signal, headers:{Accept:'application/json'}});
      stage = 'response';
      const result = await response.json();
      if (!response.ok || result.success !== true) {
        const error = new Error('metadata');
        // The Joomla endpoint emits already-localised, allowlisted 4xx messages.
        // Never display a raw 5xx response or arbitrary exception in the editor.
        error.safeKey = response.status >= 500 ? 'server_unavailable' : null;
        if (response.status >= 400 && response.status < 500 && typeof result.message === 'string'
          && result.message.length > 0 && result.message.length <= 400) error.safeMessage = result.message;
        throw error;
      }
      if (generation !== this.generation || !this.allowed(provider)) return;
      const data = result.data; if (data.provider !== provider || !/^[a-f0-9]{64}$/.test(data.ticket)) throw Error('source');
      const accept = duration => {
        if (generation !== this.generation || !validDuration(duration)) return false;
        this.field('duration_ticket').value = data.ticket;
        this.field('sdk_duration_ms').value = String(duration);
        this.status.textContent = this.config.labels.detected.replace('%s', String(duration / 1000)); return true;
      };
      if (data.duration_ms !== null) { if (!accept(data.duration_ms)) throw Error('duration'); return; }
      stage = 'adapter';
      const url = new URL(data.adapter_url, this.host.location.href);
      if (url.origin !== this.host.location.origin || !url.pathname.endsWith(`/media/plg_jlmsvideo_${provider}/js/player.js`)) throw Error('adapter');
      url.search = new URL(import.meta.url).search;
      const adapter = await this.importer(url.href);
      if (generation !== this.generation || !this.allowed(provider)) return;
      this.container.hidden = false;
      const sample = async () => { if (this.player && generation === this.generation) { const value = await this.player.sample(); return accept(value.duration_ms); } return false; };
      stage = 'player';
      const player = await adapter.create(this.container, data, (event, reason) => {
        if (generation !== this.generation) return;
        if (event === 'error') { this.clear(); this.message(providerFailures.has(reason) ? reason : 'failed'); }
        else sample().catch(() => { if (generation === this.generation) this.message('failed'); });
      }, this.abort.signal);
      if (generation !== this.generation || !this.allowed(provider)) { await player.destroy(); return; }
      this.player = player; this.message('play'); await sample();
      let polls = 0;
      this.poller = setInterval(() => { if (++polls > 180) { clearInterval(this.poller); return; } sample().catch(() => { if (generation === this.generation) this.message('failed'); }); }, 500);
     } catch (error) {
      if (generation === this.generation && error.name !== 'AbortError') {
        this.clear();
        if (error.safeMessage) this.status.textContent = error.safeMessage;
        else this.message(providerFailures.has(error.message) ? error.message
          : error.message === 'player timeout' ? 'youtube_timeout'
          : error.safeKey || (stage === 'request' ? 'network_error'
            : stage === 'response' ? 'response_error'
            : stage === 'adapter' ? 'adapter_error'
            : stage === 'player' ? 'player_error' : 'failed'));
      }
    }
  }
}
if (typeof document !== 'undefined') {
  const form = document.getElementById('jlms-lesson-form'), config = globalThis.Joomla?.getOptions('com_jlms.videoMetadata');
  if (form?.querySelector('#jlms-video-metadata') && config) new VideoMetadataEditor(form, config);
}
