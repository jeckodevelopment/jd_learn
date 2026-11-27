# CORE-03 — revoca entitlement fra contesti frontend (aggiornato 27 novembre 2026)

**Esito integrazione:** [PR #1097](https://github.com/jeckodevelopment/jd_learn/pull/1097), merge `93b241a98e400a4bbe7fd03ff8946eafd8623de2`. CI [#4939](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672529) 4/4 verde; [gate CSV #2297](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672532) superato. Artefatto installabile [JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672529/artifacts/15234490645), ZIP interno 882919 byte, SHA-256 `5bdd680bc3b2cfee32dc1feaa643936fbccece17b41011d5c0414dc7daa09500`; archivio Actions SHA-256 `f2d361ba4735b04b1f0f07487710c38e1c553ed239fd8f282f40e6a8d4887e42`.


La suite `tests/dashboard-http.py` verifica in una sessione autenticata sia la revoca del gruppo Joomla sia quella indipendente del viewing access level, mantenendo il gruppo, nei principali contesti learner: storico/iscrizioni, ripresa, classroom, area personale, scheda corso e catalogo. `tests/joomla-learning.php` copre inoltre aula, completamento e replay di iscrizione dopo il cambio del livello, con invalidazione e ripristino dei diritti Joomla.

## Comportamento automatico atteso

- Dopo revoca del gruppo o del viewing access level, la cronologia rimane visibile al proprietario ma non propone la ripresa.
- Il task `enrollment.resume` e l'accesso diretto alla classroom sono negati.
- Scheda e catalogo conservano soltanto i metadati pubblici: nessun corpo lezione privato.
- Dopo il ripristino nella stessa sessione, la ripresa torna disponibile e lo storico resta invariato.

Le prove usano fixture Joomla disposable e non modificano dati di installazioni reali. `CORE-03/cross_context` è implementato nel perimetro dei contesti core coperti; ruoli ulteriori, URL SEF e accettazione sulla specifica installazione restano collaudo manuale. La CI non sostituisce tale accettazione; gli scenari sono registrati in `docs/QA_TESTBOOK.md`.
