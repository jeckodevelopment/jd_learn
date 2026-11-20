# DOC-08 — Matrice di copertura documentale e gate release 1.0.0

La fonte machine-readable del requisito è [CORE_FLOW_DOCUMENTATION_1.0.0.json](CORE_FLOW_DOCUMENTATION_1.0.0.json): **21 flussi della baseline** con descrizione, requisito, percorso della guida realmente presente e accettazione manuale separata. I flussi non sono nuove funzioni: la mappa collega le procedure già disponibili e impedisce riferimenti documentali mancanti. Non si deve considerare `guide_review=mapped` come certificazione che un utente inesperto abbia completato il walkthrough.

## Controlli automatici

- `python3 tests/doc08-gate.py` verifica copertura completa e non duplicata, presenza di ogni guida non vuota, riferimenti non relativi fuori dal repository e obbligo dell'evidenza per uno stato `passed`.
- `python3 tools/doc08_gate.py` valida gli stessi riferimenti. Entrambi sono invocati in `tools/check.py` e quindi nel gate CI e packaging.
- `python3 tools/doc08_gate.py --release` è il **gate pre-release**: fallisce se anche uno dei 21 flussi è `pending` o `failed`, oppure se manca l'accettazione complessiva. **Lo stato corrente è pending**; il comando deve quindi fallire sulla branch candidata, fino a esecuzione documentata dei collaudi. Il gate standard non deve impedire lo sviluppo ordinario durante la fase pre-accettazione.

## Come registrare un walkthrough

Per ogni flusso, conservare un file Markdown versionato nel repository con ambiente Joomla/PHP, commit della release candidate, ruolo utilizzato, passaggi eseguiti, esito, messaggi reali e collegamenti a evidenze redatte senza segreti o dati personali. Solo dopo esito positivo impostare `manual_acceptance=passed` e `evidence` al relativo percorso. La presenza di un file non deve essere fabbricata: una review finale indipendente deve verificare il contenuto e l'effettiva corrispondenza alla candidata. Se un test fallisce, mantenere `failed` e lasciare `evidence=null` in questa matrice; registrare il difetto nel testbook e in issue.

Per una nuova funzionalità **in perimetro 1.0.0** aggiungere un ID obbligatorio a `REQUIRED` in `tools/doc08_gate.py` e la relativa riga nella mappa **nella stessa PR**; aggiungere guida, test e QA prima di dichiararla pronta. I flussi differiti alla fase 2 non vanno immessi come funzionalità disponibili della release.

## Limiti

Questo gate prova esistenza e collegamento delle guide, non che le istruzioni siano complete, accessibili, localizzate o funzionanti su un sito Joomla. Le prove manuali IT/EN e i criteri WCAG/ACL/privacy restano in `docs/QA_TESTBOOK.md` e `docs/RELEASE_PROCEDURE.md`. Il guard CLI con `--release` non sostituisce la delibera umana di rilascio.
