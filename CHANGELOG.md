## 0.41.9-dev — controlli separati di ammissione ed erogazione

- Aggiunge i comandi motivati per chiudere/aprire nuove ammissioni e sospendere/riprendere l’erogazione, con anteprima di iscritti e quiz aperti, conferma, conflitto su revisioni obsolete e storico transazionale.
- Blocca nuove iscrizioni automatiche/manuali quando le ammissioni sono chiuse; la sospensione nega le azioni didattiche protette senza modificare iscrizioni, risposte o progressi.
- Introduce migrazione 0.41.9.0, comandi dal roster, traduzioni e guida operativa. PR #70 integrata nel merge 70f6ed7; CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run 42428496835); collaudo manuale Joomla ancora aperto.

# Changelog

## 0.41.13-dev — finestre temporali dei materiali

- Aggiunge date iniziali/finali ai materiali extra, inserite nel fuso del corso e conservate in UTC; modifica delle date disponibile soltanto sulla bozza.
- La pubblicazione accetta finestre valide; il preflight le segnala come avviso e l’aula continua a nascondere gli allegati fuori finestra.
- Intervalli invertiti o date ambigue/non valide vengono rifiutati prima di creare o sostituire un asset.


## 0.41.12-dev — preflight materiali non pronti

- Il controllo preliminare del Programma segnala allegati in quarantena, con scansione ClamAV obbligatoria non pulita, marcati come sola anteprima o con finestra di pubblicazione configurata, in linea con la validazione server.
- Il preflight legge soltanto i metadati degli allegati della bozza; aggiunte verifiche di dominio e regressione HTTP con materiale fixture temporaneamente inserito e rimosso.
- Nessuna modifica ai file, ai permessi o ai contenuti pubblicati.


## 0.41.11-dev — preflight durata video

- Il controllo preliminare della bozza segnala come bloccante il video privo di durata tra 1 secondo e 8 ore o senza conferma registrata, coerentemente con il validatore server.
- Il controllo usa solo i metadati già salvati e non interroga provider o carica i corpi delle lezioni.
- Aggiunte verifiche di dominio per durata mancante e valida; documentazione Programma sincronizzata nel pacchetto. PR #78 integrata (merge 64dcf6c); CI standard superata nei due ambienti PHP/database e Chromium (run 42441526162). Collaudo manuale Joomla ancora aperto. Artefatto verificato: SHA-256 interno `3a67a2e92424cf426c593f0cedcee7efb4d470f674095baacc898878568edf1a`.


## 0.41.10-dev — riepilogo preliminare di pubblicazione

- Mostra nella bozza del programma gli errori strutturali bloccanti separati dagli avvisi facoltativi su riepilogo e copertina.
- Mantiene la verifica definitiva in `PublicationService`; il controllo preliminare non carica i corpi delle lezioni né contatta provider.
- Aggiunge test unitari e regressioni HTTP per il passaggio da modulo vuoto a lezione obbligatoria; PR #74 integrata nel merge [c023fc4](https://github.com/jeckodevelopment/jd_learn/commit/c023fc4ed29f3d643414cd5afc83b3ad42615dba); CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run [42439350519](https://github.com/jeckodevelopment/jd_learn/actions/runs/42439350519)). Collaudo manuale Joomla ancora aperto. Pacchetto verificato: SHA-256 `4d95e9627ddd6db338961da374e35721262e95356c817d46940e5b6ddc469ab3`.


## 0.41.8-dev — ingresso amministrativo più chiaro

- Mette in evidenza l’accesso alla gestione corsi, raggruppa gli strumenti secondari e raccoglie lo stato provider in un dettaglio espandibile.
- Aggiunge stile responsive confinato alla dashboard tramite Joomla Web Asset Manager; nessuna modifica ai permessi o al template amministrativo.
- Aggiunge stringhe italiane e inglesi e documenta gli ID/classi per gli override. PR #68 integrata; CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium (run [42423237440](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440)). Collaudo visivo e dei ruoli nel backend Joomla reale ancora da svolgere.

## 0.41.7-dev — campi personalizzati delle lezioni

- Abilita il contesto nativo `com_jlms.lesson` in Joomla Fields e aggiunge i campi dinamici all’editor delle lezioni.
- Salva i valori tramite il modello Fields, controllando permesso `core.edit.value`, livello di accesso e contesto; copia i valori nelle nuove bozze.
- Congela valori e metadati dei campi alla pubblicazione e li mostra nell’aula dal relativo snapshot, filtrando per livello di accesso e facendo escaping dell’output.
- Regressione Joomla aggiunta per salvataggio, snapshot, impatto della pubblicazione e resa nell’aula. PR #65 integrata; CI standard superata nei due ambienti PHP/database e Chromium ([run 42420998171](https://github.com/jeckodevelopment/jd_learn/actions/runs/42420998171)). Il collaudo manuale Joomla resta da eseguire.

## 0.41.6-dev — continuità dell’aula dopo il ritiro dal catalogo

