# LMS Joomla — Provider video, materiali, servizi ed endpoint

Versione 1.1 · 30 ottobre 2026 · Contratti applicativi del rilascio 1

Questo allegato definisce i plugin video dedicati al componente, i materiali extra di corso/lezione e la superficie applicativa necessaria. I nomi `jlms`, `com_jlms` e `jlmsvideo` sono prefissi di lavoro neutrali: saranno congelati con il nome del prodotto. I servizi sono classi applicative nello stesso componente Joomla, non microservizi o server separati. Le rotte descrivono il contratto da implementare; non sono endpoint già disponibili.

## 1. Plugin Joomla dedicati ai provider video

### 1.1 Pacchetti e gruppo

Gruppo dedicato **jlmsvideo**, separato dai generici plugin content/system. Plugin previsti: `plg_jlmsvideo_youtube`, `plg_jlmsvideo_vimeo`, `plg_jlmsvideo_cloudflarestream`; `plg_jlmsvideo_html5` gestisce i video locali secondo le regole di storage protetto. Ogni plugin ha manifest, versione, namespace, `services/provider.php`, parametri, file lingua, asset e aggiornamento propri. Dipende dal componente e dalla versione compatibile del contratto provider.

Il pacchetto base deve installarli tutti insieme, con documentazione completa per ogni plugin; in seguito un nuovo provider si aggiunge installando un altro plugin dello stesso gruppo, senza patch del nucleo LMS. Il supporto di upload remoto o sincronizzazione della libreria non è implicito nel plugin di riproduzione e rimane fuori dal primo rilascio.

### 1.2 Registrazione e contratto PHP

Il componente importa il gruppo solo nei contesti che richiedono provider, emette l'evento proprio `onJlmsRegisterVideoProviders` tramite il dispatcher Joomla e raccoglie nel `VideoProviderRegistry` factory di adapter. I plugin usano `SubscriberInterface`. Un identificativo duplicato o una versione incompatibile sono errori espliciti; nessuna selezione basata sull'ordine casuale dei plugin. Per la riproduzione si istanzia solo l'adapter richiesto. Il salvataggio dei progressi non richiama il provider o i suoi servizi remoti.

L'evento è un contratto del nostro componente, non un evento già esistente nel core Joomla. Non bisogna usare il gruppo system per obbligare tutti i plugin video a eseguire codice a ogni pagina.

| Operazione dell'adapter | Input | Output e responsabilità |
|---|---|---|
| `getDescriptor()` | Contesto configurato | Chiave stabile, etichetta traducibile, versione contratto e stato configurazione; nessun segreto |
| `getCapabilities()` | Sorgente/configurazione | Durata, ripresa, seek, velocità, eventi, sottotitoli, transcript, token temporanei, download e formati effettivamente supportati |
| `normaliseSource()` | URL/ID inserito dall'autore | `VideoSource` normalizzato: provider key, riferimento e parametri ammessi; rifiuto di host/input arbitrari |
| `validateSource()` | VideoSource e contesto autore | Errori e avvisi; il controllo locale è separato dalla verifica remota opzionale |
| `fetchMetadata()` | VideoSource autorizzato | Titolo, durata, poster e stato disponibili, con origine e scadenza della cache; solo API/host ammessi |
| `createPlaybackDescriptor()` | VideoSource e `PlaybackContext` costruito dal server | Player, asset, opzioni consentite, eventuale token temporaneo, scadenza e capacità effettive |
| `getFrontendAdapterAsset()` | Chiave/versione plugin | Asset registrabile nel Web Asset Manager, mai JavaScript arbitrario fornito dall'utente |

I nomi sono il contratto logico; firme PHP, DTO e namespace verranno fissati nello schema tecnico. Le capacità sono verificate per sorgente, non promesse incondizionatamente per tutto un provider. Se la regola del corso richiede una capacità assente, la pubblicazione è bloccata con istruzioni risolvibili.

### 1.3 Contratto del player nel browser

Il frontend adapter deve esporre inizializzazione, distruzione, lettura posizione/durata, ripresa se supportata e una sottoscrizione di eventi normalizzati: `ready`, `play`, `pause`, `position`, `seeking`, `seeked`, `ended`, `error`. La modifica del consenso o il cambio lezione distrugge l'istanza e libera listener/timer. Il LMS gestisce aggregazione degli intervalli, persistenza e completamento; non ciascun plugin con un algoritmo diverso.

