## DOC-07 — checklist ad alto rischio e diagnostica configurazione (20/11/2026)

**Automatizzato (gate statico):** `tests/doc07-safe-operations.py` in `tools/check.py` verifica la checklist dashboard, chiavi IT/EN e mapping per ACL correnti. Continuano i test `tests/scheduler-guidance.php`, `tests/provider-doc-contract.py` e gate dei provider. Non sono test funzionali HTTP o di autenticazione reale.

**Collaudo Joomla da eseguire:** (1) con gestore com_jlms abilitato verificare guida storage/corsi, mail solo se `jlms.audit.view`, attestati solo con `jlms.certificates.manage`; (2) con solo gestore com_scheduler mostrare unicamente la procedura scheduler e non indicazioni protette del componente; (3) senza permessi non mostrare la checklist; (4) browser IT/EN e lettore schermo sul `details/summary`; (5) simulare stato scheduler mancante, doppio, disabilitato, regole JSON invalide, ritardo e failure con messaggi azionabili senza link mutativi; (6) plugin video disabilitato/non configurato/non registrato; (7) configurazione storage video non privata e credenziali non disponibili: nessun segreto o bypass nelle istruzioni; (8) riprovare invii consentiti, rigenerazione attestati e archiviazione tramite flussi POST autorizzati e conservare dati dopo restore. Registrare esiti e build. **Collaudi manuali non eseguiti.**

## PBASE-02 — accettazione contratti dei quattro plugin (20/11/2026)

**Gate automatico:** `python3 tests/provider-doc-contract.py` e `python3 tests/provider-package-gate.py`; il package builder importa `tools/provider_doc_gate.py` e ora verifica per YouTube, Vimeo, Cloudflare Stream e HTML5 anche la corrispondenza dei parametri XML con il tipo Joomla, il default dichiarato e i min/max delle guide locali. Le tabelle non sostituiscono la lettura del runtime PHP, né una prova con account terzi.

**Collaudo Joomla da eseguire:** su build installata/aggiornata controllare per tutti e quattro i provider esistenza delle guide nel pacchetto, manifest/versione effettiva, configurazione conservata e messaggi in IT/EN; verificare account autorizzato e non autorizzato, consenso e revoca, avvio senza autoplay, durata, resume, replay a lezione completata, errore di rete, cambio CSP/plugin disabilitato e assenza di segreti nei log. Specifici: Vimeo caption quando disponibili; YouTube privacy enhanced e blocco embed; Cloudflare signed token/TTL/rinnovo, API con account di prova reale e origin policy; HTML5 MP4 storage fuori webroot, FFprobe, HEAD/Range/revoca. Registrare evidenza e risultato per ciascun provider. **Test manuale non eseguito**.

## UI-07 — contratto DOM frontend versionato 1.0.0 (20/11/2026)

**Gate automatico:** `python3 tests/frontend-dom-contracts.py` (invocato anche da `tools/check.py`) verifica la copertura delle 17 viste `default.php`, gli ID letterali, le classi `jlms-*`, le radici, gli override Joomla e i due selettori CSS di personalizzazione. Insieme a `tests/frontend-contract-hooks.py` conserva gli hook JS. Il controllo è statico, non prova il DOM eseguito né l'accessibilità WCAG.

**Comportamenti da testare manualmente su Joomla:** (1) applicare `#jlms-courses .jlms-course-card { border-radius: 1rem; }` in un template e verificare griglia, responsive e assenza effetti fuori dal catalogo; (2) separatamente applicare `#jlms-dashboard .jlms-my-course { padding: 1.5rem; }` in un altro template e verificare area personale e isolamento; (3) collocare moduli Joomla nella stessa pagina e analizzare con DOM inspector l'unicità degli ID, inclusi partial personali, schede e lezioni ripetute; (4) override dei layout di corso, categoria, aula, quiz, docente, attestati e preferenze, verificando hook JS, filtri, ACL, consenso e CSRF; (5) IT/EN, desktop/mobile, Chrome/Safari, tastiera e screen reader. Registrare browser, versione Joomla, build, screenshot/evidenza e risultato per scenario. **Accettazione manuale non eseguita in questa PR.**

## SEC-P1 — modello delle minacce consolidato e hardening storage video (20/11/2026)

**Review/gate automatici:** `tests/sec-p1-systematic-source-gate.py` legge l'intero runtime PHP distribuito, rifiuta primitive dinamiche non revisionate, controlla l'unico sink processo ffprobe e il trasporto HTTPS Cloudflare; `sec-p1-controller-gates.py` e `sec-p1-cross-surface-gate.py` preservano rispettivamente 37 controller e 10 plugin/3 task. **Test Joomla** `tests/joomla-video.php`: nessuna `video_storage_root` (503), root web pubblica (503), root privata esterna (riuscita). Nessun cambiamento intenzionale ai materiali PDF gestiti/cifrati.

**SEC-P5 / SEC-P2 ancora da eseguire:** cercare su installazioni aggiornate eventuali video plaintext `.blob` in `media/com_jlms/private`, mettere in sicurezza e migrare conservando i dati; testare upload/streaming con provider HTML5 in configurazione corretta, verificare rete e webserver Nginx, scansione, HEAD/Range, revoca, concorrenza e download diretto della directory privata. Eseguire pen test e analisi supply chain separati, senza confonderli con l'esito della code review SEC-P1.

## SEC-P1 — job comunicazioni: replay con contenuto cambiato (20/11/2026)

**Test CI Joomla disposable:** `tests/joomla-roster.php` accoda per un corso autorizzato un job comunicazione con `mutation_id` valido. Un replay identico deve restituire lo stesso ID senza duplicazioni; un replay con un nuovo oggetto (e relativo hash valido) e **stesso identificativo di mutazione** deve restituire 409. Verificare che il payload iniziale rimanga invariato, che non compaiano job aggiuntivi e che la fixture ripulisca job e action log. Esito da registrare nella PR.

**Comportamenti da collaudare sul sito:** preparare un messaggio a iscritti attivi, confermarlo una volta e ripetere il medesimo POST; verificare una sola operazione. Utilizzare lo stesso UUID con diverso testo, destinatari o template: ottenere conflitto 409 senza nuovo invio e senza modifiche alla prima comunicazione. Verificare anche due conferme quasi contemporanee e l'esito SMTP in ambiente isolato. **Collaudo manuale non eseguito.**

## SEC-P1 — revoca reale di sessione Joomla su stato e iscrizione manuale (20/11/2026)

**Suite HTTP CI:** `tests/enrollment-state-http.py` e `tests/manual-enrollment-http.py`. L'operatore delegato `cirosterreader` esegue il login reale e acquisisce una form autorizzata. La fixture PDO contrassegna poi come bloccato l'account in `#__users`, conservando cookie e form. Con la sessione precedente il servizio deve rifiutare GET/POST di stato e POST di ammissione manuale con HTTP 403; snapshot prima/dopo invariati. Gli script ripristinano il blocco e i gruppi nei propri `finally`. Esito da verificare nelle due configurazioni di CI Joomla.

**Accettazione manuale ancora in attesa:** ripetere con operatori delegati effettivi e seconda sessione amministrativa, includere POST senza token/alterati, revoca del solo permesso ACL di corso, altri corsi e ruolo studente. Completare audit SEC-P1 sulle altre superfici senza trasformare questi due HTTP test nella chiusura del gate intero.

## SEC-P1 — gate trasversale plugin, Scheduler, media e matrice evidenze (20/11/2026)

**Test automatico:** `tests/sec-p1-cross-surface-gate.py`, invocato da `tools/check.py`, controlla il set di 10 classi/plugin, l'inventario eventi e metodi pubblici, i tre task Scheduler, i limiti dei worker, l'accesso tramite LocalVideoService su GET/HEAD, il timeout e il divieto di redirect Cloudflare, gli hook Privacy e alcuni vincoli sulle transazioni. La matrice `docs/SEC_P1_THREAT_CONTROL_EVIDENCE_1.0.0.csv` esplicita i limiti di ogni riga; **non attribuire al gate statico il superamento di collaudi manuali o il completamento di SEC-P1**.

**Collaudo Joomla/HTTP da eseguire su installazione disposable con account fittizi:** eseguire task su ruoli differenti e verificare assenza di notifiche esterne non autorizzate; richiedere GET/HEAD/Range di file privati con sessione revocata, iscrizione di altro utente e corso estraneo; esportare/cancellare un account con iscrizioni, attestati, consensi e job pendenti; verificare provider con DNS e redirect ostili in rete isolata; acquisire evidenze redatte. Estendere ai rimanenti endpoint il test HTTP cross-role e classificare tutti i finding prima di promuovere SEC-P1. **Accettazione: in attesa.**

## SEC-P1 — tranche consolidata sicurezza amministrazione iscrizioni (20/11/2026)

**Automatizzato in CI Joomla disposable (da confermare):** test negativi di identità stale dopo blocco su `EnrollmentStateService::editor/apply`, `EnrollmentApprovalService::pending/decide` e `ManualEnrollmentService::editor/apply`. Tutti richiedono 403 con il vecchio oggetto `User`, verificano nessuna alterazione dei record e ripristinano `#__users.block` in `finally`. Riprovare la lettura positiva dopo lo sblocco. Le PR #802/#804 coprono roster e migrazione analogamente.

**Collaudo manuale Joomla ancora in attesa:** aprire con identità delegata le operazioni stato, approvazione, ammissione manuale, migrazione e roster; bloccare/revocare permessi da un secondo browser; ripetere URL/POST con token di sessione preesistente e verificare 403, risposte senza dati privati e assenza effetti DB/outbox. Ripetere per ospite, studente proprietario e non, docente del corso A e altro corso B, amministratore senza permesso specifico e autorizzato; verificare CSRF, replay, concorrenza e isolamento sulla stessa build Joomla candidata. Non dichiarare tali prove eseguite sulla base delle sole fixture CLI.

## SEC-P1 — migrazione iscrizioni: account operatore bloccato dopo login (20/11/2026)

**Regressione Joomla disposable:** `tests/joomla-enrollment-migration.php` verifica che `preview()` e `apply()` rifiutino con 403 un operatore bloccato dopo l'acquisizione dell'identità di sessione, senza modificare l'iscrizione né creare record di migrazione. Stato account ripristinato in `finally`, nuova preview positiva dopo lo sblocco. Esito CI da verificare.

**Comportamenti da testare sul sito di prova:** con operatore autorizzato aprire l'anteprima di migrazione; bloccarne l'account da un'altra sessione e provare anteprima e conferma dalla sessione precedente (403, nessun trasferimento). Ripristinare l'account; testare anche revoca di `jlms.enrollments.migrate`, docente abilitato al solo roster e ID di iscrizione appartenente ad altro corso. Verificare invariabilità DB e audit. **Accettazione manuale: in attesa.** SEC-P1 ancora aperto.

## SEC-P1 — roster: blocco operatore dopo login (20/11/2026)

**Test automatico da verificare in CI:** `tests/joomla-roster.php` usa account amministrativo di fixture in Joomla disposable, conserva la vecchia identità, blocca il record Joomla e richiede 403 a `EnrollmentRosterService::query()` e `filterOptions()`. Ripristina sempre lo stato `block` precedente in `finally`, poi verifica accesso consentito. Non è collaudo HTTP reale.

**Comportamenti da testare manualmente:** aprire roster e filtri con un amministratore autorizzato; da altra sessione bloccare l'utente, ripetere i due accessi con la prima sessione senza rifare login e verificare 403 e assenza di nomi/iscritti; sbloccarlo e verificare ritorno dei diritti effettivi. Ripetere con docente autorizzato, account senza `jlms.enrollments.manage` e corso estraneo, senza alterazioni delle iscrizioni. **Accettazione Joomla: in attesa.** Nessuna nuova funzionalità né modifica runtime.

## SEC-P1 — certificati amministrativi: blocco account dopo login (20/11/2026)

**Automatico:** test Joomla `tests/joomla-smoke.php` conserva l'istanza di identità autorizzata, blocca nel database l'utente associato senza ricaricare la sessione, poi richiede 403 per `adminList`, `adminDetail`, `adminUnissuedCompletions`, `adminDownload`, `adminRegenerate`, `adminRecoverMissing`, `adminRecoverHistorical`; l'account viene sempre ripristinato in `finally`. Il nuovo gate `tests/sec-p1-admin-identity.py` individua sette operazioni certificate admin e richiede che ognuna passi da `assertCertificateAdministrator` che ricarica account e ACL; verifica inoltre `Mailvariables/HtmlView` e il controllo fresco. Il blocco user non è una prova di revoke gruppo/ACL senza blocco.

**Manuale Joomla — In attesa:** in installazione disposable accedere come amministratore con `jlms.certificates.manage`, aprire lista/dettaglio e scaricare un PDF. Da altra sessione bloccare l'account o revocare il permesso dedicato, poi ritentare le URL e POST di rigenerazione/recupero senza nuovo login: richiedere 403, nessuna modifica audit/PDF. Ripristinare utente/ACL; ripetere la guida variabili email (accesso 403 dopo blocco), testare concessione valida e utente senza permesso dedicato. Registrare prove di entrambi i profili PHP/database e assenza PII nei log.

## SEC-P1 — produttori SMTP e identità Joomla obbligatoria (20/11/2026)

**Automatico:** tests/sec-p1-mail-recipient-identity.py, richiamato da tools/check.py, censisce i 13 produttori enqueue e il produttore enqueueAnnouncement, richiede ID Joomla e rifiuta nuove sorgenti non classificate. tests/joomla-smoke.php verifica su DB reale utente valido, bloccato, inesistente, email diversa o malformata. La prova strutturale non dimostra SMTP o ACL per endpoint.

**Collaudo Joomla manuale — In attesa:** con SMTP isolato, accodare messaggi per iscrizione, approvazione/rifiuto, completamento quiz/video, sospensione/riattivazione, iscrizione manuale, attestato, promemoria e annuncio opt-in; cambiare email o bloccare l'account prima di Scheduler e verificare skip/redazione senza invio. Per account immutato verificare consegna unica. Provare righe legacy senza ID, no dati personali nei log e pulizia fixture. Continuare ACL/IDOR HTTP e review plugin per SEC-P1.

## SEC-P1 — outbox corso, account bloccato/email stale prima di SMTP (20/11/2026)

**Automatico:** `tests/joomla-smoke.php` usa un account Joomla della fixture e `MailQueue::currentRecipient` per verificare: ID/email aggiornati ammessi, ID nullo/inesistente, email cambiata o non valida e account bloccato negati; ripristino in `finally`. Il dispatcher esegue la verifica sull'account nello stesso ciclo di invio, prima del trasporto, sulle righe con `recipient_user_id` e sempre su `com_jlms.course.message` (anche legacy senza ID, scartato); i produttori di messaggi corso persistono ID sia per corsisti sia per invio test. Nessun test SMTP reale.

**Collaudo manuale Joomla — In attesa:** con SMTP isolato e corsi/utenti fittizi, accodare un messaggio corso, cambiare l'email del destinatario prima dell'avvio task; verificare `skipped`, nessuna consegna al vecchio indirizzo e dati destinatario/payload eliminati. Ripetere bloccando account, e con account invariato (invio riuscito una sola volta). Verificare invio test al docente con ID, cambio email immediatamente dopo accodamento, comportamento delle vecchie righe senza ID e nessuna perdita di altri template email. Provare con entrambi i profili PHP/DB, pulire test e verificare nessun dato privato nei log.

## SEC-P1 — task Joomla e inventario 37 controller (20/11/2026)

**Automatico:** `tests/sec-p1-controller-gates.py` rileva variazioni non deliberate nei nomi controller, metodi pubblici e task consentiti. `tests/joomla-categories.php` verifica 404 per sette task non consentiti (`display`, `save`, `delete`, `purge`, `getModel`, `__construct`, `reassignother`) sulla classe Joomla reale, senza mutare categorie. Il task `reassign` conserva controlli POST/CSRF e ACL già presenti. Matrice: `docs/SEC_P1_CONTROLLER_DISPATCH_1.0.0.md`.

**Collaudo manuale Joomla — In attesa:** in un'installazione disposable richiamare via HTTP `administrator/index.php?option=com_jlms&task=categoryreassignment.display` e `...reassignother` e richiedere 404; invocare `reassign` con GET (405) e POST senza token (403), poi con token/ACL corretti e con account delegato non autorizzato. Verificare nessuna mutazione dopo rifiuti, messaggi IT/EN e ripristino fixture. Prima di chiudere SEC-P1 eseguire la stessa matrice negativa sugli altri entry point, plugin e modelli.

## SEC-P1 — destinatario consenso e account Joomla aggiornato (20/11/2026)

**Automatico:** `tests/joomla-smoke.php` verifica dati destinatario canonici da DB, email obsoleta e ID inesistente rifiutati, e account temporaneamente bloccato: origini `dashboard`/`enrollment` negate, `registration` ammessa per attivazione nativa; ripristino account in `finally`. Nessun invio SMTP reale.

**Collaudo Joomla pendente:** in ambiente disposable e SMTP isolato, aprire Preferenze, modificare successivamente l'email dell'account in Joomla e inviare con sessione precedente: nessun token al recapito obsoleto; riprovare con account bloccato da preferenze e iscrizione: nessun invio. Verificare registrazione con attivazione nativa, destinatario corrente, assenza di dati nei log e ripulire la fixture.

## DOC-07 — prossima esecuzione Scheduler malformata (20/11/2026)

**Automatico:** `php tests/scheduler-guidance.php` verifica valori `next_execution` inesistenti (30 febbraio), ore/minuti fuori range, formati non Joomla e confini dei 10 minuti. La classificazione `unknown` non sostituisce la semantica delle regole Joomla.

**Collaudo Joomla — In attesa:** predisporre in una installazione disposable un task con data prossima esecuzione normale e uno con data impossibile via fixture isolata, verificare stato `unknown` per il secondo, guida IT/EN e nessuna mutazione dei task. Ripristinare le righe, non attivare invii SMTP reali.

## DOC-07 — duplicati Scheduler con plugin/task disabilitati (20/11/2026)

**Automatico:** `php tests/scheduler-guidance.php` verifica che due attività dello stesso tipo restino classificate `duplicates` in ogni combinazione di abilitazione del plugin e della singola attività; una sola attività disabilitata resta `disabled`.

**Collaudo Joomla pendente:** predisporre in un'installazione disposable due task `jlms.mail.dispatch` disabilitati, verificare l'avviso duplicati nella dashboard, riabilitare il plugin e un task alla volta senza avviare l'invio di email reali. Confermare che nessuna attività sia mutata dalla sola visita alla dashboard e che gli avvisi IT/EN siano comprensibili. Ripetere con un solo task disabilitato: stato disabilitato, non duplicato. Ripulire i task di prova. Nessuna accettazione manuale attribuita.

## SECDEV-03 — spoofing del nome file tramite controlli Unicode bidi (20/11/2026)

**Automatico:** `php tests/material-policy.php` deve rifiutare nomi contenenti marcatori bidi U+202E, U+202A, U+2066/U+2069, U+200F e U+061C, mantenendo ammessi nomi UTF-8 accentati senza tali controlli. La stessa policy protegge il nome proposto nel download; i controlli MIME e sul contenuto restano separati.

**Collaudo manuale Joomla pendente:** su ambiente disposable, come autore provare a caricare `appunti‮fdp.exe` (RLO) e un nome con marcatori di isolamento: l'upload deve essere respinto, senza salvare asset o modificare il corso. Caricare poi `Lezione àèìòù – résumé.pdf` con PDF valido e antivirus configurato, quindi verificare autorizzazioni e intestazione `Content-Disposition` del download. Ripetere le prove negative per MP4 locale dove il percorso consente il caricamento. Non assumere che questa protezione risolva gli altri vettori XSS o SQLi inclusi nel gate SECDEV-03.

## PBASE-03 — guide amministratore/autore/sviluppatore ed esempio estensione (20/11/2026)

**Automatico:** `php tests/provider-contract-example.php` controlla il registry v1, l'unicità delle chiavi, la factory lazy e lo scheletro di manifest Joomla; l'adapter di esempio rimane intenzionalmente fail-closed senza playback.

**Collaudo manuale da eseguire:** confrontare le istruzioni di `docs/VIDEO_PROVIDER_ROLES_1.0.0.md` con i plugin base; provare su ambiente disposable installazione/upgrade, backup/ripristino, ACL amministratore/autore, revoca consenso e assenza di token lato browser. Non distribuire lo scheletro non operativo e non considerarlo prova di un quinto player funzionante.

## PBASE-03 — criterio di blocco build comprovato e riconciliato (20/11/2026)

La [PR #762](https://github.com/jeckodevelopment/jd_learn/pull/762) ha dimostrato nella CI quattro job verdi e il fallimento della build standalone, senza ZIP in uscita, quando manca una guida, una sezione obbligatoria o la documentazione di un campo manifest. **PBASE-03/criterion_06 = implemented (evidenza automatica)**. Questo non completa PBASE-03/criterion_04, non copre manuali per tutti i ruoli né conferma il collaudo manuale Joomla dei provider. La candidata `0.64.4-dev` resta da provare su sito disposable per upgrade/installazione e coerenza delle guide distribuite.

## PBASE-03 — blocco di distribuzione senza documentazione plugin base (20/11/2026)

**Automatizzato:** `python3 tests/provider-package-gate.py` verifica il gate positivo sui quattro README e l'assenza di ZIP quando il builder standalone incontra guida mancante, sezione fondamentale assente o parametro di manifest non documentato. `python3 tools/build.py` verifica l'archivio ordinario. Una CI senza errori non sostituisce una lettura del contenuto o la verifica manuale del plugin.

**Accettazione Joomla pendente:** installare lo ZIP verificato della build su sito disposable; ispezionare i quattro plugin inclusi e i relativi README, configurare e riprodurre video in condizioni autorizzate, verificare gli upgrade senza perdita di dati. Non importare documenti da un'altra build e non usare una build con guide manomesse in produzione. Il gate protegge da assenza delle guide e deriva dei campi, non certifica il contenuto didattico/documentale integrale.

## DOC-07 — regole di esecuzione scheduler malformate (20/11/2026)

**Verifica automatica:** `php tests/scheduler-guidance.php` prova JSON vuoto/malformato/non oggetto/senza `rule-type`, validità strutturale e classificazione `invalid_rules` anche quando `next_execution` è futura. La verifica non pretende di validare tutte le regole calendario Joomla, che restano responsabilità di Joomla Scheduler.

**Collaudo manuale Joomla pendente:** su installazione *disposable* modificare una sola attività JD Learn di prova e ripristinarla. Con regole JSON non valide e prossima esecuzione ancora valorizzata, il dashboard deve mostrare «Regole di esecuzione non valide» e l'avviso specifico, senza cambiare la configurazione; ripetere con plugin disabilitato e task duplicato (precedenze dei relativi avvisi), poi con regole valide. Verificare IT/EN, permesso `core.manage` su `com_scheduler`, assenza di trigger del task e di invii email. Non modificare direttamente i job in produzione per effettuare questa prova.

**Esito:** controllo automatico previsto, accettazione manuale non eseguita, DOC-07 non completo.

## DOC-07 — diagnostica dei provider video con verifiche consigliate (20/11/2026)

**Collaudo Joomla manuale pendente:** nella Dashboard di JD Learn espandere «Stato dei provider video». Per YouTube, Vimeo, Cloudflare Stream e HTML5 verificare separatamente i casi plugin non installato, installato/disattivato, attivo ma senza descriptor, attivo con descriptor `configured=false`, attivo e localmente configurato. Il messaggio vicino allo stato deve essere tradotto nella lingua amministrativa IT/EN e distinguere ogni causa senza esporre valori dei parametri, credenziali o contenuto delle sorgenti. La diagnostica deve rimanere in sola lettura e non abilitare plugin, creare cron o effettuare chiamate remote.

Verificare accessibilità con tastiera, ruolo autorizzato a vedere la dashboard ma senza permesso di gestione plugin, assenza di link mutativi o inviti a superare i controlli. Per lo stato «configurato» provare anche il player in un'aula autorizzata, poiché il controllo locale non certifica il provider remoto.

**CI:** test degli stati e del fallback in `tests/run.php` e controllo della presenza di tutte le traduzioni IT/EN; nessuna promozione dei criteri DOC-07 o accettazione Joomla automatica.

## PBASE-02 — guide locali dei quattro plugin video (20/11/2026)

**CI:** eseguire `python3 tests/provider-doc-contract.py` e `python3 tools/check.py`. Il gate verifica per ciascuno dei quattro provider inclusi nel package che la sua guida README identifichi plugin, parametri di configurazione del manifest e sezioni su capacità, errori e collaudo. Non verifica che il provider remoto funzioni, né tutte le affermazioni delle guide.

**Joomla manuale pendente:** su una installazione aggiornata abilitare separatamente ogni plugin, confrontare le opzioni reali con la relativa guida, configurare una lezione riproducibile, accertare l'autorizzazione dell'aula/anteprima, ripresa e replay dopo completamento, effetto di consenso/alternanza lingua e comportamento in caso di provider spento. YouTube/Vimeo: test con SDK e policy embed reali; Cloudflare: token, origini e scadenze su account di prova; HTML5: streaming protetto, Range/HEAD e backend/storage. Testare anche fallimenti, utenti senza accesso, IT/EN e disabilitazione senza perdita dello storico. Annotare build e risultati; nessuna accettazione manuale è presunta.

**Stato:** intervento documentale parziale su PBASE-02. Nessun criterio ripromosso e avanzamento minimo 57,32% invariato.

## SEC-P1 — verifica mirata CSRF/metodo/stati banca domande (20/11/2026)

**Perimetro:** `QuestionbankController::mutate`, `QuestionBankService::trash/delete/duplicate`, `tests/quiz-authoring-http.py` e form nativi della Banca domande. La verifica di codice conferma controlli espliciti `POST` e token Joomla prima dell'esecuzione dei task, ri-validazione dell'identità e ACL oggetto nel servizio, lock della riga, transizioni 0/1 → -2 → 0, blocco della cancellazione definitiva fuori dal cestino e delle revisioni ancora utilizzate.

**Regressione HTTP in CI:** su una nuova domanda isolata, GET e POST senza token devono essere rifiutati rispettivamente con 405/403 sui quattro task `trash`, `restore`, `delete` e `duplicate`. La cancellazione diretta di una domanda non cestinata deve fallire con 409, mentre cestinazione e ripristino autenticati funzionano; la ripetizione di una transizione incompatibile deve fallire con 409. La prova non modifica le domande fissate dai quiz della fixture.

**Esito e limiti:** non è stata individuata una mutazione senza CSRF su questi task mediante revisione statica; l'esito HTTP deve essere confermato dalla CI e non sostituisce revisione sistematica di tutti i controller, ACL in configurazioni reali o collaudo manuale Joomla. Non promuovere criteri SEC-P1/SECDEV-04 o percentuali per questa sola tranche. La guida `QUIZ_AUTHORING.md` è stata corretta perché descriveva duplicazione/cestinazione già implementate come assenti.

## SEC-P1 — revisione mirata IDOR su allegati privati (20/11/2026)

**Riferimento del collaudo e review statica:** `component/administrator/src/Application/MaterialService.php`, `component/administrator/src/Controller/MaterialController.php`, `component/site/src/Controller/AttachmentController.php`, `component/administrator/src/Infrastructure/ProtectedMaterialStorage.php` e `MaterialDownload.php`. Il servizio amministrativo ricarica l'attore Joomla e richiede `core.manage` su `com_jlms` e `jlms.materials.manage` sul corso; il record allegato deve appartenere alla versione dichiarata. Il frontend risolve il contesto didattico dell'iscrizione e verifica la versione proprietaria. Lo storage usa chiavi opache, file protetti e controlli di integrità. L'esame statico di questa superficie **non ha individuato un bypass IDOR confermato**.

**Nuova regressione HTTP reale:** `tests/materials-http.py` verifica download autorizzato e prova a scambiare l'ID di un allegato del corso B con corso/versione A e viceversa, su GET, HEAD e GET con `Range` valido, aspettando rifiuto 404 e assenza del payload privato. Ripete il tentativo sull'anteprima editoriale. La prova è eseguita dalla CI di integrazione Joomla nei due profili PHP/database. La suite preesistente copre tentativi di download da utente estraneo e anonimo, revoca di permessi e CSRF sugli upload.

**Limiti aperti:** non sono stati esaminati sistematicamente tutti gli endpoint, tutti i ruoli/gruppi Joomla, tutte le transizioni, le condizioni di concorrenza, i link di tutti i tipi di file e le configurazioni hosting. Non dichiarare SEC-P1, SECDEV-07, SEC-P5 o accettazione manuale completati. Test manuali sul sito: creare due corsi e utenti con autorizzazioni distinte, tentare gli stessi scambi di ID anche su preview pubbliche, verificare codici/range, cache, ID non validi e log senza dati sensibili.

## SECDEV-07 — riferimento OWASP ASVS v5.0.0 fissato (20/11/2026)

**CI:** eseguire `python3 tests/asvs-reference.py` e `python3 tools/functional_audit.py --check`; il primo deve rifiutare la rimozione della versione fissata o del disclaimer, il secondo verificare le matrici. L'inserimento del pin non prova la conformità ASVS.

**Accettazione e security review ancora pendenti:** sulla candidata 1.0.0 costruire matrice di minacce e requisiti ASVS selezionati, identificatori nella forma `v5.0.0-x.y.z`, superfici/ruoli, test positivi e negativi, findings e contromisure, evidenze di scansione dipendenze/segreti e rapporto go/no-go. Documentare i falsi positivi con motivazione e verificare che nessuna vulnerabilità high/critical pertinente non risolta passi il gate. Non marcare SECDEV-07 completato sulla base della presente PR.

## SEC-P4 — inventario storage e guida CMP bilingue (20/11/2026)

**Controllo statico in CI:** `python3 tests/privacy-storage-inventory.py` deve verificare che le chiamate dirette `localStorage` negli script di componente e plugin video siano solamente le due chiavi documentate in `docs/PRIVACY_COOKIES.md` e `docs/PRIVACY_COOKIES_EN.md`. Introdurre sperimentalmente una chiave diversa o dinamica deve far fallire il test; rimuovere la modifica dopo la prova. Questo non misura cookie Joomla o SDK esterni.

**Collaudo browser reale ancora da eseguire:** con profilo nuovo senza consenso, verificare Network/Cookies/Local Storage/Session Storage/IndexedDB prima e dopo riproduzione video. Cambiare velocità e lingua caption, verificare che i soli valori di queste preferenze siano salvati localmente, che non ci siano segreti/risposte e che storage non disponibile non blocchi la fruizione. Ripetere singolarmente YouTube, Vimeo, Cloudflare Stream e HTML5 con CMP configurata, consenso rifiutato/concesso/revocato, CSP e IT/EN, e confrontare con Joomla senza JD Learn. Registrare build, browser, configurazione e prove redatte. Non dichiarare SEC-P4/criterion_02 o criterion_03 superati prima degli esiti reali.

## CAT-01 — ricerca letterale con caratteri speciali (20/11/2026)

**Collaudo Joomla manuale pendente:** pubblicare corsi visibili con titolo o descrizione contenenti stringhe come `100% opera`, `unit_7` e `A!B`, insieme a corsi simili che contengono `100X opera`, `unitX7` e `AXB`. Cercare esattamente `100%`, `unit_7` e `A!B`: devono comparire solo i corsi che contengono i caratteri letterali e non le varianti con caratteri sostitutivi. Cercare `%` e `_` da soli: non devono trasformarsi in ricerca universale. Verificare titolo e descrizione, corsi con ACL ristrette (non devono apparire), paginazione, reset filtri, menu con ricerca disabilitata, lingua IT/EN e input Unicode. Ripetere su MySQL e MariaDB se disponibili, senza dare per eseguita l'accettazione manuale.

**CI:** `tests/catalog-literal-search.php` prova l'escape puro; `tests/joomla-publication.php` verifica SQL LIKE reale sui due engine; `tools/check.py` e `tests/frontend-catalog-options.py` assicurano che la query site usi il pattern escape e `ESCAPE '!'`. Gli URL restano GET, nessuna scrittura.

## DOC-07 — azioni consigliate nella diagnostica scheduler Joomla (20/11/2026)

**Collaudo manuale Joomla pendente:** in JD Learn → Dashboard aprire «Stato delle attività pianificate». Per ciascun task JD Learn (email, pulizia materiali, scadenza quiz) verificare separatamente: attività assente, plugin/task disabilitato, task duplicato, ultima esecuzione fallita, esecuzione oltre 10 minuti in ritardo, regole non determinabili, prima esecuzione attesa e attività con ultimo esito riuscito. Il testo IT/EN nella colonna «Verifica consigliata» deve indicare il controllo appropriato, non dichiarare consegna SMTP avvenuta e privilegiare le duplicazioni quando presenti. Con ruolo che ha accesso alla dashboard JD Learn ma non `core.manage` su `com_scheduler`, i collegamenti a Gestione attività pianificate **non devono comparire**; con ruolo autorizzato devono essere visibili. Verificare che la semplice apertura della dashboard non crei, abiliti, esegua o modifichi task, e che nessuna credenziale sia mostrata.

**CI:** test puro `tests/scheduler-guidance.php` su tutti gli stati e priorità dei duplicati; gate strutturale e integrità lingue IT/EN. Non si accredita la copertura completa DOC-07 né la verifica manuale Joomla.

## CAT-02/STU-01 — stato iscrizione sul catalogo con oltre 50 edizioni (20/11/2026)

**Collaudo Joomla manuale pendente:** con studente che ha oltre 50 iscrizioni/edizioni storiche dello stesso corso, aggiungere un'iscrizione precedente a un secondo corso visibile. Aprire il catalogo mostrando entrambe le schede: entrambe devono indicare lo stato corretto e la scheda del secondo corso non deve proporre erroneamente di iscriversi. L'azione di ripresa deve rispettare la disponibilità attuale, senza rivelare contenuti non autorizzati. Verificare che per lo stesso corso sia scelta l'ultima iscrizione per ID, non un'edizione più vecchia; provare anche stato sospeso, annullato, ritirato, lingua differente, revoca accesso e altro account (nessuna iscrizione di altri utenti). Ripetere con catalogo a 50 corsi e filtraggio/paginazione; verificare IT/EN.

**CI:** `tests/dashboard-catalog-latest-regression.php` invocato dalla fixture Joomla dashboard testa 51 edizioni aggiuntive, seconda iscrizione storica e isolamento proprietario; `tools/check.py` e `tests/frontend-catalog-options.py` proteggono la selezione per corso prima del `LIMIT 50`. Il collaudo manuale sul sito di prova resta separato.

## STU-01 — Riprendi oltre 50 iscrizioni recenti (20/11/2026)

**Collaudo manuale Joomla pendente:** con account studente di prova creare almeno 51 iscrizioni con attività registrata e non completate, in ordine cronologico. Rendere **non disponibili tutte le 50 più recenti** (sospensione, scadenza, restrizione accesso e/o ritiro consentito dal workflow), lasciando fruibile la cinquantunesima: il riepilogo deve mostrare Riprendi verso la cinquantunesima iscrizione, senza mostrare contenuti vietati per le 50 recenti. Verificare il redirect e i controlli ACL al clic. Rendere indisponibile anche la cinquantunesima: l'azione scompare, ma rimangono i conteggi storici. Ripristinare una delle attività più recenti: Riprendi deve selezionare quella, rispettando il loro ordine. Controllare sessione altro utente, IT/EN, numero di query e tempi di risposta; il caricamento è per blocchi di massimo 50 righe. Nessun GET di lettura deve scrivere progressi.

**CI:** gate strutturale in `tools/check.py` sulla scansione a lotti e suite Joomla dashboard già esistente; il caso combinato con 51 iscrizioni non è ancora coperto automaticamente e non va dichiarato superato fino a prova diretta.

## JOO-01/REL-03 — integrità ZIP pacchetto senza duplicati (19/11/2026)

**Collaudo Joomla manuale pendente:** estrarre dall'artefatto Actions il file `pkg_jlms-0.63.2-dev.zip`; controllare che il pacchetto abbia esattamente 12 ZIP figli, ciascuno presente una sola volta e descritto dal manifest. Eseguire installazione nuova e upgrade da 0.63.1-dev sul sito di prova; in Gestione estensioni verificare che Action Log sia installato una sola volta, senza nuovi record duplicati, che lo stato di abilitazione esistente resti invariato e che versione/data siano uniformi. Verificare corsi, notifiche e plugin video preesistenti. Registrare build, profilo e risultati. Il gate automatizzato sullo ZIP non equivale al collaudo manuale.

**CI prevista:** `tools/build.py`, `tests/extension-manifest-metadata.py`, installazione/upgrade Joomla, lint e matrice DB; ogni nome duplicato (anche in uno ZIP figlio) deve fallire.

## QUI-02 — cancellazione definitiva soltanto dal cestino (19/11/2026)

**Collaudo Joomla manuale da eseguire:** con utente amministratore autorizzato creare una domanda non utilizzata in bozza; inviare un POST autenticato con CSRF valido a `questionbank.delete` indicando il suo ID: atteso HTTP 409 e conservazione della domanda e delle revisioni. Ripetere sulla domanda pubblicata e su una ripristinata: stessa protezione. Cestinare la domanda dal backend, quindi eliminarla definitivamente: domanda, revisioni e asset vengono rimossi solo se non hanno riferimenti a quiz o tentativi. Una domanda referenziata deve restare intatta anche se nel cestino. Verificare ACL insufficienti, CSRF mancante, IT/EN e assenza di cancellazioni collaterali. Registrare build, utente di test ed esiti; nessuna accettazione manuale presunta.

**CI:** controllo statico di protezione server in `tools/check.py`, lint PHP e regressioni Joomla esistenti in `tests/joomla-quiz-authoring.php`. Il controllo statico non sostituisce un test HTTP diretto con esito 409: da completare come parte della copertura di accettazione.

## STA-02 — blocco esplicito dei quiz quando il tempo non basta (19/11/2026)

**Collaudo Joomla manuale da eseguire:** con quiz cronometrato (1 minuto) e corso attivo, configurare singolarmente un limite di chiusura a 30 secondi su iscrizione, edizione, disponibilità della lezione, fine pubblicazione corso e calendario corso. Aprire la pagina quiz prima della chiusura: deve comparire la motivazione localizzata con **60 secondi richiesti / 30 disponibili**, senza pulsante Avvia. Tentare anche il POST diretto: HTTP 403, nessun nuovo tentativo o consumo di contatore. A esattamente 60 secondi residui l'avvio è consentito, senza alterare il timer originario. Riprovare con durata aggiuntiva autorizzata di 120 secondi su quiz base di 60 secondi: 120 secondi residui non bastano per 180, nessun troncamento; dopo l'estensione della finestra il nuovo tentativo usa 180. Verificare tempo espresso in UTC, IT/EN, screen reader e stati di retry/massimo tentativi. Un tentativo **già iniziato** conserva il proprio timer e il comportamento di revoca sicurezza già previsto, anche se il corso chiude successivamente. Registrare build, account di collaudo, esiti e screenshot.

**CI:** `tests/sta-02-start-window.py`, `tests/joomla-quiz-attempts.php`, `tests/joomla-quiz-time.php`, pacchetto Joomla e due matrici database. Modalità scelta: rifiuto conservativo **senza troncamento**; l'alternativa opzionale non è introdotta.

## CORE-17 — plugin Joomla User Actions Log (19/11/2026)

**Collaudo manuale Joomla pendente:** installare il pacchetto 0.62.0-dev e verificare in Estensioni il plugin **JD Learn - Action Log** nel gruppo actionlog, abilitato al primo impianto. Disabilitarlo volontariamente e verificare che un upgrade non modifichi l'impostazione. Eseguire transizioni valide (revisione, ritorno, pubblicazione, ritiro, archivio, ripristino) e controllare in Utenti → Registro azioni utente attore, tipo operazione e ID corso/versione; nessun titolo, dato studente o informazione riservata deve apparire nel payload. Una transizione negata o annullata non deve generare eventi; a plugin disattivato nessun nuovo evento, ma transizioni ancora operative. Verificare che i log quiz e iscrizioni già scritti dal writer didattico non siano duplicati. Ripetere con IT/EN, permessi ACL ridotti e controllare l'installazione/upgrade da versione precedente.

**Test automatici:** `python3 tests/core-17-actionlog.py`, package e manifest, regressioni Joomla nelle matrici PHP 8.3/MySQL e PHP 8.4/MariaDB. Gli automatismi non certificano da soli l'interfaccia reale del registro azioni.

## LIFE-01 — anteprima di impatto e purga conservativa (19/11/2026)

**Collaudo Joomla manuale pendente:** (1) cestinare un corso mai usato, accedere a Verifica impatto, visualizzare conteggi zero e revisioni editoriali; confermare POST e verificare rimozione del solo corso e della sua history; (2) corso con edizioni/iscrizioni/lezioni/docenti/percorsi/mail/associazioni: riepilogo non-zero, assenza pulsante conferma e permanenza del record; (3) provare GET diretto senza ACL, POST senza CSRF/flag/con revisione obsoleta, corso non cestinato, utenti bloccati: nessuna eliminazione; (4) modifica concorrente dopo l'anteprima, nuova dipendenza prima del POST: il server rifiuta la purga; (5) verificare IT/EN, tastiera/screen reader, ritorno all'elenco, archiviazione e ripristino invariati. Registrare risultato e build prima di segnare accettato.

## DOC-06 — Aiuto nativo nei campi del corso (19/11/2026)

**Collaudo manuale Joomla pendente:** aprire il form del corso con lingua backend italiana e inglese. Controllare testo delle descrizioni su immagine decorativa e alt, obiettivi/destinatari/difficoltà, separazione accesso scheda/contenuti, metadati SEO e quattro date (pubblicazione vs didattica). Verificare tastiera, screen reader e viewport mobile, assenza di HTML non voluto e corretto rendering Joomla nativo. Ripetere il salvataggio di un corso esistente e la creazione di un nuovo corso: nessun valore, default, ACL, calendario, versioning o lingua deve cambiare per l'aggiunta degli aiuti. Non segnare DOC-06 completo: rimangono tutti gli altri form e comandi amministrativi. 

## DOC-08 — Prima matrice flussi core e guide (19/11/2026)

**Evidenza automatica prevista:** eseguire `python3 tests/contextual-help-doc05.py`: il gate DOC-08 verifica 10 righe con requisito univoco, guida prodotto presente, aiuti contestuali IT/EN in viste realmente distribuite e stato esplicito. La presenza dei file non certifica il contenuto editoriale e non rappresenta il completamento di DOC-08.

**Collaudo manuale Joomla ancora non eseguito:** installazione e upgrade dell'artefatto della PR; aprire le guide da catalogo/corso, percorsi, programma video, materiali, comunicazioni, coda, attestati, configurazione Scheduler e docenti; verificare lingua IT/EN e rinvio al capitolo pertinente; eseguire il flusso con amministratore abilitato e con profilo ACL ridotto, inclusi focus/tastiera e screen reader. Registrare build, data, profilo, risultato e difetti senza considerare eseguito alcun caso per la sola CI.

**Residuo DOC-08:** completare la mappatura di tutti gli altri flussi in perimetro 1.0.0 e ricondurre gli esiti ai criteri della matrice canonica; nessun avanzamento percentuale per questa tranche puramente documentale.

## SCR-01 — suite HTTP catalogo e categorie (19/11/2026)

**Integrazione automatica:** `tests/http-smoke.py` verifica sulla installazione Joomla usa-e-getta: URL di ricerca condivisibile, combinazioni di filtri selezionati, riapertura degli URL senza ripristino della sessione, offset paginazione 12 oltre il singolo risultato e nuovo invio GET che ritorna alla prima pagina; categoria con ordine pubblicazione, selezioni, opzioni editoriali, risultato vuoto e collegamento Azzera filtri contestuale.

**Da verificare manualmente:** browser reale con cronologia indietro/avanti (il test HTTP simula solo richieste GET successive), numero di corsi >12 e paginazione su più pagine, URL SEF multilingua, pagine indipendenti corsi/sottocategorie, override global/menu, profili ACL multipli e layout responsive. Non segnare i criteri SCR-01/url_filters o SCR-01/pagination come completamente accettati senza evidenze di questi scenari.

## SCR-01 — parità filtri Categoria e navigazione URL (19/11/2026)

**Collaudo manuale Joomla pendente.** Nel Catalogo selezionare una pagina successiva e applicare una ricerca o un altro filtro: deve tornare a pagina 1, senza conservare limitstart in sessione. Aprire URL con filtri condivisi e controllare pagina, refresh, indietro/avanti e limite server (default 12). Nella pagina Categoria provare tutti i cinque ordinamenti (editoriale, recenti, titolo, durata, pubblicazione), filtri per disponibilità, ricerca, tag, durata, difficoltà, docente e percorso, passando tra griglia/elenco. Nessun corso esterno alla categoria discendente o non autorizzato deve comparire. Verificare lista vuota e Azzera filtri, che conserva la categoria attiva senza reset del contesto. Verificare paginazione indipendente delle sottocategorie, categorie multilingua e i link SEF Joomla.

## SCR-01 — ordinamento editoriale configurabile (19/11/2026)

**Collaudo Joomla pendente:** aprire corsi pubblicati in backend, assegnare posizione 1, 2 e 0, salvare/ricaricare; verificare ordinamento 1 prima di 2, non assegnati ultimi e ID discendente a pari posizione nel catalogo e categoria. Controllare override globali e menu, filtri e paginazione, altre opzioni di ordinamento (titolo, pubblicazione, durata, recenti), visibilità ACL/lingua e bozza, input negativi/sopra 9999 e doppio salvataggio concorrente. Testare IT/EN e duplicazione corso, senza variare le versioni pubblicate.

## SCR-01 — errore recuperabile nel catalogo (19/11/2026)

**Collaudo manuale Joomla pendente:**
1. Aprire catalogo con filtri, ordinamento e pagina; simulare errore DB/servizio. Devono apparire messaggio generico localizzato e Riprova, **non** «Nessun corso», card parziali, stack trace o dettagli database.
2. Seguire Riprova dopo ripristino: gli stessi parametri URL (ricerca, filtri, categoria, ordinamento e pagina) devono essere conservati, con corsi nuovamente visibili secondo ACL.
3. Senza errore, zero risultati deve presentare il testo e Azzera filtri previsti, non il nuovo alert.
4. Ospite e studente: ripetere guasto in fase elenco e successive (docenti, iscrizioni, pending, paginazione). Nessun dato parziale deve essere esposto.
5. I veri errori 401/403/404 devono mantenere l'esito e non diventare schermate pubbliche con Riprova.
6. Verificare browser avanti/indietro, griglia/elenco, IT/EN. Nessuna mutazione e nessun salvataggio locale.

## SCR-01 — ordinamento catalogo per data di pubblicazione (19/11/2026)

**Collaudo manuale Joomla pendente:** creare due corsi pubblicati in ordine differente rispetto agli ID e aggiornare una bozza senza ripubblicarla. Nel catalogo selezionare «Data di pubblicazione (più recente)»: il corso con versione pubblicata più recente deve precedere l'altro; una modifica non pubblicata non deve cambiare la posizione. Verificare pari timestamp (tie-break ID decrescente), pagina successiva senza duplicati, filtri, categoria, ACL per due ruoli, guest e lingua IT/EN. In opzioni globali e nei menu Corsi/Categoria impostare il nuovo ordinamento predefinito, quindi disabilitare la scelta di ordinamento e verificare che parametri GET forzati non lo sostituiscano. Verificare che «Più recenti» conservi il precedente ordine per ID e che non vi sia alcun effetto su iscrizioni o progresso.

## SCR-01 — richieste d'approvazione in sospeso sulle card catalogo (19/11/2026)

**Collaudo Joomla reale pendente:**
1. Con utente autenticato, individuare corso visibile ad ammissione con approvazione; inviare una richiesta dalla scheda del corso. Tornare al catalogo: la card deve mostrare «La tua richiesta di iscrizione è in attesa di approvazione», non un invito duplicato a richiedere l'accesso.
2. Ripetere con elenco e griglia, filtri/paginazione, e lingua diversa. Solo i corsi presenti nella pagina devono essere interrogati, senza N+1.
3. Accettare la richiesta: la card deve mostrare lo stato dell'iscrizione personale e l'eventuale azione Riprendi/Rivedi, non la vecchia indicazione pendente.
4. Respingerla o annullarla: non deve più apparire la dicitura «in attesa»; le azioni del corso rimangono sottoposte alla modalità di ammissione e alle finestre correnti.
5. Verificare che una richiesta pendente di studente A **non** sia visibile a studente B o ospite; controllare anche account bloccato e sessione già aperta dopo revoca.
6. Verificare cambio della versione pubblicata e richieste storiche: lo stato pending della vecchia edizione non deve apparire come richiesta pendente per la versione corrente.
7. Verificare annunci accessibili (`role="status"`), link scheda corso, assenza di leak nei metadati e nessuna creazione automatica di nuove richieste.

**Nota:** i controlli strutturali verificano query limitata, per utente e versione; il test browser/Joomla resta separato.

## EDGE-01 — download attestato e blocco dell'account Joomla (19/11/2026)

**Collaudo Joomla reale — pendente:**
1. Con un utente attivo che possiede un attestato pronto, verificare elenco e download PDF e salvare il collegamento diretto.
2. Senza chiudere la sessione dello studente, bloccare l'account nell'amministrazione utenti core Joomla. Aprire nuovamente il vecchio URL di download (GET e HEAD, se disponibile): rispondere 403 senza trasmettere PDF, dati di attestato o storage.
3. Ripetere dalla sessione esistente su «I miei attestati»: la lettura personale non deve restituire titoli o seriali a un account bloccato.
4. Riattivare lo stesso account e verificare che l'attestato originale sia nuovamente consultabile, con seriale, data, snapshot e PDF immutati; nessuna riemissione.
5. Provare URL manipolato di un altro iscritto, ospite, iscrizione inesistente e account cancellato; verificare 404/401/403 e nessuna divulgazione di documenti.
6. Verificare messaggio localizzato IT/EN, header `Cache-Control: private, no-store`, e compatibilità con attestati in `pending_render`; un account bloccato non deve provocare una generazione PDF.
7. Verificare che l'intervento non cambi i criteri di accesso all'aula, lo stato del corso o il download di materiali didattici.

**Nota:** l'integrazione automatica Joomla usa lo stesso oggetto User prima/dopo blocco per simulare una sessione già aperta; il test manuale browser resta necessario.

## SCR-07 — uscita con risposte quiz non salvate (19/11/2026)

**Collaudo manuale browser/Joomla pendente:**
1. Aprire quiz con tentativo in corso e nessuna modifica: lasciare la pagina; non deve apparire l'avviso di perdita delle risposte.
2. Cambiare almeno una risposta e navigare verso un'altra pagina, chiudere o ricaricare la scheda: il browser compatibile mostra l'avviso di uscita; scegliendo Resta, risposte e stato del quiz restano immutati.
3. Premere Salva e attendere la conferma server: l'avviso non deve comparire alla successiva navigazione senza nuove modifiche.
4. Con rete interrotta o una richiesta di salvataggio non confermata: verificare che venga segnalato il rischio di perdita, senza conferma di persistenza; nessun retry o salvataggio via unload. Verificare la gestione di retry e conflitti con un'altra scheda.
5. Consegnare con successo o raggiungere la chiusura server del tentativo: il redirect interno non deve attivare una richiesta superflua di conferma uscita.
6. Controllare desktop/mobile su Chrome, Firefox e Safari dove possibile: l'avviso è generico e subordinato alle politiche browser; le chiusure forzate non sono intercettabili. Nessuna risposta o credenziale in localStorage/sessionStorage/URL.

## SCR-04 — percentuale dei percorsi con base completa (19/11/2026)

**Casi da collaudare su Joomla reale — non ancora eseguiti:**
1. Percorso con due corsi obbligatori entrambi visibili: studente iscritto soltanto al primo (anche se completato al 100%). La pagina deve mostrare i corsi e gli stati individuali, ma **non** una percentuale aggregata del percorso.
2. Iscrivere lo stesso studente anche al secondo con regole didattiche valide: la percentuale appare ed è la media dei corsi applicabili. Non deve raggiungere 100% se un corso necessario è incompleto o momentaneamente indisponibile.
3. Rimuovere l'accesso a un corso del percorso senza svelarne nome o descrizione: il placeholder resta anonimo e l'aggregato non deve apparire senza evidenza della base completa.
4. Verificare percorso senza corsi, studente ospite e due studenti distinti: nessuna percentuale personale non fondata o visibilità incrociata.
5. Verificare una versione storica con regole mancanti/non valide: l'aggregato viene nascosto senza cancellare lo storico. Un corso in modalità sola consultazione con iscrizione e regole valide non contribuisce numericamente; il percorso interamente in consultazione non presenta una percentuale.
6. Verificare sospensione delle lezioni, stato dell'iscrizione, lingua/ACL e navigazione; nessuna azione di modifica viene eseguita dalla vista percorso.

**Nota:** CI e test unitari controllano l'eleggibilità al calcolo; l'accettazione Joomla manuale resta separata.

## CAT-02 / SCR-03 — CTA contestuali scheda corso (19/11/2026)

**Casi da collaudare su Joomla reale — non eseguiti:**
1. Ospite su scheda pubblica: deve comparire Accedi, mai dati personali o POST per iscrizione; con utente senza livello contenuti deve comparire il messaggio di accesso richiesto.
2. Autenticato autorizzato senza iscrizione, corso automatico aperto: Iscriviti e inizia. Il POST deve restare protetto da CSRF/idempotency key e conservare l'opt-in facoltativo (predefinito non selezionato).
3. Autenticato senza iscrizione, corso ad approvazione: Richiedi accesso, senza accesso anticipato all'aula. Dopo una richiesta pendente della versione corrente deve comparire soltanto lo stato In attesa di approvazione, senza un secondo modulo POST.
4. Dopo approvazione e iscrizione, aprire la scheda da utente proprietario: Inizia prima dell'attività, Riprendi dopo progresso, Rivedi dopo completamento. Il link deve usare l'ID dell'iscrizione e le verifiche correnti di `enrollment.resume`.
5. Iscrizione sospesa, annullata, scaduta, versione/edizione non disponibili o corso con didattica disabilitata: indicazione indisponibilità, nessuna nuova iscrizione e nessun link Riprendi; verificare anche che un URL diretto al task non aggiri l'autorizzazione.
6. Utente B non deve vedere iscrizioni o richieste dell'utente A; provare cache, ACL, categoria, lingua e paginazione. Le richieste di una versione diversa non devono essere scambiate per pending corrente.
7. Verificare responsive, traduzioni IT/EN e regressione sui link a docenti, programma, attestati e materiali pubblici.

**Limite:** una verifica runtime della pagina è necessaria; CI superata non equivale a collaudo Joomla dell'utente. Le etichette e i gate non concedono diritti né sostituiscono `LearningService`.

## CAT-01 — filtro «Solo corsi disponibili» (19/11/2026)

**Comportamenti da testare sul sito Joomla di prova (non ancora eseguiti):**

1. Creare nella medesima categoria visibile quattro corsi pubblicati, con ammissioni/erogazione rispettivamente aperte/attive, chiuse/attive, aperte/sospese, chiuse/sospese. Senza spuntare il filtro tutti i corsi autorizzati devono restare nel catalogo.
2. Attivare «Solo corsi attualmente disponibili»: deve rimanere unicamente il corso con **ammissioni aperte e didattica attiva**. La scelta deve restare selezionata nella ricerca e nella paginazione; togliendola devono ritornare gli altri corsi.
3. Ripetere il controllo con categoria, ricerca, docente, tag, lingua e paginazione. Il filtro non deve aggirare ACL, finestre di pubblicazione o restrizioni sulle categorie.
4. Disabilitare i filtri tramite opzioni Joomla del catalogo: una URL GET costruita manualmente con `filter[available]=1` non deve filtrare l'elenco.
5. Da utente anonimo e autenticato, verificare che il filtro non conceda accesso ai contenuti o alle iscrizioni. Un corso con erogazione sospesa deve rimanere navigabile nella scheda promozionale quando il filtro è spento.
6. Quando il risultato è vuoto, deve apparire l'azione di azzeramento filtri; premendola si torna al catalogo non filtrato, mantenendo la categoria di contesto.

**Nota di perimetro:** «disponibile» significa ammissioni aperte **e** erogazione abilitata; non garantisce l'ammissione di un particolare utente né sostituisce l'autorizzazione alla fruizione. Accettazione manuale: **non eseguita**.

## SCR-01/card — stato individuale dell'iscrizione nel catalogo (18/11/2026)

1. Come ospite, il catalogo non deve mostrare né interrogare alcuno storico personale. La card deve mostrare soltanto azione pubblica e modalità promozionale.
2. Come utente con iscrizione propria attiva, verificare **Non iniziati/In corso/Completati** e collegamento **Riprendi/Rivedi** quando il servizio dashboard consente accesso. Il collegamento deve usare l'ID dell'iscrizione, non l'ID del corso, e passare dalle autorizzazioni di enrollment.resume.
3. Sospendere/annullare/scadere l'iscrizione o rimuovere ACL/disattivare erogazione: la card non deve mostrare Riprendi; deve mantenere Esamina il corso e un avviso di disponibilità coerente. Testare versioni/edizioni chiuse e lingua non disponibile.
4. Utenti A e B sullo stesso corso: ciascuno visualizza solo il proprio stato, inclusa navigazione avanti/indietro. Verificare assenza di leak mediante caching, login/logout e utenti bloccati.
5. Con iscrizione corrente presente non deve essere mostrata una falsa offerta di nuova iscrizione auto/approval. Richiesta di approvazione non convertita in iscrizione: non dedurre che sia attiva; questo caso resta da coprire separatamente.
6. Verificare griglia/elenco, filtri, paginazione, IT/EN, tastiera e mobile. Più iscrizioni storiche a un corso: prevalga la più recente fra quelle interrogate.

**Automazione:** verifica strutturale con suite CI. **Collaudo Joomla manuale:** In attesa; requisito SCR-01/card non dichiarato completamente collaudato.

## SCR-01/card — docenti visibili nel catalogo (18/11/2026)

1. Pubblicare un corso con due docenti pubblici, diversi e visibili: la card deve mostrarne i nomi con link alle rispettive schede e mantenere ordine della versione pubblicata, in griglia e in elenco.
2. Rendere uno dei docenti non pubblicato, non accessibile all'utente o in altra lingua: non deve comparire, pur lasciando invariata la scheda del corso; verificare il profilo con categoria antenata non pubblicata/non accessibile.
3. Modificare il nome o l'associazione del docente in bozza e non ripubblicare: il catalogo continua a mostrare la versione congelata (se il profilo resta visibile). Ripubblicare e verificare l'aggiornamento.
4. Guest e autenticato con ACL diverse: ogni card espone solo docenti autorizzati; nessuna email/account privato, bio non esposta nella card. Course senza docenti: nessuna etichetta vuota.
5. Verificare lingua IT/EN, tastiera, responsive, filtri, categoria scoped e paginazione. Un vecchio snapshot malformato non deve produrre pagina 500.

**Gate automatico:** frontend contract e CI. **Collaudo manuale Joomla:** In attesa. Lo stato personale di iscrizione e altri residui SCR-01 restano da completare.

## SCR-01 — azione disponibile nelle schede del catalogo (18/11/2026)

1. Catalogo pubblico: ogni scheda mostra **Esamina il corso**; il titolo e l'azione rimandano allo stesso corso, senza creare iscrizioni/progressi. Verificare link in griglia ed elenco, mouse e tastiera.
2. Un corso pubblicato con ammissioni/erogazione attive, modalità auto, mostra **Iscrizione automatica disponibile**; quello con approvazione mostra **Iscrizione soggetta ad approvazione**. La successiva pagina dettaglio deve applicare ACL, autenticazione e validazioni reali.
3. Corso con ammissioni chiuse o erogazione sospesa: niente promessa di modalità disponibile; resta il link informativo alla scheda. Lo stesso vale per modalità esplicita o non riconosciuta.
4. Confrontare guest, utente privo di accesso, utente autorizzato e già iscritto: non deve apparire sulla card un'azione di iscrizione diretta né un falso stato individuale; la pagina corso resta autoritativa.
5. Confermare nessun dato privato nella card, testi IT/EN, CSS responsive, navigazione tastiera e compatibilità con filtri/paginazione.

**Automazione:** `tests/frontend-catalog-options.py` e CI. **Collaudo manuale Joomla:** in attesa. SCR-01/card ancora parziale per lo stato effettivo dell'iscrizione e i docenti per scheda.

## SCR-01 — ordinamento del catalogo per durata (18/11/2026)

1. Pubblicare corsi di 15, 45 e 120 minuti e uno senza durata. Ordinare per **Durata (crescente)**: 15, 45, 120, durata sconosciuta in fondo; ripetere su più pagine con durata uguale per verificare stabilità.
2. Modificare la durata in una bozza senza pubblicarla: l'ordinamento deve rimanere quello della versione pubblicata. Pubblicare l'aggiornamento e ripetere.
3. Impostare la durata come ordinamento predefinito in opzioni componente e override della voce menu catalogo; verificare precedenza menu/globale, e il ritorno a titolo/più recenti.
4. Verificare combinazioni con categoria, tag, lingua, permessi ACL, filtro durata per intervallo, catalogo vuoto e navigazione avanti/indietro; opzione di ordinamento nascosta deve ignorare il parametro GET forgiato.
5. Controllare testi IT/EN e presentazione responsive/tastiera; verificare paginazione con duplicati di durata.

**Automazione:** test struttura frontend e suite CI; **accettazione manuale Joomla:** pendente. Requisito SCR-01 ancora parziale; SemVer invariato.

## SCR-01 — azzeramento filtri in catalogo senza risultati (18/11/2026)

1. Catalogo pubblico: cercare una stringa inesistente; stato vuoto e collegamento «Azzera filtri». Seguire il collegamento: ricerca e filtri azzerati, pagina iniziale, corsi visibili secondo ACL.
2. Ripetere con filtri categoria, tag, difficoltà, durata, docente, percorso in combinazione; azzeramento completo senza conservare parametri `filter[...]` e senza cambiare permessi.
3. Entrare in un catalogo limitato da `catid`, applicare un filtro senza risultati e azzerarlo: `catid` deve restare nell'URL, senza allargare l'ambito del catalogo.
4. Senza filtri attivi, nessun link di azzeramento; con filtri disabilitati nelle opzioni Joomla, nessun link. Verificare IT/EN, tastiera, mobile e browser back/forward.

Verifica automatica: `tests/frontend-catalog-options.py` e suite CI. Collaudo Joomla reale ancora pendente. Il requisito SCR-01 rimane parziale per gli altri criteri.

## SCR-05 — collegamento al catalogo nello stato vuoto dei corsi (18/11/2026)

1. Accedere come studente senza iscrizioni e aprire **I miei corsi**: nello stato vuoto compare il collegamento localizzato **Tutti i corsi**; verificare che conduca al catalogo filtrato da visibilità e ACL Joomla senza produrre iscrizioni.
2. Attivare un filtro progresso che produca zero risultati: mostrare il messaggio vuoto e lo stesso collegamento, navigabile da tastiera.
3. Accedere con iscrizioni visibili: mantenere le card, azioni di ripresa e l'ordinario link al catalogo, senza duplicare link nel messaggio vuoto.
4. Verificare lingua italiana e inglese, output mobile e desktop, ACL del catalogo e assenza di dati di altri utenti.

**CI:** verificare markup e lint PHP; **accettazione manuale Joomla:** pendente. Nessun cambiamento di schema, SemVer o percentuale funzionale globale fino alla riconciliazione.

## CAT-02 — riepilogo delle regole di completamento nella scheda corso (PR #657 integrata, 18/11/2026)

1. Aprire come ospite la scheda pubblica di un corso con versione didattica pubblicata in modalità apprendimento: sotto il calendario e prima del programma compare `Come si completa il corso` con lezioni obbligatorie, ordine di fruizione e soglia di completamento video.
2. Per una versione con attestato abilitato nelle regole pubblicate verificare `previsto` e la nota che distingue configurazione/emissione effettiva; per una versione senza attestato verificare che sia indicato come non previsto.
3. Per un corso di sola consultazione verificare che non siano promessi completamento, tracciamento o attestato; nessuna esposizione delle regole quiz private o di contenuti di lezioni non ancora disponibili.
4. Controllare IT/EN, accessibilità di titoli e paragrafi, sito desktop/smartphone e assenza di regressioni di login, iscrizione, curriculum e permessi ACL.
5. Provare soglia video non intera (basis points) e modalità di sequenza libera/sequenziale; il testo deve coincidere con le regole della versione **pubblicata**, non con bozze in lavorazione. L'abilitazione globale dell'emissione può impedire comunque un attestato previsto.

Verifica automatica Joomla: `tests/joomla-course-presentation.php` esteso e superato nella CI #44399697715 su entrambe le matrici PHP/database. Collaudo manuale Joomla da acquisire.

## QUI-02 — cestino conservativo banca domande (PR #653 integrata, 18/11/2026)

## STA-02 — durata quiz e finestra didattica (PR #648 integrata, 18/11/2026)

1. Su un corso con quiz a tempo di 60 secondi e un'iscrizione in scadenza tra 30 secondi, verificare che il frontend non offra l'avvio (`can_start=false`). Una richiesta diretta di avvio deve ricevere HTTP 403, senza nuova riga di tentativo o consumo del limite.
2. Rimuovere la scadenza e ripetere: il quiz può partire e conserva i 60 secondi dichiarati. Provare una finestra con tempo esattamente sufficiente.
3. Ripetere per scadenza della lezione (`publish_down`), edizione (`ends_at`), iscrizione (`ends_at`) e calendario corso (`ends_at`/`publish_down`); prevale la scadenza più vicina.
4. Verificare che una sessione già avviata e con scadenza salvata non venga alterata retroattivamente, né cambino i tentativi esistenti; controllare riapertura, proroga autorizzata e deadline già persistita.
5. Verificare fusi orari e date UTC, ruoli ACL, accesso da smartphone e il messaggio localizzato di finestra non disponibile. Non viene introdotta alcuna modalità di troncamento implicito: se il timer non entra nella finestra, l'avvio è rifiutato.

Test Joomla disposable: `tests/joomla-quiz-attempts.php` superato nella CI #44393803890. Accettazione manuale su sito Joomla di prova pendente.

## MAIL-08 — doppio invio della mail di prova (PR #646 integrata, 18/11/2026)

1. Aprire Comunicazione corso come amministratore autorizzato, compilare modello e destinatari, generare Anteprima e fare **Invia prova**: attendere una sola riga `com_jlms.course.message` diretta esclusivamente all'amministratore.
2. Ripetere identicamente il medesimo POST `coursemessage.test` (stesso `mutation_id`, anche contemporaneamente): l'evento deve comparire **una sola volta** nella coda e non generare due invii SMTP.
3. Tornare alla schermata, creare una nuova anteprima e fare intenzionalmente un altro invio di prova: la nuova richiesta ha un identificativo diverso e deve generare una nuova email.
4. Manomettere il campo `mutation_id` (mancante, stringa arbitraria o UUID non v4) e ripetere con CSRF/ACL non validi: HTTP 400/403 e nessun nuovo messaggio.
5. Controllare lingua e preferenze notifica, nessun invio agli iscritti, nessuna regressione di `coursemessage.queue` e della conferma esplicita.

**Accettazione Joomla/SMTP manuale ancora pendente**; la CI #44391196376 ha superato i quattro gate previsti, senza costituire prova manuale del doppio invio SMTP.

## NOT-01 / MAIL-02 — completamento corso tramite quiz (PR #643 integrata)

1. In un corso con una lezione quiz obbligatoria come ultimo requisito, completare e superare il quiz con un iscritto attivo: il corso diventa completato e viene creata una sola comunicazione `com_jlms.course.completed` nella Coda email.
2. Verificare che il messaggio contenga data completamento, titolo corso e URL dashboard; eseguire il task email con template IT/EN e recapito controllato.
3. Inviare nuovamente la stessa mutazione (idempotency key invariata) e riaprire il quiz: nessuna seconda notifica, nessuna regressione dei progressi.
4. Ripetere con quiz non superato, altro tipo di lezione conclusiva e certificato disabilitato; il messaggio parte solo al primo completamento reale ed è indipendente dall'eventuale attestato.
5. Ripetere con un template email non valido e verificare rollback atomico della progressione, poi correggere il modello prima di ritentare.

Verifica automatica superata in CI #44387407274: `tests/joomla-quiz-attempts.php`. Accettazione manuale Joomla/SMTP pendente.

## LCM-05 — Finder dopo archiviazione/ripristino corso (0.60.8-dev)

1. Indicizzare un corso Pubblicato e visibile a Smart Search; accertare la presenza nei risultati.
2. Dal programma didattico selezionare Archive: stato Archiviato, ammissioni ed erogazione chiuse; verificare che il risultato sparisca da Smart Search e che l'URL diretto non apra la didattica.
3. Eseguire Restore del workflow: stato editoriale Pubblicato, ma didattica/ammissioni ancora chiuse; verificare eventuale risultato promozionale secondo i filtri Finder, senza possibilità di accedere all'aula.
4. Provare evento Finder non disponibile, archiviazione negata e transizione fallita: nessuna notifica post-commit per operazione annullata; nessuna perdita di dati.
5. Verificare cache, SEO, categorizzazione, ruoli Joomla e upgrade da 0.60.7-dev.

Test Joomla `tests/joomla-publication-review.php` osserva gli eventi e lo snapshot persistito; verifica end-to-end Smart Search su sito reale non ancora eseguita.

## LCM-05 — Smart Search dopo cestinazione/ripristino corsi (0.60.7-dev)

1. Indicare un corso pubblicato e senza programmazione temporale in Smart Search e verificarne la presenza nell'indice.
2. Cestinare il corso da toolbar: verificare l'assenza del risultato dopo il refresh automatico, anche con cache, URL diretto e filtri Joomla.
3. Ripristinare da Cestinato a Non pubblicato: il risultato non deve tornare disponibile e ammissioni ed erogazione restano chiuse.
4. Pubblicare esplicitamente secondo il workflow approvato e verificare il ritorno del risultato quando tutte le altre condizioni Smart Search sono soddisfatte.
5. Ripetere azione massiva e rollback con ID non valido e profili ACL differenti; verificare che una failure del Finder non annulli la modifica editoriale già confermata.

Test Joomla automatico degli eventi post-commit in `tests/joomla-courses-ui.php`. La verifica dell'indice Finder sul sito reale è **pendente**.

## LCM-05 — messaggio distinto per erogazione sospesa nei percorsi (0.60.6-dev)

1. Su un percorso pubblicato con corso pubblicato e didattica fruibile, verificare che non compaia il messaggio di sospensione.
2. Sospendere l'erogazione del corso: la scheda percorso dell'utente con accesso deve mostrare un messaggio chiaro di sospensione didattica (IT/EN), non un generico errore di autorizzazione.
3. Accedere come utente privo del livello Joomla del corso: permane l'avviso generico di accesso riservato, senza informazioni sul flag di erogazione.
4. Riabilitare la didattica: il messaggio di sospensione scompare, lo stato didattico e le iscrizioni restano invariati.
5. Verificare la scheda da mobile/desktop e un corso non pubblicato, archiviato o cestinato: nessuna divulgazione tramite URL diretti.

Test automatici: `tests/joomla-pathways.php`, `tests/frontend-pathway-layout.py`. Accettazione Joomla reale non eseguita.

## LCM-05 — indicatore disponibilità nei percorsi (0.60.5-dev)

1. Aprire un percorso pubblicato contenente un corso pubblicato e fruibile: l'azione di accesso al corso segue gli attuali livelli Joomla.
2. Sospendere l'erogazione del corso senza archiviarlo: la scheda del percorso mantiene il corso visibile agli utenti autorizzati ma lo indica come non disponibile alla fruizione. Il link diretto all'aula deve essere negato.
3. Verificare con utente privo del livello di visibilità didattica che il corso rimanga riservato e non esponga contenuti privati.
4. Verificare che iscrizioni e cronologia didattica non cambino per il solo stato operativo; riabilitare l'erogazione e verificare il ritorno alla normale disponibilità.
5. Ripetere con corso archiviato/non pubblicato/cestinato e con ruoli Joomla distinti, IT/EN e mobile.

Test automatico `tests/joomla-pathways.php`; accettazione manuale **non eseguita**.

## LCM-02 / LCM-01 — cestino corsi con chiusura dei flag (0.60.4-dev)

1. Su un corso Pubblicato con iscrizioni ed erogazione abilitate, scegliere **Cestina** dal backend: il corso scompare dalle viste pubbliche e `admissions_open`/`delivery_enabled` sono impostati a 0.
2. Verificare che URL diretti, nuove iscrizioni e ripresa dell'aula non aggirino i blocchi, senza eliminare progressi, iscrizioni e attestati.
3. Eseguire **Ripristina**: il corso torna Non pubblicato e entrambi i flag restano chiusi. Verificare nessuna riapertura automatica al successivo passaggio editoriale.
4. Ripetere con ruoli Joomla limitati e azioni massive, inclusi errori su un elemento: rollback e assenza di modifiche parziali.
5. Verificare upgrade da 0.60.3-dev e assenza di migrazioni schema.

CI Joomla: `tests/joomla-courses-ui.php`. Accettazione Joomla reale **non eseguita**.

## LCM-01 — riattivazione individuale iscrizione (0.60.3-dev)

1. Sospendere una singola iscrizione in corso Pubblicato e verificare l'accesso all'aula negato.
2. Archiviare il corso; riattivazione individuale da backend e POST diretto con token valido devono essere respinte senza audit, notifiche o aggiornamenti iscrizione.
3. Ripetere con corso non pubblicato e cestinato. Ripristinare lo stato Pubblicato con erogazione chiusa: riattivazione sempre negata.
4. Riabilitare esplicitamente l'erogazione e riattivare l'iscrizione con motivo, audit e ACL: la cronologia didattica resta invariata.
5. Verificare che sospensione/cancellazione permesse non siano impedite dal nuovo gate e che le richieste concorrenti non aggirino le verifiche.

Accettazione manuale **non eseguita**; regressione automatica in `tests/joomla-enrollment-state.php`.

## LCM-01 — PR #619, blocco riattivazione anticipata (0.60.2-dev)

1. Su corso Pubblicato, chiudere iscrizioni e sospendere didattica da Operazioni corso; registrare motivo e audit.
2. Archiviare e tentare `admissions_open` e `delivery_resume` dal backend: anteprima bloccata e nessun comando eseguibile. Con POST diretto e CSRF valido il server deve rifiutare senza riaprire flag, creare audit o alterare revisioni.
3. Ripristinare lo stato Pubblicato: entrambi i flag restano chiusi. Solo dopo il ripristino riaprire esplicitamente, con permessi Joomla, motivazione e tracciatura.
4. Ripetere da utente senza permesso e verificare il divieto; verificare inoltre storico iscrizioni, progressi e PDF, accessi via URL diretto, lingue IT/EN e upgrade da 0.60.1.

Stato: **da collaudare manualmente** su installazione Joomla; test automatico in `tests/joomla-manual-enrollment.php` da verificare in CI.

## P0 YouTube #532 — verifica asset e fase esatta dell'errore (seconda tranche)

**P0 YouTube #532 — PR #535 integrata:** merge `26f6b94b46c77e2468cc5ab1ede2fc4b5078b37e`; [CI 43963159315](https://github.com/jeckodevelopment/jd_learn/actions/runs/43963159315) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43963159315/artifacts/14274983577) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `a114132bf1fa303718b1d0954eecce6d6b0c2b54b6e1a49eba7023c8e632b602`. Corretto il registro WAM fermo a 0.45.0 e introdotta una chiave di cache basata su versione e contenuto degli asset/componenti/provider a ogni build; diagnostica dell'editor distinta per HTTP server, rete, JSON, adapter e SDK. **La issue #532 resta aperta** finché non si verifica l'embed YouTube sul sito Joomla reale; non è un collaudo live. Avanzamento globale di riferimento 79,56% invariato.

1. Aggiornare il pacchetto installabile a build post-fix. Aprire il backend dopo pulizia cache Joomla e verifica in finestra privata. In DevTools > Rete verificare che `video-metadata.js` e `video.js` abbiano una chiave di cache aggiornata nell'URL (versione più impronta dei file) (o siano effettivamente riscaricati), senza risposte da cache obsolete; controllare anche `player.js` del plugin.
2. Provare **Rileva di nuovo**: se errore, annotare il nuovo messaggio distinto (HTTP server 5xx, network, response, adapter, player, referrer/CSP/SDK) e il codice HTTP di `lesson.metadata` senza condividere token CSRF, cookie, sessioni, dati privati.
3. Con risposta metadata 200, verificare che `adapter_url` sia sotto `/media/plg_jlmsvideo_youtube/js/player.js`, l'asset risponda 200 e `iframe_api`/iframe YouTube si carichino rispettando il consenso. Se l'iframe parte, verificare durata, salvataggio, pubblicazione e fruizione con account iscritto.
4. Confermare che le politiche CSP, provider plugin/`playback_enabled` e consenso non siano state aggirate. Il fallback manuale di durata non dimostra che il video sia incorporabile.
5. Ripetere su due browser (normale e privato), dopo upgrade e con sito in sottocartella se applicabile; collaudo manuale resta necessario per chiudere #532.

---

## P0 #532 — YouTube: durata metadati e player aula (riproduzione reale obbligatoria)

**P0 YouTube #532 — PR #533 integrata:** merge `ff4e6a376f5b59e2e4ba5d2af63f1cca9832fb2f`; [CI completa 43958890176](https://github.com/jeckodevelopment/jd_learn/actions/runs/43958890176) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43958890176/artifacts/14273243043) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `a967b7d871ac5f4a2ff6be69b11ded00d2cb9706e4b8400f6feff985ba616fac`. Diagnostica editoriale YouTube distinta per CSP/SDK/referrer/embed/timeout e messaggi server 4xx controllati; Referrer-Policy impostata alla raccomandazione Google. **Non c'è ancora una verifica su player YouTube live nel sito interessato: issue P0 #532 resta aperta e la funzionalità non è accettata manualmente.** Percentuale globale di riferimento 79,56% invariata.

1. Verificare YouTube plugin abilitato sia come estensione sia tramite `playback_enabled` e le opzioni JD Learn relative ai video esterni. Se la policy è `site_consent`, controllare che la CMP esponga `window.JlmsConsent.isAllowed('youtube') === true` dopo il consenso esplicito. Non bypassare la CMP per il test.
2. Nella bozza di lezione, inserire un video YouTube noto come incorporabile. In DevTools verificare `POST lesson.metadata`, HTTP e messaggio restituito, caricamento dell'asset JS dell'adapter, della SDK `https://www.youtube.com/iframe_api` e dell'iframe `youtube-nocookie.com`; verificare header Referer e CSP. Distinguere errori 153 (identità), 100 (privato/non trovato), 101/150 (embed negato), CSP e timeout; dopo avvio, la durata deve risultare valida e il ticket non deve essere stale.
3. Creare e salvare, poi pubblicare un corso di apprendimento tracciato: il backend deve confermare la durata (o richiedere fallback manuale esplicito) e non consentire la pubblicazione incompleta. Il fallback manuale non è una riparazione dell'embed e non deve concedere accesso fuori dalle regole Joomla.
4. Con iscritto autorizzato, aprire il frontend e verificare che l'iframe sia visibile e riproduca audio/video, che play/seek/pausa non generino errori, che il progresso venga registrato solo in modo autorizzato. Testare ospite e iscritto senza livello di accesso: nessuna riproduzione protetta.
5. Ripetere con consenso rifiutato/revocato, browser con blocco script, assenza provider, corsi consultabili, editor IT/EN, SEF e template di prova. Nessun fallback verso embed diretto non protetto. Registrare console/rete, versione e screenshot. I test CI con SDK simulato **non validano** la riproduzione YouTube live: mantenere aperta l'issue #532 fino al collaudo reale.

---

## AUT-03 — Avvisi trascrizioni nel controllo preliminare (collaudo Joomla pendente)

**Evidenza automatica post-merge:** PR #530 integrata (merge `771b6cd71a1c4dc07d4202c90d57dd4c140a6cab`), [CI 43955475409](https://github.com/jeckodevelopment/jd_learn/actions/runs/43955475409) 4/4 verde, [pacchetto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43955475409/artifacts/14271932231) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `6421b61d36d8c6b245538474f8747bad434b665d62da3183ccaa0bb6ec3f813c`. **Collaudo manuale Joomla ancora da eseguire**; riferimento globale 79,56% invariato.

1. In backend aprire un corso con lezione video senza trascrizione; il controllo preliminare deve mostrare un avviso facoltativo, non un blocco. Lo stesso vale per una lezione audio senza trascrizione (con il suo allegato valido).
2. Inserire una trascrizione significativa e riaprire il preflight: l'avviso relativo deve sparire. Ripetere con testo HTML composto solo da spazi o `&nbsp;`: l'avviso deve restare.
3. Più lezioni video/audio senza trascrizione devono produrre un avviso aggregato per tipo, senza duplicati. Verificare corsi in modalità consultazione e apprendimento e traduzioni IT/EN.
4. Verificare che l'assenza della trascrizione **non blocchi** una pubblicazione altrimenti valida. La validazione server di durata/provider/materiali continua indipendente. Controllare le caption nei quattro provider **manualmente**: il preflight non ne conosce la disponibilità.
5. Eseguire `php tests/run.php` e test d'integrazione Joomla; test automatici non equivalgono all'accettazione manuale.

---

## FE-08 — Sottocategorie e corsi nella stessa pagina (collaudo Joomla pendente)

1. Creare una voce menu di tipo **Categoria** per una categoria valida; verificare due opzioni indipendenti **Mostra sottocategorie** e **Mostra corsi**, entrambe Sì per default. Aprire la pagina senza modificare il menu e controllare che entrambe le sezioni appaiano.
2. Provare le quattro combinazioni Sì/Sì, Sì/No, No/Sì e No/No. Ogni opzione deve nascondere solo la rispettiva sezione, inclusi stato vuoto e paginazione. Intestazione/descrizione della categoria e link alle categorie devono rimanere presenti.
3. Creare due voci menu per la stessa categoria con opzioni differenti; navigare mediante le due voci, con SEF attivo e disattivo, verificando che la scelta non contamini l'altra voce e che un parametro GET artigianale non possa cambiare la configurazione. Su URL diretti senza menu categoria corrispondente deve prevalere il default retrocompatibile.
4. Con oltre 20 sottocategorie e 20 corsi, verificare paginazioni indipendenti; filtro corsi non deve modificare la pagina sottocategorie. Provare ricerca, ordinamento, filtri, layout griglia/elenco e categorie senza figli o senza corsi.
5. Come ospite e account autenticato, verificare lingue IT/EN, categorie non pubblicate, in cestino, gruppo di accesso non autorizzato e antenati nascosti. Corsi o categorie non autorizzati non devono comparire né nei collegamenti né nei conteggi di paginazione; URL diretti devono continuare a negare contenuti non visibili.
6. Verificare responsive 13–15 pollici, smartphone, zoom 200%, navigazione tastiera, focus e override template Joomla; upgrade ZIP mantenendo l'aspetto delle voci menu esistenti.

**Evidenza post-merge:** PR #520, merge `231ed6c1f7d5ea3468e616a94ba3221230575eb5`, [CI 43937676157](https://github.com/jeckodevelopment/jd_learn/actions/runs/43937676157) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43937676157/artifacts/14262196438) ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `ef30b163772df6a3dbadd8b6549ceb96c62d4b1b13539db4595811314ceb9b06`. Collaudo manuale Joomla non ancora registrato.

**Automazione:** tests/frontend-category-sections.py nel gate tools/check.py e suite Joomla categorie/corsi esistenti. Un gate verde non costituisce collaudo manuale.

---

## FE-07 — Catalogo e dettaglio percorsi formativi (collaudo Joomla pendente)

1. In backend creare/modificare un percorso e impostare una copertina locale facoltativa con il selettore Media Joomla. Salvare, riaprire e verificare persistenza; rimuovere la copertina. Provare file inesistente, path remoto, traversal, file non immagine, immagine troppo grande e alt mancante se non decorativa: il salvataggio deve respingerli. Un'immagine decorativa deve avere alt vuoto.
2. Catalogo: schede responsive con titolo, introduzione e categoria; immagine soltanto quando configurata, senza placeholder obbligatorio. Verificare lingua IT/EN, 200% zoom, smartphone, mouse e tastiera.
3. Dettaglio: copertina quando presente, descrizione, conteggio delle relazioni del percorso e sequenza verticale dei corsi in ordine editoriale. Verificare 0, 1 e diversi corsi; nessuna regola di prerequisito dedotta dalla posizione in elenco.
4. Ospite: vedere solo i dati pubblici e corsi accessibili, senza progresso personale, percentuali o nomi di corsi invisibili. Percorso non pubblicato o categoria non accessibile deve essere negato.
5. Studente autenticato: mostrare progresso del percorso solo se proiettato dal servizio, e percentuale dei singoli corsi solo quando disponibile e riferita a un'iscrizione. Provare corso completato, sospeso, archiviato, non pubblicato, consultazione senza progresso applicabile e corso rimosso. Nessuna iscrizione automatica di massa.
6. Assegnare due utenti differenti: garantire isolamento dei dati, nessuna lettura di frequenze altrui, nessun accesso ai contenuti didattici non autorizzati.
7. Nuova voce menu Joomla **Dettaglio percorso**: selezionare un percorso pubblicato e verificare routing SEF/non-SEF, breadcrumb e URL diretto. Verificare upgrade dalla versione precedente e conservazione dei percorsi con params_json preesistente.

**Evidenze di merge:** PR #518, merge `4b01daddaf5ebff7987c6cddfcab1c73758968ff`, [CI 43935233607](https://github.com/jeckodevelopment/jd_learn/actions/runs/43935233607) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43935233607/artifacts/14258414878) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `46e7d0a91fc95b0e294121c06caa7769b03ff631ef28eb448b7db1c2be6cd8c5`. Collaudo manuale Joomla non ancora registrato.

**Automazione:** tests/frontend-pathway-layout.py (contratti di sicurezza, dati e layout), test esistenti HTTP dei percorsi e integrazione Joomla CI. La CI non sostituisce il collaudo manuale.

---

## FE-06 — Navigazione personale opzionale, collaudo Joomla pendente

1. Creare le quattro voci menu studente: La mia formazione, I miei corsi, I miei attestati e Preferenze. Verificare che il parametro "Mostra navigazione personale" sia No per default in ciascuna.
2. Attivarlo solo su una voce; verificare che la barra appaia esclusivamente quando quella voce è attiva. Ripetere sulle quattro viste e con due voci distinte dello stesso tipo.
3. Controllare il routing con SEF abilitato/disabilitato, voce destinataria assente e lingue IT/EN; accertarsi che un parametro GET non possa attivare la barra.
4. Verificare landmark con etichetta accessibile, voce corrente non cliccabile, aria-current, tabulazione, focus visibile, zoom 200%, smartphone, template con moduli laterali e nessun ID duplicato.
5. Con anonimo, account bloccato e due utenti diversi, verificare che i link non aggirino ACL, consenso, download e protezione dei dati personali. Nessuna modifica a corsi, attestati o progressi tramite GET.
6. Verificare override Joomla, installazione e upgrade; i menu già esistenti devono rimanere privi della barra finché il parametro non viene abilitato.

PR #516 integrata, merge `67985316915ef6d60b25ab0c2de358129f0bf8ee`; [CI 43932007606](https://github.com/jeckodevelopment/jd_learn/actions/runs/43932007606) 4/4 verde (incluso test HTTP Joomla dei parametri menu), [artefatto](https://github.com/jeckodevelopment/jd_learn/actions/runs/43932007606/artifacts/14258847023) ZIP interno pkg_jlms-0.57.7-dev.zip, SHA-256 `6b2add93bb50e901ec33c64f685483dbd9718e094d6222951d7cdd085b06885e`.

Automazione: tests/frontend-personal-navigation.py incluso in tools/check.py. La CI non equivale al collaudo manuale.

---

## FE-05 — La mia formazione: dashboard riepilogativa (collaudo Joomla pendente)

1. Accedere alla voce menu Joomla **La mia formazione** in IT/EN come utente registrato; verificare su template standard e con override, URL SEF e non-SEF. L'anonimo deve ricevere un rifiuto e nessun dato personale; verificare gli header no-store/noindex.
2. Preparare più di 20 iscrizioni con stati non iniziato, in corso e completato, incluse iscrizioni su corsi archiviate/cestinate/non pubblicate: i conteggi devono coprire **l'intero storico**, non la pagina corrente di I miei corsi, e non devono riaprire accessi.
3. Verificare i tre collegamenti a **I miei corsi**, **I miei attestati** e **Preferenze**, e il catalogo; le viste autonome devono mantenere filtri, paginazione, download e consenso invariati.
4. Un corso in corso con attività recente può presentare **Riprendi** soltanto se l'ACL corrente permette l'accesso; provare sospensione, rimozione dei gruppi, scadenza, revoca e cambio lingua: nessuna CTA verso contenuti non disponibili.
5. Quando non ci sono corsi, mostrare lo stato vuoto; quando non esistono attestati, mostrare zero. Il numero attestati deve essere basato solo sui documenti propri non revocati.
6. Con due studenti, verificare la separazione dei dati anche variando i parametri GET `user_id`, `id` e `filter[progress]`. Confrontare la pagina riepilogativa con la vista completa I miei corsi e verificare che la semplice GET non modifichi frequenze o invii email.
7. Verificare navigazione tastiera, 200% zoom, viewport strette con moduli Joomla laterali, titolo e struttura semantica dei conteggi; installazione/upgrade del ZIP.

**Evidenza post-merge:** PR #514 integrata (`cfad66f49620beb205cecb24425215d4030d21d3`); [CI 43928472754](https://github.com/jeckodevelopment/jd_learn/actions/runs/43928472754) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43928472754/artifacts/14256409058), ZIP interno `pkg_jlms-0.57.7-dev.zip` SHA-256 `c4ab11d7b3e57064768b29a8cbee17677134840278b0acfd9759cb77f563e4a8`. **Automazione:** `tests/frontend-learner-summary.py` verifica contratti statici, `tests/dashboard-http.py` verifica la nuova landing insieme ai precedenti scenari di I miei corsi. CI non equivale al collaudo manuale Joomla.

---

## FE-05 — Preferenze personali (collaudo Joomla pendente)

1. Creare una voce menu Joomla "Preferenze JD Learn" in IT e EN, verificando routing SEF e link dashboard.
2. Verificare accesso diretto da anonimo: richiesta di login senza esposizione dello stato del consenso, header privati/noindex.
3. Con utente autenticato privo di consenso, effettuare la richiesta opzionale con checkbox non preselezionata e token CSRF; verificare email, stato pending, link di conferma e successivo confirmed.
4. Revocare il consenso dalla nuova pagina e controllare il ritorno alla vista Preferenze e lo stato aggiornato. Senza token CSRF e da anonimo, la POST deve essere rifiutata.
5. Ripetere con due account, IT/EN, mobile e template Joomla alternativi. Verificare che la dashboard mostri gli stessi dati, e che le email operative non siano disabilitate dalla preferenza promozionale.
6. Verificare upgrade e link di conferma precedenti, oltre al flusso separato di revoca tramite link.

PR #512 integrata, merge `fd20fb97981a868be9e065521eff03948e041b09`; [CI 43923892421](https://github.com/jeckodevelopment/jd_learn/actions/runs/43923892421) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43923892421/artifacts/14255631013), SHA-256 dello ZIP interno `ee1eb8ac0720c826e0ca6ebabc05354bf2a5d71a58838b2ec1ca0a61bb216678`.

Automazione: tests/frontend-preferences.py richiamato da tools/check.py. Il collaudo manuale Joomla/SMTP non è ancora eseguito.

---

## FE-05 — I miei attestati, vista privata autonoma (collaudo Joomla pendente)

1. In Joomla creare una voce menu JD Learn → **I miei attestati** per IT e EN, accessibile solo agli utenti registrati; verificare SEF, breadcrumb e template override.
2. Con un utente autenticato senza attestati verificare lo stato vuoto localizzato; con uno o più attestati verificare titolo, data, seriale, ordine e download.
3. Verificare direttamente `index.php?option=com_jlms&view=mycertificates` come anonimo: nessun titolo, codice, elenco né dato personale deve essere esposto; la risposta deve negare l'accesso.
4. Con due utenti, verificare che ciascuno veda esclusivamente i propri attestati; provare la sostituzione dell'`enrollment_id` nel link PDF e il download anonimo: accesso rifiutato.
5. Verificare che gli attestati revocati non compaiano; testare `ready` e `pending_render`, e lo storico dei corsi archiviati, non pubblicati o cestinati.
6. Verificare privacy `Cache-Control: private, no-store`, `X-Robots-Tag: noindex, nofollow`, meta robots, mobile, tastiera e browser back.
7. Verificare che la dashboard precedente continui a mostrare gli attestati senza duplicazione della logica di rendering e che **I miei corsi** mantenga filtri e paginazione.
8. Verificare installazione pulita e aggiornamento del pacchetto, menu IT/EN e sostituzioni dei template Joomla.

**Evidenza post-merge:** [PR #510](https://github.com/jeckodevelopment/jd_learn/pull/510) integrata `0605c07b6c654e3ff006b9aed79b9bfbc601d676`; [CI](https://github.com/jeckodevelopment/jd_learn/actions/runs/43919671264) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43919671264/artifacts/14252108998) SHA-256 dello ZIP interno `2215a89c7676f421aeaa0bf0bd09857b16ec44f442c5fa2e44f654c2d33f6432`. **Automazione:** `tests/frontend-mycertificates.py` verifica il routing, il servizio proprietario, gli header, i template e le traduzioni. Non è una prova di autorizzazione runtime: **collaudo manuale pendente**.

---

## LCM-01 — attestati storici di corsi ritirati, archiviati e cestinati

1. Emissione su corso regolarmente completato: annotare codice, data, titolo, versione, documento e hash del PDF; verificare download privato da area studente.
2. Passare il corso a stato non pubblicato `0`, archiviato `2`, cestinato `-2`: l'attestato già emesso deve continuare a comparire e a scaricarsi **solo** al titolare. Verificare che aula, lezioni e materiali restino bloccati anche tramite URL diretti.
3. Tentare download con utente diverso e anonimo: rifiuto senza rivelare documenti, studenti, titoli o codici; verificare anche le viste amministrative e relative ACL.
4. Controllare che seriale, snapshot identificativo, data di emissione, file e numero attestati non cambino, né si producano nuovi messaggi di disponibilità per effetto della sola archiviazione.
5. Eseguire upgrade del pacchetto con attestato già emesso e verificare integrità del download; ripetere con lingua IT/EN e backup/restauro, verificando database e storage privato.
6. Provare documento revocato e account bloccato secondo regole di privacy e retention; controllare gestione errori PDF.

**Prove automatiche:** [PR #490](https://github.com/jeckodevelopment/jd_learn/pull/490) integrata (merge `622a4ae67cae10d5dec35688d1d550fa064994a1`), [CI 43858672461](https://github.com/jeckodevelopment/jd_learn/actions/runs/43858672461) 4/4 verde. Il download deve renderizzare correttamente anche un documento `pending_render` con lock SQL parametrizzato. Integrazione Joomla su elenco/documento privato per stati `0/2/-2`, anonimo/utente estraneo e conservazione dopo upgrade. **Accettazione manuale Joomla, prove multi-lingua e restore: pendenti.**

---

## LCM-01 — dashboard storica e modalità consultazione dopo archiviazione

1. Preparare due iscritti, uno con progresso parziale e uno con corso completato, includendo un corso in modalità consultazione. Registrare titolo, data, progresso e attestati prima delle transizioni.
2. Portare il corso in stato **Non pubblicato (0)**, **Archiviato (2)** e **Cestinato (-2)**: la dashboard personale deve conservare gli elementi storici (titolo, stato, completamento, avanzamento già acquisito) e indicare aula non disponibile; verificare che il link di ripresa e gli URL diretti non aprano lezioni.
3. In modalità consultazione, la percentuale deve restare non applicabile anche quando il corso è archiviato/cestinato o completato; lo storico non deve mostrare il valore 0% o 100% erroneamente.
4. Verificare che i PDF attestato già rilasciati siano ancora elencati e scaricabili soltanto dal titolare, senza aprire la didattica. Verificare un altro account senza titolarità, utenti bloccati, lingue IT/EN, e uno storico con più di una pagina.
5. Ripubblicare il corso e verificare che riappaia l'accesso secondo ACL senza modificare la percentuale storica né riaprire iscrizioni per effetto della sola transizione.

**Automazione:** PR #488 integrata (merge `9993414aed64c1126c8894f5b23af1a542dea10a`); [CI 43853275442](https://github.com/jeckodevelopment/jd_learn/actions/runs/43853275442) 4/4. Integrazione Joomla sulla dashboard per 0/2/-2, ripresa negata, titoli, completamenti e modalità consultazione; **collaudo manuale Joomla e verifica PDF pendenti**.

---


## LCM-01 — blocco migrazioni e notifiche su corsi inattivi

1. Creare due versioni didattiche con una iscrizione migrabile: verificare anteprima e migrazione su corso pubblicato, quindi ripetere su corso non pubblicato (`0`), archiviato (`2`) e cestinato (`-2`). L'anteprima deve risultare bloccata e il POST di migrazione rifiutato anche con hash valido; le iscrizioni originali e i progressi rimangono invariati.
2. Abilitare le notifiche facoltative di nuova lezione e aggiornamento significativo e predisporre una coda con destinatario e SMTP di prova. Archiviare il corso prima della generazione e accertare che non vengano creati nuovi avvisi didattici.
3. Accodare un avviso quando il corso è pubblicato, poi archiviarlo **prima del dispatch**: l'outbox deve scartare il messaggio senza inviarlo. Controllare anche non pubblicato e cestinato, entrambe le lingue e gli ACL; ripubblicare e verificare un nuovo evento valido.
4. Verificare che le email informative già inviate restino nell'audit secondo retention, che lo storico personale e gli attestati già emessi siano preservati e che nessuna nuova migrazione avvenga attraverso una richiesta ripetuta o concorrente.

**Stato:** [PR #486](https://github.com/jeckodevelopment/jd_learn/pull/486) integrata (merge `f17f16b05f3b0d7a8d8cdb6dc46aa4aa333461e6`), [CI 43850378497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43850378497) 4/4 superata; collaudo manuale Joomla/SMTP non ancora eseguito. LCM-01 resta parziale fino al controllo completo di tutti gli endpoint e dei casi di concorrenza.
## LCM-01 — primo gate server-side per corsi non pubblicati, archiviati e cestinati

1. Su Joomla di prova predisporre un corso pubblicato con una lezione, un iscritto attivo e almeno un completamento; annotare progressi, iscrizione e PDF già emessi (se disponibili).
2. Da backend ritirare la pubblicazione (`state=0`) e verificare che catalogo, URL diretto dell'aula, completamento e nuova iscrizione non consentano accesso o scrittura. Ripetere con corso archiviato (`state=2`) e cestinato (`state=-2`) quando le azioni corrispondenti sono disponibili.
3. Verificare che gli endpoint di aula, progresso, video e materiali protetti non aggirino il blocco; provare utenti anonimi, iscritti, docenti e utenti con ACL ridotte.
4. Verificare da database e dalla dashboard personale che iscrizione, progressi, data completamento e attestati restino invariati e consultabili secondo le regole di storico. Il blocco della fruizione non equivale alla cancellazione dello storico.
5. Ripubblicare il corso su ambiente di prova e verificare che una finestra promozionale scaduta (`publish_down`) da sola non interrompa l'aula già autorizzata; controllare separatamente lo stato di apertura delle iscrizioni.
6. Ripetere con accessi diretti, concorrenza di richieste e aggiornamento del pacchetto su sito con dati preesistenti, osservando ogni eventuale via laterale ancora non coperta.

**Implementazione:** PR #484 integrata (merge `fef7a87a399e9bfafdbeca0c178f0e8629cf4ebf`), CI [43846067260](https://github.com/jeckodevelopment/jd_learn/actions/runs/43846067260) 4/4 superata. Test aggiornati per stati 0/2/-2, progressi, video player e transizione archivio. **Collaudo Joomla/accettazione: pendente.** Restano da verificare i flussi laterali LCM-01, le toolbar LCM-02, percorsi LCM-03 e docenti LCM-04.

---

## Issue #472 — voce menu frontend Percorsi formativi

1. Su Joomla 6 con JD Learn aggiornato, aprire **Menu → Nuova voce di menu → Tipo di voce di menu → JD Learn**: deve essere presente **Percorsi formativi** (in EN **Learning pathways**) con descrizione corretta.
2. Creare e pubblicare la voce, aprirla sul sito: deve visualizzare la vista `pathways` esistente con i soli percorsi disponibili, paginazione e link ai dettagli. Verificare URL SEF e non SEF.
3. Provare elenco vuoto e visibilità di percorsi pubblicati, non pubblicati e privati con utente anonimo/autenticato e ACL diversi: nessuna esposizione di contenuti riservati.
4. Verificare creazione e modifica della voce menu in italiano e inglese, selettore nativo Joomla, navigazione desktop/mobile e compatibilità con gli altri tipi di menu JD Learn.

**Esito:** attende verifica su sito Joomla reale; il test strutturale controlla XML, vista esistente e traduzioni ma non sostituisce il collaudo.

---

## Attestati — menu di ordinamento senza duplicazioni

1. Aprire **JD Learn → Attestati** con Joomla 6/Atum, anche in assenza di risultati: il menu di ordinamento deve offrire esattamente sei opzioni, una per campo (Emesso il, Studente, Corso, Codice attestato, Stato e ID).
2. Verificare che il filtro Stato mostri solo Tutti/Disponibile/In attesa/Revocato, senza duplicazioni.
3. Con almeno due attestati, verificare selezione di ogni campo e inversione crescente/decrescente tramite intestazione cliccabile della tabella; controllare ricerca, paginazione e permanenza dei filtri.
4. Ripetere in italiano e inglese e con un ruolo privo di autorizzazioni per verificare la protezione della vista.

**Esito:** collaudo Joomla da eseguire; nessuna modifica a query o dati attestati.

---

## IMP-05 — capability contract provider video

1. Installare il pacchetto candidato su Joomla di prova e verificare che i quattro plugin video risultino disponibili e che soltanto quelli intenzionalmente usati siano abilitati.
2. Preparare una lezione video tracciabile per ciascun provider (YouTube, Vimeo, Cloudflare Stream e HTML5) e provarla come studente autorizzato dopo il consenso richiesto.
3. Verificare posizione, durata, seek e ripresa nei quattro player; nessuna variazione della copertura o del completamento deve essere attribuita al solo seek.
4. Con video/account che lo supportano, verificare il selettore velocità su YouTube, Vimeo e HTML5; con un contenuto/account senza velocità disponibile il selettore non deve apparire. Cloudflare Stream non deve mostrare il controllo velocità.
5. Con video dotati di caption, verificare le tracce e la selezione sui player Vimeo e HTML5; su YouTube e Cloudflare Stream verificare la preferenza lingua all’avvio e il comportamento normale se la lingua non è disponibile. Nessuna preferenza va salvata sul profilo o inviata al server.
6. Verificare che la trascrizione locale sia disponibile separatamente per tutti i provider e che l’assenza di caption non generi una traccia artificiale.
7. Con Cloudflare Stream, verificare scadenza/rinnovo del token e che il download resti disabilitato; con provider disabilitato o non configurato, la riproduzione deve rimanere indisponibile.
8. Provare una sorgente configurata per un provider con l’adapter sbagliato: non deve risultare supportata né produrre una sessione valida.

**Esito sito Joomla:** Da eseguire su account, SDK e browser reali. I test automatici della capability matrix verificano il contratto dichiarato; non provano la disponibilità delle singole tracce o velocità nei contenuti reali.

---

## Issue #446 e #448 — toolbar comunicazioni e disponibilità corso

1. Aggiornare JD Learn su Joomla 6/Atum e aprire **Comunica agli iscritti** dal roster. Verificare che **Iscritti al corso** sia il primo comando della toolbar, con icona indietro; in fondo al corpo non deve apparire un link duplicato.
2. Dal roster aprire **Disponibilità del corso**. Verificare che **Iscritti al corso** sia il primo comando della toolbar e che i quattro comandi di azione siano nella toolbar, senza duplicati nel corpo pagina.
3. Aprire ciascuna operazione: Chiudi nuove ammissioni, Apri nuove ammissioni, Sospendi erogazione didattica, Riprendi erogazione didattica. Verificare corso e operazione selezionata, impatto, conferma, CSRF e risultato dopo il submit; il ritorno deve mantenere il corso.
4. Provare entrambi gli stati delle ammissioni e dell’erogazione, inclusi comandi già applicati e combinazioni tra loro. Verificare che audit e messaggi di conferma restino corretti.
5. Ripetere in IT/EN, desktop/mobile e con ruoli autorizzati e non autorizzati. L’accesso diretto agli URL non deve superare le ACL.

**Esito utente, 15 novembre 2026:** confermato che il ritorno al roster e le azioni toolbar delle issue #446/#448 funzionano. Collaudata la funzionalità. La richiesta cosmetica successiva accorcia le etichette in una PR distinta.

**Esito PR #452, 15 novembre 2026:** l'utente conferma che i pulsanti con etichette ridotte si vedono correttamente. La conferma riguarda la sola resa riferita; gli altri scenari della checklist restano aperti.

## Issue #455 — ritorno tra Corsi, iscritti e iscrizione manuale

1. Aggiornare JD Learn su Joomla 6/Atum e aprire il roster di un corso: il primo pulsante toolbar deve tornare a **Corsi** con icona indietro. **Iscrizione manuale** deve mostrare l'icona plus.
2. Aprire **Iscrizione manuale**: il primo pulsante toolbar deve riportare a **Iscritti** dello stesso corso, con icona indietro.
3. Verificare che nel corpo di entrambe le viste non compaiano i vecchi link di ritorno duplicati.
4. Verificare il corso/ID mantenuto nei percorsi, l'accesso da ruoli autorizzati e il diniego ACL agli altri; ripetere in italiano/inglese e su desktop/mobile.
5. Verificare anteprima, applicazione iscrizione manuale, ricevuta e filtri/paginazione del roster non modificati. Provare apertura diretta e uso della navigazione nativa Atum.

**Esito utente, 15 novembre 2026:** collaudo completo della issue #455 e della PR #457 riuscito. Confermati i ritorni dalla toolbar, l'icona Iscrizione manuale, la rimozione dei link duplicati, le destinazioni e il corso mantenuto, ACL e accesso diretto, IT/EN, desktop/mobile e il normale flusso di iscrizione manuale. La conferma vale per gli scenari della issue; non chiude l'intero requisito UI-02 o ISC-01.

## Issue #434 — azioni della vista iscritti nella toolbar

1. Aggiornare JD Learn su Joomla 6/Atum e aprire il roster di un corso con un account autorizzato. Verificare che **Iscrizione manuale**, **Comunica agli iscritti** e **Disponibilità del corso** compaiano una sola volta nella toolbar Joomla e non nel corpo della pagina.
2. Aprire ciascuna azione e verificare che mantenga il corso corretto. In particolare, **Disponibilità del corso** deve aprire l'operazione prevista di chiusura ammissioni. Tornare al roster con la navigazione nativa.
3. Verificare filtri, richieste pending, tabella, paginazione e azioni per singola iscrizione. Ripetere in italiano e inglese, su desktop e mobile.
4. Con un ruolo privo dei permessi richiesti, verificare che l'accesso alle viste di destinazione continui a essere negato anche inserendo direttamente l'URL.

La modifica riordina la navigazione del roster. Non cambia le operazioni né i relativi controlli ACL.

**Esito utente, 15 novembre 2026:** confermata la corretta visualizzazione dei pulsanti nella toolbar. La conferma copre la presenza visiva dei pulsanti; gli altri scenari sopra restano da verificare separatamente.

## MAIL-H — rendering template personalizzati, escaping (PR #410)

1. Installare/aggiornare build `0.57.7-dev` su sito di prova Joomla 6.2. In **Sistema → Modelli email** personalizzare una variante HTML di `com_jlms.enrollment.manager_request`: usare sia `{COURSE_TITLE}` sia `{COURSE_TITLE_HTML}` in paragrafi distinti; verificare che titoli contenenti `&`, `<`, `>` e virgolette vengano mostrati una volta sola senza doppio escaping e senza markup iniettato.
2. Verificare con `com_jlms.course.message` che `{MESSAGE_BODY_TEXT}` venga escapato in HTML e che `{MESSAGE_BODY_HTML}`, già sanitizzato dal producer, conservi il markup innocuo consentito; ripetere nel plain text e verificare che non appaiano `&amp;lt;` o tag HTML indesiderati.
3. Verificare modello `com_jlms.course.published` con anteprima immagine e lezioni pubbliche: `{COURSE_IMAGE_HTML}`, `{COURSE_INTRO_HTML}`, `{COURSE_LESSONS_HTML}`, `{UNSUBSCRIBE_URL_HTML}` non devono essere codificati due volte. Il modello personalizzato preesistente deve restare invariato dopo upgrade.
4. Ripetere in lingua amministrativa IT ed EN, HTML e plain text, controllando retry/outbox e assenza di regressioni su ACL e avvisi ai responsabili. Su template con placeholder inesistenti, il dispatch deve continuare a fallire senza invio o retry infinito.

**Test automatici non sostituiscono l'accettazione SMTP/Joomla su sito di prova.**

## PR #407 — navigazione amministrativa essenziale e issue #394 (accettazione Joomla pendente)

1. Aggiornare da 0.57.5-dev a 0.57.6-dev su Joomla 6 / Atum. Verificare la sidebar a **cinque** voci nell'ordine: Dashboard, Corsi, Percorsi formativi, Docenti, Banca domande. **Coda email** assente dalla sidebar, presente nella dashboard; nessuna voce obsoleta dopo l'aggiornamento.
2. Aprire Corsi → editor → programma → lezione; Percorsi → editor; Docenti → editor; Banca domande → editor. Verificare che la voce operativa corrispondente resti evidenziata e che JD Learn rimanga espanso.
3. Aprire dalla dashboard **Coda email**, **Variabili modelli email**, **Dipendenze categorie**, **Attestati** e un dettaglio secondario. Verificare che il nodo JD Learn rimanga espanso ma nessuna voce operativa sia evidenziata. Gli URL diretti devono comportarsi ugualmente. Nessun errore HTTP 500.
4. Aprire dalla dashboard **Categorie corsi**, **Categorie percorsi**, **Campi aggiuntivi**, **Plugin video** e **Template email**. Verificare che questi componenti nativi Joomla non ricevano un focus fittizio su una voce JD Learn e mantengano i propri ACL e filtri.
5. Issue **#394**: verificare visivamente la presenza dell'icona cartella su **Dipendenze categorie** in italiano/inglese, su desktop/mobile e tema chiaro/scuro. Non basta la verifica della classe HTML: verificare la resa effettiva in Atum.
6. Provare Super User e ruoli ACL limitati per ogni link, URL diretto e relativo accesso negato. Verificare installazione pulita e upgrade preservando dati, registrazione dei menu e pacchetto installabile.

La CI automatica non costituisce accettazione manuale. Perimetro UX, nessun aumento automatico della stima globale di implementazione.

## MAIL-H — issue #363: anteprima email nella toolbar editor corso (PR candidata, accettazione pendente)

1. Aggiornare Joomla con build `0.57.5-dev`. Aprire **JD Learn → Corsi** e un corso **pubblicato** con un utente autorizzato alla modifica. La toolbar amministrativa deve mostrare **Anteprima email di annuncio**, una sola volta; il vecchio pulsante nel corpo del form non deve esserci.
2. Fare clic sul pulsante: si apre la pagina di anteprima già esistente, con titolo corso e contenuto previsto. L'apertura è navigazione GET, senza salvataggio, email in coda o invio SMTP. Verificare il pulsante di ritorno e il comportamento di checkout.
3. Aprire una bozza/non pubblicato: il pulsante **non** compare. Ripetere su corso in checkout da altro utente: nessun pulsante. Un utente senza permesso di modifica non può accedere all'editor né eludere i permessi dell'anteprima passando direttamente l'URL.
4. Verificare che **Salva**, **Salva e chiudi**, **Salva e programma**, **Versioni** e **Chiudi** funzionino normalmente e che il rendering dell'editor rimanga corretto su schermi desktop e smartphone. Ripetere in amministrazione IT ed EN; verificare assenza di doppio pulsante e nessuna email al semplice caricamento.

**CI automatica e collaudo manuale Joomla sono distinti**. Il fix è una regressione UX, non aumenta la copertura funzionale stimata verso 1.0.0.

## MAIL-G — nuovi avvisi ai responsabili delle richieste (PR #401, QA pendente)

1. Aggiornare Joomla 6.2 alla build 0.57.4-dev. Verificare **Sistema → Modelli email**: `com_jlms.enrollment.manager_request`, titolo/descrizione IT/EN, token e conservazione delle modifiche a template esistenti.
2. Configurare un corso in modalità iscrizione con approvazione, con responsabile amministrativo che possiede `core.manage` su `com_jlms` e `jlms.enrollments.manage` sul corso, e un docente assegnato dotato del permesso sullo stesso corso. Con la preferenza globale OFF e override Eredita, creare richiesta pending: nessun nuovo messaggio. Con override Abilita, creare una nuova richiesta e lanciare lo Scheduler: un solo messaggio per ciascun responsabile autorizzato.
3. Aggiungere docente assegnato senza permesso di gestione, docente di un altro corso, utente non autorizzato e studente richiedente: **nessun avviso** a loro. Con globale ON e override Disabilita: nessun nuovo avviso.
4. Ripetere Scheduler e tentare richieste duplicate: deduplica per richiesta+manager, non oltre due giorni dalla creazione. Approvare/rifiutare prima del dispatch: `skipped`, destinatario/PII rimossi; nessun avviso per richieste già decise prima della rilevazione.
5. Accodare e poi revocare il permesso, rimuovere l'assegnazione docente, bloccare il responsabile, cambiarne email o disabilitare la preferenza: skip senza spedizione SMTP. Verificare che non vengano esposti nominativo, email o motivazione del richiedente nel messaggio.
6. Eseguire SMTP e retry su installazione Joomla di prova; aprire il link al registro iscrizioni da account con e senza permesso per verificare la protezione delle viste. Provare lingua IT/EN, caratteri speciali nel titolo, upgrade, più amministratori e throughput di molte richieste.

**La CI non sostituisce l'accettazione SMTP/manuale.** Registrare le evidenze distinte per scenario.

## MAIL-G — aggiornamenti didattici significativi (PR #399, accettazione pendente)

1. Installare o aggiornare la build 0.57.3-dev su Joomla 6.2; verificare in **Sistema → Modelli email** il modello `com_jlms.course.updated` e relativi titoli/descrizioni IT/EN. Assicurarsi che i modelli già personalizzati non vengano sovrascritti.
2. Creare due versioni pubblicate con differenze materiali in lezioni, quiz, contenuti, materiali o regole. Pubblicare la nuova versione senza migrare iscritti: Scheduler non deve produrre email di aggiornamento. Ripetere solo salvataggi di bozza: nessun invio.
3. Migrare esplicitamente un'iscrizione attiva alla nuova versione con modifica sostanziale. Con globale OFF e override Eredita: nessun invio. Con override Abilita, il task deve accodare e inviare un solo messaggio; ripetere Scheduler e conferma idempotente: zero duplicati. Con globale ON e override Disabilita: niente email.
4. Ripetere con differenza **solo editoriale** (presentazione, titolo promozionale): nessuna notifica; poi provare cambi di programma/quiz e verificare che la modifica sia riconosciuta. Controllare che studenti non migrati non ricevano comunicazioni.
5. Dopo l'accodamento, sospendere o completare l'iscrizione, bloccare account, disabilitare preferenza, revocare ACL/tassonomia o chiudere edizione: `skipped` e nessun SMTP; dati personali rimossi dalla riga. Verificare versioni errate, utenti con lingua IT/EN e assenza di URL a risorse private.
6. Provare il task schedulato dopo migrazione recente, SMTP fallito/retry e finestra 48h; controllare sicurezza dei log, escaping HTML dei titoli e preservazione dei dati con upgrade della build precedente.

**Accettazione manuale non eseguita.** La CI non costituisce evidenza SMTP su sito di prova.

## MAIL-G — disponibilità nuove lezioni (PR #397, accettazione pendente)

1. Installare 0.57.2-dev su Joomla 6.2 e aprire **Sistema → Modelli email** in IT/EN. Verificare `com_jlms.lesson.available`, titolo, descrizione, tag canonici, subject/HTML/testo e conservazione dei template personalizzati in upgrade.
2. Preparare una versione pubblicata con un'iscrizione attiva e una lezione con `publish_up` futuro. Mantenere la preferenza globale OFF e per corso eredita: dopo la disponibilità, Scheduler non deve accodare nulla. Abilitare un secondo caso per corso: al rilascio deve essere presente una sola notifica per iscrizione e lezione. Verificare override corso disabilitato con globale ON.
3. Ripetere per `release_after_days`, anche con `publish_up` più tardo e studente che non ha ancora avviato il corso. La data effettiva più tarda deve determinare la disponibilità. Una lezione già disponibile prima dell'iscrizione, una bozza e un normale salvataggio non producono email.
4. Iscrivere uno studente a una versione precedente mentre ne viene pubblicata una nuova con lezioni differenti: nessuna notifica relativa alle lezioni di versione non assegnata. Verificare un'iscrizione completata, sospesa, edizione chiusa, corso con erogazione sospesa, utente bloccato e finestre scadute.
5. Dopo l'accodamento revocare un permesso, bloccare l'utente o l'accesso, sospendere l'iscrizione o disattivare la preferenza: prima di SMTP il record deve passare a `skipped` ed essere privato dei dati personali; niente comunicazioni improprie.
6. Eseguire più volte Scheduler e simulare SMTP temporaneamente assente: invio singolo, retry limitato, niente doppio invio; verificare che non vengano inviate email automaticamente al salvataggio o per eventi di oltre 48 ore prima del nuovo controllo.
7. Confermare resa HTML/text IT/EN, escaping di titoli con caratteri speciali e collegamento sicuro alla sola dashboard autenticata. Registrare esiti separati dalla CI, inclusa performance su molte iscrizioni.

**Stato:** da eseguire manualmente sul sito di prova Joomla/SMTP; la CI non sostituisce l'accettazione.

## UX navigazione amministrativa — issue #359 (collaudo richiesto)

1. Installare/aggiornare JD Learn su Joomla 5/6 in italiano e inglese. Verificare che la sidebar presenti **solo** Dashboard, Corsi, Percorsi formativi, Docenti, Coda email, Banca domande, esattamente in questo ordine e senza voci residue dopo l'aggiornamento.
2. Aprire la dashboard. Verificare le sezioni Didattica, Persone e valutazione, Comunicazioni, Configurazione & Strumenti. Verificare il collegamento Dipendenze categorie in quest'ultima, la destinazione della vista di ricollocazione, e il pannello Stato provider video.
3. In Comunicazioni verificare la voce **Variabili modelli email** (#359), che deve continuare ad aprire la guida esistente; verificare anche Template email nativi Joomla e Coda email.
4. Con amministratore autorizzato e con ruoli ACL limitati confrontare sidebar, dashboard e accesso diretto agli URL: nessun collegamento deve eludere le ACL.
5. Aprire in successione elenco corsi, modifica corso, programma, modifica lezione; poi percorsi e modifica percorso, docenti e modifica docente, banca domande e modifica domanda, coda email e vista interna. Verificare la selezione della voce attiva fornita dal menu nativo Joomla nei percorsi profondi; l'accettazione manuale è ancora pendente.
6. Da una vista secondaria accessibile solo dalla dashboard (Dipendenze categorie, Plugin video, Campi aggiuntivi, Template email) verificare l'assenza di un'evidenziazione fuorviante e la disponibilità di percorsi di ritorno tramite strumenti nativi Joomla. Verificare il percorso di ritorno con la toolbar e la navigazione nativa Atum. Non utilizzare Pathway nel client administrator: provoca HTTP 500. L'accettazione manuale è ancora pendente.
7. Verificare eventuali menu di amministrazione personalizzati, caching e aggiornamento da una versione precedente: non deve essere necessario reinstallare il componente.

La mappatura nativa della voce attiva è implementata; Pathway amministrativo non è supportato. Le verifiche visive 5 e 6 restano **collaudi manuali aperti**. Non dichiarare accettazione su Atum senza riscontro reale. L'esito dei test automatici non costituisce accettazione manuale.

## Issue #391 — etichetta localizzata Salva & Chiudi

1. Installare il pacchetto `0.56.29-dev` della PR #391 integrata su Joomla 6.2.
2. Con lingua amministrativa inglese, aprire **JD Learn → Corsi → Nuovo** e verificare che la toolbar mostri **Save & Close**, senza chiavi tecniche.
3. Cambiare la lingua amministrativa in italiano, riaprire l’editor e verificare **Salva & Chiudi**.
4. Ripetere su un corso esistente e verificare che **Salva**, **Salva & Chiudi** e **Salva e apri programma** mantengano i rispettivi comportamenti.
5. Verificare che non appaiano le stringhe letterali `JTOOLBAR_SAVE_AND_CLOSE` o `COM_JLMS_COURSE_SAVE_AND_CLOSE`.

CI [43649969902](https://github.com/jeckodevelopment/jd_learn/actions/runs/43649969902) verde 4/4; la prova HTTP autenticata controlla form nuovo/esistente e traduzioni IT/EN. L’accettazione visuale sul sito di prova resta da registrare.

## Issue #384 — toolbar salvataggio corso

1. In Joomla 6.2 accedere come amministratore con permesso di creazione e modifica corsi.
2. Aprire **JD Learn → Corsi → Nuovo**. Verificare che **Salva** sia autonomo e che **Salva & Chiudi** sia il pulsante principale split.
3. Aprire il menu split: verificare la voce **Salva e apri programma**.
4. Verificare i tre esiti: **Salva** salva e resta nell’editor; **Salva & Chiudi** salva e torna all’elenco; **Salva e apri programma** salva e apre il programma del corso.
5. Ripetere le verifiche su un corso esistente e verificare che la validazione e la protezione read-only/check-out restino attive.

CI [43648461944](https://github.com/jeckodevelopment/jd_learn/actions/runs/43648461944) **4/4 verde**. La prova HTTP autenticata verifica le tre etichette e i task effettivamente renderizzati su form nuovo/esistente. Resta da registrare l’accettazione manuale sul sito Joomla di prova.

## Issue #364 — anteprima email annuncio corso

1. Accedere al backend Joomla come amministratore con autorizzazione a modificare il corso.
2. Aprire **JD Learn → Corsi**, modificare un corso esistente e individuare l’azione per l’anteprima dell’annuncio email.
3. Aprire l’anteprima. La pagina deve caricarsi senza 404 e mostrare il titolo del corso e lo stato dell’annuncio (anteprima disponibile, corso non idoneo oppure nessun destinatario).
4. Verificare che l’anteprima non invii email: l’accodamento deve avvenire solo dopo la conferma esplicita, con token Joomla valido.
5. Con un utente privo di permesso di modifica sul corso, tentare di aprire direttamente lo stesso URL: Joomla deve negare l’accesso senza mostrare i contenuti del corso.

CI copre il routing HTTP autenticato usando un corso fixture e verifica il contenuto della vista. L’invio reale SMTP e l’accodamento restano fuori da questo collaudo di sola anteprima.

## Issue #365 — azioni di salvataggio nella toolbar corso

1. In Joomla 6.2, accedere con un utente autorizzato a creare corsi e aprire **JD Learn → Corsi → Nuovo**. Verificare che **Salva** resti un'azione autonoma e che **Salva & Chiudi** sia il pulsante principale del gruppo a discesa.
2. Aprire il menu di **Salva & Chiudi** e verificare che **Salva e apri programma** compaia come opzione aggiuntiva. Selezionarla sul nuovo corso: il corso deve essere salvato una volta e si deve aprire il suo programma didattico.
3. Modificare un corso esistente e ripetere le tre azioni: **Salva** deve restare nella pagina, **Salva & Chiudi** deve tornare all'elenco, l'opzione del menu deve salvare e aprire il programma.
4. Verificare che la stessa opzione inline nella scheda **Programma** continui a funzionare, che la validazione del form sia rispettata e che la toolbar resti nascosta quando il corso è in sola lettura/check-out di un altro utente.

CI controlla il wiring Joomla della toolbar e il task associato; il flusso controller/programme è coperto dalla suite esistente. Accettazione visuale e di navigazione sul sito di prova da registrare.

## Issue #368 — traduzione voce sidebar ricollocazione categorie

1. Aprire il backend Joomla 6.2 in italiano e verificare che la voce **Gestione dipendenze categorie** compaia nel menu laterale JD Learn invece della chiave `COM_JLMS_CATEGORY_REASSIGN_TITLE`.
2. Passare all’inglese e verificare che la stessa voce mostri **Category dependency cleanup**.
3. Aprire la voce in entrambe le lingue e verificare che la pagina si carichi senza errori e mantenga le autorizzazioni esistenti.

CI verifica entrambe le traduzioni in `com_jlms.sys.ini` e che Joomla risolva la chiave in inglese nel menu amministrativo.

## Issue #377 — vista Docenti: stato e conteggio corsi

1. In Joomla 6.2 di prova, aprire **JD Learn → Docenti**. Verificare che la colonna Stato mostri l'icona nativa Joomla per pubblicato/non pubblicato, senza testo e senza consentire toggle inline.
2. Verificare il conteggio Corsi: con corsi assegnati il pulsante usa il colore di conteggio positivo delle categorie; con zero corsi usa il colore secondario. Aprire il conteggio e verificare che la lista Corsi sia filtrata per docente.
3. Controllare contrasto e leggibilità in tema chiaro/scuro, navigazione da tastiera e viewport desktop/mobile.

CI controlla uso di `jgrid.published`, assenza del toggle inline e classi di stato del conteggio. Accettazione visiva manuale da registrare.

## IMP-09 / issue #375 — toolbar Guida alle variabili email

1. Accedere con un amministratore autorizzato a `com_jlms`, `core.manage` e `core.edit` su `com_mails`. Aprire **JD Learn → Variabili modelli email** e verificare che il pulsante **Template Email** compaia nella toolbar e apra i modelli filtrati su `com_jlms`.
2. Verificare che il pulsante non sia duplicato nel corpo della guida.
3. Accedere con un utente autorizzato a JD Learn ma senza `core.edit` su `com_mails`: il pulsante non deve comparire. Ripetere senza `core.manage` su `com_mails`.
4. Verificare che catalogo, contenuto e lingua IT/EN della guida restino invariati.

CI automatica copre la presenza del link in toolbar, il filtro `com_jlms`, il controllo ACL e la rimozione del collegamento dal corpo pagina. Accettazione manuale Joomla da registrare.

## CORE-10 — Campi aggiuntivi su Joomla 6.2.0 (PR #378)

1. Aggiornare JD Learn su un sito di prova Joomla 6.2.0. Creare un campo testuale distinto per ciascuno dei tre contesti.
2. Da **JD Learn → Campi aggiuntivi**, cambiare il selettore avanti e indietro. Controllare URL, etichetta selezionata e righe: Corsi = `com_jlms.course`, Docenti = `com_jlms.teacher`, Lezioni = `com_jlms.lesson`.
3. Aprire il programma di un corso in bozza e creare o modificare una lezione. Il form deve mostrare il campo nativo lezione senza errore HTTP 500.
4. Verificare che un editor delegato privo del permesso di gestione Campi non veda il collegamento nel menu. Da una sessione anonima verificare il rinvio al login per tutti e tre gli URL diretti.

La suite HTTP copre selettore, isolamento degli elenchi, form lezione e accesso dopo l’upgrade. Collaudo manuale sul sito di prova da registrare.

## IMP-09 / CORE-18 — template email Joomla

**Comportamenti da testare in Joomla**

1. Installare o aggiornare il pacchetto e aprire **Sistema → Modelli email**. Filtrare per `com_jlms` e verificare i modelli richiesta iscrizione, iscrizione confermata e completamento corso, con testo, HTML e tag dichiarati.
2. Aprire un modello, modificarne oggetto e corpo e salvare. Creare inoltre una traduzione per la lingua secondaria configurata nel sito.
3. Aggiornare nuovamente il pacchetto: oggetto, corpo e traduzione personalizzati devono rimanere invariati; non devono comparire modelli duplicati.
4. Verificare che l'impostazione del mailer/SMTP resti quella globale di Joomla. Questo incremento registra i modelli; la spedizione automatica e il retry persistente sono residui CORE-18 e non vanno considerati collaudati da queste prove.

**Verifiche aggiunte dalla PR #291 (0.56.1-dev)**

5. Abilitare il plugin Attività - JD Learn e creare un'attività pianificata Joomla `JD Learn: invia email in coda` con frequenza breve per il collaudo. Configurare il mailer globale del sito su una casella di prova.
6. Con un utente di prova, iscriversi a un corso pubblicato in modalità automatica. Eseguire l'attività e verificare la conferma d'iscrizione con corso, nome, lingua e link aula corretti. Ripetere l'attività: la casella deve ricevere un solo messaggio.
7. Completare tutte le attività obbligatorie del corso, con una lezione manuale e una video in due prove separate. Verificare un solo messaggio di completamento per ciascuna iscrizione e nessun invio su salvataggi/ripetizioni dello stesso evento.
8. Simulare un errore SMTP temporaneo prima dell'invio, eseguire l'attività e verificare che l'elemento venga ritentato dopo l'attesa progressiva e raggiunga `sent` al ripristino. Verificare che il log dell'attività riporti l'esito senza contenuti o destinatari sensibili.
9. Verificare nel database che i messaggi inviati abbiano stato `sent` e i dati personali siano rimossi; i messaggi in stato `sending` dopo un'interruzione non devono essere reinviati automaticamente.

PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291) integrata nel commit `a6488b3b646d607dbc20317168f1c69dae055527`. CI standard 4/4 nel [run 43300371393](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393), inclusi controlli automatici su entrambi gli stack DB e Chromium. Pacchetto [JD-Learn-0.56.1-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393/artifacts/13981827024), SHA-256 ZIP `beacb58745732bf803d61c6c10c72e483ad6eba2ee2a9e4337eb80d326b90154` (scade il 26 novembre 2026). La CI copre schema, integrazioni e suite automatizzata, ma non prova SMTP end-to-end né sostituisce l'accettazione manuale Joomla. Per i test manuali descritti sopra registrare qui esito, Joomla/PHP, mailer e configurazione usata; non marcare il requisito superato senza tali evidenze.

## IMP-09-MAIL-A — preferenze globali e override corso (PR #295, 0.56.2-dev)

**Comportamenti da testare in Joomla**

1. Aprire le opzioni di JD Learn e verificare le impostazioni per conferma iscrizione automatica e completamento. Impostare entrambe su abilitate; su un corso lasciare entrambe su **Usa impostazione del sito**. Salvare e riaprire: le scelte devono persistere.
2. Con un account di prova, attivare una nuova iscrizione automatica e completare un altro corso. Eseguire il task email: entrambe le notifiche devono essere accodate e consegnate secondo le prove della sezione precedente.
3. Disabilitare a livello globale la conferma di iscrizione. Per un corso lasciare eredita, per un secondo corso impostare esplicitamente abilita. Creare nuove iscrizioni automatiche: il primo corso non deve notificare; il secondo deve notificare.
4. Impostare l'override disabilita sul completamento di un corso, lasciando l'opzione globale abilitata; completare il corso e verificare che non si accodi la mail. Per un altro corso con eredita, il completamento deve continuare a produrre la notifica.
5. Riportare un override da abilita/disabilita a **Usa impostazione del sito** e verificare la nuova eredità. Confermare che modificare queste opzioni non alteri iscrizioni, completamenti, progressi o personalizzazioni dei modelli Joomla.

Le prove automatizzate pure coprono ereditarietà, override, validazione e conservazione dei parametri estranei. L'invio SMTP end-to-end e il collaudo manuale Joomla restano da registrare.

PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295) integrata nel commit `8152e337455b1ec7b9d307dc9248c8070b964709`; CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698). Pacchetto [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`, digest artefatto `4e541658e5cf45c2b1238c41bb1501a768ec850cbd3b2958c37d3e735959450e` (scade il 26 novembre 2026). Acceptance manuale Joomla/SMTP ancora pendente.

## IMP-09-MAIL-B — email su sospensione e riattivazione (0.56.3-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare 0.56.3-dev e aprire **Sistema → Modelli email**. Verificare i template `com_jlms.enrollment.suspended` e `com_jlms.enrollment.reactivated`, i tag dichiarati e la conservazione di personalizzazioni dopo un secondo upgrade.
2. Configurare il mailer globale Joomla su casella di prova; impostare globalmente entrambe le notifiche. Sospendere l'iscrizione attiva di uno studente: l'attività pianificata deve inviare un solo messaggio con nome e corso corretti; ripetere il task senza duplicati.
3. Riattivare la stessa iscrizione: inviare il solo template di riattivazione. Verificare che progressi, stato di completamento e tentativi quiz conservati restino invariati.
4. Disabilitare globalmente la sospensione; per un corso lasciare **Usa impostazione del sito** e per un altro impostare **Abilita**. Sospendere due iscrizioni e verificare che la prima non accodi messaggi e la seconda sì. Verificare anche l'override **Disabilita** con default globale attivo e la preferenza di riattivazione separata.
5. Configurare un errore nel database/outbox durante l'applicazione: la transazione non deve lasciare uno stato aggiornato senza il corrispondente evento; ripetere una richiesta idempotente e verificare un solo record di coda.

Il test automatico copre i valori consentiti e la risoluzione delle preferenze. La CI di integrazione verifica il workflow e il registro template; invio SMTP/Joomla manuale ancora da eseguire.

## IMP-04 / STU-03 — feedback salvataggio progresso video (PR #287, 0.55.0-dev)

PR [#287](https://github.com/jeckodevelopment/jd_learn/pull/287) integrata nel commit `2af8994271d95f3c41b3e5f813ba2222794265c5`; CI standard 4/4 nel [run 43289138110](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110). Pacchetto installabile [JD-Learn-0.55.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110/artifacts/13975579105); SHA-256 package `fb30f231780fc290aeecb64ff3ab7b79f3bb2c00ea19085ea67c0ff239794282`.

**Comportamenti da testare in Joomla**

1. Su un corso in modalità Apprendimento con lezione video tracciabile, accedi come studente e avvia il video. Durante un invio periodico o dopo pausa/seek, verifica che l’area di stato mostri “Salvataggio del progresso…” e poi “Progresso salvato” dopo la risposta positiva del server.
2. Verifica che un completamento ottenuto sulla soglia prevista mostri il messaggio di completamento, senza essere sostituito dallo stato salvato.
3. Simula una perdita di rete durante il salvataggio: il player deve mostrare lo stato offline e riprendere il salvataggio idempotente al successivo invio; dopo il recupero deve mostrare lo stato salvato. Un errore 4xx definitivo deve continuare a mostrare il messaggio di accesso/conflitto e fermare la sessione secondo il comportamento esistente.
4. Ripeti in inglese e verifica le etichette localizzate. Nella preview editoriale non deve apparire uno stato di salvataggio né essere creata telemetria.

Il test automatico `tests/video-player.mjs` e la CI standard sono superati. Il collaudo Joomla manuale resta da eseguire.

## IMP-04 / AUT-03 — avviso durata video in sola consultazione (PR #284, 0.54.0-dev)

PR [#284](https://github.com/jeckodevelopment/jd_learn/pull/284) integrata nel commit `04704621f64a7ee712743f98037dfe31d7a4606f`; CI standard 4/4 nel [run 43256423443](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443). Pacchetto installabile [JD-Learn-0.54.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443/artifacts/13961114324), SHA-256 ZIP `bb30f239755b24b7c439d03ba5a70b7e11294360d648ce2a0a291cdbb0c46f12`; wrapper `ba7f9c7568500bfd82da3d9ff054ea907272942c9dfc9ff9f5203bfc8331494d`. Accettazione manuale da eseguire sul sito Joomla di prova.

1. Crea una bozza in modalità **Sola consultazione** con una lezione video la cui durata non è disponibile o confermata. Apri il riepilogo di pubblicazione: deve apparire l’avviso sulla durata e la pubblicazione non deve essere bloccata solo per questo motivo.
2. Imposta/conferma una durata valida e riapri il riepilogo: l’avviso deve scomparire.
3. Passa alla modalità **Apprendimento** e rimuovi la durata o la relativa conferma: deve restare il blocco esistente `COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED`, senza un secondo avviso duplicato.
4. Verifica che gli errori già esistenti relativi a provider o sorgente video continuino a essere segnalati correttamente.

La copertura automatizzata è in `tests/run.php`; registrare separatamente il collaudo Joomla manuale.

## IMP-01 — snapshot dei campi corso `com_fields` (0.46.0-dev, PR #266)

PR [#266](https://github.com/jeckodevelopment/jd_learn/pull/266) integrata nel commit `d41111e229ced8460ff9e30a0baa24f2094c4e60`; CI standard 4/4 nel [run 43196626438](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438). Pacchetto [JD-Learn-0.46.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438/artifacts/13932614114), SHA-256 interno `044782978b03fa740889c6f215439c7658a84a95d7bb9fe721f64dfc19ca5183`, wrapper `4993336bd9f382f8e6df7191b4842c7f3699525a12ab2c46dfa7abfff7384013` (estrarre una volta; scade il 26 novembre 2026). Accettazione manuale Joomla ancora da registrare.

Riferimenti: CORE-10, EDGE-11, AUT-04; criteri AC-48, AC-71, AC-119/120/125. Nel sito Joomla di prova crea un corso con un campo nativo `com_fields` valorizzato e pubblica una versione didattica.

1. Verificare che la pubblicazione riesca e che aula/lezioni continuino a mostrare i campi lezione previsti.
2. Cambiare il valore del campo corso dopo la pubblicazione. La scheda editoriale può mostrare il valore corrente, ma lo snapshot della versione già pubblicata e le iscrizioni assegnate devono conservare quello originario.
3. Creare una nuova versione di lavoro e pubblicarla: lo snapshot della nuova versione deve contenere il valore corrente del campo; la versione precedente deve restare invariata.
4. Ripetere con un campo non pubblicato o non visibile al livello Joomla dell’operatore: non deve essere incluso nello snapshot. Verificare inoltre che accessi e stato dei campi nativi restino gestiti da Joomla.

La CI automatizzata copre congelamento, hash e conservazione dei campi corso/lezione durante la pubblicazione. Registrare separatamente l’accettazione Joomla manuale.

## IMP-01 — ripristino dei campi corso tramite Content History (CORE-10/CORE-16)

Con un account che può modificare il corso, modificare i valori `com_fields` e usare Content History, salvare una revisione del corso con il campo impostato su “Valore A”, quindi salvare “Valore B”. Aprire **Versioni**, confrontare la revisione con “Valore A” e ripristinarla.

Atteso: i dati editoriali e il campo tornano a “Valore A”; lo stato del corso, la versione didattica corrente, le versioni assegnate, le iscrizioni e i progressi restano invariati. Se il corso ha una bozza aperta, il ripristino aggiorna i dati editoriali del corso e non sovrascrive la struttura delle versioni. Verificare anche che un account privo di `core.edit.value` sul campo non possa alterarlo tramite il ripristino.

La regressione automatica è integrata dalla PR [#268](https://github.com/jeckodevelopment/jd_learn/pull/268); CI standard 4/4 nel [run 43201132206](https://github.com/jeckodevelopment/jd_learn/actions/runs/43201132206). La prova Joomla della schermata Content History resta da eseguire.

## IMP-02 — modalità corso di sola consultazione (AUT-03 / EDGE-02)

**Comportamenti da testare in Joomla**

1. Installare il package 0.47.0-dev su Joomla 6 di prova e creare una bozza con descrizione, un modulo e una lezione Testo. In **Regole didattiche** selezionare **Sola consultazione** e salvare.
2. Verificare che la modalità imposti regole libere senza completamento; marcare tutte le lezioni come facoltative. Il preflight non deve segnalare l’assenza di attività obbligatorie, ma deve continuare a segnalare moduli vuoti, descrizione mancante e contenuti/materiali non validi.
3. Provare a pubblicare con una lezione obbligatoria: la pubblicazione deve essere bloccata con un messaggio comprensibile. Rendere la lezione facoltativa e riprovare: la pubblicazione deve riuscire.
4. In una copia della bozza, aggiungere un quiz o un prerequisito e verificare che la pubblicazione sia rifiutata. Verificare che il form non consenta il sequenziamento in modalità consultazione.
5. In un'altra lezione video facoltativa della bozza, rimuovere la durata misurata/confermata se la configurazione consente il salvataggio della bozza e pubblicare. La pubblicazione deve riuscire se la sorgente e il provider sono validi. Aprire la lezione come studente: il video deve riprodursi e non devono essere create sessioni di telemetria o righe di avanzamento; nessun completamento o percentuale deve comparire. Ripetere con provider/sorgente non validi e verificare che la pubblicazione resti bloccata.
5. Accedere come studente autorizzato, iscriversi e aprire la lezione. Aula e dashboard devono indicare “Non applicabile” per il progresso, senza percentuale, completamento o attestato. Provare l’azione manuale di completamento: deve essere rifiutata. Ripetere con un video e verificare che il tracciamento non conceda completamento.
6. Tornare in modalità **Apprendimento**: la pubblicazione senza almeno una lezione obbligatoria deve essere nuovamente bloccata. Verificare che cambiare regole in una bozza non modifichi una versione già pubblicata o le iscrizioni esistenti.

Implementato dalla PR [#270](https://github.com/jeckodevelopment/jd_learn/pull/270), merge `0a1294e491e0aa531aeca776911674fc701ddff4`; CI standard 4/4 nel [run 43207411958](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958). Package [JD-Learn-0.47.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958/artifacts/13939861423), SHA-256 interno `e65ceefa13de8ec577a84d8284ec3a09012cb6e2950351674dec9af6ed4c009f`; wrapper SHA-256 `7721896ba521c4ba8cfbb836023af16d752df3b2c40890000fe1cb29e22cc6a0`, da estrarre una volta; scadenza 26 novembre 2026. CI copre regole, readiness, pubblicazione, iscrizione e telemetria video senza completamento; il collaudo sito Joomla resta pendente. La modalità non abilita accesso anonimo o anteprima pubblica.

## IMP-02 — anteprime pubbliche esplicite delle lezioni di testo (EDGE-02 / ISC-04)

Il perimetro di questo incremento consente anteprime anonime soltanto per lezioni **Testo** marcate esplicitamente e dopo aver abilitato l’opzione nelle regole didattiche del corso. Non pubblica quiz, video o materiali associati. La scheda deve essere accessibile al catalogo pubblico; l’anteprima è in sola lettura e non crea iscrizioni, progressi o tentativi.

1. Su Joomla 6 di prova crea un corso con catalogo **Public** e contenuti **Registered**, una bozza con due lezioni Testo e un materiale privato. In **Regole didattiche** abilita le anteprime pubbliche; marca una sola lezione come disponibile in anteprima e pubblica.
2. Apri la scheda del corso in una sessione anonima. Deve apparire il collegamento solo accanto alla lezione selezionata. Aprilo: deve mostrare il titolo e il testo, senza richiedere login e senza esporre il materiale privato.
3. Confronta iscrizioni, progressi e tentativi prima/dopo: i conteggi devono restare invariati. La pagina deve indicare chiaramente la consultazione in sola lettura e avere `noindex`.
4. Richiedi direttamente l’URL per la lezione non selezionata, una lezione Video, una lezione fuori dalla finestra o un ID appartenente a un altro corso/versione: nessun contenuto deve essere restituito.
5. In una nuova bozza disabilita le anteprime lasciando una lezione marcata: la pubblicazione deve essere bloccata. Prova a marcare Video o Documento: il form non deve offrire l’opzione e una richiesta manipolata deve essere rifiutata.
6. Rendi il corso non visibile agli ospiti o ritira la versione pubblicata: scheda e URL diretta di preview devono diventare inaccessibili.

Le prove automatizzate di authoring/pubblicazione sono in `tests/joomla-curriculum.php` e `tests/joomla-publication.php`. Il collaudo Joomla manuale resta da eseguire e registrare.

## IMP-08 — filtri ricercabili del roster (0.45.0-dev)

Riferimenti: ISC-01/02/05/07, REP-02, SCR-14; AC-11/12/25/26/40/53/54/60/63/128. Nel backend Joomla aprire il roster di un corso autorizzato con più versioni e iscritti.

1. Verificare che i selettori Versione e Iscritto mostrino soltanto versioni e account associati al corso; digitare nel campo di ricerca di ciascun selettore e verificare il filtro delle opzioni senza invio del form.
2. Scegliere un valore e applicare il filtro: il totale, le righe e la paginazione devono rispettare la selezione. Combinare con ricerca nome/username, stato e avanzamento.
3. Modificare l’URL con una versione di altro corso, ID negativo o non numerico: la richiesta deve essere respinta senza esporre righe esterne. Un iscritto di altro corso non deve comparire nelle opzioni.
4. Ripetere con account amministrativo limitato al corso, anonimo e permesso revocato nella sessione: la protezione deve restare applicata.
5. Confermare che la pagina resta in sola lettura e che dati, progressi e stato iscrizione non cambiano.

Il test automatico `tests/roster-http.py` controlla opzioni native e isolamento corso; `tests/roster-filters.mjs` verifica ricerca delle opzioni e ripristino. Accettazione manuale Joomla da registrare. Implementato dalla PR [#264](https://github.com/jeckodevelopment/jd_learn/pull/264), merge `aed93820f0a948b11d3427ce9ba4d312621b0d74`; CI 4/4 nel [run 43188504970](https://github.com/jeckodevelopment/jd_learn/actions/runs/43188504970). L’esito della CI non sostituisce i cinque scenari manuali sopra.

## MAT-01 — filtro degli allegati per ambito (0.44.0-dev)

Riferimenti: MAT-01, AC-93, AC-97–103. Nel sito Joomla di prova, aprire la gestione materiali di una bozza con almeno un allegato del corso e allegati in due lezioni.

1. Verificare che l’apertura iniziale mostri tutti gli ambiti e il conteggio totale.
2. Selezionare i materiali del corso: devono apparire solo collegamenti con `lesson_id` nullo. Selezionare una lezione: devono apparire soltanto i materiali di quella lezione, senza i collegamenti riutilizzati in altri ambiti.
3. Provare paginazione e ritorno all’elenco: il filtro deve restare selezionato e conteggio/risultati devono rimanere coerenti.
4. Provare modifica, download, sostituzione, riuso e rimozione dal filtro; le azioni devono riguardare solo il collegamento scelto e rispettare ACL/versione. Alterare `material_scope` con un ID lezione di un’altra versione: deve restituire not found e non mostrare materiali.
5. Verificare un ambito vuoto: messaggio esplicito e nessun errore PHP. Ripetere con account senza permesso `jlms.materials.manage`: accesso negato.

**Automatizzato:** fixture `tests/materials-http.py` verifica selettore, separazione corso/lezione e isolamento da collegamenti riutilizzati. L’accettazione manuale Joomla resta da registrare.

## STU-05 — preferenza velocità player (0.42.0-dev)

**Riferimenti:** STU-05, VID-01, PRO-04. La preferenza è locale al browser; il selettore compare solo dopo la verifica di supporto del player e non altera il completamento.

**Comportamenti da testare in Joomla**

1. Installare il package 0.42.0-dev su Joomla 6 di prova e preparare video YouTube, Vimeo, HTML5 e Cloudflare Stream secondo le rispettive guide; usare un profilo Vimeo che consenta le velocità variabili e un video che le supporti.
2. Aprire la lezione YouTube dopo il consenso. Verificare che il selettore mostri solo le velocità restituite dall’API del video; scegliere 1,5× e controllare che il player applichi la velocità e che il valore resti selezionato ricaricando la lezione sullo stesso browser.
3. Ripetere con Vimeo: se SDK/account/video rifiuta la velocità, il selettore non deve comparire; quando supportata, verificare 0,75× e 1,5×.
4. Ripetere con HTML5 e verificare 0,5× e 2×; provare un browser che non espone playbackRate e controllare che il selettore resti nascosto.
5. Aprire Cloudflare Stream e verificare che non compaia il selettore di JD Learn. La riproduzione e i controlli nativi devono restare disponibili.
6. Con video tracciabile, campionare per un intervallo noto a velocità normale e a 1,5×; verificare che il server usi rate_milli effettivamente osservato e che l’avanzamento derivi sempre dagli intervalli unici, senza credito per il solo seek.

### Preferenza lingua caption/sottotitoli (0.50.0-dev)

1. Installare il pacchetto su Joomla 6 di prova. Preparare un video Vimeo con almeno due tracce linguistiche abilitate e un file HTML5 con tracce native `captions`/`subtitles` in due lingue. Aprire la lezione da un account iscritto e autorizzato.
2. Su Vimeo, scegliere una lingua dal controllo **Lingua dei sottotitoli**. Verificare che venga attivata la traccia scelta; ricaricare la lezione nello stesso browser e verificare la persistenza locale. Selezionare **Disattivati** e verificare che la traccia si spenga e che la preferenza venga rimossa.
3. Ripetere con HTML5; verificare che la traccia scelta sia `showing` e le altre siano `disabled`. Provare una preferenza `fr-CA` con traccia `fr`: il selettore deve scegliere la lingua principale corrispondente.
4. Ripetere su un altro browser o profilo: la preferenza non deve trasferirsi. Negare il consenso a un provider esterno: nessuna richiesta SDK o selezione deve avvenire prima del normale consenso/autorizzazione.
5. Dopo aver scelto una lingua su Vimeo o HTML5 nello stesso browser, aprire un video YouTube che contiene quella traccia. Verificare che il player chieda il caption nella lingua salvata; il selettore personalizzato non compare e i controlli YouTube restano disponibili per cambiare lingua. Ripetere con Cloudflare Stream (con codice BCP-47 corrispondente) e verificare il default all’avvio tramite il player provider; non deve comparire il selettore JD Learn. Se il video non ha quella traccia, il player deve continuare secondo il comportamento normale del provider.
6. Rimuovere la preferenza scegliendo **Disattivati** su Vimeo/HTML5 e riaprire YouTube e Cloudflare: JD Learn non deve forzare sottotitoli. Provare un valore locale non valido e localStorage bloccato; i player devono continuare a funzionare senza parametri lingua aggiunti.
7. Nell’anteprima editoriale ripetere la selezione e verificare che non siano creati progressi, iscrizioni o eventi di telemetria. Durante una lezione tracciabile, cambiare lingua e verificare che la selezione non alteri stato, percentuale, soglia o progressi del video.
8. Verificare che la preferenza resti soltanto nel browser corrente, non sia inviata a Joomla e non cambi fra dispositivi; bloccare localStorage e verificare che i player continuino a funzionare a 1×.

**Implementata dalla PR [#276](https://github.com/jeckodevelopment/jd_learn/pull/276), merge `d29cdc9326bcc0d1f9ac3dd454fa40189ca20c82`, versione 0.50.0-dev. CI standard 4/4 [run 43240360856](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856). Pacchetto [JD-Learn-0.50.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856/artifacts/13954487496), SHA-256 interno `fce6b1f09ab3d7d1fe1821aff366a11718e420e2331754ea18cf4b741b0b877c`, digest wrapper `094f514f005324c76af7284e4e0d43a2e09a25563976c94982504e23bf78d342` (estrarre una volta; scade il 26 novembre 2026). I collaudi sul sito Joomla con account/provider reali sono ancora da registrare.

**Esito sito Joomla:** Non eseguito.


---

## IMP-06 — scansione materiali con ClamAV reale (0.41.74-dev)

**Stato:** PR [#229](https://github.com/jeckodevelopment/jd_learn/pull/229) integrata in `0.41.74-dev` (merge `d02aeceb2402b8b609fbe97a4ad31c38aa7b4b8c`). CI standard 4/4 nel [run 43094357303](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303); artifact [JD-Learn-0.41.74-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303/artifacts/13890232415), wrapper SHA-256 `902cc7909297f38936feddade12020bffec6335a7fd430ffe173b71e172e29c8`. Accettazione manuale Joomla sul sito di prova ancora pendente.

**Comportamenti da testare in Joomla**

1. In un sito di prova, configura ClamAV con socket Unix locale e attiva **Componenti → JD Learn → Opzioni → Materiali → Scansione antivirus obbligatoria**.
2. Carica un file innocuo in una bozza e verifica che il risultato ClamAV sia `clean`, lo stato diventi pronto e l'asset possa essere usato dal preflight.
3. Solo in un ambiente usa-e-getta, carica il file di test EICAR come allegato. Verifica che sia rifiutato e che download e pubblicazione restino bloccati; elimina il record rifiutato.
4. Ferma ClamAV e carica un altro file: deve restare in quarantena. Riavvia il servizio, avvia la scansione dalla gestione materiali e verifica che il file innocuo diventi pronto.
5. Ripeti con interfaccia italiana e inglese, controllando che i messaggi distinguano esito infetto e scanner indisponibile.

La CI standard usa ClamAV reale, firme aggiornate e EICAR; la suite resta distinta dal collaudo sul server Joomla.

**Esito sito Joomla:** Non eseguito.

---

## IMP-05 — configurazione Cloudflare Stream solo API (0.41.73-dev)

**Stato:** PR [#228](https://github.com/jeckodevelopment/jd_learn/pull/228) integrata in `0.41.73-dev` (merge `d677924aca265f3397be156f7298d614bad330f3`). CI 4/4 nel [run 43091365354](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354); pacchetto [JD-Learn-0.41.73-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354/artifacts/13888777492), SHA ZIP `b3f577a3760a5884290a9dcb52ad09a10aaf3fd2d04d521408f1574f31c83172`. Accettazione manuale Joomla con account Cloudflare reale ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Installa JD Learn 0.41.73-dev sopra una versione precedente con il plugin Cloudflare Stream configurato. Apri **Sistema → Plugin → Cloudflare Stream**: il form non deve mostrare modalità di firma, chiave locale o ID della chiave.
2. Verifica che playback, account, dominio, hostname, riferimento al token e durata/rinnovo restino disponibili. I parametri legacy di firma salvati non devono impedire il funzionamento API.
3. Con un account di prova e un video protetto già configurati, verifica pubblicazione/riproduzione/rinnovo dopo consenso. Revoca il consenso e verifica che non venga creato il player.
4. Ripeti in italiano e inglese. Verifica che il video e il token di riferimento non siano modificati dall'upgrade.

**Esito sito Joomla:** Non eseguito.

---

## IMP-06 — periodo configurabile per la pulizia degli asset locali orfani (0.41.72-dev)

**Stato:** candidato di sviluppo; la verifica automatica di Joomla Scheduler è aggiornata per distinguere il periodo configurato da una soglia fissa. Accettazione manuale in Joomla ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Apri **Componenti → JD Learn → Opzioni → Materiali**. Verifica che la nuova impostazione abbia default **24 ore**, accetti valori da **24 a 8760** ore e mostri la descrizione in italiano e inglese. Salva 48 ore e riapri le opzioni: il valore deve restare.
2. Configura storage privato fuori dalla document root e abilita **Task - JD Learn**. Crea manualmente l'attività “JD Learn: pulisci i file materiali orfani” con limite 100.
3. Avvia l'attività prima che un asset orfano raggiunga 48 ore: il suo blob e record devono restare. Gli allegati ancora referenziati devono restare anche quando sono più vecchi.
4. Dopo 48 ore, avvia di nuovo l'attività: gli asset locali non referenziati eleggibili possono essere rimossi; file sconosciuti nella cartella, asset referenziati e materiali di versioni pubblicate devono restare intatti.
5. Imposta il periodo massimo di 8760 ore e verifica che un asset orfano più giovane non venga rimosso da un'esecuzione giornaliera.

## IMP-07 — Associazioni multilingua dei corsi pubblicati (CORE-07, 0.41.68-dev; PR #223 integrata)

**Stato:** PR [#223](https://github.com/jeckodevelopment/jd_learn/pull/223) integrata nel main per AC-20 e AC-48. Il form nativo può modificare il grafo delle associazioni anche quando i corsi sono pubblicati; le versioni didattiche devono restare immutate. Pacchetto installabile `dist/pkg_jlms-0.41.68-dev.zip` (SHA-256 `ed115844d2de2925f8a087a3a8cdbed646e870bfd529ca31eb3b2dfd1f97a7c0`). Accettazione Joomla manuale da eseguire.

**Comportamenti da testare in Joomla**

1. Abilita il plugin Language Filter e crea/pubblica due corsi, uno `en-GB` e uno `it-IT`, ciascuno con una versione didattica, materiali e progresso di un iscritto.
2. Nel form del corso inglese collega la traduzione italiana; salva e riapri entrambi i corsi per verificare il collegamento reciproco e i selettori lingua.
3. Rimuovi e poi ripristina il collegamento da un corso pubblicato. Verifica nel selettore di un altro corso che i corsi pubblicati siano selezionabili.
4. Controlla che dopo ogni modifica gli ID delle versioni live, il programma, gli iscritti e i progressi siano invariati.
5. Prova lingua duplicata, lingua `*`, target archiviato/non valido e target collegato a un altro gruppo: il salvataggio deve rifiutare o lasciare il grafo esistente coerente.
6. Con un utente senza permesso di modifica o con un corso in checkout da un altro operatore, verifica che una richiesta diretta non possa modificare o staccare il gruppo.
7. Apri la pagina pubblica e verifica che la Language Switcher mostri soltanto traduzioni pubblicate e accessibili; una traduzione non accessibile non deve essere rivelata.

## IMP-07 — Ricerca nei selettori delle traduzioni (CORE-07, 0.41.69-dev; [PR #224](https://github.com/jeckodevelopment/jd_learn/pull/224) integrata)

**Stato:** PR #224 integrata in main 0.41.69-dev. Il campo filtra localmente i corsi autorizzati per titolo o ID; limite: 2.000 candidati per lingua. La convalida server è indipendente. Pacchetto [JD-Learn-0.41.69-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43057819152/artifacts/13872631084) (SHA-256 `54b8667258a833fcd907b3a9767abbd2d7cadbbd8e01fceecd0dda1c86034450`). CI [#43057819152](https://github.com/jeckodevelopment/jd_learn/actions/runs/43057819152): controlli documentali e suite PHP 8.3/MySQL e PHP 8.4/MariaDB superati; job Chromium annullato durante l’installazione del runtime; suite non eseguita. Accettazione Joomla manuale ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Apri un corso con almeno 20 corsi eleggibili in una lingua di destinazione. Nel campo di ricerca digita una parte del titolo e verifica che restino selezionabili solo le corrispondenze.
2. Cerca un ID completo e un frammento dell'ID nel titolo visualizzato (#ID); verifica che l'esito corretto sia selezionabile.
3. Inserisci un testo senza corrispondenze: l'opzione di scollegamento resta disponibile e nessun altro corso deve poter essere scelto.
4. Cancella il testo, scegli una traduzione e salva; riapri la scheda e verifica la selezione persistente.
5. Ripeti su due lingue di destinazione nella stessa scheda: il filtro di un campo non deve alterare l'elenco dell'altro.
6. Verifica che un corso senza permesso di modifica e un corso in checkout da un altro operatore non compaiano fra i candidati, anche cercandone titolo e ID.
7. Con il filtro attivo salva una modifica alle associazioni; verifica che la validazione server mantenga i controlli su lingua, ACL, checkout e appartenenza a un altro gruppo.

## IMP-07 — Tag Joomla nativi sui corsi (CORE-09, 0.41.67-dev; PR #222 integrata)

**Stato:** PR [#222](https://github.com/jeckodevelopment/jd_learn/pull/222) integrata nel main; associazioni registrate con il tipo contenuto `com_jlms.course`. Criteri collegati: CORE-09, AC-19 e AC-64. Pacchetto installabile `dist/pkg_jlms-0.41.67-dev.zip` (SHA-256 `637e8284dde13f5b3c89398bac21a1aa862236a07619f978f33130dc8ec07ef6`). Accettazione manuale in Joomla ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Crea due tag pubblicati in Joomla → Tag. Crea un corso in bozza, assegna entrambi i tag e salva; riapri il corso e verifica che le selezioni restino.
2. Pubblica il corso e verifica che i tag compaiano nelle viste/filtri nativi `com_tags` e nel filtro tag del catalogo JD Learn, rispettando stato, accesso, lingua e ACL del corso.
3. Rimuovi un tag dal corso, salva e verifica che non risulti più associato né tra i risultati filtrati per quel tag.
4. Duplica il corso: verifica che i tag siano copiati nel nuovo corso; cambia la selezione nel duplicato e controlla che l’originale non cambi.
5. Rinomina un tag dal gestore Joomla e verifica che il nuovo nome sia visibile nell’editor, nei filtri e nelle viste tag senza ricreare l’associazione.
6. Verifica separatamente un corso italiano e uno inglese, e accedi con un utente privo dei permessi di modifica: non deve poter alterare le associazioni con una richiesta diretta.

## IMP-06 — consegna protetta dei materiali su Apache/LiteSpeed (0.41.66-dev)

**Stato:** PR [#221](https://github.com/jeckodevelopment/jd_learn/pull/221) integrata nel main 0.41.66-dev. Il flusso usa PHP protetto e non richiede Nginx o moduli aggiuntivi. Pacchetto installabile: `dist/pkg_jlms-0.41.66-dev.zip` (SHA-256 `214e311d5cee72963b3e9d01ba958dfa52088b781c52052761e4f1d74ffdc4af`). Accettazione Joomla da eseguire.

**Comportamenti da testare in Joomla**

1. In Opzioni componente configura la directory privata dei materiali e abilita la consegna PHP. Non aggiungere location o moduli specifici del web server.
2. Come studente autorizzato scarica un allegato e apri in anteprima un PDF e un’immagine. Verifica contenuto, MIME e Content-Disposition; il download non deve assegnare progresso alla lezione.
3. Prova una richiesta Range e HEAD: Range deve restituire solo i byte richiesti; HEAD deve restituire gli header senza corpo.
4. Revoca il livello di accesso o sospendi l’iscrizione e ripeti la richiesta: il file deve essere negato anche con la sessione già autenticata.
5. Verifica che un file alterato o di dimensione incoerente non venga servito e che la directory di storage resti fuori dalla document root.

## IMP-07 — ricollocazione esplicita delle dipendenze categoria (0.41.70-dev)

Riferimenti: CAT-01, CORE-01, LIFE-02. Il nuovo strumento backend esamina i riferimenti alle categorie e permette di ricollocare i record in categorie destinatarie della stessa sezione prima della rimozione. Verificare in Joomla 6 di prova con categorie dedicate a corsi, percorsi, docenti e domande. Per la prova sui corsi usare un corso pubblicato con versione attiva, iscrizione/progresso e associazione multilingua.

**Comportamenti da testare in Joomla**

1. Apri JD Learn → Gestione dipendenze categorie, seleziona una categoria con record collegati e verifica conteggi e destinazioni consentite.
2. Verifica che le destinazioni appartengano alla stessa sezione, non includano la sorgente o sue discendenti e rispettino i permessi dell’operatore.
3. Ricolloca record in ciascuna delle quattro sezioni. Verifica che i riferimenti cambino categoria e che record, storici, versioni, iscrizioni, progressi, tentativi e risultati restino invariati.
4. Per i corsi, verifica in Smart Search che gli elementi idonei riflettano la nuova categoria; ripeti anche con Finder disattivato.
5. Modifica i dati mentre la pagina di ricollocazione è aperta e riprova l’operazione: deve essere annullata integralmente se i conteggi non coincidono più.
6. Dopo la ricollocazione elimina la sorgente dal gestore Categorie nativo. Ripeti con categorie in checkout, destinazioni non autorizzate, destinazioni di altro tipo, nessun target scelto e una categoria con discendenti; nessuna operazione parziale o eliminazione implicita deve verificarsi.

## IMP-07 — lifecycle sicuro delle categorie (0.41.63-dev, PR #215 integrata)

**Stato:** CI PR #215 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654); eseguire la prova manuale lifecycle/ricollocazione categorie sul pacchetto [JD-Learn-0.41.70-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43075293879/artifacts/13881162374). La CI verifica FK su corsi, percorsi, docenti e domande, rifiuto della cancellazione permanente con riferimenti e conservazione del corso durante cestinamento/ripristino.

**Comportamenti da testare in Joomla**

1. Crea una categoria corsi e collegale un corso pubblicato con iscrizione e progresso già registrati.
2. Dal gestore categorie cestina la categoria. Verifica che la categoria e il corso spariscano dal catalogo pubblico, mentre il record corso, l’iscrizione e il progresso restano presenti.
3. Ripristina la categoria dal cestino e verifica che la visibilità del corso torni senza alterare versione, iscrizione o progresso.
4. Prova la cancellazione permanente della categoria ancora collegata al corso: Joomla/database deve rifiutarla e i record devono rimanere integri.
5. Sposta il corso in un’altra categoria valida; ripeti la cancellazione permanente e verifica che sia consentita.
6. Ripeti i passaggi 1, 4 e 5 per una categoria usata da un percorso, un docente e una domanda.

## IMP-07 — filtri docente e percorso nel catalogo (0.41.62-dev, PR #213 integrata)

PR [#213](https://github.com/jeckodevelopment/jd_learn/pull/213), milestone **1.0.0**. CI standard 4/4 nel [run 42923290761](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761); pacchetto [JD-Learn-0.41.62-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761/artifacts/13811584820), checksum wrapper `003c56dd360cd8a7c375c220a9b1a0ce0c57e6669913a693c63ea6781f032cf2`, checksum ZIP Joomla `e26a39a0c63c3ba07a9851b49b701582d3df8a656ac55c634cd1414ff9412728`. Riferimenti: CAT-01, AC-41 e AC-85. Su Joomla 6 di prova predisporre corsi pubblici con docenti pubblici e percorsi pubblicati; aggiungere profili o percorsi con accesso limitato collegati a corsi accessibili.

1. Da ospite, verificare nel catalogo e nella pagina categoria i filtri Docente e Percorso. Le opzioni devono includere solo profili/percorsi pubblici associati a corsi visibili nel contesto.
2. Selezionare un docente: devono restare i corsi pubblici a lui assegnati. Ripetere selezionando un percorso: mostrare solo i corsi visibili associati al percorso.
3. Combinare i due filtri con tag, difficoltà, durata, ricerca, ordinamento, vista elenco e paginazione. In una categoria i risultati restano nel ramo della categoria.
4. Accedere con account a livelli differenti e verificare opzioni e risultati coerenti con le autorizzazioni correnti; non devono trapelare profili, percorsi o corsi privati.
5. Inviare ID inesistenti o non accessibili per entrambi i filtri: risultati vuoti senza allargare il catalogo. Ripetere in IT/EN, SEF attivo/disattivo e viewport mobile.

**Esito sito Joomla:** Non eseguito.

## IMP-07 — filtro tag nel catalogo pubblico (0.41.60-dev, PR #209 integrata)

Riferimenti: CAT-01, AC-41 e AC-85. Installare il pacchetto candidato su Joomla 6; predisporre corsi pubblicati con tag pubblici, tag con accesso differenziato e corsi privati o in categorie non visibili.

1. Da ospite, verificare catalogo e pagine categoria: il selettore mostra solo tag pubblicati/accessibili collegati a corsi visibili.
2. Filtrare per tag nel catalogo e in una categoria con corsi diretti e discendenti: mostrare solo corsi associati, mantenendo il ramo categoria.
3. Accedere con un utente autorizzato e verificare che compaiano i tag aggiuntivi consentiti e solo i corsi per cui ha accesso.
4. Combinare tag, difficoltà, durata, ricerca, ordinamento e paginazione; un ID tag inesistente non deve allargare i risultati.
5. Ripetere IT/EN e SEF attivo/disattivo; non devono comparire titoli, riassunti o conteggi di corsi nascosti.

## IMP-07 — filtri di difficoltà e durata del catalogo (0.41.59-dev, PR #210 integrata)

PR [#210](https://github.com/jeckodevelopment/jd_learn/pull/210), milestone **1.0.0**, integrata con squash nel commit `e5d0bae954c099c8522db57c30b1e32882b468ee`. CI standard 4/4 nel [run 42913999069](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069). Pacchetto [JD-Learn-0.41.59-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069/artifacts/13807843959), wrapper SHA-256 `c1e9e8e7c3e64daebc4c5162f64695c89b0d3d79a9c41019f5eeee087227644c`, ZIP Joomla SHA-256 `f22a8a3fb1c87e17f10f0342e6ba6980e5c8882e83dcfec41bab34713b3c41dc`, disponibile fino al 24 novembre 2026.

Riferimenti: CAT-01/CAT-04, AC-41/AC-85. Verificare su Joomla 6 di prova con almeno quattro corsi pubblicati, nelle categorie accessibili: uno principiante da45minuti, uno avanzato da90minuti, uno intermedio oltre120minuti e uno senza durata stimata. Predisporre anche un account con accesso limitato e una categoria con corsi discendenti.

1. Aprire il catalogo da ospite e come account autorizzato. Atteso: i filtri difficoltà e durata sono disponibili con label tradotte in italiano/inglese.
2. Selezionare principiante e fascia30–60minuti: deve restare solo il corso principiante da45minuti. Selezionare avanzato o oltre120minuti e verificare i rispettivi corsi; le schede senza durata non devono comparire quando una fascia è attiva.
3. Aggiungere ricerca e ordinamento, poi impostare un filtro dalla pagina di una categoria: risultati e paginazione devono rispettare tutti i filtri e restare nel ramo categoria selezionato.
4. Ripetere con un corso o categoria non visibile all’account limitato: il filtro non deve rivelare titoli, riassunti o conteggi dei corsi esclusi.
5. Inviare manualmente valori non previsti per difficoltà e durata: il catalogo deve restare integro e trattarli come filtri assenti.

**Esito sito Joomla:** Non eseguito.

## IMP-07 — metadati SEO e social pubblici (0.41.57-dev, PR #207 integrata)

PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207), milestone **1.0.0**, integrata con squash nel commit `32215c493fe1d7a2bf99871b5f93087a842563bd`. CI standard 4/4 nel [run 42905972598](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598). Pacchetto [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462), wrapper SHA-256 `f0729b8cfe769f5d7f2065522651bc4b56278c6a4fdb2e29adae4e54be2199de`, ZIP Joomla SHA-256 `7e078f93ff421c143e7a7c198911e4d38c5c6208c788c5920080fd5d4d642921`, disponibile fino al 24 novembre 2026. Collaudo manuale sul sito Joomla di prova richiesto; l’esito va registrato qui dopo la prova.

1. Installare il pacchetto candidato su Joomla di prova e creare una scheda corso pubblica con titolo SEO, descrizione SEO e immagine promozionale locale; creare anche un corso con catalogo pubblico ma contenuto riservato, una categoria pubblica e una categoria accessibile solo agli utenti registrati, e un percorso pubblico.
2. Aprire la scheda pubblica e verificare nel sorgente HTML titolo/descrizione, `og:title`, `og:description`, `og:type`, `og:url`, `og:image`, `twitter:card`, `twitter:title`, `twitter:description` e `twitter:image`. L’immagine deve essere un URL assoluto del sito.
3. Aprire categoria e percorso pubblici: verificare canonical assoluto, titolo e descrizione social coerenti con la pagina, senza duplicati di meta tag.
4. Aprire scheda corso con catalogo o contenuto non accessibile agli ospiti e la categoria privata: verificare `robots=noindex, nofollow` e assenza di canonical/preview social che li promuovano come contenuti pubblici. Accedere poi da utente autorizzato: i contenuti restano consultabili, ma non devono diventare indicizzabili.
5. Aprire un percorso da utente autenticato: verificare `robots=noindex, nofollow` e assenza di metadati social/canonical.
6. Ripetere con SEF attivo e disattivo e in IT/EN; verificare che canonical sia assoluto, stabile e privo di parametri di ricerca, filtro o paginazione.

## IMP-07 — Ricerca e ordinamento del catalogo (0.41.56-dev, PR #206 integrata)

La PR aggiunge ricerca su titolo/sommario e ordinamento recenti/titolo, nel catalogo completo e dentro una categoria. Verificare sul sito Joomla 6 di prova con corsi pubblici e contenuti riservati di fixture.

1. Installare il pacchetto candidato. Aprire Catalogo corsi e verificare i controlli “Cerca nei corsi” e “Ordina per” in italiano; ripetere con lingua inglese.
2. Cercare una parola univoca nel titolo e poi una nel sommario promozionale; verificare che compaiano solo le schede pubbliche accessibili.
3. Usare un termine presente solo in una lezione o in un campo privato: nessun corso deve comparire per quel termine.
4. Ordinare per “Titolo” e per “Più recenti”; verificare ordine stabile e che paginazione e selezione restino coerenti.
5. Aprire una categoria con discendenti, cercare un corso appartenente a un discendente, cambiare ordinamento e paginare; verificare che la ricerca resti limitata alla categoria e ai discendenti.
6. Provare caratteri speciali e stringhe con virgolette; nessun errore SQL o allargamento ai contenuti protetti.

La PR #206 è stata integrata nel commit `cc5af168e5b42aff0b30db2e051acecd86806b9c`; CI standard 4/4 superata nel [run 42901417240](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240). [Pacchetto installabile JD-Learn-0.41.56-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240/artifacts/13803875500), wrapper SHA-256 `3c3d7d078ae6b3d03e530d81d833bd89babd8c12903580f84f8d8f4b3b526cd2`, disponibile fino al 24 novembre 2026. Estrarre il wrapper per ottenere lo ZIP Joomla. La CI verifica il rendering HTTP e l’isolamento dei termini di ricerca in fixture; l’accettazione sul sito reale resta da registrare separatamente.

## IMP-07 — Smart Search del catalogo JD Learn (0.41.55-dev, PR #204 integrata)

La PR #204 è stata integrata nel commit `5018d895e52727be29cedb31f4f93b5245ef346a`. CI standard 4/4 superata nel [run 42898585659](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659). Il collaudo manuale Joomla sul sito di prova resta richiesto prima di chiudere IMP-07; la CI non sostituisce queste prove. Pacchetto installabile [JD-Learn-0.41.55-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659/artifacts/13802930146), wrapper ZIP SHA-256 `48af954a9fa682519e5a6c8121ef97d22b17bd37004f9969e1e8c59c694e66e1`, disponibile fino al 24 novembre 2026. Estrarre il wrapper per ottenere il pacchetto Joomla.

1. Installare il pacchetto candidato su Joomla 6 di prova con `com_finder` attivo. Verificare **Estensioni → Plugin**: `Smart Search - JD Learn` è abilitato alla prima installazione e resta nello stato scelto durante un aggiornamento.
2. Predisporre corsi con titolo/sommario univoci: pubblicato in categoria pubblica, non pubblicato, catalogo con livello registrato, categoria privata, antenato non pubblicato, lingua differente, senza versione pubblicata, e pubblicato con `publish_up`/`publish_down` configurato. Predisporre anche un corso pubblico con titolo e sommario promozionale.
3. In **Componenti → Smart Search → Indice**, avviare l’indicizzazione e verificare che risultino soltanto i corsi pubblici idonei. Cercare dal sito il titolo univoco: il link apre la scheda corso e mostra solo titolo/sommario; nessuna lezione o informazione privata compare nel risultato.
4. Modificare titolo e sommario del corso pubblico, reindicizzare, quindi verificare i nuovi termini e l’assenza dei precedenti. Ritirare/pubblicare un corso e verificare rispettivamente rimozione/ricomparsa del risultato.
5. Cambiare accesso, stato o lingua di una categoria/antenato; spostare una categoria con corsi dentro/fuori. Dopo aggiornamento indice, verificare le nuove regole sulle schede discendenti.
6. Ripetere in IT e EN con filtro lingua attivo. Verificare che corsi incompatibili con la lingua categoria non compaiano e che i corsi con finestra programmata restino esclusi.

## PR #196 integrata — retry del progresso audio

PR [#196](https://github.com/jeckodevelopment/jd_learn/pull/196), milestone **1.0.0**, introduce un retry singolo dello stesso salvataggio per errori di rete, timeout, 408, 429 e 5xx. Gli errori permanenti non vengono riprovati. Il progresso audio non completa la lezione né aumenta la percentuale; la conferma esplicita resta necessaria. CI 4/4 nel [run 42876421523](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523). Collaudo manuale pendente.

1. Avviare una lezione audio, attendere il salvataggio e ricaricare: la ripresa deve partire dal punto registrato.
2. Mettere in pausa e spostare il cursore; verificare salvataggio e ripresa della posizione aggiornata.
3. Simulare rete/5xx: il client deve ritentare una volta lo stesso punto e mostrare il salvataggio se riuscito.
4. Simulare 403: nessun retry ripetuto, errore visibile, nessun cambiamento di completamento o percentuale.
5. Arrivare alla fine dell’audio: la lezione deve richiedere ancora la conferma esplicita.

[Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523/artifacts/13793090250), disponibile fino al 24 novembre 2026.

## PR #194 integrata — filtri della banca domande

PR [#194](https://github.com/jeckodevelopment/jd_learn/pull/194), milestone **1.0.0**, aggiunge i controlli Ricerca, Categoria, Stato e Lingua e corregge l’intestazione Azioni. CI standard 4/4 superata nel [run 42873607501](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501). Collaudo manuale pendente.

1. Aprire JD Learn → Banca domande come amministratore autorizzato.
2. Verificare i quattro controlli; filtrare singolarmente, poi combinarli. Usare Pulisci.
3. Verificare risultati, paginazione e ordinamento.
4. Verificare **Azioni** in italiano e **Actions** in inglese, senza placeholder.
5. Con un ruolo senza `jlms.quiz.manage`, verificare che l’accesso sia negato.

[Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501/artifacts/13791570779).

## PR #192 integrata — default “Usa globali” per il fuso del corso

Issue [#172](https://github.com/jeckodevelopment/jd_learn/issues/172) corretta: il campo Timezone nativo Joomla offre e seleziona “Usa globali” per un nuovo corso. Il salvataggio risolve l’opzione nel fuso IANA di Configurazione globale; corsi esistenti e date UTC restano invariati. PR [#192](https://github.com/jeckodevelopment/jd_learn/pull/192), merge commit `64ae66454c7d2a64a3237f1f7f12b2d9174f95ae`.

CI standard 4/4 superata nel [run 42865678167](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167). Pacchetto [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167/artifacts/13787890952); wrapper SHA-256 `9b29ce5e6d2e5cc54f857d64080b3dfb45be6ef46dd967248cb04ba20113d3c4` (543923 byte), pacchetto Joomla interno `2a42ae0a50139cf727995b2ab50764d33fe6734ab738a09e6f7cde62aab19ac3` (544064 byte). Scadenza artifact: 24 novembre 2026, 17:34 UTC. Collaudo manuale sul sito Joomla ancora pendente:

1. Impostare **Europe/Rome** in Configurazione globale → Server → Fuso orario.
2. Aprire JD Learn → Corsi → Nuovo e verificare che il campo fuso mostri **Usa globali**, non Abidjan.
3. Inserire una data/ora locale, salvare e riaprire: il selettore deve mostrare Europe/Rome e l’orario deve restare invariato.
4. Cambiare il fuso globale, creare un altro corso e verificare che resti selezionato **Usa globali**; dopo il salvataggio deve risultare il nuovo fuso globale.
5. Aprire un corso con fuso esplicito e verificare che fuso e date non cambino.

## PR #190 integrata — indice aula su smartphone (0.41.52-dev)

CI standard 4/4 superata nel [run 42860617351](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351). Il [pacchetto 0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351/artifacts/13784608215) contiene il runtime verificato (SHA-256 interno `278d260dddbdcdda305fff7456943d5ee43a15d042c94e1c3d901fe9f166c332`, wrapper `6214644bc36d91e60cb7da5b9d31f9216e3e53c8492eba1a5ea3ac5fa4962ddd`; scade il 24 novembre 2026 alle 17:06 UTC). La procedura manuale seguente resta pendente.

1. Aprire da smartphone (viewport 375 px) un corso pubblicato con più moduli e lezioni; aprire una lezione accessibile. Atteso: il programma inizia chiuso e il contenuto della lezione appare senza scorrere l’intero indice.
2. Attivare “Mostra programma”. Atteso: si espande l’indice, il controllo cambia in “Nascondi programma” e aria-expanded passa a true; attivarlo di nuovo lo chiude. Verificare navigazione da tastiera e focus visibile.
3. Selezionare una lezione dal programma aperto. Atteso: viene caricata la lezione selezionata e al nuovo caricamento l’indice mobile torna chiuso.
4. Ridimensionare il viewport oltre 48rem. Atteso: il controllo sparisce e il programma resta visibile nella colonna desktop. Verificare anche la navigazione su corso con lezione bloccata e senza JavaScript: contenuto non autorizzato non viene mostrato e i link del programma restano raggiungibili.

## PR #187 integrata — retry del salvataggio di avvio (0.41.51-dev)

CI standard 4/4 superata nel [run 42845258898](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898). Il [pacchetto 0.41.51-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898/artifacts/13779340061) contiene il runtime verificato (SHA-256 interno `1f7563bd4957f66027dbc1d9b925784a087e196a75ad8909d4b78ce9b2455cab`); questa procedura Joomla resta pendente.

1. Installare il pacchetto verificato 0.41.51-dev su Joomla 6 di prova. Predisporre un corso pubblicato e uno studente iscritto; aprire una lezione testo/documento accessibile e verificare lo stato visibile “Salvataggio dell’attività…” seguito da “Attività avviata e salvata”, quindi “In corso” nell’indice.
2. Con gli strumenti di rete del browser, provocare un errore transitorio (timeout/connessione interrotta oppure risposta 503) alla prima chiamata `lesson.begin`; ripristinare la rete durante la finestra di retry. Verificare una sola seconda richiesta, con la stessa chiave `idempotency_key`, esito salvato e una sola riga di progresso `started`.
3. Lasciare la rete offline per entrambe le richieste. Atteso: al massimo due tentativi e messaggio d’errore; nessun completamento/credito e ricaricando la pagina il flusso può riprovare.
4. Provare un rifiuto 403 (sessione/token non valido o accesso revocato) e verificare che non sia ripetuto; nessun contenuto riservato viene mostrato o mutato.
5. Verificare che il completamento manuale successivo conti una sola volta e che l’avvio di una lezione audio/video non azzeri posizione o copertura già acquisita.

## PR #185 integrata — avvio e ripresa della lezione (0.41.50-dev)

CI standard 4/4 superata nel [run 42839639601](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601). Il pacchetto [0.41.50-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601/artifacts/13776106013) verificato contiene SHA-256 interno `9534346da253c8f953da826d203b3f3509fab0682b09e7d09736e60180e28e83` (542668 byte) e wrapper `461d8dd9c4d8287c15d6dc667821920e25c4c879108f0a3d9a2ac8cea5ec70f6` (542489 byte), con scadenza 24 novembre 2026 15:03 UTC. L’accettazione manuale resta aperta.

1. Installare il pacchetto su Joomla 6 di prova. Creare un corso pubblicato con due lezioni obbligatorie testo/documento, iscrivere uno studente e aprire la seconda lezione senza premere “Segna come completata”.
2. Verificare che lo stato della seconda lezione diventi “In corso”, l’avanzamento del corso resti 0% e la lezione non risulti completata. Ricaricare l’aula e verificare la persistenza dello stato.
3. Tornare alla Dashboard, usare “Riprendi” e verificare che riapra l’ultima lezione aperta ancora disponibile. Ripetere con altro browser/dispositivo sullo stesso account.
4. Premere “Segna come completata”: la stessa attività passa a completata, avanza il corso una sola volta e non genera conflitti o righe duplicate. Verificare anche una lezione audio/video, per cui il player continua a salvare posizione/copertura senza cambiare la regola di completamento.
5. Tentare avvio su iscrizione non propria, lezione futura, chiusa, bloccata da prerequisiti, token mancante o metodo GET: atteso diniego e nessuna mutazione. Confermare che visualizzare una pagina bloccata non crea progresso.

## PR #183 integrata — soglia video per lezione (0.41.49-dev)

CI standard 4/4 superata nel [run 42830117573](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573). Il pacchetto [0.41.49-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573/artifacts/13770693036) contiene il runtime verificato; SHA-256 interno `46db03c8214873bbd2d266f067585458ebec059e3abbaf2fe29d9e38be1635bb`. Accettazione manuale sul Joomla di prova ancora pendente.

1. In Joomla di prova, creare una bozza con soglia di versione al 75% e due lezioni video. Lasciare vuota la soglia della prima e impostare 50% sulla seconda. Salvare entrambe, riaprire il Programma e verificare che il valore esplicito sia mostrato e la prima resti vuota (eredita).
2. Impostare valori limite 0,01% e 100% e salvare; verificare che valori 0, 100,01 e più di due decimali siano rifiutati mantenendo i dati già salvati.
3. Pubblicare e iscrivere uno studente di prova. Verificare che la prima lezione applichi il 75% e la seconda il 50% del video visto, anche se entrambe usano lo stesso provider.
4. Modificare la soglia in una nuova versione e pubblicarla: lo studente già iscritto mantiene la versione e la soglia originali; una nuova iscrizione riceve i nuovi valori.
5. Verificare che una lezione non video non esponga la soglia e che una richiesta alterata che provi a salvarla su testo/audio/documento venga respinta senza modifiche.

Warning: truncated output (original token count: 14491)
Total output lines: 515

## PR #162 integrata — anteprima dei livelli di accesso (0.41.43-dev)

CI standard 4/4 superata nel [run 42712132178](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178). Il [pacchetto 0.41.43-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178/artifacts/13715794795) contiene il runtime verificato (SHA-256 interno `39797f26dfee8bcfec34c9aa8365c218653e56857c8133808f1892996e2de738`); il collaudo Joomla resta aperto.

1. Aggiornare il sito Joomla 6 di prova. Predisporre un corso pubblicato con catalogo **Public**, contenuto **Registered**, categoria pubblica e un editor con permesso di aprire la preview del programma.
2. In **Programma → Anteprima editoriale → Verifica dei livelli di accesso**, selezionare solo **Public** e inviare. Atteso: catalogo visibile, contenuto non accessibile, requisito combinato non soddisfatto.
3. Selezionare **Public** e **Registered**. Atteso: entrambe le condizioni vere e requisito combinato soddisfatto. Provare solo Registered: il catalogo resta nascosto se il livello della categoria/corso è Public.
4. Verificare che gruppi dell’editor, stato/versione corso, iscrizioni e progressi restino invariati; nessun tentativo, iscrizione o attività viene creato.
5. Richiedere la preview per un corso fuori dall’ACL editor. Atteso: Joomla nega la richiesta senza mostrare livelli o esiti.

## PR #159 integrata — archiviazione e ripristino (0.41.42-dev)

CI standard 4/4 superata nel [run 42704783478](https://github.com/jeckodevelopment/jd_learn/actions/runs/42704783478). Il [pacchetto 0.41.42-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42704783478/artifacts/13711989978) contiene il runtime verificato (SHA-256 interno `0e8ab0086a7305829b24f76e5859369213320d8529eda56a9556edaf7f0ad6fb`); resta aperto il collaudo sul Joomla di prova.

1. Predisporre un corso pubblicato e un’iscrizione attiva con progressi, più autore e revisore con ACL distinte.
2. Come revisore…8326 tokens truncated…sponibile nella finestra e non più disponibile dopo la fine.
5. Modificare/sostituire materiale in bozza e verificare aggiornamento/conservazione della finestra e immutabilità della versione pubblicata.

**Esito sito Joomla:** Non eseguito.

### PR #80 — preflight disponibilità corso

Riferimenti: [PR #80](https://github.com/jeckodevelopment/jd_learn/pull/80), criteri di pubblicazione del corso. 

1. In una bozza, allegare materiale in quarantena/non pronto: Programma mostra un blocco di pubblicazione.
2. Con scansione ClamAV obbligatoria, il blocco resta finché il file non è pronto e la scansione `clamd` non è pulita.
3. Materiali solo anteprima o programmati mostrano un blocco.
4. Materiale pronto, non di anteprima e senza finestra rimuove il blocco (con scansione obbligatoria, solo dopo esito pulito).
5. La pagina preflight non apre/scansiona file; la pubblicazione valida di nuovo lato server e blocca materiale non valido.

**Esito sito Joomla:** Non eseguito.

### PR #74 — controlli di pubblicazione

Riferimenti: [PR #74](https://github.com/jeckodevelopment/jd_learn/pull/74), percorso editoriale/pref-light. 

1. Con amministratore autore/editore, creare bozza con un modulo senza lezioni: Programma segnala modulo vuoto e assenza di lezione obbligatoria.
2. Aggiungere lezione facoltativa: scompare il blocco modulo vuoto, resta quello sulla lezione obbligatoria.
3. Aggiungere lezione obbligatoria: scompaiono entrambi i blocchi strutturali; riepilogo/coperina assenti restano solo avvisi facoltativi.
4. Completare dettagli di contenuto e tentare la pubblicazione: controlli definitivi server rifiutano provider/contenuti non validi e conservano la bozza.
5. Ripetere la sola consultazione con ruolo che può leggere Programma ma non pubblicare. Liste separate e tradotte; il caricamento non cambia lo stato della bozza.

**Esito sito Joomla:** Non eseguito.

### PR #78 — preflight durata video mancante

Riferimenti: [PR #78](https://github.com/jeckodevelopment/jd_learn/pull/78), durata video/preflight.

1. Aprire bozza con lezione video senza durata rilevata/confermata: preflight mostra il blocco.
2. Registrare durata valida (1 secondo–8 ore) con flusso normale editor: blocco scompare dopo ricaricamento.
3. Tentare pubblicazione senza conferma: il server rifiuta e non modifica versione.
4. Verificare che l’apertura del preflight non contatti provider.

**Esito sito Joomla:** Non eseguito.

### PR #64 — accesso all’aula dopo il ritiro dal catalogo

Riferimenti: [PR #64](https://github.com/jeckodevelopment/jd_learn/pull/64), R-03.

1. Iscrivere uno studente, avviare una lezione testo e una video.
2. Ritirare la scheda e impostare la fine pubblicazione nel passato: scheda nascosta, nuovo studente non può avviare il corso né ricevere ammissione manuale.
3. Con lo studente già iscritto, usare Riprendi: aula, progressi e player continuano a funzionare anche dopo ricaricamento.
4. Revocare il livello Joomla o rendere indisponibile la categoria: l’aula viene bloccata.
5. Ripubblicare: corso torna visibile e progressi/versione dello studente restano invariati.

**Esito sito Joomla:** Non eseguito.

### PR #62 — Riprendi alla prima lezione obbligatoria incompleta

Riferimenti: [PR #62](https://github.com/jeckodevelopment/jd_learn/pull/62), R-05.

Precondizioni: corso pubblicato con almeno tre lezioni testo obbligatorie e una quarta facoltativa; iscrizione studente di prova.

1. Con iscrizione nuova, selezionare **Riprendi**: apre la prima lezione obbligatoria.
2. Completare le prime due; con ultima attività ancora valida, **Riprendi** riapre quella lezione.
3. Solo sull’istanza eliminabile, azzerare `last_lesson_id` nell’iscrizione di test: **Riprendi** apre la terza obbligatoria, non le già completate né la facoltativa.
4. Completare tutte le obbligatorie, azzerare nuovamente il riferimento e usare **Riprendi**: apre la prima lezione per revisione.
5. Controllare che le aperture non cambino stato, percentuale o progressi.

**Esito sito Joomla:** Non eseguito.

## PR senza comportamento Joomla

PR esclusivamente documentali o di infrastruttura senza cambiamento di comportamento utente: registrare “Non applicabile” con link alla PR e motivazione; non aggiungere casi manuali fittizi.

## Prove storiche ancora da recuperare

Le PR runtime senza procedura dettagliata nelle rispettive descrizioni non sono implicitamente collaudate. Per ciascuna, trascrivere i passi solo da requisiti/scenari già approvati e guide correnti; se la fonte non basta, segnalare la lacuna e chiedere una decisione di prodotto invece di inventare un test. Fino alla trascrizione ed esecuzione, mantenere lo stato “Non eseguito”.

## Manutenzione dopo ogni PR

Prima di unire una PR con comportamento runtime, riportarne qui le istruzioni manuali già presenti nella PR, collegare gli ID requisito/scenario esistenti e indicare precondizioni e risultati attesi. Se manca una procedura, indicare esplicitamente la fonte da cui ricavarla e tenere il test aperto. La descrizione di ogni PR deve includere `## Collaudo manuale Joomla`; per una PR solo documentale deve dichiarare che non cambia comportamento Joomla e che il test sito non è applicabile.
## Issue #154 — fuso iniziale del corso dalla configurazione globale Joomla

**Stato:** prova automatizzata superata in CI nel [run 42769089189](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189); il collaudo manuale Joomla è ancora da eseguire. PR [#167](https://github.com/jeckodevelopment/jd_learn/pull/167), main 0.41.44-dev.

1. In Joomla 6, impostare in **Configurazione globale → Server → Fuso orario** un valore riconoscibile, ad esempio `Europe/Rome`. Creare un nuovo corso come autore.
2. Aprire la scheda **Calendario**: il campo Fuso orario deve partire da `Europe/Rome`, anche se il fuso del profilo utente è diverso o non impostato.
3. Selezionare un fuso diverso e salvare il corso; riaprirlo e verificare che il valore scelto sia conservato.
4. Creare un altro corso e impostare date locali dopo aver selezionato un fuso diverso. Verificare che l’ora salvata/mostrata segua il fuso scelto e che le date esistenti dei corsi non vengano reinterpretate.

## Issue #157 — usabilità del programma didattico

**Stato:** PR [#169](https://github.com/jeckodevelopment/jd_learn/pull/169) integrata in 0.41.45-dev; CI standard 4/4 superata nel [run 42771935666](https://github.com/jeckodevelopment/jd_learn/actions/runs/42771935666); il collaudo manuale Joomla deve ancora essere eseguito.

1. Aprire il programma di un corso in bozza con un utente autorizzato alla modifica. Verificare che **Aggiungi modulo**, **Campi lezione**, **Regole didattiche** e **Anteprima corso** compaiano nella toolbar nativa Joomla, senza duplicati sotto il titolo, e che mantengano il corso/versione selezionati.
2. Aggiungere un modulo e aprire ciascuna delle altre destinazioni. Verificare che la navigazione arrivi alla funzione corretta, mantenendo il contesto del corso quando previsto.
3. Ripetere con un utente senza permesso di modifica e con un utente senza gestione campi: le azioni non autorizzate non devono comparire; provare anche l’accesso diretto alle relative URL.
4. Usare la toolbar e i form laterali solo da tastiera; verificare focus visibile e spaziatura leggibile a larghezza desktop e mobile, compreso il pannello Joomla compresso.

**Esito sito Joomla:** Non eseguito.


## AC-121 — docenti, associazioni e profili preesistenti

Riferimenti: AUT-01, CAT-02, CORE-01/04, LIFE-02; [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md).

Precondizioni: Joomla 6 di prova aggiornabile, un corso in bozza e uno pubblicato, un profilo docente nuovo e uno preesistente; account segreteria con permessi di gestione corsi/docenti.

1. Aprire JD Learn → Docenti e creare un profilo. Verificare che non sia richiesta né mostrata una categoria docente; il salvataggio deve usare il profilo senza assegnare gruppi Joomla o permessi all’account collegato.
2. Modificare un profilo preesistente e associarlo al corso in bozza. Salvare, riaprire il corso e verificare che l’associazione e l’ordine restino invariati.
3. Modificare l’associazione di un corso pubblicato. La scheda editoriale deve mostrare il dato corrente, mentre la versione didattica e lo snapshot già pubblicati restano invariati.
4. Ripetere l’aggiornamento dei dati di un profilo tecnico preesistente: categorie tecniche e riferimenti restano conservati e non si trasformano in un flusso di gestione per l’utente.

**Esito sito Joomla:** Non eseguito.

## AC-122 — campi e presentazione pubblica del docente

Riferimenti: CORE-10/04; [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md).

1. Da JD Learn → Campi aggiuntivi, selezionare il contesto Docenti (`com_jlms.teacher`). Creare un campo testo, email e un campo di tipo/gruppo disponibile nell’installazione; impostare lingua, accesso e permessi tramite i controlli Joomla.
2. Compilare presentazione, riferimenti pubblici e campi. Collegare al profilo un account Joomla con email privata distinta.
3. Aprire la pagina docente e la scheda pubblica di un corso associato come ospite e come utente autorizzato. Atteso: campi e gruppi appaiono solo secondo lingua, pubblicazione, livelli e ACL; l’email privata dell’account non viene esposta automaticamente.
4. Provare un valore vuoto e un campo non pubblicato: non devono apparire label o contenitori vuoti.

**Esito sito Joomla:** Non eseguito.

## AC-123 — modifica del profilo e revoca dei permessi

Riferimenti: CORE-04/10 e [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md). Preparare un docente collegato, un account segreteria e un secondo docente/account senza permessi.

1. Come docente collegato, aprire la pagina profilo e modificare i soli campi autorizzati. Verificare che account collegato, stato, lingua e livello di accesso non cambino.
2. Come segreteria, modificare gli stessi dati e un campo protetto. Atteso: l’operazione riesce solo con l’ACL corrispondente.
3. Come altro account, tentare l’URL di modifica del profilo e l’invio POST diretto. Atteso: accesso negato e dati invariati.
4. Revocare `core.edit.own` o `core.edit.value` e riprovare; il controllo deve applicare la revoca anche dopo una nuova richiesta. Ripetere con dati invalidi e una revisione obsoleta: il form conserva i dati inseriti per correggerli e non sovrascrive la revisione corrente.

**Esito sito Joomla:** Non eseguito.

## AC-124 — menu, SEF, lingue e corsi del docente

Riferimenti: CAT-02, CORE-13 e [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md). Preparare due profili e corsi con stato, lingua, categoria e livelli di accesso differenti.

1. Nel gestore menu Joomla creare voci **Pagina docente** e **Corsi di un docente**, selezionando il profilo previsto. Aprire le voci e verificare breadcrumbs, URL SEF, titolo e paginazione.
2. Ripetere per italiano e inglese, includendo un profilo/corso con lingua specifica e uno `*`. Atteso: profilo e corsi coerenti con il filtro lingua.
3. Cambiare manualmente l’ID nell’URL e aprire URL SEF dirette per profili non pubblicati o non accessibili. Atteso: nessun dettaglio o corso riservato viene esposto.
4. Verificare che un corso non visibile non compaia neppure nell’elenco del docente e che il profilo non riveli l’email privata dell’account collegato.

**Esito sito Joomla:** Non eseguito.

## AC-125 — upgrade e conservazione dei dati docente

Riferimenti: AUT-04/05, CORE-01/04/10/16, LIFE-02 ed EDGE-11. Usare un’istanza Joomla usa-e-getta con il pacchetto precedente e dati di prova preesistenti: corso pubblicato, iscrizione/progressi, profilo docente, categoria tecnica, associazioni, campi/valori e revisioni.

1. Registrare prima dell’upgrade gli ID e i valori di corso, profilo, categoria, associazioni, campo e versione didattica. Aggiornare il pacchetto seguendo la normale procedura Joomla.
2. Dopo l’upgrade confrontare ID e valori registrati; verificare che stato, livello, lingua, campi e associazioni restino validi e che lo storico didattico conservi il docente congelato.
3. Accedere come studente già iscritto e come nuovo utente: l’upgrade non deve migrare iscrizioni, azzerare progressi o assegnare gruppi/permessi. Le nuove modifiche editoriali non alterano retroattivamente le versioni assegnate.
4. Verificare che le categorie tecniche restino utilizzabili per compatibilità ma non diventino obbligatorie nell’authoring.

**Esito sito Joomla:** Non eseguito. Eseguire solo su installazione eliminabile; non usare dati di produzione.


## IMP-03 — preview video in sola lettura (0.41.46-dev)

Requisiti: AUT-02, AUT-03, STU-02, SCR-06–08; AC-20, AC-80, AC-86, AC-119–127. La procedura copre la riproduzione video e i confini read-only di questo incremento; non chiude la preview interattiva completa (documenti, audio e interazioni quiz restano non simulati).

1. Su Joomla 6 di prova, accedere come editor autorizzato e aprire **Programma → Anteprima editoriale** per una versione bozza e una pubblicata contenenti testo e video. Se disponibili, predisporre un video locale HTML5 e video YouTube/Vimeo/Cloudflare Stream.
2. Aprire una lezione testuale e poi ciascuna lezione video. Atteso: testo e navigazione sono visibili; il player video carica solo dopo l’apertura della lezione. Avviare, mettere in pausa e cercare in avanti/indietro. Per provider esterni, provare anche la policy bloccata, embed e consenso del sito: nessuna richiesta esterna deve partire prima che la policy consenta la riproduzione.
3. Prima e dopo la riproduzione, confrontare roster, iscrizioni, tentativi, sessioni e progresso della lezione/corso: nessun valore deve essere creato o cambiato. Nel player deve apparire un messaggio che dichiara che il progresso non viene salvato.
4. Provare un account senza permesso di modifica corso/materiali e poi revocare il permesso durante la sessione. Anteprima e richieste dirette POST del descrittore/GET del video devono essere negate; URL alterate con corso, versione, lezione o revisione non correlati non devono restituire media.
5. Aprire una lezione quiz. Atteso: l’attività è indicata come anteprima read-only non simulata; non viene creato alcun tentativo.

**Aggiornamento 0.41.77-dev (IMP-03):** l'anteprima include ora i materiali dell'ambito corso anche mentre si visualizzano lezioni di testo o video. I file devono essere pronti, attivi, nella finestra di disponibilità e non marcati come soli campioni pubblici. I materiali associati a una lezione restano limitati alle lezioni Documento/Audio.

6. Preparare un allegato corso privato pronto, un campione marcato come anteprima pubblica, un file futuro e una lezione di testo e una video. Aprire entrambe le lezioni nell'anteprima editoriale: il solo allegato corso disponibile per gli iscritti deve apparire in ciascuna vista; il campione pubblico e il file futuro non devono comparire. Usare il collegamento del materiale e verificare che il download protetto riesca; alterare corso/versione/lezione/allegato e verificare che non venga restituito un file.
7. Confrontare iscrizioni, tentativi e progressi prima e dopo l'apertura e il download: devono restare invariati.

**Stato build:** PR [#235](https://github.com/jeckodevelopment/jd_learn/pull/235) integrata nel commit `dbd277605a7eebd18d5d16515853a5f61a40d3f0`; CI standard 4/4 nel [run 43110571231](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231). Pacchetto [JD-Learn-0.41.77-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231/artifacts/13896755810), ZIP interno SHA-256 `22f41d76870fb2c5b39fa621d8d9d0e596ccf1a9c19886fe712aeac8d4159cd1`, wrapper SHA-256 `fcbdd65eac2f49d17a987d5510e39581dd445335e4014393f4a2db370d82f61f`, scadenza 25 novembre 2026.

**Esito sito Joomla per 0.41.77-dev:** Non eseguito.

**Esito sito Joomla:** Non eseguito.

## IMP-03 — preview editoriale di documenti e audio (0.41.47-dev)

Riferimenti: AUT-02/03, STU-02, MAT-01/02; AC-20, AC-27–31, AC-80, AC-86, AC-93, AC-97–103, AC-119–127. Eseguire su Joomla 6 eliminabile, con un editor autorizzato alla gestione del corso e dei materiali; predisporre una lezione Documento e una Audio nella bozza, PDF, DOCX/TXT, immagine e MP3/OGG in stato pronto, oltre a un allegato non disponibile (quarantena, non pubblicato o con data futura/scaduta). Conservare conteggi iniziali di iscrizioni, sessioni, tentativi e progressi.

1. Aprire **Programma → Anteprima editoriale** sulla lezione Documento. Atteso: elenco separato di materiali del corso e della lezione, con titolo, descrizione, tipo e dimensione; PDF, immagini e testo ammessi si aprono inline in una nuova scheda, gli altri file vengono scaricati. File in quarantena/non pronto, disabilitati, contrassegnati preview o fuori finestra non compaiono e non sono serviti.
2. Aprire la lezione Audio. Atteso: player HTML5 per MP3/OGG della lezione, con seek/ripresa HTTP Range; gli allegati rimanenti sono consultabili secondo il tipo file. Un file audio di formato diverso non deve essere riprodotto dall’anteprima.
3. Provare URL dirette con GET e HEAD per un allegato valido. Cambiare a turno course, version, lesson e attachment ID, includendo una lezione di un altro corso/versione e un attachment collegato a un’altra lezione. Atteso: nessun file viene restituito per relazioni non corrispondenti.
4. Ripetere con un account privo di gestione materiali e dopo la revoca del permesso nella sessione. Atteso: accesso negato anche sull’endpoint diretto. Verificare che l’endpoint non accetti POST o altri metodi.
5. Prima e dopo preview/download/riproduzione, confrontare iscrizioni, sessioni, tentativi e progresso: devono restare invariati. La lezione quiz deve continuare a mostrare solo il placeholder read-only, senza tentativi.

**Esito sito Joomla:** Non eseguito.


## IMP-04 — apertura protetta inline di PDF e immagini nell’aula (0.41.48-dev)

Riferimenti: LES-01, MAT-04/05, STU-02; AC-27–31, AC-43–44, AC-89, AC-95. Su Joomla 6 di prova predisporre un’iscrizione attiva e lezioni disponibili con PDF, JPEG/PNG/WebP, DOCX, CSV, ZIP e un file non pronto; conservare lo stato iniziale di progressi e iscrizioni.

1. In aula verificare che PDF e immagini mostrino **Apri nel browser** e **Scarica file**; DOCX, CSV e ZIP devono mostrare solo il download.
2. Aprire PDF e immagini: risposta inline in nuova scheda con `nosniff`, cache privata e senza contenuti eseguibili; provare anche Range su PDF. Il download alternativo deve continuare a funzionare.
3. Alterare attachment ID/enrollment ID e ripetere dopo revoca accesso, sospensione, chiusura finestra o blocco prerequisito della lezione. L’endpoint inline deve negare il file senza rivelare contenuto.
4. Richiedere l’endpoint inline per DOCX/CSV/ZIP o asset in quarantena: deve essere negato; il normale endpoint di download resta soggetto ai controlli esistenti. Metodi diversi da GET/HEAD devono essere rifiutati.
5. Confrontare progressi e iscrizione prima/dopo vista inline e download: nessuna apertura o scaricamento attribuisce completamento.

**Esito sito Joomla:** Non eseguito.

Run preliminare CI della PR #187: il job leggero è riuscito, ma i job PHP/database e Chromium sono risultati `skipped` sul primo evento ready. Il candidato non è considerato verificato finché un nuovo run non esegue la matrice runtime standard.


## IMP-06 — anteprima pubblica esplicita dei materiali corso (0.41.76-dev)

**Aggiornamento PR #233, integrata in 0.41.76-dev:** la pubblicazione della versione deve accettare gli allegati corso esplicitamente selezionati come anteprima, se pronti e validi; allegati lezione con flag preview devono restare rifiutati. La suite HTTP automatica ora copre pubblicazione e accesso pubblico/negato.

**Stato build:** PR [#233](https://github.com/jeckodevelopment/jd_learn/pull/233) integrata nel commit `5b7e7460dfa637b2847d6231f337b830950887df`; CI standard 4/4 nel [run 43106823340](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340). Pacchetto [JD-Learn-0.41.76-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340/artifacts/13894937008), ZIP interno 569117 byte, wrapper SHA-256 `2a50af30a6bfa349806c38325c0388771e983d9f56495186736375519e077665`; scadenza 2026-11-25T21:56:46Z. Accettazione manuale ancora non eseguita.

Riferimenti: MAT-02/04/08, AC-100/101/103. Usare Joomla 6 di prova eliminabile, un corso pubblicato visibile agli ospiti, una bozza aggiornabile e un account con permesso di gestione materiali. Preparare due allegati corso (PDF innocuo e TXT), un allegato lezione, un file in quarantena e un file con data futura.

1. Nella bozza caricare PDF e TXT come materiali del corso. Lasciare il PDF privato e selezionare **Disponibile come anteprima pubblica** solo per il TXT; salvare, riaprire e verificare che il valore resti selezionato. Provare l'opzione su un allegato lezione: il salvataggio deve rifiutare la combinazione e non deve renderlo pubblico.
2. Pubblicare la nuova versione. Da ospite aprire la scheda del corso: la regione **Materiali del corso** deve mostrare solo il TXT, con titolo, descrizione, nome file, MIME e dimensione. Il PDF privato, l'allegato lezione, il file in quarantena e quello fuori finestra non devono apparire.
3. Aprire il link del TXT e verificare download riuscito. Per PDF/immagine esplicitamente pubblicati verificare apertura inline; per gli altri formati il browser deve ricevere il download. La risposta deve includere cache privata/no-store e `X-Robots-Tag: noindex, nofollow`.
4. Provare URL dirette per un attachment privato, ID associato a un altro corso/versione, corso non visibile, materiale non pronto, disabilitato o fuori finestra; atteso 404/negato senza contenuto. Ripetere con POST e aspettarsi rifiuto del metodo.
5. Disabilitare il corso, ritirare la versione corrente oppure disabilitare/scadere il file; riprovare il medesimo link. L'accesso deve cessare senza dipendere dalla pagina già aperta. Collegare un asset esistente alla bozza: il nuovo collegamento deve restare privato finché l'editor non seleziona esplicitamente l'anteprima per l'ambito corso.

**Esito sito Joomla:** Non eseguito.

## IMP-06 — anteprima pubblica dei materiali di lezione testuale

Per una lezione testuale pubblica, l’anteprima del corso e l’opzione di anteprima del singolo allegato devono essere entrambe attive. La pagina della lezione mostra solo allegati selezionati, attivi, pronti e nella finestra di disponibilità. Provare GET/HEAD del link, POST rifiutato, URL diretta con allegato privato, lezione non selezionata, altro corso/versione e lezione video/documento: nessun materiale deve essere restituito. Disattivando il corso o la lezione, il link deve cessare di funzionare. I formati passivi ammessi possono essere visualizzati inline; gli altri vengono scaricati.

## IMP-06 — lifecycle e riuso dei materiali (0.41.64-dev)

Riferimenti: MAT-01–08, DAT-04, SCR-16, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. Su Joomla 6 di prova predisporre un corso con una bozza, almeno due lezioni e un editor con permesso di gestione materiali.

1. Caricare un allegato nella prima lezione e selezionare **Collega materiale esistente** verso materiali del corso. Atteso: il nuovo collegamento compare in entrambi gli ambiti e usa lo stesso file; nessuna seconda copia viene caricata.
2. Collegare lo stesso file a una seconda lezione, poi provare a collegarlo di nuovo allo stesso ambito. Atteso: i due ambiti validi lo rendono disponibile senza duplicati locali; la ripetizione viene rifiutata con un messaggio comprensibile.
3. Rimuovere il collegamento da un ambito. Atteso: il materiale resta utilizzabile nell'altro. Sostituire il file nel collegamento originale: gli altri collegamenti continuano a riferirsi al vecchio asset immutabile.
4. Ripetere con una versione pubblicata e con un editor privo del permesso materiali; nessuna modifica/riuso deve essere consentita. Verificare che il corso pubblicato e lo storico assegnato restino invariati.
5. Eseguire l'attività Scheduler di pulizia: il blob condiviso deve restare finché un collegamento lo referenzia e diventare eleggibile solo quando tutte le associazioni sono state rimosse e sono trascorse 24 ore.

PR [#217](https://github.com/jeckodevelopment/jd_learn/pull/217) integrata nel commit `abf82a4053918f823726c84a156d295652c7b72c`; CI standard 4/4 nel [run 42983654823](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823). La fixture Joomla eliminabile verifica il riuso tramite form amministrativo, il riferimento condiviso allo stesso asset, il rifiuto del secondo collegamento duplicato e il blocco sulle versioni pubblicate. Pacchetto della PR #217: [JD-Learn-0.41.64-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823/artifacts/13838677604). Il wrapper va estratto una volta. Collaudo manuale Joomla ancora da eseguire.

## IMP-06 — pulizia programmata dei materiali orfani (0.41.54-dev)

Riferimenti: MAT-02/03/05/06, DAT-04, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. La guida è in [MATERIALS.md](MATERIALS.md). Sul sito di prova verificare che il plugin Task - JD Learn sia abilitato.

1. Caricare un allegato valido nella bozza e sostituirlo con un nuovo file. Verificare che il contenuto precedente non venga sovrascritto; entrambi i file devono essere privati e serviti solo tramite i controlli Joomla.
2. Creare da Sistema → Attività pianificate un’attività “JD Learn: pulisci i file materiali orfani”, limite `100`, e avviarla manualmente. Atteso: record e file ancora usati da corsi/lezioni restano disponibili.
3. Verificare nel log dell’attività conteggio rimozioni, errori ed eventuale lavoro residuo; nessuna attività viene creata automaticamente dall’upgrade.

La CI standard 4/4 della PR [#202](https://github.com/jeckodevelopment/jd_learn/pull/202) nel [run 42886192087](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087) genera un asset fittizio non referenziato più vecchio di 24 ore e verifica la rimozione del record e del blob tramite la routine nativa Scheduler, mantenendo intatti gli allegati collegati. Pacchetto: [JD-Learn-0.41.54-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087/artifacts/13797460306).

**Esito sito Joomla:** Non eseguito.

## IMP-05 — pulizia opportunistica delle sessioni video (0.41.53-dev)

Riferimenti: VID-03/04, PBASE-01/04; AC-25, AC-27–31, AC-91, AC-95, AC-97–103. Su Joomla 6 di prova usare una lezione video disponibile, un’iscrizione autorizzata e uno dei quattro provider già configurati.

1. Aprire il video e avviare la riproduzione; verificare che si crei la sessione e che il salvataggio posizione funzioni.
2. Con una sessione precedente scaduta, aprire di nuovo il video: il player deve avviarsi e la riga scaduta deve essere rimossa; le sessioni attive e i progressi esistenti devono restare invariati.
3. Ripetere con provider esterno e HTML5 locale, con accesso autorizzato e poi revocato: il purge avviene solo dopo l’autorizzazione e la revoca continua a impedire l’avvio.
4. Verificare che nessuna sessione attiva venga eliminata e che una nuova sessione non attribuisca credito finché non arrivano intervalli validi.

La suite automatica copre una riga scaduta e la cancellazione; il limite massimo 250 è verificato nel codice. La pulizia è opportunistica, quindi l’eliminazione completa in assenza di nuovi avvii rimane un’attività periodica futura.

**CI:** PR [#198] integrata; standard 4/4 superata nel [run 42883180012](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012). [Pacchetto installabile JD-Learn-0.41.53-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012/artifacts/13794578948).

**Esito sito Joomla:** Non eseguito.
## IMP-07 — URL SEF con alias per corsi, categorie e percorsi (0.41.58-dev, PR #208 integrata)

PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208), milestone **1.0.0**, integrata con squash nel commit `6b92612254c060c1ef296c0a03661442871ad1cc`. CI standard 4/4 nel [run 42910392500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500). Pacchetto [JD-Learn-0.41.58-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500/artifacts/13806778546), wrapper SHA-256 `773fb7cd4e200df599ffd1b858cc5ef60d013c8823780b625024afbb4acec8cb`, ZIP Joomla SHA-256 `7a4d52cdbbd708b523d6d05f29f0b937fcf5094019a6c9e70ab443bd7a28ade3`, disponibile fino al 24 novembre 2026. Eseguire sul sito Joomla di prova e registrare qui l’esito; i percorsi numerici precedenti devono restare funzionanti.

1. Installare il pacchetto candidato su Joomla di prova e predisporre corsi, categorie e percorsi pubblicati con alias parlanti; includere due record con alias uguale in lingue/categorie diverse.
2. Attivare SEF e URL rewriting. Aprire catalogo, categoria, scheda corso e percorso; verificare che i collegamenti usino segmenti ID-alias (per esempio `42-alias-corso`), che ogni pagina corrisponda al record corretto e che non appaiano ID di un altro tipo di contenuto.
3. Aprire gli stessi record con i vecchi URL numerici; verificare che continuino a rispondere e che le pagine pubbliche espongano come canonical l’URL SEF con alias.
4. Cambiare lingua con associazioni valide e verificare che il link porti al record tradotto e al suo alias. Con un’associazione assente, verificare il fallback lingua già previsto.
5. Disattivare SEF e verificare URL query tradizionali, navigazione e menu nativi. Riattivare SEF e verificare breadcrumb, paginazione/filtri e link di ritorno.
6. Modificare un alias nel gestore Joomla; verificare che il nuovo alias generi il link successivo e che il vecchio segmento numerico continui a identificare il record.
## IMP-07 — vista elenco del catalogo (0.41.61-dev, PR #211 integrata)

Riferimenti: CAT-01, AC-41 e AC-85. Installare il candidato su Joomla 6 di prova; usare corsi pubblici e una categoria visibile.

1. Aprire il catalogo e verificare che la selezione Visualizzazione proponga Griglia ed Elenco, con etichette corrette in italiano e inglese.
2. Selezionare Elenco e applicare. Atteso: le schede sono disposte in righe, la scelta resta selezionata e l’URL conserva gli altri filtri attivi.
3. Combinare Elenco con ricerca, tag, difficoltà, durata e ordinamento; i risultati e la paginazione devono rimanere coerenti con i filtri.
4. Ripetere nella pagina di una categoria con corsi diretti e discendenti. La vista scelta non deve rimuovere il contesto categoria.
5. Restringere la finestra sotto 640 px: titolo, link e sommario devono rimanere leggibili senza overflow orizzontale. Verificare la navigazione da tastiera dei controlli.
6. Inviare `filter[layout]=unexpected`: deve essere usata la griglia predefinita. Verificare che nessun corso non autorizzato appaia in entrambe le viste.

**Esito sito Joomla:** Non eseguito.


## IMP-05 — consegna HTML5 locale compatibile con Apache/LiteSpeed (0.41.71-dev, PR #226)

**Stato:** PR [#226](https://github.com/jeckodevelopment/jd_learn/pull/226) integrata nel merge `1b889ee550a23700071488930b50fbcb0081ad74`. CI 4/4 nel [run 43079973855](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855); pacchetto [JD-Learn-0.41.71-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855/artifacts/13885130510), ZIP SHA-256 `3d9dcca6c943da364e6c6f83d6ac45b44f51a8a7a5d34f532da40150b9e21c8f`, wrapper `4ecf79e8fa6d64cc0171412dbc56b4ea26e7ee0f4a48544348fb4a6817e34d33`. Lo streaming protetto usa PHP a blocchi e non richiede Nginx, moduli aggiuntivi o regole server. I vecchi valori `x_accel` sono serviti via PHP. La capacità concorrente non è certificata senza benchmark.

**Comportamenti da testare in Joomla**

1. Aggiorna il componente dal pacchetto precedente su un sito Apache/LiteSpeed standard, abilita il plugin HTML5 e configura directory privata, ffprobe e streaming PHP. Non aggiungere direttive o moduli web server.
2. Carica un MP4 H.264 valido più grande di 64 MiB (entro il limite configurato e i limiti PHP); pubblica la lezione e avviala da un account iscritto. Verifica riproduzione, richieste Range e assenza di URL/percorso pubblico.
3. Prova guest, utente senza iscrizione, revoca del livello e sospensione dell’iscrizione: nessuno deve ricevere byte dopo il diniego. Verifica GET/HEAD, byte-range, Range non valido e intestazioni private/no-store.
4. Su un sito aggiornato con vecchio valore `x_accel`, verifica che l’anteprima e la fruizione continuino tramite PHP protetto e che non venga emesso `X-Accel-Redirect`.
5. Disabilita la consegna e verifica che pubblicazione/streaming siano bloccati. Registra versione Joomla/PHP, web server e dimensione file.
## STAB-04 — label compatta per i campi lezione (0.41.79-dev, PR #241 integrata)

**Stato:** PR [#241](https://github.com/jeckodevelopment/jd_learn/pull/241) integrata; CI 4/4 nel [run 43116473760](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760). Pacchetto [JD-Learn-0.41.79-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760/artifacts/13899451043), SHA-256 interno `4c54233651a85f33594fcde84d32f7f31d51bcc82d03f63fead3e189fa270843`, wrapper `a33d99e3742305adfe0964f8e878b8804092d4bf3caa7e125f91d5bfeef02eaa` (estrarre una volta; scade il 25 novembre 2026). Accettazione Joomla pendente.

**Issue:** [#173](https://github.com/jeckodevelopment/jd_learn/issues/173). La label lunga è sostituita con “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, sia nel catalogo componente sia in quello di sistema. Il link resta associato al contesto Joomla `com_jlms.lesson` e mantiene i controlli ACL.

**Comportamenti da testare in Joomla**

1. Installa il pacchetto 0.41.79-dev su un sito Joomla di prova. Apri JD Learn → Programma e verifica che il comando toolbar mostri “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, senza taglio o sovrapposizione a desktop e mobile.
2. Attiva il comando e verifica che apra **Campi aggiuntivi** filtrati per il contesto `com_jlms.lesson`.
3. Accedi con un utente senza `core.manage` su `com_fields`: il collegamento non deve apparire; un URL diretto non deve concedere accesso.
4. Verifica tastiera: il comando ha nome accessibile coerente con la label, riceve focus visibile e si attiva con Invio/Spazio.

**Esito sito Joomla:** Non eseguito.

---

## STAB-01 — traduzione del menu Percorsi formativi (0.41.78-dev), PR #238 integrata

**Stato:** PR [#238](https://github.com/jeckodevelopment/jd_learn/pull/238) integrata; CI 4/4 nel [run 43113528500](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500). Pacchetto [JD-Learn-0.41.78-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500/artifacts/13897377687), SHA-256 interno `19931c7606ae274ca12291e713bddc09a8c946bed3f83f7d3cad1b48db7b53f1`, wrapper `6a029d5283ef897d62869bc8fb88eaf193eb966bab3d61424a04951cae8c9ec5` (estrarre una volta; scadenza 25 novembre 2026). Accettazione manuale Joomla pendente.

**Issue:** [#150](https://github.com/jeckodevelopment/jd_learn/issues/150). La voce amministrativa usava una stringa di sistema non tradotta, anche se il titolo della vista Percorsi era già localizzato. La patch aggiunge `COM_JLMS_PATHWAYS` ai cataloghi di sistema italiano e inglese. L’ordine della dashboard e il riepilogo provider dell’issue #149 sono già implementati; verificarli nello stesso passaggio di accettazione.

**Comportamenti da testare in Joomla**

1. Installa il pacchetto 0.41.78-dev su un sito Joomla usa-e-getta; accedi come Super User con lingua amministrativa italiana e apri il menu JD Learn. La voce deve apparire come “Percorsi formativi”, senza chiavi `JD_COM_JLMS_*` visibili.
2. Apri la voce: deve portare alla gestione Percorsi e il titolo pagina deve essere tradotto.
3. Imposta inglese come lingua amministrativa e ripeti: la voce deve essere “Learning pathways”.
4. Verifica l’ordine dashboard: Corsi, Percorsi formativi, Categorie corsi, Categorie percorsi, Banca domande, Docenti, Campi aggiuntivi, Plugin video (solo le voci consentite dai permessi dell’utente).
5. Espandi lo stato provider: ciascuno dei quattro plugin deve essere indicato come configurato, non configurato, disattivato o mancante secondo il suo stato reale; non devono comparire segreti o impostazioni sensibili.

**Esito sito Joomla:** Non eseguito.

---


## STAB-05 — un solo accesso al Programma nell’editor corso (0.41.80-dev, issue #200)

**Stato:** PR [#244](https://github.com/jeckodevelopment/jd_learn/pull/244) integrata nel commit `ce3ee6f9d62c2483a3a826a34c0a0ba9b0d3bf62`. CI standard 4/4 nel [run 43121346817](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817); pacchetto [JD-Learn-0.41.80-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817/artifacts/13900597900), SHA-256 interno `5faefa65f5dce76823cb8dd7aa378ebe1801ad720bc740dc7ebe228bdb19132a`, wrapper `c82f72b0c1728bf7bda492c368e5581a3768133ad1ce06e6c9c3f9e50c65b2c7` (estrarre una volta; scade il 25 novembre 2026). Accettazione manuale Joomla non eseguita.

**Comportamenti da testare in Joomla**

1. Installare il pacchetto 0.41.80-dev su un sito Joomla di prova e accedere con un utente autorizzato a modificare corsi.
2. Aprire un corso esistente in **Componenti → JD Learn → Corsi → Modifica**. Verificare che il contenuto non mostri più il secondo pulsante “Programma didattico” sotto la toolbar e che la toolbar mostri una sola azione **Salva e apri programma**.
3. Aprire la scheda **Programma**: il riepilogo moduli e il comando devono restare disponibili. Usare la toolbar e verificare che salvataggio e apertura portino al programma dello stesso corso.
4. Aprire un nuovo corso: l’avviso che richiede il primo salvataggio deve restare visibile; dopo il salvataggio, la toolbar deve aprire il programma senza link duplicati.
5. Ripetere in italiano e inglese e con un utente in sola lettura; nessun comando di modifica non autorizzato deve comparire.

**Esito sito Joomla:** Non eseguito.


## STAB-05 — titoli non duplicati in Dashboard e Percorsi formativi (0.41.81-dev, issue #199)

**Stato:** PR [#247](https://github.com/jeckodevelopment/jd_learn/pull/247) integrata nel commit `a8734dd606cc579077312dfecd9cd66773934804`. CI standard 4/4 nel [run 43129077496](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496); pacchetto [JD-Learn-0.41.81-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496/artifacts/13904467823), SHA-256 interno `3ef211e6ac7713d381fb9fabb106ee40881eca72a9b3bbff5465fd300bdcc573`, wrapper `1924e7a641dbb539e3b5833d6b57a737c2389edda2645b8c6d6688e4699df3ee` (estrarre una volta; scade il 26 novembre 2026). Accettazione manuale Joomla non eseguita.

**Comportamenti da testare in Joomla**

1. Installare JD Learn 0.41.81-dev su un sito Joomla di prova e accedere con un utente autorizzato a `core.manage` su JD Learn.
2. Aprire **Componenti → JD Learn**. Verificare che “JD Learn” compaia una sola volta come titolo, nella barra blu, e che introduzione, scorciatoie operative e stato dei quattro provider restino visibili.
3. Aprire **Percorsi formativi** dal menu laterale o dalla dashboard. Verificare che il titolo compaia una sola volta nella barra blu, senza intestazione ripetuta sopra la tabella; tabella, intestazioni colonne e paginazione restano visibili.
4. Usare uno screen reader o l’albero accessibilità del browser: il titolo Joomla deve rimanere un heading principale; la tabella Percorsi deve conservare una caption accessibile.
5. Ripetere in italiano e inglese e con un utente senza `jlms.pathways.manage`: la vista percorsi e il relativo collegamento devono continuare a essere negati/nascosti.

**Esito sito Joomla:** Non eseguito.


## IMP-09 — Verifica FTPS reale (12 novembre 2026)

**Evidenza automatica ambiente remoto:** [GitHub Actions #43131327572](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131327572), branch `main`, commit `dae0751b6828f3dcd6bc3b9a20fdd72e2a94c621`, **SUCCESS**. Il log contiene `FTPS LIVE PROBE OK`: handshake FTPS esplicito con verifica certificato/pin configurato, login, `PROT P`, upload, lettura di ritorno, rename-overwrite di due file usa-e-getta e cancellazione. `jdlearn.xml` **non modificato**. I test offline risultano 5/5 + 3/3 + 7/7.

**Limite evidenza:** il workflow non esegue pubblicazione di una versione reale, non verifica la raggiungibilità HTTP del feed e non collauda l'aggiornamento Joomla end-to-end (IMP-10). Conferma indipendente dell'impronta del certificato privato con Host.it ancora raccomandata. **Esito collaudo Joomla:** Non eseguito.


## REL-01–03 — Dry-run del rilascio 1.0.0 (12 novembre 2026)

**Esito automatico:** [GitHub Actions 43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497), commit `67505908b2a7729429f345a420b534f13387070a`, **SUCCESS**. Generati `pkg_jlms-1.0.0.zip` (569587 byte; SHA-256 `8fd033a56ca4a424d6d754d1f9e13dfc3e78f3b40d64e49f7ad8b01b178b41df`), `jdlearn.xml` e `RELEASE_SHA256SUMS.txt`; artefatto [13906260145](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497/artifacts/13906260145). Job produzione **SKIPPED**. Feed XML generato soltanto localmente: il suo URL di download della v1.0.0 non è ancora disponibile pubblicamente.

**Comportamenti ancora da testare in Joomla (IMP-10):** installazione pulita del candidato su un sito di prova; upgrade da versione precedente mantenendo iscrizioni e progressi; compatibilità con Apache/LiteSpeed standard senza configurazioni Nginx; verifiche finali del catalogo, corsi, lezioni, player ed enrollment; updater e feed pubblico solo dopo autorizzazione esplicita al rilascio. **Esito collaudo reale Joomla:** Non eseguito. Non pubblicare una release stabile sulla base del solo dry-run.


### IMP-10 — Conferma manuale installazione candidato 1.0.0 (12 novembre 2026)

Il richiedente ha confermato in chat di aver **scaricato e installato il componente** dal candidato 1.0.0 generato dal workflow [43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497) e che il componente **funziona** sul sito Joomla di prova. Evidenza **dichiarata dall'utente**, non accompagnata da screenshot o verbale di casi dettagliati. Considerare **superato il solo smoke test manuale di installazione e avvio generale**.

**Non ancora confermati dall'utente:** aggiornamento da precedente versione con dati esistenti; preservazione corsi, iscrizioni e progressi; verifica approfondita dei provider/player, autorizzazioni e delle funzionalità operative; feed pubblico e aggiornamento automatico Joomla. **IMP-10 resta parziale**, nessun rilascio pubblico autorizzato. Il valore globale documentato resta 78% finché il criterio di misurazione non viene aggiornato sulla base di evidenze di tutti i requisiti.


## IMP-05 — capacità minime per il tracciamento video (0.51.0-dev, PR #278)

Riferimenti: PRO-05, VID-03; AC-05, AC-27, AC-30, AC-44, AC-91. PR [#278](https://github.com/jeckodevelopment/jd_learn/pull/278), integrata nel commit `62af5dbcbc6bd2300c67bc149c04bccaa0203aec`; CI standard 4/4 nel [run 43246533928](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928). Pacchetto [0.51.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928/artifacts/13957416321), SHA-256 `b6b01e432ddd660c32f25dfa4f611bfab069edcfa92977349f2ef16dbff0fd2f`; digest wrapper `2da0bd7ce7d03b58227ab24b4e7c84060143d41d5841aa3b54449aaa06a707bc`, scadenza 26 novembre 2026. Il controllo richiede posizione, durata e seek per il corso in apprendimento tracciato; velocità e sottotitoli/caption restano opzionali. Il server ripete la verifica sulle capabilities del descrittore prima di avviare o rinnovare il tracciamento.

**Comportamenti da testare in Joomla**

1. Installare il pacchetto candidato 0.51.0-dev in Joomla 6 di prova e configurare, uno alla volta, YouTube, Vimeo, Cloudflare Stream e HTML5 con sorgenti valide.
2. Per ciascun provider, creare una lezione video richiesta in un corso in modalità apprendimento, verificare la durata, pubblicare il corso e avviare la lezione. Verificare che il player si apra e che posizione, durata e seek siano disponibili; completare una parte del video e controllare che la copertura venga registrata.
3. Provare un corso in modalità consultazione con una lezione video: la riproduzione deve restare disponibile senza sessione né scritture di progresso.
4. In un ambiente di test con adapter che dichiara o restituisce capabilities incomplete, verificare che la pubblicazione oppure l’avvio del tracciamento venga rifiutato e che nessun progresso sia attribuito. L’assenza di velocità o caption, da sola, non deve bloccare la pubblicazione.

**Esito automatico locale:** build pacchetto e check strutturali superati; test player JavaScript superati. CI standard 4/4, inclusi test PHP e integrazione Joomla; il collaudo manuale live resta pendente. **Esito sito Joomla:** Non eseguito.


## IMP-04 — ripresa video tra sessioni concorrenti (0.52.0-dev, PR #280)

Requisiti: PRO-06, STU-03; AC-07, AC-37, AC-81, AC-91. PR [#280](https://github.com/jeckodevelopment/jd_learn/pull/280), integrata nel commit `dcc49eb0e09b0ba6f2424e6eaaa973b837b3e4f4`; CI standard 4/4 nel [run 43250267681](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681). Pacchetto [0.52.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681/artifacts/13958934116), SHA-256 `f5c0986ae59361de77c67ebae9ed9d91583b12f38e835a4a12a9cedfebfb9216`; wrapper `3f528639d38a2c61d39fc5713f6750f7dfadb5bf5c6220d810174cddaf8b89e9`, scadenza 26 novembre 2026. Una sessione video avviata da una posizione obsoleta può continuare ad aggiungere copertura, ma non deve sovrascrivere la posizione più recente né riacquisire il diritto di farlo nei batch successivi.

**Comportamenti da testare in Joomla**

1. Accedere con lo stesso account studente da due browser o dispositivi e avviare la stessa lezione video in entrambi.
2. Nel browser A avanzare fino a un punto riconoscibile e attendere il salvataggio; annotare la posizione confermata.
3. Lasciare aperto il browser B dalla posizione precedente e inviare almeno due salvataggi successivi. La copertura può unirsi, ma la posizione di ripresa non deve retrocedere o sostituire quella più recente.
4. Ricaricare la lezione nel browser B o aprirla in una nuova sessione: il player deve riprendere dalla posizione più recente confermata. Il completamento e la percentuale devono rimanere idempotenti.
5. Ripetere con due sessioni sulla stessa lezione e verificare che chiudere una sessione non chiuda né invalidi l’altra.

La regressione automatica d’integrazione in `tests/joomla-video.php` copre due batch consecutivi da una sessione obsoleta ed è passata nella CI standard 4/4. Accettazione live Joomla: Non eseguita.

## IMP-04 — replay del batch video obsoleto (PR #282, 0.53.0-dev)

**Stato automatico:** superato nella CI standard 4/4 [run 43252689828](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828), incluso test Joomla/PHP di replay idempotente. Merge `66a59d0a5e4e60943b0d1487ddce4c95c64f97b0`. **Accettazione manuale Joomla: da eseguire.** Pacchetto [JD-Learn-0.53.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828/artifacts/13959653251), SHA-256 ZIP `7898ba9c5dc340aaec1b9c260ef729bc4314cde4cd31bc44c2411efacca0616e`; wrapper `a2dc70a32891b12c378f0920c75c5fa4b9da3c47e46fc0efd442b898051235fa`, scade il 26 novembre 2026.

1. Sul sito Joomla di prova, aprire la stessa iscrizione video in due sessioni/browser: A e B.
2. Avanzare e salvare da A un punto di ripresa più recente. Da B inviare un batch di progresso basato sulla revisione precedente; verificare che la risposta segnali il conflitto e il resume salvato resti quello più recente.
3. Ripetere da B la stessa identica richiesta. Atteso: risposta idempotente con `replay=true` e `resume_conflict=true`; nessuna seconda applicazione di copertura, completamento o posizione di ripresa.
4. Inviare da B un ulteriore batch con la revisione obsoleta. Atteso: conflitto ancora segnalato e nessuna regressione del resume.
5. Verificare che il progresso valido già registrato da entrambe le sessioni venga mantenuto e che il corso non risulti completato due volte.

## IMP-04 / AUT-03 — avviso durata video in sola consultazione (0.54.0-dev)

Verificare su Joomla di prova il riepilogo **Verifica prima della pubblicazione** in una versione bozza con modalità **Sola consultazione** e una lezione video senza durata rilevata/confermata. Atteso: avviso `COM_JLMS_READINESS_VIDEO_DURATION_UNCONFIRMED`, nessun blocco per la sola durata e possibilità di pubblicare se gli altri controlli passano. Aggiungere poi una durata confermata valida e verificare che l’avviso scompaia. Ripetere in modalità **Apprendimento**: l’assenza di durata resta un blocco `COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED` e non viene duplicata come avviso.


## IMP-09-CERT — attestati PDF essenziali (CERT-A/B integrate)

**Comportamenti da testare in Joomla (da eseguire dopo le PR runtime):**

1. Disabilitare e abilitare il rilascio attestati a livello globale e per corso; completare un corso abilitato e uno disabilitato, verificando l'emissione solo dove autorizzata.
2. Simulare corso incompleto, anteprima editoriale e sola consultazione: nessun attestato deve essere emesso.
3. Ripetere evento e richieste concorrenti di completamento: il codice identificativo e il numero attestati non devono duplicarsi.
4. Personalizzare logo/intestazione e generare PDF con nomi e titoli lunghi, caratteri accentati, lingua italiana e inglese. Verificare studente, corso, data, codice e leggibilità.
5. Studente A scarica il proprio attestato; studente B e visitatore anonimo non possono scaricarlo nemmeno conoscendo l'URL. Verificare risposta e assenza indicizzazione di PDF privati.
6. Verificare elenco e download dal backend per amministratore autorizzato; negare accessi non consentiti da ACL a dettagli e dati personali.
7. Eseguire upgrade e ripristino dati: attestati e codici restano invariati, senza rigenerazioni accidentali.
8. Verificare mail di disponibilità tramite modello Joomla con invio, errore SMTP, retry e deduplica; l'attestato persiste quando la posta non funziona.

**Stato:** prove pianificate, nessuna accettazione manuale registrata. Vedere [CERTIFICATES_1.0.0.md](CERTIFICATES_1.0.0.md).


## IMP-09-MAIL-B — sospensione e riattivazione (PR #297, 0.56.3-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto; in **Sistema → Modelli email** verificare i template `com_jlms.enrollment.suspended` e `com_jlms.enrollment.reactivated`. Personalizzare testo e traduzione, aggiornare e verificare che restino invariati.
2. Configurare SMTP di prova e attività pianificata JD Learn. Sospendere un’iscrizione attiva: deve arrivare un solo messaggio di sospensione; rieseguire l’attività senza nuove transizioni e verificare che non partano duplicati.
3. Riattivare l’iscrizione: deve arrivare soltanto il template di riattivazione. Verificare che progressi e storico quiz restino invariati.
4. Verificare preferenze globali e override corso separatamente per sospensione e riattivazione: globale disabilitato con corso ereditato (nessun invio), override abilita (invio), override disabilita con globale abilitato (nessun invio).
5. Verificare la gestione di errore di accodamento: transazione di stato/audit/coda coerente e rollback; ripetere la stessa transizione idempotente senza duplicati.

PR [#297](https://github.com/jeckodevelopment/jd_learn/pull/297) integrata, CI standard 4/4 nel [run 43307181190](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190). Pacchetto [JD-Learn-0.56.3-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190/artifacts/13983759363), SHA-256 ZIP `8c3d5e0de6935f67cf93e1b7caf3a27e7d10d61d26c20450f5d9ace1cc6cdee0`. La suite automatizzata non sostituisce le prove manuali Joomla/SMTP sopra: registrare qui ambiente ed esito.


## IMP-09-MAIL-A — conferma di iscrizione amministrativa (candidata 0.56.4-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto candidato su Joomla di prova con SMTP di test e attività pianificata JD Learn attiva; verificare che il template `com_jlms.enrollment.confirmed` esista in Sistema → Modelli email e che le personalizzazioni esistenti siano preservate dall’upgrade.
2. Come amministratore autorizzato, iscrivere manualmente uno studente valido a un corso pubblicato in cui abbia accesso ai contenuti. Atteso: l’iscrizione viene creata e, se le preferenze globali/corso sono abilitate, entra in coda una sola email di conferma con nome, corso e URL aula corretti.
3. Ripetere la stessa richiesta idempotente o ricaricare la schermata dopo il salvataggio. Atteso: un’unica iscrizione e nessun messaggio duplicato.
4. Disabilitare l’evento globalmente; verificare nessun messaggio. Riabilitarlo e impostare l’override del corso su disabilitato; verificare ancora nessun messaggio. Con globale disabilitato e override esplicitamente abilitato, verificare un solo messaggio.
5. Eseguire un preassegnamento senza livello di accesso al contenuto. Atteso: l’assegnazione resta distinta e lo studente non riceve una falsa conferma di accesso. Se l’utente possiede già accesso corrente e l’evento è abilitato, la conferma può essere accodata.
6. Simulare errore SMTP: l’iscrizione deve restare valida e la coda deve applicare retry secondo le regole esistenti; controllare lingua e dati email nel log, senza esporre dati personali eccedenti.

**Esito sito Joomla:** Non eseguito.
# CERT-A — Emissione persistente degli attestati (0.56.5-dev)

La CI deve verificare installazione/upgrade dello schema, preferenza disabilitata per default, override per corso e un solo record attestato dopo il completamento reale nelle modalità testo/documento/audio, quiz (inclusa revisione), video e completamento idempotente. Verificare che consultazione, corso non completato e corso senza attestati abilitati non creino record; che il template e lo snapshot persistano i valori al momento del completamento.

**Collaudo manuale Joomla da eseguire:** abilitare gli attestati globalmente e poi sovrascrivere la preferenza in due corsi; completare un corso Apprendimento e verificare nel database di prova una sola riga per iscrizione/versione con nome, titolo/versione e data corretti; ripetere l'ultima richiesta e verificare che il record non cambi; completare un corso in sola consultazione e verificare che non venga emesso nulla. Il PDF, l'area studente e la vista amministrativa sono fuori da CERT-A e verranno collaudati nelle PR successive.


# CERT-B — PDF personalizzabile e download protetto (PR #302 integrata; 0.56.6-dev)

**Prerequisiti:** installare la candidata su Joomla di prova con storage privato gestito automaticamente o con cartella esterna privata valida; predisporre un PNG o JPEG locale nella cartella media consentita; attivare attestati globali e per il corso di test; usare uno studente A completabile, uno studente B e un account anonimo.

**Collaudo manuale Joomla da eseguire**

1. In Configurazione componente → Attestati impostare intestazione, sottotitolo, piè pagina, colore e logo JPEG locale. Completare il corso come studente A. Atteso: nel pannello studente compare un attestato e il PDF scaricato riporta nome, titolo del corso, data, codice, testi e logo configurati.
2. Aprire il PDF in italiano e con account inglese; verificare testo e date/localizzazione, nomi accentati, nomi lunghi e titolo corso lungo. Atteso: una pagina A4 orizzontale leggibile, contenuti non interpretati come markup e file PDF valido.
3. Configurare un logo PNG (anche trasparente) e generare il PDF. Atteso: PDF scaricabile con logo; un URL remoto o un SVG devono essere rifiutati senza esporre file e senza rompere l’aula. GIF/WebP/AVIF richiedono supporto GD appropriato.
4. Accedere all’URL di download come A, come B, anonimo e con un enrollment_id inesistente. Atteso: A scarica il proprio PDF; B, anonimo e ID inesistente non ricevono il documento di A. Il percorso non è enumerabile tramite seriale o URL asset.
5. Verificare intestazioni `Content-Type: application/pdf`, `Cache-Control: private, no-store`, `X-Robots-Tag: noindex, nofollow`; ripetere con HEAD. Atteso: HEAD non invia il corpo.
6. Ripetere il download e confrontare seriale/contenuti: lo stesso attestato resta identico. Controllare che il blob risieda nello storage protetto gestito cifrato oppure nello storage privato esterno già configurato, con permessi restrittivi e che l’upgrade non lo elimini.

**Esito sito Joomla:** Non eseguito. Questa procedura non copre ancora vista amministrativa, audit né backup/ripristino, previsti nelle PR successive/IMP-10.


**Verifica automatica PR #302:** CI standard 4/4 superata nel [run 1023](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842); il test PDF e la lint PHP sono passati. L’accettazione manuale descritta sopra resta pendente. Pacchetto [JD-Learn-0.56.6-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842/artifacts/14029108411), ZIP interno SHA-256 `73a8cb6de3e4c2f636b2a2b0702799864e3644dcc3c463913e628a4c4c4056d1`, wrapper SHA-256 `313e4a2b18adc2d1f431114eed9b19ff593ec5b154dc0784481e094546a80a92`.


# CERT-C — gestione amministrativa attestati (PR #304 integrata; 0.56.7-dev)

**Prerequisiti:** installare/aggiornare il pacchetto candidato su Joomla di prova; abilitare Attestati; completare un corso come studente; creare due account amministrativi, uno con `core.manage` e `jlms.certificates.manage`, uno senza il permesso certificati.

1. Da JD Learn > Dashboard, l’account autorizzato vede la scorciatoia **Attestati**; l’account senza il permesso non la vede. Aprire elenco e dettaglio del certificato e verificare studente, corso, codice, data e dati dello snapshot.
2. Cercare per nome studente, titolo corso e codice; applicare separatamente i filtri Disponibile, In attesa del PDF e Revocato; verificare risultati, paginazione e stato.
3. Con l’account senza `jlms.certificates.manage`, aprire direttamente elenco, dettaglio e URL `task=certificate.download`: ogni richiesta deve essere negata senza esporre dati o PDF. Provare anche da utente non autenticato.
4. Con l’account autorizzato scaricare il PDF dal dettaglio: il file deve corrispondere al codice e ai dati congelati del certificato. Se è presente un certificato già revocato, verificare il filtro/stato e il diniego download; la revoca non è introdotta da CERT-C.
5. Nel dettaglio verificare gli eventi di consultazione/download con ID amministratore e data UTC. Confermare che l’audit non includa email o altri dati personali non necessari.
6. Aggiornare da 0.56.6-dev e verificare che certificati e asset esistenti restino leggibili e non vengano rigenerati o modificati.

**Verifica automatica PR #304:** CI standard 4/4 superata nel [run 1031](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364); la suite ha superato database, installazione/upgrade e smoke test Joomla su MySQL 8.4 e MariaDB 10.6. Gli scenari manuali Joomla restano pendenti. Pacchetto [JD-Learn-0.56.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364/artifacts/14034621388), SHA-256 ZIP interno `28c462f2eae0b795cf02574408ac9bfa830e8e4a95bd5ad634bc011700e14bc4`, wrapper `f0467b1a4e96ab784283090e48377d400fbb69f944579ffd444934cdc41175e2`.


# CERT-D — notifica disponibilità attestato (PR #307 integrata; 0.56.8-dev)

1. Aggiornare il componente su Joomla di prova, abilitare attestati e notifiche email globali; verificare che il template `com_jlms.certificate.available` compaia in **Sistema → Modelli email** con oggetto, HTML/testo e tag `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Personalizzare il template e verificare che l’upgrade successivo lo preservi.
2. Completare un corso idoneo con utenti che hanno lingua profilo IT ed EN; verificare una sola riga in outbox per ciascun documento e template lingua corretto. Eseguire il task `JD Learn: send queued emails` e verificare contenuti, link autenticato alla dashboard e accesso al download.
3. Ripetere il completamento/replay: non devono essere create ulteriori notifiche. Disabilitare la preferenza globale oppure l’override per corso e verificare che l’email non venga accodata.
4. Provare un errore temporaneo SMTP: il completamento e l’attestato devono restare validi, la coda deve pianificare il retry; dopo il ripristino eseguire il task e verificare la consegna.
5. Eseguire CI e smoke test Joomla automatici; l’accettazione manuale sul sito di prova resta a carico del collaudo di progetto.


## IMP-09-MAIL-C / PR #313 — comunicazione operativa per corso (0.56.9-dev)

**PR #313 integrata; CI 4/4 superata nel run [43445911912](https://github.com/jeckodevelopment/jd_learn/actions/runs/43445911912). Collaudo manuale Joomla/SMTP pendente; la CI non equivale all'accettazione.** Preparare Joomla 6, mailer di prova, Scheduler JD Learn attivo, corso con almeno tre iscritti attivi, uno sospeso e un docente assegnato.

1. Dal roster aprire “Comunica agli iscritti”, modificare oggetto/testo e selezionare due iscritti. Verificare anteprima, conteggio e destinatari; email e indirizzi non devono apparire nell'interfaccia.
2. Inviare il test: deve riceverlo solo l'operatore autenticato; gli iscritti non ricevono il test.
3. Confermare e lanciare il task Scheduler più volte. Solo i selezionati ancora attivi e non bloccati ricevono un messaggio ciascuno; verificare corpo HTML e testo, lingua Joomla e template com_jlms.course.message in Sistema → Modelli email.
4. Ripetere/replay della conferma e inviare POST alterati con course/enrollment ID o hash vecchi: nessun duplicato, l'alterazione è respinta e la preview diventa da aggiornare se è cambiato il roster.
5. Prima dell'elaborazione revocare l'ACL del docente assegnato e provare con un docente non assegnato. Job revocato interrotto; roster e pagina comunicazione negati al docente non assegnato.
6. Provare switch globale e override corso (eredita/abilita/disabilita), mailer non disponibile e ripresa del Scheduler. Verificare log azioni, conteggi, assenza del testo e degli indirizzi nei dati di audit/job e pulizia del payload a esito terminale.


## MAIL-11.1 / PR #315 — template Joomla annuncio nuovo corso (0.56.10-dev)

PR integrata; CI 4/4 superata nel [run 43450891869](https://github.com/jeckodevelopment/jd_learn/actions/runs/43450891869). **Nessun invio è previsto da questa PR.** Collaudo manuale Joomla pendente. Sul Joomla 6 di prova:

1. Installare o aggiornare JD Learn, aprire **Sistema → Modelli email** e cercare `com_jlms.course.published`.
2. Verificare tag disponibili: `NAME`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `COURSE_IMAGE_HTML`, `COURSE_INTRO_TEXT`, `COURSE_INTRO_HTML`, `COURSE_LESSONS_TEXT`, `COURSE_LESSONS_HTML`, `COURSE_URL`, `SITE_NAME`, `SITE_NAME_HTML`, `UNSUBSCRIBE_URL`.
3. Verificare testo e HTML: immagine opzionale, titolo, introduzione, lezioni pubbliche ordinate, CTA e revoca; plain text leggibile con URL e lezioni.
4. Tradurre IT/EN con la funzione Joomla, personalizzare i corpi e aggiornare il componente: le personalizzazioni devono restare intatte.
5. Installazione pulita deve creare un solo template; aggiornamento non invia email e non crea destinatari né campagne.

MAIL-11.2/11.3 sono parziali nella PR #317; MAIL-11.4/11.5 restano da implementare e bloccano qualsiasi invio di annunci.



### PR #319 integrata — 0.56.12-dev: opt-in durante iscrizione automatica

1. Configurare il mailer Joomla verso una casella controllata e pubblicare un corso con iscrizione automatica.
2. Aprire il corso da utente autenticato: la casella annunci deve essere facoltativa e non selezionata per default.
3. Avviare il corso senza selezionarla: l'iscrizione riesce e non viene inviata una richiesta di consenso.
4. Con un utente nuovo, selezionarla e avviare il corso: l'iscrizione riesce e arriva soltanto l'email double opt-in; nessun annuncio viene accodato.
5. Ripetere il POST mentre lo stato è pending: non deve partire una seconda email di conferma. Confermare il token e ripetere: non deve essere riaperta la richiesta.
6. Simulare un errore SMTP: l'iscrizione resta valida, compare un avviso e i log non contengono email, nome o token.
7. Verificare label IT/EN e uso da tastiera. Registrazione Joomla, revoca anonima e campagna amministrativa restano fuori da questa tranche. Questi scenari sono ancora da eseguire sul sito Joomla; CI run 43469236700 ha superato i controlli automatici standard 4/4.

## IMP-09-MAIL-D — MAIL-11.2/11.3 consenso e double opt-in (PR #317, 0.56.11-dev)

PR [#317](https://github.com/jeckodevelopment/jd_learn/pull/317) integrata nel merge `6c44bc4e852bf1d693b53646b5deee6c83153629`; CI standard 4/4 nel [run 43457091398](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398). Pacchetto installabile [JD-Learn-0.56.11-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398/artifacts/14053993647), SHA-256 interno `3e6b918c157b320984064a7fecc0b392ef5bc049d164a3e088b5a835e6732420`; accettazione manuale Joomla/SMTP pendente.

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto su Joomla 6 di prova con mailer SMTP di test e un account studente valido. Aprire l’Area personale: la scelta novità corsi deve essere separata dalle mail operative e inizialmente non selezionata.
2. Senza selezionare la casella, inviare direttamente il POST di richiesta: non deve creare consenso né inviare email. Selezionare la casella e inviare: deve arrivare soltanto la mail di conferma, non un annuncio; l’Area personale mostra lo stato in attesa.
3. Verificare in database che la richiesta abbia stato pending, origine, versione informativa e data, e che non sia salvato il token in chiaro. Usare il collegamento entro 24 ore: lo stato passa a confirmed una sola volta. Riaprire il link o alterarlo: nessuna seconda transizione.
4. Confermare di nuovo con indirizzo email errato o con mailer in errore: lo stato non deve diventare confirmed; verificare messaggio generico e log senza token/email.
5. Dall’Area personale revocare l’adesione: lo stato passa a revoked e l’eventuale token pendente viene eliminato. Riaprire l’Area personale e verificare che la preferenza non risulti attiva; gli annunci restano non inviati.
6. Esportare i dati personali dal plugin Privacy Joomla: verificare origine, stato, versione informativa e timestamp senza hash/token. Eseguire la rimozione Privacy su un utente di prova: la riga di consenso deve essere rimossa.
7. Verificare IT/EN, navigazione tastiera/label checkbox e che l’upgrade conservi le personalizzazioni dei modelli `com_jlms.course.published` e `com_jlms.course.consent`.

Il collaudo manuale/SMTP resta da registrare. La revoca anonima tramite link è coperta dal caso IMP-09-MAIL-D dedicato qui sotto. Registrazione Joomla, trigger e invio degli annunci restano fuori da questa tranche.


## IMP-09-MAIL-D — Revoca anonima tramite link email (MAIL-11.3)

La pagina di revoca non modifica preferenze in GET: i client email possono aprire o pre-caricare il link. La revoca avviene solo dopo conferma POST protetta da token CSRF. Il bearer token casuale è memorizzato solo come SHA-256, scade dopo 30 giorni ed è legato al timestamp esatto del consenso confermato; una revoca invalida tutti i link emessi per quel consenso. La pagina non mostra dati dell’utente e imposta no-store/no-referrer/noindex.

**Comportamenti da testare sul sito Joomla di prova**

1. Con utente di prova che ha confermato l’opt-in, generare un’email di annuncio di prova e aprire il link di revoca senza inviare il modulo: la preferenza deve restare attiva, anche dopo il prefetch automatico del link.
2. Confermare la revoca dal modulo: verificare messaggio generico, preferenza revocata, redirect senza token nell’URL e assenza di altre email annuncio per quell’utente.
3. Ripetere con link scaduto, già usato e link di un utente senza consenso attivo: l’esito deve essere generico, senza rivelare indirizzo o stato; nessun consenso nuovo deve essere modificato.
4. Verificare POST privo di token CSRF respinto; tentare riutilizzo del link e verificare che non produca ulteriori modifiche.
5. Verificare intestazioni `Cache-Control: private, no-store`, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex, nofollow`, lingua italiana/inglese e layout mobile.
6. Rimuovere l’utente o il suo consenso tramite Privacy Joomla e verificare che anche i token di revoca siano eliminati.

La UI e il servizio sono coperti dalla CI; collaudo reale Joomla/email resta da registrare. Questa tranche non invia annunci: il generatore di token sarà collegato al flusso amministrativo in una PR successiva.

## MAIL-11.2 — opt-in durante la registrazione Joomla (0.56.14-dev)

Eseguire sul sito di prova con un indirizzo email controllato e il mailer Joomla collegato alla casella di test. Il plugin utente **JD Learn - Consenso email nuovi corsi** deve essere attivo dopo una nuova installazione.

1. Aprire la registrazione Joomla in italiano e inglese. Verificare la sezione facoltativa con checkbox non selezionata e testo che spiega conferma email e revoca; completare la registrazione senza selezionarla. L’account deve essere creato e nessun consenso deve risultare pending o confirmed.
2. Ripetere selezionando la checkbox. Verificare che l’account venga creato, il consenso risulti pending con origine `registration`, e venga inviata l’email double opt-in. Prima della conferma l’utente non deve essere eleggibile per annunci di nuovi corsi; confermare dal link e verificare lo stato confirmed.
3. Verificare che la preferenza selezionata non diventi attiva senza conferma; provare link scaduto/riutilizzato tramite i test già descritti nelle sezioni MAIL-11.3.
4. Simulare un errore di invio SMTP. La registrazione deve comunque riuscire e mostrare un avviso comprensibile; controllare log senza indirizzo email o altri dati personali. Verificare che la preferenza non risulti confirmed.
5. Disabilitare manualmente il plugin in **Sistema → Gestisci → Plugin**, aggiornare il pacchetto e verificare che resti disabilitato. Su nuova installazione deve invece essere attivo.
6. Compilare la registrazione senza consenso e con consenso selezionato, verificando che gli altri plugin e i campi standard Joomla continuino a funzionare.

La CI esercita il caricamento del plugin e la presenza del campo nel form nativo; il flusso SMTP e i casi sopra restano collaudo manuale sul sito Joomla.



## MAIL-11.4/11.5 — anteprima e invio controllato annuncio corso

**Comportamenti da testare sul sito Joomla di prova**

1. Accedere con un amministratore che può modificare un corso pubblicato e visibile nel catalogo pubblico. Nella modifica deve comparire **Anteprima email di annuncio**; verificare oggetto, HTML, testo semplice, immagine e lezioni pubblicamente visibili. L'anteprima non deve accodare email né mostrare indirizzi.
2. Verificare che il conteggio includa soltanto account attivi con email valida e consenso confermato. Bozze, corsi privati/non visibili e corsi in checkout da un altro editor non devono essere eleggibili.
3. In anteprima, inviare il modulo senza checkbox o con token CSRF alterato: la campagna non deve essere creata. Accedere con un utente privo di permesso di modifica e tentare l'URL diretto: accesso negato.
4. Selezionare la conferma esplicita e verificare una sola campagna e una riga di coda per destinatario con consenso. Ripetere la richiesta o ricaricare la pagina: non devono apparire campagne o righe duplicate; il corso non deve essere notificabile una seconda volta.
5. Configurare il mailer Joomla su una casella di prova ed eseguire il plugin Attività - JD Learn. Verificare invio in lotti, contenuto IT/EN, link del corso e link personale per revocare il consenso. Ripetere l’attività: i messaggi già inviati non devono essere rispediti.
6. Accodare un test e, prima dell'attività, revocare il consenso, bloccare l'account/cambiare email oppure rendere il corso privato o non pubblicato. Eseguire Scheduler e verificare che la riga venga marcata come saltata e nessuna email raggiunga il destinatario.
7. Simulare un errore SMTP temporaneo e verificare il retry limitato. Un errore di persistenza dopo l'accettazione SMTP non deve causare un reinvio cieco del messaggio.

La deduplicazione per corso/destinatario impedisce campagne ripetute; i controlli al dispatch usano anche la revisione temporale del consenso e i dati correnti dell'account/corso.


## MAIL-11 — riconciliazione della copertura e accettazione manuale (baseline 0.56.16-dev)

Implementazione integrata: PR #315, #317, #319, #321, #323, #325 e #327. **Tutti i test seguenti sono da eseguire e registrare sul sito di prova**; l'esito CI non li sostituisce.

1. Configurare in **Joomla → Configurazione globale → Server → Impostazioni email** un trasporto di test, senza configurare SMTP in JD Learn; verificare l'invio della conferma double opt-in e delle mail in coda tramite il medesimo trasporto.
2. Verificare opt-in inizialmente non selezionato, IT/EN, da registrazione Joomla, iscrizione al corso e Area personale; confermare il token entro 24 ore, tentare riuso e scadenza, poi revocare con link sicuro e ripetere la richiesta.
3. Personalizzare in **Sistema → Modelli email** soggetto, corpo HTML e plain text di `com_jlms.course.published`; aggiornare il componente e verificare che le modifiche non siano sovrascritte.
4. Provare anteprima con immagine assente/presente e immagini bloccate, introduzione e solo lezioni pubbliche, senza divulgare lezioni o materiali protetti; testare corso privato/non pubblicato e ACL amministrative.
5. Confermare esplicitamente l'annuncio di un nuovo corso: verificare platea eleggibile, una sola campagna per corso, deduplica, nessun invio automatico su semplice modifica/ripubblicazione e invio batch Scheduler.
6. Tra accodamento e invio revocare consenso, bloccare account, variare indirizzo email o rendere privato il corso: l'elemento deve essere saltato. Verificare minimizzazione dei dati in coda.
7. Simulare SMTP temporaneamente indisponibile; verificare retry e limite tentativi, assenza di invalidazione di registrazioni/iscrizioni o attività didattiche, e nessun doppio invio su esecuzioni ripetute. Trattare lo stato `sending` ambiguo solo con verifica degli esiti SMTP, senza presumere exactly-once.

Registrare versione Joomla/PHP, lingua, configurazione di invio, data, esito e prove. L'audit e la riconciliazione outbox sono integrati dalla PR #332; il relativo collaudo manuale resta pendente.


## IMP-09-MAIL-B / CORE-18 — audit e riconciliazione coda email (PR #332 integrata, 0.56.17-dev)

**Comportamenti da collaudare sul sito Joomla di prova**

1. Installare/aggiornare JD Learn 0.56.17-dev e aprire **JD Learn → Coda email** con un account `core.manage`. Un account senza il permesso non deve vedere la scorciatoia e deve ricevere diniego aprendo direttamente la vista.
2. Verificare filtri per stato e modello, paginazione e righe con corso e numero tentativi. Gli indirizzi devono essere mascherati; i contenuti del messaggio e l’indirizzo completo non devono essere esposti nella pagina.
3. In ambiente SMTP controllato, portare una riga in `sending` con `claimed_at` più vecchio di 15 minuti. La riga deve mostrare l’avviso; righe recenti o in altri stati devono restare in sola lettura.
4. Confrontare i log del mailer/SMTP. Se il messaggio è stato accettato/consegnato, usare **Segna come inviato**; se non è stato accettato e il retry è sicuro, usare **Accoda nuovo tentativo**. Senza nota (10–1000 caratteri) o conferma esplicita il server deve rifiutare l’operazione. Verificare che il testo avverta del rischio di duplicati.
5. Ripetere il POST senza token CSRF, con utente non autorizzato e con un ID alterato: ogni tentativo deve essere respinto. Inviare due richieste concorrenti per la stessa riga: una sola può completare; il refresh deve mostrare il nuovo stato.
6. Verificare che la scelta “inviato” imposti `sent`, data UTC e autore, cancelli email/nome/payload personali dalla coda; la scelta “retry” imposti `retry` e `available_at` immediato. Nota e azione devono essere visibili nello storico.
7. Verificare che a cinque tentativi il retry manuale non sia disponibile e che il limite venga applicato anche a richieste POST costruite a mano. Dopo la riconciliazione una riga non deve poter essere modificata una seconda volta.
8. Aggiornare da versione precedente e verificare la migrazione schema senza perdita delle righe e la compatibilità dei task Scheduler esistenti.

Le date mostrate nell’audit sono in UTC e identificano la transizione registrata, non il momento di accettazione SMTP.

**Accettazione manuale Joomla/SMTP:** da eseguire; la CI non equivale al collaudo reale. La riconciliazione “inviato” richiede evidenza nei log del mailer, mentre “retry” richiede evidenza che SMTP non abbia accettato il messaggio.


## SEC-P1 — riduzione dati nelle eccezioni SMTP (0.56.18-dev, PR #338 integrata)

**Comportamenti da collaudare sul sito Joomla di prova dopo installazione/upgrade**

1. Configurare il mailer globale Joomla su un trasporto SMTP di test e accodare un messaggio a un account fittizio con indirizzo riconoscibile, per esempio `sec-test@example.invalid`.
2. Forzare una risposta SMTP di errore che includa l'indirizzo di prova o una stringa diagnostica identificabile. Eseguire il task Scheduler e verificare che la riga resti retry/failed secondo il limite previsto.
3. Controllare la tabella `#__jlms_mail_queue`: `last_error` deve contenere solo il messaggio generico, senza testo SMTP, indirizzo, nome o dettagli del provider.
4. Aprire **JD Learn → Coda email**: la risposta della vista non deve includere il campo `last_error` né esporre payload/indirizzo completo.
5. Aggiornare da una versione precedente che contiene righe con errori storici: la migrazione deve sostituire tutti i `last_error` preesistenti con il testo generico senza modificare stato, conteggio tentativi o disponibilità.
6. Ripetere con errore temporaneo e permanente; verificare retry, limite di cinque tentativi, nessuna interruzione della registrazione/iscrizione e nessuna fuga di dati nella pagina.

**Accettazione manuale Joomla/SMTP:** pendente. CI standard 4/4 nel [run 43522457570](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570). Il pacchetto [JD-Learn-0.56.18-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570/artifacts/14083597534) contiene `pkg_jlms-0.56.18-dev.zip`, SHA-256 `c59074d3bbe6a4ddb6b79714ca0d2948e04d6bff0d40316a1ac0e15f8087cbc6`. Non inserire dati reali o segreti nelle prove; usare solo account e trasporto usa-e-getta.


## IMP-09-MAIL-D / MAIL-04 — promemoria scadenza corso (candidata 0.56.19-dev)

**Comportamenti da testare sul sito Joomla di prova**

1. Aggiornare il pacchetto e aprire le opzioni di JD Learn → Notifiche email. Confermare che il promemoria sia disabilitato per impostazione predefinita; abilitarlo e impostare una soglia di 3 giorni.
2. In un corso di prova, impostare la scadenza dell'edizione a circa 48 ore e iscrivere uno studente attivo non completato con email usa-e-getta. Eseguire il task pianificato **JD Learn: send queued emails**. Atteso: un solo messaggio in outbox con corso, data/ora UTC e collegamento all'area personale.
3. Eseguire di nuovo il task: non deve essere accodata una seconda email per la stessa iscrizione e scadenza. Verificare in **JD Learn → Coda email** il template `com_jlms.course.expiring` e il corso associato.
4. Disabilitare l'override per il corso e controllare l'ereditarietà dall'impostazione globale; impostare poi l'override su disabilitato e verificare che non venga accodata alcuna email. Con l'impostazione globale disabilitata, il comportamento deve restare spento anche per i corsi in ereditarietà.
5. Accodare un promemoria, completare o sospendere l'iscrizione prima dell'invio SMTP e rilanciare il task: l'elemento non deve essere inviato e i dati personali della riga saltata devono essere rimossi. Ripristinare mailer SMTP di prova e controllare le traduzioni IT/EN del template.
6. Provare date senza scadenza, scadenza già trascorsa, corso completato, utente bloccato e soglie 1/30 giorni; nessuno deve ricevere un promemoria fuori dai criteri.

L'esecuzione del task usa solo il mailer globale Joomla; nessun SMTP autonomo è aggiunto. La verifica manuale SMTP resta da registrare.


## PR #346 — Dashboard raggruppata e template email Joomla (CORE-20 / UI-02)

- [ ] Super User: Dashboard con sezioni Didattica, Persone e valutazione, Comunicazioni, Configurazione e tutti i collegamenti permessi nell'ordine concordato; Attestati non è in prima posizione (issue #308 soltanto parzialmente risolta: resta la vista elenco).
- [ ] Utente con ACL limitate: soltanto link autorizzati e nessuna sezione priva di pulsanti; rifiuto core dei link visitati direttamente senza permessi.
- [ ] Comunicazioni → Template email: apertura di Joomla `com_mails` con `com_jlms` selezionato; nessun template estraneo visibile, filtro rimovibile; ricerca/lingua/paginazione non restano attaccate da sessioni precedenti; verifica in IT/EN.
- [ ] Verificare issue #312 separatamente: titoli e descrizioni mail template localizzati, senza considerare il problema risolto dalla sola aggiunta del collegamento.
- [ ] Desktop 13–15 pollici, smartphone, tema scuro, accessibilità tastiera e focus, stato provider espandibile, toolbar Opzioni nativa invariata.

Nessun dato di test personale nei log o screenshot. CI e test strutturali non sono accettazione manuale Joomla.

## MAIL-F — Richiesta pending (da collaudare in Joomla)

1. Creare un corso pubblicato in modalità **Approvazione**, con edizione aperta e iscrizioni consentite. Accedere come studente avente il livello di accesso previsto: deve apparire **Richiedi iscrizione**, non **Inizia**.
2. Inviare la richiesta: verificare il messaggio di ricevuta e una sola riga `#__jlms_enrollment_requests` con stato `pending`. Nessun record in `#__jlms_enrollments`, nessun accesso all'aula, nessun progresso e nessuna variazione dei gruppi Joomla.
3. Ripetere la stessa POST e un nuovo invio con chiave differente: deve restare una sola richiesta e una sola notifica in coda; nessun reinvio duplicate SMTP dopo lo Scheduler.
4. Verificare che modalità automatica/esplicita non accettino `enrollment.request`, e che la chiamata senza CSRF, con utente non autenticato/bloccato, accesso revocato, edizione chiusa, lingua errata o corso non pubblicato venga respinta senza mutazioni.
5. Impostare le preferenze globali e gli override corso su disabilitata/abilitata/eredita; verificare la coda per nuove richieste. Modificare il template in IT/EN e verificare i placeholder canonici, lingua destinatario e conservazione upgrade.
6. Forzare un fallimento dell'accodamento e verificare rollback dell'intera richiesta; riprovare dopo il ripristino. La consegna SMTP avviene in Scheduler e non deve attribuire accesso didattico.

**Non ancora implementati in questo incremento:** decisione amministrativa approva/rifiuta, promozione a iscrizione attiva, notifiche esiti e revoca distinta; non considerarli collaudati.

## MAIL-F — Decisioni amministrative pending (prove manuali pendenti)

1. Creare un corso pubblicato in modalità approvazione e due richieste pending. Come amministratore autorizzato aprire **Corsi → Iscritti**: le richieste devono essere visibili solo nel corso di appartenenza, senza duplicati; con docente non assegnato devono essere nascoste/negate.
2. Approvare la prima richiesta: una sola iscrizione `active`, versione corretta, progressi inizialmente vuoti, audit della decisione, stato richiesta `approved`, un solo evento `enrollment.approved` in coda; nessuna concessione automatica di gruppi Joomla. Verificare l'accesso all'aula con lo studente.
3. Rifiutare la seconda: stato `rejected`, nessuna iscrizione, nessun accesso didattico, evento `enrollment.rejected`; la motivazione interna non compare in oggetto, testo o HTML dell'email.
4. Inviare nuovamente lo stesso form, con la stessa revisione, poi tentare la decisione opposta. Verificare risposta di conflitto e assenza di doppi record/email.
5. Prima di approvare chiudere iscrizioni, scadere edizione, esaurire posti, cambiare versione o revocare ACL allo studente. Ogni azione deve fallire senza mutazioni né invii. Verificare capienza in due richieste concorrenti: non superare la capacità impostata.
6. Disabilitare globalmente e per corso le notifiche approval/rejection, poi verificare gli override eredita/abilita/disabilita, l'invio tramite Scheduler SMTP e il retry; confermare la conservazione di traduzioni e template personalizzati dopo upgrade.
7. Testare utenti con permessi diversi, URL di altro corso, CSRF mancante, metodo GET, motivazioni contenenti caratteri non ammessi e richieste obsolette. Verificare audit e risposte senza dati riservati.

La CI automatizzata non sostituisce questi collaudi di accettazione.

## Collaudo upgrade 0.56.20-dev (PR #373, 14 novembre 2026)

**Ambiente:** Joomla 6.2.0 di prova con una versione JD Learn precedente alla #366 (o 0.56.19-dev) e Mail Templates Joomla preesistenti, almeno uno personalizzato. Eseguire un backup ripristinabile prima dell'upgrade. Scaricare l'artefatto Actions `JD-Learn-0.56.20-dev`, **estrarlo una volta** e caricare in Joomla solo il file interno `pkg_jlms-0.56.20-dev.zip` (manifest `pkg_jlms.xml` in radice).

- [ ] Eseguire l'upgrade in **Estensioni → Installa → Installa da URL** (fornendo un URL diretto allo ZIP installabile se disponibile) oppure **Carica file pacchetto** con lo ZIP interno; verificare esito positivo e nessuna eccezione `DatabaseDriver::updateObject() ... passed by reference`.
- [ ] Aprire **Sistema → Mail Templates**, filtrare `com_jlms`: i template presenti, inclusi quelli IT/EN, devono conservare oggetto, corpo HTML/testo, mittente e preferenze personalizzate; le variabili canoniche devono essere disponibili.
- [ ] Ripetere lo stesso upgrade, verificando idempotenza e conservazione contenuti/dati iscrizioni.
- [ ] Accedere ai corsi, alle iscrizioni e a una lezione con progresso preesistente; verificare che la ripresa e il completamento funzionino.
- [ ] Verificare separatamente il manager **Campi personalizzati** su Joomla 6.2.0 per l'issue [#374](https://github.com/jeckodevelopment/jd_learn/issues/374); questa anomalia HTTP/FieldsHelper è distinta dal blocco d'installazione.

**Stato:** prove automatizzate PR #373 4/4 CI superate (Joomla 6.0.0 in CI standard); accettazione **manuale** Joomla 6.2.0 ancora da eseguire. Non chiamare il vecchio snapshot GitHub `jdlearn-dev-latest` una release aggiornata: contiene la versione 0.56.19-dev, ora superata.

## MAIL-G — Primo incremento: avviso docente assegnato (0.56.21-dev)

- [ ] Su Joomla 6.2.0 assegnare un nuovo profilo docente **collegato a un account Joomla attivo** a un corso salvato: verificare una sola voce `com_jlms.teacher.assigned` nella Coda email e il destinatario esatto.
- [ ] Assegnare un docente senza account, ripetere il salvataggio del corso o riordinare soltanto profili già assegnati: nessuna nuova mail.
- [ ] Rimuovere il docente prima del dispatch o bloccare l'account/alterare la sua email: il dispatch deve marcare la notifica `skipped` senza consegnare o rivelare dati ad altri.
- [ ] Provare disabilitazione globale e override per corso; inviare tramite SMTP a docente di prova, controllando lingua, oggetto, HTML/testo e conservazione di template personalizzati dopo upgrade.
- [ ] Verificare che l'assegnazione non attribuisca ACL di amministrazione e non includa dati di iscritti o altre persone nell'email.

Il solo successo CI non prova l'accettazione SMTP sul sito dell'utente.


## MAIL-H / #312 — Titoli e descrizioni dei Mail Templates (PR #395)

**Comportamenti da testare sul sito Joomla 6.2 (IT/EN)**

1. Con amministrazione in italiano, aprire **Sistema → Modelli email** e filtrare `com_jlms`; verificare che tutti i 14 modelli registrati abbiano titolo e descrizione localizzati, senza chiavi `com_jlms_MAIL_..._TITLE/DESC`.
2. Aprire il dettaglio dei modelli `com_jlms.enrollment.confirmed`, `com_jlms.course.completed` e `com_jlms.course.published`: titolo e descrizione localizzati anche nella modifica.
3. Ripetere elenco e dettaglio cambiando la lingua amministrativa in inglese.
4. Modificare soggetto, HTML e testo semplice di un template e creare una versione localizzata. Eseguire upgrade e verificare che le personalizzazioni rimangano inalterate e non si creino record duplicati.
5. Verificare che accessi al gestore non accodino né inviino email; controllare che la procedura di installazione/upgrade non fallisca.

**Stato:** implementazione candidata, accettazione manuale non eseguita. La CI non sostituisce questa verifica.

## MAIL-SCHED — PR #424 integrata (collaudo pendente)

Seguire gli scenari dettagliati in [MAIL_SCHEDULER_SETUP.md](MAIL_SCHEDULER_SETUP.md): installazione pulita, task disabilitato a 5 minuti, attivazione, SMTP di prova e invio singolo, fallimento e ripristino, cron Joomla, plugin disabilitato, upgrade con impostazioni personalizzate, task duplicati, disinstallazione, ACL, IT/EN e responsive. La sola CI non costituisce accettazione.

## MAIL-RECONCILE — PR #427 integrata (collaudo manuale pendente)

Riprodurre l'errore su messaggio `sending` con `claimed_at` anteriore di almeno 15 minuti. Confermare dal log SMTP o dal destinatario che non risulta consegna. Dalla Coda email scegliere la riconciliazione `retry`, inserire nota di almeno 10 caratteri e confermare: la chiamata POST non deve più dare «Impossibile riconciliare il messaggio»; verificare `retry`, `claimed_at=NULL`, autore e nota audit, quindi task manuale e una sola consegna. Verificare anche `sent` manuale per recapito accertato, soglia 15 minuti, limite 5 tentativi, CSRF, ACL e comportamento dopo upgrade Joomla. Non marcare come accettato prima del riscontro sul sito di prova.

## LES-01 / SCR-06 — trascrizione audio

1. In una bozza, creare o modificare una lezione di tipo Audio e inserire testo con paragrafi e caratteri HTML nel campo Trascrizione audio; salvare e riaprire l’editor. Il contenuto deve essere conservato.
2. Pubblicare la nuova versione e accedere come iscritto autorizzato. La trascrizione deve apparire sotto al player audio in un controllo espandibile con nome localizzato e navigabile da tastiera.
3. Verificare che testo non affidabile venga filtrato, che HTML ammesso mantenga la struttura e che una trascrizione vuota non mostri un controllo vuoto.
4. Cambiare il tipo di lezione da Audio ad altro e salvare: la trascrizione audio non deve essere resa né riutilizzata; una lezione audio nuova non deve ereditare il testo di un’altra lezione o versione.
5. Aprire la lezione con un account non iscritto o non autorizzato: non devono essere esposti né il contenuto né la trascrizione. Verificare l’assenza di modifiche a progresso, ripresa audio e completamento.


## VID-01 / VID-02 — trascrizione facoltativa delle lezioni video

1. In una bozza creare o modificare una lezione Video e compilare Trascrizione della lezione video con paragrafi e markup ammesso. Salvare, riaprire e verificare che il testo sia conservato nella versione didattica.
2. Pubblicare la versione e accedere come iscritto autorizzato: il controllo espandibile deve comparire sotto al player, con nome accessibile e traduzioni IT/EN.
3. Verificare il filtro HTML, la trascrizione vuota senza riquadro, il cambio Video → altro tipo e l’assenza di riuso tra lezioni o versioni.
4. Verificare l’accesso negato per account non iscritto/non autorizzato: nessuna trascrizione nel markup, nel payload aula o in endpoint separati.
5. Accertare che la trascrizione non cambi soglia, durata, progresso, ripresa, consenso, richieste al provider o completamento del video.


## MAIL-E — variabili di contesto email corso/lezione/docente (PR #464 integrata; accettazione pendente)

1. Aggiornare un sito Joomla 6 con template JD Learn già personalizzati. In **Sistema → Modelli email**, verificare che i tag canonici siano aggiunti ai modelli `com_jlms.lesson.available`, `com_jlms.teacher.assigned` e `com_jlms.course.published`, mentre oggetto, testo, HTML, mittente e opzioni personalizzate restano invariati.
2. Configurare per un corso descrizione, durata stimata e inizio calendario; inviare un avviso di lezione e un annuncio opt-in. Verificare che descrizione e durata coincidano con il corso, la data sia UTC e che i valori mancanti siano vuoti senza token letterali. Controllare rendering HTML e testo semplice in italiano e inglese.
3. Con iscrizione attiva e lezione disponibile, aprire `LESSON_URL`: deve portare alla lezione nell'aula autenticata dell'iscrizione destinataria. Cambiare o revocare l'iscrizione prima dell'invio e verificare il normale controllo di idoneità al dispatch.
4. Verificare che `LESSON_DESCRIPTION` sia vuota e che nessun corpo/materiale protetto compaia nell'email. Assegnare un profilo docente collegato a un account e verificare `TEACHER_NAME` dal titolo del profilo, senza includere docenti non pertinenti.
5. Impostare il mittente Joomla valido e poi un valore non email: `CONTACT_EMAIL` deve mostrare solo il primo e restare vuoto nel secondo caso. `USER_FIRSTNAME` non deve apparire tra i tag disponibili; i modelli usano un saluto neutro dove non vogliono mostrare il nome completo.
6. Simulare HTML con caratteri speciali in descrizione/titolo e verificare escaping singolo; ripetere con campi assenti/non validi. Completare prova SMTP e verificare stato inviato in Coda email. Questa suite è richiesta prima di registrare l'accettazione sul sito.


## Issue #430 — metadati di accesso nella scheda corso pubblica

Aprire come ospite la vista frontend di un corso pubblicato e visibile nel catalogo. Verificare che la pagina e i metadati SEO/social si carichino senza warning PHP e che il livello di accesso al catalogo sia presente nel record restituito dal servizio. Provare poi un corso il cui livello catalogo non include l’ospite: deve restare inaccessibile o non indicizzabile secondo le regole già previste, senza warning. Verificare anche un corso visibile all’ospite e con livello contenuto più restrittivo: la scheda pubblica resta consultabile, ma l’accesso didattico conserva i controlli sul livello contenuto.

## CERT-03/08 — rifiuto delle date di completamento non valide (PR #473)

1. Su Joomla di prova, completare realmente un corso abilitato all'attestato; scaricare il PDF e confrontare la data con la data di completamento persistita. Ripetere con studente IT/EN e con data in formato SQL/ISO.
2. In un ambiente di prova eliminabile, predisporre un documento `pending_render` con snapshot di identità la cui `completed_at` sia vuota, relativa (`tomorrow`) o impossibile (`2026-02-30`): il generatore deve rifiutare il PDF senza sostituire la data odierna. Nessun attestato falso deve essere mostrato come disponibile.
3. Verificare download consentito al proprietario e negato a ospiti/studenti non proprietari; codice e snapshot certificati non devono variare. I PDF già generati non vengono riscritti.

**Esito:** non eseguito. CI e test unitari non equivalgono ad accettazione manuale. La correzione non completa CERT-08: restano rigenerazione amministrativa e verifica backup/ripristino.

## MAIL-10/CORE-18 — data di inizio corso nelle email (PR #474)

1. Su Joomla di prova, configurare un evento autorizzato che includa `{COURSE_START_DATE}` e impostare un inizio corso assoluto valido: controllare anteprima, accodamento e email SMTP con data UTC corretta, anche con timezone offset.
2. Preparare nel solo ambiente di prova un corso con `starts_at` mancante, relativo (`tomorrow`) o impossibile (`2026-02-30`). Il contesto email non deve inventare una data; deve usare il fallback vuoto previsto dal producer.
3. Verificare template personalizzato IT/EN, segnaposto canonici, ACL dell'operatore e consenso del destinatario. Non modificare i template globali durante una singola comunicazione.

**Esito:** non eseguito. Test automatico in `tests/mail-template-variables.php`; accettazione SMTP/Joomla separata.


## Issue #308 / PR #475 — elenco Attestati e impostazioni corso native Joomla

1. Aprire **JD Learn → Attestati** con Super User. Controllare Search Tools Joomla, ricerca per studente, corso e codice, stato disponibile/in attesa/revocato, svuotamento filtri, numero risultati e ordinamento per ogni intestazione della tabella. Creare almeno 25 attestati di prova e verificare paginazione e cambio limite, preservando i filtri.
2. Aprire un attestato dalla tabella; verificare dettaglio, audit e download. Accedere allo stesso URL come utente senza permessi: nessuna visualizzazione/download di dati riservati. Verificare IT/EN, desktop/mobile e tastiera con Atum.
3. Aprire **Corsi → Nuovo/Modifica → Attestati**, selezionare **Eredita**, **Sì** o **No** e salvare. Riaprire il corso e verificare valore persistito e rispetto del default globale. Un corso in checkout da un altro amministratore non deve permettere la modifica dell'opzione.
4. Completare realmente un corso in modalità Apprendimento con attestati abilitati; in modalità Sola consultazione o con attestati disabilitati non deve esserci emissione. Provare anche l'upgrade da dati esistenti.

**Esito:** da collaudare sul sito Joomla reale. CI verde non costituisce accettazione manuale.


## CERT-08 / PR #477 — rigenerazione tecnica PDF senza modifica attestato

1. Emettere un attestato in modalità Apprendimento, salvare i dati certificati (codice, corso, studente, versione, data e documento), poi accedere al dettaglio con un amministratore autorizzato. Compilare motivo (8–500 caratteri), confermare e premere **Rigenera PDF attestato**. Il PDF deve essere disponibile e contenere gli stessi dati. Il registro audit deve mostrare `admin_regenerate`; revisione e ID risorsa PDF devono cambiare.
2. Senza autenticazione o con utente privo di `jlms.certificates.manage`, la rigenerazione deve essere rifiutata senza accesso ai dati. Senza token CSRF o con richiesta GET/HEAD deve essere rifiutata. Motivo vuoto/corto, conferma assente e ID non valido devono essere rifiutati.
3. Aprire lo stesso attestato da due sessioni amministrative; rigenerare dalla prima e poi inviare il vecchio form dalla seconda: il secondo tentativo deve essere respinto con conflitto di revisione senza sovrascrivere il PDF più recente. Provare anche un attestato revocato: niente rigenerazione.
4. Nel solo ambiente di prova provocare indisponibilità dello storage, errore dell'audit e input PDF invalidi: l'asset corrente precedente deve restare collegato e nessuna rigenerazione parziale deve risultare attiva.
5. Verificare intestazione e lingua IT/EN, mancato invio di nuove mail, assenza di modifica a `identity_snapshot_json`, `template_snapshot_json`, serial e `issued_at`. Ripetere installazione/upgrade preservando attestati precedenti.
6. Eseguire **backup e ripristino** DB + storage privato su ambiente separato; controllare download, audit e blob storici con SHA-256 prima e dopo. Questo ultimo gate richiede prova operativa e non è soddisfatto dalla CI.

**Esito:** da eseguire sul sito di collaudo. Test automatizzati non equivalgono ad accettazione Joomla.


## Regressione PR #475 — Errore HTTP 500 nella vista Attestati

1. Su Joomla di prova, come amministratore con permesso di gestione attestati, aprire **JD Learn → Attestati** senza filtri. La vista deve caricarsi senza HTTP 500, anche con elenco vuoto.
2. Cercare uno studente, filtrare per `ready` e `revoked`, azzerare filtri, ordinare le colonne e cambiare limite/pagina. Ogni azione deve mantenere il corretto risultato e non generare eccezioni.
3. Ripetere con un utente senza `jlms.certificates.manage`: l'accesso deve essere negato senza esporre snapshot o PDF.
4. Aprire il dettaglio e scaricare un documento già disponibile; la correzione non deve cambiare i permessi, gli audit, i dati e le funzioni di rigenerazione appena introdotte.
5. Verificare dopo l'upgrade che non siano andate perse emissioni, impostazioni, dati e permessi. CI include un nuovo accesso HTTP reale con login alla vista Attestati; la convalida manuale è separata.

**Esito:** non eseguito sul sito Joomla dell'utente. L'errore originale era mascherato dal generico `COM_JLMS_SAVE_FAILED`.


## FE-10 — hook CSS pubblico e riuso schede (prima tranche)

1. Installare il pacchetto post-merge su Joomla 6 e aprire catalogo corsi, catalogo percorsi, elenco categorie e dashboard autenticata (compresi eventuali attestati). Verificare che le schede mantengano contenuti, collegamenti, ordine, filtri e progressi.
2. Controllare larghezza stretta/contenitore con colonna laterale e titoli lunghi: nessuno scroll orizzontale introdotto dalle schede; titolo leggibile, nessun elemento sovrapposto.
3. Con browser developer tools verificare che ogni scheda esponga `.jlms-card` **insieme** alla classe specifica preesistente, con ID univoco; verificare che il CSS della card non influenzi articoli e moduli Joomla esterni.
4. Applicare un override di `courses/default.php` che personalizzi `.jlms-course-card` e un CSS che personalizzi `.jlms-card`: nessuna modifica al markup funzionale necessaria, vecchi override senza classe nuova restano usabili.
5. Aprire l'aula come studente; verificare toggle programma, lingua IT/EN, stato `aria-expanded`, player e progressi invariati. Ripetere con JavaScript disabilitato per visibilità dell'indice.
6. Verificare desktop, smartphone, zoom 200%, focus tastiera, contrasto, permessi/ACL e accessibilità; la CI strutturale non prova l'accettazione browser.

**Esito:** da eseguire su Joomla di prova. Non dichiarare copertura FE-10 completa o accettazione sulla base del solo smoke statico.


## FE-10 — inventario selettori CSS/JS, WAM e override frontend (seconda tranche)

1. Dopo l'installazione dell'artefatto post-merge, aprire catalogo corsi, scheda corso, categorie, percorsi, area personale, aula, quiz e scheda docente con un template Joomla standard; verificare assenza di errori di rendering.
2. Usare un template con CSS personalizzato su `.jlms-card` e `.jlms-course-card` e verificare che gli override precedenti senza la classe condivisa funzionino ancora; verificare che non siano influenzati moduli e articoli esterni al componente.
3. Aprire/chiudere il programma dell'aula, controllare `aria-expanded` e i nomi tradotti; verificare player video, velocità/sottotitoli se disponibili, audio, segnalazione avanzamento e quiz di prova con salvataggio/ripresa.
4. Usare contenuti/ruoli IT/EN, anonimo/iscritto/utente senza accesso, con SEF attivo/disattivo; confermare che la ricognizione non modifica ACL, contenuti protetti o endpoint.
5. Ripetere su mobile, larghezza ridotta da moduli Joomla, zoom 200% e navigazione con tastiera. Verificare override nativi delle viste da `templates/NOME/html/com_jlms`.
6. Per lo sviluppo: eseguire `python3 tests/frontend-css-contract.py` e `python3 tests/frontend-contract-hooks.py`; cambiare intenzionalmente un ID nel markup in branch di prova e verificare che il gate fallisca, quindi ripristinarlo.

**Esito:** collaudo manuale Joomla non eseguito; l'inventario e i test statici verificano la struttura sorgente, non l'interazione browser.


## FE-01 — Catalogo corsi responsive e opzioni della voce menu Joomla

1. In **Componenti → JD Learn → Opzioni** configurare ricerca Sì e 12 corsi per pagina. Creare due voci di menu di tipo **Catalogo corsi**: nella prima ereditare i parametri, nella seconda impostare **Mostra ricerca e filtri = No**, **Corsi per pagina = 6**. Verificare che il primo catalogo mostri la ricerca e il secondo solo le schede, mantenendo paginazione e accessi.
2. Modificare il valore globale della ricerca a No; verificare ereditarietà e override locale Sì. Verificare che **Globale**, **Sì** e **No** si distinguano anche dopo salvataggio e upgrade, in entrambe le lingue.
3. Con ricerca disabilitata visitare l'URL con `filter[search]`, `filter[teacher_id]`, `filter[tag_id]`, `filter[layout]` e `filter[order]`: i parametri non devono modificare il risultato né riattivare la modalità elenco; un eventuale filtro server `catid` previsto deve rimanere applicato.
4. Attivare/disattivare copertina e descrizione breve per voce menu. Prove con corso dotato di immagine e senza immagine; nessuna immagine esterna forzata, testo alternativo conforme, nessun documento/URL di video protetto esposto nel sorgente.
5. Verificare l'ordinamento predefinito (più recenti / titolo) e il controllo di ordinamento visibile/nascosto. Verificare paginazione per 6, 9, 12 e 24 elementi, e links corretti con SEF attivo e disattivo.
6. Confrontare template desktop largo e stretto (colonne/moduli laterali), smartphone, zoom 200%, tastiera, focus, lingua IT/EN e override Joomla esistenti; nessuna quarta o quinta colonna, testo leggibile e assenza di scroll orizzontale.
7. Verificare la variante elenco già esistente come regressione e provare guest, utente autenticato, corso riservato, categorie non pubblicate e finestra promozionale scaduta. ACL e filtri di pubblicazione devono rimanere autorevoli lato server.
8. Eseguire `python3 tests/frontend-catalog-options.py`, `python3 tests/frontend-css-contract.py` e CI completa; installare il pacchetto finale su Joomla di prova.

**Esito:** manuale non eseguito; il test statico e la CI non equivalgono all'accettazione Joomla.


## FE-01 — Filtro categoria e metadati di scheda (seconda tranche)

1. Da **JD Learn → Opzioni** verificare **Mostra filtro categoria** = Sì; creare due voci menu `Catalogo corsi`, una che eredita il valore globale, una con override No. Se ricerca globale è No, il controllo categoria deve restare nascosto anche dove la singola opzione categoria è Sì.
2. Creare corsi pubblicati in categorie principali e sottocategorie; scegliere una categoria dal menu GET e verificare che la griglia mostri soltanto corsi **direttamente** in quella categoria. Testare `catid` già applicato alla pagina per verificare che la selezione non allarghi il perimetro ai fratelli o agli antenati.
3. Corsi in categorie non pubblicate/riservate, categorie antenate non accessibili, corsi fuori finestra, lingua differente o corso senza versione pubblicata **non** devono comparire nei valori del selettore né nella griglia.
4. Con ricerca o filtro categoria disattivati, inviare `filter[category_id]` artificialmente: deve essere ignorato. Con filtro abilitato ma ID inventato, il risultato deve essere vuoto, senza mai mostrare tutti i corsi come fallback.
5. Corsi con metadati `difficulty` e `estimated_minutes` nella versione pubblicata: verificare livello IT/EN e durata. Assenza di metadati: nessun riquadro vuoto. Modificare la bozza senza pubblicare: le card devono continuare a mostrare i valori pubblicati precedenti.
6. Verificare navigazione SEF, paginazione, ricerca per titolo, tag, docenti e percorsi, modalità legacy elenco, tema Joomla personalizzato, mobile, tastiera, zoom 200% e CSS di override.
7. Confermare CI verde e `python3 tests/frontend-catalog-options.py` prima del merge; dopo il merge installare il pacchetto ZIP Joomla e ripetere lo smoke test.

**Accettazione Joomla manuale:** pendente. CI verde non equivale a requisito accettato.


## FE-02 — Scheda corso: sidebar e programma pubblico

1. Installare l'artefatto post-merge e aprire una scheda corso pubblicata con presentazione, copertina, obiettivi, docenti e materiali di anteprima, in Joomla con template standard. Verificare che nessuna sezione vuota venga resa e che breadcrumb, header/footer Joomla rimangano disponibili.
2. Desktop largo: programma e testo a sinistra, pannello iscrizione a destra; pannello sticky senza coprire il menu del template (anche se il template usa header sticky). Ridurre l'area del componente tramite moduli laterali: verificare transizione a una colonna e pannello statico. Smartphone/zoom 200%: introduzione e descrizione/campi Joomla seguiti dal pannello, quindi docenti e programma, senza scroll orizzontale.
3. Anonimo: login; autenticato non abilitato: messaggio informativo; automatico e approvazione: **un solo form** con token CSRF/idempotency, rispettando finestra e permessi. Corso completato/archiviato e finestra chiusa non devono consentire un'iscrizione non autorizzata. Confrontare con pacchetto precedente.
4. Programma: modulo iniziale aperto, gli altri espandibili senza JavaScript; titoli del programma pubblico della versione corrente, lezione futura/scaduta o con rilascio relativo non visibile. Verificare nessuna disclosure di corpo lezioni, URL dei player, allegati non pubblici, quiz o lezioni di altra versione nel sorgente e nei link.
5. Verificare link alle anteprime esplicitamente consentite, download materiali preview, profili docenti, varianti multilingua IT/EN, permalink/SEF e override Joomla esistenti. Provare tastiera, focus `summary`, lettore di schermo e differenze di contrasto.
6. Eseguire `python3 tests/frontend-course-details.py`, il gate CSV e la CI completa, verificando che la build installabile venga generata senza regressioni; poi ripetere smoke su sito Joomla.

**Esito:** accettazione manuale non eseguita; il gate strutturale e CI non provano l'interazione reale.


## FE-03 — Aula didattica, programma espandibile e header (prima tranche)

**Evidenza automatizzata post-merge:** [PR #502](https://github.com/jeckodevelopment/jd_learn/pull/502), [CI 4/4](https://github.com/jeckodevelopment/jd_learn/actions/runs/43890696148), matrice UI-04/STU-01 allineata. Il collaudo manuale resta da svolgere.

1. Installare il pacchetto e aprire un corso con più moduli/lezioni come iscritto autorizzato. Il modulo che contiene la lezione corrente deve risultare aperto, gli altri chiusi, i titoli accessibili; aprire e richiudere con tastiera (Enter/Spazio).
2. Con template Joomla desktop stretto a causa di moduli laterali e viewport desktop ampia, il programma deve essere richiamabile dal toggle in base alla **larghezza del componente**, non alla sola viewport. Verificare `aria-expanded` e le etichette IT/EN.
3. Senza JavaScript, programma visibile e moduli `details` utilizzabili; nessuna lezione privata accessibile per il solo fatto di apparire nella scaletta.
4. Provare larghezza larga, mobile, zoom 200%, smartphone ruotato, viewport bassa, header sticky del template (usando `--jlms-classroom-sticky-offset`): nessuna sovrapposizione o scroll orizzontale, fallback statico quando necessario.
5. Verificare 0, 1, più docenti; gli account senza profilo pubblico o con ACL non valido non devono mostrare nome/link. Nessuna email/username Joomla nel DOM.
6. Video con consenso, audio, quiz, testo lungo, materiali e salvataggio progressi: comportamento invariato. Il cambio lezione non attribuisce completamento.
7. Eseguire `python3 tests/frontend-classroom-layout.py`, `tests/frontend-contract-hooks.py`, il gate CSV e tutte le matrici CI; annotare separatamente collaudo manuale e test automatici.

**Accettazione Joomla manuale:** pendente; primo incremento FE-03 non equivale al completamento di FE-04.


## FE-04 — Navigazione manuale Precedente/Successiva, prima tranche

1. Installare il pacchetto post-merge; in un corso con almeno 3 lezioni, aprire la lezione centrale: compaiono entrambi i link, con il titolo corretto. La prima lezione non presenta link Precedente, l'ultima non presenta link Successiva.
2. Impostare una lezione immediatamente successiva con prerequisito incompleto: la navigazione mostra il titolo e un'indicazione non cliccabile del blocco; dopo aver soddisfatto il prerequisito e ricaricato, il link è nuovamente disponibile. Ripetere con lezione futura e finestra chiusa.
3. Cambiare lezione con i link, senza completare alcuna attività: verificare che non si crei un progresso `completed` o un tentativo quiz. Il normale stato `started` dell'ultimo accesso è consentito solo dal servizio backend esistente. Ritornare alla precedente e verificare la ripresa.
4. Provare quiz, audio, video e testo lungo, account diverso, iscrizione revocata, corso sospeso/archiviato e lingua IT/EN. Verificare che un URL costruito manualmente non consenta l'accesso a lezioni non autorizzate.
5. Testare con JavaScript disabilitato, tastiera, reader di schermo, 200% zoom, smartphone e template Joomla con moduli laterali. Verificare il focus, `aria-label` e la resa a una colonna.
6. Eseguire `python3 tests/frontend-lesson-navigation.py`, CI completa e prova d'installazione/aggiornamento Joomla; documentare separatamente esito CI ed esito manuale.

**Esito manuale:** da eseguire. Non dichiarare FE-04 completato dopo questa sola tranche.


## FE-04 — Feedback salvataggi lezione/audio (seconda tranche)

1. Aprire una lezione testuale accessibile: `lesson.begin` visualizza brevemente il messaggio di salvataggio in corso, poi la conferma; la conferma scompare dopo circa 5 secondi, senza cambiare lo stato `completed`.
2. Simulare errore di rete persistente o risposta HTTP 500 per `lesson.begin`: il messaggio di errore resta visibile anche dopo 5 secondi, con `role=alert` e `aria-live=assertive`. La richiesta conserva la stessa chiave idempotente attraverso l'unico retry.
3. Aprire una lezione audio, avviare la riproduzione e cambiare posizione: `saving` appare soltanto quando viene inviata una posizione nuova e `saved` scompare dopo circa 5 secondi. Non devono apparire messaggi bloccati in stato `saving` se non c'è alcun nuovo salvataggio.
4. Simulare un errore audio con connessione interrotta: il messaggio persistente non deve essere nascosto da tentativi successivi finché una risposta server non conferma il salvataggio. Riprovare cambiando posizione e verificare che la conferma sostituisca l'errore.
5. Verificare i feedback con lettore schermo, tastiera, lingue IT/EN, connessione lenta, timeout, pagina chiusa e account non autorizzato. Nessun salvataggio locale viene spacciato per conferma backend.
6. Regressioni: lezioni video, quiz, completamento manuale, sidebar, allegati, progressi della dashboard. Verificare che i due messaggi non compaiano nelle lezioni bloccate da finestra o prerequisiti.
7. CI `tests/frontend-progress-feedback.py`, `node tests/frontend-progress-feedback.mjs` (successo/errore simulato e timer), PHP 8.3/MySQL, PHP 8.4/MariaDB, Chromium e pacchetto installabile; separare collaudo Joomla reale da CI verde.

**Accettazione manuale:** pendente, da eseguire sul sito di prova.


## FE-05 — I miei corsi come voce menu autonoma (prima tranche)

1. Dopo l'installazione creare nel gestore menu Joomla una voce **I miei corsi** (`mycourses`) e mantenere la voce già esistente `dashboard`: entrambe devono essere raggiungibili, con il nuovo link contestuale dalla dashboard.
2. Provare utente anonimo: nessun corso privato o storico deve essere esposto. Provare due utenti autenticati con iscrizioni differenti, verificando che progressi, ID e corsi non si mescolino.
3. Confrontare le schede di dashboard e mycourses: titoli, percentuali, accessibilità aula, ripresa e stati dello storico devono corrispondere. La nuova vista non deve caricare il blocco attestati né il consenso annunci, che rimangono temporaneamente nella dashboard.
4. Verificare filtri didattici `all/not_started/in_progress/completed`, paginazione separata da una voce all'altra, refresh, SEF attivo/disattivo, più voci menu e lingue IT/EN.
5. Provare corsi archiviati, iscrizioni sospese, accessi revocati o scaduti, corsi con lingua diversa e storico senza progresso applicabile. La scheda può rimanere nello storico, ma non deve concedere il pulsante Riprendi quando non disponibile.
6. Test responsive, zoom 200%, tastiera, CSS del template e override dei template dashboard/mycourses. Nessuna interferenza con moduli, articoli e menu Joomla.
7. Eseguire `python3 tests/frontend-my-courses.py`, CI completa con installazione e upgrade del package e verificare che i nuovi file `src/Model/MycoursesModel.php`, `src/View/Mycourses/HtmlView.php`, `tmpl/mycourses/default.php` e `default.xml` siano inclusi.

**Esito:** collaudo manuale Joomla pendente; la CI non costituisce accettazione FE-05.


## FE-09 — Catalogo pubblico docenti (PR #522)

**Stato:** PR #522 integrata, CI 4/4 verde (run 43940346249); pacchetto SHA-256 verificato. Accettazione Joomla manuale da registrare separatamente.

1. Menu → Nuova voce → JD Learn → Elenco docenti; provare IT/EN, SEF abilitato/disabilitato e più voci menu. Aprire ogni profilo dal catalogo.
2. Confrontare anonimo, utente autenticato con gruppi ACL differenti, docente pubblicato/non pubblicato/cestinato, categoria o antenati pubblicati e accessibili, lingue Joomla. Profilo non visibile deve restare 404 anche con URL diretto.
3. Pagina vuota, più di 20 profili per paginazione, foto assente/presente, alt decorativo, descrizione contenente HTML: nessuno script eseguibile, URL remoto o dato personale dell'account Joomla.
4. Provare profilo docente, corsi collegati, modifica profilo propria autorizzata e impossibilità di edit altrui; nessuna regressione ai filtri del catalogo corsi.
5. Ripetere con template differente, colonna laterale, smartphone, tastiera, zoom 200%, screen reader e override `com_jlms/teachers/default.php`.
6. Verificare l'inclusione nello ZIP dei file `TeachersModel.php`, `View/Teachers/HtmlView.php`, `tmpl/teachers/default.php`, `default.xml`; CI verde non sostituisce collaudo Joomla.


## FE-11 — Ereditarietà opzioni Joomla del catalogo e delle categorie

**Stato:** FE-11 PR #524 integrata, CI 4/4 verde (run 43944091496), pacchetto SHA-256 `8eb55d809ebb855a6a5537e0d864d3f93c426eacea922d46305e82ee0eb7b515`. Accettazione manuale Joomla pendente.

1. Impostare nelle opzioni globali JD Learn visibilità sottocategorie/corsi della categoria, ricerca/filtri e ordinamento del catalogo, dimensione pagina e ordinamento predefinito. Verificare che menu nuovi con valore Globale ereditino realmente le impostazioni.
2. Creare due voci menu che puntano a categorie differenti e impostare override contrastanti; verificare isolamento, URL SEF e non-SEF. Aprire poi una categoria figlia da un menu padre: le opzioni del padre non devono sovrascrivere quelle della figlia.
3. Nascondere filtro e ordinamento della categoria: il form non deve apparire, GET `filter[search]`, `filter[order]`, `filter[tag_id]`, `filter[teacher_id]` e `filter[pathway_id]` non devono riattivarlo o modificare i dati. Paginazione e ACL dei corsi rimangono operative.
4. Confrontare menu salvati prima della modifica e nuove installazioni; nessun cambio degli ACL di pubblicazione, lingua e categorizzazione. Provare anonimato, accessi Joomla differenti e lingue IT/EN.
5. Provare paginazioni indipendenti di corsi e sottocategorie, selezione ordinamento, filtro disattivo, override template e schermi stretti. Eseguire `python3 tests/frontend-menu-inheritance.py` e la CI completa.


## FE-12 — Gate integrato frontend 1.0.0

La matrice operativa, con 10 scenari e stato manuale **Da eseguire**, è mantenuta in [FRONTEND_FE12_ACCEPTANCE.md](FRONTEND_FE12_ACCEPTANCE.md). Gate automatico integrato con PR #526, CI 43946500653 4/4 verde e artefatto 14264109572. Gli esiti CI sono evidenze automatiche parziali, non accettazione WCAG, responsive, ACL o override in Joomla reale. Non chiudere FE-12 senza registrare per ogni caso ambiente, ruolo, lingua, evidenze e risultato.


### FE-12 — Controllo HTTP delle viste pubbliche (CI)

**Evidenza:** PR #528 integrata (merge `48418ddbf4dfa5449a4b8e008a94d64a75c1d224`); [CI 43952710335](https://github.com/jeckodevelopment/jd_learn/actions/runs/43952710335) 4/4 verde; [artefatto](https://github.com/jeckodevelopment/jd_learn/actions/runs/43952710335/artifacts/14269271284) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `f2043d79998e85fe62e2f0930f5c8e965726350029f78c61a7b68427ae7d8650`. Corretto HTTP 404 della vista `teachers` nell'allowlist `DisplayController`; verificati su Joomla disposable le viste `categories`, `pathways`, `teachers`, relativi markup e asset WAM. FE-12 resta aperto: 10 scenari manuali non eseguiti; avanzamento globale 79,56% invariato.

Verificare in `tests/http-smoke.py` che su installazione Joomla disposable siano renderizzate le viste `categories`, `pathways`, `teachers`: radice e contenitore, `h1`, CSS Web Asset Manager, nessuna presenza del contenuto lezioni protetto `SECRET_LESSON_BODY`. Ripetere sul sito di prova con menu SEF, IT/EN, template personalizzato e utenti differenti; un esito automatico verde non registra alcun collaudo manuale.


### P0 #532 — Safari: binding del trasporto metadata (patch successiva a #535)

La chiamata manuale POST a `lesson.metadata` ha restituito HTTP 200 con corpo JSON `success:true`, nonostante il `Content-Type: text/html`. La precedente UI invece mostrava `network_error` e non riportava la richiesta nel filtro Network. In `video-metadata.js` il `fetch` nativo era memorizzato in `this.transport` e invocato come metodo dell'editor: su WebKit il receiver non valido può impedire l'invio. Il wrapper senza receiver editor preserva il trasporto iniettato nei test e i controlli Joomla ACL/CSRF/consenso.

**Test automatico:** `node tests/video-metadata.mjs` verifica che il trasporto predefinito invochi `fetch` con contesto valido e raggiunga la fase di risposta, oltre ai test su trasporti simulati. **Da collaudare su Safari/Joomla reale:** dopo upgrade e cache pulita premere «Rileva di nuovo»; verificare POST in Network e stato/JSON, caricamento dell'adapter e player YouTube, durata acquisita, salvataggio e riproduzione frontend come studente autorizzato; verificare nuovamente CSP, consenso e ACL. Non dichiarare risolta l'issue #532 prima del test live. L'header MIME `text/html` non impedisce di per sé `Response.json()` e va indagato separatamente.

**Riconciliazione PR #537 (integrata):** merge `eb4cafa7b0df6aeffc726522eaea50b61c0bade8`; [CI 43970838888](https://github.com/jeckodevelopment/jd_learn/actions/runs/43970838888) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43970838888/artifacts/14279810203) `JD-Learn-0.57.7-dev` (digest dell'archivio Actions `sha256:3e7ec0b42645dcb7fdbb1f13cc96cd48eebad8f47c8a556acd88dd5b07591ca9`). Test reale Safari/editor e frontend **non ancora eseguito dopo PR #537**; issue #532 P0 non chiusa. Tracciabilità VID-02 aggiunta nel CSV canonico. La risposta del server ha body JSON valido ma MIME `text/html`: osservazione ancora da verificare separatamente.


## P0 YouTube #532 — regressione avvio contemporaneo PR #539

**P0 #532 — PR #539, in verifica CI / collaudo Joomla pendente (16/11/2026):** il frontend ora attende la conclusione della richiesta idempotente `lesson.begin` della stessa iscrizione/lezione prima di richiedere `video.playback`. La modifica mira a evitare scritture concorrenti su iscrizione/progresso e non cambia i calcoli di copertura o i privilegi. L'HTTP 500 live non è ancora attribuito con certezza a tale concorrenza; serve collaudo su Joomla e verifica separata di `video.save` e completamento. Avanzamento globale di riferimento 79,56% invariato.

1. Con studente iscritto alla versione pubblicata, aprire il frontend e verificare `lesson.begin` completato prima di `video.playback` senza HTTP 500, su Safari; ripetere dopo reload.
2. Simulare ritardo/errore transitorio del POST `lesson.begin`; assicurarsi che l'avvio video non crei una seconda transazione contemporanea e che il fallimento di `lesson.begin` rimanga visibile, senza attribuire credito video.
3. Verificare riproduzione di video nuovo pubblicato, `video.save` 200 `success:true`, `lesson_completed` solo dopo copertura reale pari alla soglia (default 90%); non accettare il solo evento `ended` né seek.
4. Verificare modalità consultazione, altro lesson ID, autoplay/consenso non aggirato, cambio pagina e rientro; verificare assenza di regressioni su testi, documenti e audio.
5. Se il 500 persiste, acquisire codice HTTP e `request_id` del task effettivo e log PHP/LiteSpeed prima di altre modifiche. Il merge CI non equivale a collaudo sito.


## Post-merge PR #539 — esito CI e pacchetto

**PR #539 integrata (P0 #532; merge `bebffec3eb01b3eead93d6a8e96833b5d49eec6e`):** CI verifica completa 4/4 verde [run 43980525575](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575), gate CSV [run 43980525067](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525067). [Artefatto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575/artifacts/14282058958), digest dell'archivio GitHub Actions `sha256:3f87d34fd71960a20bfc3adbf75d3a523c04c6c0f62eff795be57a95811c1713` (NON checksum dello ZIP Joomla interno). Il frontend serializza `lesson.begin` e `video.playback` per la stessa lezione/iscrizione; test automatici verificati. **Il collaudo reale su installazione Joomla di prova dell'avvio e del completamento video è pendente; issue #532 rimane aperta.** Avanzamento globale 79,56% invariato.


## P0 #532 — handshake frontend YouTube IFrame API, PR #541

**P0 #532 — race YouTube IFrame API (PR #541, CI e test live pendenti):** l'adapter collegava `YT.Player` solo dopo `iframe.onload`, evento di navigazione della pagina YouTube e non garanzia di handshake dell'IFrame API. Ora la API viene collegata subito dopo l'inserimento dell'iframe nel DOM, prima del completamento della navigazione; referrer policy, dominio `youtube-nocookie.com`, ID video, consenso e telemetria restano invariati. Evidenza: l'utente vede il poster, Play resta su spinner in Safari e Chrome; YouTube diretto funziona. L'esito reale della modifica deve essere confermato sul sito di prova. Avanzamento globale 79,56% invariato.

1. Aggiornare il pacchetto sul sito Joomla di prova e pulire la cache asset. Aprire lezione video pubblicata `fcnDmrtj6Sk` da studente iscritto in Safari e Chrome: poster/iframe, click Play, flusso audio/video effettivo e passaggio a stato playing; nessuno spinner persistente.
2. Verificare che una sola inizializzazione IFrame API avvenga, senza attendere `iframe.onload`; referrer policy `strict-origin-when-cross-origin` e host `youtube-nocookie.com` mantenuti, senza bypass di consenso.
3. Lasciar riprodurre il video senza seek fino alla soglia della lezione: controllare `video.save`, conteggio copertura effettiva e completamento; ripetere pausa/ripresa e rientro aula. Nessun credito per seek, buffering, poster o solo evento ended.
4. Se compare ancora spinner, raccogliere evento onError YouTube e richiesta iframe Referer effettivo: non attribuire il risultato all'API senza log live.


## Post-merge PR #541 — esito CI e artefatto

**PR #541 integrata — P0 #532:** merge `2ab0ffa2589a22013a26b2ad9ee7bcefe11c3fd6`; CI 4/4 verde [run 43983276857](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857), integrità CSV [run 43983276867](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276867). [Pacchetto installabile JD Learn 0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857/artifacts/14283797967), digest archivio Actions `sha256:f9feaf56a8d380d2ee0c101304b243613c10402bd57554cfe2ef2e1878fcc24c` (non checksum interno del pacchetto ZIP Joomla). **Riproduzione video e completamento da verificare manualmente su installazione Joomla di prova prima di chiudere issue #532**. Avanzamento globale 79,56%, invariato.


## P0 #532 — consenso e delega riproduzione iframe YouTube, PR #543

**P0 #532 — permesso riproduzione IFrame YouTube (PR #543, collaudo installazione Joomla di prova pendente):** il player JD Learn dichiarava `allow="encrypted-media; fullscreen; picture-in-picture"`, omettendo `autoplay` richiesto dagli esempi ufficiali Google per gli iframe YouTube. La configurazione ora delega il permesso `autoplay` all'iframe, senza attivare autoplay automatico (il parametro resta `autoplay=0`); SDK, consenso, `Referer`, ACL e calcolo dei progressi invariati. Test di regressione sull'attributo `allow` e sul parametro autoplay. L'eventuale blocco residuo va diagnosticato con evidenza dell'evento YouTube, non con ulteriori modifiche speculative. Percentuale globale 79,56% invariata.

1. Installare il pacchetto su Joomla di prova; verificare iframe `allow` contenente `autoplay` e URL embed con `autoplay=0`, nessuna partenza senza consenso e senza Play.
2. Premere Play in Chrome e Safari sulla stessa lezione; verificare che audio/video si riproducano normalmente (non solo poster o spinner), monitorando eventuali messaggi provider.
3. Dopo riproduzione reale, verificare `video.save`, copertura cumulata e completamento solo dopo soglia; testare pausa/ripresa, salto in avanti, cambio lezione e stato storico.
4. Se persiste spinner, acquisire evento YouTube IFrame API `onError`/`onStateChange` e header Referer dell'iframe in contesto pagina senza divulgare cookie/token.


## Post-merge PR #543 — build installabile e QA reale pendente

**PR #543 integrata (P0 YouTube #532):** merge `a695e4c24179b1933c3856c65f802baab1ab7deb`; CI completa 4/4 verde [run 43984520221](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221), gate CSV verde [run 43984520239](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520239). [Pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221/artifacts/14285480650); digest dell'archivio Actions `sha256:21074ebe6c878571a47e907e05926edd7598d168b6a6b97f742daa1def8c78ad` (non checksum dello ZIP Joomla interno). Aggiunto il permesso `autoplay` all'iframe YouTube ma confermato `autoplay=0`: serve un click utente, consenso preservato. **La riproduzione e il completamento su installazione Joomla di prova sono da collaudare, issue #532 aperta**. Avanzamento 79,56% invariato.


## P0 — completamento video e coda email (17/11/2026)

**MAIL P0 — completamento video (PR in corso, 17/11/2026):** la notifica `com_jlms.course.completed` generata da `VideoProgressService` non forniva `COMPLETION_DATE` e `DASHBOARD_URL`, nonostante il modello standard Joomla li richieda. Corretto il produttore affinché fornisca entrambi i campi (data reale del completamento persistita e URL autenticato della dashboard), con test regressione. Il messaggio di iscrizione `com_jlms.enrollment.confirmed` presente nello screenshot è un evento distinto: il relativo produttore fornisce già i tag canonici, e la causa va verificata sul modello effettivo `#__mail_templates` personalizzato dell'installazione, evitando di sovrascrivere template amministrativi o reintrodurre alias. L'emissione attestati dipende dalle regole immutabili della versione pubblicata, dal consenso configurazione `certificate_enabled` globale/per corso e dalla presenza di un modello attestato attivo; verificare le precondizioni prima di parlare di regressione. Avanzamento 79,56% invariato.

1. Pubblicare una versione del corso con regole di completamento video e template mail canonico `com_jlms.course.completed`. Completare effettivamente la lezione e il corso; verificare che la nuova riga in Coda email non fallisca per assenza di `COMPLETION_DATE` o `DASHBOARD_URL`, e che le date siano quelle del completamento reale.
2. Per un messaggio `com_jlms.enrollment.confirmed` già fallito, confrontare soggetto/testo/HTML del modello nativo Joomla realmente salvato in Sistema → Modelli email (lingua predefinita e traduzione) con i tag canonici `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME`, `COURSE_URL`, `ENROLLMENT_DATE`. Non introdurre alias legacy; preservare le personalizzazioni e correggere esplicitamente i placeholder non supportati.
3. Il record `failed` non viene reinviato automaticamente: non impostarlo su `sent` senza prova SMTP; prevedere una nuova iscrizione di prova dopo la correzione del modello, oppure una funzione esplicita di riaccodamento sicuro oggetto di PR dedicata.
4. Distinguere corso didatticamente completato da attestato emesso: controllare `certificate_enabled` nel rules JSON della versione pubblicata, abilitazione globale/per corso e presenza di almeno un template attestato attivo. Verificare in lista attestati gli stati `pending_render` e `ready`; non emettere certificati per corsi senza requisiti.


## MAIL-10 — dettaglio diagnostico modelli email in Coda (issue #547)

**MAIL-10 — issue #547 (PR #548, test sito Joomla pendente):** nella Coda email amministrativa, per messaggi `failed`/`retry` classificati come errore modello, JD Learn ora rilegge in sola lettura il template Joomla effettivo della lingua della riga e mostra se è assente oppure i **nomi** dei tag canonici non disponibili (massimo 12). Non espone indirizzi, valori delle variabili, dati SMTP, token o eccezioni. Non riscrive i modelli personalizzati, non introduce alias legacy, non modifica lo stato della coda né riavvia gli invii. Verificare anche le varianti lingua e correggere esplicitamente i template obsoleti.

1. Preparare un template `com_jlms.enrollment.confirmed` personalizzato con `{NAME}` obsoleto, un messaggio fallito con dati reali e un amministratore `core.manage`: la Coda mostra «Variabili non disponibili» con il solo nome `NAME`, senza valori email o SMTP. Verificare lingua `it-IT` e fallback `*`.
2. Testare l'assenza del template Joomla: mostra messaggio «Modello Joomla non trovato»; se il contesto non è leggibile resta solo categoria generica.
3. Testare trasporto SMTP fallito: nessuna diagnostica su nomi di token. Verificare XSS/escaping di nomi e ACL: studenti e amministratori senza `core.manage` non devono accedere alla Coda.
4. Dopo correzione manuale del modello in Sistema → Modelli email, verificare nuova notifica di iscrizione; il record storico `failed` non va reinviato automaticamente.


## P0 #546 — completamento senza attestato, diagnosi amministrativa

**CERT-06 / P0 #546 — diagnosi attestati mancanti (PR #549, CI e collaudo Joomla pendenti):** nuova sezione in JD Learn → Attestati, accessibile solo con `core.manage` e `jlms.certificates.manage`, che mostra in sola lettura le ultime 25 iscrizioni realmente completate senza emissione completa. Espone la causa: regole della versione pubblicata senza rilascio attestati, preferenze globali/corso disabilitate, modello attestato assente, configurazione invalida oppure emissione attesa ma assente/documento non collegato. Nessuna emissione automatica retroattiva, modifica delle regole storiche, invio email o variazione della percentuale di completamento. Test nativi Joomla su ACL, limiti e categorie di esito. Avanzamento globale 79,56% invariato.

1. Completare un corso Rigoletto senza `certificate_enabled` nella versione pubblicata: nella sezione 'Corsi completati senza attestato' della vista Attestati deve comparire il corso con ragione 'rilascio non previsto dalle regole della versione'. Non creare attestati automaticamente.
2. Abilitare il rilascio nella bozza di una nuova versione e pubblicarla: non alterare iscrizioni storiche; testare un nuovo completamento con regole congelate corrette, preferenze globali/corso e modello attestato attivo.
3. Per un corso completato e abilitato, simulare assenza attestato e verificare diagnosi 'Emissione attesa ma assente' oppure 'Emissione presente senza documento', senza rettifiche automatiche.
4. ACL: anonimato o amministratori senza `jlms.certificates.manage` non possono leggere le diagnosi. Mostrare massimo 25 iscrizioni; nessuna comunicazione a studenti e nessuna nuova riga di certificato durante la visita della pagina.
5. Verificare in Joomla il percorso di download del PDF `pending_render`/`ready` per gli attestati realmente emessi, non confondere PDF non ancora renderizzato con emissione mancante.


## Riconciliazione post-merge mail e attestati — 17 novembre 2026

- **PR #545 (mail completamento video)** integrata al commit `f0415c45dd512797c4f5882d97d430c58bdc29b0`; CI 4/4 verde [run 44045664274](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274), gate CSV [run 44045664187](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664187), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274/artifacts/14311024216). Token `COMPLETION_DATE` e `DASHBOARD_URL` ora disponibili nell'evento di completamento generato da `VideoProgressService`. Collaudo Joomla/SMTP ancora da eseguire.
- **PR #548 (diagnostica template mail, issue #547)** integrata al commit `12edaa91c2ba95e0ea159d897b6f9cabcb9d304e`; CI 4/4 verde [run 44046787217](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217), gate CSV [run 44046787244](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787244), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217/artifacts/14311582918). Diagnostica admin dei token non risolti/assenza modello senza esporre contenuto delle email. Il modello `com_jlms.enrollment.confirmed` fallito in installazione Joomla di prova richiede controllo e correzione nell'installazione; nessun invio storico riaccodato automaticamente.
- **PR #549 (diagnostica attestati, issue #546)** integrata al commit `013267b788e2033fa5f11ef89bdf3b284c8d47d1`; CI 4/4 verde [run 44048059581](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581), gate CSV [run 44048060095](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048060095), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581/artifacts/14311889571). Lista protetta e limitata delle iscrizioni completate senza attestato, con diagnosi dei gate; nessuna emissione retroattiva forzata. Collaudo Joomla e causa precisa del corso Rigoletto ancora pendenti.

**Accettazione manuale:** installare l'artefatto più recente (#549), verificare nel backend Attestati il motivo per Rigoletto, in Coda email ID 5 il nome del token errato o modello mancante, correggere in Joomla senza alias e provare invio di nuova iscrizione; verificare una nuova notifica di corso completato dalla lezione video. Senza riscontro non dichiarare funzionalità accettate.


## MAIL-10 #547 — recupero manuale dopo correzione del modello (PR #551)

**MAIL-10 / issue #547 — retry manuale controllato (PR #551, CI e collaudo Joomla pendenti):** le sole notifiche `com_jlms.enrollment.confirmed` e `com_jlms.course.completed` in stato `failed` con categoria errore di validazione modello **prima dell'SMTP** possono essere riportate a `retry` dall'amministratore autorizzato, con conferma esplicita e nota audit (10–1000 caratteri), dopo la riparazione del template Joomla. Prima del cambio stato il server controlla template corrente, lingua, dati, nomi dei token e recapito; aggiornamento SQL atomicamente condizionato a stato/errore/tentativi. Non ritentare errori di trasporto, invii incerti, `sending` non riconciliati, record esauriti o altre categorie. Il task di invio Joomla resta l'unico mittente, nessun invio immediato dal pulsante. Avanzamento 79,56% invariato.

1. Produrre evento `com_jlms.enrollment.confirmed` in stato failed per token `{NAME}` obsoleto: aprire Coda email da amministratore autorizzato, correggere il modello Joomla con token canonici; usare l'azione «Ritenta dopo correzione del modello», inserendo nota >=10 caratteri, spuntando conferma. Verificare update `failed` -> `retry` e che solo task Joomla invii, registrando `sent` dopo accettazione SMTP.
2. Provare senza correggere il modello o con template mancante: risposta controllata «modello ancora non valido», record failed invariato e nessuna chiamata SMTP.
3. Rifiutare `retry` da messaggi failed per errore trasporto o da altri template/snapshot; non consentire requeue dei casi `sending` se non con la procedura stale separata. Verificare massimo 5 tentativi, race di due amministratori, ACL e CSRF.
4. Verificare che la correzione non alteri contenuti personalizzati, lingua, destinatario né regole didattiche; niente duplicazione per retry concorrenti.


## Post-merge PR #551 — retry controllato dopo riparazione modello email

**PR #551 integrata — issue #547:** merge `2e9a65ab8d9044f9502950514c0a24419d04d03b`, CI completa 4/4 verde [run 44049557136](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136), gate CSV verde [run 44049557129](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557129); [pacchetto installabile JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136/artifacts/14312389981), digest archivio Actions `sha256:01ebeb70fae56e88caa1cbe54ae5d4f6eb7d408e824b8a0a94d7167dfa32da25` (non checksum interno dello ZIP Joomla). Il ritentativo esplicito è possibile solo dopo riparazione di template per iscrizione e completamento con errore validato pre-SMTP, nota e conferma, mai per trasporto incerto; collaudo Joomla/SMTP e correzione reale del modello Rigoletto rimangono pendenti. Avanzamento globale 79,56% invariato.

**Verifica da fare su installazione Joomla di prova:** installare il pacchetto dell'ultima CI, aprire Coda email ID 5, leggere il nome del token non disponibile, correggere il template in Joomla mantenendo l'eventuale personalizzazione; se la notifica è ancora pertinente usare l'azione di ritentativo con nota e conferma, verificando il passaggio a retry e la spedizione UNA sola volta dal task schedulato. Non considerare il rilascio accettato senza questa prova.


### Attestati e snapshot della versione didattica (issue #553)

- Abilitare l'emissione nelle opzioni globali e lasciare il corso su «Usa globali»; pubblicare una **nuova** versione didattica, iscrivere un utente e completare le lezioni. Verificare che la versione pubblicata contenga `certificate_enabled=true`, che l'attestato venga registrato, reso disponibile e che la notifica sia accodata.
- Ripetere con globale OFF, override del corso su abilitato e su disabilitato; verificare lo snapshot risolto al momento della pubblicazione, anche dopo la modifica delle preferenze.
- Verificare che le versioni pubblicate **prima** della correzione non vengano riscritte dall'upgrade e che i completamenti storici senza attestato non generino emissioni automatiche. Un eventuale recupero deve essere autorizzato e auditabile separatamente.
- Verificare che i corsi in modalità consultazione non emettano attestati.


### Recupero attestati per completamenti storici (issue #553)

In JD Learn → Attestati, verificare la sezione dei completamenti privi di attestato. Su un completamento di una versione pubblicata prima della correzione con `certificate_enabled=false`, configurare le preferenze globali/per corso per consentire l'emissione; inserire una motivazione di 8–500 caratteri, confermare e premere «Emetti attestato». Verificare record e documento, PDF accessibile, notifica accodata e audit con operatore, motivazione, versione e iscrizione. La versione didattica non deve essere alterata. Ripetere la richiesta: non deve essere emesso un secondo attestato. Verificare POST/CSRF, ACL, modalità consultazione, completamento assente, preferenze OFF, mancanza template e rollback se audit fallisce. Il recupero non deve partire automaticamente durante l'upgrade.


### Regressione emissione manuale attestato storico (#558)

Su un completamento storico idoneo con preferenze attestati abilitate, verificare il pulsante di emissione amministrativa con motivazione e conferma. Atteso: nessun HTTP 500, un solo record attestato e documento collegato, audit registrato, versione didattica immutata. Ripetere la richiesta e verificare che il secondo tentativo sia respinto senza duplicazioni. Il percorso positivo viene eseguito anche nella suite Joomla `tests/joomla-teaching-rules.php` su MySQL e MariaDB.


### PDF attestato storico: primo download (`pending_render`, issue #560)

Dopo l'emissione amministrativa di un attestato storico, verificare che il documento parta da `pending_render`. Accedere come titolare e scaricare il PDF: la risposta deve essere PDF valido, il documento deve diventare `ready` con asset privato persistito e il secondo download deve utilizzare lo stesso asset. Ripetere con data di completamento SQL `DATETIME(6)` con microsecondi e senza microsecondi, e con corso archiviato. Con storage privato assente/non scrivibile, verificare errore 503 senza creazione di file nel webroot e con indicazione operativa in amministrazione; configurare `material_storage_root` come directory assoluta esterna al document root e scrivibile da PHP. Non registrare né diffondere percorsi o domini reali di installazioni nei report pubblici.


### Storage gestito per hosting condiviso (issue #562, PR #563)

1. Su nuova installazione o upgrade con campo root materiali vuoto, emettere un attestato e scaricarlo dal frontend: deve passare da `pending_render` a `ready`, file PDF scaricabile e successivo download senza duplicazione.
2. Verificare cartella auto-creata `media/com_jlms/private`, con `.htaccess` e `index.php` di blocco accesso diretto e documenti salvati cifrati in file `.php` opachi; non devono essere presenti file `.pdf` pubblicamente servibili. Provare URL diretto ai file via HTTP: nessun contenuto dell'attestato deve essere rivelato anche con web server che ignora `.htaccess`.
3. Sia con Apache/LiteSpeed sia con Nginx+PHP verificare protezione e accesso esclusivamente attraverso controller autorizzato.
4. Prove negative: root sostituita con symlink, directory senza permessi, contenuto cifrato manomesso, configurazione segreta Joomla modificata: rifiuto senza consegna di dati o conversione in storage pubblico.
5. Opzione avanzata: storage privato esterno preconfigurato mantiene compatibilità file `.blob`; backup deve comprendere database, `media/com_jlms/private` e secret del file Joomla `configuration.php`.


### Regressione 503 attestato in attesa PDF su hosting condiviso (#564, PR #565)

Aggiornare JD Learn, lasciare il percorso di archiviazione personalizzato vuoto e usare un attestato già emesso `pending_render` su un hosting con `open_basedir` o temp di sistema non accessibile: scaricare dal frontend come titolare. Il PDF deve essere generato direttamente nello storage protetto, stato `ready`, secondo download invariato. Non deve essere creato alcun PDF temporaneo non cifrato nella webroot o nella directory PHP globale. Se ancora 503, consultare il log Joomla nella categoria `com_jlms` e rilevare esclusivamente la fase `logo`, `pdf`, `storage` o `database`, senza pubblicare dati personali, percorsi o chiavi. Verificare logo configurato come JPEG valido e cartella gestita scrivibile. Testare inoltre rigenerazione tecnica e due database.

### Collaudo attestati con logo PNG e recupero di PDF in attesa — PR #566 integrata

**Esito collaudo utente (17/11/2026, Joomla 6.2.0):** dopo l'installazione dell'artefatto della [PR #566](https://github.com/jeckodevelopment/jd_learn/pull/566), confermato il download funzionante dell'attestato preesistente bloccato da HTTP 503 con logo PNG. Diagnosi originale log: `phase=logo`, runtime `error_code=503`. Merge `81df4e888e12b48a6ac25705afa5e18d71cf2c67`; [CI 4/4 verde](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701); [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701/artifacts/14338442764). **Non ancora documentate come verificate:** conferma backend dello stato `ready`, immutabilità seriale e snapshot mediante confronto, altri formati e trasparenza, ACL/HEAD, backup/ripristino e recupero di completamenti senza emissione.


1. Installare aggiornamento senza disinstallare il componente. Conservare un certificato già emesso con stato **In attesa PDF** e logo PNG congelato nel suo snapshot.
2. Accedere come studente proprietario e scaricare. Verificare passaggio a **Pronto**, PDF integro, logo leggibile e codice/data/identità invariati; ripetere il download senza emissione duplicata.
3. Provare nuovi loghi JPEG, PNG, GIF, WebP e AVIF (ultimi secondo GD PHP), includendo PNG trasparente; verificare sfondo bianco, layout e PDF corretti.
4. Provare immagine inesistente, formato non consentito e utente diverso: errore controllato senza fuga di contenuti; i documenti validi non devono essere alterati.
5. Per iscrizione completata senza alcun record attestato, usare **Attestati → Corsi completati senza attestato**, distinguendo cause configurative e recupero storico esplicito; non duplicare i record pending.
6. Controllare log `com_jlms`, audit e compatibilità storage; eseguire `php tests/certificate-raster-logos.php` in CI con GD.

### CERT — recupero puntuale delle iscrizioni completate senza emissione

1. Pubblicare un corso di prova in modalità apprendimento con attestati abilitati; creare un'iscrizione completata **senza record certificato** (fixture controllata). In Attestati → Corsi completati senza attestato, la diagnosi deve essere **Emissione attesa ma assente** e deve apparire il pulsante **Recupera attestato mancante**.
2. Inserire motivazione (almeno 8 caratteri), confermare e inviare. Verificare una sola emissione, data completamento originale, record documento `pending_render`, audit `missing_issuance_recovery`, quindi download frontend → `ready`.
3. Ripetere il POST identico e verificare HTTP 409 e nessun duplicato; provare studente anonimo o amministratore senza permesso, token CSRF assente, motivazione insufficiente, iscrizione non completata, regola `certificate_enabled=false`, preferenze disattivate o template assente.
4. Un documento già emesso `pending_render` deve rimanere escluso dalla lista: il suo download deve generare solo il PDF originale senza una seconda emissione.
5. Il recupero eccezionale di una versione storica con attestati disattivati resta distinto, con motivazione e conferma. Nessun recupero automatico o bulk.

## REL-03 / issue #379 — metadati dei manifest e nomi in Gestione estensioni

Eseguire aggiornamento tramite pacchetto JD Learn e controllare in **Sistema → Gestisci → Estensioni** che le 11 estensioni figlie (plugin, libreria e componente) e il package abbiano identica versione e data `creationDate` (visibile in colonna Data) e i nomi uniformi `JD Learn - ...`. Controllare installazione pulita, upgrade dalla precedente build, assenza di duplicati e funzionalità dei provider. La CI verifica gli ZIP interni con `tests/extension-manifest-metadata.py`, ma il risultato visualizzato dalla gestione Joomla è un collaudo manuale ancora da eseguire. Procedura dettagliata in `docs/RELEASE_METADATA.md`.

### Modalità didattica YouTube — collaudo in Joomla

1. In una lezione YouTube aprire l'aula: URL iframe su `youtube-nocookie.com`, `rel=0`; comandi nativi YouTube (volume, sottotitoli, pausa, barra, fullscreen) funzionanti e nessun overlay che copra il provider.
2. Completare la visione valida secondo la soglia didattica, attendere il salvataggio del progresso: soltanto dopo il riconoscimento server `lesson_completed=true` il riquadro esterno mostra **Lezione video completata**. Se disponibile, deve offrire **Lezione successiva** con URL Joomla corretto.
3. Testare lezione successiva non accessibile, lezione terminale, versione consultazione/non tracciata, preview, errore/offline e consenso revocato: nessuna navigazione verso contenuti bloccati e nessuna falsa dichiarazione di completamento.
4. Riprendere la riproduzione o interrompere/revocare il consenso: il riquadro si nasconde. Verificare progressi, copertura e resume invariati e che YouTube possa ancora mostrare consigli all'interno del suo iframe, non sopprimibili da JD Learn.
5. Verificare l'evento `ended` anche dopo un rinnovo della sessione/provider; nessuna regressione nel player HTML5/Vimeo/Cloudflare Stream. Test automatico `node tests/video-player.mjs`.

### P0 — verifica ripristino player dopo rollback PR #571 (PR #572)

1. Aggiornare senza disinstallare JD Learn. Aprire due lezioni YouTube, una mai vista e una già completata; verificare che **entrambe** mostrino il player e che premendo Play inizino a riprodurre il video.
2. Verificare pausa, fullscreen, sottotitoli, seek, salvataggio progresso e ripresa su Chrome e Safari dove disponibili, sia con nuovo grant sia dopo ricaricamento pagina.
3. Completare un video e verificare il riquadro nativo **sotto** al player (comportamento noto della PR #570); non deve apparire una schermata in luogo dell'iframe.
4. Verificare consenso negato/revocato e rinnovo sessione: niente riproduzione senza autorizzazione; nessun errore JS o regressione su Vimeo, HTML5 e Cloudflare Stream.
5. Registrare come collaudo positivo solo dopo conferma effettiva sul sito Joomla. La regressione rimane priorità P0 fino al riscontro.

### P0 — riproduzione di video già completati

1. Con un utente iscritto completare una lezione video YouTube, controllando la conferma server e il mantenimento del completamento.
2. Uscire dall'aula e riaprire la stessa lezione: il player deve restare visibile, consentire Play e riprodurre **dall'inizio**, senza lampo seguito da schermata nera e senza seek automatico alla fine.
3. Ripetere il test dopo ricaricamento pagina e, dove disponibili, su Chrome e Safari; pausa, avanzamento manuale e replay devono continuare a funzionare.
4. Verificare che progresso storico, copertura, stato completato, numero di lezioni completate, eventuali attestati ed email già emessi rimangano invariati alla sola riapertura; nessun doppio completamento.
5. Aprire una **diversa** lezione soltanto parzialmente vista: il recupero della posizione salvata deve ancora funzionare. Verificare anche una lezione mai vista, per evitare regressioni sull'avvio.
6. Controllare il messaggio finale sotto il player, consenso e gli altri provider. Non chiudere la regressione P0 sulla sola base della CI simulata.

### Esito collaudo P0 — replay lezioni completate, PR #573 (17/11/2026)

**Esito positivo confermato dall'utente sul sito Joomla reale dopo la PR #573:** la lezione YouTube già completata si riapre e il video riparte regolarmente dall'inizio, senza la schermata nera precedentemente segnalata. La CI associata alla PR #573 è verde; il nuovo test frontend distingue esplicitamente `lesson_completed=true` (nessun seek automatico) da lezione parziale (seek salvato). La conferma copre questa regressione specifica, non costituisce collaudo integrale di tutti i provider, browser e contratti didattici.

### LCM-03 — archiviazione dei percorsi formativi

1. Con `jlms.pathways.manage`, `core.edit` e `core.edit.state`, aprire un percorso pubblicato con due corsi (uno eventualmente già frequentato), impostare **Archiviato** e salvare. Controllare stato in elenco.
2. Verificare come ospite e studente che percorso e dettaglio via URL diretto non siano più pubblici (404) e non espongano dati.
3. Confermare che i corsi del percorso mantengano gli stati autonomi, le iscrizioni e gli eventuali attestati; le associazioni rimangano registrate.
4. Passare ad **Non pubblicato**, controllare che il percorso resti invisibile, poi pubblicarlo nuovamente e verificare ordine e contenuti originari.
5. Con un ruolo senza `core.edit.state` verificare l'impossibilità di cambiare stato; nessuna transizione tramite POST diretto.
6. La cestinazione e l'eliminazione condizionata sono **ancora escluse** da questa tranche LCM-03. Accettazione manuale Joomla distinta dalla suite automatica.

### LCM-03 — cestinazione e ripristino conservativo dei percorsi (PR successiva a #575)

1. Aprire un percorso pubblicato e scegliere Stato → Cestinato. Verificare che scompaia dall'elenco ordinario e dal catalogo pubblico, compresi gli URL diretti.
2. Nell'elenco backend selezionare il filtro Cestinato: deve comparire il percorso, modificabile dai ruoli autorizzati.
3. Verificare nel percorso cestinato l'integrità dell'ordine dei corsi, dei record iscrizione/progresso e degli attestati storici; i corsi restano autonomi.
4. Tentare una transizione diretta da Cestinato a Pubblicato: deve essere rifiutata. Ripristinare a Non pubblicato: non deve comparire nel catalogo. Pubblicarlo successivamente e verificare la ricomparsa.
5. Un account senza `core.edit.state` non può cestinare/ripristinare, neppure tramite POST. Eseguire anche il controllo dopo upgrade Joomla.
6. Restano fuori da questa PR le azioni massive e la cancellazione definitiva condizionata.

### LCM-03 — selezioni multiple e cancellazione definitiva dei percorsi

1. Selezionare due percorsi in elenco: Cestina deve spostarli entrambi. Filtrare Cestinati, selezionarli e scegliere Ripristina; entrambi tornano Non pubblicati.
2. Con un account privo di `core.edit.state` o di permesso su uno dei due percorsi, tentare l'azione via POST con token valido: nessuna riga deve cambiare; token non valido e GET devono essere rifiutati.
3. Verificare che Pubblica direttamente su un percorso Cestinato sia vietato anche via POST, e che dopo Ripristina la pubblicazione esplicita funzioni.
4. Cestinare un percorso con corsi associati e provare Elimina definitivamente: deve essere bloccato senza perdere percorso, associazioni, iscrizioni, frequenza o attestati.
5. Creare e cestinare un percorso vuoto, selezionarne **uno solo** e confermare Elimina definitivamente: il percorso può essere eliminato; gli altri record non devono essere alterati. Tentare due selezioni e un ID inesistente, con esito negato.
6. Verificare controllo ACL `core.delete`, dialogo di conferma, visualizzazione delle etichette IT/EN, audit e integrità dopo upgrade. La suite automatica non sostituisce questo collaudo Joomla reale.

### LCM-04 — cestinazione e ripristino dei docenti

1. Selezionare due docenti, uno associato a un account Joomla e almeno uno assegnato a un corso; cestinare in massa.
2. Verificare che scompaiano dalla directory pubblica e dall'elenco amministrativo ordinario; nel filtro Cestinati devono rimanere presenti.
3. Verificare che gli account Joomla non vengano bloccati o eliminati e che le associazioni ai corsi e gli snapshot di versioni già pubblicate siano invariati.
4. Ripristinare entrambi: devono tornare Non pubblicati. La pubblicazione diretta dal cestino deve essere negata; pubblicare esplicitamente solo dopo il ripristino.
5. Con ruolo senza permesso `core.edit.state` o senza visibilità sul singolo docente, verificare il rifiuto di chiamate POST dirette e nessun aggiornamento parziale; GET e CSRF invalido devono essere respinti.
6. Eseguire dopo upgrade su Joomla reale. Non registrare esito positivo finché il collaudo non sarà svolto.

### LCM-04 — eliminazione condizionata dei docenti (collaudo differito)

1. Cestinare un profilo docente ancora assegnato a un corso; tentare Elimina definitivamente: deve essere negato con motivazione e senza modifiche.
2. Verificare lo stesso blocco quando l'assegnazione corrente è stata rimossa ma il docente è ancora presente nello snapshot di una versione didattica pubblicata.
3. Creare un docente di prova privo di associazioni e di versioni storiche che lo menzionino; cestinarlo, confermare l'eliminazione singola e verificare la rimozione del solo profilo.
4. Controllare che l'account Joomla collegato non sia eliminato, disattivato o modificato e che iscrizioni, attestati, snapshot e altri profili restino intatti.
5. Tentare operazioni dirette via GET, senza token, con più ID, con profilo non cestinato e senza `core.delete`: nessuna eliminazione.
6. Verificare compatibilità PHP 8.3/MySQL e PHP 8.4/MariaDB, messaggi IT/EN, checkout, cache e riferimenti di integrazioni esterne. **L'utente ha rinviato i collaudi reali: non segnare questa voce come accettata.**

## LCM-05 — regressioni automatiche isolamento percorsi (17/11/2026)

Il test nativo `tests/joomla-pathways.php` estende i controlli del ciclo di vita su percorsi pubblicati: i dettagli diretti di un percorso archiviato o cestinato devono restituire 404 anche per un visitatore anonimo, non soltanto per l'operatore autenticato. Il confronto dei record `#__jlms_courses` prima/dopo archiviazione, cestinazione ed eliminazione di un percorso vuoto dimostra che tali operazioni non modificano gli stati dei corsi associati. Il test esistente continua a verificare relazioni e iscrizioni.

**Comportamenti da collaudare su Joomla (rinviati):** aprire l'URL diretto di un percorso archiviato/cestinato come visitatore e come iscritto; verificare 404, assenza nel catalogo, invariabilità degli stati dei corsi condivisi e loro fruibilità autonoma quando pubblicati. Verificare inoltre controlli ACL, multilingua, Finder e SEO. Questi test aggiunti non chiudono LCM-05 e non dimostrano la compatibilità di integrazioni esterne, né costituiscono accettazione manuale.

## LCM-05 — negazione server-side delle mutazioni dei percorsi

Il test Joomla `tests/joomla-pathways.php` prova ora un attore anonimo contro `changeStates()` e `purgeEmpty()` con ID di percorsi realmente cestinati. Entrambe le operazioni devono essere negate; gli stati di entrambi i percorsi e le associazioni ai corsi devono rimanere immutati. Il test ripristina l'identità dell'operatore CI prima delle operazioni legittime successive. **Comportamenti da collaudare manualmente (rinviati):** POST diretti con ruoli non autorizzati, token CSRF assente/invalido, permessi ACL specifici di record, conferma per eliminazione, rollback e successiva operazione amministrativa autorizzata. Il solo test del modello non attesta l'intero gate HTTP/CSRF.

## LCM-05 — test HTTP negativi delle transizioni percorso (17/11/2026)

`tests/http-smoke.py` verifica tramite sessione amministrativa Joomla disposable i sei endpoint `pathway.trash`, `pathway.restore`, `pathway.archive`, `pathway.publish`, `pathway.unpublish` e `pathway.purge`: richieste GET devono risultare 405 e richieste POST prive di token CSRF devono risultare 403. Dopo i rifiuti il percorso resta presente nella lista amministrativa. La prova è HTTP automatica, non accettazione manuale.

**Comportamenti da collaudare (rinviati):** transizioni legittime con token corretto, token alterato/scaduto, permessi ACL per record e per selezioni miste, conferma dell'eliminazione, controllo dei dati prima e dopo ciascuna operazione, test di regressione con diverse configurazioni hosting. Rimangono anche gli audit Finder/SEO e la verifica reale Joomla.

## LCM-05 — test HTTP di transizioni autorizzate sui percorsi (17/11/2026)

`tests/http-smoke.py` recupera un token CSRF dalla lista amministrativa nativa Joomla e invia POST reali, autenticati, per il ciclo `archive → restore → publish` di un percorso usato nel fixture. Dopo l'archiviazione verifica che il percorso scompaia dal catalogo pubblico; dopo il ripristino deve rimanere non pubblicato; soltanto una pubblicazione esplicita lo rende nuovamente visibile. Il test non usa token sintetici né bypass dei controller.

**Comportamenti da testare su Joomla reale (rinviati):** permessi ACL specifici per singolo percorso e selezioni miste, POST con token scaduto/alterato, conservazione integrale di dati storici e ricerca Finder/SEO, conflitti e race; il test CI non equivale ad accettazione manuale.


## UI-01 / FE-10 — collaudi anteprima lezione e revoca consenso

Gate automatico: `python3 tests/frontend-contract-hooks.py` controlla radici DOM, corrispondenza inventario e sicurezza strutturale del form di revoca. **Collaudo manuale pendente**: aprire anteprima pubblica autorizzata e negata, verificando visibilita' materiali e isolamento; esercitare link di revoca valido, scaduto, alterato e riutilizzato, token CSRF assente, lingua IT/EN, stato finale e tastiera/screen reader. Confermare che nessun dato privato sia mostrato negli errori e che gli override template preservino gli ID. Nessun test Joomla manuale registrato come superato.


## DOC-05 — guida contestuale Joomla (prima tranche, 19/11/2026)

**Comportamenti da testare sul sito Joomla di prova — non eseguiti:** aprire Corsi, editor Corso e Iscritti; su ciascuna schermata usare il pulsante nativo Guida, controllare che apra la pagina pertinente offline, senza 404, con testo italiano per it-IT e inglese per en-GB. Verificare su installazione pulita e upgrade, permessi amministrativi, finestre/popup, tastiera e lettore schermo. La guida non deve salvare, cambiare iscrizioni o esporre dati personali. La suite statica `tests/contextual-help-doc05.py` controlla i 3 pulsanti, i 6 documenti e la cartella nel manifest; non equivale al collaudo su Joomla. Le altre schermate DOC-05 restano fuori da questa tranche.


## DOC-05 — seconda tranche di guida contestuale amministrativa (19/11/2026)

**Stato: collaudo Joomla reale non eseguito.** Dopo installazione o upgrade del pacchetto, aprire con un operatore autorizzato le viste Programma, Dashboard, Coda email, Variabili email, Comunicazioni corso, Attestati e Dettaglio attestato. Per ogni schermata selezionare **Guida** e verificare l'apertura del capitolo corretto senza connessione a Internet, senza errori 404 e senza esposizione di dati personali. Ripetere con lingua amministrativa italiana e inglese: contenuto pertinente, nessuna lingua mescolata. Verificare inoltre tastiera, focus, popup, lettore di schermo e visualizzazione mobile. I pulsanti non devono eseguire scritture o bypassare ACL. In Programma confrontare i consigli video con provider installati; in Coda email verificare che la guida non suggerisca ritentativi di consegne incerte; in Attestati verificare la distinzione tra completamento ed emissione. Infine assicurarsi che i file di aiuto siano presenti sia su installazione pulita sia dopo upgrade.

**Test automatico** `python3 tests/contextual-help-doc05.py`: contratti statici per 10 viste e 20 pagine HTML IT/EN; non è un test di visualizzazione Joomla e non chiude DOC-05.


## DOC-05 — copertura di tutte le viste amministrative (29/29; terza tranche, 19/11/2026)

**Cosa collaudare sul sito Joomla di prova (non eseguito):** dopo installazione e upgrade del pacchetto, aprire **ciascuna** delle 29 viste dell'inventario `docs/ADMIN_HELP_COVERAGE.md`. Verificare che il pulsante nativo **Guida** sia presente e apra il file corretto per la vista, senza 404 né necessità di rete. Ripetere con amministratore in italiano e inglese, quindi con un operatore avente ACL limitate e accesso solo ad alcune schermate. Testare tastiera (Tab/Enter/Escape), focus e lettore schermo e almeno una viewport mobile. Le guide non devono eseguire POST, salvare dati, modificare stato o esporre informazioni protette. In particolare verificare le viste di transizione (stato/migrazione iscritti, revisione quiz, estensione tempi, preview pubblicazione), i permessi e i token dei form e che il pulsante Guida non alteri la funzionalità originaria.

**Gate statico automatico:** `python3 tests/contextual-help-doc05.py` scopre le classi HtmlView distribuite dal componente e fallisce se anche una nuova schermata manca di pulsante/aiuto IT/EN oppure se ci sono file orfani o un return anticipato dopo la chiamata alla Guida. **Non equivale a un collaudo Joomla reale.** Il requisito DOC-05 potrà essere completamente chiuso solo dopo collegamenti aggiornati ai manuali e accettazione manuale riportata nel testbook.


## DOC-05 — chiusura tecnica: link manuali offline IT/EN (19/11/2026)

**Automatizzato:** per ciascuna delle 29 viste elencate in `docs/ADMIN_HELP_COVERAGE.md`, `tests/contextual-help-doc05.py` verifica pulsante `Guida`, i due file locali IT/EN, il link relativo `manual.html#<vista>`, i 29 capitoli esistenti in entrambi i manuali e l'assenza di URL remoti o file orfani. Il manifest deve distribuire `help` e la CI deve creare il pacchetto Joomla aggiornato. Questo costituisce evidenza di **implementazione tecnica**, non accettazione UI.

**Collaudo Joomla reale da registrare (non eseguito):** installare/aggiornare la versione della PR, quindi aprire tutte le 29 viste in `it-IT` ed `en-GB`; per ciascuna premere `Guida`, poi il link al capitolo; verificare la corretta apertura e navigazione con/ senza connessione esterna, nessun 404 e lingua coerente. Campionare ruoli amministrativi limitati senza nuove visibilità o scritture, menu e stato del corso invariati. Verificare Tab/Invio/Escape, focus, finestra, lettore schermo e almeno una viewport mobile. Annotare per vista/ruolo/lingua esito **Superata/Fallita**, versione Joomla, browser e screenshot senza dati personali. Non marcare `Accettazione Joomla=Superata` prima del completamento.


## JOO-04 — diagnostica di tutte le attività pianificate Joomla (19/11/2026)

**Test da eseguire sul sito Joomla di prova, non eseguiti:** aprire Dashboard amministrativa come amministratore autorizzato e il riquadro Stato delle attività pianificate JD Learn. Verificare esattamente tre righe: quiz expire, materials cleanup, mail dispatch, con ultima e prossima esecuzione senza indirizzi o dati personali. Per ogni tipo: (1) nessun task → Assente; (2) plugin/task spento → Disabilitato; (3) più istanze dello stesso tipo → Duplicato; (4) task appena abilitato e non ancora scaduto → In attesa; (5) forzare nel sistema di test una prossima esecuzione oltre 10 minuti nel passato → In ritardo; (6) ultima esecuzione con exit code diverso da zero → Fallita; (7) run riuscito → Operativa; (8) data prossima esecuzione nulla/manuale → Stato non determinabile. Verificare cron e Lazy Scheduler, lingua IT/EN, ACL limitate, accessibilità tastiera/mobile e link a Scheduler Joomla. Nessun task deve essere creato, avviato, abilitato o modificato dalla sola apertura della dashboard. Un task operativo non garantisce consegna SMTP.

**Regressione automatica:** `php tests/scheduler-health-joo04.php` copre classificazione senza dipendere da data corrente/DB. Le due matrici CI di Joomla eseguono questo contratto oltre alle prove precedenti; non attribuire esito manuale in base alla CI.


## MAIL-08 — invio test dopo refresh e invii ripetuti (0.60.9-dev)

**Da collaudare manualmente su Joomla 6 con SMTP di prova (non eseguito):** aprire Comunicazioni corso come docente/amministratore autorizzato, selezionare un corso e comporre l'email. Eseguire Anteprima, quindi (1) fare refresh della pagina, premere due volte rapidamente **Invia prova** e verificare **una sola riga** in coda per l'evento e una sola consegna SMTP; (2) tornare alla stessa anteprima, aggiornare la pagina e ripetere Invia prova: la seconda operazione deve essere riconosciuta come la stessa e non generare una nuova riga; (3) creare intenzionalmente una **nuova anteprima** e verificare che il nuovo invio di prova generi una nuova riga; (4) verificare che l'invio finale ai partecipanti, con conferma esplicita, rimanga indipendente dall'invio test e conservi il conteggio destinatari; (5) testare ACL limitate, token CSRF, HTML modificato prima dell'anteprima e dati temporanei di sessione pre-0.60.9; (6) simulare SMTP rifiutato o incerto, verificare che non vengano ripetuti automaticamente invii già tentati. Riportare versione, browser, ruolo, lingue IT/EN, numero record di coda e prova SMTP senza dati personali.

**Automatizzato:** `php tests/course-message-idempotency.php` nella CI preflight e nelle due matrici Joomla; verifica UUID v4, idempotenza dell'event key, segregazione per utente e corso, stabilità della mutation in sessione e vincolo SQL univoco della coda. Il test automatico non sostituisce la conferma SMTP end-to-end.


### SEC-P1 — autorizzazione al download amministrativo degli attestati (20/11/2026)

**Automatico (fixture Joomla):** `tests/joomla-dashboard.php` verifica che `CertificateService::adminDownload` restituisca 403 per proprietario studente, secondo studente e ospite, ma consegni un PDF all'operatore dotato dei permessi amministrativi specifici. Verificare esito su entrambi i profili PHP/database della CI; fino a quel momento la prova è predisposta, non certificata.

**Accettazione:** registrare account/ruolo e codice HTTP, senza allegare file contenenti dati personali; esito manuale resta in attesa finché non osservato.

**Comportamenti da testare sul sito Joomla disposable:** creare un attestato di uno studente; come studente proprietario e come altro studente tentare l'URL amministrativo di download e ottenere rifiuto senza byte del PDF; verificare anche ospite; come amministratore con `core.manage` e `jlms.certificates.manage` scaricare il PDF corretto. Revocare selettivamente `jlms.certificates.manage` a un account amministrativo di prova e verificare nuovamente il 403. Riprovare HEAD e Range; controllare cache privata, contenuto non divulgato e log privi di dati personali. Distinguere questo collaudo manuale dalla fixture di servizio.


### SEC-P1 — token monouso nella conferma consenso email (20/11/2026)

**Comportamenti da testare su Joomla disposable:** inviare una richiesta di consenso su utente di prova; aprire il link di conferma con token valido in una sessione pulita e verificare risposta HTTP iniziale (redirect) con `Cache-Control: no-store`, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex, nofollow`; accertare che la richiesta di destinazione non trasmetta l'URL tokenizzato nel Referer. Ripetere il link e verificarne la monousabilità; provare token scaduto e malformato, senza dati personali nei log; verificare che la revoca continui a funzionare e che il consenso non venga attivato da un token invalido. Prova automatica strutturale `tests/frontend-preferences.py`; collaudo HTTP/manuale **in attesa**. L'inventario e la matrice delle superfici da coprire sono in `docs/SEC_P1_ATTACK_SURFACE_1.0.0.md`.


### SEC-P1 — inventario controller e regressioni sui gate (20/11/2026)

**CI:** eseguire `python3 tests/sec-p1-controller-gates.py` e `python3 tools/check.py`. Il test deve enumerare i controller Joomla Site/Admin e fallire quando viene aggiunto un controller non classificato, viene eliminato un controllo method/CSRF espressamente presente, o cambia la delega dichiarata al controller base Joomla. Lo script attesta soltanto presenza/deriva strutturale.

**Comportamenti da testare manualmente:** per ogni controller scrivente, come anonimo e utenti con/senza permesso specifico, verificare GET sui task mutanti (rifiutato), POST senza token (rifiutato), POST con token e ACL errata (rifiutato), POST con oggetto appartenente ad altro corso/iscrizione (rifiutato) e POST autorizzato (consentito, senza side effect duplicati). Verificare la persistenza DB prima/dopo e redazione dei log. Stato della prova complessiva: **in attesa**.


### SEC-P1 — sessione obsoleta dopo blocco account su riassegnazione categorie (20/11/2026)

**Prova automatica:** la fixture `tests/joomla-categories.php` esegue `inspect` e `reassign` dopo aver impostato `#__users.block=1` sull'operatore già autenticato; entrambe devono fallire con 403 e la categoria del corso rimanere invariata. Il campo `block` è ripristinato in `finally`; successivamente l'operazione permessa resta funzionante.

**Comportamenti da testare manualmente:** autenticarsi con operatore autorizzato alla gestione categorie, da altra sessione bloccare l'account senza chiudere la prima sessione, quindi tentare ispezione e riassegnazione di categoria sorgente cestinata: rifiuto, nessun nuovo `catid`; ripetere dopo riabilitazione dell'account con ACL corrette; testare anche revoca di `core.manage`, permessi `core.delete` sulla sorgente e `core.edit` sulla destinazione. Eseguire su Joomla disposable, non su dati reali. Stato: **in attesa**.


### SEC-P1 — blocco account e ACL profilo docente (20/11/2026)

**CI Joomla:** `tests/joomla-teachers.php` crea un profilo collegato all'operatore; verifica `TeacherProfiles::canEdit()` positivo, imposta `#__users.block=1` senza rigenerare l'identità passata al metodo e richiede rifiuto; ripristina `block=0` in `finally` e verifica ritorno dell'autorizzazione. L'esito deve essere confermato su entrambi i profili PHP/database.

**Comportamenti da testare sul sito Joomla disposable:** con docente collegato e profilo editabile, aprire pagina profilo; bloccare l'account da una sessione amministrativa distinta; senza effettuare logout, controllare che link/form di modifica non siano più disponibili e il POST di salvataggio venga respinto senza scrivere dati. Ripetere con utente non proprietario e ACL `core.edit.own` revocata, poi sbloccare e riprovare con account autorizzato. Test HTTP/manuale **in attesa**; non pubblicare dati personali dei docenti.


### SEC-P1 — comunicazioni corso, destinatari e cross-course IDOR (20/11/2026)

**Automazione Joomla:** `tests/joomla-roster.php` crea un operatore con diritto `jlms.enrollments.manage` e accesso amministrativo sul corso A, senza diritto sul corso B; testa `CourseCommunicationService::editor` e `preview` su entrambi i corsi, anteprima e accodamento con `enrollment_id` di B all'interno di A, anonimo e studente non autorizzato; richiede nessuna nuova riga in `#__jlms_jobs` o `#__jlms_mail_queue` per le richieste rifiutate. Eseguire la CI su PHP 8.3/MySQL 8.4 e PHP 8.4/MariaDB 10.6; il risultato non è attestato fino alla verifica del run.

**Comportamenti da testare manualmente:** con due corsi e due iscritti di prova, accedere alla composizione come operatore autorizzato soltanto sul corso A; tentare direttamente l'URL del corso B, quindi forzare nel POST di anteprima e accodamento un `enrollment_id` del corso B. Verificare 403/409, nessuna divulgazione del nome/email del destinatario estraneo e nessun job o email inviato. Riprovare con utente anonimo, studente proprietario e dopo revoca del permesso. La verifica HTTP, il test di concorrenza Scheduler e l'accettazione amministrativa sono **in attesa**.


### SEC-P1 — ACL globale coda email e riconciliazione (20/11/2026)

**Test CI Joomla:** un operatore delegato con `core.manage` su JD Learn e `jlms.enrollments.manage` sul corso A, ma senza `jlms.audit.view`, deve ricevere 403 da `MailQueueAuditService::page()` e `reconcile()`; la UI dashboard non deve proporre il collegamento globale. Con concessione esplicita del permesso `jlms.audit.view` su `com_jlms`, la pagina è accessibile ma il DTO non contiene `recipient_email`, `recipient_name`, `template_data`, `last_error` e `language`. La riconciliazione di un ID inesistente restituisce 409 senza alterare record. Dopo revoca immediata, la precedente identità di sessione non può più leggere la coda. Test in `tests/joomla-roster.php` su entrambi i database, con ripristino delle ACL in `finally`.

**Comportamenti da testare manualmente:** Super User può leggere la coda e gestire un messaggio `sending` vecchio dopo verifica SMTP esterna; utente delegato senza `jlms.audit.view` non vede shortcut e l'URL diretto viene negato; permesso concedibile a operatore dedicato, con riconciliazione solo dopo POST e token valido; revocare e riprovare dalla stessa sessione. Verificare output senza indirizzi completi, payload di template e dettagli SMTP; due richieste concorrenti non devono cambiare due volte la stessa riga. Test manuale/HTTP e conferma SMTP **pendenti**.


### SEC-P1 — concorrenza logica e dati nei diagnostici della coda (20/11/2026)

**Automazione Joomla:** nella fixture `tests/joomla-roster.php`, per un operatore con `core.manage` e `jlms.audit.view`, creare righe email di prova in `sending` con `claimed_at` vecchio di 20 minuti. Chiamare `reconcile(...,'sent',note,true)`: stato `sent`, destinatario e template rimossi, `reconciliation_note` contiene la motivazione ma `last_error` soltanto `Manual reconciliation recorded.`. Una seconda chiamata sulla stessa riga deve fallire con 409 senza alterare l'audit. Ripetere con `retry`: destinatario mantenuto per l'invio, nota non duplicata, seconda chiamata 409. La fixture cancella sempre le righe artificiali; CI da confermare su MySQL e MariaDB.

**Collaudo manuale Joomla/SMTP:** con ambiente disposable e server SMTP di prova, predisporre messaggio bloccato in `sending` da almeno 15 minuti; verificare l'effettiva accettazione presso SMTP prima di scegliere `sent` o `retry`; tentare due richieste concorrenti distinte sulla stessa riga e verificare una sola transizione, niente doppio invio e nessun dettaglio SMTP in `last_error`/HTML. Controllare note con dati fittizi e i campi di audit, senza riportare dati personali o segreti. Manuale e concurrency HTTP **in attesa**.


### SEC-P1 — due riconciliazioni parallele su una stessa email (20/11/2026)

**CI automatica:** `tests/joomla-roster.php` avvia due processi Joomla distinti con connessioni DB indipendenti e attende `ready`; il padre crea una riga `sending` vecchia e mantiene `SELECT ... FOR UPDATE` sino al segnale di partenza dei due worker. Alla liberazione della lock, un solo `reconcile(sent)` può aggiornare la riga; l'altro riceve 409. Verificare anche stato finale `sent`, un solo campo di audit, tentativi invariati, email/payload redatti e pulizia dei record di test.

**Collaudo manuale residuale:** inviare simultaneamente due POST amministrativi reali con token valido su SMTP isolato, distinguere accettazione SMTP da stato DB, verificare nessun doppio invio e nessuna divulgazione di token o indirizzi; non riavviare invii `sending` senza conferma di mancata accettazione. Non attribuire alla fixture DB la prova di consegna SMTP; accettazione manuale ancora in attesa.
