# Perimetro della prima versione stabile

**Decisione approvata il 7 novembre 2026.** Questo documento registra il perimetro concordato con il committente per la prima versione stabile di JD Learn. La corrispondenza con requisiti e scenari è dettagliata nella [mappatura del perimetro](RELEASE_SCOPE_MATRIX.md). Ha precedenza nel pianificare il lavoro rispetto alla suddivisione storica delle fasi, senza cancellare o riscrivere la specifica funzionale 1.5 e la matrice dei 210 requisiti. Prima dell’accettazione stabile, i criteri inclusi dovranno essere mappati esplicitamente ai requisiti e agli scenari di collaudo esistenti.

## Prima versione stabile: funzionalità essenziali

- **Gestione corsi semplice e completa:** creare, modificare e aggiornare corsi pubblicati con un flusso familiare a Joomla; aggiornare programma, lezioni e materiali; presentare e pubblicare una nuova revisione didattica preservando storico, versioni assegnate e progressi degli studenti.
- **Programma didattico utilizzabile:** organizzare moduli e lezioni, modificarne ordine e contenuti, gestire testo e video e pubblicare aggiornamenti senza procedure macchinose.
- **Video e materiali funzionanti:** supportare e collaudare i provider video previsti dal progetto — YouTube, Vimeo e Cloudflare Stream — oltre ai media locali previsti; mostrare il player direttamente nella lezione secondo la politica di consenso del sito; gestire i materiali didattici essenziali.
- **Pubblicazione e fruizione:** catalogo e scheda corso, iscrizione/accesso essenziali, aula studente, ripresa del percorso e salvataggio affidabile dei progressi.
- **Gestione essenziale degli iscritti:** elenco per corso con stato dell’iscrizione e informazioni operative di base; operazioni singole necessarie per ammettere, sospendere o riattivare l’accesso, nel rispetto dei permessi e dello storico. Questo elenco resta nella prima versione stabile.
- **Amministrazione intuitiva:** schermate di corsi, programma e docenti conformi ai pattern standard backend Joomla; accesso alle viste native dei campi corso/lezione; lista e form banca domande separati con creazione, duplicazione ed eliminazione delle domande esistenti come eccezione limitata esplicitamente approvata. Navigazione e voci principali di JD Learn nella sidebar Joomla.
- **Requisiti trasversali di rilascio:** installazione e aggiornamento del pacchetto, ACL, protezione dei dati, privacy/consenso, accessibilità, lingua, affidabilità e documentazione necessarie a usare in sicurezza le funzionalità incluse.

## Fase 2: sviluppo sospeso per la prima versione

- **Quiz e banca domande:** le funzionalità didattiche quiz restano congelate. Eccezione approvata l’8 novembre 2026: rendere usabile l’amministrazione della banca esistente con elenco e form separati e azioni di creazione, duplicazione ed eliminazione, usando i pattern standard Joomla (AC-131). Nessun nuovo tipo di domanda, comportamento di valutazione o flusso studente rientra nell’eccezione. Il codice esistente si conserva; restano ammesse correzioni di blocchi, sicurezza e integrità.
- **Attestati:** emissione, modelli, download, verifica e revoca rinviati.
- **Reportistica avanzata:** KPI, analisi dettagliate di frequenza/risultati e report avanzati rinviati. Il roster operativo essenziale descritto sopra rimane incluso.
- **Flussi iscrizioni avanzati:** importazioni massive, approvazioni articolate, edizioni/finestre e reportistica di massa rinviati, salvo una dipendenza dimostrata per i flussi essenziali.

La sospensione è una scelta di priorità, non una rimozione delle funzioni già sviluppate o dei dati. Le attività di manutenzione correttiva, sicurezza, privacy e conservazione dello storico rimangono applicabili anche alle funzioni congelate.

**Decisione dell’8 novembre 2026:** la prova end-to-end con un quinto plugin video e lo sviluppo di ulteriori plugin di streaming passano alla fase 2. Per la prima stabile si verifica il funzionamento dei quattro adapter già distribuiti (YouTube, Vimeo, Cloudflare Stream e HTML5), inclusi installazione/configurazione, consenso, autorizzazioni e progressi.

## Ordine operativo

1. Completare e collaudare la gestione dei corsi e del programma, inclusa la revisione dei corsi pubblicati.
2. Chiudere i provider video e i materiali essenziali con prove su Joomla e account/contenuti reali autorizzati.
3. Completare accesso, aula studente, progressi e gestione essenziale degli iscritti.
4. Rifinire l’esperienza amministrativa e chiudere installazione, upgrade, accessibilità, privacy e qualità di rilascio.
5. Riprendere quiz, attestati e reportistica avanzata solo dopo la prima versione stabile o su una nuova decisione esplicita di priorità.

## Tracciabilità e stato

La specifica funzionale 1.5 e la matrice requisiti/scenari restano la baseline di tracciabilità. Le assegnazioni originarie M01–M13 in `RELEASE_PLAN.json` descrivono la pianificazione storica e non vanno interpretate come promessa che tutte quelle funzioni siano necessarie per il primo rilascio. Le discrepanze di perimetro devono essere risolte con una revisione esplicita della mappatura e degli scenari prima di dichiarare accettata una funzionalità o la versione stabile.

Lo stato implementativo continua a essere registrato in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md); l’ordine di lavoro in [NEXT_STEPS.md](NEXT_STEPS.md); le modifiche approvate ai requisiti in [REQUIREMENTS_CHANGES.md](REQUIREMENTS_CHANGES.md). Una funzione presente nel codice non è per questo automaticamente completa o accettata.