- L’accesso degli iscritti autorizzati non dipende più dallo stato editoriale, dal livello di catalogo o dalla scadenza della scheda del corso. Restano attivi i controlli correnti su account, livello contenuto, categoria, lingua, versione, edizione e finestre dell’iscrizione.
- Le nuove ammissioni restano soggette alla pubblicazione e disponibilità del corso.
- Aggiunte regressioni d’integrazione per aula testo/video. PR #64 integrata nel commit [303f781](https://github.com/jeckodevelopment/jd_learn/commit/303f78124ef94a6968a0f0997770bb80e3d28800); CI standard finale superata in PHP/database e Chromium ([run 42414492267](https://github.com/jeckodevelopment/jd_learn/actions/runs/42414492267)). Collaudo manuale Joomla reale ancora aperto.
- R-03 è parziale: mancano le azioni amministrative separate di chiusura iscrizioni e sospensione erogazione, con riepilogo, motivo e audit.

## 0.41.5-dev — ripresa alla prima attività obbligatoria incompleta

- Se non esiste un’ultima attività valida, Riprendi sceglie la prima lezione obbligatoria non completata nell’ordine del programma.
- Se tutte le attività obbligatorie sono completate, Riprendi apre la prima lezione per consentire la revisione.
- Mantiene controlli di accesso, versione e disponibilità; lettura senza modificare progressi o iscrizione.
- Test Joomla aggiornato per attività obbligatorie già completate, fallback alla successiva incompleta e ultima attività ancora valida. PR #62 integrata; quattro job standard superati ([run 42412135232](https://github.com/jeckodevelopment/jd_learn/actions/runs/42412135232)). Pacchetto verificato: [0.41.5-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42412135232/artifacts/13590285852), SHA-256 `baaf4447da1f9559472ea889fdc34c224259501d061a84e797d10b8709bbe70c`.

## 0.41.4-dev — editor corso più lineare

- Riunisce dati, copertina e docenti nella scheda Corso; colloca regole nel contesto Programma e consolida accesso e calendario.
- Lascia invariati modello dati, salvataggio, versioni e permessi.
- PR #60 integrata in main. Quattro job standard superati: controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium ([run 42410200676](https://github.com/jeckodevelopment/jd_learn/actions/runs/42410200676)). Pacchetto: [artifact 13589411586](https://github.com/jeckodevelopment/jd_learn/actions/runs/42410200676/artifacts/13589411586).

## 0.41.3-dev — valore corretto per il checkbox della risposta

- Rimuove `default="0"` dal campo checkbox Joomla: il core usa quel default anche come attributo `value`, così una casella selezionata inviava `0` invece di `1`.
- Aggiorna il test HTTP affinché legga nomi e valori dei checkbox dal markup nativo Joomla e simuli la selezione reale dell’autore.
- La causa è individuata; la correzione resta da verificare sul sito dell’utente dopo installazione.


## 0.41.2-dev — diagnosi degli errori della banca domande

- Mostra i messaggi di validazione nativi Joomla quando il form non supera la validazione, così da indicare il campo rifiutato.
- Distingue gli errori semantici di testo, opzioni, etichette e risposte corrette.
- La segnalazione reale persiste dopo0.41.1; questo incremento è diagnostico e non è dichiarato risolutivo finché non viene riprovato nell’installazione che ha segnalato il problema.


## 0.41.1-dev — salvataggio delle opzioni domanda

- Normalizza i checkbox non selezionati della banca domande sia quando il browser li omette sia quando Joomla li filtra come stringa vuota.
- Aggiunge regressioni di dominio e invio HTTP del form nativo che riproducono il checkbox non selezionato.
- Incremento mirato alla segnalazione di salvataggio 422; non dichiara l’intero authoring quiz accettato.


## 0.41.0-dev — campi nativi nella scheda pubblica del corso

- Resa dei campi editoriali correnti con com_fields, plugin di tipo e layout/override nativi; posizioni dopo titolo, prima e dopo la presentazione. Nascosti/manuali ed elementi vuoti non producono una sezione vuota.
- Accesso corrente a corso, campo e gruppo, pubblicazione, categoria e lingua; aggiornamenti dei valori senza ripubblicare il programma e senza mutare versioni assegnate o progressi.
- Prove native/HTTP per tipi testo/area/lista, impostazioni di visualizzazione, revoche nella stessa sessione e override, più conservazione dopo upgrade. Snapshot didattici, campi lezione e ripristino completo dei valori tramite Content History restano aperti.

## 0.40.1-dev — gestione nativa dei plugin video

- Sostituita la voce fuorviante Stato provider video con Plugin video: apre com_plugins filtrato su jlmsvideo, azzerando i filtri precedenti e la paginazione.
- Visibilità e gestione restano soggette ai permessi nativi Joomla per com_plugins. La vecchia dashboard non è più destinazione del menu e non rappresenta un controllo di riproduzione.
- Prove aggiornate di navigazione HTTP, filtri persistiti, quattro plugin e conservazione del menu dopo upgrade.

## 0.40.0-dev — accesso diretto ai corsi e menu amministrativo

- JD Learn apre direttamente l’elenco corsi. Menu nativo Joomla con Corsi, Categorie corsi, Banca domande, Docenti e Stato provider video, installato/aggiornato dal manifest.
- La pagina precedente resta accessibile esplicitamente per la diagnostica; nessun menu globale per pagine che richiedono il contesto corso.
- Controlli di installazione/upgrade del menu e navigazione HTTP autenticata. Restyling completo, collaudo ruoli/browser e campi/snapshot restano aperti.

