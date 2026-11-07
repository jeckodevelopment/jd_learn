# Prossime attività — ordine e condizioni di chiusura

Aggiornato al **7 novembre 2026**. Riferimento: **0.41.3-dev**, resa pubblica dei campi editoriali corso verificata nella CI standard; menu amministrativo e durata automatica già implementati. Qui si pianifica soltanto il lavoro residuo; funzioni presenti e prove sono nello [stato attuale](IMPLEMENTATION_STATUS.md).

## Priorità

| Ordine | Attività | Risultato richiesto / dipendenze |
|---|---|---|
| 1 | Completare gestione corsi, programma e provider essenziali (M01/M02/M04) | Eseguire e registrare i collaudi reali della checklist; completare le parti mancanti dell'editor, con i collegamenti M02/M03/M08/M10. Prima pubblicazione, duplicazioni, riordino, riepilogo impatto e recupero storico sono disponibili. Menu nativo e ingresso diretto ai corsi implementati; prosegue il restyling residuo, secondo ADMIN_UX_PLAN.md. Correggere subito ogni blocco di pubblicazione/fruizione YouTube, Vimeo e Cloudflare Stream; collaudare configurazioni e SDK reali su desktop/mobile. |
| 2 | Ciclo editoriale (M02) | Anteprima interattiva e dei livelli senza effetti, revisione/archivio, errori e avvisi distinti; migrazioni esplicite. |
| 3 | Didattica (M03) | Audio/documento autonomi, prerequisiti/cicli, sequenze/rilascio progressivo e fallback Riprendi R-05. |
| 4 | Infrastruttura job (M07) | Batch recuperabili, cursore/lease/retry/idempotenza e gestione degli esiti; prerequisito di import, notifiche e attestati. |
| 5 | Iscrizioni complete (M06) | CSV simulato/confermato, approvazioni/rifiuti, annullamenti, edizioni/finestre e separazione nuove iscrizioni/erogazione R-03. |
| 6 | Estensioni media (M04) | Estensibilità senza patch R-02, accessibilità video e lifecycle materiali. I tre provider esterni essenziali e il loro collaudo operativo sono già priorità1. |
| 7 | Valutazione (M05) | Rivalutazioni, disponibilità e anomalie complete senza alterare le evidenze storiche. |
| 8 | Attestati e notifiche (M08/M07) | PDF/idoneità/emissione/download/verifica/revoca; outbox e Mail Templates affidabili. |
| 9 | Esperienza e report (M09) | Catalogo/percorsi/area personale completi, KPI/dettagli e CSV protetti. |
| 10 | Integrazioni residue e rilascio (M10–M12) | Completare core/lifecycle, matrice di accettazione, patch corrente, recovery e carico. |

**Privacy, ACL, sicurezza, lingue, accessibilità e conservazione dati sono trasversali.** Il plugin Privacy e le politiche sui dati vanno avviati presto, senza aspettare il punto 10. Il collaudo accompagna ogni attività, non soltanto il rilascio finale. Le dipendenze possono anticipare parti di una fase senza ridurre il perimetro.

## Primo lavoro da svolgere

**Durata automatica e scarti (R-06):** la0.39 implementa il flusso nell’editor per YouTube/Vimeo/Stream e conserva la misura da file MP4, con ricevute di sorgente e tolleranza coerente con il completamento. CI standard superata; prima di accettarlo completare il collaudo SDK/browser/account reali secondo [VIDEO_DURATION_PLAN.md](VIDEO_DURATION_PLAN.md), correggendo subito eventuali blocchi. La resa pubblica dei campi editoriali corso è implementata e verificata nella CI standard0.41; il seguito riprende snapshot didattici, campi lezione e residui dell’editor; menu nativo e ingresso diretto implementati; restyling residuo pianificato M01/M10. Non ripianificare l’acquisizione ordinaria come assente, né dichiarare concluso il collaudo reale.

Usare [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md) come elenco operativo: collaudare l’amministrazione e il programma su Joomla reale e registrare gli esiti per build/ruolo/browser, affrontando subito eventuali blocchi dei provider essenziali. Campi corso, riepilogo/conferma degli aggiornamenti e recupero storico sono implementati e coperti dalle rispettive regressioni. L’editor delle regole supportate e la soglia video della versione sono implementati e coperti dalla CI standard della0.38. Seguono campi/snapshot pertinenti e regole avanzate, rispettando le dipendenze M03/M08/M10; non mostrare controlli per attestati o regole ancora non operative. L’anteprima interattiva senza effetti e il ciclo revisione/archivio restano il seguito M02. Non considerare M01 chiuso per effetto della sola CI o di una conferma generica.

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
