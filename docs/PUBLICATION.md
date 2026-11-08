# Pubblicazione e catalogo

La prima pubblicazione è disponibile ed è stata confermata dall’utente sul percorso provato. Questa guida descrive l’uso corrente; per funzioni e collaudi mancanti vedere [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Procedura amministrativa attuale

1. In **Corsi → Nuovo**, compilare titolo, categoria, lingua, descrizione estesa e livelli di catalogo/contenuto. Le bozze incomplete possono essere salvate, ma la prima pubblicazione richiede una descrizione con testo significativo. Il campo Stato è intenzionalmente disabilitato: il salvataggio non pubblica un programma non validato.
2. Scegliere **Salva e apri programma** nell’editor: salva i dati e prepara/riapre la bozza di lavoro. Dal corso già salvato si può anche aprire **Programma** nell’elenco.
3. Aggiungere almeno un modulo e una lezione valida per ciascun modulo. Almeno una lezione del corso deve essere obbligatoria. Salvare ogni operazione.
4. Nella sezione Pubblicazione premere **Pubblica**. Se il workflow configurato offre più transizioni, selezionare quella desiderata ed eseguirla. Il workflow standard mostra direttamente il nome dell’azione sul pulsante. Verificare sia lo Stato del corso sia quello della versione. Una validazione fallita conserva la bozza e mostra un errore; un salvataggio riuscito delle lezioni non equivale alla pubblicazione.
5. Verificare catalogo e scheda con categoria, lingua e livello richiesti, poi la fruizione con un account studente avente il livello contenuto. La preassegnazione non concede tale livello.

L’editor a schede, le duplicazioni e la pubblicazione degli aggiornamenti sono disponibili. Restano anteprima studente interattiva, revisione/archivio completi, analisi impatti e migrazioni. Per aggiornare un corso già pubblicato seguire [COURSE_EDITOR.md](COURSE_EDITOR.md). Non modificare lo stato tramite SQL o task generici per aggirare la validazione.

## Diagnostica

Il Programma mostra lo stato del corso e un motivo se il comando non è disponibile:

- Nessuna versione: crearla con il pulsante dedicato.
- Permesso di modifica stato assente: serve `core.edit.state` sull'asset del corso, oltre ai permessi di accesso/authoring.
- Plugin Joomla **Workflow - Pubblicazione** non attivo: un amministratore lo verifica in Sistema → Plugin. Non viene abilitato automaticamente.
- Nessuna transizione: verificare workflow assegnato alla categoria/default, fasi/transizioni pubblicate, opzione `publishing` 0/1 e `core.execute.transition` sull'asset della transizione. Gestione nativa: `administrator/index.php?option=com_workflow&extension=com_jlms.course`.

Se il comando esiste ma fallisce, leggere il messaggio: programma vuoto, configurazione non supportata, materiale non pronto, revisione obsoleta o transizione annullata. Ricaricare prima di riprovare una revisione obsoleta. Per segnalare una nuova regressione registrare versione Joomla/pacchetto, ruolo, categoria/workflow, tipo di lezione, azione e messaggio esatto, senza dati personali o segreti. Un override amministrativo precedente può nascondere nuovi avvisi: confrontarlo con i layout distribuiti.

## Controllo preliminare della bozza

Nella vista Programma di una versione in bozza, il riquadro **Verifica prima della pubblicazione** separa i problemi strutturali bloccanti dagli avvisi facoltativi. Al momento segnala descrizione estesa mancante, assenza di moduli, moduli senza lezioni, assenza di lezioni obbligatorie, riepilogo breve assente e copertina assente. I primi quattro sono bloccanti; riepilogo e copertina sono avvisi.

È un controllo rapido, non una promessa che il corso sia pubblicabile: non carica i corpi delle lezioni e non contatta provider. Al comando **Pubblica**, `PublicationService` ripete i controlli autorevoli su contenuti, materiali, provider, quiz, permessi e revisioni; eventuali errori mantengono la bozza. Un avviso non impedisce la pubblicazione.

## Contenuti pubblicabili e limiti

Sono supportate lezioni testo con corpo non vuoto e completamento manuale, video validi e quiz con snapshot valido. Sequenza libera, completamento di tutte le attività obbligatorie e attestati disattivati. Non ancora supportati prerequisiti, rilascio differito, preview di lezione, audio/documenti come lezioni autonome e regole avanzate. Gli allegati a corso/lezione sono supportati se pronti e conformi alla politica di scansione; non vanno confusi con lezioni documento.

Per video esterni serve la politica consenso configurata (`embed`/`site_consent`); il default bloccato impedisce la pubblicazione. Durata dichiarata/confermata fra1secondo e8ore, provider configurato e tracciamento posizione disponibile. Cloudflare richiede metadati remoti coerenti prima dei lock; HTML5 richiede un file pronto. Il quiz deve superare la validazione di QuizSnapshotService. Non tutte le capacità dei provider sono ancora implementate; vedere le rispettive guide.

## Contratto e integrità

POST HTML interno `curriculum.transition`, con token Joomla, `jform[course_id]`, `version_id`, `revision` del corso, `expected_version_revision` e `transition_id`. Non è un endpoint JSON pubblico.

Il servizio autorizza e valida parent, revisioni e check-out; acquisisce lock corso → versione. Limiti:1000moduli,5000lezioni, corpo testo massimo1MiB letto in lotti di16. Congela titolo, presentazione, regole, struttura e hash dei contenuti; crea una sola edizione default. Versione, puntatore corrente, edizione, associazione e stato workflow cambiano nella stessa transazione. Veto/errori ripristinano i dati. Nessun bypass del plugin Publishing o modifica delle versioni pubblicate.

Ritiro e ripubblicazione della versione corrente non ricreano edizioni né hash/timestamp. Il ritiro dal catalogo resta distinto dai comandi R-03 integrati nella 0.41.9: chiusura ammissioni e sospensione erogazione sono leve indipendenti, ciascuna con motivo e audit. Gli eventi di terze parti devono evitare effetti esterni non transazionali.

## Catalogo e verifiche

Catalogo: `index.php?option=com_jlms&view=courses`; scheda: `index.php?option=com_jlms&view=course&id=ID`. Router/menu/SEF nativi. Richiesti corso e versione corrente pubblicati, disponibilità temporale, livello catalogo, lingua e categoria/antenati pubblicati, accessibili e coerenti. Lookup non autorizzato404. Scheda e programma pubblico non espongono corpi, sorgenti o risposte. Dati personalizzati `private, no-store`.

`tests/joomla-publication.php` verifica servizio, rifiuti, congelamento e rollback; `tests/http-smoke.py` aggiunge nella0.20.1 creazione iniziale dalle form native, editor → programma → versione → modulo → lezione → prima pubblicazione → scheda, oltre a programma vuoto, plugin disabilitato e POST ripetuto. Conserva le precedenti verifiche di ritiro/ripubblicazione. Le prove devono risultare verdi nella CI del build effettivo; non sostituiscono il riscontro sul sito segnalante, tutte le configurazioni/ruoli/provider o l'accettazione completa.

## Confermare l’impatto di un aggiornamento

Per una bozza che aggiorna una versione già attiva, il comando iniziale apre la vista amministrativa `publicationreview` con un riepilogo di differenze e assegnazioni esistenti. Solo la successiva conferma esplicita pubblica. GET non pubblica; POST richiede sessione/token Joomla e ticket di revisione consumabile una volta, con scadenza15minuti. Nessun nuovo endpoint pubblico o cambio dei gruppi.

Il confronto usa chiavi logiche per moduli/lezioni e definizioni/riferimenti per quiz/materiali, ignorando gli ID e le revisioni rigenerati dalla copia. Sostituzioni degli asset dei materiali possono apparire come rimozione e aggiunta. Dettagli limitati alle prime100modifiche per tipo, con conteggi complessivi completi. Corpi, sorgenti e risposte corrette non vengono esposti; query aggregate/batch e hash limitano memoria e accessi. I conteggi delle iscrizioni sono informativi, anche storici; nuove ammissioni nel frattempo non alterano la conferma del programma.

`PublicationService::transition` richiede l’impronta restituita da `updateImpact` quando attiva una bozza di aggiornamento. La verifica è ripetuta sotto i lock corso/versione prima del congelamento. I client interni devono richiedere e confermare il riepilogo; nessuna conferma implicita di aggiornamenti. Cambi a corso, bozza, versione sorgente o contenuti producono un rifiuto. Identità/ACL/transizione/checkout e validazioni didattiche/media vengono ricontrollati. Prima pubblicazione e ritiro/ripubblicazione della medesima versione mantengono il flusso precedente. Il confronto non è un certificato di pubblicabilità e non avvia richieste ai provider.

Gli iscritti mantengono versioni, tentativi, progressi e file assegnati. La migrazione esplicita e l’anteprima interattiva restano nel residuo AUT-04/M02; il recupero storico è disponibile come nuova bozza separata e non chiude l’intero requisito.

## Recuperare un programma storico

Dal Programma → Versioni selezionare una versione storica e usare **Recupera come nuova bozza**. Le altre bozze, la scheda e la pubblicazione corrente e i dati studente restano invariati. La nuova pubblicazione richiede il normale riepilogo/conferma e i controlli dei provider. Contratto, permessi e procedura nella [guida editor](COURSE_EDITOR.md#recuperare-un-programma-storico).

## Soglia video della versione

La pubblicazione verifica che la soglia sia un intero valido in centesimi di percentuale (1–10000), con default90% quando manca nei dati legacy supportati. Il form Regole didattiche usa percentuali con massimo due decimali, senza arrotondamento in virgola mobile. Il riepilogo mostra soglia precedente e nuova quando cambiano; l’impronta include le regole e rifiuta conferme superate. Dopo la pubblicazione, ogni iscrizione continua a usare la soglia della versione assegnata; nessun ricalcolo dei completamenti esistenti.
