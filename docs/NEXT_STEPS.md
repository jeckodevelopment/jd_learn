# Piano esecutivo residuo — stabilizzazione prima delle nuove funzioni

Priorità attuale: semplificazione M01 richiesta il4novembre, con storico Joomla e aggiornamento dei corsi, lezioni/materiali e docenti. Le dipendenze M02/AUT-04 e M10/CORE-10/13/16 sono anticipate per rendere coerente il flusso richiesto. Il collaudo utente ha confermato prima pubblicazione e YouTube. La 0.29 completa il vincolo della descrizione estesa e la 0.30 integra le ACL per corso. La0.31 integra salvataggio pubblicato/storico, copia completa per aggiornamenti e profili docente. La specifica1.3 e AC-119–125 recepiscono esplicitamente la richiesta. I residui M01 sono puntuali in [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md); anteprima completa, revisione e migrazioni restano nel piano.

Aggiornamento:4novembre2026. Questo è un backlog ordinato, non una stima di calendario né una dichiarazione di funzionalità già esistenti. La [matrice](REQUIREMENTS_STATUS.csv) e il [registro differenze](IMPLEMENTATION_GAPS.md) definiscono ciò che resta aperto; l’ordine può essere affinato per dipendenze mantenendo l’intero perimetro del rilascio1.

## Piano per incrementi fino alla versione stabile

La [roadmap](ROADMAP_TO_STABLE.md) suddivide il perimetro in12fasi di rilascio e una fase differita. [RELEASE_PLAN.json](RELEASE_PLAN.json) assegna tutti i210requisiti alla fase primaria. Gli incrementi M01 comprendono presentazione, copertina/SEO, docenti, calendario, ACL di navigazione e validazioni. La 0.28 aggiunge l’anteprima editoriale iniziale M02. La 0.29 completa il requisito di descrizione prima della pubblicazione. La 0.30 integra la gestione delle regole per corso. M01 richiede ancora implementazione dei criteri mancanti e collaudo complessivo, non soltanto una conferma utente. La copia/versione di aggiornamento0.31 è operativa; M02 richiede ancora anteprima interattiva, revisione, archivio, impatto e migrazioni. L’import CSV segue le dipendenze M06/M07 e non precede questi residui della gestione corsi.

## 0. Riscontro acquisito: prima pubblicazione e YouTube

Il collaudo del1novembre aveva rilevato il percorso di pubblicazione assente o poco riconoscibile. Gli incrementi da0.20.1 hanno aggiunto il comando nel Programma, diagnostica, controlli e prove HTTP complete. L’utente ha successivamente confermato prima pubblicazione e riproduzione YouTube sulla0.27.2. Questo riscontro chiude il blocco specifico segnalato sul percorso provato. I criteri generali e le altre funzioni AUT rimangono nella matrice.

**Accettazione del blocco:** riprodurre e registrare l'esito con versione Joomla/pacchetto, ruolo, categoria/workflow, tipo di lezione e messaggio mostrato; nessun dato personale o segreto. Provare corso minimo testuale, video e quiz supportati, errori comprensibili, permessi e token, prima pubblicazione e accesso studente con il livello richiesto. Nessuna modifica diretta dello stato o del database come workaround; nessuna perdita dello storico. La risoluzione di questo difetto non completa AUT-01/02/03/04 o SCR-13: editor completo, revisione, anteprima, archivio e versionamento restano nel piano.

Seguire la checklist M01: prima duplicazioni autonome di corso/modulo/lezione e ordinamento mouse/tastiera con ID nuovi e nessuna copia dei risultati; poi parti mancanti dell’editor e relativi collegamenti M02/M08/M10. I comandi di aggiornamento0.31 non sono duplicazioni autonome. Conservare storico, ACL, token e controlli negativi. Non anticipare il CSV prima di questo lavoro.

## 1. Iscrizioni: ammissione e import

La0.20 implementa R-01 nel flusso singolo con opt-in/avviso e prove CI. Estendere questi invarianti al CSV: distinguere assegnazione didattica e diritto corrente di fruizione, con preassegnazione esplicita/avviso se manca il livello e controlli runtime sempre obbligatori. Nessun gruppo commerciale o account creato. Conservare sospensioni/progressi e identità native; stessa email non deve trasferire storico.

Quindi implementare upload/simulazione CSV secondo DAT-06/LIFE-04/05: UTF-8, delimitatore dichiarato, intestazioni `user_id,course_id,course_version_id,edition,starts_at,ends_at`, solo account esistenti, date ISO8601 con fuso, default10MiB/10000righe entro limiti configurati. Non trattare come già disponibili versione/edizione/date solo perché sono colonne: la0.19 ammette soltanto corrente/default e richiede un’estensione verificata del contratto.

