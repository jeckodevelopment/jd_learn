# Prossime attività — ordine e condizioni di chiusura

Aggiornato al **7 novembre 2026**. Riferimento: **0.41.4-dev candidato, PR #60 in bozza**, resa pubblica dei campi editoriali corso verificata nella CI standard; menu amministrativo e durata automatica già implementati. Qui si pianifica soltanto il lavoro residuo; funzioni presenti e prove sono nello [stato attuale](IMPLEMENTATION_STATUS.md).

## Priorità per la prima versione stabile

Il perimetro approvato e la suddivisione dei requisiti sono in [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md). Le fasi originarie restano utili come mappa tecnica, ma le attività elencate come fase 2 non sono criteri di blocco per la prima stabile.

| Ordine | Attività | Risultato richiesto |
|---|---|---|
| 1 | Gestione corsi e programma (M01/M02) | Completare il percorso intuitivo di creazione, modifica dei corsi pubblicati, aggiornamento di lezioni/materiali e pubblicazione della nuova versione, preservando corsi in uso, versioni assegnate e progressi. |
| 2 | Provider video e materiali essenziali (M04) | YouTube, Vimeo, Cloudflare Stream e media locali previsti funzionano nella lezione con consenso, accesso e progressi corretti; collaudo su browser e account reali. |
| 3 | Fruizione e progressi core (M03/M09) | Lezioni testo/video, regole supportate, aula, catalogo, ripresa e progressi persistenti; non rendere i quiz prerequisito di pubblicazione o completamento quando il corso non li usa. |
| 4 | Iscrizione singola e roster essenziale (M06/M09, sottoinsieme) | Accesso e iscrizione singola, sospensione/riattivazione e roster per corso con stato, avanzamento e date; ACL e storico conservati. Nessun KPI/export necessario in questa fase. |
| 5 | Qualità amministrativa e integrazioni Joomla | Completare usabilità del backend, voci di menu, docente/corsi, privacy e integrazioni core applicabili, senza introdurre flussi incompleti per quiz o attestati. |
| 6 | Accettazione della release (M10–M12) | Installazione/upgrade, sicurezza, accessibilità, browser/provider, conservazione dati e guide per il perimetro core. Eseguire la matrice estesa al candidato stabile, non a ogni commit preparatorio. |

### Funzionalità congelate per la fase 2

Quiz e banca domande, attestati, KPI/reportistica avanzata, export CSV e importazioni/operazioni massive, edizioni e finestre complesse, notifiche e job non indispensabili al flusso core. Sono ammesse correzioni mirate di blocchi, sicurezza e integrità. La gestione essenziale degli iscritti resta in prima fase.

Privacy, ACL, sicurezza, accessibilità, lingue e conservazione dati si applicano da subito a ogni funzione inclusa.

## Primo lavoro da svolgere

La PR #60 riorganizza le schede del corso senza modificare dati o salvataggi. Completare la revisione leggera e il candidato; dopo il collaudo d’integrazione affrontare i residui M01 indicati in [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md), mantenendo in primo piano aggiornamento di corsi pubblicati, programma e materiali. Verificare che una nuova versione non sposti iscritti o alteri progressi/storico esistenti.

In parallelo, mantenere i tre provider esterni e i media locali come requisiti core: la [durata automatica e gli scarti video](VIDEO_DURATION_PLAN.md) sono implementati nella linea0.39 ma il collaudo completo con provider/browser/account reali resta necessario. Aggiornare lo stato per ciascun build e distinguere ciò che è implementato da ciò che è accettato.

Dopo il consolidamento di corsi, programma e media, completare l’iscrizione singola e il roster core secondo AC-128. Quiz, attestati e reportistica avanzata restano congelati: non riprendere quei lavori per ampliare funzionalità mentre il core è aperto.

## Modalità di sviluppo e verifica

PR in bozza durante i commit preparatori; controlli leggeri a ogni aggiornamento, integrazioni sul candidato pronto per il merge. Due ambienti PHP/database più browser ordinariamente; matrice completa su avvio manuale prima di milestone/rilascio o modifiche di compatibilità. Nessuna duplicazione al merge. Procedura e invalidazione degli esiti in [CI_POLICY.md](CI_POLICY.md).

## Condizioni tecniche delle attività successive

I paragrafi seguenti dettagliano i contratti da rispettare; l’ordine di priorità è definito dalla tabella sopra.

### Iscrizioni: ammissione e import

La preassegnazione singola è disponibile con opt-in/avviso e prove CI (R-01). Estendere questi invarianti al CSV: distinguere assegnazione didattica e diritto corrente di fruizione, con preassegnazione esplicita/avviso se manca il livello e controlli runtime sempre obbligatori. Nessun gruppo commerciale o account creato. Conservare sospensioni/progressi e identità native; stessa email non deve trasferire storico.

Quindi implementare upload/simulazione CSV secondo DAT-06/LIFE-04/05: UTF-8, delimitatore dichiarato, intestazioni `user_id,course_id,course_version_id,edition,starts_at,ends_at`, solo account esistenti, date ISO8601 con fuso, default10MiB/10000righe entro limiti configurati. Non trattare come già disponibili versione/edizione/date solo perché sono colonne: il percorso singolo implementato ammette soltanto corrente/default e richiede un’estensione verificata del contratto.

