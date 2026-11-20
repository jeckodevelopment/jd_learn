# SEC-P1 — Threat model, revisione del codice e registro finding per JD Learn 1.0.0

**Data revisione:** 20 novembre 2026. **Build candidata:** 0.64.17-dev, PR di consolidamento SEC-P1. **Obiettivo del documento:** produrre il deliverable SEC-P1 previsto in `SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md`, distinto dai gate SEC-P2 (hardening/supply chain), SEC-P3 (privacy e retention), SEC-P4 (cookie/CMP) e SEC-P5 (E2E/pentest della release candidate). Non è una certificazione OWASP ASVS né una promessa che ogni configurazione di hosting sia sicura.

## 1. Confini e minacce

**Asset:** dati corsisti e iscrizioni, progressi/audio/video, quiz e risposte, documenti PDF attestati, materiali privati, dati di docenti e titoli pubblici, consenso email e token hashati, credenziali del provider remoto, coda e log, copie/installazioni/update.

**Soggetti con diversi privilegi:** ospite, studente proprietario, studente di altro corso, docente, gestore di un corso ma non di altri, amministratore con `core.manage` ma senza permessi specialistici, Super User, processo Joomla Scheduler, personale che invia comunicazioni, destinatario di conferma email, provider esterno. L'oggetto `User` in memoria può divenire obsoleto dopo blocco, rimozione gruppi o nuova ACL.

**Confini di trust:**
1. Browser (query, form, cookie, token, upload, Range, player) → Joomla router/controller; **non fidarsi di identità/ID/stati o hash proposti dal client**.
2. Controller → servizi applicativi: permessi attuali per corso, utente, versione e lezione devono essere verificati prima di lettura privata e nuovamente prima della mutazione.
3. Servizi → MySQL/MariaDB: binding di parametri, allowlist identificatori, row lock/transaction, idempotenza e audit; non assumere l'immutabilità di uno stato letto prima del lock.
4. Uploader/processi → filesystem e PHP streamer: nome MIME e dimensione verificati, chiave casuale, file non esposti tramite webserver, Range limitato e disabilitazione di esecuzione/percorsi arbitrari.
5. Scheduler/consenso → outbox e SMTP: privilegi del proprietario attuali, destinatario Joomla corrente, redazione dati non più necessari, stati atomici, consenso valido e recipient-ID obbligatorio.
6. App → provider video via HTTPS: destinazioni fisse, redirect disabilitati, riferimenti video normalizzati, token non nei log, timeout/risposte limitati.
7. Backend/Frontend → HTML, ricerca Finder e PDF: encoding contestuale, sanitizzazione HTML e proiezione dei soli dati pubblicabili.
8. Plugin Joomla e package → runtime: entrypoint e nuovi hook revisionati, namespace/manifest verificati, segreti estranei al package.

**Rischi attaccante considerati:** enumerazione ID, ACL stale e cross-course, CSRF/replay, XSS stored/reflected, SQL injection, SSRF, traversal, MIME/polyglot, file privati e contenuti non pubblicati, deserializzazione/comandi, disclosure via logging, race/TOCTOU, code e invii duplicati, token di consenso rubati o riutilizzati.

## 2. Metodo ed evidenze riproducibili

- **Copertura meccanica di tutto il runtime PHP distribuito:** `tests/sec-p1-systematic-source-gate.py` scandisce tutti i sorgenti PHP sotto `component/`, `plugins/` e `libraries/` (baseline ~340 file), rifiuta sink di esecuzione/deserializzazione non previsti e I/O diretto da superglobali, permette `proc_open` solo nel validatore ffprobe con argv ad array/protocolli locali e `HttpFactory` solo nel trasporto Cloudflare con HTTPS, hostname fisso, niente redirect e timeout.
- **Endpoint e task:** `tests/sec-p1-controller-gates.py` classifica esattamente i 37 controller Joomla, i public methods e i task dispatch; nuovi controller/metodi non censiti fanno fallire la CI, con verifica strutturale POST/token. `tests/sec-p1-cross-surface-gate.py` copre i 10 plugin, i relativi eventi pubblici e i 3 task Scheduler.
- **Percorsi applicativi sensibili esaminati:** `LearningService`, `EnrollmentRosterService`, `EnrollmentStateService`, `EnrollmentApprovalService`, `EnrollmentMigrationService`, `ManualEnrollmentService`, `CourseCommunicationService`, `MailQueue`, `QuizAttemptService`, `QuizReviewService`, `QuizTimeService`, `CourseEditorAccess`, `CategoryReassignmentService`, `TeacherProfiles`, `PublicationService`, `CurriculumService`, `CatalogService`, `MaterialService`, `ProtectedMaterialStorage`, `ProtectedVideoStorage`, `LocalVideoService`, `MaterialDownload`, `VideoFilePolicy`, `CertificateService`, `CourseAnnouncementConsent`, componenti Finder/Privacy/Task/video. Questa lista è il perimetro di **review approfondita delle decisioni trust/sink**, non l'affermazione di una verifica manuale riga per riga dei 340 file.
- **Evidenza comportamentale:** `tests/joomla-roster.php`, `tests/joomla-enrollment-state.php`, `tests/joomla-manual-enrollment.php`, `tests/joomla-enrollment-migration.php`, `tests/joomla-categories.php`, `tests/joomla-teachers.php`, `tests/joomla-video.php`, `tests/materials-http.py`, `tests/enrollment-state-http.py`, `tests/manual-enrollment-http.py` e gli altri test di servizi già presenti. L'esistenza di una suite non equivale a un pass fino alla CI del commit candidato.
- **Matrice per macro-superficie:** `SEC_P1_THREAT_CONTROL_EVIDENCE_1.0.0.csv` è il registro minaccia → controllo → test/evidenza → rischio e successiva verifica E2E. Il contenuto HTTP/manuale pendente è pianificato per **SEC-P5**, non un motivo per lasciare indefinitamente aperta la review di codice SEC-P1.

