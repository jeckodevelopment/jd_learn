# CORE-03 — revoca entitlement fra contesti frontend (aggiornato 27 novembre 2026)

La suite `tests/dashboard-http.py` verifica in una sessione autenticata sia la revoca del gruppo Joomla sia quella indipendente del viewing access level, mantenendo il gruppo, nei principali contesti learner: storico/iscrizioni, ripresa, classroom, area personale, scheda corso e catalogo. `tests/joomla-learning.php` copre inoltre aula, completamento e replay di iscrizione dopo il cambio del livello, con invalidazione e ripristino dei diritti Joomla.

## Comportamento automatico atteso

- Dopo revoca del gruppo o del viewing access level, la cronologia rimane visibile al proprietario ma non propone la ripresa.
- Il task `enrollment.resume` e l'accesso diretto alla classroom sono negati.
- Scheda e catalogo conservano soltanto i metadati pubblici: nessun corpo lezione privato.
- Dopo il ripristino nella stessa sessione, la ripresa torna disponibile e lo storico resta invariato.

Le prove usano fixture Joomla disposable e non modificano dati di installazioni reali. `CORE-03/cross_context` è implementato nel perimetro dei contesti core coperti; ruoli ulteriori, URL SEF e accettazione sulla specifica installazione restano collaudo manuale. La CI non sostituisce tale accettazione; gli scenari sono registrati in `docs/QA_TESTBOOK.md`.
