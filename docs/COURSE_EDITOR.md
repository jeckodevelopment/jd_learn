# Gestione dei corsi

## Editor corso essenziale (0.41.4-dev)

L’editor raggruppa le attività in cinque schede: **Corso** (testi, immagine, docenti e metadati essenziali), **Programma** (struttura e regole didattiche supportate), **Accesso e pubblicazione** (categoria, lingua, livelli, iscrizione e calendario), **Campi personalizzati** quando disponibili, e **Opzioni** (SEO, associazioni e permessi). Le regole si aprono dal contesto del programma. La riorganizzazione riguarda la navigazione del form; non cambia i dati salvati, le versioni didattiche o i permessi.


## Modificare i dati del corso

Aprire Corsi → Modifica, cambiare titolo, descrizioni, immagine, docenti, accessi o calendario e scegliere Salva oppure Salva e chiudi. Il corso pubblicato resta pubblicato. Le correzioni editoriali aggiornano la scheda pubblica e vengono registrate nello storico Joomla.

Il pulsante Versioni apre Content History di Joomla per confrontare le revisioni editoriali e ripristinarle. Il ripristino passa dagli stessi controlli del salvataggio, con permessi, blocco modifica e revisione corrente. La nota di versione può descrivere l’intervento. La configurazione del componente permette di abilitare lo storico e definirne il limite. Il ripristino editoriale conserva la versione didattica attiva e le assegnazioni degli iscritti.

## Campi personalizzati del corso

Definire campi e gruppi con il gestore Campi di Joomla, contesto Corsi (`com_jlms.course`), e abilitare il plugin di sistema Fields. Quando il core aggiunge campi al form, l’editor mostra la scheda Campi personalizzati: tipi, gruppi, lingua, categoria, validazione e permessi dei valori restano quelli nativi. La scheda non compare se non ci sono campi applicabili. Sul corso bloccato da un altro operatore i campi sono disabilitati come gli altri dati.

Salva/Salva e chiudi inoltrano `com_fields` agli eventi Joomla. Gli errori di validazione della form conservano l’input; Annulla lascia i valori salvati. Client precedenti che omettono il gruppo non cancellano i valori. Le revisioni obsolete e i permessi del corso vengono verificati prima del salvataggio. Il modello non scrive una tabella parallela dei campi.

Il collegamento amministrativo e la resa editoriale pubblica dei campi corso sono implementati. La 0.41.7 aggiunge campi nativi per le lezioni, salvataggio autorizzato e snapshot dei valori alla pubblicazione, con resa dei valori assegnati nell’aula; CI standard superata. CORE-10 resta in corso per collaudo manuale, copertura completa dei tipi/plugin e ripristino tramite Content History. La modifica dei campi editoriali non migra gli iscritti o riscrive lo storico didattico.

## Campi personalizzati delle lezioni (0.41.7)

In Joomla → Campi, scegliere il contesto Lezioni (`com_jlms.lesson`), creare/pubblicare i campi e assegnarli ai livelli e gruppi desiderati. Si può raggiungere questa vista anche dal pulsante **Gestisci campi aggiuntivi delle lezioni** in Programma, visibile solo a chi può gestire `com_fields`. Abilitare il plugin di sistema Fields. Nell’editor Programma aprire o creare una lezione: il fieldset Campi personalizzati mostra i campi applicabili. Il salvataggio usa il modello Fields nativo; l’operatore deve avere `core.edit.value` sul campo e il suo livello di accesso. Quando si duplica una lezione o si prepara una nuova versione, i valori consentiti vengono copiati nella nuova bozza.

Alla pubblicazione i valori e le etichette visibili all’autore vengono congelati nella lezione della versione didattica. L’aula mostra i valori della versione assegnata e ricontrolla il livello di accesso corrente; l’output è escapato come testo. Cambiare il valore corrente o la definizione non riscrive versioni già pubblicate.

**Collaudo dopo l’installazione:** creare un campo testo nel contesto Lezioni; assegnare un valore, salvare e riaprire la lezione; pubblicare la bozza e verificare il valore nell’aula; cambiare il valore nella nuova bozza e verificare che la versione già assegnata resti invariata; provare con un operatore senza `core.edit.value` e con uno studente privo del livello del campo.

## Aggiornare lezioni e materiali

