# Lezioni video

**Guide operative per ruolo (PBASE-03):** [Amministratore, autore e sviluppatore](VIDEO_PROVIDER_ROLES_1.0.0.md), con esempio di plugin Joomla aderente al contratto di registrazione ma volutamente senza player.

## Configurazione e limiti attuali

**0.39 — acquisizione della durata:** automatico nell’editor per YouTube/Vimeo tramite SDK ufficiali e Cloudflare Stream tramite API server; durata MP4 dal file invariata. Nuove prove in corso; il collaudo completo con account/SDK/browser reali rimane distinto dalla CI controllata.

Nel Programma del corso, sotto Configurazione video, sono visibili lo stato di registrazione dei quattro provider, la configurazione della riproduzione e l’eventuale blocco per la politica dei video esterni. Gli operatori autorizzati possono aprire direttamente le opzioni Joomla. Lo stato configurato indica i controlli locali: la sorgente e la riproduzione vanno provate nell’aula.

Il pacchetto base contiene i quattro plugin Joomla del gruppo `jlmsvideo`. YouTube e Vimeo includono adapter JavaScript per gli SDK ufficiali. Abilitare il plugin nel gestore Joomla e attivare `playback_enabled`; entrambi sono chiusi per impostazione iniziale. Cloudflare Stream supporta token temporanei esclusivamente tramite API e rinnovo; il form non espone più la firma locale non implementata e i parametri legacy vengono ignorati. Vedere [CLOUDFLARE_STREAM.md](CLOUDFLARE_STREAM.md) per prerequisiti e limiti. HTML5 supporta MP4 privati tramite streaming PHP compatibile con Apache/LiteSpeed: vedere [HTML5_VIDEO.md](HTML5_VIDEO.md). La firma locale Cloudflare resta chiusa. La presenza nel pacchetto non ne dichiara il completamento.

Nelle opzioni del componente scegliere `blocked` (predefinito), `embed` (Mostra il player all’apertura) oppure `site_consent`. La precedente impostazione `click` richiede che il gestore selezioni la nuova modalità prima di attivare video esterni. L’aula mostra il player con i suoi controlli e mantiene disattivato l’autoplay. Con `site_consent`, il sito deve esporre `window.JlmsConsent.isAllowed(providerKey)` e inviare `window.dispatchEvent(new Event('jlms:consent-changed'))` a ogni cambiamento. Il consenso apre il player e la revoca lo rimuove. I video MP4 locali vengono mostrati all’apertura con l’autorizzazione Joomla.

Nessun riferimento video, iframe, SDK esterno o hash Vimeo è presente nella risposta GET dell'aula. I moduli locali del plugin caricano gli SDK esterni soltanto dopo consenso e risposta autorizzata del server. Le richieste verso un provider già avviate non possono essere ritirate; la revoca impedisce ulteriori istanze del player. Il sito deve autorizzare gli opportuni domini nella propria CSP. Non viene modificata la CSP globale Joomla.

In bozza scegliere il provider e la sorgente: il form usa **Rileva automaticamente**. L’anteprima amministrativa YouTube/Vimeo legge i metadati mediante gli stessi adapter SDK ufficiali; YouTube può restituire zero finché non si avvia l’anteprima. L’avviso indica questa azione, senza chiedere secondi esatti. Stream legge la durata con le credenziali API già configurate sul server, senza inviarle al browser. Gli MP4 locali continuano a essere misurati all’upload/verifica. Non è richiesta la precedente casella di conferma manuale. La durata rilevata appare come informazione; la sorgente è necessaria, non l’inserimento ordinario della durata.

Se il rilevamento non riesce si può riprovare, salvare la bozza incompleta o scegliere **Inserimento manuale di ripiego** e dichiarare i secondi (massimo tre decimali). Una durata valida da1secondo a8ore rimane necessaria alla pubblicazione di video tracciati. Se si modifica soltanto il testo della stessa sorgente, un errore temporaneo di acquisizione non cancella la durata già salvata. Cambiare sorgente senza una nuova misura produce una bozza da completare, senza riutilizzare la vecchia durata.

