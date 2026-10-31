> Stato implementazione0.6: YouTube/Vimeo dispongono di embed opt-in con consenso e telemetria; Cloudflare/HTML5 rimangono chiusi. Metadati automatici, firma, consegna privata e prove SDK reali non sono completati. La durata è verificata manualmente in bozza; capabilities attive solo posizione/durata/seek. Consultare docs/VIDEO.md nel repository per protocollo, configurazione e limiti. Le sezioni seguenti restano la specifica completa del prodotto da realizzare.

# LMS Joomla — Sviluppo sicuro e specifiche dei plugin video

Versione 1.0 · 30 ottobre 2026 · Parte obbligatoria della specifica del pacchetto base

## 1. Vincoli di sviluppo e distribuzione

Il componente e tutti i plugin distribuiti adottano sviluppo software sicuro, architettura MVC Joomla e servizi core applicabili. Questi sono requisiti di accettazione, non raccomandazioni facoltative. Il documento definisce il comportamento e i controlli da implementare; non certifica software già sviluppato o verifiche di sicurezza già superate.

Il pacchetto base installabile deve contenere componente, librerie condivise necessarie e i quattro plugin video specificati qui: YouTube, Vimeo, Cloudflare Stream e HTML5 per video locali. Sono estensioni Joomla separate per modularità, ma non moduli commerciali separati o funzioni escluse dal prodotto base. Nessun download manuale di uno di questi plugin deve essere necessario per completare l'installazione del pacchetto.

Installato non significa automaticamente attivo. L'installazione registra tutti i plugin; l'amministratore abilita quelli necessari tramite Joomla, dopo la configurazione. Nessun account provider, token, video dimostrativo pubblicato o credenziale predefinita viene creato. I servizi esterni mantengono i propri requisiti di account e contratto, separati dall'inclusione del software nel pacchetto.

I nomi `jlms` e `jlmsvideo` restano prefissi tecnici di lavoro fino al nome definitivo. I requisiti non sono legati a un'organizzazione o template.

## 2. Architettura Joomla e PHP

| Area | Regola obbligatoria | Evidenza di accettazione |
|---|---|---|
| Controller | Classi MVC Joomla appropriate, controllo metodo/token/input e invocazione dei servizi | Nessuna logica di voto, fruizione o accesso affidata al browser/template |
| Model | Modelli Joomla per dati, form, liste, ordinamenti, stato, checkout e persistenza | Form e operazioni standard integrati senza motori paralleli |
| View e tmpl | View prepara i dati, tmpl/layout genera HTML; output contestualmente codificato | Override possibili senza query, autorizzazioni o decisioni didattiche nei layout |
| Servizi | Policy e logica didattica condivise, dipendenze iniettate tramite container/provider | Stessa regola applicata a frontend, backend, task e futuro trasporto API |
| Plugin | CMSPlugin, eventi/SubscriberInterface e servizi Joomla secondo versione target | Nessuna patch del core o bootstrap alternativo |
| Stile PHP | PSR-12 e regole Joomla; namespace, tipi e contratti pubblici documentati | Lint e controllo stile automatici su componente e plugin |
| Runtime | Versioni PHP/Joomla supportate e matrice verificata prima del rilascio | Nessuna dipendenza da API rimosse o da versioni PHP fuori supporto dichiarato |
| Dati | Database driver/query builder Joomla e parametri bindati; transazioni brevi | Test SQL injection, atomicità e concorrenza |
| Asset | Web Asset Manager e asset del solo provider utilizzato | Nessun caricamento di tutti gli SDK in catalogo o a ogni progresso |
| Core | Applicare la matrice CORE-01–23 della specifica | Eventuali servizi non pertinenti motivati; niente duplicazione di utenti, ACL, categorie o privacy |

Separare sanitizzazione di contenuto consentito, validazione di dominio e codifica dell'output: una singola funzione generica di escape non sostituisce tutti i controlli. I segreti non devono essere semplici proprietà pubbliche delle entità o finire in serializzazioni automatiche.