Nel Programma di un corso pubblicato, scegliere Modifica programma. Il sistema prepara o riapre la versione di lavoro con moduli, lezioni, quiz, domande e collegamenti ai materiali. Da qui si usano i normali comandi di modifica, aggiunta e caricamento. I file immutabili esistenti vengono riutilizzati, con associazioni indipendenti per la nuova versione.

Riepiloga aggiornamento apre il confronto con la versione attiva: moduli, lezioni, quiz e materiali aggiunti, modificati/riordinati o rimossi; regole e iscrizioni per versione. Il riepilogo non modifica i dati. Verificare le differenze e selezionare la conferma esplicita; Pubblica aggiornamento esegue quindi la transizione del workflow e rende disponibile il programma aggiornato per le nuove iscrizioni. Il ticket è vincolato a sessione, operatore, corso, bozza e transizione, scade dopo15minuti ed è consumato al tentativo di conferma. Modifiche intervenute dopo il riepilogo richiedono un nuovo confronto. I conteggi degli iscritti sono informativi al momento della lettura e non espongono nominativi. Gli iscritti esistenti conservano versione, progressi, tentativi, risultati e materiali assegnati. Le modifiche al programma possono essere preparate durante l’erogazione. La migrazione degli iscritti rimane un’operazione esplicita da sviluppare secondo AUT-04.

## Docenti

Nella scheda Docenti del corso si associano i profili desiderati. Un profilo può essere creato senza scegliere una categoria. I campi aggiuntivi si definiscono con il gestore Campi di Joomla nel contesto Docenti. Visibilità, lingua, gruppi di campi e permessi di modifica dei valori seguono il core.

Un profilo può essere collegato a un account Joomla. Con il permesso Modifica propri, il docente può aprire la propria pagina e scegliere Modifica il profilo. Può cambiare presentazione e campi autorizzati. Pubblicazione, visibilità e collegamento dell’account sono gestiti dalla segreteria autorizzata. L’associazione a un corso non assegna automaticamente permessi o gruppi.

I tipi di voce di menu Pagina docente e Corsi di un docente consentono rispettivamente la pagina completa o il solo elenco dei corsi. Le viste rispettano pubblicazione, lingua e livelli di accesso correnti del profilo e dei corsi. I campi personalizzati pubblicati seguono le proprie ACL. L’email dell’account non viene aggiunta automaticamente al profilo.

## Compatibilità e limiti

Le categorie dei profili preesistenti restano conservate per mantenere ACL e disponibilità già configurate. La scheda docente presenta un flusso senza scelta di categoria. I nuovi profili ricevono un contenitore tecnico predisposto all’installazione.

Le versioni didattiche già assegnate rimangono consultabili dall’amministrazione. Il corso mantiene il proprio storico editoriale Joomla separato dalle assegnazioni didattiche. Revisione editoriale, versione del programma e tentativo di quiz hanno funzioni distinte. Workflow avanzato, migrazioni, lezioni audio/documento e completamento di tutte le integrazioni M10 seguono la roadmap.

## Duplicare corso, modulo e lezione

Nel Programma, Duplica corso apre titolo e riepilogo: viene copiata la versione selezionata in una nuova identità in bozza, nella stessa categoria e lingua. Il titolo è modificabile prima di confermare, l’alias è nuovo e i permessi si ereditano dalla categoria. Non si copiano ACL specifiche del record, associazioni di traduzione, workflow storico, iscrizioni, progressi, tentativi o attestati. La copia include impostazioni/promozione, docenti, valori dei campi Joomla autorizzati e un programma completo con moduli, lezioni, quiz e materiali. Gli asset immutabili e le revisioni domanda restano riutilizzati tramite nuove associazioni; nessuna pubblicazione automatica.

Nel programma di lavoro, ogni modulo e lezione espone Duplica modulo/Duplica lezione. La copia del modulo contiene tutte le lezioni e i relativi quiz/materiali. La copia di una lezione può essere collocata in un modulo scelto della stessa versione di lavoro. Le copie si aggiungono in fondo, hanno nuovi ID e chiavi logiche; quelle del modulo mantengono l’ordine relativo delle lezioni. I prerequisiti interni al modulo si rimappano alle copie; quelli richiesti dalle lezioni copiate verso attività esterne restano riferiti alle attività originali della stessa versione. Le attività originali non vengono reindirizzate alle copie.