Una ricevuta di sessione lega acquisizione, operatore, corso/versione, modulo/lezione, revisioni e sorgente normalizzata; dura15minuti e viene consumata al salvataggio. Ripetere il rilevamento se scaduta. La provenienza distingue `provider_api`, `author_sdk` e `declared`; i file locali conservano la prova di verifica esistente. Un valore SDK fornito dal browser dell’autore è una misura editoriale autorizzata, non una certificazione API server. Le misure tardive di una sorgente diversa e le richieste obsolete vengono rifiutate. Metadati e anteprima richiedono permessi correnti, checkout compatibile e politica/consenso esterno; con site_consent il ponte del gestore consensi deve essere disponibile anche nel backend. Nessun autoplay nascosto o iscrizione/progresso viene creato dall’anteprima.

La fruizione accetta scarti entro **max(2secondi, min(5secondi,1% della durata pubblicata))**, arrotondando il limite al millisecondo superiore. La stessa regola è applicata nel client e nel server, anche al confronto API Stream prima della pubblicazione. Nel limite, intervalli e denominatore usano il tempo effettivamente osservato dal player: un video lievemente più corto non rimane impossibile da completare. La copertura è sempre l’unione degli intervalli reali, senza credito per seek/fine, con limiti di tempo server e idempotenza; una sessione non riscrive durata o revisione della lezione pubblicata. Scarti maggiori, durate non finite/zero o oltre8ore restano errori, senza azzerare i completamenti acquisiti. Per sostituire il contenuto usare una nuova versione del programma.

Supporto previsto per video on demand a durata finita. Non è implementata una certificazione server dei live YouTube tramite Data API: il percorso senza API key usa lo SDK, e durata zero/instabile o incompatibile non deve essere interpretata come completamento. Il collaudo reale deve verificare anche errori dei provider, CSP, hash Vimeo, consenso e disponibilità dei metadati.

L'iframe YouTube utilizza `strict-origin-when-cross-origin` per identificare il sito nelle richieste cross-origin, come raccomandato da YouTube; il codice 153 indica l'identificazione del sito mancante, ma anche altri problemi di browser/provider possono impedire il player. Nel controllo editoriale gli errori CSP, SDK, referrer e non-incorporabilità sono distinti e non collassano più nel generico errore metadati. Restano necessari consenso e policy corretti; usare il ripiego manuale di durata solo per una sorgente controllata, senza considerarlo soluzione a un embed non funzionante. Nessuna dichiarazione di collaudo live di YouTube è derivabile dalla CI.

**Diagnostica P0 #532:** rilevato WAM `joomla.asset.json` fermo a 0.45.0 con componente a 0.57.7; ciò può mantenere in cache script più vecchi in editor e frontend. Il package builder ora imposta il versionamento WAM sulla versione del pacchetto più un'impronta dei file media del componente e dei provider, in modo da invalidare la cache anche tra build con la stessa versione e CI controlla l'asset incluso nella ZIP Joomla. L'editor distingue problemi di server (HTTP 5xx), rete, risposta JSON, modulo player e SDK. Questi sintomi indicano la fase di guasto, ma non sostituiscono una prova reale di caricamento video. Dopo upgrade verificare lo script JS effettivamente servito e la versione del parametro cache nella scheda Rete del browser.

YouTube usa `youtube-nocookie.com` per l'iframe, Vimeo `dnt=1`, entrambi senza autoplay. Le capabilities attive sono posizione, durata e seek. La velocità è disponibile dove il provider la supporta; sottotitoli/caption usano il selettore nei provider con tracce interrogabili e la preferenza di avvio YouTube/Cloudflare descritta sotto. La trascrizione della lezione video può essere redatta localmente nell’editor e resa nell’aula autorizzata; non viene generata né importata automaticamente dal provider; i token a scadenza sono disponibili nel percorso Cloudflare dedicato. Le impostazioni storiche del manifest relative a cache, timeout, autoplay, lingua e privacy non hanno ancora un effetto configurabile: il flusso descritto usa i valori conservativi descritti qui. Non inserire credenziali nelle sorgenti. I riferimenti di un video YouTube/Vimeo sono necessariamente visibili a uno studente autorizzato quando il player viene caricato; il controllo Joomla protegge aula e progressi, non rende segreto un URL condivisibile del provider.

Per le lezioni in modalità apprendimento tracciato, la pubblicazione e la creazione del descrittore di riproduzione verificano posizione, durata e seek sia nelle capabilities dichiarate dall’adapter, sia in quelle restituite dal descrittore. Se una di queste manca, la pubblicazione è bloccata o la riproduzione fallisce in modo chiuso; non viene assegnato progresso. Velocità, caption, trascrizione, playback a scadenza e download non sono prerequisiti di completamento. Le lezioni in modalità consultazione non richiedono capabilities di tracciamento.

