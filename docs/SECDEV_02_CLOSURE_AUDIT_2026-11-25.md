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
