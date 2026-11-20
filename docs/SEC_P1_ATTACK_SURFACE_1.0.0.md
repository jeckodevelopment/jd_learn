# SEC-P1 — inventario delle superfici applicative JD Learn 1.0.0

**Baseline:** 20/11/2026; revisione incrementale su codice `main`. **Non è un audit SEC-P1 completato.** L'inventario raggruppa le superfici individuate nel repository; un endpoint con controlli visibili non equivale a una prova negativa E2E. Ogni verifica successiva dovrà registrare file, operazione, minaccia, controllo, test, risultato CI e collaudo Joomla distinti.

## Copertura per superficie

| Area | Controller / servizi da verificare | Minacce e controlli richiesti | Stato delle evidenze |
|---|---|---|---|
| Catalogo pubblico e navigazione | `site/Controller/DisplayController`, `site/Model/{Course,Courses,Category,Categories,Pathway,Pathways,Teacher,Teachers}Model` | Visibilità, Joomla view levels, dati non pubblicati, ID manipolati, output escaping, cache | Parziale: test pubblici disponibili; revisione SEC-P1 complessiva da completare |
| Iscrizioni, aula e ripresa | `site/Controller/{Enrollment,Lesson}Controller`, `LearningService` | Fresh identity, CSRF, enrollment ownership, race/idempotency, edit/version access | Parziale: test owner/blocked/suspended disponibili; matrice ruoli/azioni da completare |
| Progresso video e audio | `site/Controller/{Video,Audio,Media}Controller`, `VideoProgressService`, `LocalVideoService` | POST+CSRF, replay, session ownership, durata, consenso, source revision, streaming HEAD/Range | Parziale: gate endpoint, video e materiali; review SEC-P1 completa pendente |
| Materiali didattici | `site/Controller/AttachmentController`, `administrator/Controller/{Material,Videofile}Controller`, `MaterialService`, `MaterialDownload`, `MaterialFilePolicy` | IDOR course/version, upload, MIME, traversal, preview, download, scanner | Revisione mirata e regressioni HTTP documentate; non penetration test |
| Attestati | `site/Controller/CertificateController`, `administrator/Controller/CertificateController`, `CertificateService` | Proprietario vs admin, ACL dedicata, token, stato/asset, file privati, rigenerazione | Test owner/guest e admin negativa PR #770; revisione esaustiva E2E pendente |
| Quiz e banca domande | `site/Controller/QuizController`, `administrator/Controller/{Quiz,Quiztime,Quizextend,Quizreview,Questionbank}Controller` | Tentativi/replay, timer, grading, ACL oggetto, CSRF, transizioni e race | Review CSRF task banca domande e HTTP mirato documentati; altre operazioni da esaminare |
| Editing e pubblicazione corsi | `administrator/Controller/{Course,Courses,Lesson,Module,Curriculum,Courseoperations,Coursepreview}Controller` | Form allowlist, ACL granulari, versioni immutabili, CSRF, preview privata, stato/transazioni | Parziale, da verificare per ogni metodo e ruolo |
| Docenti, percorsi e categorie | `site/Controller/TeacherController`, `administrator/Controller/{Teacher,Pathway,Categoryreassignment}Controller` | Ownership, escalation da editor, ACL category/pathway, form binding e XSS | Da completare con prove negative oggetto per oggetto |
| Roster e amministrazione iscrizioni | `administrator/Controller/{Manualenrollment,Enrollmentapproval,Enrollmentstate,Enrollmentmigration}Controller` | Ruoli, cross-course IDOR, race, CSRF, import, rollback | Parziale: fixture di servizio/esecuzione esistenti; matrice E2E da completare |
| Email, consenso e coda | `site/Controller/MailconsentController`, `administrator/Controller/{Mailqueue,Coursemessage,Courseannouncement}Controller`, `CourseAnnouncementConsent` | Token monouso, URL Referer, CSRF, ACL, retry duplicati, privacy, SMTP errors | **Finding mirato:** redirect conferma token senza no-referrer; mitigazione in PR di questa tranche; resto aperto |
| Privacy Joomla e task | `plugins/privacy/jdlearn`, `plugins/task/*`, `plugins/user/jdlearnconsent`, `plugins/actionlog/jdlearn` | Export/removal, retention, token residue, task privileges, logging/redaction | SEC-P3 e SEC-P1 non completati; seguire registro PRIV-C |
| Distribuzione, update e dipendenze | `component/script.php`, `tools/build.py`, manifests/package, workflow CI, update server | Secret disclosure, dipendenze, upgrade/rollback, ZIP integrity, privileges | Gate di packaging esistenti; review SEC-P2/SEC-P5 completa pendente |