## 3. Ciclo di sviluppo software sicuro

### 3.1 Prima e durante l'implementazione

- Definire modello delle minacce e confini di fiducia per studenti, autori, amministratori, plugin installati, file, player e servizi esterni. Includere accessi orizzontali fra studenti e verticali verso il backend.
- Collegare ogni minaccia rilevante ai controlli della specifica e ai test. Usare OWASP ASVS 5.0.0 come riferimento di verifica, senza dichiarare certificazione o conformità a un livello non effettivamente verificato.
- Sviluppare con repository e revisioni dei cambiamenti; review esplicita del codice sensibile prima del rilascio. Non affidare i controlli soltanto a scanner automatici.
- Bloccare dipendenze riproducibili nei lockfile, inventariare componenti terzi e licenze, verificare aggiornamenti e vulnerabilità. Nessun pacchetto dev, segreto, fixture privata o file temporaneo nello ZIP di produzione.
- Testare unità di dominio, servizi con database e integrazioni Joomla, comportamento end-to-end e casi negativi. Le prove devono verificare proprietà di sicurezza, non solo replicare le istruzioni del codice.

### 3.2 Controlli applicativi minimi

| Rischio | Controllo richiesto |
|---|---|
| SQL injection | Binding di valori e allowlist per colonne/ordinamenti, mai concatenazione di input nelle query |
| XSS e HTML attivo | Filtri Joomla e codifica contestuale in HTML/attributi/JSON/URL; niente iframe/script arbitrari o contenuti marcati affidabili senza permesso |
| CSRF | Token Joomla su mutazioni browser e controllo del metodo; il token non sostituisce autenticazione e ACL |
| IDOR/BOLA e privilegi | Policy per ogni oggetto, utente e parent; verifica anche su file, job, report, URL e endpoint diretti |
| Mass assignment | Campi accettati espliciti, campi sistema ignorati/rifiutati; nessuna modifica di user_id, gruppi, punteggi o stato da form non autorizzato |
| SSRF | Host e schemi fissi/allowlist per provider, HTTPS verificato, timeout e dimensione risposta limitati; nessun fetch server di URL arbitrari o destinazioni locali/private |
| Redirect remoti | Non seguire automaticamente redirect verso host non consentiti; rivalidare destinazione e risoluzione a ogni passaggio |
| Upload e traversal | MIME/estensione/dimensione verificati; nomi storage generati e percorsi non controllabili dal client; niente esecuzione o estrazione ZIP automatica |
| Race e replay | Revisioni, chiavi idempotenti, vincoli univoci e transizioni atomiche; nuova autorizzazione anche sul retry |
| Esposizione di dati | DTO selettivi, cache personale corretta, niente risposte quiz/segreti/payload completi nei log o HTML nascosto |
| Token e crittografia | Primitive PHP/librerie mantenute; generatori casuali sicuri per codici/token; nessuna crittografia o firma proprietaria improvvisata |
| Abuso delle risorse | Limiti su payload, paginazione, upload, retry e richieste; batch lunghi e code, senza ostacolare il carico legittimo previsto |
| Errori esterni | Eccezioni gestite, codici stabili e correlazione; nessun fallback a contenuti pubblici o disattivazione delle verifiche TLS |

Gli indirizzi video non sono password, ma URL con hash di privacy e token temporanei vanno trattati come dati da non riversare indiscriminatamente nei log. L'escaping deve coprire anche CSV e template email.

### 3.3 Segreti e configurazione

Le credenziali amministrative dei provider restano sul server. Preferire riferimenti a variabili d'ambiente o configurazione protetta fuori dalla radice pubblica; il parametro del plugin conserva il riferimento, non il segreto. Se una distribuzione richiede segreti nel database, usare una soluzione di cifratura mantenuta con chiave separata dal database e documentare backup/rotazione; non presumere che un normale campo password XML Joomla cifri automaticamente il valore.

