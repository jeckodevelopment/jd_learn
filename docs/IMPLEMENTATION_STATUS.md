# Stato attuale di JD Learn

**Main è 0.41.15-dev**, PR [#90](https://github.com/jeckodevelopment/jd_learn/pull/90) integrata con merge [0f55bd1](https://github.com/jeckodevelopment/jd_learn/commit/0f55bd149a58c6fd1ce373655d89b1154c58edd4). Il registry video, pubblicazione/fruizione e loader asset ora accettano plugin provider aggiuntivi senza patch al componente. CI standard superata sul commit verificato 1833162 nel run [42476313500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42476313500); collaudo end-to-end con un quinto plugin e collaudo manuale Joomla restano aperti.

**Main è 0.41.14-dev**, integrata con la PR [#88](https://github.com/jeckodevelopment/jd_learn/pull/88) (merge [f2f8c76](https://github.com/jeckodevelopment/jd_learn/commit/f2f8c76b7ed88119e0a8c8b9796a52bcb0b97810)). La migrazione esplicita di una singola iscrizione alla versione corrente trasferisce solo i completamenti non quiz selezionati e conserva la provenienza. CI standard superata sul candidato esatto (run [42473935482](https://github.com/jeckodevelopment/jd_learn/actions/runs/42473935482)); resta il collaudo Joomla manuale.


La versione **0.41.9-dev**, ora integrata in main con la PR #70 (merge [70f6ed7](https://github.com/jeckodevelopment/jd_learn/commit/70f6ed7f2a1f4386b943bcb834f57e8d31cf935f)), aggiunge i controlli separati di ammissione ed erogazione R-03, con anteprima, motivo, conferma e storico. La CI standard è superata nei due ambienti PHP/database e Chromium (run [42428496835](https://github.com/jeckodevelopment/jd_learn/actions/runs/42428496835)); resta il collaudo manuale Joomla. La dashboard amministrativa della **0.41.8-dev** mette in primo piano la gestione corsi, raggruppa gli strumenti e rende espandibile lo stato dei provider. La PR #68 è integrata in main; la CI standard è superata. Restano aperti i controlli manuali nel backend Joomla.

I campi editoriali del corso e i campi didattici delle lezioni sono disponibili tramite Joomla Fields. La **0.41.7-dev** integra il contesto lezioni, il salvataggio ACL, la copia e revisione dei valori, lo snapshot immutabile alla pubblicazione e la resa nell’aula. CI standard superata; il collaudo Joomla reale resta da completare. Il ripristino completo dei valori tramite Content History resta aperto.

Aggiornato all’**8 novembre 2026**. Candidato corrente **0.41.15-dev**; main integrata **0.41.14-dev**; ultima CI runtime superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run [42473935482](https://github.com/jeckodevelopment/jd_learn/actions/runs/42473935482)). Il collaudo manuale Joomla reale resta aperto. Il prodotto è in sviluppo e non è pronto per la produzione.

La PR #65 ha completato questo incremento M01/M02: campi personalizzati delle lezioni con salvataggio nativo, copia/revisione del baseline, snapshot immutabile e resa in aula. Il percorso di base è disponibile: creare e pubblicare un corso, aggiornare il programma, ammettere uno studente e fruire lezioni testo/video/quiz con progressi persistenti. Il lavoro corrente è il completamento della gestione corsi **M01**, che resta aperta per funzioni mancanti e collaudi; non stiamo più implementando da zero pubblicazione, duplicazioni o riordino.

### Incremento0.41.3-dev — checkbox risposta corretta

In Joomla `default="0"` diventava anche il valore HTML inviato dal checkbox selezionato. Il server riceveva0 e non trovava risposte corrette. Rimosso il default; il markup Joomla ora invia1 quando la casella è selezionata. La regressione è verificata dai test HTTP che estraggono nomi e valori dal form nativo. I quattro job standard sono passati; attende conferma sul sito dell’utente.

### Incremento0.41.4-dev — schede del corso raggruppate

La PR #60 è stata unita. Riunisce copertina/docenti con i dati del corso, accesso/calendario in una scheda unica e regole nel contesto del programma. Modifica solo la presentazione dell’editor: campi salvati una volta, senza cambiare schema, workflow o versioni. Le verifiche HTTP mirate, la suite standard d’integrazione e Chromium sono passati nella CI 42410200676; resta il collaudo manuale nel Joomla reale, inclusi mobile, tastiera e override.

### Incremento0.41.6-dev — continuità dell’aula dopo il ritiro\n\nLa PR #64 è integrata in main. La CI standard finale ha superato entrambi gli ambienti PHP/database e Chromium ([run 42414492267](https://github.com/jeckodevelopment/jd_learn/actions/runs/42414492267)). Gli iscritti autorizzati mantengono l’accesso dopo il ritiro della scheda; le nuove ammissioni continuano a richiedere la disponibilità editoriale. R-03 è parziale: restano da implementare chiusura separata delle nuove ammissioni e sospensione dell’erogazione, con motivo e audit. Il collaudo manuale in Joomla reale resta aperto.\n\n### Incremento0.41.5-dev — ripresa dell’attività

La PR #62 corregge R-05: quando manca un’ultima lezione valida, Riprendi seleziona la prima obbligatoria non completata; se non ne restano, apre la prima lezione per revisione. Integrazione Joomla superata in entrambi gli ambienti. Da provare nel sito Joomla reale.

### Incremento 0.41.15-dev — provider video plugin estendibili

Rimosse le liste fisse dei quattro provider base nei percorsi di metadata editor, pubblicazione e ammissione alla fruizione. Il loader carica il player secondo la convenzione Joomla `media/plg_jlmsvideo_<provider>/js/player.js`; chiave validata e modulo importato soltanto dopo consenso e grant di playback. Il riepilogo in Programma legge i provider registrati. Aggiunta regressione client per provider aggiuntivo. R-02 resta parziale finché un quinto plugin Joomla non supera prova end-to-end; la CI PR e il collaudo manuale Joomla del sito restano da completare.

## Avanzamento stimato verso la prima versione stabile

Le percentuali indicano la completezza implementativa stimata rispetto al perimetro approvato; non equivalgono ad accettazione manuale in Joomla.

| Area | Avanzamento |
|---|---:|
| Corsi, versioni e programma (M01/M02) | 68% |
| Fruizione e progressi core (M03/M09) | 55% |
| Video e materiali essenziali (M04) | 80% |
| Iscrizione e roster essenziale (M06/M09) | 65% |
| Usabilità amministrativa e integrazioni Joomla | 80% |
| Collaudo e preparazione release (M10–M12) | 58% |
| **Avanzamento complessivo stimato** | **68%** |

## Funzioni presenti e lavoro residuo

“Disponibile” significa che esiste un flusso operativo con prove parziali. Non significa che l'intero requisito sia accettato. “Da implementare” indica una funzione assente, anche quando ne esistono schema o specifiche.

| Area | Disponibile oggi | Da implementare o completare |
|---|---|---|
| Amministrazione corsi e docenti | Elenco/editor secondo le convenzioni articoli Joomla; presentazione, copertina/alt/SEO di base, calendario, accessi e ACL; salvataggio dei corsi pubblicati con storico; profili docente, campi nativi docente e scheda Campi personalizzati per i corsi, resa editoriale nella scheda pubblica e accesso alle Regole didattiche, modifica propria e voci di menu; menu amministrativo nativo, ingresso diretto ai corsi e dashboard rivista nella 0.41.8; controlli operativi R-03 integrati nella 0.41.9 | Collaudo manuale; regole avanzate e attestato; snapshot didattici dei campi corso e campi lezione, copertura completa di lingue, associazioni e gestione dei dati |
| Programma | Moduli e lezioni testo/video/quiz; editor contestuale singolo; duplicazioni autonome di corso/modulo/lezione; spostamento fra moduli della bozza; trascinamento e Su/Giù; recupero input dopo errori e avviso modifiche non salvate; editor delle regole supportate, soglia video e finestre temporali dei materiali della bozza | Lezioni audio/documento autonome; prerequisiti, sequenze e rilascio progressivo |
| Ciclo editoriale | Bozza, precontrollo con blocchi/avvisi distinti nella 0.41.10 e controlli video/materiali nella 0.41.11–0.41.12; aggiornamento in nuova versione immutabile; recupero storico con audit; 0.41.14: migrazione esplicita singola dal roster con mappatura, anteprima/hash, conferma/motivo, trasferimento dei soli completamenti non quiz e provenienza. La fonte resta nello storico; tentativi aperti, destinazione esistente o edizione non disponibile bloccano l’azione | Revisione/archivio completi, anteprima interattiva e dei livelli senza effetti; collaudo manuale della migrazione e dei casi negativi |
| Catalogo e categorie | Catalogo paginato, categorie e antenati, scheda/programma autorizzati, menu/Pathway/SEF e prime associazioni multilingua | Ricerca/ordinamenti/filtri e SEO completi, percorsi aggregati, lifecycle categorie e associazioni pubblicate |
| Aula e area personale | Fruizione protetta, progressi persistenti, storico del proprietario, filtri e ripresa autorizzata; fallback R-05 corretto nella 0.41.5; la 0.41.7 rende i campi lezione congelati | Attività dettagliate e collegamento agli attestati; collaudo manuale di ripresa ancora richiesto |
| Video | Quattro plugin base: YouTube, Vimeo, Cloudflare Stream e HTML5; registry, controlli di pubblicazione/fruizione, riepilogo plugin e loader asset estendibili senza patch (0.41.15); metadati automatici nell’editor, provenienza/ricevute e tolleranza con denominatore osservato; consenso, copertura/ripresa, soglia video configurabile per versione con default90%, API/token Stream e MP4 privati con Range/Nginx | Collaudo completo di acquisizione/scarti con provider e browser reali (R-06); collaudo end-to-end di un quinto provider installabile senza patch (R-02), sottotitoli/trascrizioni/preferenze complete, firma locale/cache Stream e configurazioni restanti |
| Materiali | Allegati distinti corso/lezione, upload validato, quarantena/scanner opzionale, storage privato e download autorizzato; preflight coerente con stato/scansione/preview e date iniziali/finali nel fuso del corso (0.41.13 in CI) | Riuso/anteprima/metadati e lifecycle completi, offload, pulizia e conservazione |
| Iscrizioni | Avvio automatico, elenco amministrativo, ammissione singola con anteprima/conferma/audit, preassegnazione esplicita senza concedere accesso, stato della singola iscrizione; ritiro dal catalogo senza revoca dell’aula per iscritti autorizzati (0.41.6); comandi R-03 separati di chiusura/apertura ammissioni e sospensione/ripresa erogazione con anteprima, motivo e storico (0.41.9 integrata) | Collaudo manuale Joomla reale R-03; CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive e proroghe |
| Quiz | Banca/revisioni, composizione/pool/snapshot, tentativi e autosave/CAS, valutazione/feedback, scadenza server, tempi individuali, annullamenti e proroghe motivati | Rivalutazioni, disponibilità e anomalie complete, collegamento agli attestati e rifinitura dell'interfaccia |
| Attestati | Schema e specifiche; **nessun flusso operativo** | Modelli/PDF, idoneità ed emissione, download, verifica opzionale, revoca/sostituzione |
| Processi e notifiche | Task Scheduler per scadenze quiz; audit nativo su quiz/iscrizioni/recupero programma | Job generali recuperabili, outbox/retry, email/Mail Templates e pulizia programmata |
| Report | Elenco iscritti con filtri, stato, avanzamento e date | Aggregati/KPI, dettagli studente/quiz/attestati e CSV protetti |
| Integrazioni Joomla | Identità/sessioni/CSRF, ACL/asset, categorie, form, WAM, menu, override e lingue nei flussi presenti; Fields docenti e storico corso/docente parziali | Privacy, Finder, Tags, copertura completa Fields/History/Media/lifecycle e audit generale |
| Installazione e rilascio | Build riproducibile, installazione/upgrade/reinstall con conservazione dei dati verificati dalla suite | Update server, backup/ripristino, patch Joomla corrente, integrazione reale con estensione abbonamenti e documentazione di rilascio |

Gestione corsi e funzionamento operativo di YouTube, Vimeo e Cloudflare Stream sono la priorità corrente condivisa. Estensioni avanzate dei provider restano separate: ogni difetto che impedisce di pubblicare o fruire un corso video va affrontato subito.

## Verifiche disponibili e limiti

La [CI0.41.8 (PR #68)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440) ha superato i quattro job standard: controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. La prova browser è una fixture controllata e non sostituisce il collaudo manuale della dashboard nel Joomla reale. La [CI0.41.7 (PR #65)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42420998171) e la [CI0.41.6 (PR #64)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42414492267) restano nel registro delle prove precedenti.

La [CI0.41.3](https://github.com/jeckodevelopment/jd_learn/actions/runs/42405954861) ha superato i quattro job standard: per ambiente, 23 controlli di dominio e 11 HTTP sulla banca/quiz. Il test usa il checkbox del markup nativo, verifica valore1 e salvataggio Vero/Falso e Scelta singola. Il caso reale sul sito dell’utente resta da verificare. La [CI0.41.2](https://github.com/jeckodevelopment/jd_learn/actions/runs/42404631048) ha verificato i messaggi di validazione. La CI0.41 precedente (vedi registro prove) ha superato i quattro job standard sul codice `dd2b5ba8ca5489909f0968c0cad3c15c2975a8fc`:

- PHP8.3/MySQL8.4 e PHP8.4/MariaDB10.6: intera suite implementata, build riproducibile, installazione Joomla, upgrade0.40.1→0.41, reinstallazione e storico conservato. Per ambiente, 29 nuovi controlli nativi, 20 HTTP e 1 retained sui campi editoriali pubblici. Provati plugin testo/textarea/lista, posizioni e default, accesso campo/gruppo/corso e revoca nella stessa sessione, lingua/categoria, aggiornamento editoriale e override nativo; versione assegnata e progressi invariati. Le regressioni video e del menu rimangono incluse.
- Chromium: 12 controlli su fixture desktop/mobile delle modifiche non salvate. Non è collaudo interattivo dell’intero Joomla, del form regole o dei nuovi campi pubblici.
- Job leggero: politica CI, build, struttura, traduzioni e tracciabilità. Le bozze preparatorie hanno eseguito soltanto questi controlli; la verifica finale usa la [matrice standard](CI_POLICY.md). Le due coppie incrociate restano da eseguire nella matrice completa prima di milestone/rilascio o modifiche di compatibilità.

La registrazione successiva degli esiti modifica soltanto documentazione esterna al pacchetto: runtime e pacchetto restano quelli verificati. Nessuna ripetizione automatica al merge. La matrice completa precedente è conservata nel [registro delle prove](IMPLEMENTATION_EVIDENCE.json), con i propri limiti.

Prima pubblicazione e YouTube sono stati confermati dall’utente sul percorso provato. Restano collaudo completo browser/mobile/tastiera/accessibilità e dei provider/SDK reali, audit sicurezza, backup/ripristino, patch Joomla corrente e benchmark di 300 studenti/60 minuti e 500/10 minuti. Le prove di telemetria server e i test concorrenti non sostituiscono queste verifiche. M01 resta aperto.

## Prossimo passo

La PR #68 è integrata in main: la dashboard porta in primo piano la gestione corsi e raccoglie strumenti secondari e stato provider; la CI standard è superata nel run 42423237440. Il collaudo manuale su Joomla resta aperto. La PR #65 ha integrato i campi nativi delle lezioni e i valori congelati nella versione assegnata; restano da verificare nell’installazione reale. La PR #62 integrata nella 0.41.5 corregge R-05: quando non esiste una lezione precedente valida, Riprendi seleziona la prima obbligatoria non completata; se tutte sono complete, apre la prima lezione per la revisione. Per R-03 la 0.41.6 integrata mantiene l’aula degli iscritti autorizzati quando la scheda viene ritirata dal catalogo o termina la pubblicazione; la stessa condizione non permette nuove ammissioni. Comandi amministrativi motivati di chiusura/sospensione restano da realizzare.

Continuare M01 sul flusso editoriale semplificato, ora integrato in main, e sui residui di programma/campi/versioni indicati nella checklist, senza includere quiz e attestati nel gate della prima stabile. La banca domande ha ricevuto la correzione mirata0.41.3; i requisiti quiz restano aperti per i criteri non coperti. Collaudare con i provider e browser reali il percorso di [durata automatica e scarti video](VIDEO_DURATION_PLAN.md) e chiudere i residui della [checklist M01](M01_ACCEPTANCE.md), distinguendo implementazioni mancanti e collaudi. Il [piano ordinato](NEXT_STEPS.md) definisce il lavoro successivo e le dipendenze; l'import CSV viene dopo il flusso essenziale dei corsi e richiede i job recuperabili. Privacy e sicurezza accompagnano ogni attività.

## Perimetro e tracciabilità

La specifica funzionale **1.5** e la [matrice dei 210 requisiti](REQUIREMENTS_STATUS.csv) restano il perimetro: 209 requisiti del rilascio 1 e uno differito. Nessun requisito è dichiarato integralmente accettato senza un report completo. Non si ricava una percentuale contando le righe “In corso”.

Schema attuale: **42 tabelle / 59 FK**; 7 estensioni nel pacchetto. Questi conteggi descrivono l'architettura, non l'avanzamento.

Questo documento è il riepilogo corrente. [Roadmap](ROADMAP_TO_STABLE.md), [problemi specifici](IMPLEMENTATION_GAPS.md) e [guide](../README.md#usare-e-configurare-jd-learn) approfondiscono i rispettivi temi; [cronologia](IMPLEMENTATION_HISTORY.md) e [changelog](../CHANGELOG.md) conservano gli incrementi passati.