## 0.39.0-dev — durata automatica dei video

- Metadati nell’editor con preview SDK YouTube/Vimeo, API server Stream e durata MP4 da file invariata. Automatico predefinito, ripiego manuale distinto, nessuna casella di conferma ordinaria.
- Ricevute di sessione per autore/sorgente/revisioni, scadenza e consumo; scope, ACL, checkout e CSRF. Richieste remote fuori dalle transazioni, bozze incomplete salvabili e durata preservata sulle modifiche della stessa sorgente.
- Tolleranza player/server max(2s,min(5s,1%)), durata osservata per intervalli e denominatore, copertura/idempotenza e dati congelati preservati. Grandi incongruenze restano bloccate. Nessun progresso generato dall’anteprima amministrativa.
- Vimeo0.6.1: un errore temporaneo di caricamento SDK non compromette i successivi tentativi di anteprima/player. Import locali dell’authoring con versione cache coerente al componente.
- Controlli su metadati/provenienza, sorgente cambiata/risposta tardiva, consenso e completamento con scarti; collaudo reale dei quattro provider ancora distinto dalle fixture automatiche.

## Requisiti — 7 novembre 2026: durata video

- Registrata come prossima priorità R-06 l’acquisizione automatica della durata dai provider/file, con inserimento manuale di ripiego e gestione coerente degli scarti player/server.
- Aggiornati piano, limiti attuali, checklist e tracciabilità; runtime/pacchetto0.38 invariati. La funzione esterna completa è ancora da implementare.

## 0.38.0-dev — regole didattiche e soglia video della versione

- Scheda Regole didattiche nel corso con salvataggio prima dell’apertura della bozza; editor contestuale nel Programma, lettura delle versioni pubblicate e riuso dell’avviso modifiche non salvate.
- Soglia video configurabile da0,01% a100%, massimo due decimali, default90%; conversione/confronto esatti e validazione anche prima del congelamento. Navigazione libera e tutte le attività obbligatorie restano le regole operative.
- Salvataggio POST/token con ACL correnti, checkout e CAS. Riepilogo di pubblicazione con soglia precedente/nuova e invalidazione se le regole cambiano; storico e iscritti conservano la versione assegnata.
- Prove di completamento a soglie diverse per iscrizioni vecchie/nuove, form nativi e conservazione dopo upgrade. Regole avanzate, attestati e override per lezione restano aperti.

## Processo e roadmap — 7 novembre 2026

- PR in bozza con controlli leggeri; integrazioni per il candidato pronto, due coppie PHP/database e Chromium. Matrice completa disponibile manualmente prima di milestone/rilascio o interventi di compatibilità; nessuna rimozione di scenari dalla suite.
- Restyling del backend/ingresso e menu amministrativo JD Learn nella sidebar nativa pianificati in M01/M10, con verifica ACL, installazione/upgrade e navigazione accessibile. Non ancora implementati.
- Versione applicativa e pacchetto0.37 invariati.

## 0.37.0-dev — recupero del programma storico come nuova bozza

- Comando nativo dal programma storico con conferma POST/CSRF, ACL fresche, checkout, revisioni e audit atomico. Rigenera gli ID mantenendo chiavi logiche, lezioni, riferimenti video, quiz e materiali.
- Nessuna sovrascrittura delle altre bozze, dei dati promozionali correnti o dei dati studente; la versione attiva cambia soltanto con la pubblicazione ordinaria e il riepilogo di impatto. Modifica programma riapre la bozza più recente.
- Prove di recupero e ripubblicazione con YouTube, Vimeo e Cloudflare Stream; trasporto Stream controllato, senza credenziali o rete reali. Il collaudo SDK/browser reale rimane distinto.

## 0.36.0-dev — riepilogo e conferma degli aggiornamenti didattici

- Riepilogo separato prima di pubblicare una bozza che aggiorna il programma attivo: conteggi e dettagli di moduli, lezioni, quiz e materiali aggiunti/modificati/rimossi, regole e assegnazioni esistenti per versione.
- Confronto per chiavi logiche e riferimenti immutabili, senza falsi cambi per gli ID rigenerati dalla copia di lavoro; corpi e descrizioni confrontati tramite hash senza esporli nel riepilogo.
- Conferma esplicita POST/CSRF con ticket di sessione, validità15minuti e consumo singolo; impronta ricontrollata nella transazione di pubblicazione, con ACL fresche e rifiuto delle modifiche concorrenti. Nessuna migrazione o modifica dei dati studente.
- Prima pubblicazione e ritiro/ripubblicazione della stessa versione conservano il percorso precedente. Tutti i controlli ordinari di pubblicazione restano applicati dopo la conferma.

## 0.35.0-dev — campi personalizzati nativi nell’editor corso

- Scheda Campi personalizzati presente quando Joomla aggiunge campi al form del corso; resa di campi e gruppi tramite il core, con disabilitazione sul corso bloccato da un altro operatore.
- Salvataggio di com_fields tramite gli eventi del plugin Fields nativo, conservando i valori per i client che omettono il gruppo e rifiutando revisioni obsolete prima delle scritture.
- Prove native/HTTP del percorso di creazione, modifica, errore e annullamento. Snapshot didattici e campi lezione rimangono residui M10.
- CI completa conservata sulle PR e avvio manuale disponibile; rimosso il secondo avvio automatico al merge in main per evitare esecuzioni duplicate.

