# JD Learn — YouTube (plugin base)

Plugin Joomla `plg_jlmsvideo_youtube` (gruppo `jlmsvideo`, contratto provider v1). Incluso nel package JD Learn; la versione nei manifest del pacchetto è uniformata dal builder. Questa guida descrive il comportamento disponibile nel codice della serie 0.64.1-dev, **non** una prova di accettazione su browser reali.

## Configurazione e sorgente
Abilitare il plugin in Joomla e `playback_enabled` nelle opzioni; il valore iniziale è 0. I campi del manifest sono `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`, `embed_privacy_mode`. La riproduzione JD Learn non avvia automaticamente il video (`autoplay=0` nel player), anche quando l'iframe dichiara il permesso `autoplay`. Usare solo ID/URL YouTube normalizzati: nessun HTML embed arbitrario.

## Capacità e limiti
Adapter IFrame SDK ufficiale; progresso, posizione, durata, seek e velocità quando esposti dal player/video; preferenza lingua caption al caricamento senza elenco tracce interrogabile da JD Learn. Nessuna trascrizione remota garantita, download o attestazione che `ended` equivalga a completamento. La durata rilevata nell'editor proviene dallo SDK con ricevuta editoriale, non da una Data API server. La policy del componente gestisce accesso, consenso esterno, revoca, completamento e audit: il plugin non assegna crediti.

## Errori, manutenzione e collaudo
Controllare opt-in plugin, policy `blocked/embed/site_consent`, bridge CMP, CSP, embed consentito dal proprietario, referrer, SDK e rete; distinguere il codice YouTube 153 dagli altri guasti. Disabilitare il plugin non elimina sorgenti/storico; non considerare l'eventuale URL privato una protezione di accesso. Dopo upgrade verificare cache WAM, avvio manuale, riapertura delle lezioni completate, registrazione progressi, cambio consenso, utenti senza ACL, desktop/mobile e IT/EN. Test automatici non sostituiscono i collaudi Joomla reali.

Riferimenti: `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/youtube/youtube.xml`, `libraries/jlms/src/Video/EmbedProvider.php`.

## Identità, dipendenze e contratto di installazione

- Identità Joomla: `plg_jlmsvideo_youtube`, gruppo `jlmsvideo`, namespace e dipendenze come da manifest `plugins/jlmsvideo/youtube/youtube.xml`. Il builder della distribuzione normalizza la versione del manifest: verificare la versione reale nello ZIP installabile, non assumere quella del sorgente.
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
| `embed_privacy_mode` | `list` | `inherit` | Nessun vincolo numerico nel manifest |

## Privacy, tracciamento e ciclo di vita

L'avvio della riproduzione è distinto dal consenso e dall'accesso: il core decide autorizzazione, tracciamento e soglia didattica, mentre SDK e browser possono non fornire eventi affidabili. Verificare riapertura di lezioni completate e negazione di progressi/URL di utenti estranei. Il plugin non crea automaticamente attestati, non autentica gli utenti terzi e non garantisce disponibilità del servizio esterno.

Per aggiornamento/disattivazione: salvare configurazione e riferimenti didattici, aggiornare il package completo, verificare il manifest installato e ripetere riproduzione, resume, ACL, consenso e codice di errore; la sola disattivazione non cancella progressi. Per rimozione definitiva valutare prima dipendenze da corsi e lezioni: non dedurre assenza di riferimenti dal solo plugin disabilitato. Backup/ripristino e migrazione di configurazione sono collaudi da eseguire sull'installazione reale, non prove già acquisite.

## Prestazioni, errori e matrice di accettazione

Le impostazioni di cache e timeout sono limiti configurativi, non SLA o misure di latenza. Valutare tempo di caricamento, fallback quando SDK/servizio non risponde, concorrenza, navigazione mobile e stato del player con rete intermittente, senza loggare token o URL firmati. Testare con almeno un utente autorizzato, un utente non autorizzato, un amministratore e un browser senza consenso esterno; conservare risultato, build, data e configurazione redatta nel QA Testbook. Nessun esito manuale viene dichiarato da questa guida.
