# SECDEV-03 — gate input, query e output (1.0.0)

**Stato: perimetro software implementato per 1.0.0, previa CI verde della PR di chiusura; collaudo Joomla/manuale e gate di rilascio separati, NON penetration test.** La baseline non include un esportatore CSV applicativo (import/export massivo rimandato); il rischio spreadsheet injection sarà riesaminato prima di introdurre un produttore CSV.

## Evidenze verificabili in CI

| Rischio | Superficie provata | Evidenza | Limite |
|---|---|---|---|
| SQL injection / escaping LIKE | Ricerca catalogo con quote, commenti, UNION e wildcard, incluse prove reali con binding su MySQL e MariaDB | `component/site/src/Helper/CatalogSearch.php`, `tests/catalog-literal-search.php`, `tests/joomla-publication.php` e CI PHP/database | Non dimostra la sicurezza di ogni query del componente |
| Stored XSS / output HTML | Presentazione corso su modello Joomla e HTML filtrato, script, iframe, immagini e attributi attivi | `tests/joomla-course-presentation.php` in CI Joomla disposable | Non copre tutte le viste e tutti gli override/template |
| Mass assignment | Il modello Corso ignora `params_json`, `current_version_id` e stato di pubblicazione arbitrari | `tests/joomla-course-presentation.php` in CI | Occorre audit delle altre mutazioni |
| XSS e mass assignment sui percorsi | Modello Joomla Pathway: contenuto con script, iframe, attributi attivi e URL javascript, più tentativo di sovrascrivere `params_json`, autore e checkout | `tests/joomla-pathways.php` eseguito nella matrice PHP/DB e riferito dal gate SECDEV-03 | Non verifica ancora tutte le altre mutazioni, i ruoli e gli override |
| Esecuzione dinamica | Scansione locale Semgrep con fixture negative PHP/JS/Python | `tests/secdev07-sast.py`, `security/secdev07-semgrep.yml`, CI standard | Regole statiche selezionate, non analisi esaustiva |
| Formule CSV | Scansione di tutti i file PHP distribuiti in componente, librerie e plugin; tripwire su nuovi produttori CSV e fixture negative | `tests/secdev03-input-output-gate.py` | Non sostituisce test di escaping quando la funzionalità sarà prevista |

`tests/secdev03-input-output-gate.py`, eseguito in `tools/check.py`, impedisce di perdere questi test (compresa la prova SQL su database reale con parametri associati) oppure introdurre un produttore CSV in controller, servizi, modelli, template o plugin senza revisione specifica. La possibilità di caricare o scaricare un allegato CSV come file protetto non costituisce un esportatore di dati tabellari generati dall'applicazione; la policy MIME degli allegati è separata. Il controllo non riscrive dati, non accede a segreti e non attribuisce un risultato a endpoint non provati.

## Perimetro approvato e separazione fra sviluppo e collaudo

SECDEV-03 valuta l'implementazione **del pacchetto JD Learn 1.0.0**: acquisizione e validazione server, campi modificabili, costruzione query, HTML generato e test di regressione. I controlli implementativi fanno riferimento al codice e alle suite seguenti:

1. `tests/sec-p1-controller-gates.py` verifica l'inventario e le protezioni method/CSRF dei 37 controller; `tests/sec-p1-cross-surface-gate.py` cataloga i 10 plugin, gli eventi e i tre task scheduler; il threat model di riferimento è `docs/SEC_P1_THREAT_MODEL_1.0.0.md`.
2. `tests/sec-p1-systematic-source-gate.py` scandisce i PHP distribuiti per sink dinamici, deserializzazione, accesso diretto a dati non attendibili e processi/trasporti HTTP fuori allowlist; `tests/secdev07-sast.py` integra Semgrep locale su PHP, JS e Python.
3. `tests/joomla-publication.php`, `tests/catalog-literal-search.php` e `tests/joomla-pathways.php` dimostrano binding/escaping SQL e rifiuto di identificativi SQL-like su database reali; `tests/joomla-course-presentation.php`, `tests/joomla-teachers.php`, `tests/joomla-pathways.php` e `tests/joomla-curriculum.php` esercitano filtri HTML, whitelist e mass assignment.
4. `tests/secdev03-input-output-gate.py` impedisce l'introduzione di un produttore di CSV senza mitigazione dedicata. L'**export tabellare CSV applicativo è escluso dal runtime 1.0.0**, pertanto la prova è il rilevamento preventivo dei sink, non una simulazione di un export inesistente.
5. `tests/secdev03-implementation-coverage.py`, eseguito dal preflight, collega questi controlli a CI, inventaria tutti i sorgenti PHP distribuiti, i controller/plugin e i template e rifiuta il passaggio diretto di `$_GET`/`$_POST`/`$_REQUEST`/`$_COOKIE` a query ed output HTML. Le fixture negative verificano che il controllo stesso non sia inerte.

