# Audit probatorio 1.0.0 — Provider video e pacchetto (18 novembre 2026)

## Fonte di verità

Baseline `main` dopo PR #607. Registro canonico: `docs/FUNCTIONAL_CRITERIA_1.0.0.json`; perimetro: `docs/REQUIREMENTS_STATUS.csv`.

La verifica distingue **evidenza di implementazione** (sorgenti e fixture CI), **esito della CI** e **accettazione manuale Joomla**. Le tre dimensioni non sono intercambiabili. Nessuna esecuzione reale dei quattro provider video è stata effettuata con questa PR.

## Criteri verificati positivamente (13)

- `PVD-01` — 3/3: manifest del pacchetto contenente quattro plugin; registro con versione contrattuale; importazione del gruppo Joomla e distinzione stato abilitato/configurato. Evidenza: `packages/pkg_jlms.xml`, `tools/build.py`, `component/administrator/services/provider.php`, `libraries/jlms/src/Video/VideoProviderRegistry.php`, `tests/joomla-smoke.php`.
- `PVD-02` — primi 6/8: registry e interfaccia; evento applicativo `onJlmsRegisterVideoProviders` inviato dal componente; registrazione factory dai plugin; natura applicativa dell'evento; chiave univoca/major contract; rifiuto di collisioni e incompatibilità. I due criteri su operazioni complete e documentazione di contratto **restano da valutare**.
- `PVD-06` — 1/3: interfaccia e registro consentono una factory aggiuntiva senza necessità di modificare il modello del corso. Le garanzie complete di avanzamento e di gestione delle capability per provider diversi **restano da verificare**.
- `PBASE-01` — primi 3/5: struttura installabile e modularità Joomla dei quattro plugin. Installazione simultanea effettiva e assenza di pubblicazione automatica/credenziali esposte restano da verificare singolarmente.

## Limiti di questa verifica

Il codice indica che le protezioni sono implementate; non dimostra automaticamente che siano prive di regressioni, che tutti i casi d'uso dei quattro provider funzionino dal vivo o che il sito di prova abbia superato il collaudo. I riferimenti nelle `evidence` sono file esistenti del repository, non verbali di collaudo. Nessun requisito viene marcato completo per prossimità; nessuna percentuale globale viene sbloccata.

## Comportamenti da testare

1. `tools/functional_progress.py` e `tests/functional-progress.py`: JSON valido, file `evidence` presenti, 214 requisiti censiti, percentuale globale ancora `null`.
2. `tests/joomla-smoke.php`: registrazione nativa dei quattro provider.
3. In sede di collaudo (fuori da questa PR): installazione ZIP, abilitazione/disabilitazione plugin, consent, riproduzione e completamento su ciascun provider, transizioni/ACL e browser.

Questa PR è esclusivamente documentale: nessuna modifica runtime, nessun incremento SemVer.