Serve poter modificare la sorgente e creare nella categoria del nuovo corso. Si applicano anche i permessi sui quiz, sui materiali e sui valori dei campi coinvolti; un diniego impedisce la copia completa, senza omettere silenziosamente quelle parti. Le copie di moduli/lezioni richiedono un programma in bozza; checkout altrui e revisioni obsolete impediscono la scrittura. La destinazione di una lezione deve appartenere alla stessa versione. Per copiare un elemento pubblicato in un corso attivo, aprire prima Modifica programma e usare i comandi sulla copia di lavoro.

Questi comandi sono distinti dalla nuova versione dello stesso corso. Duplicazioni, rollback e riordino sono coperti dalle regressioni della CI corrente. Per usare la scaletta e il riordino vedere [programma](AUTHORING.md); per implementazioni e collaudi residui vedere [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md).

## Elenco ed editor — convenzioni articoli Joomla

Corsi usa la toolbar nativa Nuovo/Modifica/Programma/Opzioni, checkbox di selezione, titolo cliccabile, Search Tools e paginazione. Ricerca per titolo/alias o id:123; filtri stato, categoria con discendenti, livello di accesso ai contenuti, autore Joomla, lingua e blocco modifica. Sono ordinabili titolo, stato, categoria, accesso, autore, lingua, ultima modifica e ID. I filtri persistono tornando dall’editor; un cambio filtro torna alla prima pagina, con massimo100righe. L’autore è il creatore del record Joomla, distinto dai profili docente associati.

La riga mostra metadati e collegamenti Programma/Iscrizioni se autorizzati; lo stato apre la gestione della pubblicazione nel Programma. Non esegue un toggle che aggiri workflow e validazioni. Con selezione, Programma richiede un solo corso e ricontrolla i permessi. Nessuna azione massiva di stato viene simulata.

L’editor mostra titolo, alias, sintesi e descrizione nella prima scheda, con categoria, lingua, stato e accesso essenziale nella sidebar. Programma, Docenti, Immagine e Pubblicazione sono schede dirette; Opzioni raccoglie accesso catalogo/avvio, SEO, associazioni e permessi. Tutti i campi precedenti rimangono renderizzati una volta e conservano i controlli di salvataggio.

Salva e apri programma salva/valida prima i dati mediante il controller Joomla e poi apre moduli, lezioni e materiali. Prepara o riapre automaticamente il programma di lavoro, anche dopo la prima creazione o per aggiornare un corso pubblicato. Un errore di validazione lascia il form da correggere; se la preparazione fallisce dopo il salvataggio, il corso salvato resta conservato e compare un avviso. La versione didattica attiva e gli iscritti non vengono cambiati. L’apertura diretta del programma già salvato resta disponibile per compatibilità. I vecchi ID di regione restano nel layout; alcuni identificano ora sezioni della scheda Corso/Opzioni anziché schede separate. Gli override che dipendono dalla vecchia gerarchia vanno confrontati con il nuovo template.

## Recuperare un programma storico

Nel Programma aprire Versioni, selezionare una versione storica già pubblicata e scegliere **Recupera come nuova bozza**, confermando il comando. Si crea una bozza distinta con nuovi ID e le stesse chiavi logiche: moduli, lezioni, riferimenti/opzioni video YouTube/Vimeo/Cloudflare Stream, quiz e riferimenti immutabili alle domande, materiali e regole. Le altre bozze rimangono disponibili; Modifica programma riapre la più recente.

Il recupero conserva scheda promozionale, docenti, accessi e versione attiva correnti. Non assegna gruppi e non modifica iscrizioni, progressi o tentativi. Per rendere attivo il programma recuperato occorre modificarlo se necessario e pubblicarlo tramite il normale riepilogo/conferma dell’impatto; si applicano di nuovo i controlli su regole, risorse, privacy e configurazione dei provider. Gli iscritti esistenti conservano la versione assegnata.

Il comando richiede POST/token Joomla, conferma, identità e permessi correnti, checkout compatibile, revisioni del corso e della fonte; la fonte deve appartenere al corso ed essere storica congelata. Rifiuta fonti attive o bozze e richieste obsolete. Copia e Action Logs nativo sono atomici. Nessun ripristino o migrazione automatica degli studenti.

## Regole didattiche e soglia video

Nell’editor del corso la scheda **Regole didattiche** offre **Salva e modifica le regole didattiche**: valida/salva prima la scheda corrente e prepara o riapre la bozza del programma. Dal Programma il collegamento Regole didattiche apre lo stesso editor contestuale, senza un secondo form di lezione o modulo. Sono disponibili le modalità Libera e Sequenziale e il completamento di tutte le attività obbligatorie. In modalità sequenziale la pubblicazione collega ogni lezione alla precedente obbligatoria nell’ordine del programma.