Questa è una verifica **implementativa software**: non certifica l'assenza assoluta di vulnerabilità, non esegue penetration test e non comprende override di template esterni al pacchetto, server, plugin di terze parti o configurazione dell'hosting. La revisione indipendente, la matrice completa di ruoli/oggetti in un'installazione reale e il collaudo operativo rimangono nelle attività manuali dell'utente e nei gate SEC-P2/SEC-P5; **non sono residui di sviluppo di SECDEV-03**. La chiusura implementativa non modifica `Accettazione Joomla`, che resta `Non eseguita`. Ogni nuova superficie sarà soggetta ai gate antiregressione e a revisione prima del rilascio.

## Rafforzamento perimetro di controllo (23/11/2026)

Il gate non si limita più ai controller, ai template e ai plugin: esamina tutti i file PHP distribuiti sotto `component/`, `libraries/` e `plugins/`, compresi servizi applicativi, modelli e classi di dominio. I casi negativi del controllo comprendono `fputcsv`, `SplFileObject`, risposte HTTP `text/csv` o `application/csv` e writer CSV; sono presenti controlli benigni che non devono fallire. Il controllo è volutamente conservativo e richiede analisi umana degli eventuali falsi positivi. Le chiamate indirette, implementazioni JavaScript e il contenuto prodotto non sono analizzati semanticamente: queste limitazioni restano aperte ai gate SECDEV-03 e SEC-P5.

## Regressione modello Percorso (23/11/2026)

Il test Joomla disposable sui percorsi include contenuto ostile nella descrizione e campi protetti extra nel payload di creazione. Dopo il salvataggio viene letta la riga realmente persistita in MySQL/MariaDB e vengono verificate sia la rimozione dei vettori HTML attivi sia l'impossibilità di sovrascrivere JSON grezzo, autore e checkout. La verifica riguarda il modello percorso ed è separata dai controlli E2E sulla release candidate (SEC-P5).

## Validazione rigorosa degli artwork dei percorsi (23/11/2026)

Rilevata la conversione implicita a stringa di input `cover_image` e `cover_alt` nel modello amministrativo dei percorsi: un array, un oggetto o un valore UTF-8 invalido non deve essere salvato come metadato editoriale. Il modello ora controlla esplicitamente che entrambi i valori siano stringhe e che il testo alternativo sia UTF-8 valido prima della normalizzazione. `tests/joomla-pathways.php` esegue casi negativi sul modello reale e verifica, dopo ciascun rifiuto, la conservazione di revisione, JSON e descrizione persistiti. Il gate SECDEV-03 mantiene la prova obbligatoria. L'intervento è limitato alla superficie Percorsi; non sostituisce l'audit di tutti i form.

## Binding SQL delle associazioni Percorso–Corso (23/11/2026)

Individuato un inserimento in `#__jlms_pathway_courses` che concatenava tre interi previamente normalizzati. Per aderire pienamente alla policy SECDEV-03 sui dati in input, l'operazione usa ora parametri SQL `pathwayId`, `courseId` e `position` con tipo intero attraverso il query builder Joomla. La suite `tests/joomla-pathways.php` verifica gli inserimenti ordinati nel database e rifiuta identificativi di corso simili a SQL con conservazione delle associazioni già persistite. `tests/secdev03-input-output-gate.py` controlla che il binding non venga rimosso. Questa verifica non costituisce audit completo di tutte le query del repository.

## URL pubbliche dei docenti: parametri riservati (23/11/2026)

Il form Docente già rifiutava scheme non HTTP(S) e userinfo nell'URL `website`, ma non bloccava token o segreti trasmessi tramite query o fragment. Il modello adesso riutilizza il rilevatore di parametri sensibili di `EditorialLinkSafety`, già applicato ai collegamenti HTML editoriali. I test di integrazione `tests/joomla-teachers.php` tentano URL con `accessToken`, `clientSecret` e `api_key`, verificando il rifiuto e l'immutabilità della revisione; un URL pubblico innocuo resta valido. Questo è un intervento puntuale sulla superficie Docenti, non un audit generale di tutte le uscite HTML o delle fonti esterne.
