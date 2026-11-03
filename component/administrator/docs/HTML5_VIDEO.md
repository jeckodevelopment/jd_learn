# Video HTML5 locali — incremento 0.8

Il provider `plg_jlmsvideo_html5` è incluso nel pacchetto base. Supporta MP4/H.264 a8bit yuv420p con una traccia video e, facoltativamente, una traccia audio AAC. Non effettua transcodifica. Altri container/codec, playlist, video remoti, sottotitoli/trascrizioni gestiti dal componente e CDN/object storage restano da implementare. Questa versione è di sviluppo, non una certificazione per la produzione.

## Configurazione

1. Installare il pacchetto completo; abilitare il plugin HTML5 nel gestore Joomla e attivare `playback_enabled`.
2. Sul server Linux predisporre una directory video fuori da tutte le directory pubbliche. Impostare `video_storage_root` nelle opzioni del componente. La directory non può coincidere con il webroot né esserne una sottodirectory; symlink e percorsi relativi non sono accettati. Non pubblicarla come cartella Media Joomla.
3. Installare una versione mantenuta di FFmpeg/ffprobe e configurare il percorso assoluto del vero eseguibile in `video_ffprobe_path`, es. `/usr/bin/ffprobe`. PHP deve consentire `proc_open`. Non sono inclusi eseguibili nel pacchetto. La validazione usa un array di argomenti senza shell, solo protocollo file, riferimenti esterni MOV disabilitati, timeout10secondi e output limitato. Aggiornamenti e limiti OS del processo restano responsabilità del gestore.
4. Configurare `video_max_mib` (1–250MiB), comunque ridotto dai limiti PHP `upload_max_filesize` e `post_max_size`, con margine per il multipart. Questo limite è distinto dai20MiB degli allegati.
5. Scegliere una consegna esplicita: `disabled` (default), `php_bounded` per prove/file fino a64MiB, oppure `x_accel` per Nginx. Nessun fallback automatico e nessun URL pubblico del file.

Le opzioni autorevoli di storage/consegna/upload sono quelle del componente. I campi storici del manifest plugin (`storage_profile_id`, `delivery_mode`, `video_upload_max_bytes`, `preload`, ecc.) non attivano profili alternativi in questo incremento; il player usa `preload=metadata`. La gestione di più profili è da completare. Il primo percorso di verifica/consegna è Linux; altri sistemi operativi non sono stati collaudati.

## Upload e versioni

Creare una lezione video scegliendo HTML5, salvare la bozza e usare il form MP4 accanto alla lezione nel programma. Durata, asset e revisione vengono assegnati dal server; l'autore non può associare un ID asset arbitrario né impostare una durata manuale per il video locale. Sono necessari `core.manage`, `core.edit` e `jlms.materials.manage` per il corso. POST e token nativi Joomla, parent corso/versione/lezione e revisione aggregata sono verificati prima e dopo l'analisi/scansione.

Il server controlla nome, dimensione, MIME, intestazione MP4 e metadati reali ffprobe. Durata ammessa1secondo–8ore, dimensioni16–8192pixel per lato, codec sopra indicati. Non è una validazione integrale di ogni frame né una garanzia della compatibilità con ogni decoder/browser. Il file è copiato con nome casuale immutabile e hash verificato prima del collegamento. Non si eseguono analisi o scansioni mentre sono detenuti lock di authoring.

Una sostituzione in bozza crea un nuovo blob e asset e incrementa la revisione della sorgente, della lezione e della versione. Il vecchio blob non viene sovrascritto. Le modifiche al titolo/testo conservano metadati e sorgente server. Non sono implementati riuso di asset tra lezioni, upload riprendibili/multipart storage, rimozione fisica e cleanup dei blob orfani. Un conflitto dopo lo staging può lasciare un blob privato non referenziato.

La policy antivirus è condivisa con i materiali: `material_scan_required` e `material_clamd_socket`. Se obbligatoria e non disponibile, l'asset resta in quarantena; se infetto viene rifiutato. Senza scanner il risultato è esplicitamente `not_configured`, non `clean`. La pubblicazione richiede asset pronto, metadata coerenti e consegna disponibile. La ripetizione della scansione è permessa anche dopo pubblicazione: cambia solo lo stato di sicurezza dell'asset, senza riscrivere la sorgente, il programma congelato o lo storico didattico. Un asset non pronto blocca nuovi accessi/progressi.

## Consegna Nginx

Configurare una location interna sul server, ad esempio:

```nginx
location /jdlearn-private/ {
    internal;
    alias /srv/jdlearn-private-videos/;
    types { }
    default_type video/mp4;
    add_header Cache-Control "private, no-store" always;
}
```

La `alias` deve corrispondere esattamente allo storage, con slash finale. Il worker deve avere accesso in lettura senza rendere i file leggibili pubblicamente: directory/gruppo configurati dal gestore; i nuovi blob hanno permessi0640. PHP deve poter creare file. Non viene cambiata la configurazione del web server dall'estensione.

Impostare `video_internal_prefix=/jdlearn-private/`, `video_delivery_mode=x_accel` e confermare `video_internal_confirmed` **solo dopo avere verificato** che una richiesta HTTP diretta a quella location restituisca404 e che le richieste autorizzate funzionino. Il componente non può certificare da solo la configurazione dell'hosting: la conferma è una dichiarazione del gestore. Un server che ignora `X-Accel-Redirect` non riceve un fallback PHP automatico. Proxy/FastCGI cache e altri cache layer devono rispettare `private, no-store` e non memorizzare i risultati autorizzati per altri utenti.

Il controller autentica e autorizza, chiude la sessione Joomla ed emette un redirect **interno** a Nginx. Il web server gestisce trasferimento, Range e HEAD senza occupare un worker PHP per tutta la riproduzione. La CI verifica questo percorso con Nginx reale, includendo il rifiuto della location interna diretta. Apache X-Sendfile, CDN e object storage non sono ancora implementati.

## Accesso, Range e progresso

Il descrittore viene richiesto all’apertura della lezione autorizzata e contiene soltanto un URL del controller same-origin. Non espone percorso fisico o storage key; la lezione pubblica e il GET iniziale dell'aula non contengono l'URL media. I video locali non contattano provider esterni e funzionano anche con `video_external_policy=blocked`; restano necessari login/entitlement Joomla e caricamento esplicito.

Endpoint: `GET/HEAD index.php?option=com_jlms&task=media.stream&format=raw&enrollment_id=...&lesson_id=...&source_revision=...`. L'identità deriva dalla sessione, non da ID utente o token URL. A ogni richiesta vengono verificati account, livelli/gruppi correnti, ownership, corso/versione/edizione, finestre, parent, plugin attivo, revisione, metadata/hash registrato, stato dell'asset e configurazione della consegna. Nessun credito viene attribuito dalla consegna del file.

In `php_bounded` il trasferimento usa chunk limitati, single Range (anche suffix/open-ended), HEAD, ETag, Content-Range e416 per range invalidi/multipli. Header `video/mp4`, inline, nosniff, private/no-store. La sessione viene chiusa prima del corpo. È un percorso esplicito per prove e file piccoli; non è il percorso validato per centinaia di studenti. Nginx gestisce i Range nella modalità offload.

L'hash completo viene verificato allo staging e dalla scansione quando configurata. Ogni richiesta media controlla stato, formato del key, tipo file regolare e dimensione; non ricalcola l'hash dell'intero filmato per ogni Range. Il processo e lo storage devono impedire modifiche fuori dal flusso applicativo. Un controllo periodico di integrità e storage write-once sono attività successive. La revoca impedisce nuove richieste, ma non può ritirare dati già trasferiti o presenti nel buffer. Il media è accessibile a uno studente autorizzato e non è DRM; `controlsList=nodownload` è soltanto un'indicazione UI.

Il player normalizza eventi HTMLMediaElement nel protocollo video comune. Copertura unica, soglia90%, budget temporale server, replay, conflitti tra schede e contatori rimangono nel componente. Il completamento manuale non può completare una lezione video. Arresto/abbandono rimuovono src, listener e istanza; il salvataggio finale resta best effort.

## Collaudo e limiti

La pipeline usa MP4 generati localmente, ffprobe reale, login/upload/range HTTP, quarantena/disponibilità e Nginx reale. I test JavaScript usano un doppio dell'elemento video: non provano decoder, controlli nativi e buffering nei browser reali. Test desktop/mobile, accessibilità completa, scanner antivirus reale, audit dipendenze, patch Joomla corrente e carico300/500studenti rimangono da eseguire. Non sono inclusi file di utenti o video reali nel repository.

Fonti primarie: [ffprobe](https://ffmpeg.org/ffprobe-all.html), [protocolli FFmpeg](https://ffmpeg.org/ffmpeg-protocols.html), [Nginx internal](https://nginx.org/en/docs/http/ngx_http_core_module.html#internal), [Nginx X-Accel-Redirect](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html).