La soglia video della versione è modificabile da **0,01% a 100%**, massimo due decimali, con predefinita90%. Si applica agli intervalli unici riprodotti dei video tracciati, indipendentemente dal provider supportato. Non misura l’apprendimento e saltare alla fine non completa la lezione. Il salvataggio usa un form Joomla POST/token con permesso corrente, checkout e revisione della bozza; gli errori conservano il valore per correggerlo e le modifiche non salvate usano l’avviso già previsto nell’editor contestuale.

Le versioni pubblicate/storiche sono in sola lettura. Una modifica in bozza non cambia corso attivo, dati promozionali, iscritti o progressi. La pubblicazione aggiornata richiede il normale riepilogo/conferma, che mostra anche soglia precedente e nuova; i nuovi iscritti seguono la versione aggiornata e quelli già assegnati conservano la propria soglia. Un cambiamento successivo al riepilogo lo invalida.

Questo è il sottoinsieme operativo dell’editor regole: il rilascio programmato per data, la modalità consultabile, gli attestati e le soglie specifiche per singola lezione restano residui delle rispettive fasi. Non si mostrano controlli che attivino funzioni ancora non supportate.

## Durata automatica nell’editor delle lezioni

Il form video usa Rileva automaticamente: SDK/anteprima per YouTube e Vimeo, API server per Stream, misura dal file per MP4. Per YouTube può essere necessario avviare l’anteprima prima che i metadati siano disponibili. Il campo manuale è un ripiego esplicito; la vecchia conferma della durata non è un passaggio ordinario. Le ricevute impediscono il riuso di una misura su sorgenti o revisioni diverse. Una bozza incompleta può essere salvata; una durata acquisita per la stessa sorgente viene conservata durante modifiche al testo. Politica privacy e consenso restano applicati anche nel backend. Procedura, tolleranza e limiti in [VIDEO.md](VIDEO.md).

## Accesso amministrativo (0.40)

Aprire JD Learn porta direttamente all’elenco corsi. Nella sidebar nativa Joomla sono disponibili Corsi, Categorie corsi, Banca domande, Docenti, Campi aggiuntivi e Plugin video. Programma, materiali e iscritti si aprono dal corso selezionato. Le categorie sono dei corsi; non occorre una categoria per il docente. La sidebar e i percorsi mantengono i controlli dei permessi Joomla.

La voce Plugin video apre la gestione nativa Joomla filtrata sul gruppo `jlmsvideo`, con ricerca, stato, accesso e singolo plugin azzerati per vedere tutti i provider installati, anche disattivati. Da qui si abilitano/disabilitano e configurano i plugin secondo i permessi di `com_plugins`. Non è una verifica della riproduzione; nessun accesso ai plugin è concesso dal solo permesso di gestire corsi.

## Campi nella scheda pubblica del corso (0.41)

La vista dei campi corso (`com_jlms.course`) è raggiungibile da JD Learn → **Campi aggiuntivi** nella sidebar e dal filtro contesto nativo di Joomla.

I campi correnti del contesto `com_jlms.course` sono preparati dai plugin di tipo Joomla e resi con i layout nativi. La posizione automatica si configura nel campo: dopo il titolo, prima della presentazione o dopo la presentazione. Con visualizzazione automatica disattivata il campo non compare; valori vuoti non generano etichette o contenitori vuoti. Sono applicati pubblicazione, categoria, lingua e accesso di campo e gruppo, oltre all’accesso corrente al corso. La sola visibilità del corso non rende pubblici campi riservati.

Una modifica editoriale aggiorna la scheda senza dover ripubblicare il programma. Questi valori correnti non definiscono regole, soglie, risultati o diritto di accesso e non vengono inseriti retroattivamente nelle versioni degli iscritti. I campi pertinenti alla fruizione richiedono ancora il proprio snapshot: non usare campi editoriali live come regole didattiche.

Sono usati `fields.render` e `field.render` di Joomla. Gli override del template possono essere definiti sotto `html/layouts/com_jlms/fields/render.php` e `html/layouts/com_jlms/field/render.php`, con fallback ai layout com_fields. Plugin di tipo e override installati sono codice del sito: devono mantenere escaping, ACL e accessibilità. Le prove campionano testo, area di testo e lista; non certificano ogni plugin di terze parti o tutte le combinazioni multilingua.
