# Ciclo di vita di corsi, percorsi e docenti — requisiti approvati 1.0.0

Decisione funzionale del 15 novembre 2026. Stato: **requisiti approvati; primo incremento LCM-01 integrato con PR #484 (CI 4/4); completamento funzionale e collaudo Joomla ancora pendenti**. La presenza di un workflow editoriale di archiviazione in JD Learn **non** equivale al blocco della fruizione già acquisita; verificare entrambi separatamente.

## Regole generali

- Utilizzare ove possibile toolbar, filtri di stato, selezione multipla, ACL e transizioni native Joomla; distinguere Pubblicato, Non pubblicato, Archiviato (ove pertinente), Cestinato. Per i docenti non introdurre uno stato Archiviato autonomo.
- La cestinazione è reversibile; ripristinare non equivale a pubblicare o riaprire iscrizioni. Le transizioni devono essere autorizzate server-side, protette da CSRF e registrate nell'audit disponibile.
- Vietare la cancellazione fisica in presenza di utenti collegati, iscrizioni o dipendenze storiche da conservare, anche se l'account Joomla è inattivo. Un vincolo FK non sostituisce il controllo applicativo: mostrare un messaggio comprensibile e non eseguire cancellazioni a cascata.
- Ogni controllo dev'essere applicato anche agli endpoint diretti, non soltanto alla UI. Preservare ACL, accessi riservati, versioni e dati personali secondo retention approvata.

## Corsi

- **Pubblicato:** accessi e iscrizioni governati dalle regole esistenti.
- **Non pubblicato:** non visibile pubblicamente; senza nuove iscrizioni o accesso alla didattica.
- **Archiviato:** niente nuove iscrizioni, avvio/ripresa, lezioni, player o materiali, inclusi URL diretti; escluso dal catalogo attivo. Restano nella storia personale titolo/partecipazione/completamento/dati di frequenza e attestati precedentemente emessi, consultabili e scaricabili secondo autorizzazione e retention. L'archiviazione non completa né annulla iscrizioni o progressi.
- **Cestinato:** escluso dagli elenchi ordinari; nessuna nuova iscrizione o fruizione; record di frequenza, progressi e attestati preservati e protetti. Verificare compatibilità con la vista storico personale.
- **Riattivazione:** possibile da archiviato con permesso appropriato; nessuna riapertura automatica delle iscrizioni né modifica dei progressi.
- **Eliminazione definitiva:** consentita soltanto per record privi di dipendenze significative e utenti collegati; includere versioni/lezioni/iscrizioni/progressi/attestati e riferimenti esterni nel controllo, con messaggio esplicativo.

## Percorsi

Stessi stati e azioni del corso, inclusa archiviazione, storico e protezione della fruizione aggregata. Archiviare o cestinare un percorso **non** modifica né elimina i corsi che lo compongono, anche se sono condivisi con altri percorsi. Non perdere storico/attestati eventualmente collegati.

## Docenti

Consentire pubblicazione/non pubblicazione/cestinazione/ripristino, senza stato Archiviato aggiuntivo. La cessazione dell'attività non elimina l'utente Joomla né i riferimenti storici a corsi/lezioni; l'eliminazione definitiva è vietata se esistono associazioni o registrazioni da preservare.

## Incremento LCM-01 / PR #484 — protezione della fruizione su stato corso

La query condivisa `CatalogService::courseQuery()` richiede ora `c.state = 1` anche per `enrolledCourse()`: i corsi non pubblicati (`0`), archiviati (`2`) o cestinati (`-2`) non possono essere recuperati da `LearningService::authorisedEnrollment()` e dalle chiamate che la utilizzano, comprese aula e mutazioni di completamento. Il catalogo già escludeva i corsi con stato diverso da 1 e le nuove iscrizioni automatiche richiedevano la vista pubblica. Il limite della finestra promozionale (`publish_down`) resta distinto: da solo non interrompe un'iscrizione attiva. I record iscrizione/progresso non vengono modificati dal controllo.

**Limiti:** non equivale al completamento di LCM-01: vanno ancora ricontrollati tutti i flussi laterali, transizioni concorrenti, download, player, notifiche, migrazioni, storico/attestati e accessi per URL diretti in Joomla. Le azioni toolbar, ripristino controllato e protezione delle eliminazioni restano LCM-02 e successivi. Non attribuire accettazione prima dei test manuali.


### LCM-01 — migrazioni amministrative e notifiche didattiche (PR #486 integrata, CI 4/4)