`PlaybackDescriptor` contiene soltanto dati necessari al player autorizzato: `provider`, `source_revision`, `player_asset`, `player_options`, `capabilities`, `expires_at` e, se necessario, `playback_token` oppure URL temporaneo. Credenziali API e chiavi private restano sul server. Un token di riproduzione restituito al browser non è una credenziale amministrativa e mantiene la validità residua documentata.

### 1.4 Errori, estensione e compatibilità

Contratto versione 1 con campi opzionali additivi; modifiche incompatibili richiedono nuova major e migrazione documentata. Disabilitare/rimuovere un plugin non cancella i riferimenti alle lezioni: editor e diagnostica mostrano i corsi interessati, la fruizione di quei video fallisce in modo controllato. Le altre lezioni funzionano; nessun fallback a provider pubblico o completamento manuale.

I plugin sono codice installato con privilegi applicativi e vanno amministrati come estensioni Joomla attendibili: l'interfaccia non costituisce sandbox. Le autorizzazioni sono verificate dal LMS prima di invocarli; un risultato del plugin non può concedere livelli Joomla, scrivere voti o emettere attestati.

## 2. Materiali extra di corso e lezione

### 2.1 Collegamento e presentazione

Ogni versione di corso può avere zero o più materiali generali; ogni lezione può avere zero o più materiali propri. L'aula mostra due gruppi distinti, Materiali del corso e Materiali della lezione, senza duplicare lo stesso collegamento. La scheda corso ha la regione `#jlms-course-materials`, l'aula conserva `#jlms-lesson-resources`; classi ripetute `.jlms-material`, `.jlms-material__title`, `.jlms-material__meta`, `.jlms-material__action`.

Tipi previsti: PDF, documenti di testo, fogli di calcolo, presentazioni, immagini, TXT/CSV e archivi ZIP ammessi. Allowlist iniziale: pdf, docx, xlsx, pptx, odt, ods, odp, txt, csv, jpg, jpeg, png, webp, zip. Nessuna esecuzione, estrazione automatica di archivi o pubblicazione HTML/SVG caricati. L'amministratore può restringere l'elenco; il controllo comprende formato/MIME, dimensione e autorizzazione, non la sola estensione. Default 20 MiB per file, entro i limiti reali dell'hosting.

| Campo | Regola |
|---|---|
| Identificativo materiale | Generato; distinto dal nome file e dal percorso fisico |
| Ambito | `course_version` oppure `lesson`; un collegamento ha un solo proprietario |
| Proprietario | Versione del corso o lezione appartenente alla versione; controllato dal server |
| Titolo e descrizione | Titolo obbligatorio fino a 255 caratteri; descrizione facoltativa |
| File/blob e revisione | Asset interno, hash e revisione immutabile; niente percorso fornito dal browser |
| Nome originale, MIME e dimensione | Metadati validati; nome usato in download sanificato |
| Stato e ordinamento | Bozza/pubblicato/cestino e ordine all'interno dell'ambito |
| Visibilità | Eredita dal contesto protetto; pubblicabilità in anteprima solo esplicita e compatibile col contesto |
| Disponibilità | Date facoltative entro la disponibilità del contesto |
| Azione | Download consentito; eventuale visualizzazione PDF/immagine soltanto quando ammessa |
| Audit | Creatore, date, modifiche e sostituzione tracciata |

Gli allegati sono materiali di supporto: download o apertura non sono requisiti di completamento. Se un PDF deve essere obbligatorio, va creato come lezione documento con la propria regola. I materiali aggiunti al corso non devono diminuire il progresso degli iscritti.

### 2.2 Autorizzazioni e conservazione

Default: corso/versione accessibile, account e livello Joomla validi e iscrizione attiva per i materiali generali; per quelli della lezione anche disponibilità e prerequisiti della lezione. Prima di Inizia lo studente non vede file riservati. Un'anteprima autorizza solo i materiali dichiarati pubblici in quel contesto; non l'intera libreria del corso. L'iscrizione manuale non aggira il livello Joomla.

Il servizio ricava il corso proprietario dall'ID del materiale; non si fida di `course_id`, utente o percorso inviati nel client. Ogni GET di download rivalida i diritti. Per file pubblicabili si usa una scelta esplicita, per file protetti storage fuori dalla radice pubblica o servizio equivalente controllato. Risposta con MIME e Content-Disposition corretti, `nosniff`, cache privata o disabilitata; nessun link permanente diretto al disco.