## Documentazione — 5 novembre 2026

- Consolidato lo stato attuale per funzionalità; separati piano residuo, perimetro delle fasi e cronologia.
- Allineati matrice, problemi aperti, prove del build e guide alla 0.34.1; distinte funzioni assenti e collaudi pendenti.
- Nessuna modifica del runtime, della versione o dei criteri di accettazione.

## 0.34.1-dev — protezione delle modifiche nel programma

- Avviso nativo del browser quando si abbandona il modulo o la lezione con modifiche non salvate, incluse modifiche TinyMCE non sincronizzate nella textarea.
- Salvataggio valido consentito; validazione annullata e ritorno dalla cache del browser non disattivano la protezione. Nessun contenuto memorizzato nel browser.
- Test JS dedicati; collaudo completo M01 ancora aperto.

## 0.34.0-dev — programma didattico contestuale

- Scaletta leggibile di moduli espandibili e lezioni cliccabili; aggiunta nel modulo, editor singolo, materiali e quiz nel contesto selezionato.
- Duplicazione, eliminazione con conferma e spostamento della lezione raccolti nelle azioni secondarie. Input recuperato dopo errori di validazione.
- Riordino tramite trascinamento con permutazione atomica/CAS e ripristino visivo su errore; Su/Giù da tastiera e form nativo senza JS. Nessuna modifica dello storico o dei risultati.
- Specifica1.5/AC-127 e guide aggiornate; M01 resta parziale.

## 0.33.0-dev — gestione corsi nativa e più diretta

- Elenco corsi secondo le convenzioni della gestione articoli Joomla: Search Tools, filtri, sort, paginazione e selezione con toolbar; metadati leggibili e accesso al programma dalla riga o dalla selezione.
- Editor compatto con contenuto e impostazioni principali affiancati; schede Programma, Docenti, Immagine, Pubblicazione e Opzioni. Rimossi gli avvisi ripetuti dall’elenco e dall’editor; dati e ID di compatibilità conservati.
- Salva e apri programma riusa la validazione e il salvataggio Joomla, conservando modifiche e storico. Lo stato rimanda al workflow autorizzato, senza toggle che aggirino la pubblicazione.
- Specifica1.4 e AC-126 recepiscono la preferenza esplicita dell’utente per la gestione amministrativa simile agli articoli.

## 0.32.0-dev — duplicazioni autonome

- Comandi per duplicare un corso come nuova bozza e un modulo o una lezione nel programma di lavoro, con titolo e destinazione espliciti.
- Copia di quiz, revisioni domanda, materiali e campi del corso senza duplicare blob, iscrizioni, progressi, tentativi o attestati. Nuovi ID e chiavi logiche; ACL native ereditate dalla categoria.
- Prenotazione dell’asset Joomla prima della transazione; rollback delle scritture e rimozione dell’asset inutilizzato se la copia fallisce. Controlli su identità corrente, sorgente, destinazione, checkout e revisioni.

## Requisiti funzionali1.3 —4novembre2026

- Recepita esplicitamente la richiesta di aggiornamento dei corsi pubblicati con storico Joomla e gestione semplificata dei docenti, campi nativi e viste di menu.
- Aggiornate specifica funzionale/tecnica, matrice e catalogo con AC-119–125, registro della modifica e criteri residui M01; nessun requisito dichiarato completo per effetto dell’aggiornamento documentale.
- Pacchetto runtime invariato0.31.0-dev; verifiche della PR38 registrate separatamente dai nuovi criteri di accettazione.

## 0.31.0-dev — revisione del flusso editoriale

- Corsi pubblicati aggiornabili dal normale editor, con Content History di Joomla e ripristino editoriale protetto.
- Modifica del programma tramite copia completa della versione corrente, incluse lezioni, quiz e materiali. Pubblicazione degli aggiornamenti con conservazione delle assegnazioni didattiche.
- Profili docente senza selettore di categoria, campi Joomla, pagina e lista corsi configurabili da menu, modifica del proprio profilo dal frontend.

## 0.30.0-dev — regole Joomla per corso

- Campo Rules nativo nei corsi salvati per utenti autorizzati con core.admin sul relativo asset. Le modifiche vengono salvate dal controller com_config di Joomla.
- I salvataggi editoriali conservano le regole; grant, deny, ereditarietà, CSRF e scope verificati sul percorso HTTP nativo.

## 0.29.0-dev — descrizione richiesta alla pubblicazione

- Applica il requisito della descrizione estesa alla prima pubblicazione. Rifiuta testo assente o composto da soli spazi e markup vuoto, indicando il campo da completare.
- Le bozze esistenti richiedono il completamento della descrizione. Le versioni già pubblicate e i ritiri/ripubblicazioni conservano i dati congelati.

## 0.28.0-dev — anteprima editoriale

- Il Programma apre l’anteprima della versione selezionata. L’editor può consultare moduli e testo delle lezioni senza iscrizioni, riproduzione video, tentativi o progressi.

## 0.27.2-dev — inizializzazione iframe YouTube

