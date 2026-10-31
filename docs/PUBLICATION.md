# Pubblicazione e catalogo — incremento 0.3

Il corso viene pubblicato dalla pagina **Programma** tramite un POST `curriculum.transition`, token Joomla e campi `course_id`, `version_id`, `revision` del corso, `expected_version_revision`, `transition_id`. Sono task HTML interni, distinti dagli endpoint JSON pianificati.

## Workflow Joomla

L’installer crea un workflow nativo per `com_jlms.course`, due fasi e transizioni di pubblicazione/rimozione con relativi asset ACL. Non modifica workflow preesistenti. Si gestiscono nel core: `administrator/index.php?option=com_workflow&extension=com_jlms.course`. Per funzionare richiede il plugin core **Workflow - Publishing**, disponibile nel Joomla standard. Se disabilitato o se un plugin annulla la transizione, l’operazione fallisce e i dati vengono ripristinati. Non viene abilitato automaticamente un plugin disattivato dall’amministratore.

Permessi necessari: identità attiva, `core.manage`, `core.edit.state` sull’asset corso e `core.execute.transition` sull’asset della transizione. Le transizioni offerte devono appartenere al workflow/fase corrente, essere pubblicate e avere opzione `publishing` 0/1. Il componente usa `Workflow::executeTransition`, eventi e plugin core. Il cambiamento di stato del modello è ammesso soltanto durante la transizione validata dal servizio, per impedire bypass tramite task generici. La scelta del workflow iniziale rispetta i parametri di categoria e il default core.

## Validazione e transazione

Ordine lock: corso, versione, come nell’authoring. Controlli: proprietà del parent, revisioni, check-out del corso; almeno un modulo, ogni modulo non vuoto, almeno una lezione obbligatoria, corpi testo non vuoti, massimo 1.000 moduli e 5.000 lezioni. Attualmente sono ammessi solo testo/completamento manuale, sequenza libera e completamento di tutte le lezioni obbligatorie, senza attestati, release di lezione, preview, prerequisiti, quiz o allegati. Lezioni video sono salvabili in bozza ma impediscono la pubblicazione dell’intera versione. Sono limitazioni dichiarate dell’incremento, non modifiche allo scope finale.

Il servizio congela titolo, presentazione, regole e un hash SHA256 del programma con hash dei corpi, imposta `published_at` e crea una sola edizione `default-vN`. Il calcolo legge i corpi in lotti di massimo 16 lezioni con paginazione keyset e limite di 1 MiB per corpo, anche con driver che bufferizzano i risultati. Puntatore corrente, versione, edizione e associazione/stato del workflow sono nella stessa transazione InnoDB; una transizione rifiutata non lascia una versione parzialmente pubblicata. Gli eventi di plugin terzi devono evitare effetti esterni non transazionali; outbox e Action Logs sono ancora pianificati.

Le versioni pubblicate non sono editabili. Rimozione e ripubblicazione cambiano la visibilità del corso senza ricreare edizioni o cambiare hash/timestamp della versione. Clonazione/equivalenze non disponibili. Restano da completare i workflow editoriali con fasi senza cambio di stato e l’integrazione Content History/campi/tag.

## Pagine pubbliche

- Catalogo: `index.php?option=com_jlms&view=courses`, paginazione Joomla (limite 1–100), ordine ID decrescente.
- Filtro categoria e discendenti: `&catid=ID` dai link di categoria delle schede.
- Scheda: `index.php?option=com_jlms&view=course&id=ID`, anche configurabile come voce di menu Joomla. RouterView e regole menu/standard/no-menu native; segmenti numerici senza lookup di alias riservati.

Ogni query richiede corso pubblicato, versione corrente pubblicata dello stesso corso, livello catalogo corrente, lingua `*`/corrente, finestra date UTC e categoria valida. Categoria e tutti gli antenati devono essere pubblicati, accessibili e compatibili con la lingua. Lookup diretto non autorizzato: 404. Nessuna cache condivisa, header `private, no-store`; eventuali plugin Page Cache/CDN esterni devono rispettare queste pagine personalizzate. Il livello contenuto viene soltanto indicato, non concede iscrizioni o gruppi.

La scheda mostra titolo, descrizione e titoli di moduli/lezioni obbligatorie/opzionali. Non legge corpi, sorgenti media, allegati o dati personali. L’aula e le iscrizioni non sono ancora disponibili. L’albero categorie dedicato, ricerca, filtri avanzati, cache mirata e test di carico 300/500 restano nella roadmap.

## Collaudo

`tests/joomla-publication.php` sul sito effimero prova rifiuti, rollback su veto workflow, congelamento, ACL, immutabilità, visibilità corrente, antenati, filtro discendenti, rimozione e ripubblicazione. `tests/http-smoke.py` verifica catalogo e scheda realmente renderizzati e assenza dei corpi protetti. La CI esegue inoltre lint/test base e upgrade da 0.1 con dati conservati su quattro combinazioni PHP/database. Consultare i risultati Actions effettivi della PR: predisporre i test non equivale a superarli.