## Protocollo implementato

Tutti gli endpoint sono task MVC frontend Joomla, con `format=json`, sessione nativa, POST form-urlencoded, token CSRF nativo, `private, no-store`. Non sono ancora route del Web Services API Joomla. Il servizio `VideoProgressService` è registrato nel container; i plugin forniscono descrittori e adapter senza attribuire crediti.

| Task | Input aggiuntivi agli ID enrollment/lesson e token | Output principale |
|---|---|---|
| `video.playback` | `consent=1` | UUID sessione, revisione sorgente, sequenza iniziale, resume/durata, provider e opzioni whitelist |
| `video.save` | `session_id`, `source_revision`, `sequence`, `duration_ms`, `events_json` | prossima sequenza, replay/conflitto resume, copertura, completamento lezione/corso e percentuale |
| `video.close` | `session_id` | scadenza immediata della sessione |

`events_json` è una lista non vuota di massimo 100 eventi e 64 KiB; corpo HTTP massimo 75.000 byte. Ogni evento contiene esattamente `id`, `type`, `position_ms`, `client_ms`, `rate_milli`, `playing`, con interi/bool rigorosi. Tipi: play, position, pause, seeking, seeked, ended, buffering, ratechange. ID monotoni e tempo client relativo monotono; rate consentiti 0,25–2. Il client campiona ogni secondo, invia ogni 16–24 secondi con jitter e in pausa/fine; invia una sola richiesta alla volta, ritenta lo stesso batch immutabile e conserva solo un buffer limitato in memoria. Offline sospende la riproduzione; abbandono della pagina usa un invio best effort. Nessuna garanzia di persistenza dell'ultimo tratto in caso di chiusura/crash e nessun localStorage.

Ogni richiesta rilegge utente, gruppi/livelli Joomla, iscrizione, corso, versione, edizione, lingua e finestre correnti. Nella 0.41.6 integrata, lo stato e la data di pubblicazione della scheda del corso non sono una scadenza dell’accesso video già autorizzato; fanno fede le date dell’edizione e dell’iscrizione. Il lock sull'iscrizione precede i lock di progresso/sessione. Sequenze mancanti, sorgente cambiata, batch differenti con stessa sequenza e sessioni scadute restituiscono 409. Un replay identico ricontrolla prima l'accesso e non riattribuisce credito. Sessioni inattive scadono dopo 2 ore; massimo 32 attive per coppia iscrizione/lezione. Ogni nuova sessione autorizzata elimina al massimo 250 righe scadute in ordine di scadenza, tramite l’indice dedicato; il limite mantiene breve la transazione. È una pulizia opportunistica: non sostituisce una pianificazione periodica per siti inattivi.

La copertura è l'unione di intervalli in millisecondi. Si conta solo una progressione continua coerente con tempo client/rate, campioni distanti al massimo 30 secondi e budget cumulativo di tempo trascorso sul server per sessione, con tolleranza fissa di 2 secondi. Seek, buffering e fine isolata non danno credito. Soglia predefinita 90%, contatori obbligatori e completamento una sola volta. Schede concorrenti uniscono la copertura; una revisione obsoleta non sovrascrive il resume e mantiene il conflitto per tutta la sessione, così i batch successivi della stessa scheda obsoleta non possono riutilizzare la revisione aggiornata per sovrascrivere la ripresa. Materiali allegati restano facoltativi. Il task manuale di completamento rifiuta le lezioni video.

La telemetria browser può essere falsificata: non certifica identità, attenzione o visione effettiva. Il budget è per sessione, non un sistema antifrode globale tra dispositivi. Autenticazione forte/proctoring e limiti globali più restrittivi richiedono progettazione separata. Errori 401/403/404/409 interrompono il player; problemi di rete/5xx/429 mantengono il batch per ritentare. Il client non ricrea silenziosamente una sessione dopo un conflitto.

## Collaudo ed estensibilità

Sono previsti test dominio, integrazione database/Joomla, richieste HTTP reali e mock di trasporto browser per consenso, retry e teardown. Le prove con video reali, browser desktop/mobile, CSP, cookie/CMP e rimozione remota del video richiedono ancora un ambiente di collaudo: i mock non sostituiscono queste prove. Il benchmark 300/500 studenti e la verifica della patch corrente Joomla sono ancora da eseguire.