Per file grandi usare consegna delegata al web server/storage dopo autorizzazione, secondo l'hosting. La possibilità di scaricare implica che l'utente autorizzato può conservare una copia; nessuna revoca futura può rimuovere copie già ricevute.

### 2.3 Upload e sostituzione

Upload autorizzato nel backend, verifica e registrazione di un asset in staging; associazione esplicita al corso/lezione solo dopo validazione. Staging scade e viene ripulito dai task, non compare nel frontend. Un autore può associare solo asset creati/assegnati e contesti che può modificare. Se la scansione configurata è attiva, un file in attesa non è distribuibile.

Sostituire crea una nuova revisione di file e collegamento senza sovrascrivere un blob storico. La rimozione da una versione non cancella blob ancora referenziati da altre versioni. La cancellazione definitiva rispetta riferimenti e politica dati. Reset didattico e cancellazione utente non cancellano i materiali del corso.

## 3. Servizi applicativi da definire

| Servizio | Responsabilità e confine |
|---|---|
| `AccessPolicyService` | Identità corrente, livelli, categorie, stato, ambito, prerequisiti e permessi amministrativi; unica decisione autorizzativa |
| `CatalogService` | Categorie, ricerca, corsi, percorsi, docenti e schede promozionali filtrati per accesso/lingua |
| `CourseAuthoringService` | CRUD corso/modulo/lezione, ordinamento, validazioni e workflow, usando i servizi core |
| `CourseVersionService` | Snapshot didattici, duplicazione, nuove versioni e migrazioni controllate; distinto da Content History |
| `EnrollmentService` | Avvio idempotente, richiesta opzionale, assegnazione manuale, import, sospensione/reset formativo; nessuna gestione commerciale |
| `LearningService` | Aula, programma, ripresa e struttura delle attività disponibili |
| `ProgressService` | Normalizzazione e unione intervalli, conferme manuali ammesse, revisione e deduplicazione |
| `CompletionService` | Regole centralizzate per lezione/corso, valutazione dell'idoneità e data di completamento |
| `VideoProviderRegistry` e `PlaybackService` | Selezione plugin, capacità, metadati e descrittore di riproduzione dopo l'access policy |
| `AttachmentService` e `StorageService` | Associazioni materiali, upload/staging/revisioni, streaming/download e riferimenti del file |
| `QuizService` | Configurazione, banca domande, estrazione, tentativi, salvataggi e correzione server |
| `CertificateService` | Modelli, emissioni idempotenti, documenti, sostituzione/revoca e verifica pubblica opzionale |
| `ReportingService` | KPI, report circoscritti, CSV e lavori di export; nessun accesso trasversale implicito |
| `NotificationService` e `JobService` | Coda, Mail Templates, retry, task, progressi dei batch e output temporanei protetti |
| `AuditService` e `PrivacyIntegrationService` | Registro Joomla e audit didattico, export/rimozione tramite plugin privacy core |

I controller sono sottili: validano metodo/sessione/input, invocano policy e servizio e traducono il risultato. Non calcolano voti o autorizzazioni nei layout. Un servizio non espone automaticamente un endpoint: privacy e task sono invocati anche dagli strumenti nativi Joomla.

## 4. Trasporto HTTP del rilascio 1

### 4.1 Controller del componente e API esterne

Le richieste del browser del sito usano i controller MVC del componente:

```text
GET|POST /index.php?option=com_jlms&task=<controller.metodo>&format=json
GET|POST /administrator/index.php?option=com_jlms&task=<controller.metodo>&format=json
GET /index.php?option=com_jlms&task=attachment.download&attachment_id=42&format=raw
```

I percorsi sono relativi alla radice Joomla e non presuppongono installazione sul root del dominio. Le pagine HTML usano le normali view/router Joomla: non si devono duplicare tutte le viste come nuove API se non necessario all'interazione. Metodi, DTO e policy della tabella sono comunque il contratto di quelle azioni.

Il catalogo allegato elenca i task logici previsti. Tutte le scritture sono POST nel trasporto MVC della prima versione; rifiutare metodi diversi con 405. JSON per azioni/letture dinamiche, raw per documenti e output binari. Non usare `com_ajax` come scorciatoia per aggirare i controller e i controlli del componente.

La API per applicazioni esterne resta differita: eventuali rotte REST versionate saranno esposte tramite plugin **webservices Joomla**, usando gli stessi servizi e policy. Non è necessario offrire da subito un'API esterna, token API o endpoint pubblici di amministrazione. Nessun gateway abbonamenti, webhook di pagamento o API utente parallela nel LMS.

