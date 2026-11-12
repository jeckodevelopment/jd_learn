**Stato aggiornato al 12 novembre 2026: main 0.48.0-dev, avanzamento implementativo 81%** (media 80,71%; M01/M02 80%). La candidata 0.49.0-dev implementa l’anteprima pubblica esplicita delle lezioni Testo, con sola lettura e controlli corso/versione/finestra; CI e merge da completare. Accettazione manuale Joomla e gate IMP-10 restano aperti. Metodo e macroaree in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

Piano aggiornato al 12 novembre 2026. Main **0.45.0-dev** integra IMP-08; PR #264 completa la ricerca nei filtri Versione e Iscritto del roster. Il collaudo Joomla è descritto in `docs/QA_TESTBOOK.md` e resta da eseguire. Le note successive documentano lo storico delle PR precedenti.

La PR [#226](https://github.com/jeckodevelopment/jd_learn/pull/226) è integrata in main 0.41.71-dev: sostituisce la consegna video HTML5 che richiedeva Nginx con streaming PHP protetto a blocchi, compatibile con Apache/LiteSpeed. CI standard 4/4 nel [run 43079973855](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855); pacchetto [JD-Learn-0.41.71-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855/artifacts/13885130510), ZIP SHA-256 `3d9dcca6c943da364e6c6f83d6ac45b44f51a8a7a5d34f532da40150b9e21c8f`, wrapper `4ecf79e8fa6d64cc0171412dbc56b4ea26e7ee0f4a48544348fb4a6817e34d33`. Gestisce Range/HEAD e autorizzazioni Joomla; i vecchi valori `x_accel` vengono serviti via PHP. Collaudo Joomla e benchmark concorrente da registrare nel testbook.

La PR #225 aggiunge **Gestione dipendenze categorie** nel backend: conteggio per sezione e ricollocazione esplicita di corsi, percorsi, docenti e domande verso categorie modificabili dello stesso tipo. Le modifiche al solo `catid` sono atomiche e lasciano invariati record/storici; i corsi ricollocati sono reindicizzati quando Smart Search è attivo. Le FK introdotte dalla PR #215 continuano a impedire eliminazioni premature. Collaudo Joomla da eseguire secondo `docs/QA_TESTBOOK.md`; requisiti: CAT-01, CORE-01, LIFE-02.

Aggiornato all’11 novembre 2026. Main **0.41.69-dev** integra la PR [#224](https://github.com/jeckodevelopment/jd_learn/pull/224) per la ricerca nei selettori multilingua (CORE-07), oltre alle PR #221–#223. Pacchetto installabile [JD-Learn-0.41.69-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43057819152/artifacts/13872631084), SHA-256 `54b8667258a833fcd907b3a9767abbd2d7cadbbd8e01fceecd0dda1c86034450`. Avanzamento implementativo **77%** (92% integrazioni Joomla; media 536/7 = 76,57%). CI: controlli documentali e stack PHP 8.3/MySQL + PHP 8.4/MariaDB superati; Chromium è ancora bloccato nell’installazione del runtime. Accettazione manuale AC-20/AC-48 pendente.

Il piano ha due blocchi in quest’ordine:

1. **IMP — implementazioni dei requisiti inclusi nella 1.0.0**.
2. **STAB — correzioni delle issue e delle regressioni emerse durante i collaudi**, da integrare dopo le PR implementative e prima del gate finale di rilascio.

Gli identificativi IMP e STAB sono riferimenti stabili del piano, non numeri di PR. Le PR effettive vanno aperte nell’ordine indicato, assegnate alla milestone GitHub `1.0.0` e collegate agli ID dei requisiti coperti.

La issue #160 chiude il requisito di processo SemVer: applicare [la policy versioni](VERSIONING.md) e i controlli automatici su manifest e release tooling prima della 1.0.0. L’attuale `0.41.81-dev` è storico; le nuove release runtime seguono da subito la regola definita.

## Regole di pianificazione

- Prima di ogni PR, confrontare il suo perimetro con lo stato di `main` e con le evidenze aggiornate. Se una parte è già implementata, non duplicarla: aggiornare la PR per coprire solo il residuo, oppure registrare l’ID come implementato con evidenza.
- “In corso” nella matrice non significa che tutta la funzione manchi: distinguere implementazione, collaudo automatizzato e accettazione Joomla manuale.
- Ogni PR runtime deve indicare requisiti e criteri di accettazione, includere la sezione **Comportamenti da testare** con prove concrete nel sito Joomla e aggiornare stato, changelog, testbook e guide pertinenti.
- Dopo ogni merge, pubblicare il link al pacchetto installabile verificato e la stima aggiornata dell’avanzamento verso la 1.0.0. Il collaudo manuale resta separato dall’esito CI.
- Non introdurre requisiti non approvati. Quiz didattici avanzati, attestati, report/KPI avanzati, export/import CSV, operazioni massive e ulteriori provider video restano fuori da questo rilascio. Restano incluse la gestione essenziale degli iscritti e l’eccezione amministrativa AC-131 per la banca domande.
- Tutte le PR implementative e di stabilizzazione appartengono alla milestone `1.0.0`. Credentials è un progetto successivo alla 1.0.0 e mantiene la propria nomenclatura e pianificazione separate.

IMP-05 è integrato: PR [#198] ha aggiunto la pulizia batch limitata delle sessioni video scadute; PR [#228] completa la coerenza della configurazione Cloudflare Stream rimuovendo i controlli di firma locale non implementati. CI 4/4 nel run 43091365354. L’accettazione reale dei quattro provider resta un gate manuale. Nessun quinto provider.
IMP-07 integra filtri per tag/difficoltà/durata (PR #209/#210), vista griglia/elenco (PR #211) e filtri docente/percorso (PR #213) in main 0.41.62-dev. CORE-09 è implementato dalla PR #222: type alias `com_jlms.course`, content type/mapping UCM e campo Tag nativo nel form corso. I filtri esistenti leggono le associazioni Joomla. Accettazione manuale AC-19/AC-64 ancora aperta. La build 0.41.69-dev include la modifica delle associazioni di corsi pubblicati senza toccare le versioni didattiche (PR #223) e la ricerca per titolo/ID (PR #224). AC-20/AC-48 restano da collaudare in Joomla. La PR #225 completa la ricollocazione esplicita e atomica delle dipendenze categoria; restano i collaudi manuali CAT-01/lifecycle.

IMP-06: PR #202 integra la pulizia Scheduler batch degli asset locali orfani dopo 24 ore; PR #217 completa il riuso dei materiali tra ambiti della stessa bozza; la PR #227 configura la retention. PR #229 aggiunge scansione ClamAV reale al CI con file pulito ed EICAR. Preview/metadati e lifecycle materiali restano residui; acceptance sul sito Joomla pendente. PR #219 aveva introdotto consegna Nginx per gli allegati; la PR correttiva #221 ripristina il flusso PHP protetto compatibile con Apache/LiteSpeed. Build 0.41.66-dev: `dist/pkg_jlms-0.41.66-dev.zip`, SHA-256 `214e311d5cee72963b3e9d01ba958dfa52088b781c52052761e4f1d74ffdc4af`. Restano preview/metadati e conservazione completa; l'accettazione manuale Joomla è pendente.

IMP-07 / PR [#204](https://github.com/jeckodevelopment/jd_learn/pull/204) è integrata in 0.41.55-dev: Smart Search indicizza esclusivamente titolo/sommario delle schede pubbliche idonee e aggiorna l’indice per corsi e categorie; CI standard 4/4 superata, collaudo Joomla pendente. PR [#206](https://github.com/jeckodevelopment/jd_learn/pull/206) integra ricerca su titolo/sommario e ordinamento in 0.41.56-dev. PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integra metadati canonical/social pubblici in 0.41.57-dev. PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208) integra alias SEF per corsi, categorie e percorsi in 0.41.58-dev, merge `6b92612254c060c1ef296c0a03661442871ad1cc`, CI 4/4 nel [run 42910392500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500); la PR #210 completa filtri difficoltà/durata in 0.41.59-dev (CI standard 4/4 nel [run 42913999069](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069)); restano filtri docente/percorso/tag, layout catalogo, lifecycle categorie e accettazione Joomla.

## Blocco IMP — implementazione

| Ordine | ID / priorità | Titolo e perimetro della PR | Requisiti e scenari | Dipendenze / uscita |
|---:|---|---|---|---|
| 1 | **IMP-01 · P0** | **Completamento residui M01 — editor e dati del corso.** Chiudere soltanto i requisiti ancora assenti nel percorso amministrativo del corso: dati, docenti, calendario, campi e ACL, con salvataggi coerenti con Joomla. PR #266 / 0.46.0-dev congela i valori dei campi corso nella versione didattica; PR #268 copre il ripristino dei campi tramite Content History e preserva versione attiva/iscrizioni. Resta il collaudo UI Joomla e la ricognizione delle restanti verifiche. | AUT-01, AUT-04, AUT-05, CORE-10, SCR-13; AC-119–127 e AC-129 dove pertinente. | Base per l’aggiornamento editoriale e per le prove d’accettazione M01. Esito: ogni criterio incluso è mappato a codice o a evidenza preesistente, senza lacune non classificate. |
| 2 | **IMP-02 · P0** | **Aggiornamento sicuro dei corsi pubblicati.** Il ciclo bozza/revisione/pubblicazione e l’aggiornamento del programma in nuova versione sono implementati, inclusa la preservazione di versioni assegnate, iscrizioni e progressi; collaudo Joomla pendente. PR #270 introduce la modalità esplicita di sola consultazione; PR #272 consente video consultabili senza durata/telemetria/progresso; PR #274 aggiunge anteprime pubbliche selettive per lezioni Testo. I tipi non approvati per preview pubblica restano esclusi. | AUT-02, AUT-03, AUT-04, DAT-01, DAT-02, LIFE-01, EDGE-02/11, ISC-04, SCR-13; AC-20, AC-48, AC-53, AC-70, AC-80, AC-86, AC-96, AC-119–127. | Dipende da IMP-01. Esito: modifica e pubblicazione non alterano silenziosamente le iscrizioni o le versioni già in uso. |
| 3 | **IMP-03 · P1 · core implementato, accettazione pendente** | **Anteprima autorizzata in sola lettura.** La preview editoriale core usa il contesto di fruizione senza creare iscrizioni, tentativi, statistiche o progressi e senza esporre contenuti non autorizzati. La PR #235 (0.41.77-dev) mostra anche i materiali di corso disponibili durante lezioni testo/video; quiz restano fuori dal perimetro. Accettazione Joomla manuale ancora pendente. | AUT-02, AUT-03, STU-02, SCR-06–08; AC-20, AC-80, AC-86, AC-119–127, secondo la tracciabilità puntuale in matrice. | Dipende da IMP-02. Esito: anteprima realistica e verificabile, priva di effetti persistenti e soggetta ad ACL/accesso. |
| 4 | **IMP-04 · P1** | **Completamento della fruizione core e dei progressi.** Chiudere i residui delle lezioni testo, documento, audio e video, completamento delle attività non quiz, sequenziamento/rilascio già previsto, ripresa e coerenza del progresso. Applicare le regole solo ai contenuti usati dal corso. | AUT-03, LES-01–04, PRO-01–06 (solo attività non quiz), STU-01–04, SCR-06–08; AC-02, AC-05, AC-07–08, AC-30, AC-36–37, AC-43–44, AC-52, AC-66–67, AC-81, AC-89, AC-91, AC-95. | Dipende da IMP-02. Esito: percorso studente completo per le attività core, con stato persistente e accessi protetti. |
| 5 | **IMP-05 · P1** | **Completamento tecnico dei quattro provider video.** Chiudere soltanto i residui del contratto adapter/capacità, configurazioni, sostituzione/versionamento e integrazione lato player per YouTube, Vimeo, Cloudflare Stream e HTML5 locale. PR #276 / 0.50.0-dev applica la lingua caption salvata all’avvio YouTube/Cloudflare; selettore dinamico solo per Vimeo/HTML5 che espongono le tracce. Restano i collaudi end-to-end reali e altri residui IMP-05. Prevedere prove end-to-end con SDK/account e browser reali. Nessun quinto provider. | VID-01–08, PVD-01–06, PBASE-01–04, PRO-04/06, SCR-08; AC-25, AC-27–31, AC-91, AC-93, AC-95, AC-97–103, AC-107, AC-109, AC-117–118. | IMP-04. Il collaudo reale dei quattro provider è inoltre un gate di accettazione IMP-10; mock e CI non lo sostituiscono. |
| 6 | **IMP-06 · P1** | **Completamento del ciclo dei materiali.** Chiudere i residui di gestione e fruizione degli allegati corso/lezione: ambiti multipli, riuso e sostituzione in bozza, anteprima/metadati, scansione, conservazione e cleanup, rispettando storage e accesso privato. | MAT-01–08, DAT-04, SCR-16, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. | IMP-02 e IMP-04. Esito: lifecycle verificato senza esporre file a utenti non autorizzati né invalidare versioni pubblicate. |
| 7 | **IMP-07 · P1** | **Completamento catalogo, percorsi, tag nativi e ricerca/SEO core.** Chiudere lifecycle e associazioni delle categorie, tag Joomla nativi per i corsi, presentazione dei percorsi, URL e metadati, Smart Search/Finder e protezione dei contenuti non indicizzabili. Non confondere l'associazione nativa dei tag con i filtri già presenti. | CAT-01–04, SCR-01, SCR-04, EDGE-04, CORE-01, CORE-06–07, CORE-09, CORE-13; AC-03, AC-16, AC-19, AC-26, AC-32, AC-41–44, AC-60, AC-64, AC-69, AC-79, AC-85, AC-89–91, AC-93. | Dipende da IMP-02 e si coordina con IMP-08 per menu, lingua e integrazioni Joomla. Esito: catalogo e percorsi coerenti con tag nativi, ACL, lingua e indicizzazione prevista. |
| 8 | **IMP-08 · P1 · implementazione core in chiusura** | **Iscrizione e roster essenziali.** Iscrizione automatica/manuale singola, accesso, stati attivo/sospeso, riattivazione, date e roster per corso con avanzamento sono implementati. La PR in preparazione 0.45.0-dev chiude la lacuna UX dei filtri ricercabili versione/iscritto, limitati al corso. Restano collaudi Joomla e ricognizione puntuale dei criteri; CSV, approvazioni articolate, edizioni e KPI/export restano esclusi. | ISC-01/02/04–08, LIFE-01–03/09, REP-02 parziale, SCR-14; AC-02, AC-11–12, AC-25–26, AC-40, AC-53–54, AC-60, AC-63, AC-128. | Evidenza: `EnrollmentRosterService`, `ManualEnrollmentService`, `EnrollmentStateService` e `REQUIREMENTS_STATUS.csv` segnano completato il sottoinsieme core. I selettori ricercabili sono implementati dalla candidata 0.45.0-dev; dopo il merge, proseguire con le verifiche e i gate IMP-10 senza duplicare funzioni già presenti. |
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
| 1 | **STAB-01 · P1 · implementato da PR #238, accettazione pendente** | [#149](https://github.com/jeckodevelopment/jd_learn/issues/149), [#150](https://github.com/jeckodevelopment/jd_learn/issues/150) | L’ordine dashboard e il riepilogo dei quattro provider dell’issue #149 risultano già implementati. La correzione isolata per #150 aggiunge `COM_JLMS_PATHWAYS` ai cataloghi di sistema IT/EN; resta il collaudo Joomla di menu, ordine, stati provider, ruoli e URL dirette. |
| 2 | **STAB-02 · P1** | [#155](https://github.com/jeckodevelopment/jd_learn/issues/155), [#156](https://github.com/jeckodevelopment/jd_learn/issues/156) | Verificare filtri Search Tools della banca domande e label della colonna Azioni. Correggere come PR unica solo se riguarda la stessa vista e lo stesso ciclo di collaudo. Conservare i confini AC-131: nessun ampliamento didattico quiz. |
| 3 | **STAB-03 · P1 · completato** | [#172](https://github.com/jeckodevelopment/jd_learn/issues/172), [PR #192](https://github.com/jeckodevelopment/jd_learn/pull/192) | Corretto il selettore del fuso globale per nuovi corsi; risoluzione nel fuso IANA del sito al salvataggio. CI 4/4; collaudo Joomla pendente nel testbook. |
| 4 | **STAB-04 · P2 · implementato da PR #241, accettazione pendente** | [#173](https://github.com/jeckodevelopment/jd_learn/issues/173) | Issue #173: la PR [#241](https://github.com/jeckodevelopment/jd_learn/pull/241) / 0.41.79-dev accorcia la label in italiano a “Campi aggiuntivi” e in inglese a “Lesson fields” nei cataloghi componente e sistema; verificare accessibilità, ACL e resa nella toolbar. |
| 5 | **STAB-05 · P0/P1 · issue #199/#200 implementate, accettazione pendente** | Issue #199, #200 e regressioni registrate durante i collaudi IMP | PR #244 / 0.41.80-dev risolve il link Programma duplicato; PR [#247](https://github.com/jeckodevelopment/jd_learn/pull/247) / 0.41.81-dev rimuove i titoli ripetuti in Dashboard e Percorsi formativi, CI 4/4. Eseguire i collaudi manuali Joomla indicati nel testbook. Altre correzioni STAB-05 richiedono una regressione riproducibile e un issue nel perimetro approvato.

STAB-01–05 non autorizzano nuove funzionalità. Un’anomalia che richieda una decisione di prodotto, allarghi il perimetro o contraddica un requisito va registrata separatamente e non inserita implicitamente in una PR di stabilizzazione.

## Gate di uscita verso la 1.0.0

La release è candidata solo quando:

1. IMP-01–09 sono implementate per il sottoinsieme approvato; IMP-10 ha raccolto le evidenze richieste.
2. STAB-01–05 sono risolte oppure ogni issue non riproducibile/non bloccante ha una decisione ed evidenza esplicita.
3. La matrice requisiti/test e il testbook riportano build, esito e limiti; nessuna prova manuale Joomla è marcata superata sulla sola base della CI.
4. Il pacchetto finale è installabile, aggiornabile e accompagnato da checksum, changelog e guide aggiornate.
5. Nessuna funzione di fase 2 o Credentials viene conteggiata come requisito della 1.0.0.

## Stato corrente del piano

Aggiornato al 12 novembre 2026: main **0.48.0-dev** integra la PR [#272](https://github.com/jeckodevelopment/jd_learn/pull/272), con avanzamento **81%** (80,71%; M01/M02 80%). La candidata 0.49.0-dev implementa preview pubbliche selettive per lezioni Testo; CI standard e merge restano da completare. Accettazione manuale Joomla e IMP-10 restano aperti.

## Aggiornamento 10 novembre 2026 — IMP-07 / PR #208

PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208) integrata in 0.41.58-dev: alias SEF ID-alias per corsi, categorie e percorsi, compatibile con percorsi numerici precedenti. CI standard 4/4; collaudo Joomla pendente nel testbook. Pacchetto [JD-Learn-0.41.58-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500/artifacts/13806778546), wrapper `773fb7cd4e200df599ffd1b858cc5ef60d013c8823780b625024afbb4acec8cb`, ZIP `7a4d52cdbbd708b523d6d05f29f0b937fcf5094019a6c9e70ab443bd7a28ade3`. Avanzamento stimato: **74%** (517/7).

## Aggiornamento 10 novembre 2026 — IMP-07 / PR #207

PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integrata in 0.41.57-dev: metadati canonical/Open Graph/Twitter per viste pubbliche e noindex per contenuti non accessibili agli ospiti. CI standard 4/4; collaudo Joomla pendente nel testbook. Pacchetto [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462). Avanzamento stimato: **74%** (516/7).

## Aggiornamento 10 novembre 2026 — STAB-02

La PR [#194](https://github.com/jeckodevelopment/jd_learn/pull/194) integra i filtri e l’intestazione della Banca domande per le issue #155/#156, milestone **1.0.0**. CI 4/4 superata; collaudo Joomla manuale ancora pendente. Avanzamento globale: **73%**.


## Aggiornamento 10 novembre 2026 — IMP-04 / progresso audio

La PR [#196](https://github.com/jeckodevelopment/jd_learn/pull/196) completa un residuo di recupero/ripresa audio: retry limitato e singolo per errori transitori, errore permanente visibile, nessun effetto sul completamento o sulla percentuale. CI 4/4; collaudo Joomla pendente nel [testbook](QA_TESTBOOK.md#pr-196-integrata--retry-del-progresso-audio). Proseguire con gli altri residui IMP-04 e IMP-05 secondo il piano.


## Aggiornamento 10 novembre 2026 — IMP-07 / PR #210

PR [#210](https://github.com/jeckodevelopment/jd_learn/pull/210) e [#209](https://github.com/jeckodevelopment/jd_learn/pull/209) sono integrate in 0.41.60-dev: filtri pubblici per difficoltà, durata e tag nel catalogo e nelle categorie. La #209 ha CI standard 4/4 nel [run 42918303751](https://github.com/jeckodevelopment/jd_learn/actions/runs/42918303751); accettazione Joomla pendente. La PR [#211](https://github.com/jeckodevelopment/jd_learn/pull/211) integra la vista elenco in 0.41.61-dev; CI standard 4/4. La PR [#213](https://github.com/jeckodevelopment/jd_learn/pull/213) integra filtri docente/percorso in 0.41.62-dev; CI standard 4/4. Avanzamento 75% (524/7; catalogo 70%).


## Aggiornamento 11 novembre 2026 — IMP-07 / PR #215

PR [#215](https://github.com/jeckodevelopment/jd_learn/pull/215) completa la protezione del lifecycle categorie: FK su corsi, percorsi, docenti e domande; hard-delete impedito finché ci sono riferimenti; cestinamento/ripristino reversibili. CI 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654). Pacchetto [JD-Learn-0.41.63-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654/artifacts/13835639241), wrapper SHA-256 `dfcfab154b2425a1c862a3680315aecb9c633abdab249c6f7c66af93e0a9feb0`, ZIP Joomla SHA-256 `938a623037eeaba7cdb0c5f70e6aa159f9926e6b84bfbc88e856e7c285b8bdd5`; disponibile fino al 25 novembre 2026. Avanzamento 76% (529/7). Collaudo Joomla pendente nel testbook.


## Aggiornamento 11 novembre 2026 — STAB-01 / PR #238

La PR [#238](https://github.com/jeckodevelopment/jd_learn/pull/238) integra la traduzione IT/EN del menu Percorsi in 0.41.78-dev; CI standard 4/4 nel [run 43113528500](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500); merge `6e7d2cb40f8a641e30b391f046b6e4b1d3b09e33`. Pacchetto [JD-Learn-0.41.78-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500/artifacts/13897377687), SHA-256 interno `19931c7606ae274ca12291e713bddc09a8c946bed3f83f7d3cad1b48db7b53f1`, wrapper `6a029d5283ef897d62869bc8fb88eaf193eb966bab3d61424a04951cae8c9ec5` (estrazione singola; scade il 25 novembre 2026). Collaudo Joomla pendente; avanzamento 78%.

## Integrazione piano — Update Server Joomla / JED (11 novembre 2026)

**Ricognizione:** la funzionalità è già inclusa in **CORE-22** e nel residuo **IMP-09**, con verifica finale in **IMP-10**; la matrice `docs/REQUIREMENTS_STATUS.csv` la registra ancora **Parziale** (update server/firma mancanti). **Non creare un requisito duplicato né una nuova fase:** eseguire una PR tecnica dedicata nel blocco IMP-09, prima del gate IMP-10, associata alla milestone GitHub **1.0.0**.

**IMP-09 / attività US-01–US-06 — P1, obbligatoria per release/JED.** Implementare manifest package `pkg_jlms` con `updateservers`, feed XML pubblico HTTPS, URL e checksum della release ZIP immutabile, versione e compatibilità Joomla, workflow di pubblicazione controllato e validazioni automatiche. Endpoint proposto: `https://www.jeckodevelopment.com/updates/jdlearn.xml` (**da attivare e verificare**, non dato per esistente). Usare hosting web standard Apache/LiteSpeed, senza dipendenze Nginx, VPS o servizi proprietari. Non includere il license server nella 1.0.0. Requisiti correlati: **CORE-22, JOO-01, JOO-03, SECDEV-08, PBASE-04, REL-01–03**. Specifica dettagliata, responsabilità e scenari: [JOOMLA_UPDATE_SERVER_1.0.md](JOOMLA_UPDATE_SERVER_1.0.md).

**IMP-10 / gate specifico Update System:** sul sito di prova installare il package precedente, creare dati reali, verificare che Joomla rilevi una release stabile più recente da un feed HTTPS, aggiorni l'**intero** package (compresi i plugin) e preservi dati/configurazioni/progressi. Verificare inoltre casi 404/feed invalido/ZIP mancante/versione incompatibile, rollback da backup, checksum, nessuna esposizione di `-dev` e compatibilità stack Joomla supportati. Registrare le evidenze in `QA_TESTBOOK.md` e `IMPLEMENTATION_EVIDENCE.json` senza anticipare l'esito del collaudo manuale.

**Condizione aggiuntiva del gate 1.0.0:** endpoint produttivo realmente accessibile, asset di release scaricabile e corrispondente al feed, configurazione update site installata automaticamente e aggiornamento Joomla end-to-end superato. Documentare procedura per nuove release, mantenimento degli asset e recupero da aggiornamento fallito. Le percentuali di avanzamento non aumentano per la sola approvazione di questa specifica.


### IMP-09 — Pipeline di rilascio automatizzata (preparazione tecnica)

- Implementata la procedura manuale `.github/workflows/release.yml` con parametro versione, canale, note e flag `publish`, **disabilitato per default**: la preparazione del pacchetto e del feed non pubblica la 1.0.0.
- `tools/build.py --release-version` produce il package ZIP stabile senza cambiare il manifest sorgente; `tools/release_feed.py` genera e verifica il feed XML Joomla con URL GitHub Release e SHA-256; `tools/publish_update_feed.py` esegue trasferimento SFTP con chiave host verificata e sostituzione atomica.
- L'update site ufficiale è dichiarato nel manifest di `pkg_jlms`; la configurazione produttiva di SFTP e l'effettivo endpoint HTTPS **restano da predisporre e collaudare**. Istruzioni: [RELEASE_PROCEDURE.md](RELEASE_PROCEDURE.md).
- La procedura di publish reale richiede conferma e protezioni GitHub Environment `jdlearn-release-production`; non eseguire fino al gate IMP-10, con CI completa, prove Joomla e verifica recovery. Il workflow di preparazione non prova da solo la disponibilità produttiva del feed.
- Requisiti interessati: CORE-22, JOO-01/03, SECDEV-08, REL-01–03, PBASE-04; criteri US-01–06. Lo stato dei requisiti resta **Parziale** finché l'aggiornamento end-to-end e l'hosting non saranno verificati. La percentuale globale non va aumentata automaticamente per il solo workflow.


## Aggiornamento 12 novembre 2026 — STAB-05 / PR #244

PR [#244](https://github.com/jeckodevelopment/jd_learn/pull/244) integra la correzione dell’issue #200: il collegamento al Programma presente sotto la toolbar è stato rimosso; restano l’azione di toolbar, il riepilogo nella scheda Programma e il messaggio per i corsi non ancora salvati. Merge `ce3ee6f9d62c2483a3a826a34c0a0ba9b0d3bf62`. CI standard 4/4 nel [run 43121346817](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817). Pacchetto installabile [JD-Learn-0.41.80-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817/artifacts/13900597900), SHA-256 interno `5faefa65f5dce76823cb8dd7aa378ebe1801ad720bc740dc7ebe228bdb19132a`, wrapper `c82f72b0c1728bf7bda492c368e5581a3768133ad1ce06e6c9c3f9e50c65b2c7` da estrarre una volta (scade il 25 novembre 2026). L’accettazione manuale Joomla resta nel testbook; avanzamento implementativo 78%.


### IMP-09 — Compatibilità FTPS riutilizzo sessione TLS (12 novembre 2026)

Il test FTPS live `43129478205` ha superato l'autenticazione ma il primo trasferimento è stato rifiutato con `425 Unable to build data connection: Operation not permitted`. Possibile causa: il server ProFTPD richiede il **riutilizzo della sessione TLS** dal canale di controllo al canale dati. Il client FTPS protetto viene aggiornato per richiedere session resumption su ogni trasferimento, senza disabilitare la cifratura né il pin del certificato, con test offline dedicati. L'effettiva compatibilità sul server Host.it è ancora da confermare con il workflow FTPS sicuro; requisiti IMP-09 e IMP-10 non si considerano completati e la percentuale globale resta invariata.


### IMP-09 — Esito FTPS live confermato (12 novembre 2026)

Il workflow [43131327572](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131327572) è **SUCCESS** sul commit `dae0751b6828f3dcd6bc3b9a20fdd72e2a94c621` successivo al merge della PR #249. Evidenza: `FTPS LIVE PROBE OK` dopo verifica TLS/pin, autenticazione, `PROT P`, upload, readback, rename-overwrite di file temporanei e cleanup; `jdlearn.xml` non toccato. Questo conferma la compatibilità tecnica FTPS live; l'identità del certificato privato deve essere confermata con Host.it attraverso un canale indipendente. Non segna IMP-10 né l'aggiornamento Joomla end-to-end come superati. Avanzamento globale di sviluppo documentato invariato al **78%**.


### REL-01–03 — Candidate dry-run 1.0.0 superato (12 novembre 2026)

[Run 43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497): **SUCCESS** in preparazione del candidato stabile versione 1.0.0 senza pubblicazione, ZIP generato e feed XML preparato con checksum SHA-256. Il job protetto di pubblicazione è stato **SKIPPED** come previsto (`publish=false`). Artefatto [jd-learn-release-candidate-1.0.0](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497/artifacts/13906260145). Questo non completa IMP-10 né dimostra il corretto funzionamento dell'updater Joomla sul sito di prova; avanzamento implementativo globale documentato **78% invariato**.


### IMP-10 — Installazione manuale candidato confermata (12 novembre 2026)

L'utente ha confermato che il candidato 1.0.0 preparato nel [run 43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497) è stato installato sul sito Joomla di prova e che il componente funziona. Evidenza limitata allo **smoke test di installazione e funzionamento generale**, da registrare come **conferma utente**. Non equivale ad accettazione completa IMP-10: restano da collaudare upgrade con dati storici e conservazione dei progressi, flussi applicativi specifici e updater/feed Joomla. Stato IMP-10 ancora **parziale**; pubblicazione 1.0.0 non autorizzata. Avanzamento implementativo globale documentato **78% invariato**.


### 12 novembre 2026 — STU-05 / PR #258

Integrata la preferenza locale della velocità per YouTube, Vimeo quando consentito da video/account e HTML5; Cloudflare Stream resta senza controllo. La capacità è verificata a runtime e non cambia il completamento. STU-05 resta parziale: preferenza lingua da implementare. CI 4/4 [run 43134490135](https://github.com/jeckodevelopment/jd_learn/actions/runs/43134490135); pacchetto [0.42.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43134490135/artifacts/13907211349), SHA-256 interno `b984c68bcd06470d656de5b11d88e13aba614327df37216b66058e655826d26d`. M04 95%; stima 80%. Accettazione Joomla pendente nel testbook.


### 12 novembre 2026 — MAT-01 / 0.44.0-dev (PR in preparazione)

Aggiunto alla gestione amministrativa un filtro per allegati del corso, tutte le lezioni o una singola lezione; risultati, conteggio e paginazione sono limitati all’ambito e gli ID lezione sono validati sulla versione. Test automatico `materials-http.py` aggiunto; accettazione Joomla manuale pendente. M04 stimato 96%, avanzamento globale 80% (arrotondato).


### 12 novembre 2026 — IMP-08 / 0.45.0-dev (PR in preparazione)

La candidata aggiunge filtri ricercabili del roster per versione e iscritto, popolati solo dal corso autorizzato. Validazione server-side e ricerca libera restano attive. Test HTTP/JavaScript aggiunti; accettazione manuale Joomla pendente. M06/M09 sale da 67% a 68%; media globale 80,29%, arrotondata a 80%.
