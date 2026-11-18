# Audit probatorio JD Learn 1.0.0 — frontend (18 novembre 2026)

## Esito

Riconciliati **10 criteri** della matrice `FUNCTIONAL_CRITERIA_1.0.0.json` con sorgenti e controlli automatici esistenti. Questa è **evidenza di implementazione**, non una dichiarazione di accettazione complessiva Joomla. Non sono state modificate funzionalità runtime, versioni o metriche globali.

| Requisito | Criteri riconciliati | Evidenze e limiti |
| --- | --- | --- |
| SCR-02 | category_contents, hidden_children | Layout categoria, query di accesso alle categorie, verifica strutturale FE-08. Restano dipendenze editoriali e l'assenza di accessi concessi implicitamente da verificare in combinazione. |
| SCR-03 | presentation, protected_program | Layout scheda corso e proiezione server `CatalogService::outline`, con test FE-02. Azioni complete e attestati non dichiarati risolti. |
| SCR-04 | criterion_01, criterion_02, criterion_04 | Associazioni ordinate, stati individuali e calcolo che attribuisce zero ai corsi indisponibili; `tests/joomla-pathways.php` e FE-07. Il calcolo di ogni combinazione non viene dato per accettato manualmente. |
| SCR-05 | criterion_01, criterion_02 | Stati di apprendimento distinti dall'accesso e filtro/paginazione del modello My Courses; FE-05. Restano da valutare attestato, azioni in tutte le transizioni, storico e stato vuoto. |
| SCR-06 | criterion_02 | Navigazione adiacente read-only senza chiamata di completamento; test FE-04. Non generalizzare agli altri trigger di completamento. |

## Vincoli

Tutte le evidenze sono percorsi del repository e gli ID rimangono invariati. L'assenza di una prova non equivale automaticamente a un requisito mancante; i criteri non dimostrati restano `unassessed`. Non attribuire avanzamento funzionale globale finché la verifica di tutti i 214 requisiti e il trattamento dei criteri misti non sono completati. I test manuali Joomla restano un asse distinto dai test CI e dalla sola presenza del codice.

## Comportamenti da testare

- Validare unicità di ID e riferimenti `evidence`, `functional_progress` senza percentuale globale prematura.
- Eseguire CI standard, inclusi i test strutturali FE-02, FE-04, FE-05, FE-07 e FE-08 già presenti nei gate.
- Durante la futura accettazione Joomla verificare filtri combinati e categorie nascoste, scheda corso e ACL, progressi aggregati e storico personale, autoplay/completamento delle lezioni e ripresa in condizioni revocate.

PR documentale: nessun nuovo collaudo manuale dichiarato.
