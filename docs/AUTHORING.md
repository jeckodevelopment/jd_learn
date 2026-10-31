# Programma didattico — incremento0.2

Dal backend aprire **Componenti → JD Learn → Corsi → Programma didattico**. Il primo pulsante crea la versione1 in bozza; ripetere la richiesta restituisce la stessa bozza dopo aver rivalidato i permessi. Non vengono ancora pubblicati contenuti.

La schermata consente di creare/modificare moduli, aggiungere lezioni di testo con l’editor Joomla o riferimenti video normalizzati, scegliere l’obbligatorietà e riordinare gli elementi. Per i video abilitare il plugin corrispondente: il registro è estensibile anche con altri plugin compatibili. Le capacità player restano disattivate e il salvataggio di una sorgente non equivale a una riproduzione autorizzata.

La modifica si apre dai collegamenti Modifica accanto a modulo/lezione; un accesso al Programma senza edit_module/edit_lesson prepara i form per nuovi elementi. La rimozione di una lezione richiede conferma; un modulo non può essere rimosso finché contiene lezioni. Risorse con riferimenti a quiz, allegati o prerequisiti non vengono cancellate implicitamente.

## Sicurezza e concorrenza

I comandi HTML MVC sono `curriculum.createVersion`, `module.save`, `module.delete`, `module.reorder`, `lesson.save`, `lesson.delete`, `lesson.reorder`. Richiedono POST, sessione amministrativa, token Joomla e permessi sul corso. Non sono ancora le API JSON del catalogo70task: il trasporto API sarà implementato separatamente, senza cambiare le regole applicative.

L’Application Service viene registrato nel container DI e passato al componente. Controller/model sono adattatori della UI; le transazioni e i controlli sono nel servizio. I contenuti dell’editor usano il filtro del core Joomla. Ordinamento, identificativi logici, tipo di completamento, anteprima e revisione della sorgente sono controllati dal server.

Ogni mutazione blocca corso e versione nello stesso ordine; verifica expected_version_revision e, per gli aggiornamenti, la revisione dell’elemento. Le modifiche concorrenti obsolete vengono rifiutate. Tutti gli ID figli sono ricontrollati nel parent. Le versioni published/retired sono di sola lettura; la copia storica completa e il passaggio di lezioni fra moduli rimangono chiusi fino all’implementazione delle relative migrazioni.

La lettura del programma esegue query aggregate per moduli e lezioni e raggruppa i figli una sola volta. L’editor iniziale ammette fino a1000moduli e5000lezioni per versione; programmi più grandi richiederanno una vista paginata. Non vengono caricati player o API remote nel form.

## Confini dello sviluppo

Pubblicazione e workflow, nuovi tipi audio/documento/quiz, materiali protetti, prerequisiti, assegnazione agli studenti, clonazione storica, Content History e associazioni multilingua restano da completare. La struttura fisica è già versionata e non autorizza a modificare lo storico pubblicato.

## Pubblicazione del corso di testo (0.3)

Nel programma scegliere la transizione Joomla e premere **Esegui transizione**. Il server ricontrolla permessi, revisioni e struttura; vedere [PUBBLICAZIONE](PUBLICATION.md). Dopo la pubblicazione la versione è di sola lettura. Per nascondere o ripristinare il catalogo usare le transizioni successive; lo storico non cambia.
