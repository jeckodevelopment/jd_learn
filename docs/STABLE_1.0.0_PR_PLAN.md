# Piano delle Pull Request verso JD Learn 1.0.0

Aggiornato al **10 novembre 2026**. Questo è il piano operativo per completare la prima versione stabile. Il perimetro funzionale è quello approvato in [RELEASE_SCOPE.md](RELEASE_SCOPE.md) e [RELEASE_SCOPE_MATRIX.md](RELEASE_SCOPE_MATRIX.md); la tracciabilità puntuale resta in [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) e nel catalogo dei test.

Il piano ha due blocchi in quest’ordine:

1. **IMP — implementazioni dei requisiti inclusi nella 1.0.0**.
2. **STAB — correzioni delle issue e delle regressioni emerse durante i collaudi**, da integrare dopo le PR implementative e prima del gate finale di rilascio.

Gli identificativi IMP e STAB sono riferimenti stabili del piano, non numeri di PR. Le PR effettive vanno aperte nell’ordine indicato, assegnate alla milestone GitHub `1.0.0` e collegate agli ID dei requisiti coperti.

## Regole di pianificazione

- Prima di ogni PR, confrontare il suo perimetro con lo stato di `main` e con le evidenze aggiornate. Se una parte è già implementata, non duplicarla: aggiornare la PR per coprire solo il residuo, oppure registrare l’ID come implementato con evidenza.
- “In corso” nella matrice non significa che tutta la funzione manchi: distinguere implementazione, collaudo automatizzato e accettazione Joomla manuale.
- Ogni PR runtime deve indicare requisiti e criteri di accettazione, includere la sezione **Comportamenti da testare** con prove concrete nel sito Joomla e aggiornare stato, changelog, testbook e guide pertinenti.
- Dopo ogni merge, pubblicare il link al pacchetto installabile verificato e la stima aggiornata dell’avanzamento verso la 1.0.0. Il collaudo manuale resta separato dall’esito CI.
- Non introdurre requisiti non approvati. Quiz didattici avanzati, attestati, report/KPI avanzati, export/import CSV, operazioni massive e ulteriori provider video restano fuori da questo rilascio. Restano incluse la gestione essenziale degli iscritti e l’eccezione amministrativa AC-131 per la banca domande.
- Tutte le PR implementative e di stabilizzazione appartengono alla milestone `1.0.0`. Credentials è un progetto successivo alla 1.0.0 e mantiene la propria nomenclatura e pianificazione separate.