I campi sono mascherati e non restituiti ai normali endpoint di lettura. Export della configurazione senza segreti; log senza chiavi, Authorization header o token completi. Rotazione e revoca devono essere documentate e testabili. Un autore di corsi non necessita del diritto di leggere o cambiare credenziali provider.

Le configurazioni del player rispettano CSP e politiche del sito; non si modifica una policy globale per inserire wildcard, script arbitrari o disabilitare protezioni. Gli SDK del provider vanno caricati soltanto quando il caricamento esterno è consentito. Gli asset esterni richiesti dal provider e i loro limiti di versionamento devono essere documentati, senza fingere che siano dipendenze completamente controllate dal nostro build.

### 3.4 Controlli di rilascio

Prima di distribuire: lint PHP, stile, analisi statica, scansione segreti/dipendenze, test funzionali e di sicurezza, controlli manuali ACL e upload, collaudi dei quattro plugin, installazione/aggiornamento e verifica ZIP. Le vulnerabilità critiche/alte confermate e pertinenti non risolte bloccano il rilascio; falsi positivi o non applicabilità sono motivati nel report, non ignorati tramite un'esclusione generale.

Produrre report dei controlli, inventario dipendenze e checksum; usare il sistema aggiornamenti Joomla su HTTPS senza modificare il core. Non dichiarare firma o verifica automatica dei pacchetti se non implementata. Ogni aggiornamento sensibile ripete le prove pertinenti; la checklist non viene segnata passata prima dell'esecuzione sul build.

## 4. Contratto comune completo dei plugin video

Il contratto PHP e frontend è definito anche in Servizi_endpoint_LMS_Joomla_1.0.md. Ogni plugin deve dichiarare la propria versione del contratto, registrare la factory nell'evento di progetto `onJlmsRegisterVideoProviders` e fornire identità, capacità, normalizzazione/validazione sorgente, metadati, playback descriptor e asset player. Il registro rifiuta chiavi duplicate e versioni incompatibili. Nessun provider conferisce autorizzazioni o valuta quiz.

| Aspetto comune | Specifica |
|---|---|
| Identificativo | Stabile e non traducibile; distinto dall'etichetta tradotta |
| Configurazione | Parametri plugin globali, valori per lezione ammessi e precedenza esplicita; nessun segreto per lezione |
| Lezione | provider_key, source_reference, source_revision e opzioni ammesse; niente codice embed arbitrario |
| Metadati | Titolo, durata e poster quando disponibili, con origine/cache e fallback dichiarato; nessun segreto |
| Player | Inizializzazione/distruzione, posizione/durata, ripresa e subscribe agli eventi comuni supportati |
| Eventi | ready, play, pause, position, seeking, seeked, ended, error; eventi mancanti dichiarati nelle capacità |
| Completamento | Solo il core calcola copertura e soglia; ended non equivale a completo; eventi aggregati e persistiti con fonte/revisione |
| Consenso | Placeholder locale; nessun iframe/SDK/poster remoto prima delle condizioni di caricamento; cleanup alla revoca |
| Errori | Codice comune, motivo leggibile e dettaglio tecnico senza segreti; guasto provider non blocca il sito intero |
| Aggiornamento | Versione plugin/contratto e compatibilità componente dichiarate; dati e riferimenti delle lezioni preservati |
| Disabilitazione | Provider non più selezionabile per nuovi contenuti; contenuti esistenti segnalati e riproduzione negata in modo controllato |
| Disinstallazione | Avviso sull'impatto ove gestito dal pacchetto; nessuna cancellazione remota dei video o dei risultati didattici |
| Documentazione | Manuale amministratore, autore e sviluppatore, parametri, esempi, errori, limitazioni, compatibilità e test |