Un corso con stato diverso da pubblicato (`state != 1`) non può attivare una migrazione di iscrizione verso una nuova versione: l'anteprima presenta un blocco e l'esecuzione ricontrolla la condizione dopo il lock transazionale. La migrazione non altera lo storico se è bloccata. I job di avviso **nuova lezione** e **modifica significativa dopo migrazione** selezionano solo corsi pubblicati; l'outbox ricontrolla lo stato al momento del dispatch così da non inviare notifiche didattiche accodate prima di un'archiviazione. Le comunicazioni amministrative e le attestazioni storiche non sono cancellate automaticamente. Restano da auditare gli altri producer e i diritti storici nel gate LCM-01/LCM-05.

### LCM-01 — storico personale e percentuali dei corsi di consultazione (PR #488 integrata, CI 4/4)

La dashboard storica mantiene la proiezione delle iscrizioni dalla versione didattica immutabile anche quando il corso è non pubblicato (`0`), archiviato (`2`) o cestinato (`-2`). L'aula rimane indisponibile, ma titolo, stato di completamento e progresso acquisito restano nello storico. Per i corsi in modalità **consultazione** non viene mostrata alcuna percentuale: il riconoscimento del tipo di corso viene effettuato sulla versione assegnata, indipendentemente dallo stato corrente e dalla visibilità nel catalogo. Nessuna mutazione di storico o ampliamento dei permessi. Collaudo Joomla pendente; i PDF già emessi sono gestiti dal servizio attestati con controllo titolarità distinto dal catalogo.

### LCM-01 — attestati storici dopo archiviazione o cestinazione (PR #490 integrata, CI 4/4)

Il servizio `CertificateService::listForStudent()` e il download privato degli attestati si basano su titolarità dell'iscrizione, completamento e documento corrente non revocato, **non** sullo stato editoriale attuale del corso. Questo separa definitivamente la conservazione dell'attestazione dal diritto di riprendere le lezioni. Un test Joomla dedicato prepara un attestato da snapshot immutabili e verifica per stati `0/2/-2` elenco titolare, download PDF autenticato, 404 per utente estraneo, 401 per visitatore, identità del documento invariata, nessuna duplicazione, negazione della ripresa e permanenza del PDF dopo upgrade del pacchetto. Il test di download ha evidenziato un bug nel lock SQL parametrizzato del rendering differito, corretto nella stessa PR; invariata la politica di revoca e i permessi. CI automatica non equivale al collaudo manuale con sito reale.

### LCM-01 — ripristino dell'archivio senza riapertura automatica (0.60.1)

Il workflow di archiviazione imposta admissions_open=0 e delivery_enabled=0 nella stessa transazione del passaggio allo stato Archiviato. La transizione inversa porta lo stato editoriale a Pubblicato conservando la versione didattica immutabile, ma non riapre automaticamente ammissioni o fruizione. LearningService applica già delivery_enabled all'accesso per gli iscritti, inclusi i link diretti, restituendo HTTP 403 per il corso sospeso. La modifica al workflow garantisce che il blocco rimanga attivo anche dopo il ripristino. Per riaprire iscrizioni o didattica servono comandi espliciti con audit e permessi. I test Joomla verificano flag prima/dopo ripristino, diniego dell'aula e permanenza della versione. Rimangono audit trasversale e accettazione reale.

### LCM-01 — gate sulle operazioni manuali di riattivazione (0.60.2)

Le operazioni amministrative esistenti `admissions_open` e `delivery_resume` sono ammesse solo se lo stato editoriale del corso è Pubblicato (`state=1`). Anteprima e conferma server-side condividono il blocco, per impedire la riapertura anticipata mentre il corso è archiviato, cestinato o non pubblicato. Chiudere le ammissioni o sospendere l'erogazione rimane possibile. Non è previsto alcun automatismo in fase di Restore; la riattivazione rimane esplicita e soggetta a permessi, motivazione e audit. Test Joomla per tentativi negativi e storico invariato; accettazione reale pendente.

### LCM-01 — riattivazione della singola iscrizione (0.60.3)

Il passaggio amministrativo di un'iscrizione da Sospesa ad Attiva è consentito soltanto quando lo stato del corso è Pubblicato e `delivery_enabled=1`. Il comando non è un'alternativa per riaprire un corso archiviato: editor e POST applicativo condividono lo stesso gate, e la conferma serializza il corso prima dell'iscrizione. Restano permesse le operazioni di sospensione e annullamento secondo le regole ordinarie. I test Joomla negano la riattivazione su corsi archiviati, non pubblicati, cestinati o con erogazione sospesa, senza scrivere audit né notifiche. Accettazione reale e verifica altri endpoint ancora pendenti.

### LCM-02 — cestinazione con blocco operativo conservativo (0.60.4)

