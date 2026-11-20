# JD Learn — Cloudflare Stream (plugin base)

Plugin `plg_jlmsvideo_cloudflarestream`, gruppo `jlmsvideo`, contratto provider v1, compreso nel package. Il manifest nello ZIP viene versionato dal builder.

## Configurazione e sorgente
Il manifest definisce `playback_enabled` (default 0), `site_host`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `account_id`, `playback_domain`, `api_token_ref`, `token_ttl_seconds` (60–3600, default 900) e `renew_before_seconds` (minimo 1, default 120, inferiore al TTL). Il riferimento segreto resta sul server: non inserire un token nelle lezioni. Usare account ID esadecimale valido, dominio customer Cloudflare esatto e origine del sito consentita; niente wildcard.

## Capacità e limiti
Il servizio recupera metadati via API server autorizzata, richiede video pronto, `requireSignedURLs=true`, URL HLS sul dominio previsto e `allowedOrigins` comprendente il sito. Playback con token temporaneo emesso via API e rinnovo autorizzato; **firma locale non implementata**. Durata/posizione/seek dipendono dall'adapter configurato; non promettere velocità variabile. Caption: preferenza lingua di avvio, ma nessun selettore delle tracce interrogabili in JD Learn. Nessun download o trascrizione automatica. Il core, non il provider, determina completamento e accesso.

## Errori, manutenzione e collaudo
Risposte credenziali negate, risorsa mancante, provider occupato/non pronto, origine rifiutata e configurazione invalida sono errori distinti; token e corpi remoti non devono comparire nei log. Disabilitare senza cancellare i progressi. Test Joomla con account reale: protezione signed URL, rotazione credenziali, TTL/rinnovo, origini, CSP/CMP, revoca, riapertura, ruoli e fallimenti API. La suite con transport simulato non sostituisce tale verifica.

Riferimenti: `docs/CLOUDFLARE_STREAM.md`, `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/cloudflarestream/src/Provider/Provider.php`, `plugins/jlmsvideo/cloudflarestream/cloudflarestream.xml`.

## Identità, dipendenze e contratto di installazione

- Identità Joomla: `plg_jlmsvideo_cloudflarestream`, gruppo `jlmsvideo`, namespace e dipendenze come da manifest `plugins/jlmsvideo/cloudflarestream/cloudflarestream.xml`. Il builder della distribuzione normalizza la versione del manifest: verificare la versione reale nello ZIP installabile, non assumere quella del sorgente.
- Prerequisiti: Joomla supportato dal pacchetto JD Learn, componente e libreria JD Learn, plugin installato e abilitato, policy di riproduzione consentita e autorizzazione effettiva della singola lezione. I servizi remoti richiedono rete, configurazione privacy/CSP e diritto di incorporare la risorsa.
- Dati di lezione: riferimento della sorgente normalizzato dal componente, durata/snapshot editoriale e policy dell'iscrizione. Non utilizzare HTML arbitrario o URL forniti dall'utente come sostituti del controllo backend.

## Parametri del manifest, tipi e valori iniziali

La tabella descrive i valori **dichiarati nell'XML** (non garantisce che ogni combinazione sia valida in runtime); «Non dichiarato» significa assenza di default esplicito, non valore vuoto sicuro. Un campo senza `required` nel manifest non diventa automaticamente sufficiente a far funzionare il provider.

| Campo | Tipo Joomla | Default XML | Vincoli dichiarati |
|---|---|---|---|
| `playback_enabled` | `radio` | `0` | filter integer |
| `site_host` | `text` | Non dichiarato | filter string |
| `language_mode` | `list` | `user` | Nessun vincolo numerico nel manifest |
| `autoplay` | `radio` | `0` | Nessun vincolo numerico nel manifest |
| `metadata_cache_ttl` | `number` | `3600` | min 0, max 86400, filter integer |
| `remote_timeout` | `number` | `5` | min 1, max 15, filter integer |
| `account_id` | `text` | Non dichiarato | filter string |
| `playback_domain` | `text` | Non dichiarato | filter string |
| `api_token_ref` | `text` | Non dichiarato | filter string |
| `token_ttl_seconds` | `number` | `900` | min 60, max 3600, filter integer |
| `renew_before_seconds` | `number` | `120` | min 1, max 3599, filter integer |

## Privacy, tracciamento e ciclo di vita

L'avvio della riproduzione è distinto dal consenso e dall'accesso: il core decide autorizzazione, tracciamento e soglia didattica, mentre SDK e browser possono non fornire eventi affidabili. Verificare riapertura di lezioni completate e negazione di progressi/URL di utenti estranei. Il plugin non crea automaticamente attestati, non autentica gli utenti terzi e non garantisce disponibilità del servizio esterno.

Per aggiornamento/disattivazione: salvare configurazione e riferimenti didattici, aggiornare il package completo, verificare il manifest installato e ripetere riproduzione, resume, ACL, consenso e codice di errore; la sola disattivazione non cancella progressi. Per rimozione definitiva valutare prima dipendenze da corsi e lezioni: non dedurre assenza di riferimenti dal solo plugin disabilitato. Backup/ripristino e migrazione di configurazione sono collaudi da eseguire sull'installazione reale, non prove già acquisite.

## Prestazioni, errori e matrice di accettazione

Le impostazioni di cache e timeout sono limiti configurativi, non SLA o misure di latenza. Valutare tempo di caricamento, fallback quando SDK/servizio non risponde, concorrenza, navigazione mobile e stato del player con rete intermittente, senza loggare token o URL firmati. Testare con almeno un utente autorizzato, un utente non autorizzato, un amministratore e un browser senza consenso esterno; conservare risultato, build, data e configurazione redatta nel QA Testbook. Nessun esito manuale viene dichiarato da questa guida.
