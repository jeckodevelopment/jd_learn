# SECDEV-02 — riconciliazione per la chiusura implementativa (26 novembre 2026)

## Decisione: chiusura complessiva NON ancora autorizzata

La chiusura deve distinguere **sviluppi/test di codice**, **accettazione Joomla manuale** e **prove su servizi/hosting reali**. La presenza di una PR integrata o di una CI verde non può essere convertita in accettazione integrale dei criteri più estesi.

- **CORE-01–23:** 47 criteri canonici, di cui 24 `implemented` e **23 `unverifiable`**. Fra questi ultimi, **17** richiedono ancora verifiche di sviluppo/CI per l'intera formulazione del criterio e **6** dipendono da ambiente, policy o servizi esterni; fonte: `docs/SECDEV_02_CORE_RESIDUAL_CLASSIFICATION_2026-11-25.json`. Non equivalgono a 23 difetti di codice confermati.
- **SECDEV-02:** quattro criteri: **criterion_04 implementato con prova specifica**, mentre `criterion_01`, `criterion_02`, `criterion_03` rimangono `unverifiable`. La conformità PSR-12, i test sul pacchetto installato e la versione Joomla 6.2.1 sono verificati nel perimetro CI, non su ogni ambiente/estensione.
- **Collaudi Joomla manuali:** **non eseguiti** nell'ambito di questa ricognizione. La chiusura implementativa del lavoro residuo rimane distinta dall'accettazione finale della release.
- **Stato `docs/REQUIREMENTS_STATUS.csv`: Parziale**. Non aumentare la percentuale complessiva del progetto senza ricalcolare tutti i criteri ponderati.
- **Soglia minima di evidenza dello sviluppo ricalcolata:** **63,76%**, rispetto al 63,64% precedente (+0,12 punti); area sicurezza **50,26%**. È una stima inferiore documentale e **non** la percentuale funzionale globale definitiva. Copertura accettazione manuale **4,67%**, invariata. Fonti: `tools/functional_progress.py`, `docs/REQUIREMENTS_RECONCILIATION.json` e `docs/RELEASE_DASHBOARD.md`.

## Criterio architetturale certificato: SECDEV-02/criterion_04

I **23/23** servizi Joomla sono documentati individualmente nel registro `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json`, con scelta `native`/`native_plus_domain` e una motivazione distinta. Nessuno dei servizi è dichiarato non pertinente senza spiegazione; il codice JD Learn riusa servizi Joomla invece di prevederne copie funzionali indiscriminate.

Il nuovo `tests/secdev02-core-decision-signoff.py`, eseguito obbligatoriamente nella CI, rifiuta ID mancanti/duplicati, motivazioni generiche, decisioni sconosciute o abbassamento silenzioso dell'obbligo di verifica esterna sui sei servizi dipendenti dall'ambiente. Il gate non certifica `criterion_01–03` né i 23 criteri CORE pendenti.

## Riconciliazione dei 17 criteri con verifiche implementative residue

Le PR seguenti sono **già integrate** in `main`, ampliano concretamente le verifiche di codice ma **non coprono ogni contesto** descritto dal criterio canonico. Si registra il riferimento utile, non una promozione di stato.

| Criterio | Evidenza automatica integrata | Residuo prima della chiusura del criterio |
|---|---|---|
| CORE-01/all_hierarchy | #1056, `joomla-categories.php` | Combinazioni di gerarchie e permessi in tutte le entità editoriali |
| CORE-03/cross_context | #1056, `joomla-categories.php`, `categories-http.py` | Revoca di gruppi/ruoli nel flusso completo iscrizione-aula-area personale |
| CORE-04/record_acl | #1049, #1050, `joomla-editor-access.php` | Matrice per-record e batch su tutti i ruoli/editor |
| CORE-04/teacher_edit | #1051, `joomla-teachers.php` | Campi consentiti e negati in tutti i casi di sessione/checkout |
| CORE-05/layout_coverage | #1043, #1044, inventario MVC/override | Rendering override in tutti i template e moduli distribuiti |
| CORE-06/strings | #1031, #1052, validazione IT/EN | Chiavi costruite dinamicamente, mail e language override |
| CORE-07/menu_matrix | #1056, menu IT/EN e route `Itemid` | Incrocio integrale di menu, lingua, accesso, associazioni e fallback |
| CORE-09/tags_context | #1042, #1044, prove tag corso/docente | Tutti i contesti/filtri applicabili |
| CORE-10/teacher_scoping | #1053, `joomla-course-fields.php` | Permessi/combinazioni lingua-campi di lezione e docente |
| CORE-11/content_events | #1053, contenuto prima/dopo evento | Copertura completa dei plugin sui campi editoriali |
| CORE-13/all_routes | #1054, router sette viste | Matrice completa breadcrumb, SEF, lingua, ACL e accessi diretti |
| CORE-14/index_acl | #1045–1047, Finder persistente | Reindicizzazione e accesso per tutte le mutazioni rilevanti |
| CORE-15/native_workflow | #1054, transizioni percorsi | Workflow nativi e ACL per ogni entità/editor |
| CORE-16/all_entities | #1055, checkout e storico corso | Ripristini concorrenti per altre entità e versioni assegnate |
| CORE-19/idempotent_tasks | #1048, task mail/retry | Retry multibatch e diagnostica in esercizio |
| CORE-20/native_completeness | #1055, filtri amministrativi | Toolbar, batch, errori e recupero input in tutte le viste admin |
| CORE-21/private_cache | #1032, #1038, #1043, matrice endpoint | Prove su tutte le cache effettive e sessioni/configurazioni ulteriori |