### 4.2 Regole comuni di contratto

- Autenticazione browser tramite sessione Joomla; per il backend anche autorizzazione amministrativa. Per ogni POST token CSRF Joomla verificato sul server; nessun token di autenticazione in query string.
- Ricalcolo dei diritti correnti a ogni operazione sensibile, prima di leggere o deduplicare un risultato. Il possesso di un ID o di una vecchia risposta non concede accesso.
- Risposte JSON secondo la convenzione `JsonResponse`: `success`, `message`, `messages`, `data`. Errori con codice applicativo stabile e ID di correlazione dentro `data`, mai stack trace o segreti.
- Esiti HTTP: 200 lettura/aggiornamento, 201 nuova risorsa, 202 lavoro accodato; 400 richiesta malformata, 401 sessione assente, 403 divieto su risorsa pubblicizzabile, 404 risorsa assente/non rivelabile, 409 revisione/conflitto, 413 troppo grande, 422 validazione, 429 limiti, 503 provider indisponibile. Gli errori conservano JSON e codice HTTP coerenti.
- Liste paginate con limite massimo, filtri allowlist e ordinamenti ammessi. Nessun `user_id` può ampliare il contesto personale del chiamante.
- Mutazioni con retry usano chiave idempotenza dove indicato; chiave legata a utente, azione, contesto e hash richiesta. Stessa chiave con payload diverso = 409. La deduplicazione non elude la nuova autorizzazione.
- Concorrenza tramite revisione attesa per contenuti/tentativi e nonce degli eventi per il progresso; niente sovrascrittura silenziosa.
- Risposte personali, token, quiz e documenti non entrano in cache condivise. Catalogo promozionale eventualmente in cache con lingua e autorizzazioni corrette.
- Nessuna chiamata remota per ogni salvataggio video; rate limit e payload limitati, senza penalizzare il carico legittimo di 300/500 studenti.

Esempio di risposta del playback, con valori dimostrativi:

```json
{
  "success": true,
  "message": null,
  "messages": null,
  "data": {
    "provider": "cloudflarestream",
    "source_revision": "asset-r3",
    "expires_at": "2026-10-30T22:15:00Z",
    "capabilities": {"resume": true, "progress": true},
    "playback_token": "TOKEN_TEMPORANEO_DIMOSTRATIVO"
  }
}
```

### 4.3 Endpoint principali per lo studente

Il CSV `Catalogo_endpoint_LMS_Joomla_1.0.csv` è il catalogo completo di azioni browser/backend. I gruppi principali sono:

| Area | Letture GET | Azioni POST |
|---|---|---|
| Catalogo | `catalog.courses`, `catalog.categories`, `course.get`, `pathway.get`, `teacher.get` | Nessuna assegnazione di diritti |
| Area personale | `learning.dashboard`, `learning.course` | `enrollment.start`, `enrollment.request` quando configurato |
| Aula/video | `lesson.get`, `video.stream` per i soli video locali | `video.playback`, `progress.save`, `lesson.complete` solo con regola manuale |
| Quiz | `quiz.summary`, `quiz.attempt`, `quiz.result` | `quiz.start`, `quiz.save`, `quiz.submit` |
| Materiali | `attachments.list`, `attachment.download` | Nessuna scrittura studente sui materiali |
| Attestati | `certificates.list`, `certificate.download`, `certificate.verify` se pubblico abilitato | Nessuna emissione dal solo comando dello studente |

### 4.4 Contratti delle azioni critiche

