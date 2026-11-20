# SEC-P1 — matrice di dispatch e superficie controller JD Learn 1.0.0

**Baseline esaminata:** codice `main` dopo la 0.64.13-dev, tranche correttiva 0.64.14-dev del 20/11/2026. **Stato: verifica di dispatch strutturale, non audit SEC-P1 concluso.** Questa matrice copre i 37 file `*Controller.php` distribuiti (11 frontend, 26 amministrazione), non gli endpoint dei plugin, l'intero router, i model, i task Scheduler, né tutte le chiamate ad API esterne.

## Inventario degli entry point Joomla

`tests/sec-p1-controller-gates.py` confronta i file con un elenco approvato, i loro metodi `public` e i task esplicitamente ammessi nei metodi `execute()`. Il test fallisce per metodi/task aggiunti senza revisione. Le due eccezioni deliberate sono `DisplayController` frontend e backend, che limitano le `view`, e `LessonController` frontend, che eredita il dispatch da `EnrollmentController` con distinzione esplicita della classe. **L'inventario del router non prova le autorizzazioni dei servizi.**

| Area | Controller | Task di ingresso censiti | Controllo oggetto/ruolo e prove residue |
|---|---|---|---|
| Site | Audio | save | Iscrizione, consenso media, CSRF, replay; HTTP e ruoli da completare |
| Site | Video | playback, renew, save, close | Sessione/ownership/revision e replay; HTTP e ruoli da completare |
| Site | Quiz | start, save, submit, state | Attempt ownership, revisione, grading e timer; HTTP e ruoli da completare |
| Site | Enrollment | start, resume, request | Idempotenza, quota e proprietà iscrizione; HTTP e ruoli da completare |
| Site | Lesson | begin, complete (ereditati) | Eredita dispatch di Enrollment ma non gli altri task; matrice HTTP pendente |
| Site | Mailconsent | request, confirm, revoke, revokelink | Token, HTTP, revoca, email aggiornata; SMTP/HTTP manuale pendente |
| Site | Display | display e view allowlist | Visibilità contenuti, ACL, cache e output; test E2E per ruoli pendenti |
| Site | Attachment | download, audio, inline, preview, publicpreview, publiclessonpreview | IDOR, pubblicazione, file HEAD/Range; esistono prove mirate, matrice completa pendente |
| Site | Media | stream | Video locale, preview, HEAD/Range; matrice HTTP completa pendente |
| Site | Certificate | download | Solo proprietario e PDF privato; prove mirate, E2E completo pendente |
| Site | Teacher | edit, save, apply, cancel | FormController nativo, allowEdit/allowAdd; ACL negative complete pendenti |
| Administrator | Quiz | save, delete | ACL corso/banca domande e CSRF; matrice completa pendente |
| Administrator | Course | add, edit, apply, save, cancel, loadhistory, programme, programmerules, trash, restore, purge | ACL oggetto, archiviazione e dipendenze; matrice completa pendente |
| Administrator | Lesson | metadata, save, delete, reorder, duplicate, move | Lock versioni e autorizzazione corso; matrice completa pendente |
| Administrator | Courses | programme | Operazioni collettive POST/CSRF e scope; matrice completa pendente |
| Administrator | Teacher | add, edit, apply, save, cancel, loadhistory, trash, restore, publish, unpublish, purge | Permessi oggetto, dipendenze, ACL; matrice completa pendente |
| Administrator | Pathway | add, edit, apply, save, cancel, trash, restore, archive, publish, unpublish, purge | ACL oggetto e associazioni; matrice completa pendente |
| Administrator | Quiztime | save | Timer e autorizzazione; matrice completa pendente |
| Administrator | Material | upload, reuse, save, delete, scan, download, preview | Upload, MIME, antimalware, IDOR; prove HTTP mirate, matrice completa pendente |
| Administrator | Videofile | upload, scan | Upload e scanner, ACL; matrice completa pendente |
| Administrator | Mailqueue | display, reconcile | Audit globale separato da core.manage; prove mirate, SMTP/HTTP pendenti |
| Administrator | Curriculum | createversion, restoreversion, saveteachingrules, transition, duplicatecourse, saveorder | Lock, snapshot e ACL corso; matrice completa pendente |
| Administrator | Quizextend | apply | ACL e modifica tempo; matrice completa pendente |
| Administrator | Quizreview | cancel | ACL e revoca tentativo; matrice completa pendente |
| Administrator | Certificate | download, regenerate, recoverhistorical, recovermissing | ACL specifica e file privati; prove negative mirate, E2E pendente |
| Administrator | Questionbank | save, checkout, checkin, duplicate, delete, trash, restore | Test metodo/CSRF mirati; ACL/oggetto completi pendenti |
| Administrator | Coursemessage | preview, test, queue | Destinatari cross-course e CAS; prove mirate, SMTP/HTTP pendenti |
| Administrator | Coursepreview | playback | Preview autore per corso; matrice completa pendente |
| Administrator | Enrollmentstate | apply | Roster, stato e CSRF; matrice completa pendente |
| Administrator | Courseoperations | apply | Operazioni corso e lock; matrice completa pendente |
| Administrator | Manualenrollment | apply | Roster, quota, transazioni e ACL; matrice completa pendente |
| Administrator | Enrollmentapproval | decide | Lock edition/decisione; matrice multi-ruolo completa pendente |
| Administrator | Courseannouncement | notify | Destinatari e preferenze; test invio end-to-end pendenti |
| Administrator | Enrollmentmigration | apply | Cross-course e rollback; matrice completa pendente |
| Administrator | Categoryreassignment | reassign | **Fix tranche:** dispatch `execute()` 404 per altri task; POST/CSRF e ACL già esistenti; prova Joomla CLI negativa, HTTP manuale pendente |
| Administrator | Display | display e view allowlist | Controlli view, accessi per scheda, no leakage; E2E per ruoli pendente |
| Administrator | Module | duplicate, save, delete, reorder | Dispatch derivato da Curriculum, ACL corso e lock; matrice completa pendente |