IMP-05 è in corso: PR [#198] ha integrato la pulizia batch limitata delle sessioni video scadute. Restano l’accettazione reale dei quattro provider e gli eventuali residui implementativi confermati; non si introduce un quinto provider.

IMP-06: PR [#202] integrata in 0.41.54-dev con pulizia Scheduler batch degli asset locali orfani dopo 24 ore; CI 4/4 superata. Restano il collaudo sul sito Joomla e gli eventuali residui degli altri lifecycle materiali.

IMP-07 / PR [#204](https://github.com/jeckodevelopment/jd_learn/pull/204) è integrata in 0.41.55-dev: Smart Search indicizza esclusivamente titolo/sommario delle schede pubbliche idonee e aggiorna l’indice per corsi e categorie; CI standard 4/4 superata, collaudo Joomla pendente. PR [#206](https://github.com/jeckodevelopment/jd_learn/pull/206) integra ricerca su titolo/sommario e ordinamento in 0.41.56-dev. PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integra metadati canonical/social pubblici in 0.41.57-dev, merge `32215c493fe1d7a2bf99871b5f93087a842563bd`, CI 4/4 nel run 42905972598; restano alias/canonicalizzazione storica, altri residui catalogo/percorsi e accettazione Joomla.

## Blocco IMP — implementazione

| Ordine | ID / priorità | Titolo e perimetro della PR | Requisiti e scenari | Dipendenze / uscita |
|---:|---|---|---|---|
| 1 | **IMP-01 · P0** | **Completamento residui M01 — editor e dati del corso.** Chiudere soltanto i requisiti ancora assenti nel percorso amministrativo del corso: dati, docenti, calendario, campi e ACL, con salvataggi coerenti con Joomla. Non ripetere CRUD, toolbar, fuso globale o campi già presenti. | AUT-01, AUT-04, AUT-05, CORE-10, SCR-13; AC-119–127 e AC-129 dove pertinente. | Base per l’aggiornamento editoriale e per le prove d’accettazione M01. Esito: ogni criterio incluso è mappato a codice o a evidenza preesistente, senza lacune non classificate. |
| 2 | **IMP-02 · P0** | **Aggiornamento sicuro dei corsi pubblicati.** Completare i residui del ciclo bozza/revisione/pubblicazione e dell’aggiornamento del programma in una nuova versione. Preservare la versione assegnata, lo storico e i progressi degli iscritti; migrazioni solo esplicite, con anteprima e conferma. | AUT-02, AUT-03, AUT-04, DAT-01, DAT-02, LIFE-01, EDGE-11, SCR-13; AC-20, AC-48, AC-53, AC-70, AC-80, AC-86, AC-96, AC-119–127. | Dipende da IMP-01. Esito: modifica e pubblicazione non alterano silenziosamente le iscrizioni o le versioni già in uso. |
| 3 | **IMP-03 · P1** | **Anteprima studente interattiva in sola lettura.** Completare l’anteprima autorizzata del corso usando il contesto di fruizione previsto, senza creare iscrizioni, tentativi, statistiche o progressi e senza esporre contenuti non autorizzati. | AUT-02, AUT-03, STU-02, SCR-06–08; AC-20, AC-80, AC-86, AC-119–127, secondo la tracciabilità puntuale in matrice. | Dipende da IMP-02. Esito: anteprima realistica e verificabile, priva di effetti persistenti e soggetta ad ACL/accesso. |
| 4 | **IMP-04 · P1** | **Completamento della fruizione core e dei progressi.** Chiudere i residui delle lezioni testo, documento, audio e video, completamento delle attività non quiz, sequenziamento/rilascio già previsto, ripresa e coerenza del progresso. Applicare le regole solo ai contenuti usati dal corso. | AUT-03, LES-01–04, PRO-01–06 (solo attività non quiz), STU-01–04, SCR-06–08; AC-02, AC-05, AC-07–08, AC-30, AC-36–37, AC-43–44, AC-52, AC-66–67, AC-81, AC-89, AC-91, AC-95. | Dipende da IMP-02. Esito: percorso studente completo per le attività core, con stato persistente e accessi protetti. |
| 5 | **IMP-05 · P1** | **Completamento tecnico dei quattro provider video.** Chiudere soltanto i residui del contratto adapter/capacità, configurazioni, sostituzione/versionamento e integrazione lato player per YouTube, Vimeo, Cloudflare Stream e HTML5 locale. Prevedere prove end-to-end con SDK/account e browser reali. Nessun quinto provider. | VID-01–08, PVD-01–06, PBASE-01–04, PRO-04/06, SCR-08; AC-25, AC-27–31, AC-91, AC-93, AC-95, AC-97–103, AC-107, AC-109, AC-117–118. | IMP-04. Il collaudo reale dei quattro provider è inoltre un gate di accettazione IMP-10; mock e CI non lo sostituiscono. |
| 6 | **IMP-06 · P1** | **Completamento del ciclo dei materiali.** Chiudere i residui di gestione e fruizione degli allegati corso/lezione: ambiti multipli, riuso e sostituzione in bozza, anteprima/metadati, scansione, conservazione e cleanup, rispettando storage e accesso privato. | MAT-01–08, DAT-04, SCR-16, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. | IMP-02 e IMP-04. Esito: lifecycle verificato senza esporre file a utenti non autorizzati né invalidare versioni pubblicate. |
| 7 | **IMP-07 · P1** | **Completamento catalogo, percorsi e ricerca/SEO core.** Chiudere lifecycle e associazioni delle categorie, presentazione dei percorsi, URL e metadati, Smart Search/Finder e protezione dei contenuti non indicizzabili. | CAT-01–04, SCR-01, SCR-04, EDGE-04, CORE-01, CORE-06–07, CORE-13; AC-03, AC-16, AC-26, AC-32, AC-41–44, AC-60, AC-69, AC-79, AC-85, AC-89–91, AC-93. | Dipende da IMP-02 e si coordina con IMP-08 per menu, lingua e integrazioni Joomla. Esito: catalogo e percorsi coerenti con ACL, lingua e indicizzazione prevista. |
| 8 | **IMP-08 · P1** | **Iscrizione e roster essenziali.** Completare il sottoinsieme incluso: iscrizione automatica/manuale singola, accesso, stati attivo/sospeso, riattivazione, date e roster per corso con avanzamento. Conservare storico e progressi. Non includere CSV, approvazioni articolate, edizioni, scadenze formative configurabili o KPI/export. | ISC-01 (sottoinsieme senza import), ISC-02 (sottoinsieme stati essenziali), ISC-04–08, LIFE-01–03, LIFE-09, REP-02 (parziale), SCR-14; AC-02, AC-11–12, AC-25–26, AC-40, AC-53–54, AC-60, AC-63, AC-128. | Dipende da IMP-04. Esito: l’operatore vede e gestisce solo i dati del corso autorizzato; nessuna concessione implicita di accesso. |
| 9 | **IMP-09 · P1** | **Chiusura delle integrazioni e dei requisiti trasversali Joomla.** Completare solo i residui di ACL, privacy, lingue, installazione/upgrade e interoperabilità Joomla che bloccano il perimetro incluso. Non ripetere implementazioni già presenti per AC-129–134: trattare i loro collaudi manuali nel testbook. | CORE-01–08, CORE-10, CORE-13, CORE-15–17, CORE-20–23, PRI-01–03, JOO-01–05, ACL-DET-01/02/04, SECDEV-01–08, HTTP-01–06; AC-15, AC-63, AC-129–134 dove applicabile. | È trasversale; ogni PR precedente deve già applicare ACL, privacy, sicurezza e localizzazione pertinenti. Esito: installazione pulita e upgrade conservano dati e preferenze, e i flussi inclusi rispettano gli strumenti Joomla. |
| 10 | **IMP-10 · P1 / gate** | **Pacchetto e accettazione tecnica della release.** Chiudere guide, checksum/changelog, update e ripristino, matrice di installazione/upgrade, audit sicurezza, accessibilità, browser/provider e benchmark sul profilo approvato. Nessuna capacità prestazionale si dichiara senza evidenze misurate. | REL-01–04, PERF-01–02, UI-01–07, ACC-01, R-04, R-06 e scenari applicabili della matrice; AC-01–134 limitatamente al perimetro approvato. | Dopo IMP-01–09. È il gate di accettazione implementativa: ogni requisito incluso deve avere esito riferito al build; le anomalie vanno registrate e passano al blocco STAB. |

### Confini espliciti del blocco IMP

- In IMP-04 e IMP-05 i quiz non diventano prerequisito per pubblicare o completare un corso che non li usa.
- In IMP-08 si implementa il roster operativo ristretto di AC-128, non la reportistica completa di REP-02.
- In IMP-09 la gestione amministrativa AC-131 è l’unica eccezione quiz approvata. Non aggiungere tipi di domanda, valutazioni, flussi studente o ampliamenti della banca.
- Le trascrizioni audio, il quinto plugin video, attestati, KPI/report avanzati, export/import CSV, operazioni massive, notifiche/job non indispensabili, sblocco sequenziale tra corsi e funzioni Credentials non sono inclusi, salvo diversa decisione di prodotto formalizzata.

## Blocco STAB — correzioni dopo le implementazioni

Le issue sotto risultano aperte su GitHub alla data del piano. Prima di aprire la relativa PR, verificare se una PR già integrata le abbia risolte nel build corrente. Se non sono riproducibili, documentare l’evidenza e chiudere la issue senza introdurre una correzione superflua.

| Ordine | ID / priorità | Issue | Perimetro della correzione e uscita |
|---:|---|---|---|
| 1 | **STAB-01 · P1** | [#149](https://github.com/jeckodevelopment/jd_learn/issues/149), [#150](https://github.com/jeckodevelopment/jd_learn/issues/150) | Verificare ordine dashboard, stato dei quattro plugin video e traduzione del menu root. Correggere nella stessa PR solo se le prove confermano un problema comune di navigazione/dashboard; altrimenti separare. Collaudare ruoli, IT/EN e URL dirette. |
| 2 | **STAB-02 · P1** | [#155](https://github.com/jeckodevelopment/jd_learn/issues/155), [#156](https://github.com/jeckodevelopment/jd_learn/issues/156) | Verificare filtri Search Tools della banca domande e label della colonna Azioni. Correggere come PR unica solo se riguarda la stessa vista e lo stesso ciclo di collaudo. Conservare i confini AC-131: nessun ampliamento didattico quiz. |
| 3 | **STAB-03 · P1 · completato** | [#172](https://github.com/jeckodevelopment/jd_learn/issues/172), [PR #192](https://github.com/jeckodevelopment/jd_learn/pull/192) | Corretto il selettore del fuso globale per nuovi corsi; risoluzione nel fuso IANA del sito al salvataggio. CI 4/4; collaudo Joomla pendente nel testbook. |
| 4 | **STAB-04 · P2** | [#173](https://github.com/jeckodevelopment/jd_learn/issues/173) | Accorciare la label “Gestisci campi aggiuntivi delle lezioni” in “Campi aggiuntivi” e verificare lingue, accessibilità e resa nella toolbar. |
| 5 | **STAB-05 · P0/P1** | Issue e regressioni registrate durante i collaudi IMP | Dopo la prova manuale di ciascuna funzione, correggere difetti riproducibili che impediscono un requisito incluso o ne compromettono sicurezza/integrità. Una issue per PR, salvo difetti strettamente correlati. Ogni correzione deve collegare requisito, build, passi di riproduzione e prova di regressione. |

STAB-01–05 non autorizzano nuove funzionalità. Un’anomalia che richieda una decisione di prodotto, allarghi il perimetro o contraddica un requisito va registrata separatamente e non inserita implicitamente in una PR di stabilizzazione.

## Gate di uscita verso la 1.0.0

La release è candidata solo quando:

1. IMP-01–09 sono implementate per il sottoinsieme approvato; IMP-10 ha raccolto le evidenze richieste.
2. STAB-01–05 sono risolte oppure ogni issue non riproducibile/non bloccante ha una decisione ed evidenza esplicita.
3. La matrice requisiti/test e il testbook riportano build, esito e limiti; nessuna prova manuale Joomla è marcata superata sulla sola base della CI.
4. Il pacchetto finale è installabile, aggiornabile e accompagnato da checksum, changelog e guide aggiornate.
5. Nessuna funzione di fase 2 o Credentials viene conteggiata come requisito della 1.0.0.

## Stato corrente del piano

Aggiornato al 10 novembre 2026: main è **0.41.57-dev** e include le PR #190, #192, #194, #196, #198, #202, #204, #206 e #207; la CI standard della #207 è 4/4 superata nel [run 42905972598](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598). IMP-01 è stato ricognitivamente mappato; IMP-02 è implementato con accettazione Joomla pendente; IMP-03 e IMP-04 hanno i residui documentati nel testbook; IMP-05 e IMP-06 sono integrati e hanno ancora collaudi manuali aperti. La stima corrente è **74%** secondo IMPLEMENTATION_STATUS (516/7; area catalogo 62%); il collaudo Joomla resta pendente. IMP-07 continua per i residui SEO, catalogo e percorsi.

## Aggiornamento 10 novembre 2026 — IMP-07 / PR #207

PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integrata in 0.41.57-dev: metadati canonical/Open Graph/Twitter per viste pubbliche e noindex per contenuti non accessibili agli ospiti. CI standard 4/4; collaudo Joomla pendente nel testbook. Pacchetto [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462). Avanzamento stimato: **74%** (516/7).

## Aggiornamento 10 novembre 2026 — STAB-02

La PR [#194](https://github.com/jeckodevelopment/jd_learn/pull/194) integra i filtri e l’intestazione della Banca domande per le issue #155/#156, milestone **1.0.0**. CI 4/4 superata; collaudo Joomla manuale ancora pendente. Avanzamento globale: **73%**.


## Aggiornamento 10 novembre 2026 — IMP-04 / progresso audio

La PR [#196](https://github.com/jeckodevelopment/jd_learn/pull/196) completa un residuo di recupero/ripresa audio: retry limitato e singolo per errori transitori, errore permanente visibile, nessun effetto sul completamento o sulla percentuale. CI 4/4; collaudo Joomla pendente nel [testbook](QA_TESTBOOK.md#pr-196-integrata--retry-del-progresso-audio). Proseguire con gli altri residui IMP-04 e IMP-05 secondo il piano.
