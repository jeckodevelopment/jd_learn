# Stato attuale di JD Learn

Aggiornato al **7 novembre 2026**. Versione **0.36.0-dev**: riepilogo e conferma dell’impatto degli aggiornamenti didattici, PR #46. **Il prodotto è in sviluppo e non è pronto per la produzione.**

Il percorso di base è disponibile: creare e pubblicare un corso, aggiornare il programma, ammettere uno studente e fruire lezioni testo/video/quiz con progressi persistenti. Il lavoro corrente è il completamento della gestione corsi **M01**, che resta aperta per funzioni mancanti e collaudi; non stiamo più implementando da zero pubblicazione, duplicazioni o riordino.

## Funzioni presenti e lavoro residuo

“Disponibile” significa che esiste un flusso operativo con prove parziali. Non significa che l'intero requisito sia accettato. “Da implementare” indica una funzione assente, anche quando ne esistono schema o specifiche.

| Area | Disponibile oggi | Da implementare o completare |
|---|---|---|
| Amministrazione corsi e docenti | Elenco/editor secondo le convenzioni articoli Joomla; presentazione, copertina/alt/SEO di base, calendario, accessi e ACL; salvataggio dei corsi pubblicati con storico; profili docente, campi nativi docente e scheda Campi personalizzati per i corsi, modifica propria e voci di menu | Parti dell'editor per regole didattiche/attestato; snapshot e resa dei campi corso/lezione, copertura completa di lingue, associazioni e gestione dei dati |
| Programma | Moduli e lezioni testo/video/quiz; editor contestuale singolo; duplicazioni autonome di corso/modulo/lezione; spostamento fra moduli della bozza; trascinamento e Su/Giù; recupero input dopo errori e avviso modifiche non salvate | Lezioni audio/documento autonome; prerequisiti, sequenze e rilascio progressivo |
| Ciclo editoriale | Bozza, prima pubblicazione, aggiornamento del programma mediante copia completa, versioni didattiche immutabili e iscritti mantenuti sulla versione assegnata; anteprima amministrativa di struttura e testo; riepilogo delle differenze e conferma esplicita dell’aggiornamento | Revisione/archivio completi, anteprima interattiva e dei livelli senza effetti, migrazioni con equivalenze, recupero di programmi storici in nuova bozza |
| Catalogo e categorie | Catalogo paginato, categorie e antenati, scheda/programma autorizzati, menu/Pathway/SEF e prime associazioni multilingua | Ricerca/ordinamenti/filtri e SEO completi, percorsi aggregati, lifecycle categorie e associazioni pubblicate |
| Aula e area personale | Fruizione protetta, progressi persistenti, storico del proprietario, filtri e ripresa autorizzata | Fallback di Riprendi sulla prima attività obbligatoria non completata (R-05), attività dettagliate e collegamento agli attestati |
| Video | Quattro plugin base: YouTube, Vimeo, Cloudflare Stream e HTML5; consenso, copertura/ripresa, API/token Stream e MP4 privati con Range/Nginx | Quinto provider installabile senza patch (R-02), sottotitoli/trascrizioni/preferenze complete, firma locale/cache Stream e configurazioni restanti |
| Materiali | Allegati distinti corso/lezione, upload validato, quarantena/scanner opzionale, storage privato e download autorizzato | Riuso/anteprima/metadati e lifecycle completi, offload, pulizia e conservazione |
| Iscrizioni | Avvio automatico, elenco amministrativo, ammissione singola con anteprima/conferma/audit, preassegnazione esplicita senza concedere accesso, sospensione/riattivazione | CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive e proroghe; separare chiusura nuove iscrizioni e sospensione dell'aula (R-03) |
| Quiz | Banca/revisioni, composizione/pool/snapshot, tentativi e autosave/CAS, valutazione/feedback, scadenza server, tempi individuali, annullamenti e proroghe motivati | Rivalutazioni, disponibilità e anomalie complete, collegamento agli attestati e rifinitura dell'interfaccia |
| Attestati | Schema e specifiche; **nessun flusso operativo** | Modelli/PDF, idoneità ed emissione, download, verifica opzionale, revoca/sostituzione |
| Processi e notifiche | Task Scheduler per scadenze quiz; audit nativo su quiz/iscrizioni | Job generali recuperabili, outbox/retry, email/Mail Templates e pulizia programmata |
| Report | Elenco iscritti con filtri, stato, avanzamento e date | Aggregati/KPI, dettagli studente/quiz/attestati e CSV protetti |
| Integrazioni Joomla | Identità/sessioni/CSRF, ACL/asset, categorie, form, WAM, menu, override e lingue nei flussi presenti; Fields docenti e storico corso/docente parziali | Privacy, Finder, Tags, copertura completa Fields/History/Media/lifecycle e audit generale |
| Installazione e rilascio | Build riproducibile, installazione/upgrade/reinstall con conservazione dei dati verificati dalla suite | Update server, backup/ripristino, patch Joomla corrente, integrazione reale con estensione abbonamenti e documentazione di rilascio |