Anteprima con esito per riga, esclusioni per scope del corso e duplicati senza aggiornamenti impliciti; file/risultati riservati all’operatore. Permesso `jlms.enrollments.import` distinto dalla gestione singola, limiti di memoria e protezione dei temporanei. Per i volumi previsti servono esecuzione a batch/job con piano immutabile/hash, cursore, lease/retry/idempotenza e riepilogo recuperabile tramite strumenti amministrativi; non una lunga richiesta PHP o un cron pubblico. Un’opzione email richiede prima outbox/Mail Templates: nessuna comunicazione finché non selezionata e implementata.

**Accettazione:** nessun gruppo/password/risultato importato; confini di dimensione/righe/date/parent e scope; duplicati, file malformati, riavvio del job e concorrenza/capienza; rifiuto degli utenti non autorizzati, conservazione dello storico e cleanup secondo politica.

### Stati, edizioni e disponibilità

Completare richieste/approvazioni/rifiuti, annullamento dell’iscrizione, gestione delle edizioni/finestre e proroghe formative. Chiudere R-03 con comandi separati per nuove iscrizioni ed erogazione. Ogni operazione sensibile ha scope, motivo/anteprima/conferma, audit, replay e impatto sui quiz/risorse già aperti. Non confondere un rinnovo di livello con una nuova frequenza.

**Accettazione:** transizioni valide/negate, stesse sessioni dopo revoca/ripristino, timer che non si riaprono, stato didattico separato dall’accesso e nessuna cancellazione o migrazione silenziosa.

### Didattica, versioni e media

Audio/documenti come lezioni autonome; prerequisiti/sequenze/rilascio con validazione dei cicli e dei contenuti irraggiungibili; fallback R-05. Completamento del versionamento: equivalenze/migrazione esplicita con la propria anteprima di impatto e rivalutazioni quiz senza alterare evidenze congelate. Percorsi aggregati del primo rilascio, senza anticipare attestati di percorso/sequenze fra corsi differiti.

Chiudere R-02 con un quinto provider di prova installabile senza patch; completare capacità/asset, sottotitoli/trascrizioni/preferenze dove supportati, limiti e diagnostica. I quattro provider base restano obbligatori nella distribuzione. Collaudare SDK e contenuti reali autorizzati su desktop/mobile, non soltanto mock.

**Accettazione:** storico sulla versione assegnata, pubblicazione impossibile per regole non supportate/cicliche, progresso coerente dopo cambiamenti espliciti e nessuna apertura di media o risposte riservate.

### Attestati e notifiche

Prerequisito condiviso: job/outbox affidabili e Mail Templates nativi, con eventi/lingua/destinatari documentati e gestione retry/esito incerto. Implementare idoneità, modelli/PDF e snapshot, download, verifica opzionale non enumerabile, revoca/sostituzione e anomalie dopo rivalutazione. Non inviare posta dentro le transazioni didattiche.

**Accettazione:** un documento per emissione, retry senza duplicati, layout con nomi lunghi, nessuna revoca automatica impropria e disponibilità del documento anche se la mail fallisce.

### Report e integrazioni core

Completare report/KPI, dettagli studente e CSV export con scope/filtri, fusi orari e neutralizzazione formule. Finder soltanto sui contenuti indicizzabili; Tags, Custom Fields/snapshot, Content History, Media Manager, lifecycle categorie/dipendenze e audit generale. Ampliare copertura lingua, menu, override e documentazione per ogni nuova vista.

**Privacy è trasversale, non un’attività da attendere fino a questo punto:** iniziare presto il plugin `com_privacy` con capabilities/export/removal, inventario di dati e politiche configurabili; ogni nuovo job/import/export deve integrare temporanei, accessi, cancellazione/impedimenti e conservazione. Lo stesso vale per minimizzazione e audit di sicurezza.

**Accettazione:** prove native Joomla per ogni integrazione, export/removal coerenti con dati/storici reali, nessuna indicizzazione o esportazione di contenuti privati fuori dallo scope.

### Accettazione di rilascio

Eseguire tutti gli scenari obbligatori della matrice funzionale e dei provider con riferimento al build. Verificare patch corrente Joomla, install/upgrade/backup/ripristino, integrazione reale con estensione abbonamenti, test browser/accessibilità e due personalizzazioni indipendenti. Chiudere R-04 sul documento completo nel contesto target, senza patch al core.

Audit sicurezza con analisi statica/stile/scansioni e risultati motivati. Carico misto dichiarato:300studenti attivi per60minuti e500per10minuti, dataset/infrastruttura misurati, p95/errori/integrità/lock/code e prove oltre soglia. I test concorrenti della CI non sostituiscono questo benchmark.

**Accettazione:** nessun obbligo di rilascio senza esito, anomalie con decisione esplicita, report reale delle prestazioni e guide/ZIP/checksum/aggiornamenti completi. Vendita e abbonamenti restano esterni; compiti/live/SCORM/xAPI/LTI mantengono le rispettive fasi differite.

Il seguito dei campi riguarda snapshot didattici e campi lezione; il loro contratto richiede definizioni e valori pertinenti congelati alla pubblicazione, accesso corrente e conservazione delle evidenze assegnate. La sola resa editoriale pubblica non li completa.
