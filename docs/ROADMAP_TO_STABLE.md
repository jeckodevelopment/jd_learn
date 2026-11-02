# Percorso di sviluppo verso JD Learn stabile

Aggiornato il1novembre2026 su richiesta dell'utente. Il perimetro è quello della specifica funzionale1.2, progetto tecnico, servizi e plugin video già definiti. La matrice contiene209requisiti del rilascio1 e NOT-03 differito: nessuna funzione viene eliminata per accelerare il rilascio. Vendita e abbonamenti restano esterni come concordato.

Ogni fase è suddivisa in PR piccole ma funzionalmente verificabili. Le fasi indicano l'ordine principale e la responsabilità del requisito; dipendenze tecniche possono richiedere interventi trasversali. Sicurezza, privacy, ACL, lingua, override e upgrade accompagnano tutte le fasi. Il file RELEASE_PLAN.json assegna tutti i210ID una sola volta a una fase primaria; questa assegnazione non rappresenta avanzamento o accettazione.

| Fase | Risultato atteso | Passaggi e condizioni per chiuderla |
|---|---|---|
| M01 — editor corsi | Corso configurabile con tutti i dati previsti e interfaccia comprensibile | a) schede e presentazione (incremento0.21); b) immagini/alt, docenti, dati/metadati, calendario e opzioni pertinenti; c) permessi e associazioni native, errori e salvataggi coerenti. Non esporre comandi fittizi per funzioni ancora assenti. |
| M02 — ciclo editoriale | Bozza, revisione, pubblicazione, archivio e nuove versioni | Anteprima autorizzata senza iscrizioni/statistiche; workflow nativo e transizioni; clonazione completa con riferimenti a quiz/media/materiali corretti; versioni esistenti immutabili e migrazioni solo esplicite con anteprima/equivalenze. |
| M03 — didattica | Programma e regole di completamento completi | Duplicazione/ordinamento con mouse e tastiera; lezioni audio/documento, prerequisiti senza cicli, sequenze/rilascio progressivo, corso consultabile distinto; progresso coerente e fallback Riprendi R-05. |
| M04 — media | Quattro provider base completi e provider aggiuntivi installabili senza patch | Registro capacità/asset estensibile e quinto plugin di collaudo; sottotitoli/trascrizioni, sostituzione delle risorse e consenso; materiali con riuso/lifecycle/scansione/offload; test SDK/provider e browser reali. |
| M05 — quiz | Tutti i flussi di valutazione previsti | Composizione/pool e tipi della baseline, regole e disponibilità, autosave/retry, tempi, feedback, rivalutazioni e anomalie, snapshot/storico intatti; interfaccia accessibile e test completi. |
| M06 — iscrizioni | Ammissioni, richieste, approvazioni, edizioni, scadenze, import/export | Separare nuove ammissioni e fruizione (R-03); operazioni singole/massive con scope e audit; CSV simulato e piano immutabile, batch recuperabili; nessuna assegnazione di gruppi o creazione di password/account. Richiede l'infrastruttura job di M07. |
| M07 — job e notifiche | Esecuzione affidabile e comunicazioni configurate | Infrastruttura prima dei processi massivi di M06 e attestati M08: cursore/lease/retry/idempotenza, outbox, esiti incerti e cleanup; Mail Templates Joomla, lingua/destinatari e opt-in previsti. Nessun invio dentro transazioni didattiche. |
| M08 — attestati | Emissione, download e gestione documenti | Modelli/PDF, dati congelati, idoneità, codice univoco, verifica pubblica opzionale, revoca/sostituzione e anomalie; retry senza duplicati, documento disponibile anche se la mail fallisce. |
| M09 — esperienza e report | Catalogo, percorsi, area studente e report completi | Ricerca/filtri/SEO/azioni coerenti, schede/percorsi e tutti gli stati personali; dettagli studente, KPI/CSV export protetti; personalizzazione mediante ID/classi/override documentati. |
| M10 — Joomla e privacy | Tutte le integrazioni core pertinenti | Privacy capabilities/export/removal e retention da avviare già durante M01–M06; Finder senza contenuti riservati, Tags, Fields/snapshot, Content History, Media Manager, associazioni, categorie, menu, accessi, audit e configurazione. |
| M11 — qualità trasversale | Sicurezza, accessibilità e capacità dimostrate | Applicare da M01: revisione sicurezza, stile/analisi statica/scansioni, contratti HTTP e casi negativi; browser desktop/mobile, WCAG, personalizzazioni; benchmark300studenti/60min e500/10min con p95/errori/integrità misurati. |
| M12 — rilascio stabile | Pacchetto accettato e manutenibile | Tutti gli scenari obbligatori del rilascio1 collegati al build, nessun blocco aperto; patch corrente Joomla, installazione/upgrade, backup/ripristino, integrazione reale con estensione commerciale, guide, checksum, update server e procedura di supporto. |
| M13 — differito | Conservare il perimetro successivo già concordato | NOT-03: promemoria inattività/comunicazioni di gruppo dopo rilascio1. Compiti/live/SCORM/xAPI/LTI e altre evoluzioni mantengono le fasi della specifica, non sono implicitamente incluse nel primo rilascio stabile. |

