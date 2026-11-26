# CORE-03 — revoca entitlement fra contesti frontend (26 novembre 2026)

La suite `tests/dashboard-http.py` verifica ora lo stesso cambio di appartenenza Joomla durante una sessione già autenticata nei principali contesti learner: lista iscrizioni, ripresa, classroom, area personale, scheda corso e catalogo.

## Comportamento automatico atteso

- Dopo la revoca del gruppo, la cronologia rimane visibile al proprietario ma non propone la ripresa.
- Il task `enrollment.resume` e l'accesso diretto alla classroom sono negati.
- Scheda e catalogo conservano soltanto i metadati pubblici: nessun corpo lezione privato.
- Dopo il ripristino del gruppo nella stessa sessione, la ripresa torna disponibile e lo storico resta invariato.

La prova usa il fixture Joomla disposable e non modifica i dati dell'installazione reale. Il criterio CORE-03/cross_context resta `unverifiable` finché non è dimostrata la coerenza dell'intero perimetro di viste, ruoli e transizioni. La CI non sostituisce il collaudo manuale Joomla; i casi di revoca/ripristino sono registrati nel testbook.