Codici comuni previsti: SOURCE_INVALID, PROVIDER_DISABLED, CONFIGURATION_REQUIRED, CAPABILITY_UNSUPPORTED, CONSENT_REQUIRED, ACCESS_DENIED, REMOTE_UNAVAILABLE, MEDIA_NOT_FOUND, EMBED_NOT_ALLOWED, PLAYBACK_EXPIRED. Non esporre dettagli remoti riservati a un utente non autorizzato. Le traduzioni sono file lingua Joomla, non testi incorporati negli SDK.

## 5. Plugin YouTube

**Identità:** youtube · pacchetto di lavoro plg_jlmsvideo_youtube · incluso nel base.

| Area | Specifica funzionale e tecnica |
|---|---|
| Contenuti supportati | Singolo video riproducibile tramite embed ufficiale; niente playlist, canali o gestione live nel primo rilascio |
| Input autore | Video ID oppure URL watch, youtu.be o embed riconosciuto; estrazione ID e normalizzazione, niente query arbitraria trasferita al player |
| Parametri globali | Stato plugin, impostazioni player ammesse, lingua/fallback, origine del sito, impostazioni di caricamento/consenso; autoplay disattivato per default |
| Credenziali | Nessuna credenziale amministrativa necessaria per la sola riproduzione embed; API di gestione e upload non richieste |
| Dati per lezione | ID video, revisione, eventuale poster locale/trascrizione; nessun HTML iframe inserito manualmente |
| Playback | IFrame Player API ufficiale; configurazione generata dall'adapter e vincolata all'origine prevista |
| Tracking | Stato e posizione campionata dal player; seek e ripetizioni non attribuiti come nuova copertura; durata effettiva letta quando disponibile |
| Ripresa | Riposizionamento tramite API nei limiti del player/browser; nessuna promessa di autoplay su mobile |
| Accessibilità | Controlli del player e sottotitoli se presenti sul video; trascrizione locale separata; nessuna promessa di generazione sottotitoli dal LMS |
| Privacy e riservatezza | Un link non in elenco può essere condiviso; il controllo Joomla protegge la pagina, non l'URL YouTube fuori dal sito. Privacy del player e consenso non equivalgono a protezione per abbonato |
| Personalizzazione | Solo opzioni documentate del player; CSS del componente modifica il contenitore. Non garantire rimozione di branding, pubblicità o suggerimenti |
| Errori | Video rimosso/privato, embed disabilitato, configurazione/API bloccata, rete/consenso; mapping ai codici comuni |
| Fuori perimetro | Upload, libreria canale, statistiche account, trascrizioni scaricate automaticamente, credenziali Google dello studente |

Collaudi minimi: URL valido/non valido; embed permesso/negato; consenso assente/revocato; ripresa desktop/mobile; salto alla fine; intervalli rivisti; rete interrotta e player distrutto senza listener residui. La capacità dichiarata deve corrispondere al comportamento effettivo sulla matrice browser del rilascio.

## 6. Plugin Vimeo

**Identità:** vimeo · pacchetto di lavoro plg_jlmsvideo_vimeo · incluso nel base.

| Area | Specifica funzionale e tecnica |
|---|---|
| Contenuti supportati | Singolo video incorporabile; niente showcase, libreria o live nel primo rilascio |
| Input autore | URL Vimeo o player URL riconosciuto, oppure ID con eventuale privacy hash; hash necessario conservato senza perderlo durante la normalizzazione |
| Parametri globali | Stato plugin, dominio/origine del sito, lingua e opzioni player ammesse, caricamento/consenso; autoplay off |
| Credenziali | Riproduzione tramite player di video incorporabile; API account per gestione/upload non necessarie al flusso base. Eventuali metadati autenticati richiedono configurazione server separata e non sono requisito per la semplice riproduzione |
| Dati per lezione | ID, hash richiesto, revisione, poster/trascrizione; niente token amministrativo nel contenuto |
| Playback | Player SDK ufficiale e iframe generato dal plugin; gestione della promessa ready e degli errori |
| Tracking e ripresa | Eventi e lettura posizione/durata, seek controllato dal player, posizione salvata dal core; cambio velocità e altre funzioni solo se effettivamente disponibili |
| Privacy | Impostazioni del video e restrizione embed per dominio configurate sul provider; non equivalgono all'autenticazione Joomla del singolo studente |
| Dominio | Diagnostica di restrizione dominio/referrer; non disabilitare politiche del sito indiscriminatamente per far funzionare un embed |
| Accessibilità | Sottotitoli e controlli secondo il contenuto/provider; trascrizione locale e layout responsive |
| Personalizzazione | Opzioni del provider e dell'account; nessuna modifica cross-origin dei controlli interni via CSS del template |
| Errori | Hash assente/errato, video rimosso o non incorporabile, dominio non ammesso, SDK/rete/consenso; niente ripiego a link pubblico |
| Fuori perimetro | Upload, sincronizzazione remota, gestione piani/account, download video abilitato automaticamente |