Cestinare un corso non deve lasciare impostazioni operative pre-armate: la stessa transazione di `CourseModel::changeStates()` imposta `admissions_open=0` e `delivery_enabled=0` quando porta lo stato a Cestinato (`-2`). Il ripristino editoriale allo stato Non pubblicato mantiene i due flag a zero. La riapertura è possibile solo successivamente e con i comandi autorizzati, mai in modo implicito. Le versioni didattiche, iscrizioni, progressi e attestati restano conservati. Test Joomla di cestinazione/ripristino su un corso precedentemente operativo, accettazione reale ancora pendente.

### LCM-05 — indicatore di disponibilità nei percorsi (0.60.5)

Nella vista di dettaglio del percorso un corso Pubblicato ma con erogazione didattica disabilitata rimane visibile ai soggetti autorizzati come elemento del programma, ma non può essere indicato come fruibile. `PathwayService::detail()` restituisce accesso `unavailable`, preservando lo stato personale di apprendimento. Gli utenti privi di livelli Joomla continuano a visualizzare il corso come `restricted`; nessuna esposizione di informazioni private aggiuntive. Alla riattivazione amministrativa dell'erogazione, il normale indicatore di accesso torna disponibile. Test Joomla automatici; collaudo reale ancora pendente.

### LCM-05 — messaggio mirato di sospensione (0.60.6)

La vista di dettaglio di un percorso comunica esplicitamente che l'erogazione didattica di un corso visibile è sospesa, senza confonderla con un diniego di autorizzazione. Il flag `delivery_suspended` è esposto soltanto quando il corso è catalogabile per il soggetto e il suo livello di contenuto è autorizzato; per i corsi `restricted` la scheda non rivela lo stato operativo. Il riavvio esplicito dell'erogazione elimina il messaggio. Copertura Joomla e frontend statica; accettazione reale da eseguire.

### LCM-05 — notifiche Smart Search dopo transizioni corso (0.60.7)

Cestinazione e ripristino dei corsi eseguiti con aggiornamento diretto sul modello notificano il plugin Finder dopo il commit tramite evento Joomla `onFinderAfterSave` e snapshot persistito del corso. Il plugin Finder applica il filtro di indicizzazione dei soli corsi pubblicati; l'eventuale errore di refresh non altera il successo dell'operazione editoriale. Il test Joomla verifica gli eventi dopo cestinazione e ripristino, con ammissioni ed erogazione chiuse. Collaudo end-to-end Smart Search, categoria, cache e SEO resta pendente.

### LCM-05 — Finder dopo Archive/Restore del workflow (0.60.8)

Le transizioni Joomla di archiviazione e ripristino passano da `PublicationService::transition()`: oltre agli eventi eventualmente emessi dal workflow prima del commit, il servizio invia una notifica Finder con lo stato già persistito, così l'indice possa escludere il corso archiviato e riconsiderare quello ripristinato solo secondo i propri filtri. I gate `admissions_open` e `delivery_enabled` restano chiusi al ripristino. Il refresh non è parte della transazione: il suo fallimento viene registrato e non annulla il workflow. Test Joomla su eventi e campi salvati; collaudo E2E Finder/SEO ancora pendente.

## Piano di implementazione separabile

1. **LCM-01 (P0):** audit stato attuale, semantica unificata e tutela iscrizioni, storico, attestati; introdurre check lato server per corsi archiviati/non pubblicati/cestinati in tutti i flussi di accesso e iscrizione.
2. **LCM-02 (P1):** toolbar, filtri, cestinazione e ripristino corsi; controllo dipendenze su eliminazione fisica.
3. **LCM-03 (P1):** stessi controlli e archiviazione percorsi, senza effetti sui corsi inclusi.
4. **LCM-04 (P1):** gestione docenti e associazioni storiche; mai eliminare l'account Joomla associato.
5. **LCM-05 (gate):** test automatici/UI Joomla, ACL/CSRF, retrocompatibilità e upgrade dati, catalogo/Finder/SEO, link diretti, storico personale/attestati, documentazione e collaudi.

### LCM-02 — cestinazione e ripristino conservativo corsi (PR #614)

Dal pacchetto 0.59.0 la vista Corsi espone il filtro Cestinati e toolbar native per cestinare/ripristinare, anche in selezioni massive entro 100 ID. Il controller applica POST, CSRF e validazione stretta; il modello applica ACL `core.manage`, `core.edit`, `core.edit.state` per ciascun corso, lock InnoDB e rollback in caso di diniego. La cestinazione imposta `state=-2`; il ripristino esclusivamente da `-2` imposta `state=0`, **mai 1**, senza cancellare versioni o riferimenti di iscrizione. In ogni altro stato, il comando Restore è respinto. I test su database Joomla e richieste HTTP coprono le nuove operazioni; il collaudo manuale resta separato. La purga fisica rimane deliberatamente bloccata, in attesa di un controllo esplicito delle dipendenze interne/esterne. Il workflow editoriale di archiviazione (`state=2`) rimane distinto dal cestino.

