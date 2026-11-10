# Area personale

La voce menu Joomla **I miei corsi** apre `index.php?option=com_jlms&view=dashboard`. La vista è riservata all'account autenticato e attivo, indipendentemente dal livello impostato sulla voce menu. Si consiglia un livello menu per utenti registrati. Non esiste un parametro per selezionare un altro utente: anche aggiungendo `user_id` o `id` alla query si vede esclusivamente lo storico del proprio account Joomla.

## Storico, apprendimento e disponibilità

La pagina raggruppa le iscrizioni non iniziate, in corso e completate, con filtro nativo e paginazione Joomla. Il totale riguarda tutte le iscrizioni dell'utente, comprese quelle sospese, annullate o non più accessibili. Il limite per pagina è massimo50. I gruppi sono quelli della pagina corrente, non conteggi globali distinti. Il filtro GET `filter[progress]` ammette `all`, `not_started`, `in_progress`, `completed`; valori diversi ricadono su `all`.

Il titolo proviene dallo snapshot della versione a cui l'utente è iscritto. Coerentemente con D-09, titolo storico e avanzamento rimangono consultabili quando la scheda viene ritirata o il livello di accesso viene revocato. Non vengono inclusi descrizioni delle lezioni, risorse, URL video, domande, risposte corrette, regole interne, chiavi soggetto o dati di altri utenti.

Lo stato didattico usa `completed_at`, altrimenti attività/contatori persistenti; il solo avvio di un'iscrizione non equivale a un'attività svolta. L'accesso all'aula è un'altra informazione: disponibile, livello insufficiente, lingua non disponibile, iscrizione sospesa/annullata/non attiva, finestra futura/scaduta oppure contenuto non disponibile. Un completamento non riapre una finestra scaduta. Le percentuali seguono la proiezione persistente, con limite99 in assenza di completamento registrato; contatori mancanti o incoerenti non generano una percentuale inventata.

Letture e azioni ricaricano identità e livelli Joomla. Un account bloccato non può consultare questa area. Revoche/ripristini dei gruppi sono visibili anche mantenendo la stessa sessione. Il componente non assegna gruppi e non gestisce vendite o abbonamenti.

## Riprendi e Rivedi

Le azioni compaiono soltanto quando l'aula risulta attualmente disponibile. Il collegamento usa `GET index.php?option=com_jlms&task=enrollment.resume&id=ID_ISCRIZIONE`; ogni altro metodo è rifiutato. È una navigazione in lettura e non richiede un token CSRF: non crea iscrizioni, non consuma tentativi e non attribuisce avanzamento.

`LearningService::resumeTarget` rilegge proprietario, livello del corso, catalogo/categorie/antenati, versione, edizione, stato e finestre dell'iscrizione. Se `last_lesson_id` appartiene alla versione ed è accessibile viene scelto; altrimenti la versione 0.41.5 seleziona la prima attività obbligatoria non completata nell’ordine del programma. Se tutte le attività obbligatorie sono complete, si apre la prima lezione per consentire la revisione. La destinazione viene nuovamente validata. Dalla 0.41.50 (PR #185) l’apertura di una lezione accessibile registra esplicitamente `last_lesson_id` e lo stato `started` via POST con token, senza credito; così Riprendi può usare anche una lezione testo/documento lasciata a metà. Il GET dell’aula rimane senza scritture. CI standard 4/4 superata nel run 42839639601; collaudo manuale Joomla ancora richiesto. Un programma incoerente rimane bloccato. La risposta del task è un redirect nativo all'aula autorizzata, non un JSON con contenuto didattico. I relativi endpoint dell'aula continuano ad applicare i propri controlli.

Una nuova revoca fra la visualizzazione e il clic rifiuta il redirect. Utente anonimo401, account bloccato o fruizione negata403, iscrizione non propria/assente404, ID non valido400, metodo diverso da GET405. Gli errori tecnici non espongono SQL o parametri privati.

Le iscrizioni in lingue diverse sono nello stesso storico. Con Filtro lingua attivo la lingua del corso deve avere pacchetto sito installato, contenuto pubblicato e accessibile; il redirect usa il tag lingua originale, per esempio `it-IT`, senza sostituire l'iscrizione con quella di un corso associato. Con Filtro lingua disattivato è disponibile la lingua dell'interfaccia corrente e i contenuti comuni `*`. Per conservare la pagina personale durante un cambio lingua associare le voci menu con gli strumenti nativi Joomla.

## MVC, dati e prestazioni

`DashboardService::query` seleziona soltanto iscrizioni del proprietario, con join coerenti a versione/edizione e proiezione minima dello storico. `cards` costruisce il modello pubblico e carica la visibilità corrente con una query catalogo per lingua presente nella pagina, senza interrogare l'aula o i corpi delle lezioni per ogni riga. `DashboardModel` usa ListModel/Pagination e Form Joomla; `HtmlView`/layout sono sostituibili tramite override. Non ci sono nuove tabelle, migrazioni, lock di lettura condivisi o cache di storico fra utenti.

Risposte `private, no-store`, header `X-Robots-Tag: noindex, nofollow` e metadati robots. Il collegamento dall'aula usa `jlms-classroom-dashboard`. Vedere FRONTEND_CSS.md per tutte le regioni e variabili CSS. Non rimuovere ownership, escaping, controlli sul redirect e intestazioni di riservatezza negli override.

## Collaudo e perimetro residuo

La CI verifica account distinti e vuoto, filtri/paginazione, completamento separato dalla scadenza, sospensione e finestre future, revoche e blocco durante la sessione, ritiro del catalogo, ripresa valida/ID fuori versione, lingue e conservazione al reinstall. Le prove HTTP usano login/menu/form nativi, SEF0/1 e controllano che i GET non cambino progressi o tentativi. Gli esiti effettivi sono quelli delle Actions della PR #16.

Questa prima area personale non include attestati, elenco dettagliato delle attività da correggere/ripetere, richieste di iscrizione, calendario/scadenze con fuso utente o dashboard amministrativa degli iscritti. La disponibilità mostrata è quella ordinaria dell'aula: un quiz già in corso segue ancora il suo endpoint e le regole di grazia esistenti, senza una nuova azione dedicata in questa vista. La sequenza/prerequisiti avanzati restano chiusi. STU-01/STU-03 e CORE-13 rimangono parziali; browser/accessibilità completi, patch corrente Joomla e carico300/500 devono ancora essere collaudati.