Collaudi minimi: URL con/senza hash, dominio autorizzato e non autorizzato, player bloccato da consenso, errori di referrer, ripresa, completamento a soglia e cambio lezione senza doppio conteggio. L'anteprima amministrativa non genera progressi reali.

## 7. Plugin Cloudflare Stream

**Identità:** cloudflarestream · pacchetto di lavoro plg_jlmsvideo_cloudflarestream · incluso nel base.

| Area | Specifica funzionale e tecnica |
|---|---|
| Contenuti supportati | Video on demand Stream; upload/libreria/live non inclusi nel primo rilascio |
| Input autore | Video UID o URL Stream riconosciuto e riferito alla configurazione autorizzata; niente host API scelto dall'autore |
| Parametri obbligatori | Account di riferimento, dominio playback consentito, modalità di autorizzazione, riferimento al segreto server e stato plugin |
| Modalità token | Endpoint provider invocato dal server con credenziale a privilegi minimi, oppure firma locale con chiave dedicata e libreria mantenuta. La modalità scelta è esplicita; mai entrambe richieste senza motivo |
| Credenziali | Token API o chiave di firma secondo modalità, tramite SecretProvider; nessun segreto nel form lezione, export, log o DTO del browser |
| Parametri aggiuntivi | TTL predefinito 15 minuti, rinnovo anticipato, timeout/retry limitati, domini/origini autorizzate e download vietato per default |
| Dati per lezione | UID, revisione, poster/trascrizione e riferimento configurazione; nessun token playback permanente salvato nella lezione |
| Protezione | Per contenuti riservati requireSignedURLs deve essere attivo sul provider; verifica/diagnostica prima di dichiarare protetto un contenuto. Un token usato nel player non rende privato un video ancora accessibile pubblicamente sul provider |
| Playback | Stream Player API; controllo accesso Joomla prima di rilascio e rinnovo del descrittore temporaneo |
| Tracking | Eventi player normalizzati; nessuna chiamata API Stream a ogni salvataggio progresso; sorgente/revisione coerenti |
| Revoca | Dopo perdita livello nessun nuovo token/rinnovo; validità residua del token già emesso e buffer dichiarata, senza promessa di arresto immediato |
| Poster e sottotitoli | Risorse soggette alla stessa riservatezza/configurazione; nessun poster remoto pubblico che eluda le condizioni di caricamento |
| Errori | Credenziale assente/scaduta o account errato, UID mancante, video in elaborazione/non pronto, API indisponibile, token scaduto, restrizione origine; messaggi distinti e segreti oscurati |
| Limiti | Distribuzione, storage e requisiti account sono del provider; il pacchetto software non include un servizio Stream prepagato |

Collaudi minimi: utente autorizzato/non autorizzato; link pubblico del UID non utilizzabile per un video dichiarato riservato; token scaduto e rinnovo; credenziale revocata/ruotata; errore API e retry; assenza di segreti nei payload; origini non consentite; revoca accesso durante visione; avanzamento preservato dopo errore temporaneo.

La configurazione della protezione remota è responsabilità esplicita del gestore: il plugin non modifica di nascosto la privacy o cancella risorse del provider durante installazione/disinstallazione. Il test della connessione è separato dalla pubblicazione e dall'emissione di token per studenti.

