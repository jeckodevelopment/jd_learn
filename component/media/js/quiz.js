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

export function initialiseQuiz(options, document = globalThis.document, location = globalThis.location) {
    const root = document.getElementById('jlms-quiz'); if (!root) return;
    const form = document.getElementById('jlms-quiz-form'), status = document.getElementById('jlms-quiz-status');
    const transport = new QuizTransport(options.urls, {enrollment_id: options.enrollment, lesson_id: options.lesson, [options.token]: 1});
    const uuid = () => globalThis.crypto.randomUUID();
    let attempt = options.attempt, busy = false, halted = false, pending = null, submitted = false;
    let baseline = '', clockStart = performance.now();
    const labels = options.labels, clockNow = () => options.server_now + (performance.now() - clockStart) / 1000;
    const read = () => {
        const answers = {};
        for (const field of form.querySelectorAll('[data-question-id]')) answers[field.dataset.questionId] = [...field.querySelectorAll('input:checked')].map(x => x.value).sort();
        return JSON.stringify(answers);
    };
    baseline = read();
    const message = key => { status.textContent = labels[key] + (key === 'saved' ? ' ' + new Date().toLocaleTimeString() : ''); };
    const fail = error => {
        // An uncertain mutation stays immutable until retried; a confirmed rejection stops this tab.
        if ([400,401,403,404,409,413,422,429].includes(error.status)) { halted = true; pending = null; }
        message(error.status === 409 ? 'conflict' : 'error');
    };
    const reload = () => { const url = new URL(location.href); url.searchParams.delete('attempt_id'); location.assign(url.href); };
    const save = async (submit = false) => {
        if (busy || halted || !attempt || attempt.state !== 'open') return false;
        busy = true; message('saving');
        try {
            if (pending) {
                const data = await transport.send(pending.operation, pending.payload); baseline = pending.payload.answers_json; pending = null;
                attempt = data.attempt;
                if (attempt.state !== 'open') { reload(); return true; }
            }
            const answers = read();
            if (!submit && answers === baseline) { message('saved'); return true; }
            const payload = {attempt_id: attempt.id, revision: attempt.revision, mutation_id: uuid(), answers_json: answers};
            pending = {operation: submit ? 'submit' : 'save', payload};
            const data = await transport.send(pending.operation, pending.payload); pending = null; baseline = answers; attempt = data.attempt;
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
    let pollBusy = false;
    const tick = globalThis.setInterval(async () => {
        if (halted || !attempt || attempt.state !== 'open') return;
        const left = attempt.deadline === null ? null : Math.max(0, Math.ceil(attempt.deadline - clockNow()));
        document.getElementById('jlms-quiz-timer').textContent = left === null ? '' : `${Math.floor(left / 60)}:${String(left % 60).padStart(2,'0')}`;
        if (left === 0 && !busy && !pollBusy) {
            // Submit no new answers at expiry. The server grades only previously saved answers.
            pollBusy = true;
            try {
                const url = new URL(options.urls.state, location.href); url.searchParams.set('enrollment_id', options.enrollment); url.searchParams.set('lesson_id', options.lesson);
                const response = await globalThis.fetch(url, {credentials:'same-origin', cache:'no-store'}); const result = await response.json();
                if (!response.ok || result.error) { const error = new Error('Quiz expired'); error.status = response.status; throw error; }
                if (result.data.attempt?.state !== 'open') reload();
            } catch (error) { fail(error); }
            finally { pollBusy = false; }
        }
    }, 1000);
    const autosave = globalThis.setInterval(() => { if (attempt?.state === 'open' && (attempt.deadline === null || clockNow() < attempt.deadline)) save(); }, 20000);
    globalThis.addEventListener('pagehide', () => { clearInterval(tick); clearInterval(autosave); });
}
if (globalThis.document && globalThis.Joomla) initialiseQuiz(Joomla.getOptions('com_jlms.quiz'));
