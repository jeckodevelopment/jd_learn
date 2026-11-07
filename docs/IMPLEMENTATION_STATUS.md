# Stato attuale di JD Learn

I campi editoriali correnti sono disponibili nella scheda pubblica del corso tramite plugin e layout Joomla. Snapshot didattici, campi lezione e ripristino completo dei valori tramite Content History restano aperti.

Aggiornato al **7 novembre 2026**. Versione **0.41.1-dev**: menu nativo e ingresso diretto ai corsi verificati nella CI standard42400903346; regressioni e upgrade superati, collaudi reali ancora aperti. **Il prodotto è in sviluppo e non è pronto per la produzione.**

Il percorso di base è disponibile: creare e pubblicare un corso, aggiornare il programma, ammettere uno studente e fruire lezioni testo/video/quiz con progressi persistenti. Il lavoro corrente è il completamento della gestione corsi **M01**, che resta aperta per funzioni mancanti e collaudi; non stiamo più implementando da zero pubblicazione, duplicazioni o riordino.

### Incremento candidato0.41.1-dev

Il salvataggio della banca domande trattava una stringa vuota del checkbox “corretta” come input malformato. Il browser normalmente omette i checkbox non selezionati, mentre alcuni percorsi di filtro del form possono preservare il valore vuoto: entrambe le rappresentazioni ora significano risposta non corretta. Restano obbligatorie le regole di validazione (almeno una risposta corretta; una sola per scelta singola e vero/falso). Regressioni dominio e invio HTTP nativo sono aggiunti; la CI standard di questa correzione è in attesa e la correzione non è ancora pubblicata.

## Funzioni presenti e lavoro residuo

“Disponibile” significa che esiste un flusso operativo con prove parziali. Non significa che l'intero requisito sia accettato. “Da implementare” indica una funzione assente, anche quando ne esistono schema o specifiche.

| Area | Disponibile oggi | Da implementare o completare |
|---|---|---|
| Amministrazione corsi e docenti | Elenco/editor secondo le convenzioni articoli Joomla; presentazione, copertina/alt/SEO di base, calendario, accessi e ACL; salvataggio dei corsi pubblicati con storico; profili docente, campi nativi docente e scheda Campi personalizzati per i corsi, resa editoriale nella scheda pubblica e accesso alle Regole didattiche, modifica propria e voci di menu; menu amministrativo nativo e ingresso diretto ai corsi | Completare restyling e collaudo; regole avanzate e attestato; snapshot didattici dei campi corso e campi lezione, copertura completa di lingue, associazioni e gestione dei dati |
| Programma | Moduli e lezioni testo/video/quiz; editor contestuale singolo; duplicazioni autonome di corso/modulo/lezione; spostamento fra moduli della bozza; trascinamento e Su/Giù; recupero input dopo errori e avviso modifiche non salvate; editor delle regole supportate e soglia video della bozza | Lezioni audio/documento autonome; prerequisiti, sequenze e rilascio progressivo |
| Ciclo editoriale | Bozza, prima pubblicazione, aggiornamento del programma mediante copia completa, versioni didattiche immutabili e iscritti mantenuti sulla versione assegnata; anteprima amministrativa di struttura e testo; riepilogo delle differenze e conferma esplicita dell’aggiornamento; recupero di una versione storica in una nuova bozza con audit, senza sovrascrivere le altre | Revisione/archivio completi, anteprima interattiva e dei livelli senza effetti, migrazioni con equivalenze |
| Catalogo e categorie | Catalogo paginato, categorie e antenati, scheda/programma autorizzati, menu/Pathway/SEF e prime associazioni multilingua | Ricerca/ordinamenti/filtri e SEO completi, percorsi aggregati, lifecycle categorie e associazioni pubblicate |
| Aula e area personale | Fruizione protetta, progressi persistenti, storico del proprietario, filtri e ripresa autorizzata | Fallback di Riprendi sulla prima attività obbligatoria non completata (R-05), attività dettagliate e collegamento agli attestati |
| Video | Quattro plugin base: YouTube, Vimeo, Cloudflare Stream e HTML5; metadati automatici nell’editor, provenienza/ricevute e tolleranza con denominatore osservato; consenso, copertura/ripresa, soglia video configurabile per versione con default90%, API/token Stream e MP4 privati con Range/Nginx | Collaudo completo di acquisizione/scarti con provider e browser reali (R-06); quinto provider installabile senza patch (R-02), sottotitoli/trascrizioni/preferenze complete, firma locale/cache Stream e configurazioni restanti |
| Materiali | Allegati distinti corso/lezione, upload validato, quarantena/scanner opzionale, storage privato e download autorizzato | Riuso/anteprima/metadati e lifecycle completi, offload, pulizia e conservazione |
| Iscrizioni | Avvio automatico, elenco amministrativo, ammissione singola con anteprima/conferma/audit, preassegnazione esplicita senza concedere accesso, sospensione/riattivazione | CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive e proroghe; separare chiusura nuove iscrizioni e sospensione dell'aula (R-03) |
| Quiz | Banca/revisioni, composizione/pool/snapshot, tentativi e autosave/CAS, valutazione/feedback, scadenza server, tempi individuali, annullamenti e proroghe motivati | Rivalutazioni, disponibilità e anomalie complete, collegamento agli attestati e rifinitura dell'interfaccia |
| Attestati | Schema e specifiche; **nessun flusso operativo** | Modelli/PDF, idoneità ed emissione, download, verifica opzionale, revoca/sostituzione |
| Processi e notifiche | Task Scheduler per scadenze quiz; audit nativo su quiz/iscrizioni/recupero programma | Job generali recuperabili, outbox/retry, email/Mail Templates e pulizia programmata |
| Report | Elenco iscritti con filtri, stato, avanzamento e date | Aggregati/KPI, dettagli studente/quiz/attestati e CSV protetti |
| Integrazioni Joomla | Identità/sessioni/CSRF, ACL/asset, categorie, form, WAM, menu, override e lingue nei flussi presenti; Fields docenti e storico corso/docente parziali | Privacy, Finder, Tags, copertura completa Fields/History/Media/lifecycle e audit generale |
| Installazione e rilascio | Build riproducibile, installazione/upgrade/reinstall con conservazione dei dati verificati dalla suite | Update server, backup/ripristino, patch Joomla corrente, integrazione reale con estensione abbonamenti e documentazione di rilascio |

