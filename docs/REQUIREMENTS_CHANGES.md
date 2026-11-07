# Registro delle modifiche ai requisiti

## RF-2026-11-04 — aggiornamento dei corsi e profili docente

**Origine e autorizzazione:** richiesta esplicita del committente del4novembre2026: completare M01, semplificare la modifica dei corsi pubblicati come nell’editor articoli Joomla, usare il versioning nativo, aggiornare lezioni/materiali, associare corso e docente senza gestione di categorie docente, usare campi Joomla per il profilo e consentire pagine/elenco corsi tramite menu.

**Versione vigente:** specifica funzionale1.3, progetto tecnico1.1, matrice requisito-test e catalogo dei collaudi aggiornati. I suffissi1.0 dei file sono storici. I210ID e i relativi perimetri sono conservati; sono estese le descrizioni e le verifiche pertinenti. La precedente specifica1.2 è conservata nella [revisione precedente su GitHub](https://github.com/jeckodevelopment/jd_learn/blob/2705b4d83a47e0e812d07e2fb974d7b281b85a48/docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md). Nessun criterio preesistente viene cancellato per adeguarlo al codice disponibile.

| Indicazione recepita | Requisiti interessati | Prescrizione | Collaudi aggiunti |
|---|---|---|---|
| Modificare normalmente un corso pubblicato | AUT-01/02/04, CORE-16, SCR-13, EDGE-11 | Salva/Salva e chiudi, stato pubblicato conservato, revisione Joomla confrontabile/ripristinabile; niente ritiro o ricreazione obbligatori | AC-119, AC-125 |
| Aggiornare lezioni e aggiungere materiali | AUT-01/02/04, SCR-13, EDGE-11; LES-01 e MAT-06 già pertinenti | Copia completa del programma corrente, modifica/aggiunta ordinaria, pubblicazione autorizzata dell’aggiornamento; versione vigente disponibile durante il lavoro | AC-120, AC-125 |
| Semplificare il docente come riferimento autoriale | CAT-02, CORE-01/04, SCR-13, LIFE-02 | Associazione dalla scheda corso; nessuna categoria docente da creare/selezionare/gestire; eventuali contenitori tecnici non diventano un flusso utente | AC-121, AC-125 |
| Presentazione, email e riferimenti personalizzabili | CORE-10, CORE-04 | Campi/gruppi Joomla nel contesto docente, configurabili dalla segreteria; valori propri modificabili dal docente autorizzato, anche dal sito | AC-122, AC-123 |
| Mostrare docente e suoi corsi da menu | CAT-02, CORE-13 | Pagina docente e Corsi di un docente con selezione del profilo; filtri di pubblicazione/lingua/accesso anche su URL diretto | AC-124 |
| Conservare dati e autorizzazioni | AUT-04, CORE-01/04/10/16, LIFE-02, EDGE-11 | Nessuna migrazione automatica, modifica dei risultati/attestati, pubblicazione dell’email privata o attribuzione di gruppi/permessi dalla sola associazione | AC-119–125 |

La distinzione fra revisione editoriale e versione didattica era già concordata. La richiesta semplifica il percorso di modifica, mantenendo le garanzie sui risultati: il salvataggio promozionale può aggiornare la scheda corrente; lezioni/materiali e requisiti sostanziali si preparano e pubblicano nella nuova versione. La migrazione di iscritti richiede ancora l’operazione esplicita con impatto/equivalenze prevista da AUT-04.

D-10/D-12 sono precisate e D-19–D-22 registrano le nuove scelte. La modifica frontend del profilo docente non autorizza implicitamente un editor frontend di tutti i contenuti didattici. Uno o più docenti restano associabili secondo il dizionario preesistente; l’analogia con l’autore Joomla non introduce una limitazione a un solo docente né converte automaticamente i profili in utenti.

## Stato dell’implementazione distinto dai requisiti

La [PR38](https://github.com/jeckodevelopment/jd_learn/pull/38) ha introdotto il sottoinsieme0.31.0-dev. La [CI41819674307](https://github.com/jeckodevelopment/jd_learn/actions/runs/41819674307), sul commit `2d562a08d186c6f192c7855bba2d3ca1e976ae18`, ha superato i4ambienti PHP8.3/8.4 × MySQL8.4/MariaDB10.6 su Joomla6.0.0, con34verifiche editoriali native e6HTTP per ambiente, oltre a regressioni e conservazione dopo upgrade. Sono prove parziali specifiche: non si attribuisce a quel build la completa esecuzione dei nuovi scenari AC-119–125, del routing menu multilingua, dell’anteprima di impatto o della migrazione.

Lo stato dettagliato resta in REQUIREMENTS_STATUS.csv. CORE-10 è Campi personalizzati, CORE-13 è Menu/routing e CORE-16 è Content History: i tre non sono intercambiabili. AUT-05 riguarda Lingue; la vecchia nota di stato relativa ai workflow è corretta senza cambiare il requisito.

La [checklist M01](M01_ACCEPTANCE.md) indica parti implementate, mancanti e collaudi residui. Questo aggiornamento riguarda i requisiti: non modifica il pacchetto runtime0.31 e non chiude alcuna fase automaticamente.

## RF-2026-11-04-B — amministrazione corsi simile agli articoli Joomla

L’utente segnala che la parte amministrativa rimane estremamente complessa e richiede una vista simile alla gestione articoli. La specifica1.4/progetto tecnico1.2 estendono AUT-01, SCR-13 e CORE-20 con elenco e form secondo le convenzioni Joomla, Search Tools/toolbar/selezione, metadati e accesso diretto al programma conservando il form. AC-126 si aggiunge al catalogo senza eliminare i criteri precedenti. D-23 registra la preferenza. Il sottoinsieme0.33 e la relativa CI sono riportati nella PR del build: non sono accettazione completa di tutte le interazioni browser/mobile/tastiera. M01/h dà priorità a questa indicazione; il riordino con trascinamento/tastiera resta nel passaggio M01/i.

## RF-2026-11-04-C — ricostruzione del programma didattico

Richiesta: «dovremmo ricostruire anche la vista del programma didattico. La modalità attuale è inusabile». Specifica1.5 sezione44, AUT-01/SCR-13/CORE-20 e nuovo AC-127: scaletta contestuale, moduli espandibili, editor singolo, aggiunta nel modulo, materiali/quiz contestuali e azioni secondarie. M01/i implementa ordine mouse/tastiera con protezione da conflitti e spostamento tra moduli della bozza; nessuna mutazione di storico, risultati o gruppi. Tutti i210ID conservati. AC-127 e M01 non dichiarati accettati: le prove automatizzate non sostituiscono il collaudo browser/mobile/accessibilità.

## RF-2026-11-07 — durata automatica dei video

**Origine:** richiesta esplicita del committente: evitare l’inserimento ordinario della durata esatta nell’authoring e gli errori causati da piccoli scarti; preferire l’acquisizione dai metadati del provider/file. La specifica1.5 già prevede lettura da player/API in VID-02 e confronto corretto degli intervalli in PRO-04: la richiesta rende prioritario completare questa parte, senza modificare i210ID o dichiarare accettati criteri mancanti.

Percorso richiesto e collaudi in [VIDEO_DURATION_PLAN.md](VIDEO_DURATION_PLAN.md), caso aperto R-06. Manuale soltanto come ripiego esplicito; misure associate alla sorgente, consenso/permessi, tolleranza limitata coerente con percentuali e salvaguardia delle versioni assegnate. Riguarda anche VID-03/08, PRO-06, AUT-01/04 e SCR-13. Implementazione ancora assente nella0.38 per il flusso esterno completo: nessuna modifica runtime in questo aggiornamento dei requisiti.

## RF-2026-11-07-B — perimetro della prima versione stabile

**Decisione approvata dal committente:** concentrare la prima versione stabile sulle funzionalità essenziali e congelare lo sviluppo di quiz/banca domande, attestati e KPI/reportistica avanzata per la fase 2. L’elenco operativo essenziale degli iscritti rimane incluso. Importazioni e flussi iscrizioni complessi sono differiti salvo dipendenze dimostrate per l’uso essenziale.

Il perimetro, le condizioni e l’ordine operativo sono registrati in [RELEASE_SCOPE.md](RELEASE_SCOPE.md). La specifica 1.5 e i 210 requisiti restano conservati come baseline; le assegnazioni originarie del piano non vengono riscritte implicitamente. Prima dell’accettazione stabile andranno mappati i criteri inclusi agli scenari della baseline, separando il roster essenziale da KPI/export avanzati. Il congelamento consente soltanto correzioni di blocchi, sicurezza e integrità dei dati per le funzionalità rinviate.