## Verifiche disponibili e limiti

La [CI0.36](https://github.com/jeckodevelopment/jd_learn/actions/runs/42382758865) ha superato tutti5i job sul codice `dee3f1f2481fd215cb7b4538760fb1513b0b2017`:

- PHP8.3/8.4 × MySQL8.4/MariaDB10.6: intera suite nativa/HTTP/JS,19nuovi controlli nativi e10HTTP di confronto/conferma degli aggiornamenti, build riproducibile, installazione e upgrade0.35→0.36, reinstallazione e storici conservati.
- Chromium:12controlli della navigazione con modifiche non salvate su fixture desktop/mobile. Non è un collaudo visivo dell’intero Joomla, della nuova pagina di riepilogo o un’integrazione reale TinyMCE.

La successiva registrazione degli esiti modifica soltanto documentazione; il runtime e il pacchetto restano quelli verificati. La CI completa si esegue sulle PR, senza ripetizione automatica al merge; resta disponibile l’avvio manuale.

Prima pubblicazione e YouTube sono stati confermati anche dall’utente sul percorso provato. Restano collaudo completo browser/mobile/tastiera/accessibilità, provider reali, audit sicurezza, backup/ripristino, patch Joomla corrente e benchmark300studenti/60minuti e500/10minuti. I test concorrenti non dimostrano tale capacità. Il [registro delle prove](IMPLEMENTATION_EVIDENCE.json) conserva build, hash, job e limiti; nessuna accettazione completa M01/AUT-04 è dedotta dalla CI.

## Prossimo passo

Chiudere i residui della [checklist M01](M01_ACCEPTANCE.md), distinguendo implementazioni mancanti e collaudi. Il [piano ordinato](NEXT_STEPS.md) definisce il lavoro successivo e le dipendenze; l'import CSV viene dopo il flusso essenziale dei corsi e richiede i job recuperabili. Privacy e sicurezza accompagnano ogni attività.

## Perimetro e tracciabilità

La specifica funzionale **1.5** e la [matrice dei 210 requisiti](REQUIREMENTS_STATUS.csv) restano il perimetro: 209 requisiti del rilascio 1 e uno differito. Nessun requisito è dichiarato integralmente accettato senza un report completo. Non si ricava una percentuale contando le righe “In corso”.

Schema attuale: **41 tabelle / 58 FK**; 7 estensioni nel pacchetto. Questi conteggi descrivono l'architettura, non l'avanzamento.

Questo documento è il riepilogo corrente. [Roadmap](ROADMAP_TO_STABLE.md), [problemi specifici](IMPLEMENTATION_GAPS.md) e [guide](../README.md#usare-e-configurare-jd-learn) approfondiscono i rispettivi temi; [cronologia](IMPLEMENTATION_HISTORY.md) e [changelog](../CHANGELOG.md) conservano gli incrementi passati.