### LCM-02 — purga condizionata (0.60.0)

Solo dalla vista Cestinati è possibile chiedere la cancellazione fisica di **un singolo** corso mediante conferma, POST, token CSRF e permesso `core.delete` sull'asset specifico. La transazione blocca la riga prima di verificare stato `-2`, check-out e assenza di versione attiva, versioni salvate, edizioni, iscrizioni, docenti, percorsi, operazioni, notifiche/campagne, associazioni multilingue. La cronologia **editoriale Joomla del medesimo corso vuoto** viene eliminata esplicitamente nella stessa transazione, perché l'operazione elimina definitivamente il suo oggetto; nessuna versione didattica, frequenza, attestato o altra cronologia degli studenti può essere eliminata. La cancellazione usa la tabella Joomla nativa, con vincoli FK `ON DELETE RESTRICT` come ulteriore protezione; **non cancella mai a cascata** dati didattici. In presenza di un qualsiasi riferimento, blocca la purga con un messaggio e conserva tutti i dati.

Sono documentati test di negazione su versione didattica esistente e richiesta diretta senza metodo/token. Rimane da confermare in CI il caso positivo di corso realmente privo di qualsiasi storico, oltre alla verifica dei riferimenti di estensioni terze e al collaudo manuale. Non attribuire automaticamente completamento di `LCM-02` o accettazione finale.

## Comportamenti da testare sul sito Joomla

1. Pubblicare e archiviare un corso con un iscritto incompleto e uno completato; entrambi mantengono stato/progressi e attestati, ma non aprono lezioni/materiali/player e non possono iscriversi nuovamente.
2. Verificare endpoint diretti, download protetti, catalogo, Smart Search e cache dopo l'archiviazione.
3. Ripristinare corso archiviato: storico invariato e iscrizioni non riaperte automaticamente.
4. Cestinare/ripristinare corso, percorso e docente via selezione e azioni massive, con ruoli autorizzati e non autorizzati.
5. Negare eliminazione definitiva quando esiste una dipendenza storica o utente collegato anche non più attivo; mostrare il motivo senza alterare database.
6. Archiviare percorso con corsi riutilizzati altrove: nessun cambiamento dello stato dei corsi; preservare gli eventuali attestati di percorso.
7. Cestinare docente collegato a corso o utente Joomla: mantenere identità e riferimenti storici.
8. Aggiornare un'installazione con dati preesistenti e verificare ACL, multilingua e nessuna regressione.

Non indicare accettazione finché i test su un sito Joomla reale non sono registrati in QA_TESTBOOK.md.

### LCM-04 — prima tranche: cestinazione e ripristino docenti

Il profilo docente utilizza esclusivamente gli stati Pubblicato (1), Non pubblicato (0) e Cestinato (-2): **nessuno stato Archiviato**. Sono presenti filtro Cestinati e toolbar Joomla per azioni massive di pubblicazione, sospensione della pubblicazione, cestinazione e ripristino. Il ripristino torna esclusivamente a Non pubblicato e la transizione diretta dal cestino a Pubblicato è respinta anche lato server. Ogni selezione richiede POST/CSRF, ACL `jlms.teachers.manage`, `core.edit` e `core.edit.state` per elemento; la transazione è atomica. Non vengono eliminati o modificati account Joomla, relazioni con i corsi né snapshot didattici. **Residui:** eliminazione fisica condizionata, verifica completa dei riferimenti storici esterni e accettazione Joomla reale.

### LCM-04 — eliminazione definitiva condizionata del docente

La gestione amministrativa consente ora l'eliminazione definitiva di **un solo profilo docente alla volta**, esclusivamente se già Cestinato, con `core.delete` sull'asset docente, controllo del checkout, richiesta POST e token CSRF, selezione singola e conferma esplicita. L'eliminazione è **negata** se esistono assegnazioni in `#__jlms_course_teachers` o un riferimento al docente nell'array `teachers` di una versione didattica immutabile (`#__jlms_course_versions.presentation_json`): la verifica delle versioni usa `JSON_CONTAINS` anziché cercare identificativi con una sottostringa. Il modello mantiene la transazione di controllo e cancellazione e non elimina mai utenti Joomla, corsi, iscrizioni o snapshot. Test Joomla coprono blocco per associazione e blocco storico dopo rimozione dell'associazione, oltre alla cancellazione di un profilo cestinato privo di relazioni.

**Accettazione ancora pendente:** controlli manuali Joomla e audit di eventuali riferimenti di terze parti; non equiparare la copertura delle dipendenze note alla prova che ogni integrazione esterna sia priva di riferimenti.