**Non classificare questo elenco come “17 nuove funzioni da implementare”.** Sono ambiti di copertura/verifica residua: ogni difetto riproducibile richiede una correzione mirata. È il perimetro tecnico da esaurire prima di dichiarare terminato lo sviluppo.

## Sei criteri con dipendenze ambientali/esterne

| Criterio | Accettazione separata |
|---|---|
| CORE-02/auth_variants | MFA, login e provider identità reali |
| CORE-08/scope | Policy privacy, export e conservazione dati nel contesto reale |
| CORE-12/media_providers | Provider media, protezione URL e asset su hosting |
| CORE-18/smtp | Consegna SMTP e mail template configurati |
| CORE-22/release_updates | Upgrade/recovery su ambienti di destinazione |
| CORE-23/event_contracts | Integrazione con consumer/plugin terzi senza patch al componente |

**CORE-17** è già 2/2 nella matrice tecnica. L'accettazione amministrativa sull'ambiente reale rimane distinta.

## Lotto #1058 — prova dell'artefatto installabile, non soltanto dei sorgenti

La CI ora costruisce il package di sviluppo prima di eseguire `tests/secdev02-package-core-boundary.py` sullo ZIP realmente prodotto. Il test confronta i dodici costituenti con le dichiarazioni del manifest Joomla, i tipi/ID/gruppi, la versione timbrata degli ZIP figli e i percorsi installabili consentiti. Blocca traversal, file esterni al namespace JD Learn, ZIP non sicuri, duplicati o link simbolici; le fixture negative esercitano le principali deviazioni.

**Ambito:** rafforza le evidenze per SECDEV-02/criterion_03 (assenza di modifiche distribuite al core Joomla) e l'integrità CORE-22. Non certifica automaticamente l'assenza di *ogni* dipendenza da API rimosse, l'upgrade su hosting target o il collaudo Joomla: i 3 criteri SECDEV pendenti e i 23 CORE in revisione rimangono invariati. Nessuna percentuale viene promossa sulla sola aggiunta del gate.

## Condizioni operative per chiudere senza false dichiarazioni

1. Completare le prove o correzioni implementative pertinenti ai **17** residui e registrarne test e risultato. Se una porzione è esclusivamente manuale, motivarne esplicitamente la riclassificazione, senza fingere test di sviluppo.
2. Verificare `SECDEV-02/criterion_01–03` contro package finale, Joomla target, metodi API e compatibilità e registrare i limiti della prova.
3. Mantenere la **CI completa** verde su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium, PSR-12, matrici requisiti e ZIP.
4. Registrare l'accettazione Joomla e dei provider come attività successive, distinta dalla chiusura degli sviluppi.
5. Solo con prove sufficienti modificare i singoli stati del registro JSON canonico e le percentuali derivate. Il comando `python3 tests/secdev02-closure-readiness.py --require-complete` deve rimanere bloccante finché l'accettazione integrale non risulta documentata.

## Comportamenti da testare nella presente PR

- Rimozione di un servizio dal registro: il nuovo gate fallisce.
- Motivazione duplicata o segnaposto: il nuovo gate fallisce.
- Riclassificazione fittizia di un criterio ambientale: il nuovo gate fallisce.
- Mancata evidenza per `criterion_04` dichiarato implementato: il gate fallisce.
- Suite CI completa verde senza alterare i 23 criteri CORE ancora privi di evidenza integrale.
