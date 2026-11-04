# Cloudflare Stream

Il plugin base `plg_jlmsvideo_cloudflarestream` supporta video on demand e token temporanei tramite API. È disabilitato inizialmente, richiede `playback_enabled=1` e la policy di consenso del componente. La modalità `local_key` resta chiusa: nessuna firma locale o libreria JWT viene presentata come implementata. HTML5 resta da realizzare. Il pacchetto è di sviluppo.

## Configurazione

| Campo plugin | Valore/regola |
|---|---|
| `account_id` | Account Cloudflare, 32 caratteri esadecimali minuscoli |
| `playback_domain` | Host esatto `customer-CODICE.cloudflarestream.com`, senza schema/path/porta |
| `site_host` | Host del sito, es. `learning.example.org`; deve essere autorizzato esattamente nel video |
| `signing_mode` | `api_endpoint` in questo incremento |
| `api_token_ref` | Solo un riferimento `env:JLMS_SECRET_NOME`, mai il token API |
| `token_ttl_seconds` | 60–3600, default900; ulteriormente ridotto dalle scadenze didattiche note |
| `renew_before_seconds` | Positivo e inferiore al TTL, default120 |
| `remote_timeout` | 1–10 secondi per richiesta, default5 |

Il gestore dell'hosting deve iniettare la variabile nell'ambiente **del processo PHP del sito**, senza pubblicarla in file accessibili dal web. `EnvironmentSecretProvider` accetta solo nomi con prefisso `JLMS_SECRET_` e valori ASCII stampabili, 10–4096 caratteri senza spazi/CR/LF. I parametri Joomla salvano soltanto il nome. Nessun segreto reale viene creato, inserito negli esempi o salvato nelle lezioni. Rotazione: aggiornare il valore server; risoluzione a ogni nuova richiesta, senza cache persistente del segreto. Non vengono modificati `configuration.php`, gruppi Joomla o impostazioni remote del provider.

La credenziale deve poter leggere i dettagli del video ed emettere token Stream per l'account configurato. Assegnare soltanto i permessi effettivamente richiesti dagli endpoint utilizzati: verificare nella configurazione Cloudflare i permessi dell'account, senza usare una Global API Key. La UI diagnostica dedicata, i connettori per vault esterni e il test di connessione autonomo restano da completare.

Sul video Cloudflare impostare `requireSignedURLs=true` e `allowedOrigins` contenente l'hostname esatto del sito. Liste vuote, wildcard `*`, host assente e wildcard di sottodominio non sono accettati da questo incremento. Il plugin non abilita automaticamente queste protezioni. Il server verifica UID/account, video pronto, durata e hostname HLS associato alla configurazione. Il dominio non è scelto dall'autore e nessun endpoint arbitrario viene richiesto.

## Pubblicazione e rilascio

L'autore usa un UID/URL riconosciuto e conferma la durata, come per gli altri video. Prima di congelare una bozza, il componente legge i metadati remoti senza lock di scrittura; nella transazione successiva ricontrolla revisioni, sorgenti e durata. Una verifica fallita lascia la bozza invariata. L'incremento limita a100 le lezioni Cloudflare per una singola pubblicazione e non ha ancora una verifica asincrona: i timeout PHP/provider possono richiedere un limite pratico inferiore. Non è una certificazione della configurazione remota futura.

Per ogni rilascio o rinnovo, il componente:

1. Verifica accesso corrente Joomla, proprietà, iscrizione e sorgente.
2. Legge i dettagli da `https://api.cloudflare.com/client/v4/accounts/{account}/stream/{uid}` e richiede il token a `.../{uid}/token`; TLS verificato, redirect disabilitati, timeout limitato e nessun retry automatico server.
3. Rifiuta video pubblici/non pronti, dominio/origini incoerenti, risposte inattese e token con UID, algoritmo, durata o download non conformi. Il token proviene esclusivamente dalla risposta TLS dell'API fissa: non si accettano JWT del client e non si presenta il parsing dei claim come verifica crittografica autonoma.
4. Ricontrolla identità/entitlement e sorgente dopo la rete; soltanto allora conserva lo stato di sessione e restituisce il descrittore. Nessuna chiamata remota avviene nella transazione di scrittura.

