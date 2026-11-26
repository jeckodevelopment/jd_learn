# CORE-01 — convalida del genitore nella directory categorie (26 novembre 2026)

## Intervento implementativo

La directory `view=categories` distingue ora la richiesta senza
`parent_id` (radice) da una richiesta esplicita con identificativo
malformato. Prima del fix, `getInt()` e il successivo clamp a zero
potevano reindirizzare silenziosamente un genitore negativo o non numerico
alla directory principale.

`CategoriesModel::populateState()` accetta soltanto numeri interi
non negativi rappresentabili, normalizza gli zeri iniziali e rifiuta
input non numerici, negativi o fuori intervallo con HTTP 404.
L'assenza del parametro e `parent_id=0` continuano a indicare la radice;
gli identificativi positivi sono poi verificati tramite la query nativa
di categoria con stato, lingua e autorizzazioni correnti.

## Evidenze automatiche e limiti

`tests/categories-http.py` prova entrambe le modalità SEF, il 404 per
genitori negativi, testuali e overflow, la directory radice esplicita
e le verifiche preesistenti su ACL, lingua e menu.

La correzione non sostituisce il collaudo Joomla con menu/ACL reali.
CORE-01 rimane **Parziale** e nessuna percentuale viene promossa soltanto
per questo lotto.

## Collaudo manuale Joomla (NON ESEGUITO)

Su Joomla 6.2.1, con SEF attivo/disattivo e sessioni guest/autenticata:

- `index.php?option=com_jlms&view=categories&parent_id=-1` restituisce 404.
- `parent_id=nonvalido` e un ID troppo grande restituiscono 404.
- Nessun parametro e `parent_id=0` mostrano la radice.
- Una categoria pubblicata, visibile per lingua e livello di accesso
  viene visualizzata; una categoria nascosta o non autorizzata restituisce 404.
