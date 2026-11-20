# JD Learn — Vimeo (plugin base)

Plugin `plg_jlmsvideo_vimeo` nel gruppo `jlmsvideo`, contratto provider v1. Incluso nel package base. Il builder uniforma la versione dei manifest nello ZIP: non usare le versioni storiche di questo README come versione del rilascio.

## Configurazione e sorgente
Abilitare plugin e `playback_enabled` (default 0). Campi manifest: `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`. Usare riferimenti Vimeo validati e il player SDK ufficiale, senza iframe forniti dall'autore. La policy del componente governa consenso `blocked/embed/site_consent`, autorizzazione, grant e progressi. Il player resta senza autoplay automatico.

## Capacità e limiti
Posizione, durata, seek e velocità solo se account/video e SDK li consentono. Sottotitoli: elenco e selezione delle tracce quando disponibili tramite SDK; la preferenza è locale al browser. L'acquisizione della durata editoriale usa l'SDK, non un fetchMetadata server del plugin. La trascrizione JD Learn è editoriale, non generata da Vimeo. Nessun download e nessuna certificazione server della visione o della disponibilità dei video privati: l'evento `ended` non attribuisce completamento.

## Errori, manutenzione e collaudo
Verificare privacy del video, dominio autorizzato per embed, SDK, CSP, consenso, stato di registrazione plugin e disponibilità metadati. Una riproduzione in Vimeo non dimostra che l'embed nel sito sia consentito. Disabilitazione e aggiornamento non devono cancellare riferimenti didattici. Testare in Joomla: autorizzazione, avvio manuale, ripresa, replay a lezione completata, sottotitoli se presenti, revoke CMP, blocchi e utenti non autorizzati. Collaudo account/SDK/browser reale ancora richiesto.

Riferimenti: `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/vimeo/vimeo.xml`, `libraries/jlms/src/Video/EmbedProvider.php`.