- Crea l’iframe con URL e origine espliciti e attende la navigazione prima di collegare l’SDK. Il referrer dell’iframe contiene solo l’origine del sito.

## 0.27.1-dev — caricamento SDK YouTube

- Ripulisce il caricamento SDK fallito, consente un nuovo tentativo e distingue blocchi CSP, script indisponibile, video non incorporabile e identificazione del sito mancante.

## 0.27.0-dev — player integrato nell’aula

- Il player appare all’apertura della lezione secondo la politica configurata dal gestore. Rimangono i controlli di riproduzione del player.
- La precedente modalità esplicita richiede una scelta nelle opzioni. Sessione, modulo e provider hanno errori distinti.

## 0.26.3-dev — avvio player YouTube

- Attende i metadati di YouTube prima di valutare la durata e registrare progressi. Mostra un messaggio distinto se il server rifiuta l’accesso al player.

## 0.26.2-dev — diagnostica video nel programma

- Stato di configurazione dei quattro provider nel form lezione e accesso alle impostazioni native per operatori autorizzati.
- Messaggi specifici per sorgenti non valide, provider non registrati e rifiuti del servizio Cloudflare Stream.

## 0.26.1-dev — pubblicazione delegata

- Il workflow verifica il permesso di cambio stato sul singolo corso, con ereditarietà dalla categoria. Prova di prima pubblicazione HTTP con un operatore senza permesso globale.

## 0.26.0-dev — validazione editoriale M01/c2

- Limite di 500 caratteri per descrizioni brevi nuove o modificate, senza troncamento dei testi preesistenti.
- Alias duplicati rilevati dopo normalizzazione, con messaggio specifico per categoria e lingua.
- Prove native e HTTP per i limiti, la conservazione dei dati e i messaggi del modulo.

## 0.25.0-dev — permessi editor M01/c1

- Elenco corsi con scope ACL nativo per-record prima di paginazione/totali, cache coerente dopo revoca e nessun filtro basato sul solo autore.
- Nuovo corso consentito anche ai delegati categoriali; selettore Category esteso con scope e conservazione della categoria reale, cestino rifiutato.
- Permessi effettivi, opzioni native autorizzate, checkout altrui in sola lettura ed errori copertina specifici; modalità avvio documentate.
- Nuove prove native/HTTP/reinstall, guide e matrice aggiornate; esiti nella PR. Schema41tabelle/58FK invariato; M01 e integrazioni core ancora parziali.

## 0.24.0-dev — calendario M01/b3

- Campi calendario/fuso nativi: pubblicazione e fruizione, UTC, intervalli e DST senza normalizzazione silenziosa.
- Snapshot delle ultime date alla pubblicazione e assegnazione della finestra alla prima edizione; date pubblicate immutabili, vecchi client/snapshot conservati.
- Scheda con fuso/offset e disponibilità corrente del comando di avvio; politica dei quiz già aperti invariata. Test dominio/Joomla/HTTP/reinstall e guide aggiornati, esiti nella PR del build. Schema41tabelle/58FK invariato.

## 0.23.0-dev — docenti M01/b2

- Profili docente con MVC/Form/Table Joomla, categorie/asset, ACL dedicata e revisioni; bio testuale, immagine/alt, sito e account facoltativo senza gruppi aggiunti.
- Selezione multipla nei corsi in bozza; snapshot ordinato, pubblicazione validata e visibilità corrente/storica su profili e categorie.
- Prove native/HTTP/upgrade e guide/IT-EN/override; esiti nella PR del build. Schema41tabelle/58FK invariato. Calendario, directory pubblica standalone, associazioni multilingua dei docenti e selettore ricercabile avanzato ancora pendenti.

## 0.22.0-dev — copertina e SEO M01/b1

- Media field nativo per copertina pubblica locale, alt obbligatorio o decorazione esplicita; validazione path/MIME/dimensioni e confini filesystem.
- Tab metadati con titolo/descrizione SEO facoltativi, congelati nella versione e applicati alla head nativa soltanto dopo autorizzazione.
- Compatibilità degli snapshot0.21 e test di upgrade reale; schema41tabelle/58FK invariato. Docenti, calendario, provider media remoti e asset versionati ancora pendenti.
- Corretto avvio automatico concorrente: il lock dell’edizione è la prima lettura transazionale, prima dello snapshot MySQL/MariaDB; identità e disponibilità aggiornate dopo l’attesa. Test deterministico con un’iscrizione concorrente inserita durante il blocco.
- Prove servizio/HTTP e regressioni: esiti nella PR del build. Nessuna accettazione completa della fase dedotta dai test.

## 0.21.0-dev — editor/presentazione M01/a

- Schede native editor e nuovi campi di presentazione, filtrati/tipizzati e conservati senza accettare JSON grezzo.
- Snapshot degli ultimi dati alla pubblicazione; scheda autorizzata con nuove regioni CSS. Versioni storiche non modificate e liste leggere.
- Piano M01–M12 più differitoM13, con assegnazione verificata di tutti i210ID; non dichiarazione di completamento.
- Nuove prove di persistenza, autorizzazione, XSS, input invalidi, snapshot e upgrade; risultati da registrare. Schema41tabelle/58FK invariato.

