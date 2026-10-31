/** Serialised mutations retain their UUID and exact body across network retries. No answer keys or local storage. */
export class QuizTransport {
    constructor(urls, common, request = globalThis.fetch.bind(globalThis), pause = ms => new Promise(r => setTimeout(r, ms))) {
        this.urls = urls; this.common = common; this.request = request; this.pause = pause;
    }
    async send(operation, payload) {
        const body = new URLSearchParams({...this.common, ...payload}).toString();
        for (let retry = 0; ; retry++) {
            let response;
            try { response = await this.request(this.urls[operation], {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body}); }
            catch (error) { if (retry >= 3) throw error; await this.pause(Math.min(8000, 1000 * 2 ** retry)); continue; }
            if (response.status >= 500 && retry < 3) { await this.pause(Math.min(8000, 1000 * 2 ** retry)); continue; }
            const result = await response.json();
            if (!response.ok || result.error) { const error = new Error(result.message || 'Quiz request failed'); error.status = response.status; throw error; }
            return result.data;
        }
    }
}

/** Accessible hints only; server deadlines remain authoritative. */
export function quizWarning(left, previous, total) {
    if (left === null || left <= 0) return null;
    if (left <= 60 && previous > 60) return 'one_minute';
    if (total >= 300 && left <= 300 && previous > 300) return 'five_minutes';
    return null;
}

