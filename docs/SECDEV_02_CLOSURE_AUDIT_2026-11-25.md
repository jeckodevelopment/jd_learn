# SECDEV-02 — Audit di chiusura (25 novembre 2026)

## Decisione di accettazione

**Non chiudere ancora SECDEV-02.** Dopo le PR #1027–1028, la verifica PHP_CodeSniffer 4.0.4 con PSR12 ha **0 finding** e la CI su Joomla 6.2.0 è verde, senza plugin compat6. Questi risultati sono reali e conservati.

Il registro canonico contiene **23 servizi CORE**, **47 criteri CORE** (24 `implemented`, 23 `unverifiable`) e **4 criteri SECDEV-02 ancora `unverifiable`**. L'audit di tracciabilità dei servizi non è una certificazione dei criteri funzionali. Per aggiornare `Parziale` a `Completato` occorrono evidenze adeguate anche per gli scoperti e il collaudo manuale applicabile.

### Aggiornamento obbligatorio del target Joomla

Il **24 novembre 2026** è stata pubblicata **Joomla 6.2.1** come release di sicurezza e bugfix. Fonti: [annuncio Joomla](https://www.joomla.org/announcements/release-news/joomla-6-2-1-security-bugfix-release.html), [download e SHA ufficiale](https://downloads.joomla.org/cms/joomla6/6-2-1), [release GitHub e SHA-256](https://github.com/joomla/joomla-cms/releases/tag/6.2.1). Questa PR aggiorna la fixture CI alla patch 6.2.1, conserva il controllo `compat6` disabilitato e la verifica dell'archivio con SHA-256 ufficiale. **Il risultato è da accettare con la CI sull'HEAD definitivo**, senza assumere che il sito reale sia aggiornato.

## Inventario residui CORE-01–23

Fonte unica di stato: `docs/FUNCTIONAL_CRITERIA_1.0.0.json`. Per servizio, le decisioni di uso nativo e le motivazioni sono nel registro `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json`. Le righe qui sotto servono a indirizzare le verifiche, non costituiscono una seconda matrice autoritativa.

| Servizio | Criteri implementati | Criteri da verificare | Verifica residua documentata |
|---|---:|---|---|
| CORE-01 | 1/2 | all_hierarchy | Gerarchia, lingua, permessi e riordino end-to-end |
| CORE-02 | 1/2 | auth_variants | MFA e provider esterni reali |
| CORE-03 | 1/2 | cross_context | Rimozione accessi tra tutti i contesti |
| CORE-04 | 1/3 | record_acl, teacher_edit | Matrice ruoli e protezioni su tutti i record |
| CORE-05 | 1/2 | layout_coverage | Override di ogni vista e modulo distribuito |
| CORE-06 | 1/2 | strings | Copertura totale stringhe e messaggi |
| CORE-07 | 1/2 | menu_matrix | Combinazioni menu, filtri e fallback |
| CORE-08 | 1/2 | scope | Classi dati, export, cancellazione e conservazione su hosting |
| CORE-09 | 1/2 | tags_context | Tutti i contesti e filtri previsti |
| CORE-10 | 1/2 | teacher_scoping | ACL docente e lezione, lingua e accesso |
| CORE-11 | 1/2 | content_events | Eventi plugin e filtro HTML per ogni campo |
| CORE-12 | 1/2 | media_providers | Provider media e assenza di fuga allegati |
| CORE-13 | 1/2 | all_routes | URL diretti, breadcrumb e menu multilingua |
| CORE-14 | 1/2 | index_acl | Invalidazione indice per ACL, stato e lingua |
| CORE-15 | 1/2 | native_workflow | Transizioni e autorizzazioni per tutte le entità |
| CORE-16 | 1/2 | all_entities | Ripristini concorrenti e non mutazione assegnazioni |
| CORE-17 | 2/2 | — | Completezza eventi e politiche retention |
| CORE-18 | 1/2 | smtp | SMTP e template multilingua in ambiente |
| CORE-19 | 1/2 | idempotent_tasks | Ripetizioni, errori e notifiche duplicate |
| CORE-20 | 1/2 | native_completeness | Batch, errori e recupero input nei percorsi residui |
| CORE-21 | 1/2 | private_cache | Cache cross-account su tutti gli endpoint |
| CORE-22 | 1/2 | release_updates | Aggiornamenti e rollback su ambienti target |
| CORE-23 | 1/2 | event_contracts | Payload degli eventi e compatibilità plugin terzi |

La registrazione di `unverifiable` **non prova** che la funzione manchi; significa che manca una verifica integrale del criterio nella sua formulazione originale. Non creare artificiosamente funzionalità già coperte dal core Joomla.

## API Joomla: evidenza reale e limiti dell'inventario

La CI di PR #1028 ha verificato **351 file PHP distribuiti**, **113 simboli Joomla**, **60 chiamate statiche**, e **3 chiamate su istanze direttamente costruite**, senza problemi riscontrati, su Joomla 6.2.0 con compat6 disabilitato. Restano **10.311 siti di chiamata su oggetti bisognosi di analisi di flusso** e **98 chiamate statiche dinamiche non risolte**: questi sono siti di codice censiti, non 10.409 difetti né 10.409 API rimosse. Lo scanner lessicale, per progettazione, **non prova la compatibilità di ogni chiamata dinamica**.

La nuova verifica sulla patch 6.2.1 deve essere acquisita da un run CI successivo. Le prove di conformità PHP non sostituiscono le prove Joomla, ACL, CSRF o di esercizio dei provider terzi.

## Condizioni di chiusura verificabili

1. Verificare l'esito reale della CI Joomla **6.2.1** sulle due matrici PHP/database, pacchetto ZIP, Chromium, compat6 disabilitato, controlli Joomla API e gate PSR12.
2. Riconciliare i 23 criteri CORE ancora `unverifiable`, indicando per ognuno test, configurazioni, motivi di eventuale non pertinenza e risultato. **Non** promuoverli con una dichiarazione collettiva o per il solo PASS di un gate statico.
3. Rendere tracciabile per `SECDEV-02/criterion_01–04` ogni evidenza di soddisfacimento e test, con la distinzione fra runtime verificato e configurazione reale dell'hosting.
4. Registrare separatamente in `docs/QA_TESTBOOK.md` i collaudi manuali pertinenti su Joomla, tra cui permessi editoriali per record, MFA, privacy/retention, SMTP, Task Scheduler, Finder, multilingua e upgrade. Non dichiararli eseguiti senza risultati osservati.
5. Solo quando le prove sono complete, aggiornare lo stato nel registro JSON, il CSV canonico, le percentuali e il report finale. Fino ad allora il requisito resta **Parziale**.

## Controlli negativi

`python3 tests/secdev02-closure-readiness.py --self-test` verifica che una falsa dichiarazione `Completato`, un CORE mancante o una patch CI incoerente non siano accettati. La modalità senza argomenti espone l'inventario ma non scambia `unverifiable` per un errore di esecuzione; `--require-complete` fallisce finché esistono criteri senza prove. Questo controllo aggiuntivo è di **accettazione**, non un sostituto dei test funzionali esistenti.

## Comportamenti da testare

- Installazione e upgrade pacchetto su Joomla 6.2.1, stesso schema/ACL e nessuna perdita dati.
- Rendering corso, categoria, docente, quiz e dashboard nelle due matrici PHP/DB.
- Blocco per compat6 attivo, simboli Joomla rimossi o downgrade della patch.
- Integrità requisiti e inventario CORE; impossibilità di promuovere SECDEV-02 se i residui rimangono.
- Collaudi manuali Joomla, da registrare in un'iterazione di accettazione separata.


## Riconciliazione operativa dei 23 servizi CORE — 25 novembre 2026, lotto unico

Sono state analizzate **tutte le 23 decisioni di riuso Joomla**, confrontando matrice originale, registro dei servizi, test effettivamente presenti nel repository e limiti di accettazione. Non sono state introdotte funzionalità artificiali per abbassare il contatore. Il campo `reconciliation` del registro `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json` è ora consultabile e verificato automaticamente da `tests/secdev02-core-ledger.py` (allineamento esatto degli ID, esistenza dei test, divieto di promozioni non motivate e fixture negative).

**Lettura della tabella:** i test elencati **esistono** e costituiscono punti di partenza per la verifica; non è attestato che coprano automaticamente il criterio in ogni ambiente o combinazione. Le righe con `ci_plus_site` richiedono anche test di accettazione Joomla; le righe `site_and_external` hanno dipendenze dalla configurazione, policy o provider. Il `code_complete_site_pending` per CORE-17 indica criteri di codice già implementati, non un collaudo completo del sito.

| Servizio | Criteri ancora senza accettazione | Test di riferimento presenti | Verifica da completare |
|---|---|---|---|
| CORE-01 | all_hierarchy | `joomla-categories.php`, `categories-http.py` | CI parziale + matrice Joomla |
| CORE-02 | auth_variants | `joomla-learning.php`, `joomla-smoke.php` | Dipendenza dal sito/provider |
| CORE-03 | cross_context | `joomla-learning.php`, `dashboard-http.py` | CI parziale + matrice Joomla |
| CORE-04 | record_acl, teacher_edit | `joomla-editor-access.php`, `joomla-courses-ui.php` | CI parziale + matrice Joomla |
| CORE-05 | layout_coverage | `frontend-css-contract.py`, `frontend-dom-runtime.py` | CI parziale + matrice Joomla |
| CORE-06 | strings | `secdev02-core06-locale-integrity.py`, `secdev02-core-native-contracts.py` | CI parziale + matrice Joomla |
| CORE-07 | menu_matrix | `joomla-associations.php`, `associations-http.py` | CI parziale + matrice Joomla |
| CORE-08 | scope | `joomla-smoke.php`, `sec-p3-data-inventory.py` | Dipendenza dal sito/provider |
| CORE-09 | tags_context | `secdev02-core-native-contracts.py`, `joomla-courses-ui.php` | CI parziale + matrice Joomla |
| CORE-10 | teacher_scoping | `joomla-course-fields.php`, `joomla-courses-ui.php` | CI parziale + matrice Joomla |
| CORE-11 | content_events | `joomla-curriculum.php` | CI parziale + matrice Joomla |
| CORE-12 | media_providers | `materials-http.py`, `html5-http.py` | Dipendenza dal sito/provider |
| CORE-13 | all_routes | `categories-http.py`, `associations-http.py` | CI parziale + matrice Joomla |
| CORE-14 | index_acl | `joomla-courses-ui.php` | CI parziale + matrice Joomla |
| CORE-15 | native_workflow | `joomla-publication.php` | CI parziale + matrice Joomla |
| CORE-16 | all_entities | `joomla-programme-restore.php` | CI parziale + matrice Joomla |
| CORE-17 | Nessuno (codice) | `joomla-publication.php`, `joomla-enrollment-state.php` | Codice verificato; accettazione sito |
| CORE-18 | smtp | `joomla-roster.php`, `joomla-smoke.php` | Dipendenza dal sito/provider |
| CORE-19 | idempotent_tasks | `joomla-quiz-attempts.php` | CI parziale + matrice Joomla |
| CORE-20 | native_completeness | `joomla-courses-ui.php` | CI parziale + matrice Joomla |
| CORE-21 | private_cache | `secdev02-core21-private-dispatch.py`, `dashboard-http.py` | CI parziale + matrice Joomla |
| CORE-22 | release_updates | `install-package.php` | Dipendenza dal sito/provider |
| CORE-23 | event_contracts | `provider-contract-example.php`, `joomla-smoke.php` | Dipendenza dal sito/provider |

### Bonifiche funzionali e nuovi gate di questa riconciliazione

**CORE-06 — cataloghi IT/EN.** Riscontrata la dichiarazione duplicata `COM_JLMS_TEACHERS` nei file frontend italiani e inglesi (stesso valore ripetuto), rimossa senza cambiare la traduzione. Il test `tests/secdev02-core06-locale-integrity.py` valida tutte le **quattro coppie di cataloghi** `com_jlms.ini`/`com_jlms.sys.ini` frontend/backend in IT/EN: unicità delle chiavi, sintassi, parità fra lingue, valori non vuoti e segnaposto compatibili, con prove negative. Il criterio completo resta da verificare per i contenuti non letterali, le email personalizzate e il funzionamento dei language overrides nel sito reale.

**CORE-21 — privacy delle route.** Otto controller frontend con task sensibili (`Enrollment`, `Teacher`, `Quiz`, `Video`, `Media`, `Attachment`, `Certificate`, `Mailconsent`) impostano ora `Cache-Control: private, no-store` **prima del dispatch e anche sugli errori**. La navigazione `DisplayController` resta non memorizzabile. Il test `tests/secdev02-core21-private-dispatch.py` copre tutti questi ingressi e blocca la rimozione/il ritardo della policy con fixture negative. Restano indispensabili prove HTTP multi-utente sui flussi e sull'hosting reale, che possono aggirare o riscrivere header PHP.

### Classificazione precisa dei residui

- **Richiedono ambiente o servizi esterni:** CORE-02 (MFA/IdP), CORE-08 (privacy e retention), CORE-12 (provider e risorse), CORE-18 (SMTP), CORE-22 (upgrade/rollback host), CORE-23 (plugin terzi). La CI non può simularne l'intero funzionamento reale.
- **Richiedono ampliamento di regressioni funzionali Joomla:** CORE-01/03/04/05/06/07/09/10/11/13/14/15/16/19/20/21. Per ciascuno la tabella indica i test già eseguiti dal repository da riutilizzare; nessuna promozione per semplice presenza del file.
- **Già documentato come implementato nel registro del codice:** CORE-17 (2/2); i collaudi amministrativi esterni rimangono distinti dai criteri software.

**Risultato della riconciliazione:** 23/23 servizi mappati a evidenza e prossimo collaudo, due interventi correttivi concreti e gate negativi permanenti; la conta originale dei criteri non verificabili resta da validare dopo la CI. Nessun requisito è chiuso per convenienza amministrativa.


## Verifica dinamica dei residui CORE-06 e CORE-21 (25 novembre 2026, lotto applicativo)

Questa tranche non si limita a enumerare i criteri: aggiunge test riproducibili direttamente al candidato Joomla 6.2.1.

- **CORE-06 — lookup dei messaggi PHP.** Il test `tests/secdev02-core06-runtime-keys.py` attraversa tutti i file PHP del componente, sia backend sia frontend. Ricava i riferimenti statici `Text::_`, `Text::sprintf`, `Text::plural` e `Text::script` con chiavi `COM_JLMS_*` e verifica che ciascuna chiave esista **in entrambe le lingue** del client corrispondente. Sono presenti fixture negative per una chiave mancante da una sola traduzione. Il gate precedente verifica la parità dei cataloghi; questo verifica la **parità con il codice chiamante**. Vengono riferiti separatamente gli usi calcolati di `Text::_`, la cui verifica richiede sorgenti o scenari dinamici diversi.
- **CORE-21 — HTTP Joomla reale.** Il test `tests/dashboard-http.py` estende la fixture a nove tentativi rifiutati (otto controller di task riservati, più inizio lezione), validando su risposta HTTP il codice di errore e l'header `Cache-Control: no-store`. I test esistenti verificano già separazione proprietario/iscrizione, revoca della sessione e visualizzazioni private con due modalità SEF. La nuova prova deve essere acquisita nella CI completa, evitando di dedurre correttezza del reverse proxy esterno dai soli header di PHP.
- **Accettazione:** il nuovo gate può dimostrare il comportamento delle route e delle chiavi letterali **nel perimetro CI**, ma non attesta da solo tutti i modelli email/configurazioni SMTP, le chiavi computate, gli override lingua installati o le cache CDN/hosting. Pertanto i due criteri restano `unverifiable` finché tutte queste dimensioni pertinenti non siano collaudate e registrate.
- **SECDEV-02:** nessuna percentuale aggiornata sulla sola presenza di script. L'obiettivo è abbassare gli elementi realmente non coperti tramite test eseguiti e rilevare differenze con risultati riproducibili.

### Comportamenti da testare

- Inserire temporaneamente un `Text::_('COM_JLMS_MISSING')` in una vista: gate CORE-06 rosso, anche con cataloghi IT/EN perfettamente allineati fra loro.
- Rimuovere una chiave da uno dei cataloghi: gate rosso solo per la coppia/lingua realmente scoperta.
- Eseguire senza token o con metodo errato task di video, quiz, iscrizioni, materiali, attestati, preferenze e docente: risposta di errore con `Cache-Control: private, no-store`.
- Eseguire le due matrici PHP/database e la suite Chromium senza alterare flussi di apprendimento o modificare il core Joomla.


### Riscontri effettivi dei nuovi gate — PR #1031

Il nuovo controllo `tests/secdev02-core06-runtime-keys.py` ha riscontrato **6 chiavi letterali mancanti**, in entrambe le lingue (**12 traduzioni**): `COM_JLMS_PATHWAY_COURSES` (frontend) e le cinque etichette admin `COM_JLMS_VIDEO_SPEED`, `COM_JLMS_VIDEO_SPEED_UNAVAILABLE`, `COM_JLMS_VIDEO_CAPTIONS_LANGUAGE`, `COM_JLMS_VIDEO_CAPTIONS_OFF`, `COM_JLMS_VIDEO_CAPTIONS_UNAVAILABLE`. Tutti i cataloghi ora contengono le traduzioni corrispondenti; lo scanner nella CI rileva attualmente **661 chiavi letterali uniche su 296 file PHP**, zero chiavi letterali mancanti dopo la bonifica. **191 siti con chiavi calcolate** restano censiti, non convalidati dal gate statico.

L'estensione del collaudo HTTP ha poi individuato un difetto effettivo in `QuizController`: la risposta JSON immediata su metodo rifiutato (HTTP 405) non esponeva l'header `Cache-Control: no-store`, nonostante `setHeader` fosse chiamato. La risposta veniva emessa direttamente senza lo step `sendHeaders` del normale flusso Joomla. L'intervento di sicurezza su `QuizController`, `VideoController` e `LessonController::begin` mantiene i risultati e gli errori JSON invariati, ma **invia gli header Joomla prima di stampare il corpo e chiudere**. I test negativi in `tests/secdev02-core21-private-dispatch.py` impediscono di reintrodurre la chiusura senza flush. La prova HTTP è eseguita nelle matrici CI. Finché non risulta verde sull'HEAD definitivo, il fix è un candidato, non un'accettazione.

Questi risultati costituiscono correzioni misurabili e permanenti, ma **non chiudono** l'intero CORE-06/CORE-21 né SECDEV-02: modelli email e chiavi calcolate, retrocompatibilità di override, reverse proxy/cache CDN, sessioni reali e rimanenti criteri CORE richiedono ancora prove proprie.


## SECDEV-02 — Matrice esaustiva route private (intervento 25 novembre 2026)

**Problema di accettazione individuato:** i vecchi test HTTP CORE-21 campionavano soltanto 9 task; il componente espone un numero maggiore di operazioni private. Una serie di passaggi sui soli endpoint documentati non dimostra l'assenza di nuovi endpoint privati dimenticati.

Il nuovo manifesto `tests/secdev02-core21-task-matrix.py` viene validato confrontando il **set esatto** delle task allowlist nei controller PHP distribuiti, comprese le due alternative di `EnrollmentController` per la sottoclasse `LessonController`, e l'insieme esatto delle `$privateViews` in `DisplayController`. L'aggiunta o la rimozione di una route senza aggiornare il test blocca i controlli CI. Fixture negative verificano il parser e i casi non riconosciuti.

Il test HTTP Joomla `tests/dashboard-http.py` esegue automaticamente **tutte le route manifestate** con cookie separati per titolare iscrizione, secondo studente senza iscrizioni e visitatore anonimo. Usa solo metodi non consentiti, POST senza CSRF o link volutamente non valido, non muta consensi o progressi, e legge anche i redirect immediati **senza seguire il cambio pagina**: in questo modo controlla l'header `Cache-Control: no-store` della risposta originale, invece che quello di una pagina finale differente. Per le viste personali verifica anche `X-Robots-Tag: noindex` e l'assenza di contenuti didattici privati nelle risposte di account differenti. Il test esistente continua a verificare sessione, revoca, paginazione e avvio lezioni con due modalità SEF. È una **prova automatica significativa**, non una certificazione di reverse proxy/CDN o sessioni su hosting diverso.

**Gate di promozione:** solo dopo la CI integrale verde e dopo la verifica che il manifesto corrisponda a ogni endpoint pubblicato valutare `CORE-21/private_cache` come implementato dal punto di vista del codice. Il collaudo della cache gestita dal provider e l'accettazione release restano a parte. Non anticipare percentuali prima degli esiti.


## CORE-08 — correzione implementativa Privacy con utenti multipli (25/11/2026)

**Difetto rilevato:** l'anonimizzazione di un account azzerava anche `created_by` e `modified_by`
appartenenti a un altro account quando uno solo dei due campi corrispondeva al soggetto.
La richiesta di iscrizione poteva perdere il richiedente e la motivazione quando l'account
rimosso era soltanto il revisore; la coda mail perdeva l'attribuzione di un altro operatore
di riconciliazione.

**Correzione nel codice:** aggiornamenti indipendenti per autore, modificatore,
richiedente e revisore; redazione del motivo solo per il richiedente; mantenimento
dell'attribuzione a operatori terzi nella coda mail. Nessuna nuova tabella e nessuna
modifica al core Joomla.

**Prove implementative:** `tests/joomla-smoke.php` con due account distinti e
attribuzioni editoriali incrociate, più gate di regressione `tests/sec-p2-p3-guard.py`.
I test e le matrici CI devono essere eseguiti sull'HEAD della PR.

**Collaudi manuali separati:** in un'installazione reale verificare la privacy su due
account, le richieste di iscrizione, la conservazione dei dati e le attribuzioni;
non considerarli eseguiti per effetto del merge.

La correzione è un lotto concreto di sviluppo CORE-08; **non** equivale alla
chiusura implementativa di tutti i 23 criteri o al collaudo SECDEV-02.


## SECDEV-02 — lotto implementativo ACL e categorie (25 novembre 2026)

**CORE-01/03/04 — verifiche in codice, distinte dall'accettazione manuale.**
`CategoryReassignmentService` controllava i permessi di eliminazione/modifica
sulla categoria sorgente/destinazione, ma il batch aggiornava direttamente tutti
i record con `catid` corrispondente: una negazione ACL sul singolo corso,
percorso, docente o domanda e il checkout di un altro editor non fermavano
l'operazione. Ora ogni record viene letto con lock `FOR UPDATE` nella
transazione, autorizzato tramite `core.edit` o `core.edit.own` per il
creatore e verificato per checkout, prima dell'aggiornamento. La selezione
deve ancora corrispondere al conteggio iniziale; ogni diniego provoca rollback
atomico. Gli ID corso da sincronizzare con Finder sono raccolti dalle righe
bloccate, evitando l'inventario preventivo potenzialmente obsoleto.

**CORE-01/04 — categoria non utilizzabile.** `CourseEditorAccess::canCreate`
e la scelta/validazione delle destinazioni escludono anche categorie formalmente
attive ma sotto un antenato nel cestino. I permessi sono sempre letti tramite
utente Joomla aggiornato, inclusa la visualizzazione delle destinazioni.

**Test automatici:** `tests/joomla-categories.php` prova il checkout
appartenente a un altro utente, il rollback senza spostamenti e il rifiuto
(o mancata offerta) di destinazioni con antenato cestinato; `tests/joomla-editor-access.php`
verifica la creazione in categoria cestinata anche con permesso ACL nativo e il rollback totale del batch quando un record ha un diniego ACL esplicito pur avendo categorie sorgente/destinazione autorizzate.
La CI delle due matrici PHP/database e Chromium è necessaria prima del merge.

**Collaudi manuali non eseguiti:** con ruoli Joomla separati, testare i
permessi sul singolo record, le selezioni massive, il checkout concorrente,
categorie annidate, Finder dopo il trasferimento e UI mobile backend; nessuno
è considerato superato automaticamente dalla CI.

**Stato:** questi sono difetti implementativi specifici e non una promozione
automatica dell'intero CORE-01/03/04 o di SECDEV-02. La certificazione
implementativa completa resta da riconciliare con le altre aree core.
