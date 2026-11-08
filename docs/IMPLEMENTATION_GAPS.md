# Problemi specifici e differenze aperte

Stato all’**8 novembre 2026**: main **0.41.20-dev** (PR #100 integrata, CI originale superata). La regressione ha trovato che il package script non attivava Privacy. Un run successivo ha confermato l'attivazione ma si è fermato perché il fixture CLI non aveva inizializzato una lingua Joomla: non è prova di un ulteriore difetto in produzione. PR #102 sposta l'hook nel manifest plugin, esplicita i percorsi lingua e inizializza en-GB nel fixture; CI completa della revisione aggiornata ancora da superare. I collaudi Joomla restano aperti. La ricerca roster della PR #94 è una deviazione rispetto al perimetro concordato, da riesaminare e non da considerare requisito aggiunto. Questo registro tratta casi specifici; l'elenco completo è nello [stato attuale](IMPLEMENTATION_STATUS.md) e nella [matrice](REQUIREMENTS_STATUS.csv).

## Casi aperti

| ID | Requisiti | Differenza attuale | Condizione di chiusura |
|---|---|---|---|
| R-08 | CAT-03, SCR-04, EDGE-04 | Gestione percorsi, vista ordinata e aggregazione uniforme integrate in 0.41.17-dev; CI standard e prove HTTP/upgrade-retention superate nel run standard associato alla main 0.41.18. Collaudo manuale Joomla ancora da eseguire | Verificare ACL, ospiti, corso nascosto, progresso zero e percorso vuoto secondo [testbook](QA_TESTBOOK.md#pr-93--percorsi-formativi-core-catalogo-e-progresso) |
| R-09 | CORE-10, AUT-01, SCR-13, QUI-02 | AC-129 è implementato in main 0.41.19 ma non ancora collaudato manualmente; AC-130/131 restano da implementare. La candidata PR #102 corregge attivazione e caricamento del dominio lingua per PRI-02/CORE-08; suite corrente ancora da superare | Collaudare AC-129 e la correzione privacy PR #102; implementare AC-130/131 secondo [piano UX](ADMIN_UX_PLAN.md) e [testbook](QA_TESTBOOK.md); nessun ampliamento didattico quiz oltre la vista e le azioni amministrative esplicitamente approvate |
| R-02 | PVD-01/06, CORE-23, VID-01, PBASE-03 | Il registry PHP, i flussi di pubblicazione/fruizione/editor e il caricamento convenzionale dell’asset accettano chiavi provider estese (0.41.15-dev). Su decisione dell’8 novembre, un quinto plugin è differito alla fase 2 e non blocca la prima stabile | Fase 2: quinto provider di prova installabile senza patch, con asset/capacità/validazione/WAM autorizzati e completamento centrale invariato. Prima release: collaudo end-to-end dei quattro plugin distribuiti |
| R-03 | EDGE-09, ISC-04 | 0.41.9 integrata (PR #70): controlli separati di ammissione ed erogazione, anteprima, motivo, conferma e storico dedicato; CI standard superata nel run 42428496835; collaudo manuale Joomla reale aperto | Verificare tutti i percorsi automatici/manuali, la sospensione su ogni azione protetta e la ripresa di quiz/progressi conservati; collaudo manuale su Joomla reale e ulteriori criteri baseline |
| R-04 | UI-02, ACC-01, REL-02 | Le prove sugli ID del componente non certificano l'intera pagina Joomla; rilevato in CI un duplicato nativo `guidedtours-popup-content109` | Verifica dell'intero documento nel contesto core/template target e sulla patch corrente, senza modificare il core; collaudo accessibilità completo |
| R-06 | VID-02/03/08, PRO-04/06, AUT-01/04, SCR-13 | Flusso automatico e tolleranza implementati nella 0.39, CI standard42393451244 superata; accettazione reale provider/browser ancora aperta | Collaudare ricevute/consenso, SDK e metadati reali, scarti/percentuali/seek/sessioni e storico nei quattro provider; [contratto](VIDEO_DURATION_PLAN.md). Non ripetere la precedente diagnosi di editor soltanto manuale |

## Incrementi parziali

- **R-03 (0.41.6-dev integrata):** l’accesso di un iscritto non dipende dallo stato, dal livello catalogo o dalla finestra promozionale della scheda; categoria e livello contenuto restano verificati, e l’ammissione continua a richiedere disponibilità catalogo. Il ritiro non sospende i progressi né l’aula video. La 0.41.9 integrata aggiunge le operazioni amministrative tracciate; la CI standard e l’upgrade automatico del pacchetto sono superati nel run 42428496835; resta la prova manuale su Joomla reale. La CI standard della PR #64 è superata; la prova manuale in Joomla resta da svolgere.

## Segnalazioni risolte nel percorso indicato

| R-05 — ripresa dell’attività | Corretto nella 0.41.5: usa l’ultima attività valida; in sua assenza individua la prima obbligatoria non completata e, se il corso è concluso, consente la revisione dalla prima lezione | [CI standard della PR #62](https://github.com/jeckodevelopment/jd_learn/actions/runs/42412135232) superata; prova manuale in Joomla ancora da registrare |

| ID | Risoluzione riscontrata | Cosa rimane separatamente aperto |
|---|---|---|
| R-00 — prima pubblicazione | Comando e diagnostica disponibili; prima pubblicazione confermata dall'utente e coperta dalle regressioni HTTP | Accettazione completa della gestione corsi e del ciclo editoriale; non riaprire il blocco specifico senza una nuova regressione |
| R-01 — preassegnazione singola | Opt-in e avviso consentono l'assegnazione senza concedere gruppi o diritto di fruizione; grant/revoca conserva lo storico | Estendere gli invarianti al CSV e agli altri flussi di iscrizione |

Per chiudere un caso servono comportamento conforme, guida, prove positive/negative e riferimento al build. Una correzione puntuale non accetta automaticamente l'intero requisito. Le evidenze storiche sono conservate nel [registro](IMPLEMENTATION_EVIDENCE.json) e nella cronologia Git.