## 0.20.1-dev — stabilizzazione pubblicazione

- Priorità0 per il blocco segnalato nel collaudo utente; import CSV rinviato fino alla verifica del flusso essenziale. Nessuna diagnosi del sito dedotta dalla CI.
- Collegamento programma/pubblicazione dall’editor, istruzioni corrette e diagnostica di versione, permessi, plugin Publishing e transizioni mancanti.
- Nuova prova HTTP di creazione e prima pubblicazione, rifiuto programma vuoto/plugin disabilitato e replay senza duplicare l’edizione. Prima CI41349160121:13asserzioni HTTP per ambiente e suite completa riuscite. Chiarimento successivo della voce nell’elenco corsi nella PR22; riscontro sul sito pendente.
- Schema invariato, quattro provider base conservati, nessun bypass del workflow o dell’immutabilità.

## 0.20.0-dev

- Preassegnazione manuale esplicita senza livelli correnti, con avviso e conferma; default di ammissione invariato.
- Assegnazione distinta dalla fruizione: nessun gruppo aggiunto, stessi controlli runtime, disponibilità editoriale obbligatoria.
- Opt-in vincolato all’anteprima/hash e registrato nel log Joomla; compatibilità degli hash standard0.19 preservata.
- Nessuna modifica allo schema41tabelle/58FK; prove negative, concorrenza, grant/revoca e conservazione superate nella CI41319974680 (37servizio/18HTTP/4conservazione per ambiente). CSV e accettazione completa ancora pendenti.

## Riallineamento documentale della baseline0.19

- Riepilogo/README aggiornati ai flussi realmente presenti, schema41tabelle/58FK e integrazioni core ancora mancanti.
- Matrice dei210requisiti con descrizione/perimetro/scenari baseline preservati, sorgenti e prove parziali distinti dai criteri residui; nessuna accettazione completa dedotta dalla CI.
- Registro delle differenze aperte (preassegnazione senza livello, provider aggiuntivi, ritiro/erogazione, ID pagina e fallback di ripresa), evidenze del build0.19 e piano esecutivo residuo.
- Note storiche conservate separatamente e controllo di coerenza documentale nella verifica strutturale. Nessuna modifica al codice distribuito o al pacchetto0.19.

## 0.19.0-dev

- Iscrizione manuale singola alla versione pubblicata corrente e all’edizione predefinita, con controlli freschi su operatore e destinatario.
- Anteprima/conferma/motivo, CSRF, receipt idempotente e audit core atomico; nessuna assegnazione di gruppi o duplicazione/riattivazione implicita.
- Lock dell’edizione condiviso con Inizia, capienza e subject risolti in transazione; nessuna email automatica.
- Schema additivo41tabelle/58FK; prove runtime/HTTP/concorrenza/audit/upgrade predisposte, esiti effettivi nelle Actions. Import CSV, approvazioni e gestione completa restano pendenti.

## 0.18.0-dev

- Sospensione e riattivazione delle iscrizioni con anteprima aggiornata, motivo/conferma e permessi correnti per corso.
- Eventi append-only e Action Logs atomici; replay idempotenti e lock/CAS condivisi con i writer didattici.
- Progressi, completamenti, risposte e timer conservati; nessuna assegnazione di gruppi o proroga delle finestre.
- Schema additivo40tabelle/57FK; test runtime/HTTP/concorrenza/audit/upgrade predisposti, esiti effettivi nelle Actions. Creazione manuale, annullamenti e gestione completa ancora pendenti.

## 0.17.0-dev

- Elenco amministrativo degli iscritti per corso con permessi nativi dedicati e aggiornati su ogni lettura.
- Filtri versione/utente/stato/avanzamento, paginazione nativa e date UTC; nessuna mutazione o assegnazione di gruppi.
- Stato iscrizione distinto dal completamento; dati minimizzati e nomi escapati, viste native e override documentati.
- Nessuna modifica allo schema; prove ACL/runtime/HTTP/conservazione predisposte, esiti effettivi nelle Actions. Gestione con audit, report completi ed export restano da implementare.

## 0.16.0-dev

- Area personale del solo utente autenticato con storico paginato, filtri nativi e gruppi didattici separati dalla disponibilità corrente.
- Storico e titolo congelato conservati dopo revoca o scadenza; nessuna lezione/risorsa/risposta nel riepilogo.
- Riprendi/Rivedi in lettura con nuova autorizzazione, ultima attività valida e lingua originale, senza iscrizioni o tentativi automatici.
- MVC/menu/form/override e ID CSS nativi; intestazioni private e noindex. Nessuna nuova tabella; test runtime/HTTP/upgrade predisposti, esiti effettivi nelle Actions.
- Attestati, attività dettagliate e gestione amministrativa delle iscrizioni restano da implementare.

## 0.15.0-dev

- Viste pubbliche categorie e categoria con figli/corsi paginati e ID CSS stabili per override nativi.
- Filtro corrente di stato/lingua/accesso su ogni antenato e sezione; nessun conteggio di rami nascosti.
- Tipo nativo course.category nel gestore Associazioni e collegamenti lingua pubblici, menu/Pathway e SEF.
- Nessuna nuova tabella; test lifecycle/HTTP/upgrade predisposti, esiti effettivi nelle Actions. Rimozione con dipendenze e SEO completo restano da implementare.

