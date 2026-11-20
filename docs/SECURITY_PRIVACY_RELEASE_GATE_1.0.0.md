# JD Learn — Security, privacy e cookie: release gate 1.0.0

**Approvato come piano, non come certificazione o audit eseguito.** Seguire [progettazione tecnica](specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md), [policy sicurezza](../SECURITY.md) e [inventario cookie](PRIVACY_COOKIES.md). Tutte le verifiche SEC-P1–SEC-P5 sono necessarie prima del rilascio stabile; non sostituiscono l'accettazione Joomla e non aumentano la copertura stimata al solo atto di pianificazione.

| Fase | Priorità | Contenuto | Evidenze/gate |
|---|---|---|---|
| SEC-P1 | P0 | Threat model e code review sistematica su endpoint, ACL/IDOR, CSRF, XSS/SQLi, SSRF, upload/traversal, file privati, quiz, token/replay, segreti, transazioni, code; confronto con prassi Joomla e OWASP ASVS | Matrice minaccia → controllo → test, findings classificati |
| SEC-P2 | P0 | Hardening runtime e supply chain: dipendenze, secret scan, configurazione sicura, logging, dati sensibili, build/package, CSP e webhook provider | Nessun critical/high pertinente aperto senza decisione esplicita e motivata |
| SEC-P3 | P0 | Privacy by design: mappatura trattamenti, export/cancellazione via Joomla com_privacy, retention per progressi/attestati/email, log e backup, diritti dell'interessato | Flussi manuali ed evidenze con account e dati di prova |
| SEC-P4 | P0 | Inventario cookie e storage browser, integrazione CMP e video, documentazione pubblicabile IT/EN per amministratori | Scansioni desktop/mobile per Joomla, JD Learn e provider; guida configurazione CMP; vedi [PRIVACY_COOKIES.md](PRIVACY_COOKIES.md) |
| SEC-P5 | P0 | Test negativi E2E e penetration test/risk review della candidate release, riesame vulnerabilità, patch e incident process | Rapporto finale, tracciabilità issue, gate go/no-go documentato |

## OWASP ASVS 5.0.0 — riferimento di verifica fissato

