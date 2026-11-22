# SECDEV-03 — gate input, query e output (1.0.0)

**Stato: verifica incrementale, non chiusura di SECDEV-03 e non penetration test.** La baseline non include un esportatore CSV applicativo (import/export massivo rimandato); il rischio spreadsheet injection sarà riesaminato prima di introdurre un produttore CSV.

## Evidenze verificabili in CI

| Rischio | Superficie provata | Evidenza | Limite |
|---|---|---|---|
| SQL injection / escaping LIKE | Ricerca catalogo con quote, commenti, UNION e wildcard | `component/site/src/Helper/CatalogSearch.php`, `tests/catalog-literal-search.php`, CI standard | Non dimostra la sicurezza di ogni query del componente |
| Stored XSS / output HTML | Presentazione corso su modello Joomla e HTML filtrato, script, iframe, immagini e attributi attivi | `tests/joomla-course-presentation.php` in CI Joomla disposable | Non copre tutte le viste e tutti gli override/template |
| Mass assignment | Il modello Corso ignora `params_json`, `current_version_id` e stato di pubblicazione arbitrari | `tests/joomla-course-presentation.php` in CI | Occorre audit delle altre mutazioni |
| Esecuzione dinamica | Scansione locale Semgrep con fixture negative PHP/JS/Python | `tests/secdev07-sast.py`, `security/secdev07-semgrep.yml`, CI standard | Regole statiche selezionate, non analisi esaustiva |
| Formule CSV | Nessun produttore CSV applicativo nel sottoinsieme runtime verificato; tripwire se introdotto | `tests/secdev03-input-output-gate.py` | Non sostituisce test di escaping quando la funzionalità sarà prevista |

`tests/secdev03-input-output-gate.py`, eseguito in `tools/check.py`, impedisce di perdere questi test oppure introdurre un produttore CSV nelle superfici individuate senza revisione specifica. Il controllo non riscrive dati, non accede a segreti e non attribuisce un risultato a endpoint non provati.

## Per chiudere formalmente il requisito

- Ricognizione completa dei controlli server-side e delle query dinamiche su controller, servizi, modelli, plugin e task inclusi nella candidata finale, con findings e correzioni.
- Test negativi E2E per i principali confini di autorizzazione e mutazione, inclusi payload malevoli su parametri di ricerca, campi HTML/URL e campi riservati.
- Evidenza distinta di template e override effettivamente installati, configurazioni provider e hosting Joomla reale.
- Riesame del rischio CSV quando una funzione di esportazione verrà proposta.
- Registrazione dello stato in `docs/FUNCTIONAL_CRITERIA_1.0.0.json` solo dopo evidenze complete e CI del commit finale.

Non marcare `SECDEV-03/criterion_01/02/04` come `implemented` sulla base della sola presenza del gate. `criterion_03` resta non completato nella matrice fino alla review trasversale.