## 3. Giudizio di revisione per superficie

| Area | Decisione di trust e sink | Evidenza primaria | Finding/security review |
|---|---|---|---|
| 37 controller e dispatch Joomla | POST + CSRF per mutazioni, allowlist task e controlli in servizio | `sec-p1-controller-gates.py`, test HTTP | Controlli presenti; casi E2E per tutte le ACL rimandati a SEC-P5 |
| Catalogo, categorie e Finder | Proiezione metadata pubblici, categoria e livello 1 per indicizzazione pubblica; altre visibilità via `CatalogService` | `CatalogService`, plugin Finder | Nessun bypass confermato dalla review selezionata |
| Iscrizioni, progressi e aula | Identity fresh e ownership/enrollment-scoping; lock prima di mutate | `LearningService`, roster, fixture HTTP | Stale identity corretta, regression presenti |
| Quiz, banca, scoring | Tentativo legato all'iscrizione e versione, lock e hash di retry | `QuizAttemptService`, quiz test DB/HTTP | Nessun nuovo bypass confermato |
| Materiali, upload e file | MIME/extension policy, asset immutabile, file crittografati nel managed root, path non pubblici e Range bounded | `MaterialService`, `ProtectedMaterialStorage`, `MaterialDownload`, `materials-http.py` | Nessun path traversal/IDOR confermato in percorsi analizzati |
| Video locale | Il file MP4 in chiaro deve stare esclusivamente **fuori** dal webroot | `ProtectedVideoStorage`, `joomla-video.php` | SEC-P1/VIDEO-ROOT: fallback pubblico con parametro vuoto corretto in questa PR |
| Video Cloudflare ed esterni | Endpoint API Cloudflare fisso, ID normalizzato, redirect negato; HLS/vimeo/youtube tramite adapter | `Provider.php`, `VideoFilePolicy`, source gate | Nessuna SSRF confermata; probe/hosting provider reali in SEC-P5 |
| Docenti, percorsi e categorie | Identity ricaricata, ACL course/category/pathway | `TeacherProfiles`, `CategoryReassignmentService`, fixture DB | Finding stale actor corretti nelle PR precedenti |
| Attestati | Lookup certificato nel perimetro del corsista, protezione download, ACL admin | `CertificateService`, controller e fixture | Gestione admin fresh già rafforzata |
| Email, consenso, annunci | Token casuali monouso hashati; destinatario Joomla riletto prima dell'invio; coda globale con ACL specifica | `CourseAnnouncementConsent`, `MailQueue`, `MailQueueAuditService` | Referrer, audit note e global ACL corretti |
| Comunicazioni corso e job | Owner/corso freschi, destinatari scoping, lease, idempotenza su payload originale | `CourseCommunicationService`, `joomla-roster.php` | SEC-P1/COMMUNICATION-REPLAY corretto nella PR #810 |
| Plugin Privacy, User, Actionlog, Finder, Task | Subscriber Joomla e scope; log minimizzati, transazione su rimozione; hook/versioni esplicite | `sec-p1-cross-surface-gate.py`, codice plugin | Privacy retention completa appartiene anche a SEC-P3 |
| Scheduler | 3 task e limiti 1–100, lease e retry; non usare attore client per eseguire batch | `plugins/task/jlms`, `MailQueue` | Concorrenza multi-processo parziale, prove load/SMTP SEC-P5 |
| Package/update/segreti | Manifests, 12 zip, build versionata, credenziali remote tramite environment | `tools/build.py`, `tools/check.py`, workflow CI | Secret audit/supply-chain finale incluso SEC-P2 |

