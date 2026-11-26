# CORE-07 — isolamento delle cache del catalogo per lingua e utente

## Intervento implementativo (26 novembre 2026)

In `CoursesModel` sono stati aggiornati due livelli distinti di caching:

1. `getStoreId()` nativo di Joomla include ora anche il tag lingua
   oltre all'ID dell'utente; le query della lista sono filtrate per lingua.
2. La memoizzazione `visibleCatalogCategories`, utilizzata dal filtro
   categoria, è associata a una chiave composta da utente Joomla,
   lingua corrente e categoria padre. Con un contesto diverso viene
   rieseguita la query con gli ACL correnti, anziché usare un array
   valorizzato precedentemente nello stesso modello.

Il filtro continua a usare esclusivamente `CatalogService::publicCourseCategories()`,
senza introdurre liste di accesso parallele. Le modifiche non riguardano
lo stato di iscrizioni, progressi o associazioni fra corsi tradotti.

## Prove automatiche

- `tests/joomla-categories.php`: isolamento tra guest/editor per
  ListModel e memoizzazione; ripristino chiavi al ritorno a guest.
- `tests/frontend-catalog-options.py`: controllo strutturale di
  chiave lingua e invalidazione categoria sul cambio contesto.
- I test e le prove precedenti su menu e ACL restano invariati.

## Confini CORE-04–07

- CORE-04: le ACL di record e docente sono già implementate nei servizi
  dedicati; restano verifiche incrociate per ruolo e operazioni batch.
- CORE-05: inventario degli override MVC e gate automatico già presenti;
  restano prove con template Joomla di terzi e browser.
- CORE-06: cataloghi lingua IT/EN e gate di coerenza già presenti;
  restano verifiche esaustive di override/email/messaggi.
- CORE-07: questo lotto corregge l'isolamento delle cache del catalogo;
  la matrice multilingua completa resta da collaudare.

Nessun criterio viene promosso a `implemented` solo per il presente
intervento; la percentuale complessiva resta invariata.

## Collaudo manuale Joomla (NON ESEGUITO)

Su Joomla 6.2.1 con menu/associazioni IT/EN e livelli di accesso diversi:

- Aprire catalogo in IT e EN; filtri e categorie devono rispettare lingua.
- Verificare un corso visibile a guest e uno riservato; cambiare account
  e ripetere per entrambe le lingue e le modalità SEF.
- Revocare e ripristinare un livello di accesso Joomla; controllare
  schede corsi e opzioni del filtro categoria.
- Ripetere con caching Joomla attivo e documentare i risultati effettivi
  in `docs/QA_TESTBOOK.md`.