## 0.14.0-dev

- Associazioni dei corsi in bozza tramite AdminModel, tabella e contratto nativi Joomla; campi per lingua con ACL e checkout dei target.
- Integrazione con gestore Associazioni, Language Filter e mod_languages; schede tradotte filtrate per visibilità corrente, senza trasferire progressi.
- Gruppi pubblicati protetti, input manomessi e appropriazione di collegamenti rifiutati; nessuna modifica allo schema39tabelle/56FK.
- Test runtime, HTTP con pacchetto lingua italiano, routing SEF e conservazione predisposti. Categorie e gestione dei collegamenti pubblicati restano da completare; esiti effettivi nelle Actions.

## 0.13.0-dev

- Proroghe dei tentativi aperti con timer, anteprima/conferma/motivo, autorizzazioni native per corso e Action Logs atomici.
- Deadline effettiva distinta dallo snapshot originario: storia append-only, nessuna modifica alle risposte o alla loro revisione; limite48ore e nessuna riapertura dopo scadenza/consegna.
- Stato studente compatto e sincronizzazione del timer anche senza risposte cambiate; selezioni locali conservate e avvisi riallineati alla proroga.
- Schema additivo39tabelle/56FK. Verifiche runtime/HTTP/client/concorrenza e aggiornamento dalla0.12 predisposti; esiti effettivi nelle Actions.
- Rivalutazioni, attestati/anomalie, integrazioni complete e certificazioni di produzione restano da completare.

## 0.12.0-dev

- Annullamento dei tentativi con anteprima d’impatto, conferma e motivo; restituzione esplicita del consumo e numeri storici mai riutilizzati.
- Evidenze originarie conservate, risultati/completamento ricalcolati sui tentativi validi, annullamenti distinti dallo storico ancora valido.
- Audit Action Logs e ledger nella transazione; anteprime obsolete e replay incompatibili rifiutati. Mutazioni dello studente bloccate sul tentativo annullato.
- Guardie per attestati, revisioni d’esito e storici oltre10.000tentativi; questi casi richiedono flussi dedicati successivi.
- Test runtime/HTTP/concorrenza/upgrade predisposti; esiti effettivi nelle Actions. Nessuna modifica allo schema38tabelle/55FK.

## 0.11.0-dev

- Autorizzazione e ritiro del tempo aggiuntivo per iscrizione/quiz, con backend MVC/form nativi, ACL per corso, motivazione e revisioni append-only.
- Durata individuale congelata all’avvio; tentativi già aperti/conclusi immutabili, anche durante revoche o aumenti successivi. Compatibilità con snapshot0.10.
- Action Logs Joomla con dati minimi e verifica atomica della registrazione; nessun invio email dentro la transazione.
- Avvisi a cinque minuti e a un minuto in regione accessibile separata dal salvataggio. Riepilogo distinto fra tempo attuale e prossimo tentativo.
- Schema additivo:38tabelle/55FK. Collaudi predisposti per aggiornamento0.10→0.11 con tentativi preesistenti, ACL/audit/deadline, HTTP e conservazione. Risultati effettivi nelle Actions della PR.
- Rivalutazioni, proroghe durante un tentativo, privacy completa, carico e certificazione accessibilità restano da completare.

## 0.10.0-dev

- Pubblicazione di quiz con pool congelato e snapshot privati/pubblici per ogni tentativo; estrazione e ordine stabili alla ripresa.
- Avvio condiviso fra schede, salvataggi CAS, UUID idempotenti, consegna e scoring esatto lato server, migliore risultato e completamento una sola volta.
- Timer server, chiusura sulle sole risposte salvate e plugin base Joomla Scheduler `task/jlms`, routine `jlms.quiz.expire` a lotti limitati.
- Viste studenti con override/ID CSS, task JSON con CSRF/accesso corrente e feedback protetto; autosave JavaScript ogni20secondi con retry immutabili.
- Test runtime/HTTP/concorrenza/upgrade predisposti; vedere Actions della PR per l’esito effettivo. Tempi aggiuntivi, rivalutazione, collaudo browser/carico e integrazioni complete restano da implementare.

## 0.9.0-dev

- Banca domande con categorie e asset Joomla, lingua, stato, editor/subform e checkout/checkin nativi.
- Scelta singola, vero/falso e multipla: validazione, chiavi di correzione separate e nuove revisioni immutabili a ogni salvataggio.
- Quiz nelle bozze: composizione per revisioni esplicite, punti, selezione/estrazione configurata, soglia esatta, timer/tentativi/intervallo/feedback e cancellazione con dipendenze.
- ACL su corso e banca, revisione aggregata, confini delle risposte corrette e guide/override aggiornati.
- Tentativi, estrazione runtime, autosave, valutazione, feedback studente e pubblicazione dei quiz restano chiusi fino all'incremento successivo.

## 0.8.0-dev