## 8. Plugin HTML5 per video locali

**Identità:** html5 · pacchetto di lavoro plg_jlmsvideo_html5 · incluso nel base.

| Area | Specifica funzionale e tecnica |
|---|---|
| Contenuti supportati | Asset video del repository protetto del LMS, in formati/codec dichiarati compatibili; MP4 come primo formato di riferimento |
| Input autore | Selezione asset interno autorizzato; nessun percorso fisico o URL arbitrario da usare per fetch server |
| Parametri globali | Storage/delivery adapter, limiti upload video distinti dagli allegati, MIME/codec ammessi, gestione Range e controlli player |
| Credenziali | Nessuna credenziale video esterna; eventuali segreti storage gestiti soltanto dal servizio di storage |
| Dati per lezione | Asset ID/revisione, poster locale, sottotitoli ammessi e trascrizione |
| Playback | Elemento video HTML5 con controlli accessibili; URL controllato dal servizio LMS, non file privato messo nella cartella pubblica |
| Download/stream | Endpoint video.stream o consegna delegata al web server dopo autorizzazione; Range con 206/416 corretti quando supportato |
| Tracking | Eventi HTMLMediaElement normalizzati; stessa logica di intervalli, ripresa e soglia degli altri provider |
| Sicurezza | Verifica MIME/formato/percorso, nessuna esecuzione, transcodifica o unpack automatico; controllo ogni nuova richiesta protetta |
| Prestazioni | Preferire consegna efficiente del web server/storage; non mantenere processi PHP occupati da centinaia di stream né blocchi di sessione durante il trasferimento |
| Errori | Asset mancante, formato/codec non supportato, Range invalido, permesso revocato e consegna non configurata |
| Limiti | Nessuna promessa di supporto a ogni codec/browser, transcodifica automatica, DRM o impossibilità di copia |

Collaudi minimi: riproduzione/ripresa, Range valido e invalido, richiesta non autorizzata e manipolazione percorso, rimozione asset referenziato, cambio revisione, codec non riproducibile e carico sulla consegna reale. Il test 300/500 studenti deve indicare se usa video provider o locali: i due carichi infrastrutturali non sono equivalenti.

## 9. Documentazione e accettazione del pacchetto base

Ogni plugin deve avere una scheda di rilascio con: versione, contratto supportato, prerequisiti e dipendenze, parametri con tipi/default/obblighi, formati URL ammessi, dati salvati nella lezione, eventi/capacità, opzioni player, flusso metadati/playback, consenso e dati esterni, autorizzazioni, errori e diagnosi, aggiornamento/disattivazione/rimozione, limitazioni e test. I manuali comprendono procedura di configurazione e esempio di una lezione; mai credenziali reali o URL riservati copiati nei file di esempio.

La guida sviluppatori documenta interfacce, DTO, evento di registrazione, versionamento e come creare un nuovo plugin compatibile. Le modifiche a un plugin aggiornano insieme contratto, manuale, changelog e test pertinenti. Non è accettabile dichiarare completo il pacchetto se uno dei plugin base è privo di specifica o documentazione.

Installazione/aggiornamento dell'intero ZIP verificano compatibilità dei quattro plugin e del componente. L'aggiornamento preserva configurazione e stato abilitato, segreti esterni, riferimenti delle lezioni, progressi e override. Non si attiva da solo un provider precedentemente disattivato e non si amplia la visibilità dei video. Errori parziali producono diagnosi e procedura di recupero, senza dichiarare riuscito l'intero aggiornamento quando una parte non lo è.

## 10. Dizionario dei parametri dei plugin

I nomi seguenti sono il contratto della configurazione da implementare, con default di progetto. Lo stato abilitato/disabilitato usa il campo nativo dell'estensione Joomla e non un secondo interruttore nel database LMS. Provider key e versione contratto sono metadati readonly. Ogni parametro usa label/descrizione traducibili, con errori per campo; valori invalidi non vengono corretti o troncati silenziosamente.