## Metodo vincolante per chiudere SEC-P1

1. Enumerare ogni action/controller e ogni evento/endpoint del pacchetto finale, associandoli alla tabella; controllare router e task ereditati, non solo quelli esplicitamente dichiarati.
2. Per ogni mutazione: metodo HTTP, CSRF, identificazione corrente dell'utente, ACL per oggetto e stato, idempotenza/transaction/concorrenza, allowlist dei campi e output.
3. Per ogni lettura: proprietà e visibilità reale, SQL binding, isolamento tra corsi/utenti, gestione file, HEAD/Range, cache, errori e leakage.
4. Per integrazioni e job: segreti, canali esterni, log, contesto esecutore, dati sensibili e retention.
5. Aggiungere test negativi su Joomla disposable per almeno: anonimo, studente proprietario, studente estraneo, docente, autore di altro corso, amministratore senza permission specifica, amministratore autorizzato; confrontare asset e stato DB prima/dopo.
6. Tracciare `non esaminato`, `controllo implementato`, `regressione CI superata`, `collaudo manuale superato`, `finding aperto`, `non applicabile motivato`, senza promuovere criteri sulla base di mere letture del codice.

## Finding SEC-P1/CONSENT-REFERRER (20/11/2026)

`MailconsentController::confirm()` riceve un token monouso nella query string e redirige alla vista preferenze. Prima di questa tranche imponeva `Cache-Control: no-store`, ma non una policy per impedire che l'URL della conferma venga riutilizzato come `Referer` nella navigazione/redirezione; il percorso di revoca era già protetto. Correzione: inviare `Referrer-Policy: no-referrer` e `X-Robots-Tag: noindex, nofollow` prima della conferma e del redirect. Il token resta casuale, hashato, a scadenza e monouso; nessuna modifica a modalità double opt-in, destinatari, database o cookie. `tests/frontend-preferences.py` impedisce la rimozione accidentale degli header (regressione strutturale); l'effetto HTTP effettivo deve essere verificato su sito disposable. Classificazione: hardening di privacy/sicurezza puntuale; **nessuna dichiarazione di esfiltrazione osservata**.

**Stato SEC-P1:** aperto. **Stato SEC-P5:** aperto. Minimo funzionale storico 57,48%, invariato per questa mitigazione.

## SEC-P1 — controllo automatico trasversale dei controller

`tests/sec-p1-controller-gates.py` enumera i file `*Controller.php` nei controller Site e Administrator distribuiti e richiede che ogni nuovo controller venga assegnato espressamente a una delle categorie `writable`, `readonly` o `delegated`; i controller scriventi classificati richiedono evidenze strutturali di controllo POST e token Joomla, con eccezioni ereditate documentate (`ModuleController` e `TeacherController` frontend). Il gate è invocato da `tools/check.py` e deve impedire regressioni o aggiunte non analizzate. **Non esegue richieste HTTP, non dimostra l'effettivo perimetro ACL dei servizi, né chiude il finding o la review integrale SEC-P1.** CI e collaudo Joomla da verificare, nessuna promozione di criterio.

## Finding SEC-P1/CATEGORY-ACTOR (20/11/2026)

La riassegnazione amministrativa delle categorie verificava ACL, lock e blocco account, ma `CategoryReassignmentService::assertManage()` clonava l'oggetto `User` del chiamante: una sessione mantenuta in memoria poteva conservare `block=0` dopo che il record Joomla era già stato bloccato. Correzione server: creare un nuovo `User(id)` dal database e invalidare la cache ACL per ogni autorizzazione della sorgente e della destinazione. La fixture Joomla `tests/joomla-categories.php` blocca l'operatore dopo il login, riutilizza volutamente la vecchia identità e richiede 403 su `inspect` e `reassign`, senza mutare la categoria; ripristina il blocco in `finally`. La prova copre questo stato account specifico; non prova tutta la matrice ACL/gruppi né tutte le transizioni simultanee. SEC-P1 resta aperto.