| Azione | Richiesta minima | Esito e invarianti |
|---|---|---|
| `enrollment.start` | course_id, idempotency_key | Versione corrente selezionata dal server; nuova iscrizione o esistente equivalente; mai gruppi Joomla assegnati |
| `video.playback` | lesson_id, enrollment_id | Policy prima del plugin; descrittore temporaneo; nessun accesso amministrativo remoto concesso al browser |
| `progress.save` | enrollment_id, lesson_id, source_revision, session_id, eventi con ID/sequence/intervalli | Intervalli uniti, eventi duplicati ignorati, sorgente errata/conflitto segnalati; risposta con progresso e timestamp persistito |
| `lesson.complete` | enrollment_id, lesson_id, idempotency_key | Solo regole di conferma ammesse; non completa quiz fallito o video con soglia non raggiunta |
| `quiz.start` | quiz_id, enrollment_id, idempotency_key | Snapshot domande/ordine, attempt_id, scadenza se prevista; un solo tentativo aperto |
| `quiz.save` | attempt_id, expected_revision, risposte modificate, mutation_id | Proprietario e accesso correnti, risposte valide, scadenza server; nuova revisione oppure 409 |
| `quiz.submit` | attempt_id, expected_revision, idempotency_key | Consegna solo dell'ultima revisione salvata; nessuna risposta non confermata nascosta nel comando; server corregge e completa eventualmente il corso |
| `attachment.download` | attachment_id | Parent ricavato dal server, autorizzazione e revisione del file, binario o errore; niente filesystem path nel client |
| `certificate.download` | certificate_id | Documento proprio e stato verificato; applicazione della regola di mantenimento dopo perdita accesso corso |
| `course.migrate` backend | versione origine/destinazione, iscrizioni selezionate, equivalenze, expected_revision, idempotency_key | Simulazione/conferma e job tracciato; nessuna perdita silenziosa dello storico |
| `attachment.upload` backend | ambito/proprietario, file multipart, upload_request_id | Staging protetto con esito, revisione e metadati; nessuna pubblicazione automatica |
| `attachment.save` backend | staging_asset_id o asset autorizzato, ambito/proprietario, metadati, expected_revision | Associazione validata; un asset di altro autore/contesto non viene acquisito senza permesso |

La scelta di non includere risposte nuove in `quiz.submit` rende inequivocabile cosa è stato salvato: il client deve attendere conferma del salvataggio prima della consegna. Se la rete cade, può verificare tentativo e revisione; non ricrea il test. I campi esatti e i massimi numerici saranno fissati nello schema DTO, senza cambiare gli invarianti.

### 4.5 Backend, job e servizi core

Il catalogo include editor, ordinamento, workflow, versioni, iscrizioni/import, banca domande, materiali, report/export, attestati e diagnostica. Configurazione plugin, categorie, utenti, livelli, campi, tag, lingue, Mail Templates e privacy restano nei gestori core; non vengono duplicati come endpoint LMS.

Email, PDF, export estesi, migrazioni/import lunghi e pulizia staging vengono eseguiti da job tramite Scheduler/CLI amministrati. `jobs.get` mostra avanzamento; `job.download` consegna un output a chi ha ancora il permesso. Non creare un endpoint cron pubblico non autenticato. I job operano con l'ambito registrato, rivalidano permessi quando necessario e non usano genericamente un super user.

Webhook provider e API di upload remoto non sono necessari al collegamento manuale dei video previsto nel rilascio 1. Se aggiunti in futuro, avranno verifica firma/replay e non potranno conferire automaticamente completamenti didattici.

## 5. Verifiche da eseguire

1. Aggiungere un provider dimostrativo installando soltanto il plugin: selezione editor, player, capacità e progresso funzionano senza modifica del componente.
2. Disabilitare il provider usato: errore controllato, riferimenti conservati e nessun blocco globale del sito.
3. Allegati a corso e lezione disponibili solo nei rispettivi contesti autorizzati; URL manipolati e asset di altri corsi negati.
4. Sostituzione di file e nuova versione corso conservano i riferimenti storici; nessun impatto sul completamento.
5. Ogni endpoint rispetta metodo, sessione, CSRF ove pertinente, policy per oggetto e regole di cache; chiamate dirette non aggirano l'interfaccia.
6. Retry, doppia scheda, payload modificato e revoca di accesso non duplicano risultati né riscrivono dati confermati.

Questi sono contratti di progetto; non descrivono software già implementato o test già superati.

## Fonti tecniche

- [Joomla How Plugins Work](https://manual.joomla.org/docs/building-extensions/plugins/how-plugins-work/)
- [Joomla Plugin Methods and Arguments](https://manual.joomla.org/docs/building-extensions/plugins/methods-and-arguments/)
- [Joomla Ajax e JsonResponse](https://manual.joomla.org/docs/next/general-concepts/javascript/ajax/)
- [Joomla Web Services delle estensioni](https://docs.joomla.org/J4.x:Adding_an_API_to_a_Joomla_Component/en)

Le fonti confermano i meccanismi Joomla; gruppo, eventi, interfacce e task `jlms` sono contratti originali del progetto. Le firme definitive si verificano sulla patch Joomla 6 scelta.


Le specifiche individuali dei quattro plugin inclusi, i dizionari dei parametri e il ciclo di sviluppo sicuro sono definiti in Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md. I requisiti di inclusione nel pacchetto base prevalgono su qualsiasi interpretazione di plugin come componente facoltativo da distribuire separatamente.