Il contratto PHP v1, evento `onJlmsRegisterVideoProviders`, descriptor e factory lazy restano estensibili. La sola registrazione di un plugin futuro non aggira l'allowlist di pubblicazione/consegna di il flusso descritto: il nuovo provider richiede adapter, policy, test e abilitazione esplicita nel componente. Gli adapter YouTube/Vimeo, Cloudflare e HTML5 sono caricati da una mappa esplicita; i moduli JS attuali espongono `create(container, descriptor, emit, abortSignal)` e restituiscono `sample`, `pause`, `seek`, `destroy`; il contratto di caricamento generico per provider terzi rimane da completare.

Documentazione primaria SDK: [YouTube IFrame API](https://developers.google.com/youtube/iframe_api_reference), [Vimeo player.js](https://github.com/vimeo/player.js). Le specifiche complete di tutti e quattro i plugin sono in `specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md`; questo documento distingue il sottoinsieme implementato.

Il task `video.renew` e la verifica remota Cloudflare sono documentati in CLOUDFLARE_STREAM.md. Le sezioni0.6 descrivono il primo protocollo degli embed; TTL, doppio controllo di accesso e flusso di rinnovo introdotti nella0.7 si applicano ai descrittori a scadenza. Nessuna chiamata remota viene effettuata mentre sono detenuti lock di scrittura.

HTML5 usa la stessa sessione/protocollo dei progressi; consegna e policy locale sono documentate in HTML5_VIDEO.md. Il consenso ai provider esterni non blocca il video same-origin.

YouTube può restituire durata zero prima di caricare i metadati. L’aula aspetta un campione valido prima di registrare progressi e applica il controllo di durata al primo campione disponibile.

Per YouTube l’aula distingue il blocco CSP dello script, il mancato caricamento dell’SDK, il rifiuto dell’incorporamento e l’assenza dell’identificazione del sito. Il controllo avviene dopo l’autorizzazione al player; una risposta positiva della sessione non certifica la raggiungibilità del provider.

L’adapter YouTube crea l’iframe con origine del sito e API abilitate, poi attende il caricamento prima di collegare l’SDK. L’iframe usa un referrer limitato all’origine del sito.



### Contratto `VideoCapabilities` (IMP-05)

Le capabilities sono relative all’adapter attivo e alla sorgente validata; provider disabilitato/non configurato o sorgente con `providerKey` differente restituisce tutte le flags a `false`. La matrice indica il supporto dell’adapter, non la presenza garantita di una traccia o di una velocità nel singolo video.

| Provider | posizione/durata/seek | velocità | caption | trascrizione LMS | playback a scadenza | download |
|---|---|---|---|---|---|---|
| YouTube | sì | sì, se player/video la espongono | sì, lingua preferita all’avvio; nessun selettore JD Learn | no | no | no |
| Vimeo | sì | sì, se account/video la consentono | sì, tracce interrogabili quando presenti | no | no | no |
| Cloudflare Stream | sì, se configurato | no | sì, preferenza di avvio; nessun selettore JD Learn | no | sì, token temporaneo | no |
| HTML5 | sì, se abilitato | sì, secondo supporto browser | sì, tracce native quando presenti | no | no | no |

`transcript` è `false` per tutti gli adapter: la trascrizione editoriale locale è gestita da JD Learn e non dipende dal provider. Le caption non sono generate dal componente. La velocità e le tracce restano facoltative e non modificano le regole di completamento.

## Preferenze del player (STU-05)

Nel player aula la velocità è una preferenza locale del browser, conservata nel solo localStorage del sito. Il selettore compare solo quando il player e il video segnalano che una velocità diversa da 1× è disponibile. YouTube fornisce i valori per il singolo video; Vimeo viene verificato tramite il suo SDK e può non consentire la funzione secondo account/video; HTML5 usa le velocità del browser. Cloudflare Stream non espone l’impostazione nella sua API e resta senza selettore. La velocità campionata continua a essere inclusa nella telemetria per mantenere corretto il calcolo degli intervalli; la preferenza non modifica la soglia di completamento. La selezione non viene sincronizzata fra dispositivi né salvata nel profilo Joomla.

Quando il player espone le tracce di sottotitoli/caption, l’aula mostra un selettore con le lingue disponibili e una voce per disattivarle. Il browser memorizza localmente la scelta; all’apertura successiva seleziona la lingua esatta o, se manca, una traccia con lo stesso codice lingua principale. Vimeo e HTML5 espongono le tracce al selettore. YouTube e Cloudflare Stream non espongono qui un elenco interrogabile: all’avvio ricevono la lingua locale preferita, se valida, e il provider decide se è disponibile; YouTube abilita i caption e Cloudflare imposta `defaultTextTrack`. Se la traccia non è disponibile, il provider mantiene il suo comportamento normale. I controlli nativi consentono allo studente di cambiare scelta durante la riproduzione. La preferenza non viene inviata al server né sincronizzata tra dispositivi; accesso a localStorage non disponibile o valori non validi sono ignorati. L’anteprima editoriale usa lo stesso controllo, senza creare progressi.


## P0 #532 — handshake YouTube IFrame API frontend (PR #541)

**P0 #532 — race YouTube IFrame API (PR #541, CI e test live pendenti):** l'adapter collegava `YT.Player` solo dopo `iframe.onload`, evento di navigazione della pagina YouTube e non garanzia di handshake dell'IFrame API. Ora la API viene collegata subito dopo l'inserimento dell'iframe nel DOM, prima del completamento della navigazione; referrer policy, dominio `youtube-nocookie.com`, ID video, consenso e telemetria restano invariati. Evidenza: l'utente vede il poster, Play resta su spinner in Safari e Chrome; YouTube diretto funziona. L'esito reale della modifica deve essere confermato sul sito di prova. Avanzamento globale 79,56% invariato.


## P0 #532 — delega autoplay per playback utente (PR #543)

**P0 #532 — permesso riproduzione IFrame YouTube (PR #543, collaudo installazione Joomla di prova pendente):** il player JD Learn dichiarava `allow="encrypted-media; fullscreen; picture-in-picture"`, omettendo `autoplay` richiesto dagli esempi ufficiali Google per gli iframe YouTube. La configurazione ora delega il permesso `autoplay` all'iframe, senza attivare autoplay automatico (il parametro resta `autoplay=0`); SDK, consenso, `Referer`, ACL e calcolo dei progressi invariati. Test di regressione sull'attributo `allow` e sul parametro autoplay. L'eventuale blocco residuo va diagnosticato con evidenza dell'evento YouTube, non con ulteriori modifiche speculative. Percentuale globale 79,56% invariata.

## Modalità didattica YouTube — fine lezione

Gli iframe YouTube sono caricati su `www.youtube-nocookie.com` con `rel=0`: secondo le policy correnti del provider questo **non elimina** i suggerimenti, ma ne limita la provenienza al canale del video. JD Learn non deve mascherare, intercettare o coprire i link e i controlli nativi dell'iframe. In aula, sotto il player, un riquadro nativo accessibile appare **solo** per YouTube quando il provider segnala `ended` **e** il salvataggio di progresso restituisce `lesson_completed=true` dal server. Offre il pulsante **Lezione successiva** esclusivamente quando la lezione successiva editoriale è accessibile secondo calendario e prerequisiti; nell'ultima lezione mostra la sola conferma. In preview e in modalità non tracciata non attribuisce completamenti. Replay, consenso revocato o chiusura del player rimuovono il riquadro. È sempre possibile usare i controlli YouTube. Non è garantita l'assenza di video correlati all'interno dell'iframe; per un ambiente senza tali elementi usare un provider controllabile (HTML5/Cloudflare Stream).

### P0: ripristino riproduzione dopo PR #571 (PR #572)

La PR #571, che sostituiva in loco l'iframe YouTube alla fine della lezione, è stata ritirata dal runtime a seguito di regressione segnalata nell'ambiente Joomla: i video non partivano. La PR #572 ripristina il flusso player della PR #570, precedentemente collaudata. Il riquadro finale rimane **sotto** all'iframe; non è ancora stata realizzata la soluzione in loco. Continuano ad applicarsi `rel=0`, tracciamento e guardia server per `lesson_completed`. Il nuovo design dovrà essere rivalutato separatamente con collaudo reale su video iniziale, replay, rinnovo grant e consenso.

### P0 — riapertura delle lezioni già completate

Confermato in collaudo reale: il video mai completato parte, mentre la lezione completata può mostrare «Lezione video completata» e terminare subito per il seek automatico a `resume_ms` prossimo alla durata. Il grant di playback espone `lesson_completed` e, per lezioni completate, `resume_ms=0`; il frontend non applica mai il seek di ripresa quando `lesson_completed=true`. Le lezioni in corso mantengono la ripresa salvata. La riapertura **non** azzera copertura, storico, completamento, iscrizione o attestato e non duplica il conteggio. Il collaudo browser reale rimane necessario prima della chiusura P0.