export function initialiseQuiz(options, document = globalThis.document, location = globalThis.location) {
    const root = document.getElementById('jlms-quiz'); if (!root) return;
    const form = document.getElementById('jlms-quiz-form'), status = document.getElementById('jlms-quiz-status');
    const transport = new QuizTransport(options.urls, {enrollment_id: options.enrollment, lesson_id: options.lesson, [options.token]: 1});
    const uuid = () => globalThis.crypto.randomUUID();
    let attempt = options.attempt, busy = false, halted = false, pending = null, submitted = false;
    let baseline = '', clockStart = performance.now(), serverAnchor = options.server_now, savedAt = new Date(options.server_now * 1000);
    let pollBusy = false, pollAfter = 0, pollBackoff = 1, previousLeft = Infinity;
    const labels = options.labels, clockNow = () => serverAnchor + (performance.now() - clockStart) / 1000;
    const read = () => {
        const answers = {};
        for (const field of form.querySelectorAll('[data-question-id]')) answers[field.dataset.questionId] = [...field.querySelectorAll('input:checked')].map(x => x.value).sort();
        return JSON.stringify(answers);
    };
    baseline = read();
    const message = key => { status.textContent = labels[key] + (key === 'saved' ? ' ' + savedAt.toLocaleTimeString() : ''); };
    const fail = error => {
        // An uncertain mutation stays immutable until retried; a confirmed rejection stops this tab.
        if ([400,401,403,404,409,413,422,429].includes(error.status)) { halted = true; pending = null; }
        message(error.status === 409 ? 'conflict' : 'error');
    };
    const reload = () => { const url = new URL(location.href); url.searchParams.delete('attempt_id'); location.assign(url.href); };
    const applyTiming = data => {
        const next = data.attempt;
        if (attempt?.deadline !== null && next.deadline !== null && next.deadline > attempt.deadline) {
            previousLeft = Infinity;
            if (document.getElementById('jlms-quiz-warning')) document.getElementById('jlms-quiz-warning').textContent = '';
            if (document.getElementById('jlms-quiz-extension')) document.getElementById('jlms-quiz-extension').textContent = `${labels.extended} ${data.extension_seconds} s`;
        }
        attempt = next; serverAnchor = data.server_now; clockStart = performance.now();
        if (document.getElementById('jlms-quiz-duration')) document.getElementById('jlms-quiz-duration').textContent = String(data.time_seconds);
    };
    const sync = async () => {
        if (pollBusy || halted || clockNow() < pollAfter) return false;
        pollBusy = true;
        try {
            const url = new URL(options.urls.state, location.href);
            url.searchParams.set('enrollment_id', options.enrollment); url.searchParams.set('lesson_id', options.lesson);
            url.searchParams.set('attempt_id', attempt.id); url.searchParams.set('compact', '1');
            const response = await globalThis.fetch(url, {credentials:'same-origin', cache:'no-store'}); const result = await response.json();
            if (!response.ok || result.error) { const error = new Error('Quiz state unavailable'); error.status = response.status; throw error; }
            const next = result.data.attempt;
            if (next.state !== 'open') { reload(); return true; }
            if (next.id !== attempt.id || next.revision !== attempt.revision) { const error = new Error('Quiz changed'); error.status = 409; throw error; }
            // No answer values or baseline are replaced by a read. Dirty selections remain in the form.
            applyTiming(result.data); pollBackoff = 1; return true;
        } catch (error) { fail(error); pollBackoff = Math.min(30, pollBackoff * 2); return false; }
        finally { pollAfter = clockNow() + pollBackoff; pollBusy = false; }
    };
    const save = async (submit = false) => {
        if (busy || pollBusy || halted || !attempt || attempt.state !== 'open') return false;
        busy = true; message('saving');
        try {
            if (pending) {
                const data = await transport.send(pending.operation, pending.payload); baseline = pending.payload.answers_json; pending = null;
                applyTiming(data); savedAt = new Date(data.server_now * 1000);
                if (attempt.state !== 'open') { reload(); return true; }
            }
            const answers = read();
            if (!submit && answers === baseline) { if (attempt.deadline === null || await sync()) message('saved'); return true; }
            const payload = {attempt_id: attempt.id, revision: attempt.revision, mutation_id: uuid(), answers_json: answers};
            pending = {operation: submit ? 'submit' : 'save', payload};
            const data = await transport.send(pending.operation, pending.payload); pending = null; baseline = answers; applyTiming(data); savedAt = new Date(data.server_now * 1000);
            message('saved');
            if (submit || attempt.state !== 'open') reload();
            return true;
        } catch (error) { fail(error); return false; }
        finally { busy = false; }
    };
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || halted || submitted) return;
        if (!globalThis.confirm(labels.confirm)) return;
        submitted = true; if (!await save(true)) submitted = false;
    });
    document.getElementById('jlms-quiz-save')?.addEventListener('click', () => save());
    for (const button of form.querySelectorAll('.jlms-quiz-clear')) button.addEventListener('click', () => {
        for (const input of button.closest('fieldset').querySelectorAll('input')) input.checked = false;
    });
    document.getElementById('jlms-quiz-start')?.addEventListener('click', async event => {
        if (busy || halted) return; busy = true; event.currentTarget.disabled = true;
        try {
            pending ??= {operation: 'start', payload: {mutation_id: uuid()}};
            await transport.send(pending.operation, pending.payload); pending = null; reload();
        } catch (error) { fail(error); event.currentTarget.disabled = halted; }
        finally { busy = false; }
    });
    if (attempt?.state === 'open') message('saved');
    const tick = globalThis.setInterval(async () => {
        if (halted || !attempt || attempt.state !== 'open') return;
        const left = attempt.deadline === null ? null : Math.max(0, Math.ceil(attempt.deadline - clockNow()));
        const warning = quizWarning(left, previousLeft, (attempt.deadline ?? 0) - (attempt.started_at ?? 0));
        if (warning && document.getElementById('jlms-quiz-warning')) document.getElementById('jlms-quiz-warning').textContent = labels[warning];
        if (left !== null) previousLeft = left;
        document.getElementById('jlms-quiz-timer').textContent = left === null ? '' : `${Math.floor(left / 60)}:${String(left % 60).padStart(2,'0')}`;
        if (left === 0 && !busy && !pollBusy && clockNow() >= pollAfter) {
            // Read the authoritative deadline, sending no new answers at local expiry.
            await sync();
        }
    }, 1000);
    const autosave = globalThis.setInterval(() => { if (attempt?.state === 'open' && (attempt.deadline === null || clockNow() < attempt.deadline)) save(); }, 20000);
    globalThis.addEventListener('pagehide', () => { clearInterval(tick); clearInterval(autosave); });
}
if (globalThis.document && globalThis.Joomla) initialiseQuiz(Joomla.getOptions('com_jlms.quiz'));