- HTML5 con upload MP4/H.264/AAC per lezione, durata/codec rilevati da ffprobe e asset privati immutabili.
- Quarantena e rescan con policy antivirus condivisa; nessuna assegnazione arbitraria di asset o sostituzione di video pubblicati.
- Consegna GET/HEAD/Range con accesso Joomla corrente, PHP limitato a64MiB oppure offload Nginx interno, senza fallback pubblico.
- Player nativo con progressi comuni e nessuna dipendenza dal consenso ai provider esterni; nuove regioni admin documentate.
- Verifica HTTP con ffprobe e Nginx reali; browser decoder, carico, altri codec/storage e cleanup ancora da completare.

## 0.7.0-dev

- Cloudflare Stream opt-in in modalità API: segreti server via riferimenti ambiente, verifica remota di privacy/account/dominio/origini e token temporanei senza download.
- Scadenza limitata dalle finestre didattiche note; rinnovo con nuovo controllo Joomla, generazione per sessione e conservazione progressi/posizione.
- Chiamate provider fuori dai lock di scrittura e nuova autorizzazione prima della consegna; nessuna chiamata API durante i salvataggi del progresso.
- Adapter SDK Stream, messaggi di rinnovo e test API/SDK simulati, accesso revocato durante risposta e upgrade dalla0.6.
- Firma locale, HTML5, diagnostica autonoma, cache e collaudo reale provider/carico restano da completare.

## 0.6.0-dev

- Player YouTube/Vimeo opt-in tramite plugin Joomla, SDK ufficiali caricati dopo consenso e grant server.
- Pubblicazione video con durata verificata e snapshot immutabile; aula con nuove regioni CSS e materiali corso/lezione.
- Sessioni, sequenze e replay, copertura unica, budget temporale server, resume concorrente e controllo ACL corrente.
- Test dominio, integrazione Joomla/database, endpoint HTTP e mock del trasporto browser; upgrade da 0.5 con storico conservato.
- Cloudflare Stream/HTML5, metadati remoti, prove SDK reali e test di carico restano da completare.

## 0.5.0-dev

- Materiali di corso/versione e lezione: upload, metadati, ordine, sostituzione immutabile e rimozione del collegamento in bozza.
- Storage fuori webroot, allowlist e controlli MIME/firme/archivi bounded, quarantena e scansione ClamAV opzionale via socket locale.
- Download con accesso corrente, ownership/parent, SHA256, attachment/nosniff/no-store e Range/HEAD; materiali facoltativi senza credito didattico.
- Pubblicazione include hash dei materiali e richiede asset pronti; nuove regioni CSS e override documentati.
- Offload, cleanup, preview e riuso asset ancora da completare.

## 0.4.0-dev

- Iscrizione automatica/ripresa e aula per lezioni di testo, con login nativo Joomla.
- Completamento idempotente e transazioni per iscrizione, contatori obbligatori e data di conclusione separata dallo stato di accesso.
- Identità e livelli aggiornati dal core a ogni richiesta, anche dopo revoca con sessione aperta.
- Test con processi concorrenti, revoca/ripristino, ownership, CSRF e flusso HTTP studente.
- Modalità approvazione, player/materiali, quiz, attestati e integrazioni restanti ancora in sviluppo.

## 0.3.0-dev

- Pubblicazione validata dei corsi di testo tramite workflow e plugin Publishing Joomla; transazione unica, versione immutabile, hash ed edizione iniziale.
- Catalogo paginato e scheda pubblica con programma, filtro categoria/discendenti, autorizzazioni correnti e nuovi ID CSS.
- Test integrativi workflow/veto/immutabilità/visibilità e rendering HTTP; risultati effettivi nella PR.
- Video, materiali, iscrizioni e fruizione studenti ancora in sviluppo.

## 0.2.0-dev — 31 ottobre 2026

- Aggiunti servizio applicativo DI, prima bozza didattica, editor del programma e comandi MVC nativi.
- Implementati moduli e lezioni testo/video, obbligatorietà, modifica, riordino e rimozione con controlli delle dipendenze.
- Controlli centralizzati di ACL, parent, revisione aggregata/elemento e immutabilità delle versioni pubblicate; query e transazioni brevi.
- Integrazione editor/filtro HTML core, provider dal registro estensibile e normalizzazione delle sorgenti.
- Nuovi test integrativi e HTTP del flusso completo. Nessuna pubblicazione o fruizione studenti ancora attiva.

## 0.1.0-dev — 31 ottobre 2026

Primo incremento di sviluppo, non release del sistema e-learning completo.

- Importate le specifiche funzionali, tecniche, sicurezza/provider, servizi/endpoint e matrici di collaudo concordate.
- Aggiunti package Joomla, libreria contratti, componente MVC, schema37tabelle/53FK e installer conservativo con verifiche dello schema trattenuto.
- Implementata gestione dei corsi in bozza con categorie, livelli, lingua, asset ACL, check-in/out e controllo delle revisioni.
- Inclusi i quattro plugin video con registrazione lazy e validazione locale delle sorgenti; riproduzione esplicitamente non disponibile.
- Aggiunte regole dominio iniziali, traduzioni, WAM, prime regioni CSS pubbliche e guida agli override.
- Predisposte build riproducibile e CI PHP8.3/8.4, MySQL8.4/MariaDB10.6, installazione/aggiornamento Joomla, test MVC e rendering HTTP.
- Pubblicazione, fruizione, quiz persistenti, attestati e integrazioni rimanenti restano nello stato documentato in IMPLEMENTATION_STATUS.md.