### 10.1 Parametri comuni

| Parametro | Tipo | Obbligo e default | Validazione/comportamento |
|---|---|---|---|
| language_mode | enum site/user | Facoltativo, user con fallback site | Risolve lingue Joomla installate; niente lingua arbitraria dall'URL |
| autoplay | boolean | Facoltativo, false | Non garantisce avvio su mobile e non attribuisce fruizione prima dell'evento play |
| controls | boolean | Fisso true nel rilascio base | Controlli disponibili e accessibili; non nascondere l'interfaccia per forzare una frequenza |
| external_load_policy | enum inherit_site_policy | Obbligatorio per provider esterni | L'ereditarietà non consente di aggirare consenso o restrizioni del sito |
| metadata_cache_ttl | integer, secondi | Default 3600 | Intervallo applicativo 0–86400; cache mai per token playback o segreti |
| remote_timeout | integer, secondi | Default 5 ove usato | Intervallo applicativo 1–15; verifica TLS sempre attiva |
| remote_max_retries | integer | Default 1 ove usato | 0–2, backoff; nessun retry illimitato o su ogni evento player |

### 10.2 Parametri specifici YouTube

| Parametro | Tipo | Obbligo e default | Regola |
|---|---|---|---|
| site_origin | URL HTTPS dell'installazione | Obbligatorio, derivato e validato dal sito | Non impostabile dall'autore della lezione |
| embed_privacy_mode | enum inherit/standard/privacy_enhanced | Default inherit | La policy del sito prevale; standard usa il dominio embed ufficiale YouTube e privacy_enhanced il dominio ufficiale youtube-nocookie. Nessuna promessa di assenza totale di trattamento dati |
| video_id, nella lezione | stringa | Obbligatorio, nessun default | Estratto solo dai formati riconosciuti oppure validato come ID; query non ammesse eliminate con avviso |
| transcript_asset_id, nella lezione | ID asset | Facoltativo, null | Asset locale autorizzato; non scarica trascrizioni automaticamente |

Nessun api_key obbligatorio per l'embed base. Opzioni non documentate, URL canale e playlist sono rifiutati come sorgente singola. Le preferenze player non sono input JSON libero passato dal form.

### 10.3 Parametri specifici Vimeo

| Parametro | Tipo | Obbligo e default | Regola |
|---|---|---|---|
| allowed_site_origin | URL HTTPS del sito | Derivato dalla configurazione | Supporto diagnostico; l'allowlist remota rimane configurazione Vimeo |
| video_id, nella lezione | stringa numerica | Obbligatorio | Normalizzato da URL riconosciuto, mai confuso con ID di showcase o account |
| privacy_hash, nella lezione | stringa opaca fino a 256 caratteri | Obbligatorio solo quando richiesto dal video | Conservazione senza alterazione, encoding contestuale e redazione dai log; mai usato come codice eseguibile |
| transcript_asset_id, nella lezione | ID asset | Facoltativo, null | Contenuto locale sottoposto ai diritti della lezione |

L'account token per eventuali metadati autenticati è una funzione aggiuntiva separata dal player e non viene richiesto per pubblicare un semplice video già incorporabile. La documentazione utente spiega le impostazioni Vimeo da applicare, senza promettere funzioni non disponibili nell'account.

### 10.4 Parametri specifici Cloudflare Stream

