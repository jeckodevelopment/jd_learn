# Stato attuale di JD Learn

Aggiornato all’**9 novembre 2026**. Main è **0.41.29-dev** dopo il merge della PR [#124](https://github.com/jeckodevelopment/jd_learn/pull/124), che risolve issue [#115](https://github.com/jeckodevelopment/jd_learn/issues/115) / AC-134: il titolo della pagina Opzioni e il gruppo Cronologia versioni sono tradotti in italiano e inglese. Tutti e quattro i job CI sono superati nel [run 42533814214](https://github.com/jeckodevelopment/jd_learn/actions/runs/42533814214); il collaudo manuale Joomla AC-134 resta pendente. La PR [#122](https://github.com/jeckodevelopment/jd_learn/pull/122) integra issue #117 / AC-133: conteggio corsi per docente, link filtrato e filtro Docente nella lista Corsi; CI superata, collaudo manuale pendente. La documentazione AC-134 è nel [testbook](QA_TESTBOOK.md). L’utente ha confermato il collaudo manuale Dashboard l’8 novembre 2026; il prodotto non è pronto per la produzione.

**Controllo del perimetro:** la ricerca roster PR #94 non era nell’elenco precedente delle lacune concordate. È una deviazione di processo; il merge non equivale ad approvazione come requisito stabile. È conservata nel runtime di main e annotata come elemento da riesaminare in [testbook](QA_TESTBOOK.md#pr-94--ricerca-roster-deviazione-di-perimetro-non-gate-di-accettazione). Le prossime implementazioni devono riferirsi a requisiti/scenari e residui già elencati in [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md). Per le viste backend, seguire la regola di uniformità documentata nel [piano UX](ADMIN_UX_PLAN.md#regola-di-uniformità-delle-viste-joomla).

L’addendum requisiti dell’8 novembre aggiunge AC-129–134 per accesso a com_fields, viste docenti/banca domande e dashboard. AC-129 è implementato dalla PR #104; AC-132 è implementato dalla PR #108 e corretto dalla PR #110. AC-130 è implementato dalla PR #116; AC-131 è implementato dalla PR #120 in main 0.41.27-dev: elenco e form banca domande separati, con azioni amministrative circoscritte e CI completa superata. Il collaudo manuale AC-132 Dashboard è superato secondo conferma utente; AC-129 e AC-130 restano da collaudare manualmente; AC-133 è integrata nella 0.41.28-dev dalla PR #122 e il relativo collaudo manuale resta nel testbook. Issue #115 / AC-134 è integrata dalla PR #124 in 0.41.29-dev: titolo della pagina Opzioni e label Cronologia versioni tradotti in IT/EN; CI superata, collaudo manuale pendente. L’eccezione quiz è limitata alla gestione amministrativa descritta.


La gestione corsi, programma, video/materiali, aula e iscrizioni comprende i percorsi già descritti nella tabella sotto. Main include anche i percorsi formativi della 0.41.17. Per dettagli cronologici delle PR e verifiche, consultare [CHANGELOG.md](../CHANGELOG.md) e il [testbook manuale](QA_TESTBOOK.md). Il collaudo Joomla reale resta da registrare per i flussi runtime; la CI non equivale ad accettazione.

## Avanzamento stimato verso la prima versione stabile

Le percentuali indicano la completezza implementativa stimata rispetto al perimetro approvato; non equivalgono ad accettazione manuale in Joomla.

| Area | Avanzamento |
|---|---:|
| Corsi, versioni e programma (M01/M02) | 68% |
| Catalogo e percorsi formativi (CAT-03/SCR-04/EDGE-04) | 57% |
| Fruizione e progressi core (M03/M09) | 55% |
| Video e materiali essenziali (M04) | 80% |
| Iscrizione e roster essenziale (M06/M09) | 67% |
| Usabilità amministrativa e integrazioni Joomla | 84% |
| Collaudo e preparazione release (M10–M12) | 58% |
| **Avanzamento complessivo stimato** | **67%** |

Metodo: media semplice delle sette aree incluse nella prima versione stabile riportate sopra, arrotondata all'intero più vicino (469/7 = 67%). Fase 2 esclusa. La percentuale misura implementazione stimata, non accettazione manuale; tutte le prove Joomla reali restano conteggiate separatamente come pendenti.

## Funzioni presenti e lavoro residuo

“Disponibile” significa che esiste un flusso operativo con prove parziali. Non significa che l'intero requisito sia accettato. “Da implementare” indica una funzione assente, anche quando ne esistono schema o specifiche.

| Area | Disponibile oggi | Da implementare o completare |
|---|---|---|
| Amministrazione corsi, docenti e campi | Elenco/editor corsi secondo convenzioni articoli Joomla; accesso nativo a `com_fields` e contesti corso/docente/lezione; PR #104 centralizza l'accesso nella sidebar; PR #108 in 0.41.23 apre la dashboard con sei riquadri e ACL; PR #110 in 0.41.24 corregge menu root, URL e introduzione; PR #112 in 0.41.25 aggiunge la voce Dashboard in cima alla sidebar; PR #116 in 0.41.26 rende la lista Docenti conforme a Search Tools Joomla (AC-130); PR #122 in 0.41.28 aggiunge conteggio corsi e filtro docente (AC-133); la PR #124 in 0.41.29 corregge titolo pagina Opzioni e label Cronologia versioni in IT/EN (AC-134); PR #120 in 0.41.27 separa la lista banca domande dal form (AC-131) | Collaudi manuali issue #99/AC-129 e AC-130, collaudo AC-133, ACL/lifecycle/snapshot campi; collaudo AC-131 pendente; ricollaudo issue #105/AC-132 già superato secondo utente |
| Banca domande | Banca a revisioni e composizione quiz esistenti | PR #120 / 0.41.27-dev: elenco Search Tools separato dal form, crea/duplica/elimina e blocco cancellazione per dipendenze quiz/tentativi (AC-131); CI completa superata, collaudo manuale Joomla pendente. Altre nuove funzioni didattiche quiz restano fase 2 |
| Programma | Moduli e lezioni testo/video/quiz; editor contestuale singolo; duplicazioni autonome di corso/modulo/lezione; spostamento fra moduli della bozza; trascinamento e Su/Giù; recupero input dopo errori e avviso modifiche non salvate; editor delle regole supportate, soglia video e finestre temporali dei materiali della bozza | Lezioni audio/documento autonome; prerequisiti, sequenze e rilascio progressivo |
| Ciclo editoriale | Bozza, precontrollo con blocchi/avvisi distinti nella 0.41.10 e controlli video/materiali nella 0.41.11–0.41.12; aggiornamento in nuova versione immutabile; recupero storico con audit; 0.41.14: migrazione esplicita singola dal roster con mappatura, anteprima/hash, conferma/motivo, trasferimento dei soli completamenti non quiz e provenienza. La fonte resta nello storico; tentativi aperti, destinazione esistente o edizione non disponibile bloccano l’azione | Revisione/archivio completi, anteprima interattiva e dei livelli senza effetti; collaudo manuale della migrazione e dei casi negativi |
| Catalogo, categorie e percorsi | Catalogo paginato, categorie/antenati, schede autorizzate, menu/SEF; 0.41.17 aggiunge gestione percorsi ordinati, viste pubbliche, accesso/stato corsi e progresso aggregato, test automatici superati | Collaudo manuale del percorso, SEO/ricerca completi, lifecycle categorie e associazioni pubblicate |
| Aula e area personale | Fruizione protetta, progressi persistenti, storico del proprietario, filtri e ripresa autorizzata; fallback R-05 corretto nella 0.41.5; la 0.41.7 rende i campi lezione congelati | Attività dettagliate e collegamento agli attestati; collaudo manuale di ripresa ancora richiesto |
| Video | Quattro plugin base: YouTube, Vimeo, Cloudflare Stream e HTML5; registry, controlli di pubblicazione/fruizione, riepilogo plugin e loader asset estendibili senza patch (0.41.15); metadati automatici nell’editor, provenienza/ricevute e tolleranza con denominatore osservato; consenso, copertura/ripresa, soglia video configurabile per versione con default90%, API/token Stream e MP4 privati con Range/Nginx | Collaudo completo di acquisizione/scarti con provider e browser reali (R-06); funzionalità estendibile di un quinto provider (fase 2), sottotitoli/trascrizioni/preferenze complete, firma locale/cache Stream e configurazioni restanti |
| Materiali | Allegati distinti corso/lezione, upload validato, quarantena/scanner opzionale, storage privato e download autorizzato; preflight coerente con stato/scansione/preview e date iniziali/finali nel fuso del corso (0.41.13 in CI) | Riuso/anteprima/metadati e lifecycle completi, offload, pulizia e conservazione |
| Iscrizioni | Avvio automatico, elenco amministrativo, ammissione singola con anteprima/conferma/audit, preassegnazione esplicita senza concedere accesso, stato della singola iscrizione; ritiro dal catalogo senza revoca dell’aula per iscritti autorizzati (0.41.6); comandi R-03 separati di chiusura/apertura ammissioni e sospensione/ripresa erogazione con anteprima, motivo e storico (0.41.9 integrata) | Collaudo manuale Joomla reale R-03; CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive e proroghe |
| Quiz | Banca/revisioni, composizione/pool/snapshot, tentativi e autosave/CAS, valutazione/feedback, scadenza server, tempi individuali, annullamenti e proroghe motivati | Rivalutazioni, disponibilità e anomalie complete, collegamento agli attestati e rifinitura dell'interfaccia |
| Attestati | Schema e specifiche; **nessun flusso operativo** | Modelli/PDF, idoneità ed emissione, download, verifica opzionale, revoca/sostituzione |
| Processi e notifiche | Task Scheduler per scadenze quiz; audit nativo su quiz/iscrizioni/recupero programma | Job generali recuperabili, outbox/retry, email/Mail Templates e pulizia programmata |
| Report | Elenco iscritti con filtri, stato, avanzamento e date | Aggregati/KPI, dettagli studente/quiz/attestati e CSV protetti |
| Integrazioni Joomla | Identità/sessioni/CSRF, ACL/asset, categorie, form, WAM, menu, override e lingue nei flussi presenti; plugin Privacy con export/rimozione minimizzata; PR #102 abilita il plugin all'installazione e carica il dominio lingua nel contesto Joomla; regressioni CI installazione e stringhe superate | Ricollaudo Joomla 0.41.21 per prima install, upgrade, IT/EN e flusso privacy; retention; Finder, Tags, copertura completa Fields/History/Media/lifecycle e audit generale |
| Installazione e rilascio | Build riproducibile, installazione/upgrade/reinstall con conservazione dei dati verificati dalla suite | Update server, backup/ripristino, patch Joomla corrente, integrazione reale con estensione abbonamenti e documentazione di rilascio |

Gestione corsi e funzionamento operativo di YouTube, Vimeo e Cloudflare Stream sono la priorità corrente condivisa. Estensioni avanzate dei provider restano separate: ogni difetto che impedisce di pubblicare o fruire un corso video va affrontato subito.

## Verifiche disponibili e limiti

Le procedure manuali per PR runtime recenti sono nel [testbook](QA_TESTBOOK.md). Dove la PR storica non aveva una procedura, il testbook lo segnala e ne mantiene il collaudo aperto fino alla trascrizione da criteri approvati.

La [CI0.41.8 (PR #68)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440) ha superato i quattro job standard: controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. La prova browser è una fixture controllata e non sostituisce il collaudo manuale della dashboard nel Joomla reale. La [CI0.41.7 (PR #65)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42420998171) e la [CI0.41.6 (PR #64)](https://github.com/jeckodevelopment/jd_learn/actions/runs/42414492267) restano nel registro delle prove precedenti.

La [CI0.41.3](https://github.com/jeckodevelopment/jd_learn/actions/runs/42405954861) ha superato i quattro job standard: per ambiente, 23 controlli di dominio e 11 HTTP sulla banca/quiz. Il test usa il checkbox del markup nativo, verifica valore1 e salvataggio Vero/Falso e Scelta singola. Il caso reale sul sito dell’utente resta da verificare. La [CI0.41.2](https://github.com/jeckodevelopment/jd_learn/actions/runs/42404631048) ha verificato i messaggi di validazione. La CI0.41 precedente (vedi registro prove) ha superato i quattro job standard sul codice `dd2b5ba8ca5489909f0968c0cad3c15c2975a8fc`:

- PHP8.3/MySQL8.4 e PHP8.4/MariaDB10.6: intera suite implementata, build riproducibile, installazione Joomla, upgrade0.40.1→0.41, reinstallazione e storico conservato. Per ambiente, 29 nuovi controlli nativi, 20 HTTP e 1 retained sui campi editoriali pubblici. Provati plugin testo/textarea/lista, posizioni e default, accesso campo/gruppo/corso e revoca nella stessa sessione, lingua/categoria, aggiornamento editoriale e override nativo; versione assegnata e progressi invariati. Le regressioni video e del menu rimangono incluse.
- Chromium: 12 controlli su fixture desktop/mobile delle modifiche non salvate. Non è collaudo interattivo dell’intero Joomla, del form regole o dei nuovi campi pubblici.
- Job leggero: politica CI, build, struttura, traduzioni e tracciabilità. Le bozze preparatorie hanno eseguito soltanto questi controlli; la verifica finale usa la [matrice standard](CI_POLICY.md). Le due coppie incrociate restano da eseguire nella matrice completa prima di milestone/rilascio o modifiche di compatibilità.

La registrazione successiva degli esiti modifica soltanto documentazione esterna al pacchetto: runtime e pacchetto restano quelli verificati. Nessuna ripetizione automatica al merge. La matrice completa precedente è conservata nel [registro delle prove](IMPLEMENTATION_EVIDENCE.json), con i propri limiti.

Prima pubblicazione e YouTube sono stati confermati dall’utente sul percorso provato. Restano collaudo completo browser/mobile/tastiera/accessibilità e dei provider/SDK reali, audit sicurezza, backup/ripristino, patch Joomla corrente e benchmark di 300 studenti/60 minuti e 500/10 minuti. Le prove di telemetria server e i test concorrenti non sostituiscono queste verifiche. M01 resta aperto.

## Prossime attività

Seguire l’ordine e i confini già approvati in [NEXT_STEPS.md](NEXT_STEPS.md), [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md). Il [testbook Joomla](QA_TESTBOOK.md) raccoglie i collaudi manuali aperti. Le attività di fase 2 (fra cui importazioni massive, attestati e analisi avanzate) non vanno anticipate nel lavoro sul primo rilascio.

## Perimetro e tracciabilità

La specifica funzionale **1.5** e la [matrice dei 210 requisiti](REQUIREMENTS_STATUS.csv) restano il perimetro: 209 requisiti del rilascio 1 e uno differito. Nessun requisito è dichiarato integralmente accettato senza un report completo. Non si ricava una percentuale contando le righe “In corso”.

Schema attuale: **42 tabelle / 59 FK**; 8 estensioni nel pacchetto candidato. Questi conteggi descrivono l'architettura, non l'avanzamento.

Questo documento è il riepilogo corrente. [Roadmap](ROADMAP_TO_STABLE.md), [problemi specifici](IMPLEMENTATION_GAPS.md) e [guide](../README.md#usare-e-configurare-jd-learn) approfondiscono i rispettivi temi; [cronologia](IMPLEMENTATION_HISTORY.md) e [changelog](../CHANGELOG.md) conservano gli incrementi passati.
