# Durata video: acquisizione automatica e tolleranza

Richiesta del 7 novembre 2026, priorità gestione corsi/provider R-06. **Implementazione 0.39 verificata nella [CI standard](https://github.com/jeckodevelopment/jd_learn/actions/runs/42393451244)**, non accettazione completa dei provider reali. Specifica vigente1.5; gli ID e i criteri dei requisiti restano invariati. Contratto operativo e limiti attuali nella [guida video](VIDEO.md).

## Risultato per l’autore

L’autore sceglie provider e URL/ID oppure carica il file. La durata viene acquisita dai metadati disponibili e mostrata come informazione rilevata, senza dover compilare ordinariamente secondi esatti o confermare manualmente una precisione artificiale. L’inserimento manuale rimane un ripiego esplicito quando il provider non consente la lettura, con provenienza e limiti dichiarati. Salvare una bozza incompleta deve restare possibile; il completamento di attività obbligatorie richiede una regola effettivamente supportata prima della pubblicazione.

| Sorgente | Implementato nella0.39 | Verifica residua |
|---|---|---|
| YouTube | Preview SDK, misura automatica con ricevuta editoriale; nessuna nuova API key obbligatoria | SDK/account/browser reali, metadati tardivi e avvio dell’anteprima; riconoscimento certo dei live non implementato |
| Vimeo | Preview SDK con riferimento/hash normalizzati e misura automatica | Restrizioni, privacy e arrotondamenti nei player reali |
| Cloudflare Stream | Acquisizione da API server prima del salvataggio, prova della provenienza e normale verifica di pubblicazione | Account reale, segreti/permessi, domini e disponibilità |
| MP4 locale | Acquisizione dal file all’upload/verifica già presente, nessun campo manuale obbligatorio | Hosting/browser/decoder reali secondo la matrice |

## Pubblicazione e fruizione

La durata acquisita deve essere associata a provider, riferimento normalizzato e revisione della sorgente. Una risposta tardiva riferita al video precedente non deve sovrascrivere il nuovo. Distinguere metadati verificati via API/file, lettura SDK nel contesto dell’autore e valore dichiarato: una misura nel browser non diventa una certificazione server del contenuto o della presenza dello studente.

Gestire piccoli scarti di arrotondamento/encoding senza bloccare il caricamento o i salvataggi di progresso. La0.39 usa max(2s,min(5s,1% della durata pubblicata)) e richiede di collaudarla nei provider reali, con una durata di riferimento coerente per gli intervalli e la percentuale: rimuovere soltanto il controllo senza correggere il denominatore può lasciare un video impossibile da completare. Non concedere crediti per intervalli saltati, evento fine o metadati mancanti. Durate incongruenti, non finite, live/non supportate o contenuto effettivamente sostituito richiedono una diagnostica distinta; non accettare qualsiasi valore ricevuto dal client.

Nessuna correzione silenziosa delle strutture pubblicate o dei risultati storici. Conservare regole assegnate, sorgente/revisione e completamenti acquisiti; distinguere una lieve differenza del player dalla sostituzione sostanziale del video, che segue il versionamento previsto. Recuperare errori temporanei senza costringere a ricreare la lezione o a indovinare la durata.

## Condizioni di implementazione e collaudo

- Form Joomla e autorizzazione corrente, proprietà corso/versione/lezione, checkout, revisione e token su eventuali comandi di acquisizione/salvataggio; niente scritture da GET.
- Chiamate server soltanto a endpoint ufficiali ammessi e sorgenti normalizzate, con timeout/limiti e fuori dalle transazioni lunghe; nessun recupero generico di URL forniti dall’autore.
- Rispettare politica esterna e consenso anche nelle anteprime amministrative; gestire annullamento, cambio sorgente, timeout e pulizia del player. Nessun autoplay nascosto o nuovo passaggio obbligatorio per lo studente.
- Provare i quattro provider: acquisizione riuscita, metadati tardivi/zero, arrotondamenti, hash Vimeo, accesso revocato, sorgente cambiata durante la risposta, scarti di pochi secondi, incongruenze grandi, fine/seek/ripresa, retry e più sessioni. Verificare soglie e progressi esatti, copie di lavoro, pubblicazione e storici dopo upgrade.
- Controlli locali mirati e PR in bozza; integrazioni standard sul candidato pronto, senza avviare la matrice completa a ogni commit. Registrare separatamente fixture controllate e prove SDK/browser/account reali.

## Fonti tecniche ufficiali consultate

- [YouTube IFrame API: getDuration](https://developers.google.com/youtube/iframe_api_reference#Retrieving_video_information): zero finché i metadati non sono disponibili, normalmente dopo l’avvio; distinguere i live.
- [YouTube Data API: durata](https://developers.google.com/youtube/data/reference/rest/v3/videos#contentDetails.duration): possibili lievi differenze dalla durata effettiva per encoding/transcoding.
- [Vimeo Player SDK: getDuration](https://developer.vimeo.com/player/sdk/reference): precisione diversa prima e dopo l’avvio.
- [Cloudflare Stream: metadati video](https://developers.cloudflare.com/api/resources/stream/methods/get/): durata del video e stato di disponibilità.
