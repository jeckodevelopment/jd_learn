# Gestione dei corsi —0.33

## Modificare i dati del corso

Aprire Corsi → Modifica, cambiare titolo, descrizioni, immagine, docenti, accessi o calendario e scegliere Salva oppure Salva e chiudi. Il corso pubblicato resta pubblicato. Le correzioni editoriali aggiornano la scheda pubblica e vengono registrate nello storico Joomla.

Il pulsante Versioni apre Content History di Joomla per confrontare le revisioni editoriali e ripristinarle. Il ripristino passa dagli stessi controlli del salvataggio, con permessi, blocco modifica e revisione corrente. La nota di versione può descrivere l’intervento. La configurazione del componente permette di abilitare lo storico e definirne il limite. Il ripristino editoriale conserva la versione didattica attiva e le assegnazioni degli iscritti.

## Aggiornare lezioni e materiali

Nel Programma di un corso pubblicato, scegliere Modifica programma. Il sistema prepara o riapre la versione di lavoro con moduli, lezioni, quiz, domande e collegamenti ai materiali. Da qui si usano i normali comandi di modifica, aggiunta e caricamento. I file immutabili esistenti vengono riutilizzati, con associazioni indipendenti per la nuova versione.

Pubblica aggiornamento esegue la transizione del workflow e rende disponibile il programma aggiornato per le nuove iscrizioni. Gli iscritti esistenti conservano versione, progressi, tentativi, risultati e materiali assegnati. Le modifiche al programma possono essere preparate durante l’erogazione. La migrazione degli iscritti rimane un’operazione esplicita da sviluppare secondo AUT-04.

## Docenti

Nella scheda Docenti del corso si associano i profili desiderati. Un profilo può essere creato senza scegliere una categoria. I campi aggiuntivi si definiscono con il gestore Campi di Joomla nel contesto Docenti. Visibilità, lingua, gruppi di campi e permessi di modifica dei valori seguono il core.

Un profilo può essere collegato a un account Joomla. Con il permesso Modifica propri, il docente può aprire la propria pagina e scegliere Modifica il profilo. Può cambiare presentazione e campi autorizzati. Pubblicazione, visibilità e collegamento dell’account sono gestiti dalla segreteria autorizzata. L’associazione a un corso non assegna automaticamente permessi o gruppi.

I tipi di voce di menu Pagina docente e Corsi di un docente consentono rispettivamente la pagina completa o il solo elenco dei corsi. Le viste rispettano pubblicazione, lingua e livelli di accesso correnti del profilo e dei corsi. I campi personalizzati pubblicati seguono le proprie ACL. L’email dell’account non viene aggiunta automaticamente al profilo.

## Compatibilità e limiti

Le categorie dei profili preesistenti restano conservate per mantenere ACL e disponibilità già configurate. La scheda docente presenta un flusso senza scelta di categoria. I nuovi profili ricevono un contenitore tecnico predisposto all’installazione.

Le versioni didattiche già assegnate rimangono consultabili dall’amministrazione. Il corso mantiene il proprio storico editoriale Joomla separato dalle assegnazioni didattiche. Revisione editoriale, versione del programma e tentativo di quiz hanno funzioni distinte. Workflow avanzato, migrazioni, lezioni audio/documento e completamento di tutte le integrazioni M10 seguono la roadmap.

## Duplicare corso, modulo e lezione —0.32

Nel Programma, Duplica corso apre titolo e riepilogo: viene copiata la versione selezionata in una nuova identità in bozza, nella stessa categoria e lingua. Il titolo è modificabile prima di confermare, l’alias è nuovo e i permessi si ereditano dalla categoria. Non si copiano ACL specifiche del record, associazioni di traduzione, workflow storico, iscrizioni, progressi, tentativi o attestati. La copia include impostazioni/promozione, docenti, valori dei campi Joomla autorizzati e un programma completo con moduli, lezioni, quiz e materiali. Gli asset immutabili e le revisioni domanda restano riutilizzati tramite nuove associazioni; nessuna pubblicazione automatica.

Nel programma di lavoro, ogni modulo e lezione espone Duplica modulo/Duplica lezione. La copia del modulo contiene tutte le lezioni e i relativi quiz/materiali. La copia di una lezione può essere collocata in un modulo scelto della stessa versione di lavoro. Le copie si aggiungono in fondo, hanno nuovi ID e chiavi logiche; quelle del modulo mantengono l’ordine relativo delle lezioni. I prerequisiti interni al modulo si rimappano alle copie; quelli richiesti dalle lezioni copiate verso attività esterne restano riferiti alle attività originali della stessa versione. Le attività originali non vengono reindirizzate alle copie.

Serve poter modificare la sorgente e creare nella categoria del nuovo corso. Si applicano anche i permessi sui quiz, sui materiali e sui valori dei campi coinvolti; un diniego impedisce la copia completa, senza omettere silenziosamente quelle parti. Le copie di moduli/lezioni richiedono un programma in bozza; checkout altrui e revisioni obsolete impediscono la scrittura. La destinazione di una lezione deve appartenere alla stessa versione. Per copiare un elemento pubblicato in un corso attivo, aprire prima Modifica programma e usare i comandi sulla copia di lavoro.

Questi comandi sono distinti dalla nuova versione dello stesso corso. Duplicazione e rollback sono verificati dalle suite joomla-duplication.php e duplication-http.py nella CI del build; l’esistenza delle suite non attesta l’esito. Restano riordino con trascinamento/accessibilità, altre parti dell’editor e criteri M01 da completare.

## Elenco ed editor0.33 — convenzioni articoli Joomla

Corsi usa la toolbar nativa Nuovo/Modifica/Programma/Opzioni, checkbox di selezione, titolo cliccabile, Search Tools e paginazione. Ricerca per titolo/alias o id:123; filtri stato, categoria con discendenti, livello di accesso ai contenuti, autore Joomla, lingua e blocco modifica. Sono ordinabili titolo, stato, categoria, accesso, autore, lingua, ultima modifica e ID. I filtri persistono tornando dall’editor; un cambio filtro torna alla prima pagina, con massimo100righe. L’autore è il creatore del record Joomla, distinto dai profili docente associati.

La riga mostra metadati e collegamenti Programma/Iscrizioni se autorizzati; lo stato apre la gestione della pubblicazione nel Programma. Non esegue un toggle che aggiri workflow e validazioni. Con selezione, Programma richiede un solo corso e ricontrolla i permessi. Nessuna azione massiva di stato viene simulata.

L’editor mostra titolo, alias, sintesi e descrizione nella prima scheda, con categoria, lingua, stato e accesso essenziale nella sidebar. Programma, Docenti, Immagine e Pubblicazione sono schede dirette; Opzioni raccoglie accesso catalogo/avvio, SEO, associazioni e permessi. Tutti i campi precedenti rimangono renderizzati una volta e conservano i controlli di salvataggio.

Salva e apri programma salva/valida prima i dati mediante il controller Joomla e poi apre moduli, lezioni e materiali. Funziona anche dopo la prima creazione; un errore lascia il form da correggere. L’apertura diretta del programma già salvato resta disponibile per compatibilità. I vecchi ID di regione restano nel layout; alcuni identificano ora sezioni della scheda Corso/Opzioni anziché schede separate. Gli override che dipendono dalla vecchia gerarchia vanno confrontati con il nuovo template.
