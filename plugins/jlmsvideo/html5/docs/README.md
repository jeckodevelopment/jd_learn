# JD Learn — HTML5 privato (plugin base)

Plugin `plg_jlmsvideo_html5`, gruppo `jlmsvideo`, contratto provider v1, incluso nel pacchetto. La versione dei manifest ZIP viene normalizzata dal builder.

## Configurazione e sorgente
Abilitare il plugin e `playback_enabled` (default 0); predisporre FFprobe e lo storage protetto del componente. Campi manifest: `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `storage_profile_id`, `delivery_mode` (`webserver`, `storage`, `php_bounded`), `video_upload_max_bytes`, `preload` (`none` o `metadata`). Le modalità dichiarate nel form non garantiscono che ogni infrastruttura sia pronta: verificare configurazione e delivery attiva. La sorgente della lezione non è un URL arbitrario pubblico.

## Capacità e limiti
Video locale MP4/H.264, AAC facoltativo, durata verificata sul server, player HTML5, avanzamento/seek/velocità quando supportati dal browser; TextTrack native quando presenti. Per gli stream protetti il descriptor usa `media.stream` con grant dell'iscrizione o anteprima editoriale autorizzata. GET/HEAD/Range e storage richiedono i controlli previsti dal componente. Nessuna garanzia di sottotitoli generati, codec alternativi o download indiscriminato. L'evento di fine player non equivale a completamento.

## Errori, manutenzione e collaudo
Verificare ffprobe, dimensione/MIME del file, protezione fuori dalla radice pubblica, limitazioni del PHP streaming, upload interrotto, autorizzazione dell'anteprima, revoca accesso, range/HEAD, replay e compatibilità decoder. Disabilitazione e upgrade non devono cancellare lezioni, storage o risultati. Eseguire prove reali su Apache/LiteSpeed con account distinti; non dedurre il collaudo infrastrutturale dalla CI.

Riferimenti: `docs/HTML5_VIDEO.md`, `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/html5/src/Provider/Provider.php`, `plugins/jlmsvideo/html5/html5.xml`.

## Identità, dipendenze e contratto di installazione

- Identità Joomla: `plg_jlmsvideo_html5`, gruppo `jlmsvideo`, namespace e dipendenze come da manifest `plugins/jlmsvideo/html5/html5.xml`. Il builder della distribuzione normalizza la versione del manifest: verificare la versione reale nello ZIP installabile, non assumere quella del sorgente.
- Prerequisiti: Joomla supportato dal pacchetto JD Learn, componente e libreria JD Learn, plugin installato e abilitato, policy di riproduzione consentita e autorizzazione effettiva della singola lezione. I servizi remoti richiedono rete, configurazione privacy/CSP e diritto di incorporare la risorsa.
- Dati di lezione: riferimento della sorgente normalizzato dal componente, durata/snapshot editoriale e policy dell'iscrizione. Non utilizzare HTML arbitrario o URL forniti dall'utente come sostituti del controllo backend.

## Parametri del manifest, tipi e valori iniziali

La tabella descrive i valori **dichiarati nell'XML** (non garantisce che ogni combinazione sia valida in runtime); «Non dichiarato» significa assenza di default esplicito, non valore vuoto sicuro. Un campo senza `required` nel manifest non diventa automaticamente sufficiente a far funzionare il provider.

| Campo | Tipo Joomla | Default XML | Vincoli dichiarati |
|---|---|---|---|
| `playback_enabled` | `radio` | `0` | filter integer |
| `language_mode` | `list` | `user` | Nessun vincolo numerico nel manifest |
| `autoplay` | `radio` | `0` | Nessun vincolo numerico nel manifest |
| `metadata_cache_ttl` | `number` | `3600` | min 0, max 86400, filter integer |
| `remote_timeout` | `number` | `5` | min 1, max 15, filter integer |
| `storage_profile_id` | `text` | Non dichiarato | filter string |
| `delivery_mode` | `list` | `webserver` | Nessun vincolo numerico nel manifest |
| `video_upload_max_bytes` | `number` | Non dichiarato | min 1, filter integer |
| `preload` | `list` | `metadata` | Nessun vincolo numerico nel manifest |

## Privacy, tracciamento e ciclo di vita

L'avvio della riproduzione è distinto dal consenso e dall'accesso: il core decide autorizzazione, tracciamento e soglia didattica, mentre SDK e browser possono non fornire eventi affidabili. Verificare riapertura di lezioni completate e negazione di progressi/URL di utenti estranei. Il plugin non crea automaticamente attestati, non autentica gli utenti terzi e non garantisce disponibilità del servizio esterno.

Per aggiornamento/disattivazione: salvare configurazione e riferimenti didattici, aggiornare il package completo, verificare il manifest installato e ripetere riproduzione, resume, ACL, consenso e codice di errore; la sola disattivazione non cancella progressi. Per rimozione definitiva valutare prima dipendenze da corsi e lezioni: non dedurre assenza di riferimenti dal solo plugin disabilitato. Backup/ripristino e migrazione di configurazione sono collaudi da eseguire sull'installazione reale, non prove già acquisite.

## Prestazioni, errori e matrice di accettazione

Le impostazioni di cache e timeout sono limiti configurativi, non SLA o misure di latenza. Valutare tempo di caricamento, fallback quando SDK/servizio non risponde, concorrenza, navigazione mobile e stato del player con rete intermittente, senza loggare token o URL firmati. Testare con almeno un utente autorizzato, un utente non autorizzato, un amministratore e un browser senza consenso esterno; conservare risultato, build, data e configurazione redatta nel QA Testbook. Nessun esito manuale viene dichiarato da questa guida.
