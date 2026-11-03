# Lezioni video — incremento 0.6

## Configurazione e limiti attuali

Nel Programma del corso, sotto Configurazione video, sono visibili lo stato di registrazione dei quattro provider, la configurazione della riproduzione e l’eventuale blocco per la politica dei video esterni. Gli operatori autorizzati possono aprire direttamente le opzioni Joomla. Lo stato configurato indica i controlli locali: la sorgente e la riproduzione vanno provate nell’aula.

Il pacchetto base contiene i quattro plugin Joomla del gruppo `jlmsvideo`. YouTube e Vimeo includono adapter JavaScript per gli SDK ufficiali. Abilitare il plugin nel gestore Joomla e attivare `playback_enabled`; entrambi sono chiusi per impostazione iniziale. Dalla0.7 Cloudflare Stream supporta token temporanei in modalità API e rinnovo: vedere [CLOUDFLARE_STREAM.md](CLOUDFLARE_STREAM.md) per prerequisiti e limiti. Dalla0.8 HTML5 supporta MP4 privati con PHP limitato/Nginx: vedere [HTML5_VIDEO.md](HTML5_VIDEO.md). La firma locale Cloudflare resta chiusa. La presenza nel pacchetto non ne dichiara il completamento.

Nelle opzioni del componente scegliere `blocked` (predefinito), `embed` (Mostra il player all’apertura) oppure `site_consent`. La precedente impostazione `click` richiede che il gestore selezioni la nuova modalità prima di attivare video esterni. L’aula mostra il player con i suoi controlli e mantiene disattivato l’autoplay. Con `site_consent`, il sito deve esporre `window.JlmsConsent.isAllowed(providerKey)` e inviare `window.dispatchEvent(new Event('jlms:consent-changed'))` a ogni cambiamento. Il consenso apre il player e la revoca lo rimuove. I video MP4 locali vengono mostrati all’apertura con l’autorizzazione Joomla.

Nessun riferimento video, iframe, SDK esterno o hash Vimeo è presente nella risposta GET dell'aula. I moduli locali del plugin caricano gli SDK esterni soltanto dopo consenso e risposta autorizzata del server. Le richieste verso un provider già avviate non possono essere ritirate; la revoca impedisce ulteriori istanze del player. Il sito deve autorizzare gli opportuni domini nella propria CSP. Non viene modificata la CSP globale Joomla.

In bozza selezionare il provider e una sorgente riconosciuta, inserire la durata in secondi con massimo tre decimali e confermarne la verifica. Sono supportate durate da 1 secondo a 8 ore. La pubblicazione rifiuta sorgenti/provider non disponibili e durate non confermate. Non viene effettuata una chiamata di rete durante la transazione di pubblicazione. La durata osservata dal player deve differire da quella pubblicata al massimo di 1 secondo o 0,5%, il maggiore dei due: altrimenti nessun nuovo progresso viene accettato. Metadati remoti automatici e modifica/sostituzione di video già pubblicati non sono implementati.

YouTube usa `youtube-nocookie.com` per l'iframe, Vimeo `dnt=1`, entrambi senza autoplay. Le capabilities attive sono posizione, durata e seek. UI velocità/caption/transcript e token a scadenza non sono dichiarati implementati. Le impostazioni storiche del manifest relative a cache, timeout, autoplay, lingua e privacy non hanno ancora un effetto configurabile: questo incremento usa i valori conservativi descritti qui. Non inserire credenziali nelle sorgenti. I riferimenti di un video YouTube/Vimeo sono necessariamente visibili a uno studente autorizzato quando il player viene caricato; il controllo Joomla protegge aula e progressi, non rende segreto un URL condivisibile del provider.

## Protocollo implementato

Tutti gli endpoint sono task MVC frontend Joomla, con `format=json`, sessione nativa, POST form-urlencoded, token CSRF nativo, `private, no-store`. Non sono ancora route del Web Services API Joomla. Il servizio `VideoProgressService` è registrato nel container; i plugin forniscono descrittori e adapter senza attribuire crediti.

| Task | Input aggiuntivi agli ID enrollment/lesson e token | Output principale |
|---|---|---|
| `video.playback` | `consent=1` | UUID sessione, revisione sorgente, sequenza iniziale, resume/durata, provider e opzioni whitelist |
| `video.save` | `session_id`, `source_revision`, `sequence`, `duration_ms`, `events_json` | prossima sequenza, replay/conflitto resume, copertura, completamento lezione/corso e percentuale |
| `video.close` | `session_id` | scadenza immediata della sessione |

