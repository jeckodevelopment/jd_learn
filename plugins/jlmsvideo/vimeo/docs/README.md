# JD Learn — Vimeo (plugin base)

Plugin `plg_jlmsvideo_vimeo` nel gruppo `jlmsvideo`, contratto provider v1. Incluso nel package base. Il builder uniforma la versione dei manifest nello ZIP: non usare le versioni storiche di questo README come versione del rilascio.

## Configurazione e sorgente
Abilitare plugin e `playback_enabled` (default 0). Campi manifest: `playback_enabled`, `language_mode`, `autoplay`, `metadata_cache_ttl`, `remote_timeout`. Usare riferimenti Vimeo validati e il player SDK ufficiale, senza iframe forniti dall'autore. La policy del componente governa consenso `blocked/embed/site_consent`, autorizzazione, grant e progressi. Il player resta senza autoplay automatico.

## Capacità e limiti
Posizione, durata, seek e velocità solo se account/video e SDK li consentono. Sottotitoli: elenco e selezione delle tracce quando disponibili tramite SDK; la preferenza è locale al browser. L'acquisizione della durata editoriale usa l'SDK, non un fetchMetadata server del plugin. La trascrizione JD Learn è editoriale, non generata da Vimeo. Nessun download e nessuna certificazione server della visione o della disponibilità dei video privati: l'evento `ended` non attribuisce completamento.

## Errori, manutenzione e collaudo
Verificare privacy del video, dominio autorizzato per embed, SDK, CSP, consenso, stato di registrazione plugin e disponibilità metadati. Una riproduzione in Vimeo non dimostra che l'embed nel sito sia consentito. Disabilitazione e aggiornamento non devono cancellare riferimenti didattici. Testare in Joomla: autorizzazione, avvio manuale, ripresa, replay a lezione completata, sottotitoli se presenti, revoke CMP, blocchi e utenti non autorizzati. Collaudo account/SDK/browser reale ancora richiesto.

Riferimenti: `docs/VIDEO.md`, `component/administrator/docs/VIDEO_PROVIDERS_SPEC.md`, `plugins/jlmsvideo/vimeo/vimeo.xml`, `libraries/jlms/src/Video/EmbedProvider.php`.

## Identità, dipendenze e contratto di installazione

- Identità Joomla: `plg_jlmsvideo_vimeo`, gruppo `jlmsvideo`, namespace e dipendenze come da manifest `plugins/jlmsvideo/vimeo/vimeo.xml`. Il builder della distribuzione normalizza la versione del manifest: verificare la versione reale nello ZIP installabile, non assumere quella del sorgente.
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

## Privacy, tracciamento e ciclo di vita

L'avvio della riproduzione è distinto dal consenso e dall'accesso: il core decide autorizzazione, tracciamento e soglia didattica, mentre SDK e browser possono non fornire eventi affidabili. Verificare riapertura di lezioni completate e negazione di progressi/URL di utenti estranei. Il plugin non crea automaticamente attestati, non autentica gli utenti terzi e non garantisce disponibilità del servizio esterno.

Per aggiornamento/disattivazione: salvare configurazione e riferimenti didattici, aggiornare il package completo, verificare il manifest installato e ripetere riproduzione, resume, ACL, consenso e codice di errore; la sola disattivazione non cancella progressi. Per rimozione definitiva valutare prima dipendenze da corsi e lezioni: non dedurre assenza di riferimenti dal solo plugin disabilitato. Backup/ripristino e migrazione di configurazione sono collaudi da eseguire sull'installazione reale, non prove già acquisite.

## Prestazioni, errori e matrice di accettazione

Le impostazioni di cache e timeout sono limiti configurativi, non SLA o misure di latenza. Valutare tempo di caricamento, fallback quando SDK/servizio non risponde, concorrenza, navigazione mobile e stato del player con rete intermittente, senza loggare token o URL firmati. Testare con almeno un utente autorizzato, un utente non autorizzato, un amministratore e un browser senza consenso esterno; conservare risultato, build, data e configurazione redatta nel QA Testbook. Nessun esito manuale viene dichiarato da questa guida.