Per il gate di JD Learn 1.0.0 il riferimento tecnico è **OWASP Application Security Verification Standard (ASVS) v5.0.0, release stabile del 30 maggio 2025** ([fonte ufficiale](https://asvs.owasp.org/downloads/)). Questa versione rimane fissata per il piano di rilascio 1.0.0 anche qualora OWASP pubblichi revisioni successive: eventuali cambi richiedono una decisione esplicita, modifica di questo documento, revisione della matrice di test e nuovo controllo CI.

Quando si associano finding e test a un requisito ASVS, usare identificatori qualificati come `v5.0.0-1.2.5` (sintassi `v<versione>-<capitolo>.<sezione>.<requisito>`), non codici privi di versione che potrebbero assumere un significato diverso. Il rapporto di sicurezza deve elencare, per ogni controllo selezionato, riferimento, superficie JD Learn, minaccia, mitigazione, test/esito, build esaminata e motivazione di eventuali non applicabilità o falsi positivi. La selezione e la tracciabilità per release si completano nel rapporto finale SEC-P1/SEC-P5, **non** tramite questo pin.

**ASVS è un riferimento di verifica, non una certificazione**: la scelta di una versione, l'esecuzione del solo preflight e la documentazione di casi di prova non attestano conformità, certificazione OWASP o assenza di vulnerabilità. Restano obbligatori threat model, code review, analisi di dipendenze, test negativi e revisione dei finding. Il controllo `tests/asvs-reference.py` preserva esclusivamente la coerenza della versione e il disclaimer in questo gate.

## Regole di esecuzione

- Eseguire audit **su codice e candidata finale effettivi**, non soltanto su specifiche; CI verde, controlli statici e mock non dimostrano sicurezza in produzione.
- Non inviare agli strumenti esterni credenziali, dati degli studenti o segreti. Registrare evidenze minimizzate e prive di dati personali.
- Verificare su hosting standard Joomla Apache/LiteSpeed con sessioni/cookie protetti, upgrade da versione precedente, minimo runtime supportato e versione corrente.
- [SECURITY.md](../SECURITY.md) è stato aggiornato nella PR di sicurezza 1.0.0: non dichiara supportate release non ancora pubblicate, descrive il canale di segnalazione privata e il processo di risposta. Questo aggiornamento di policy non completa SEC-P1: la review sistematica del codice, la matrice minaccia-controllo-test e la classificazione dei findings restano da svolgere.
- Non dichiarare certificazione ASVS/GDPR o assenza di cookie fino all'ottenimento delle evidenze pertinenti; classificazione legale e privacy policy finale spettano al titolare del sito.
- Includere per ogni PR implementativa una sezione **Impatto sicurezza/privacy/cookie** con test di regressione; conservare collegamento tra PR, requisito e testbook.

Questo piano è separato dalle funzionalità future Credentials/Open Badges; firma digitale, custodia/rotazione chiavi, revoca e verifiche di interoperabilità richiederanno una review dedicata quando implementate.


## PRIV-C — Registro e diritto di accesso ai consensi applicativi

La verifica di SEC-P3 include obbligatoriamente **PRIV-C1–C5** in [JOOMLA_PRIVACY_CONSENT_INTEGRATION.md](JOOMLA_PRIVACY_CONSENT_INTEGRATION.md): verificare l'interfaccia Joomla 6 per l'eventuale visualizzazione del consenso MAIL-11 nel registro centrale; garantire comunque una consultazione autorizzata senza duplicare fonti di verità, completare export/rimozione/anomizzazione dei dati correlati e documentare la procedura per gli amministratori IT/EN. Il plugin Privacy JD Learn espone già capabilities, export e un percorso di removal, ma la copertura dei token di revoca, code e audit va provata. Il registro Joomla non aggrega automaticamente consensi conservati in tabelle di componenti terzi. **Non scrivere direttamente nel registro interno Joomla senza API documentata e testata.** Nessuna nuova copertura attribuita finché le PR implementative e i test non sono integrati.


## Finding SEC-P1 — dati di eccezione SMTP persistiti nell'outbox

La review mirata della coda email ha rilevato che il testo delle eccezioni del mailer veniva scritto in `last_error`; risposte SMTP e provider possono contenere indirizzi destinatario o altri dettagli operativi. Classificazione preliminare **media**: esposizione non necessaria a chi legge il database o i backup, senza evidenza di accesso anonimo via interfaccia. La PR #338 (0.56.18-dev) sostituisce il testo con un messaggio generico, esclude `last_error` dalla vista di audit e redige i valori già registrati tramite migrazione. CI standard 4/4 nel run 43522457570; collaudo Joomla/SMTP pendente. Questa mitigazione mirata non chiude SEC-P1 e non sostituisce la review sistematica né le fasi SEC-P1–P5.

## SEC-P1 — revisione mirata IDOR su allegati privati (20/11/2026)

**Perimetro effettivamente esaminato (non audit SEC-P1 completo):** `component/administrator/src/Application/MaterialService.php`, `component/administrator/src/Controller/MaterialController.php`, `component/site/src/Controller/AttachmentController.php`, `component/administrator/src/Infrastructure/ProtectedMaterialStorage.php` e `MaterialDownload.php`. Il servizio amministrativo ricarica l'attore Joomla e richiede `core.manage` su `com_jlms` e `jlms.materials.manage` sul corso; il record allegato deve appartenere alla versione dichiarata. Il frontend risolve il contesto didattico dell'iscrizione e verifica la versione proprietaria. Lo storage usa chiavi opache, file protetti e controlli di integrità. L'esame statico di questa superficie **non ha individuato un bypass IDOR confermato**.

**Nuova regressione HTTP reale:** `tests/materials-http.py` verifica download autorizzato e prova a scambiare l'ID di un allegato del corso B con corso/versione A e viceversa, su GET, HEAD e GET con `Range` valido, aspettando rifiuto 404 e assenza del payload privato. Ripete il tentativo sull'anteprima editoriale. La prova è eseguita dalla CI di integrazione Joomla nei due profili PHP/database. La suite preesistente copre tentativi di download da utente estraneo e anonimo, revoca di permessi e CSRF sugli upload.

**Limiti aperti:** non sono stati esaminati sistematicamente tutti gli endpoint, tutti i ruoli/gruppi Joomla, tutte le transizioni, le condizioni di concorrenza, i link di tutti i tipi di file e le configurazioni hosting. Non dichiarare SEC-P1, SECDEV-07, SEC-P5 o accettazione manuale completati. Test manuali sul sito: creare due corsi e utenti con autorizzazioni distinte, tentare gli stessi scambi di ID anche su preview pubbliche, verificare codici/range, cache, ID non validi e log senza dati sensibili.


## SEC-P1 — verifica mirata CSRF/metodo/stati banca domande (20/11/2026)

**Perimetro:** `QuestionbankController::mutate`, `QuestionBankService::trash/delete/duplicate`, `tests/quiz-authoring-http.py` e form nativi della Banca domande. La verifica di codice conferma controlli espliciti `POST` e token Joomla prima dell'esecuzione dei task, ri-validazione dell'identità e ACL oggetto nel servizio, lock della riga, transizioni 0/1 → -2 → 0, blocco della cancellazione definitiva fuori dal cestino e delle revisioni ancora utilizzate.

**Regressione HTTP in CI:** su una nuova domanda isolata, GET e POST senza token devono essere rifiutati rispettivamente con 405/403 sui quattro task `trash`, `restore`, `delete` e `duplicate`. La cancellazione diretta di una domanda non cestinata deve fallire con 409, mentre cestinazione e ripristino autenticati funzionano; la ripetizione di una transizione incompatibile deve fallire con 409. La prova non modifica le domande fissate dai quiz della fixture.

**Esito e limiti:** non è stata individuata una mutazione senza CSRF su questi task mediante revisione statica; l'esito HTTP deve essere confermato dalla CI e non sostituisce revisione sistematica di tutti i controller, ACL in configurazioni reali o collaudo manuale Joomla. Non promuovere criteri SEC-P1/SECDEV-04 o percentuali per questa sola tranche. La guida `QUIZ_AUTHORING.md` è stata corretta perché descriveva duplicazione/cestinazione già implementate come assenti.

## SEC-P1 — regressione ACL download amministrativo attestati (20/11/2026)

**Superficie:** `CertificateService::adminDownload` e `CertificateService::assertCertificateAdministrator`, con fixture Joomla `tests/joomla-dashboard.php`. Il metodo richiede congiuntamente `core.manage` e `jlms.certificates.manage` su `com_jlms`; il solo possesso dell'attestato o l'autenticazione non attribuiscono il permesso amministrativo.

**Test aggiunti:** dopo la creazione di un attestato PDF reale nella fixture, tre tentativi di download amministrativo (proprietario, altro studente e ospite) devono fallire con 403; il download da operatore autorizzato deve ottenere il documento PDF e chiudere il file handle. La prova richiede la CI Joomla completa; non è attestata come superata prima dell'esito CI.

**Limite:** verifica mirata del servizio e del ruolo della fixture, non penetration test, non matrice esaustiva dei gruppi Joomla né accettazione manuale. SEC-P1 e SEC-P5 restano aperti; nessuna percentuale funzionale aggiuntiva viene attribuita.

Il tracciamento della prova mantiene separati: codice che applica ACL, esito HTTP nella fixture e collaudo manuale con ruoli configurati; nessun esito non osservato deve essere marcato verificato.

## SEC-P1 — inventario trasversale e finding sui token di conferma (20/11/2026)

L'inventario per l'intero perimetro della 1.0.0, con superfici ed evidenze ancora mancanti, è in [SEC_P1_ATTACK_SURFACE_1.0.0.md](SEC_P1_ATTACK_SURFACE_1.0.0.md). **La predisposizione della matrice non chiude SEC-P1.** Revisione mirata: `MailconsentController::confirm()` riceve un token monouso nell'URL e lo consuma prima di redirigere alle preferenze; mancava la policy anti-referrer già presente nel percorso di revoca. La mitigazione aggiunge `Referrer-Policy: no-referrer` e `X-Robots-Tag: noindex, nofollow` oltre al `Cache-Control: no-store` esistente, prima dell'operazione. Test di regressione strutturale in `tests/frontend-preferences.py`; conferma HTTP su Joomla disposable da effettuare. Non è stata osservata un'esfiltrazione; nessun incremento di copertura o accettazione dichiarato.

## SEC-P1 — inventory gate controller di Joomla (20/11/2026)

Introdotto gate strutturale `tests/sec-p1-controller-gates.py` richiamato dal preflight `tools/check.py`: censisce controller frontend/backend, richiede classificazione esplicita dei nuovi file e conserva i controlli POST/CSRF o le deleghe Joomla dichiarate. La documentazione integrale delle superfici è in `docs/SEC_P1_ATTACK_SURFACE_1.0.0.md`. La suite non sostituisce prove negative ACL/IDOR, CSRF e stato DB nei singoli endpoint; gate SEC-P1 e SEC-P5 **restano aperti**.

## SEC-P1 — blocco account su riassegnazione categorie (20/11/2026)

Finding mirato: `CategoryReassignmentService::assertManage()` verificava la proprietà `block` di un oggetto utente clonato dalla sessione e non ricaricato dal database. Il controllo poteva quindi essere obsoleto rispetto a un blocco amministrativo successivo al login. La mitigazione ricarica l'attore Joomla con `new User(id)` e invalida l'ACL cache prima di valutare i permessi sulla categoria sorgente e destinazione. Test Joomla disposable riproduce blocco account con session identity ancora in memoria, rifiuto 403 di lettura/mutazione e assenza di side effect; CI e collaudo manuale da confermare. Non rappresenta il completamento del gate SEC-P1 generale.

## SEC-P1 — identità fresca nel controllo di editing docenti (20/11/2026)

Il metodo condiviso `TeacherProfiles::canEdit()` ricarica ora l'account Joomla dal database e azzera i diritti memorizzati prima di valutare l'autorizzazione: una sessione non può conservare `block=0` dopo blocco amministrativo. La fixture Joomla docenti esegue prova negativa con account bloccato e identità di sessione stale, oltre a due prove positive. La copertura mirata non sostituisce controllo E2E CSRF, editing a oggetti altrui e permission matrix; il gate SEC-P1 non viene dichiarato chiuso.

## SEC-P1 — test negativi cross-course per comunicazioni ai corsisti (20/11/2026)

Nuova copertura mirata in `tests/joomla-roster.php`: operatore autorizzato al roster di un corso non deve accedere all'editor o all'anteprima delle comunicazioni di un altro corso; un ID iscrizione di un altro corso non deve essere incluso nei destinatari o creare job/email. Ospiti e studenti non devono vedere il roster destinatari amministrativo; sono assertati anche i conteggi dell'outbox e dei job, invariati dopo errori. Stato automatizzato da confermare con CI su Joomla disposable, collaudo HTTP/manuale in attesa; non si dichiara una vulnerabilità osservata né chiusura dell'intero SEC-P1.

## SEC-P1 — separazione ACL per coda email globale (20/11/2026)

Correzione runtime: `MailQueueAuditService::page` e `reconcile` ora richiedono `core.manage` **e** `jlms.audit.view` su `com_jlms`; il permesso di audit esisteva già nella ACL del componente. `core.manage` da solo può essere assegnato a operatori di un singolo corso, mentre la coda contiene metadati e note di tutti i corsi. Il collegamento nella dashboard è condizionato al medesimo permesso. Test negativo/positivo su Joomla disposable in `tests/joomla-roster.php`, prova senza side effect e verifica dell'assenza di campi di email/template dal DTO. L'audit SEC-P1 globale, test concorrenza Scheduler e accettazione manuale non sono dichiarati conclusi.