## 4. Registro finding — severità, correzione e controprova

| ID | Severità potenziale | Risultato della review | Azione / prova | Stato per SEC-P1 |
|---|---|---|---|---|
| CONSENT-REFERRER | Bassa: URL di conferma in Referer | Token one-use su GET richiedeva protezione Referer | Header `Referrer-Policy: no-referrer`, `X-Robots-Tag`; frontend-preferences | Mitigato, verifica reale del browser in SEC-P5 |
| CATEGORY-ACTOR | Media: ACL stale | Operatore bloccato poteva conservare decisione precedente | `CategoryReassignmentService` refresh + `joomla-categories.php`; PR #775 | Mitigato |
| TEACHER-FRESH-IDENTITY | Media: ACL stale | `TeacherProfiles::canEdit` si fidava di `User` precedente | Identità fresca + `joomla-teachers.php`; PR #777 | Mitigato |
| MAILQUEUE-GLOBAL-ACL | Alta: disclosure cross-course | `core.manage` non bastava per coda globale | `jlms.audit.view` obbligatorio + `joomla-roster.php`; PR #781 | Mitigato |
| MAIL-RECONCILIATION-NOTE | Bassa: conservazione testo sensibile | Nota di audit duplicata in diagnostici | Marker `last_error` generico; PR #783 | Mitigato |
| COMMUNICATION-REPLAY | Media: retry falso positivo | Stessa mutation UUID con payload diverso otteneva job precedente | Payload canonico e confronto `hash_equals`, 409 e no side effects; PR #810 | Mitigato |
| VIDEO-ROOT | Alta potenziale su server senza regole .htaccess | `ProtectedVideoStorage` ricadeva nel managed root pubblico se configurazione vuota nonostante MP4 in chiaro | Root obbligatoriamente esplicito/non pubblico, 503 se assente; test Joomla per assente, pubblico e privato; questa PR | Nuovi upload protetti dopo CI; valutare eventuali file legacy sul server nella fase SEC-P2 |
| MAIL-RECIPIENT-ID | Media: email obsoleta | Legacy senza recipient ID e account variato non erano fail-closed | 13 produttori censiti, recheck a dispatch + redazione, PR precedenti | Mitigato |
| ENDPOINT/CSRF e IDOR mirati | Variabile | Review per task, freshness e cross-course | Controller gate + test DB/HTTP | Nessuna nuova vulnerabilità confermata nelle aree esaminate |
| SECRET/EXTERNAL | Variabile | Shell e HTTP sinks ad alto rischio censiti | Gate su 340 PHP, ffprobe argv e Cloudflare fixed HTTPS; niente segreti in log | Nessun nuovo sink non classificato ammesso |

**Rischio residuo da verificare sulle installazioni esistenti (non cancellare dati automaticamente):** la precedente configurazione video priva di root esterno potrebbe aver creato MP4 `.blob` nella directory managed sotto il webroot. Il nuovo runtime impedisce il fallback, ma **non sposta né cancella** file già presenti. Il gestore deve controllare ed eventualmente mettere in sicurezza file legacy prima del go-live; questo finding è tracciato al gate di hardening SEC-P2. Il rischio non va dichiarato inesistente. L'assenza di conferme di esfiltrazione non ne elimina il potenziale impatto.

## 5. Criteri di accettazione e separazione dei gate

Per dichiarare SEC-P1 completato sul commit candidato servono: rapporto minacce e trust boundary, inventario verificato dei 37 controller/10 plugin/3 task, review source-sink trasversale, finding classificati con mitigazione e controprova, matrice collegata a test, preflight e entrambe le matrici Joomla verdi. **Il pass di SEC-P1 non significa release-ready:** SEC-P2 deve chiudere hardening/migrazioni/supply chain (inclusa ricerca eventuali video legacy), SEC-P3 export/retention, SEC-P4 cookie/CMP e SEC-P5 test negativi E2E/pentest multi-ruolo con rapporto finale e accettazione.

**Esito della review SEC-P1:** threat model, code review trasversale e classificazione dei finding documentati; esecuzione tecnica preliminare della PR #811 [run #44801464075](https://github.com/jeckodevelopment/jd_learn/actions/runs/44801464075) conclusa 4/4 verde. I due criteri SEC-P1 sono registrati come implementati nella matrice dopo riconciliazione; la build finale della PR deve confermare il medesimo esito. **Accettazione Joomla manuale non eseguita**: l'analisi SEC-P1 non sostituisce SEC-P2/SEC-P3/SEC-P4/SEC-P5 né certifica assenza di vulnerabilità.