Gestione corsi e funzionamento operativo di YouTube, Vimeo e Cloudflare Stream sono la priorità corrente condivisa. Estensioni avanzate dei provider restano separate: ogni difetto che impedisce di pubblicare o fruire un corso video va affrontato subito.

## Verifiche disponibili e limiti

La [CI 0.41](https://github.com/jeckodevelopment/jd_learn/actions/runs/42400903346) ha superato i quattro job standard sul codice `dd2b5ba8ca5489909f0968c0cad3c15c2975a8fc`:

- PHP8.3/MySQL8.4 e PHP8.4/MariaDB10.6: intera suite implementata, build riproducibile, installazione Joomla, upgrade0.40.1→0.41, reinstallazione e storico conservato. Per ambiente, 29 nuovi controlli nativi, 20 HTTP e 1 retained sui campi editoriali pubblici. Provati plugin testo/textarea/lista, posizioni e default, accesso campo/gruppo/corso e revoca nella stessa sessione, lingua/categoria, aggiornamento editoriale e override nativo; versione assegnata e progressi invariati. Le regressioni video e del menu rimangono incluse.
- Chromium: 12 controlli su fixture desktop/mobile delle modifiche non salvate. Non è collaudo interattivo dell’intero Joomla, del form regole o dei nuovi campi pubblici.
- Job leggero: politica CI, build, struttura, traduzioni e tracciabilità. Le bozze preparatorie hanno eseguito soltanto questi controlli; la verifica finale usa la [matrice standard](CI_POLICY.md). Le due coppie incrociate restano da eseguire nella matrice completa prima di milestone/rilascio o modifiche di compatibilità.

La registrazione successiva degli esiti modifica soltanto documentazione esterna al pacchetto: runtime e pacchetto restano quelli verificati. Nessuna ripetizione automatica al merge. La matrice completa precedente è conservata nel [registro delle prove](IMPLEMENTATION_EVIDENCE.json), con i propri limiti.

Prima pubblicazione e YouTube sono stati confermati dall’utente sul percorso provato. Restano collaudo completo browser/mobile/tastiera/accessibilità e dei provider/SDK reali, audit sicurezza, backup/ripristino, patch Joomla corrente e benchmark di 300 studenti/60 minuti e 500/10 minuti. Le prove di telemetria server e i test concorrenti non sostituiscono queste verifiche. M01 resta aperto.

## Prossimo passo

Collaudare con i provider e browser reali il percorso di [durata automatica e scarti video](VIDEO_DURATION_PLAN.md) e chiudere i residui della [checklist M01](M01_ACCEPTANCE.md), distinguendo implementazioni mancanti e collaudi. Il [piano ordinato](NEXT_STEPS.md) definisce il lavoro successivo e le dipendenze; l'import CSV viene dopo il flusso essenziale dei corsi e richiede i job recuperabili. Privacy e sicurezza accompagnano ogni attività.

## Perimetro e tracciabilità

La specifica funzionale **1.5** e la [matrice dei 210 requisiti](REQUIREMENTS_STATUS.csv) restano il perimetro: 209 requisiti del rilascio 1 e uno differito. Nessun requisito è dichiarato integralmente accettato senza un report completo. Non si ricava una percentuale contando le righe “In corso”.

Schema attuale: **41 tabelle / 58 FK**; 7 estensioni nel pacchetto. Questi conteggi descrivono l'architettura, non l'avanzamento.

Questo documento è il riepilogo corrente. [Roadmap](ROADMAP_TO_STABLE.md), [problemi specifici](IMPLEMENTATION_GAPS.md) e [guide](../README.md#usare-e-configurare-jd-learn) approfondiscono i rispettivi temi; [cronologia](IMPLEMENTATION_HISTORY.md) e [changelog](../CHANGELOG.md) conservano gli incrementi passati.
