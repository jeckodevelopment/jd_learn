# JD Learn — YouTube (plugin base)

Plugin Joomla `plg_jlmsvideo_youtube` (gruppo `jlmsvideo`, contratto provider v1). Incluso nel package JD Learn; la versione nei manifest del pacchetto è uniformata dal builder. Questa guida descrive il comportamento disponibile nel codice della serie 0.64.1-dev, **non** una prova di accettazione su browser reali.

## Configurazione e sorgente
Abilitare il plugin in Joomla e `playback_enabled` nelle opzioni; il valore iniziale è 0. I campi del manifest sono `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `embed_privacy_mode`. La riproduzione JD Learn non avvia automaticamente il video (`autoplay=0` nel player), anche quando l'iframe dichiara il permesso `autoplay`. Usare solo ID/URL YouTube normalizzati: nessun HTML embed arbitrario.

## Capacità e limiti
Adapter IFrame SDK ufficiale; progresso, posizione, durata, seek e velocità quando esposti dal player/video; preferenza lingua caption al caricamento senza elenco tracce interrogabile da JD Learn. Nessuna trascrizione remota garantita, download o attestazione che `ended` equivalga a completamento. La durata rilevata nell'editor proviene dallo SDK con ricevuta editoriale, non da una Data API server. La policy del componente gestisce accesso, consenso esterno, revoca, completamento e audit: il plugin non assegna crediti.

## Errori, manutenzione e collaudo
Controllare opt-in plugin, policy `blocked/embed/site_consent`, bridge CMP, CSP, embed consentito dal proprietario, referrer, SDK e rete; distinguere il codice YouTube 153 dagli altri guasti. Disabilitare il plugin non elimina sorgenti/storico; non considerare l'eventuale URL privato una protezione di accesso. Dopo upgrade verificare cache WAM, avvio manuale, riapertura delle lezioni completate, registrazione progressi, cambio consenso, utenti senza ACL, desktop/mobile e IT/EN. Test automatici non sostituiscono i collaudi Joomla reali.

Riferimenti: `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/youtube/youtube.xml`, `libraries/jlms/src/Video/EmbedProvider.php`.