Il browser riceve dominio e token temporaneo, scadenza e intervallo di rinnovo. La credenziale amministrativa, account config e riferimento al segreto non sono inclusi nel descrittore. Il token playback è necessariamente visibile al destinatario autorizzato e rimane un bearer token fino alla sua scadenza: non è legato crittograficamente all'utente Joomla. Download disabilitato; nessun poster remoto aggiuntivo, traccia o link MP4 viene emesso.

TTL default15 minuti, rinnovo previsto2 minuti prima; per TTL brevi si usa al massimo metà della vita residua come anticipo. Il token è limitato anche dalla fine nota di corso, edizione e iscrizione. Dopo revoca accesso non si rilasciano nuovi token; un token già consegnato e il buffer possono conservare validità residua. Cambiamenti remoti successivi alla verifica non possono essere esclusi dal componente.

## Rinnovo e progressi

Endpoint aggiuntivo: `POST index.php?option=com_jlms&task=video.renew&format=json`, sessione e CSRF nativi. Input: `enrollment_id`, `lesson_id`, `session_id`, `source_revision`, `consent=1`. L'identità deriva esclusivamente dalla sessione. Un rinnovo anticipato, concorrente già superato o riferito a una diversa sorgente restituisce409. Le generazioni del token non sostituiscono sequenze e copertura della stessa sessione.

Il client mette in pausa, salva il buffer, chiede rinnovo e ricrea l'iframe conservando la posizione. Il nuovo player resta in pausa: il messaggio invita a premere play. Questo evita affidarsi a proprietà SDK di sostituzione token non documentate. Non è ancora un rinnovo privo di interruzione. Un fallimento temporaneo mantiene la pausa e ritenta; errori di accesso/conflitto interrompono la sessione. Il caricamento iniziale rispetta il consenso; revoca durante l'inizializzazione impedisce la creazione successiva dell'iframe.

Il salvataggio della copertura non invoca API Cloudflare. Stato della riproduzione, budget temporale, progressi e sessione restano nel componente. La scadenza del token impedisce nuovo credito con quella sessione fino al rinnovo; storico e progressi non vengono cancellati. Le prove browser rimangono dichiarazioni del client, non attestazioni di attenzione.

## Errori, capacità e collaudo

Messaggi distinti per configurazione/segreto assente, credenziale rifiutata, video mancante, non pronto, pubblico, origine negata, rate limit e indisponibilità. Nessuna risposta remota, Authorization o URL con token viene inserita nei messaggi/log applicativi. Le eccezioni di trasporto sono sostituite da errori controllati. Eventuale logging HTTP aggiuntivo del sito/hosting va configurato separatamente per non raccogliere credenziali.

Capacità dichiarate: posizione, durata, seek, playback a scadenza. Download, sottotitoli/trascrizioni gestiti dal componente, interfaccia velocità e firma locale non sono dichiarati completati. L'adapter usa lo SDK ufficiale Stream Player; nessuna modifica globale alla CSP, nessun autoplay iniziale. Autorizzare nel sito i domini SDK e il dominio playback richiesti.

Test automatizzati con risposte API e SDK simulati: privacy/origini/account, limiti token, revoca durante risposta remota, rinnovo, expiry, conservazione progressi e assenza di lock di scrittura durante la rete. Nessuna chiave reale o chiamata Cloudflare viene utilizzata in CI. Queste prove non sostituiscono un collaudo con account/video reali, credenziali ruotate, link pubblico effettivamente negato, browser mobile/desktop e CSP/CMP.

La modalità API fa due richieste per rilascio/rinnovo e una lettura per lezione alla prima pubblicazione. Nessuna cache dei metadati viene usata in questo incremento; il parametro storico `metadata_cache_ttl` non cambia tale comportamento. Carichi elevati richiedono verifica dei limiti Cloudflare e collaudo: la firma locale e una cache verificata rimangono attività successive. Non è stato eseguito il benchmark300/500 studenti né certificata la patch Joomla corrente.

Fonti primarie: [protezione Stream](https://developers.cloudflare.com/stream/viewing-videos/securing-your-stream/), [dettagli video](https://developers.cloudflare.com/api/resources/stream/methods/get/), [token API](https://developers.cloudflare.com/api/resources/stream/subresources/token/methods/create/), [Stream Player API](https://developers.cloudflare.com/stream/viewing-videos/using-the-stream-player/using-the-player-api/).
