# Pubblicazione e catalogo — stato0.20.1

## Segnalazione aperta e priorità

Il collaudo utente segnala corsi che rimangono in bozza. L’utente precisa che non trova il comando di pubblicazione nell’interfaccia. Il nuovo accesso esplicito deve essere verificato anche sul sito. Questa guida spiega il percorso presente e la diagnostica0.20.1; non certifica la risoluzione sul sito. La stabilizzazione del flusso precede l'import CSV, come indicato in NEXT_STEPS.md (punto0 nella repository).

## Procedura amministrativa attuale

1. In **Corsi → Nuovo**, compilare titolo, categoria, lingua e livelli di catalogo/contenuto. Salvare e chiudere. Il campo Stato è intenzionalmente disabilitato: il salvataggio non pubblica un programma non validato.
2. Aprire **Programma** dall'elenco, oppure **Apri programma e pubblicazione** dal corso salvato dopo aver salvato/chiuso eventuali modifiche. Creare la versione in bozza se assente.
3. Aggiungere almeno un modulo e una lezione valida per ciascun modulo. Almeno una lezione del corso deve essere obbligatoria. Salvare ogni operazione.
4. Nella sezione Pubblicazione premere **Pubblica**. Se il workflow configurato offre più transizioni, selezionare quella desiderata ed eseguirla. Il workflow standard mostra direttamente il nome dell’azione sul pulsante. Verificare sia lo Stato del corso sia quello della versione. Una validazione fallita conserva la bozza e mostra un errore; un salvataggio riuscito delle lezioni non equivale alla pubblicazione.
5. Verificare catalogo e scheda con categoria, lingua e livello richiesti, poi la fruizione con un account studente avente il livello contenuto. La preassegnazione non concede tale livello.

La gestione è ancora parziale: mancano editor completo a schede, anteprima studente, revisione/archivio completi, clonazione e nuove versioni pubblicate. Non modificare lo stato tramite SQL o task generici per aggirare la validazione.

## Diagnostica

Il Programma mostra lo stato del corso e un motivo se il comando non è disponibile:

- Nessuna versione: crearla con il pulsante dedicato.
- Permesso di modifica stato assente: serve `core.edit.state` sull'asset del corso, oltre ai permessi di accesso/authoring.
- Plugin Joomla **Workflow - Pubblicazione** non attivo: un amministratore lo verifica in Sistema → Plugin. Non viene abilitato automaticamente.
- Nessuna transizione: verificare workflow assegnato alla categoria/default, fasi/transizioni pubblicate, opzione `publishing` 0/1 e `core.execute.transition` sull'asset della transizione. Gestione nativa: `administrator/index.php?option=com_workflow&extension=com_jlms.course`.

Se il comando esiste ma fallisce, leggere il messaggio: programma vuoto, configurazione non supportata, materiale non pronto, revisione obsoleta o transizione annullata. Ricaricare prima di riprovare una revisione obsoleta. Per riprodurre la segnalazione registrare versione Joomla/pacchetto, ruolo, categoria/workflow, tipo di lezione, azione e messaggio esatto, senza dati personali o segreti. Un override amministrativo precedente può nascondere nuovi avvisi: confrontarlo con i layout distribuiti.

## Contenuti pubblicabili e limiti

Sono supportate lezioni testo con corpo non vuoto e completamento manuale, video validi e quiz con snapshot valido. Sequenza libera, completamento di tutte le attività obbligatorie e attestati disattivati. Non ancora supportati prerequisiti, rilascio differito, preview di lezione, audio/documenti come lezioni autonome e regole avanzate. Gli allegati a corso/lezione sono supportati se pronti e conformi alla politica di scansione; non vanno confusi con lezioni documento.

Per video esterni serve la politica consenso configurata (`click`/`site_consent`); il default bloccato impedisce la pubblicazione. Durata dichiarata/confermata fra1secondo e8ore, provider configurato e tracciamento posizione disponibile. Cloudflare richiede metadati remoti coerenti prima dei lock; HTML5 richiede un file pronto. Il quiz deve superare la validazione di QuizSnapshotService. Non tutte le capacità dei provider sono ancora implementate; vedere le rispettive guide.

## Contratto e integrità

POST HTML interno `curriculum.transition`, con token Joomla, `jform[course_id]`, `version_id`, `revision` del corso, `expected_version_revision` e `transition_id`. Non è un endpoint JSON pubblico.

Il servizio autorizza e valida parent, revisioni e check-out; acquisisce lock corso → versione. Limiti:1000moduli,5000lezioni, corpo testo massimo1MiB letto in lotti di16. Congela titolo, presentazione, regole, struttura e hash dei contenuti; crea una sola edizione default. Versione, puntatore corrente, edizione, associazione e stato workflow cambiano nella stessa transazione. Veto/errori ripristinano i dati. Nessun bypass del plugin Publishing o modifica delle versioni pubblicate.

Ritiro e ripubblicazione della versione corrente non ricreano edizioni né hash/timestamp. Il ritiro attuale può bloccare anche la fruizione: chiusura nuove iscrizioni e sospensione erogazione restano da separare (R-03). Gli eventi di terze parti devono evitare effetti esterni non transazionali.

## Catalogo e verifiche

Catalogo: `index.php?option=com_jlms&view=courses`; scheda: `index.php?option=com_jlms&view=course&id=ID`. Router/menu/SEF nativi. Richiesti corso e versione corrente pubblicati, disponibilità temporale, livello catalogo, lingua e categoria/antenati pubblicati, accessibili e coerenti. Lookup non autorizzato404. Scheda e programma pubblico non espongono corpi, sorgenti o risposte. Dati personalizzati `private, no-store`.

`tests/joomla-publication.php` verifica servizio, rifiuti, congelamento e rollback; `tests/http-smoke.py` aggiunge nella0.20.1 creazione iniziale dalle form native, editor → programma → versione → modulo → lezione → prima pubblicazione → scheda, oltre a programma vuoto, plugin disabilitato e POST ripetuto. Conserva le precedenti verifiche di ritiro/ripubblicazione. Le prove devono risultare verdi nella CI del build effettivo; non sostituiscono il riscontro sul sito segnalante, tutte le configurazioni/ruoli/provider o l'accettazione completa.
