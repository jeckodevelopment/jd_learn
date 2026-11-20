# JD Learn — HTML5 privato (plugin base)

Plugin `plg_jlmsvideo_html5`, gruppo `jlmsvideo`, contratto provider v1, incluso nel pacchetto. La versione dei manifest ZIP viene normalizzata dal builder.

## Configurazione e sorgente
Abilitare il plugin e `playback_enabled` (default 0); predisporre FFprobe e lo storage protetto del componente. Campi manifest: `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `storage_profile_id`, `delivery_mode` (`webserver`, `storage`, `php_bounded`), `video_upload_max_bytes`, `preload` (`none` o `metadata`). Le modalità dichiarate nel form non garantiscono che ogni infrastruttura sia pronta: verificare configurazione e delivery attiva. La sorgente della lezione non è un URL arbitrario pubblico.

## Capacità e limiti
Video locale MP4/H.264, AAC facoltativo, durata verificata sul server, player HTML5, avanzamento/seek/velocità quando supportati dal browser; TextTrack native quando presenti. Per gli stream protetti il descriptor usa `media.stream` con grant dell'iscrizione o anteprima editoriale autorizzata. GET/HEAD/Range e storage richiedono i controlli previsti dal componente. Nessuna garanzia di sottotitoli generati, codec alternativi o download indiscriminato. L'evento di fine player non equivale a completamento.

## Errori, manutenzione e collaudo
Verificare ffprobe, dimensione/MIME del file, protezione fuori dalla radice pubblica, limitazioni del PHP streaming, upload interrotto, autorizzazione dell'anteprima, revoca accesso, range/HEAD, replay e compatibilità decoder. Disabilitazione e upgrade non devono cancellare lezioni, storage o risultati. Eseguire prove reali su Apache/LiteSpeed con account distinti; non dedurre il collaudo infrastrutturale dalla CI.

Riferimenti: `docs/HTML5_VIDEO.md`, `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/html5/src/Provider/Provider.php`, `plugins/jlmsvideo/html5/html5.xml`.