## Finding SEC-P1/DISPATCH-CATEGORY

Prima della tranche, il solo controller amministrativo mutativo `CategoryreassignmentController` non definiva `execute()` e lasciava al `BaseController` Joomla la selezione dei metodi. Il task `reassign` era già protetto da POST, CSRF e `CategoryReassignmentService`; **non è stato osservato un bypass di tali controlli**. La correzione aggiunge un confine di dispatch esplicito: solo `reassign` passa al controller Joomla; altri task sono respinti con 404 prima di chiamare metodi ereditati.

`tests/joomla-categories.php` esercita la classe distribuita Joomla e richiede 404 per `display`, `save`, `delete`, `purge`, `getModel`, `__construct` e un task sconosciuto, senza costruire né mutare la richiesta. `tests/sec-p1-controller-gates.py` fissa tutti i 37 controller, i loro metodi pubblici e le task allowlist; una modifica di superficie provoca errore in CI fino a revisione consapevole. **Non è un penetration test o una prova HTTP dell'intero router.** Il collaudo HTTP con sessione e ruoli resta aperto.

## Matrice minima per la chiusura formale SEC-P1

Per ogni riga della tabella bisogna documentare la prova effettiva di: metodo HTTP e CSRF (mutazioni); autorizzazione fresca del soggetto e ACL dell'oggetto; isolamento cross-course/cross-user; stato precedente e successivo nel DB, ripetizione e concorrenza quando pertinente; serializzazione output senza dati privati; errore 400/401/403/404/409 appropriato. Per endpoint di file: anche GET/HEAD/Range e caching. Per email e Scheduler: destinatario, token, code/lease, comportamento retry e SMTP reale isolato.

Resta inoltre da esaminare **per evento** il dispatch dei plugin Privacy/User/Task/Actionlog, il router Joomla, l'intero percorso di autorizzazione dei modelli e delle query, la superficie installazione/update e l'integrazione provider media. Le prove vanno eseguite in fixture disposable su una build candidata, confrontando guest, proprietario, estraneo, docente/operatore per corso, amministratore senza permesso specifico e amministratore autorizzato. Evidenziare separatamente prove statiche, CI integrazione e collaudo manuale.

**Decisione gate:** SEC-P1 **APERTO**, SEC-P5 **APERTO**. Non promuovere i due criteri mancanti SEC-P1 né aumentare la percentuale minima 57,48% sulla base di questa tranche. Non dichiarare sicurezza completa o certificazione OWASP ASVS/GDPR.