`events_json` è una lista non vuota di massimo 100 eventi e 64 KiB; corpo HTTP massimo 75.000 byte. Ogni evento contiene esattamente `id`, `type`, `position_ms`, `client_ms`, `rate_milli`, `playing`, con interi/bool rigorosi. Tipi: play, position, pause, seeking, seeked, ended, buffering, ratechange. ID monotoni e tempo client relativo monotono; rate consentiti 0,25–2. Il client campiona ogni secondo, invia ogni 16–24 secondi con jitter e in pausa/fine; invia una sola richiesta alla volta, ritenta lo stesso batch immutabile e conserva solo un buffer limitato in memoria. Offline sospende la riproduzione; abbandono della pagina usa un invio best effort. Nessuna garanzia di persistenza dell'ultimo tratto in caso di chiusura/crash e nessun localStorage.

Ogni richiesta rilegge utente, gruppi/livelli Joomla, iscrizione, corso, versione, edizione, lingua e finestre correnti. Il lock sull'iscrizione precede i lock di progresso/sessione. Sequenze mancanti, sorgente cambiata, batch differenti con stessa sequenza e sessioni scadute restituiscono 409. Un replay identico ricontrolla prima l'accesso e non riattribuisce credito. Sessioni inattive scadono dopo 2 ore; massimo 32 attive per coppia iscrizione/lezione. La pulizia periodica delle righe scadute rimane da implementare.

La copertura è l'unione di intervalli in millisecondi. Si conta solo una progressione continua coerente con tempo client/rate, campioni distanti al massimo 30 secondi e budget cumulativo di tempo trascorso sul server per sessione, con tolleranza fissa di 2 secondi. Seek, buffering e fine isolata non danno credito. Soglia predefinita 90%, contatori obbligatori e completamento una sola volta. Schede concorrenti uniscono la copertura; una revisione obsoleta non sovrascrive il resume. Materiali allegati restano facoltativi. Il task manuale di completamento rifiuta le lezioni video.

La telemetria browser può essere falsificata: non certifica identità, attenzione o visione effettiva. Il budget è per sessione, non un sistema antifrode globale tra dispositivi. Autenticazione forte/proctoring e limiti globali più restrittivi richiedono progettazione separata. Errori 401/403/404/409 interrompono il player; problemi di rete/5xx/429 mantengono il batch per ritentare. Il client non ricrea silenziosamente una sessione dopo un conflitto.

## Collaudo ed estensibilità

Sono previsti test dominio, integrazione database/Joomla, richieste HTTP reali e mock di trasporto browser per consenso, retry e teardown. Le prove con video reali, browser desktop/mobile, CSP, cookie/CMP e rimozione remota del video richiedono ancora un ambiente di collaudo: i mock non sostituiscono queste prove. Il benchmark 300/500 studenti e la verifica della patch corrente Joomla sono ancora da eseguire.

Il contratto PHP v1, evento `onJlmsRegisterVideoProviders`, descriptor e factory lazy restano estensibili. La sola registrazione di un plugin futuro non aggira l'allowlist di pubblicazione/consegna di questo incremento: il nuovo provider richiede adapter, policy, test e abilitazione esplicita nel componente. Gli adapter YouTube/Vimeo, Cloudflare e HTML5 sono caricati da una mappa esplicita; i moduli JS attuali espongono `create(container, descriptor, emit, abortSignal)` e restituiscono `sample`, `pause`, `seek`, `destroy`; il contratto di caricamento generico per provider terzi rimane da completare per provider terzi.

Documentazione primaria SDK: [YouTube IFrame API](https://developers.google.com/youtube/iframe_api_reference), [Vimeo player.js](https://github.com/vimeo/player.js). Le specifiche complete di tutti e quattro i plugin sono in `specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md`; questo documento distingue il sottoinsieme implementato.

Il task `video.renew` e la verifica remota Cloudflare sono documentati in CLOUDFLARE_STREAM.md. Le sezioni0.6 descrivono il primo protocollo degli embed; TTL, doppio controllo di accesso e flusso di rinnovo introdotti nella0.7 si applicano ai descrittori a scadenza. Nessuna chiamata remota viene effettuata mentre sono detenuti lock di scrittura.

HTML5 usa la stessa sessione/protocollo dei progressi; consegna e policy locale sono documentate in HTML5_VIDEO.md. Il consenso ai provider esterni non blocca il video same-origin.

YouTube può restituire durata zero prima di caricare i metadati. L’aula aspetta un campione valido prima di registrare progressi e applica il controllo di durata al primo campione disponibile.

Per YouTube l’aula distingue il blocco CSP dello script, il mancato caricamento dell’SDK, il rifiuto dell’incorporamento e l’assenza dell’identificazione del sito. Il controllo avviene dopo l’autorizzazione al player; una risposta positiva della sessione non certifica la raggiungibilità del provider.
