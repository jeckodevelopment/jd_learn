# Riconciliazione criteri non verificabili — dipendenze esterne (22/11/2026)

## Ambito e regola di classificazione

Questa prima tranche esamina due criteri della matrice `docs/FUNCTIONAL_CRITERIA_1.0.0.json` senza equiparare test automatici e accettazione Joomla. Nessun criterio viene promosso a `implemented` in assenza di prova completa. Il conteggio di partenza è 259 criteri `unverifiable`.

| Requisito / criterio | Evidenza disponibile | Lacuna rispetto al criterio | Esito |
| --- | --- | --- | --- |
| LCM-03 / external_references | `component/administrator/src/Model/PathwayModel.php`, metodo `purgeEmpty`; `tests/joomla-pathways.php` per relazioni interne | Non esiste un inventario verificato di tabelle e riferimenti creati da estensioni terze | `unverifiable`, motivazione confermata |
| LCM-04 / external_references | `component/administrator/src/Model/TeacherModel.php`, metodo `changeStates`; `tests/joomla-teachers.php` per relazioni note | Non è dimostrata la protezione di riferimenti di estensioni terze non presenti nel repository | `unverifiable`, motivazione confermata |

## Condizioni di chiusura

1. Definire esplicitamente il confine di responsabilità delle integrazioni esterne nella specifica 1.0.0: se non sono supportate, delimitare il criterio anziché dichiarare supporto universale.
2. Se sono supportate, inventariare le tabelle/relazioni delle integrazioni dichiarate e aggiungere test automatici per cancellazione, blocco e conservazione delle referenze.
3. Separare la prova automatica del codice dalla successiva verifica manuale su un'installazione Joomla con le integrazioni previste.
4. Aggiornare i singoli record JSON, il registro di riconciliazione e la percentuale soltanto dopo nuove evidenze verificabili.

## Comportamenti da testare successivamente su Joomla

- Percorso con riferimenti esterni: nessuna cancellazione che lasci riferimenti pendenti.
- Docente con riferimenti esterni: nessuna eliminazione definitiva impropria.
- Percorso/docente senza riferimenti: eliminazione consentita soltanto con ACL e stato appropriati.
- I casi con estensioni terze non censite restano esplicitamente fuori dall'accettazione.

**Risultato di questa tranche:** 2 criteri esaminati, 0 promossi, 0 falsi completamenti, 257 criteri ancora da esaminare. Avanzamento globale invariato al 61,73% finché non emergono evidenze nuove.