| Parametro | Tipo | Obbligo e default | Regola |
|---|---|---|---|
| account_id | stringa | Obbligatorio | Account amministrato dal gestore; formato e corrispondenza verificati dal plugin |
| playback_domain | hostname HTTPS autorizzato | Obbligatorio | Dominio playback Stream riconosciuto e associato alla configurazione; non endpoint generico arbitrario |
| signing_mode | enum api_endpoint/local_key | Obbligatorio, api_endpoint | Determina i soli segreti necessari |
| api_token_ref | riferimento SecretProvider | Obbligatorio in api_endpoint | Nessun valore del token memorizzato nella lezione o esposto nei DTO |
| signing_key_ref e signing_key_id | riferimento + stringa | Obbligatori in local_key | Chiave server separata e libreria/primitive mantenute; niente JWT creato con algoritmo improvvisato |
| token_ttl_seconds | integer | Default 900 | Intervallo applicativo 60–3600, ulteriormente limitato dalle scadenze note e capacità effettive |
| renew_before_seconds | integer | Default 120 | Positivo e minore del TTL; rinnovo solo previa nuova autorizzazione |
| require_signed_playback | boolean | Fisso true per video riservati | Configurazione remota non verificata/non sicura impedisce di dichiarare il contenuto protetto |
| allow_download | boolean | Default false | Attivazione esplicita compatibile con risorsa, diritti e opzioni provider |
| video_uid, nella lezione | stringa | Obbligatorio | UID normalizzato e validato dal provider; nessun URL API controllato dall'autore |

Il test connessione non emette token a utenti anonimi e non cambia le impostazioni remote del video. Le origini di incorporamento consentite sono configurate e verificate nell'ambiente del provider; non sono un sostituto del livello Joomla.

### 10.5 Parametri specifici HTML5

| Parametro | Tipo | Obbligo e default | Regola |
|---|---|---|---|
| storage_profile_id | ID di configurazione | Obbligatorio | Storage protetto già verificato dal gestore |
| delivery_mode | enum webserver/storage/php_bounded | Obbligatorio, webserver se disponibile | Fallback PHP esplicito e sottoposto a limiti/collaudo; nessun fallback a file pubblico |
| video_upload_max_bytes | integer positivo | Configurazione sito | Limitato da hosting e storage; separato dal limite allegati di 20 MiB |
| allowed_video_formats | allowlist | Default mp4 | MIME/codec effettivi e compatibilità browser verificati; estensione da sola insufficiente |
| preload | enum none/metadata | Default metadata | Non precaricare l'intero filmato nel catalogo o prima dell'autorizzazione |
| video_asset_id, nella lezione | ID asset | Obbligatorio | Asset/revisione appartenenti a contesto autorizzato, nessun percorso fisico nel client |
| captions_asset_ids | elenco ID asset e lingua | Facoltativo, vuoto | Formati sottotitoli ammessi, risorse protette e lingua dichiarata |

Il profilo di consegna determina il supporto Range e lo espone nelle capacità effettive. Se la consegna richiesta non è disponibile non si dichiara la funzione pronta: mostrare il requisito da configurare.

## Fonti tecniche

- Joomla MVC: https://manual.joomla.org/docs/general-concepts/forms/mvc-etc/
- Joomla coding style: https://manual.joomla.org/docs/get-started/codestyle/
- Joomla query sicure: https://manual.joomla.org/docs/security/secure-db-queries/
- Joomla CSRF: https://manual.joomla.org/docs/security/csrf-protection/
- Joomla plugin: https://manual.joomla.org/docs/building-extensions/plugins/how-plugins-work/
- PHP primitive casuali sicure: https://www.php.net/random-bytes
- OWASP ASVS: https://owasp.org/www-project-application-security-verification-standard/
- YouTube IFrame Player API: https://developers.google.com/youtube/iframe_api_reference
- Vimeo Player SDK: https://github.com/vimeo/player.js
- Vimeo privacy dei video: https://help.vimeo.com/hc/en-us/articles/12426199699985-About-video-privacy-settings
- Cloudflare Player API: https://developers.cloudflare.com/stream/viewing-videos/using-the-stream-player/using-the-player-api/
- Cloudflare protezione Stream: https://developers.cloudflare.com/stream/viewing-videos/securing-your-stream/

Le fonti documentano meccanismi e capacità; i vincoli del pacchetto, i servizi del progetto e le prove indicate sono requisiti originali da implementare e collaudare sulla release target.