## Incremento attivo: M01/b3 —0.24

Calendario pubblicazione/fruizione, UTC/DST, snapshot/edizione e disponibilità corrente. Guida CALENDAR e risultati nella PR del build. Successivamente riesaminare M01/c (permessi/opzioni native/errori) prima di M02; nessuna fase integralmente accettata.

## Incremento precedente: M01/b2 —0.23

Profili e associazioni docente, snapshot e filtri correnti/storici; guida TEACHERS e prove nella PR del build. Non completa M01; calendario successivo.

## Incremento precedente: M01/b1 —0.22

Copertina pubblica locale tramite Media Manager, alt/decorazione, titolo e descrizione SEO congelati. Primo sottoinsieme di M01/b: docenti, calendario e configurazione restano da completare. Prove servizio, HTTP e upgrade reale predisposte, risultati nella PR del build. Non dichiarare M01 o CORE-12 accettati.

## Incremento precedente: M01/a —0.21

Campi di presentazione nell'editor Joomla: descrizione estesa HTML filtrato, obiettivi, destinatari, prerequisiti dichiarativi, difficoltà e durata stimata in minuti. Persistenza in params_json.presentation senza nuova tabella; nessun JSON grezzo accettato dai form. Campi omessi da client precedenti conservati; gli altri parametri restano intatti.

Il salvataggio continua a creare una bozza. La pubblicazione congela gli ultimi dati salvati nella presentazione della versione e nel suo hash. La scheda legge esclusivamente la presentazione pubblicata autorizzata; niente dati della bozza, corpi delle lezioni, parametri estranei o informazioni personali. Liste del catalogo e controlli di iscrizione non caricano la descrizione estesa. Le versioni precedenti prive di dettagli rimangono valide e inalterate.

**Verifiche richieste:** persistenza e riapertura delle form, input malformati e dimensioni, HTML attivo, revisione obsoleta, ACL, immutabilità, visibilità della scheda, vecchi client e aggiornamento con dati conservati. I risultati saranno riportati nella PR del build; l'esistenza del test non ne certifica il successo.

Questo incremento non completa tutta M01 né AUT-01/SCR-13. Immagine/alt/docenti, editor completo e altre opzioni restano nei passaggi successivi; descrizione obbligatoria alla pubblicazione e limite breve500caratteri richiedono gestione esplicita dei contenuti preesistenti, senza troncare o invalidare silenziosamente le versioni0.20.1. Il blocco R-00 resta aperto al riscontro sul sito dell'utente.

## Regola di avanzamento

Ogni PR deve fornire comportamento utilizzabile, modello/servizio/controller/vista quando necessari, ACL e validazione server, guida e ID, test positivi/negativi e upgrade pertinente. Dopo la CI si verifica il flusso tramite interfaccia; anomalie segnalate dall'utente restano aperte fino a riscontro. Il lavoro indipendente può proseguire senza fingere che la segnalazione sia chiusa.

Nessuna percentuale derivata da tabelle, classi o test. Un requisito diventa accettato solo con tutti i criteri della sua baseline e un report riferito al build. La versione è stabile soltanto dopo M12:0.21 rimane un pacchetto di sviluppo.
