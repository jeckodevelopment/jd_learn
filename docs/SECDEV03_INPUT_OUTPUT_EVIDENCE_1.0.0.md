# SECDEV-03 — gate input, query e output (1.0.0)

**Stato: verifica incrementale, non chiusura di SECDEV-03 e non penetration test.** La baseline non include un esportatore CSV applicativo (import/export massivo rimandato); il rischio spreadsheet injection sarà riesaminato prima di introdurre un produttore CSV.

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

## Per chiudere formalmente il requisito

- Ricognizione completa dei controlli server-side e delle query dinamiche su controller, servizi, modelli, plugin e task inclusi nella candidata finale, con findings e correzioni.
- Test negativi E2E per i principali confini di autorizzazione e mutazione, inclusi payload malevoli su parametri di ricerca, campi HTML/URL e campi riservati.
- Evidenza distinta di template e override effettivamente installati, configurazioni provider e hosting Joomla reale.
- Riesame del rischio CSV quando una funzione di esportazione verrà proposta.
- Registrazione dello stato in `docs/FUNCTIONAL_CRITERIA_1.0.0.json` solo dopo evidenze complete e CI del commit finale.

Non marcare `SECDEV-03/criterion_01/02/04` come `implemented` sulla base della sola presenza del gate. `criterion_03` resta non completato nella matrice fino alla review trasversale.

## Rafforzamento perimetro di controllo (23/11/2026)

Il gate non si limita più ai controller, ai template e ai plugin: esamina tutti i file PHP distribuiti sotto `component/`, `libraries/` e `plugins/`, compresi servizi applicativi, modelli e classi di dominio. I casi negativi del controllo comprendono `fputcsv`, `SplFileObject`, risposte HTTP `text/csv` o `application/csv` e writer CSV; sono presenti controlli benigni che non devono fallire. Il controllo è volutamente conservativo e richiede analisi umana degli eventuali falsi positivi. Le chiamate indirette, implementazioni JavaScript e il contenuto prodotto non sono analizzati semanticamente: queste limitazioni restano aperte ai gate SECDEV-03 e SEC-P5.

## Regressione modello Percorso (23/11/2026)

Il test Joomla disposable sui percorsi include contenuto ostile nella descrizione e campi protetti extra nel payload di creazione. Dopo il salvataggio viene letta la riga realmente persistita in MySQL/MariaDB e vengono verificate sia la rimozione dei vettori HTML attivi sia l'impossibilità di sovrascrivere JSON grezzo, autore e checkout. La verifica riguarda il modello percorso ed è separata dai controlli E2E sulla release candidate (SEC-P5).

## Validazione rigorosa degli artwork dei percorsi (23/11/2026)

Rilevata la conversione implicita a stringa di input `cover_image` e `cover_alt` nel modello amministrativo dei percorsi: un array, un oggetto o un valore UTF-8 invalido non deve essere salvato come metadato editoriale. Il modello ora controlla esplicitamente che entrambi i valori siano stringhe e che il testo alternativo sia UTF-8 valido prima della normalizzazione. `tests/joomla-pathways.php` esegue casi negativi sul modello reale e verifica, dopo ciascun rifiuto, la conservazione di revisione, JSON e descrizione persistiti. Il gate SECDEV-03 mantiene la prova obbligatoria. L'intervento è limitato alla superficie Percorsi; non sostituisce l'audit di tutti i form.

## Binding SQL delle associazioni Percorso–Corso (23/11/2026)

Individuato un inserimento in `#__jlms_pathway_courses` che concatenava tre interi previamente normalizzati. Per aderire pienamente alla policy SECDEV-03 sui dati in input, l'operazione usa ora parametri SQL `pathwayId`, `courseId` e `position` con tipo intero attraverso il query builder Joomla. La suite `tests/joomla-pathways.php` verifica gli inserimenti ordinati nel database e rifiuta identificativi di corso simili a SQL con conservazione delle associazioni già persistite. `tests/secdev03-input-output-gate.py` controlla che il binding non venga rimosso. Questa verifica non costituisce audit completo di tutte le query del repository.