Anteprima con esito per riga, esclusioni per scope del corso e duplicati senza aggiornamenti impliciti; file/risultati riservati all’operatore. Permesso `jlms.enrollments.import` distinto dalla gestione singola, limiti di memoria e protezione dei temporanei. Per i volumi previsti servono esecuzione a batch/job con piano immutabile/hash, cursore, lease/retry/idempotenza e riepilogo recuperabile tramite strumenti amministrativi; non una lunga richiesta PHP o un cron pubblico. Un’opzione email richiede prima outbox/Mail Templates: nessuna comunicazione finché non selezionata e implementata.

**Accettazione:** nessun gruppo/password/risultato importato; confini di dimensione/righe/date/parent e scope; duplicati, file malformati, riavvio del job e concorrenza/capienza; rifiuto degli utenti non autorizzati, conservazione dello storico e cleanup secondo politica.

## 2. Stati, edizioni e disponibilità

Completare richieste/approvazioni/rifiuti, annullamento dell’iscrizione, gestione delle edizioni/finestre e proroghe formative. Chiudere R-03 con comandi separati per nuove iscrizioni ed erogazione. Ogni operazione sensibile ha scope, motivo/anteprima/conferma, audit, replay e impatto sui quiz/risorse già aperti. Non confondere un rinnovo di livello con una nuova frequenza.

**Accettazione:** transizioni valide/negate, stesse sessioni dopo revoca/ripristino, timer che non si riaprono, stato didattico separato dall’accesso e nessuna cancellazione o migrazione silenziosa.

## 3. Didattica, versioni e media

Audio/documenti come lezioni autonome; prerequisiti/sequenze/rilascio con validazione dei cicli e dei contenuti irraggiungibili; fallback R-05. Clonazione/nuove versioni, equivalenze/migrazione esplicita e rivalutazioni quiz senza alterare evidenze congelate. Percorsi aggregati del primo rilascio, senza anticipare attestati di percorso/sequenze fra corsi differiti.

Chiudere R-02 con un quinto provider di prova installabile senza patch; completare capacità/asset, sottotitoli/trascrizioni/preferenze dove supportati, limiti e diagnostica. I quattro provider base restano obbligatori nella distribuzione. Collaudare SDK e contenuti reali autorizzati su desktop/mobile, non soltanto mock.

**Accettazione:** storico sulla versione assegnata, pubblicazione impossibile per regole non supportate/cicliche, progresso coerente dopo cambiamenti espliciti e nessuna apertura di media o risposte riservate.

## 4. Attestati e notifiche

Prerequisito condiviso: job/outbox affidabili e Mail Templates nativi, con eventi/lingua/destinatari documentati e gestione retry/esito incerto. Implementare idoneità, modelli/PDF e snapshot, download, verifica opzionale non enumerabile, revoca/sostituzione e anomalie dopo rivalutazione. Non inviare posta dentro le transazioni didattiche.

**Accettazione:** un documento per emissione, retry senza duplicati, layout con nomi lunghi, nessuna revoca automatica impropria e disponibilità del documento anche se la mail fallisce.

## 5. Report e integrazioni core

Completare report/KPI, dettagli studente e CSV export con scope/filtri, fusi orari e neutralizzazione formule. Finder soltanto sui contenuti indicizzabili; Tags, Custom Fields/snapshot, Content History, Media Manager, lifecycle categorie/dipendenze e audit generale. Ampliare copertura lingua, menu, override e documentazione per ogni nuova vista.

**Privacy è trasversale, non un’attività da attendere fino a questo punto:** iniziare presto il plugin `com_privacy` con capabilities/export/removal, inventario di dati e politiche configurabili; ogni nuovo job/import/export deve integrare temporanei, accessi, cancellazione/impedimenti e conservazione. Lo stesso vale per minimizzazione e audit di sicurezza.

**Accettazione:** prove native Joomla per ogni integrazione, export/removal coerenti con dati/storici reali, nessuna indicizzazione o esportazione di contenuti privati fuori dallo scope.

## 6. Accettazione di rilascio

Eseguire tutti gli scenari obbligatori della matrice funzionale e dei provider con riferimento al build. Verificare patch corrente Joomla, install/upgrade/backup/ripristino, integrazione reale con estensione abbonamenti, test browser/accessibilità e due personalizzazioni indipendenti. Chiudere R-04 sul documento completo nel contesto target, senza patch al core.

Audit sicurezza con analisi statica/stile/scansioni e risultati motivati. Carico misto dichiarato:300studenti attivi per60minuti e500per10minuti, dataset/infrastruttura misurati, p95/errori/integrità/lock/code e prove oltre soglia. I test concorrenti della CI non sostituiscono questo benchmark.

**Accettazione:** nessun obbligo di rilascio senza esito, anomalie con decisione esplicita, report reale delle prestazioni e guide/ZIP/checksum/aggiornamenti completi. Vendita e abbonamenti restano esterni; compiti/live/SCORM/xAPI/LTI mantengono le rispettive fasi differite.
