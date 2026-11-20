# JD Learn — Cloudflare Stream (plugin base)

Plugin `plg_jlmsvideo_cloudflarestream`, gruppo `jlmsvideo`, contratto provider v1, compreso nel package. Il manifest nello ZIP viene versionato dal builder.

## Configurazione e sorgente
Il manifest definisce `playback_enabled` (default 0), `site_host`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `account_id`, `playback_domain`, `api_token_ref`, `token_ttl_seconds` (60–3600, default 900) e `renew_before_seconds` (minimo 1, default 120, inferiore al TTL). Il riferimento segreto resta sul server: non inserire un token nelle lezioni. Usare account ID esadecimale valido, dominio customer Cloudflare esatto e origine del sito consentita; niente wildcard.

## Capacità e limiti
Il servizio recupera metadati via API server autorizzata, richiede video pronto, `requireSignedURLs=true`, URL HLS sul dominio previsto e `allowedOrigins` comprendente il sito. Playback con token temporaneo emesso via API e rinnovo autorizzato; **firma locale non implementata**. Durata/posizione/seek dipendono dall'adapter configurato; non promettere velocità variabile. Caption: preferenza lingua di avvio, ma nessun selettore delle tracce interrogabili in JD Learn. Nessun download o trascrizione automatica. Il core, non il provider, determina completamento e accesso.

## Errori, manutenzione e collaudo
Risposte credenziali negate, risorsa mancante, provider occupato/non pronto, origine rifiutata e configurazione invalida sono errori distinti; token e corpi remoti non devono comparire nei log. Disabilitare senza cancellare i progressi. Test Joomla con account reale: protezione signed URL, rotazione credenziali, TTL/rinnovo, origini, CSP/CMP, revoca, riapertura, ruoli e fallimenti API. La suite con transport simulato non sostituisce tale verifica.

Riferimenti: `docs/CLOUDFLARE_STREAM.md`, `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/cloudflarestream/src/Provider/Provider.php`, `plugins/jlmsvideo/cloudflarestream/cloudflarestream.xml`.
