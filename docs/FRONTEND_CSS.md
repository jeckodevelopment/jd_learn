# Contratto frontend CSS v1

Questa guida descrive il contratto corrente per layout, CSS e override. Le sezioni sono organizzate per vista/funzione; per avanzamento e limiti funzionali usare [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

Variabili: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`. Si possono impostare nel CSS personalizzato del template. Nessun selettore globale del prodotto modifica il template ospitante.

Override Joomla: `templates/NOME/html/com_jlms/courses/default.php`; preservare gli ID delle regioni e usare classi per elementi ripetuti. Non duplicare ID. Preservare gli attributi funzionali usati dagli script e descritti nelle sezioni delle rispettive viste.

Aggiornare questa guida a ogni introduzione/modifica di stili e layout. Non modificare i file del core o del componente installato per una personalizzazione del sito.

## Backend del programma didattico

Override: `administrator/templates/NOME/html/com_jlms/curriculum/default.php`. La scaletta conserva `#jlms-admin-curriculum`, `#jlms-admin-course-outline`, `.jlms-admin-module` e `data-module-id`. Nuovi ID unici `jlms-module-ID`, `jlms-lesson-ID`, `jlms-order-status`, `jlms-reorder-form`. Il form `jlms-module-form` o `jlms-lesson-form` è presente soltanto per l’editor richiesto, non su ogni pagina. I test o override che assumevano entrambi sempre visibili devono seguire i link contestuali. Le lezioni MP4 espongono upload/scan soltanto nella selezione della lezione; la scansione di sicurezza resta consentita sullo storico.

WAM `com_jlms.curriculum` registra `curriculum.css` e il modulo `curriculum.js`, caricato solo per bozze modificabili. CSS confinato alla radice amministrativa; griglia scaletta/riepilogo che passa a una colonna sotto991px. La classe `jlms-sort-enabled` attiva le maniglie solo con JS e nessun editor aperto. Preservare `data-editor-mode`, `data-sort-kind`, `data-item-id`, `data-drag-handle`, nomi/token/scope del form condiviso e attributi tradotti dello stato. Gli attributi sono contratto funzionale, non selettori estetici. Modifiche dell’override non devono introdurre form annidati, ID duplicati o scorciatoie GET mutative. La struttura usa dettagli nativi, titoli, label e link per navigazione/tastiera; il collaudo browser/accessibilità resta richiesto.

## Catalogo e scheda corso

| Vista | ID stabili | Classi ripetute |
|---|---|---|
| Catalogo | `jlms-courses`, `jlms-catalog-header`, `jlms-course-grid`, `jlms-category-filter`, `jlms-catalog-empty`, `jlms-catalog-pagination` | `jlms-course-card`, `jlms-course-card__title`, `jlms-course-card__category`, `jlms-course-card__summary` |
| Scheda | `jlms-course`, `jlms-course-header`, `jlms-course-title`, `jlms-course-category`, `jlms-course-summary`, `jlms-course-access`, `jlms-course-outline`, `jlms-course-back` | `jlms-module`, `jlms-module__title`, `jlms-module__lessons`, `jlms-lesson`, `jlms-lesson__title`, `jlms-lesson__requirement` |

Elementi ripetuti: `jlms-course-card-{courseId}`, `jlms-module-{moduleId}`, `jlms-lesson-{lessonId}`; gli ID non dipendono dal titolo o dalla lingua. Le regioni condizionali sono presenti solo quando pertinenti. Override nativi in `templates/NOME/html/com_jlms/courses/default.php` e `course/default.php`; gli override devono conservare escaping, link Joomla e assenza di dati protetti. WAM `com_jlms.site`; variabili CSS già documentate. Le viste categorie e aula sono descritte nelle sezioni dedicate di questa guida.

## Aula testo

Override nativo: `templates/NOME/html/com_jlms/classroom/default.php`. Regioni: `jlms-classroom`, `jlms-classroom-header`, `jlms-learning-progress`, `jlms-progress-meter`, `jlms-course-completed` (condizionale), `jlms-classroom-layout`, `jlms-classroom-outline`, `jlms-lesson-content`, `jlms-lesson-title`, `jlms-lesson-body`, `jlms-complete-form`, `jlms-classroom-back`. Ripetizioni: `jlms-classroom-module-{id}`, `jlms-classroom-lesson-{id}`. Classi omonime per layout/moduli/lezioni; `aria-current` indica la lezione aperta. Griglia a due colonne da desktop e una colonna sotto 48rem, senza dimensioni fisse del testo. Usare le variabili WAM già documentate. Conservare token, key, ID di parent e filtro HTML negli override.

Scheda pubblica: `jlms-course-login` per ospiti, `jlms-start-form` per accesso automatico autorizzato. Il bottone non è mostrato quando manca il livello o l’account è bloccato.

## Materiali protetti

Regioni: `jlms-course-materials` nella scheda (nessun metadato protetto); `jlms-classroom-course-materials` e `jlms-lesson-resources` nell’aula, raggruppate da `jlms-material-groups`. Ripetizioni `jlms-material-{attachmentId}` e classi `.jlms-material`, `.jlms-material__title`, `.jlms-material__meta`, `.jlms-material__action`, `.jlms-material-list`. Ogni ambito ha paginazione distinta (`course_material_page`, `lesson_material_page`). Override `course/default.php` e `classroom/default.php` nativi; mantenere i link del componente con enrollment_id, mai un URL filesystem/storage diretto. Le classi e variabili WAM rimangono compatibili con 0.4.

## Player video

L'override `classroom/default.php` distingue testo e video: solo il testo include `jlms-complete-form`. Il player si apre secondo la politica video del sito. Il video usa `jlms-video-region`, `jlms-player-status` e `jlms-player`; classe `.jlms-player`. Preservare questi ID unici, pulsanti type=button, stato aria-live e le script options/WAM `com_jlms.video`. Il contenitore player ha aspect ratio16/9 soltanto quando non è vuoto. Gli stili possono cambiare senza inserire iframe remoti nel markup o esporre sorgenti. Vedere `VIDEO.md` per consenso/CMP e lifecycle.

## Cloudflare Stream

Riutilizza le regioni del player0.6 senza introdurre ID duplicati o stili globali. `jlms-player-status` annuncia anche il rinnovo; dopo la ricreazione dell'iframe la posizione viene ripristinata e la riproduzione rimane in pausa. Gli override mantengono WAM, script options, controlli di consenso e stato aria-live. Non inserire token o credenziali nei layout: il descrittore temporaneo arriva da POST autorizzato.

## HTML5 e form video

Il player locale riutilizza tutte le regioni aula0.6: `jlms-player` contiene un elemento video nativo con controlli, playsinline e preload metadata dopo caricamento esplicito. CSS `.jlms-player` e `#jlms-player video` mantengono il rapporto16/9 senza selettori globali. Gli override non inseriscono URL filesystem o video pubblici.

Nel backend `curriculum/default.php`: `jlms-video-file-{lessonId}`, `jlms-video-upload-{lessonId}`, `jlms-video-input-{lessonId}`, classe `.jlms-video-file`. Il form upload esiste solo in bozza; quello di rescan può essere disponibile su una lezione pubblicata per lo stato di sicurezza. Conservare enctype, nomi campo, parent, revisione e token Joomla; nessun ID è duplicato. Non rendere modificabili durata e asset del video locale nel form lezione: sono server-owned.

## Authoring quiz

Override backend nativi `questionbank/default.php` e `quiz/default.php`. Regioni: `jlms-question-bank`, `jlms-question-list`, `jlms-question-form`, `jlms-question-lock-status`, `jlms-quiz-editor`, `jlms-quiz-form`, `jlms-quiz-delete-form`. Sono ID unici; righe/opzioni ripetute usano il layout subform Joomla. Non aggiungere risposte corrette ai layout pubblici né spostare logica di permesso nei template. Preservare token, nomi dei campi, revisioni e parent; gli override devono conservare i controlli del flusso e non pubblicare chiavi o spiegazioni protette. Nessun nuovo CSS globale.

## Quiz studenti

Override nativo `com_jlms/quiz/default.php`. ID unici `jlms-quiz`, `jlms-quiz-header`, `jlms-quiz-rules`, `jlms-quiz-availability`, `jlms-quiz-status`, `jlms-quiz-timer`, `jlms-quiz-result`, `jlms-quiz-form`, `jlms-quiz-questions`, `jlms-quiz-actions`, `jlms-quiz-save`, `jlms-quiz-submit`, `jlms-quiz-start`, `jlms-quiz-history`, `jlms-quiz-back`. Regioni condizionali possono mancare. Righe `jlms-quiz-question-{snapshotId}`, opzioni `jlms-quiz-option-{snapshotId}-{optionId}`; classi `.jlms-quiz-question`, `__prompt`, `__option`, `__feedback`, `.jlms-quiz-clear`. Nell’aula `jlms-lesson-quiz` collega la pagina dedicata.

Gli script dipendono da ID form/status/timer/azioni e da `data-question-id`, input/valori, fieldset e `.jlms-quiz-clear`: conservarli o sostituire anche l’adattatore client. Le regole e gli snapshot sono verificati sul server; non derivare autorizzazioni dallo stato DOM. Il DTO rilascia correzioni solo quando ammesse; un override non deve leggere la banca direttamente. CSS limitato alla regione quiz.

## Tempo individuale e avvisi

Frontend: `jlms-quiz-time-allowance` mostra tempo individuale del tentativo e durata del prossimo; `jlms-quiz-warning` è la regione `role=alert` per avvisi a cinque/un minuto. Conservare ruolo e `aria-live`, senza unire questa regione alla conferma del salvataggio. La durata visualizzata non autorizza una deadline differente sul server.

Backend: override `quiztime/default.php`, ID `jlms-quiz-time-editor`, `jlms-quiz-time-policy`, `jlms-quiz-time-enrollments`, `jlms-quiz-time-active`, `jlms-quiz-time-form`, `jlms-quiz-time-history`; nell’editor quiz `jlms-quiz-time-link`. Non pubblicare motivazioni operative nei template del sito. Preservare parent, revisione, UUID e token del form. Nessuna nuova classe CSS globale.

## Annullamento tentativi

Frontend: `jlms-quiz-invalidated` spiega che il tentativo è annullato e il punteggio eventualmente mostrato è storico. Preservare il controllo sul campo `valid`/stato del DTO: non presentare un superamento annullato come efficace né mostrare correzioni non rilasciate.

Backend: override `quizreview/default.php`; ID `jlms-quiz-review`, `jlms-quiz-review-policy`, `jlms-quiz-review-enrollments`, `jlms-quiz-review-attempts`, `jlms-quiz-review-preview`, `jlms-quiz-review-blocked`, `jlms-quiz-review-form`, `jlms-quiz-review-record`. `jlms-quiz-review-link` collega dalla gestione tempo. Conservare parent, revisioni, scelta di restituzione, hash, UUID, conferma e token. I link di anteprima non eseguono mutazioni; non trasformarli in annullamenti automatici. Nessuna nuova classe CSS globale.

## Proroga del tentativo

Frontend: `jlms-quiz-duration` contiene i secondi effettivi e viene aggiornato dalla sincronizzazione; `jlms-quiz-extension` annuncia la proroga con `role=status`, distinto da salvataggio e avvisi. Conservare ID e regioni live negli override o sostituire anche l’adattatore client. La sincronizzazione non deve sostituire le risposte locali né allentare i controlli server.

Backend: override `quizextend/default.php`, ID `jlms-quiz-extend`, `jlms-quiz-extend-policy`, `jlms-quiz-extend-preview-form`, `jlms-quiz-extend-minutes`, `jlms-quiz-extend-impact`, `jlms-quiz-extend-blocked`, `jlms-quiz-extend-form`, `jlms-quiz-extend-history`; collegamento `jlms-quiz-extend-link` dalla revisione del tentativo. Conservare parent, revisioni, minuti, hash, UUID, conferma e CSRF. L’anteprima GET non esegue proroghe. Nessuna nuova classe CSS globale.

## Lingua

Il cambio lingua dei corsi usa il modulo core `mod_languages`: stile e override seguono il contratto del modulo Joomla. I contenitori pubblici `jlms-course` e `jlms-courses` restano gli stessi nelle traduzioni. Nel backend `jlms-course-associations` contiene i campi nativi delle traduzioni. Vedere MULTILINGUAL.md per fallback e perimetro.

## Categorie

Override nativi `categories/default.php` e `category/default.php`. Nuove regioni:

| Vista | ID stabili | Ripetizioni |
|---|---|---|
| Elenco | `jlms-categories`, `jlms-categories-header`, `jlms-categories-description`, `jlms-categories-list`, `jlms-categories-pagination`, `jlms-categories-empty`, `jlms-categories-parent`, `jlms-categories-catalog` | `jlms-category-card-{id}`, classi `jlms-category-card`, `jlms-category-card__title`, `jlms-category-card__description` |
| Scheda | `jlms-category`, `jlms-category-header`, `jlms-category-title`, `jlms-category-description`, `jlms-subcategories`, `jlms-subcategories-list`, `jlms-subcategories-empty`, `jlms-subcategories-pagination`, `jlms-category-courses`, `jlms-category-course-grid`, `jlms-category-courses-empty`, `jlms-category-courses-pagination`, `jlms-category-back` | `jlms-subcategory-{id}`, `jlms-course-card-{id}` |

Le regioni vuote/di ritorno sono condizionali. Il catalogo aggiunge `jlms-catalog-categories`; i precedenti link categoria mantengono ID/classi e aprono la nuova scheda. Griglia responsive `.jlms-category-grid` con variabili CSS esistenti e focus visibile. Nessun ID dipende dal titolo o dalla lingua; nessun ID è duplicato nella stessa pagina. Le paginazioni corsi e figli devono conservare parametri distinti. Pathway e selettore lingua sono resi dai moduli core, personalizzabili tramite i loro override Joomla.

## Area personale

Override `templates/NOME/html/com_jlms/dashboard/default.php`. Regioni: `jlms-dashboard`, `jlms-dashboard-header`, `jlms-dashboard-filters`, `jlms-my-courses`, `jlms-dashboard-not-started`, `jlms-dashboard-in-progress`, `jlms-dashboard-completed`, `jlms-dashboard-empty`, `jlms-dashboard-pagination`, `jlms-dashboard-catalog`. Gruppi e stato vuoto sono condizionali.

Ripetizioni `jlms-my-course-{enrollmentId}`, `jlms-my-course-progress-{enrollmentId}`, `jlms-my-course-resume-{enrollmentId}`; classi `.jlms-my-course-grid`, `.jlms-my-course`, `.jlms-my-course__title`, `.jlms-my-course__access`, `.jlms-my-course__progress`, `.jlms-my-course__action`. Nessun ID dipende dal titolo o dalla lingua. Griglia responsive e focus con le variabili esistenti. Il form filtra tramite `filter[progress]`; conservare menu/router, intestazioni private, escaping e distinzione fra avanzamento e disponibilità. Il link di ritorno nell'aula è `jlms-classroom-dashboard`.

## Backend iscritti

La vista amministrativa usa ID e override descritti in [ENROLLMENT_ROSTER.md](ENROLLMENT_ROSTER.md). Tabella responsive e form adottano gli stili core del template amministrativo. Nessun nuovo stile frontend.

## Backend stato iscrizione

Gli ID/override del flusso anteprima/conferma sono documentati in [ENROLLMENT_STATE.md](ENROLLMENT_STATE.md). Form, alert e tabella responsive usano gli stili core del template amministrativo; nessun CSS pubblico aggiunto.

## Backend iscrizione manuale

ID, form e override descritti in [MANUAL_ENROLLMENT.md](MANUAL_ENROLLMENT.md). Stili del template amministrativo nativi; nessun CSS globale frontend aggiunto.

## Avviso preassegnazione

Il layout amministrativo manualenrollment aggiunge `#jlms-manual-access-warning` (`alert alert-warning`, `role=status`) quando manca l’accesso corrente. [PREASSIGNMENT.md](PREASSIGNMENT.md) descrive campo opzionale e override da aggiornare; nessun nuovo CSS pubblico.

## Backend pubblicazione

Override amministrativi `course/edit.php` e `curriculum/default.php`. Nuovi ID: `jlms-course-publication-help`, `jlms-course-publication-heading`, `jlms-course-programme-link` (solo corso salvato), `jlms-publication-status`, `jlms-publication-heading`, `jlms-publication-notice` (solo impedimento), `jlms-publication-submit` (azione disponibile). Preservare avvisi, token, ACL e transizione nativa. Stili alert/btn del template amministrativo, nessun CSS pubblico aggiunto. Gli override esistenti vanno confrontati per ricevere navigazione e diagnostica.

## Presentazione corso

Editor nativo: ID e pannelli documentati in COURSE_EDITOR.md. Nuove regioni nella scheda pubblica (override `templates/NOME/html/com_jlms/course/default.php`): `jlms-course-description`, `jlms-course-objectives`, `jlms-course-audience`, `jlms-course-prerequisite-text`, `jlms-course-facts`, `jlms-course-difficulty`, `jlms-course-estimated-duration`. Sono condizionali, presenti una sola volta e indipendenti da lingua/titolo. Classi `.jlms-course-description`, `.jlms-course-presentation-section`; nessuna nuova regola CSS globale. Usare le variabili esistenti e il CSS del template per margini/tipografia.

La descrizione è HTML con allowlist già applicata; gli altri testi sono escapati. Conservare entrambi i comportamenti nell'override. Non leggere params_json del corso o bozze per popolare queste regioni: la sorgente è la proiezione editoriale corrente autorizzata in `$this->item['presentation']`; lo storico didattico mantiene i propri snapshot.

## Copertina e metadati

`#jlms-course-cover` e `.jlms-course-cover` avvolgono l’immagine pubblica opzionale in `course/default.php`. Lo stile limita la larghezza al contenitore mantenendo le proporzioni. Esempio override CSS: `.jlms-course-cover img { max-height: 24rem; width: 100%; object-fit: contain; }`. Conservare l’escaping del percorso/alt e `alt=""` per immagini decorative; non usare campi di bozza o URL media didattici. Il layout codifica i segmenti del percorso e usa la base Joomla, anche con installazione in sottocartella. Il titolo visibile resta `#jlms-course-title`; titolo/descrizione SEO appartengono alla head nativa dopo il controllo di accesso. Il tab amministrativo `#jlms-course-metadata` usa `renderFieldset('metadata')`; gli override devono conservarlo insieme al campo media nativo e alla validazione server.

## Docenti

`#jlms-admin-teachers` e `#jlms-teacher-editor` nelle viste amministrative native; scheda corso editor `#jlms-course-teachers-settings`. Frontend: `#jlms-course-teachers` opzionale, `#jlms-course-teacher-{id}` e `.jlms-course-teacher` per ciascun profilo. Foto con larghezza massima10rem e proporzioni conservate; esempio `.jlms-course-teacher { padding: 1rem; border: 1px solid currentColor; }`. Conservare escaping di nome/bio/alt/link, alt vuoto per foto decorative e proiezione già autorizzata `$this->item['teachers']`. La scheda pubblica usa la proiezione corrente autorizzata; lo storico didattico mantiene gli snapshot. Non aggiungere query dirette al profilo o all’account negli override. Form amministrative e selezione multipla devono conservare token/revisioni, stato e controlli nativi.

## Calendario

Editor: tab `#jlms-course-calendar`, fieldset calendar con controlli nativi. Frontend: sezione opzionale `#jlms-course-calendar`, elenco definizioni con date editoriali correnti, fuso e offset; la disponibilità continua a usare le finestre dell’edizione assegnata. `#jlms-course-window-closed` comunica la finestra corrente non aperta per chi possiede il livello. Conservare `$this->canStartWindow` insieme ai controlli di identità/accesso nei rami del pulsante; gli endpoint ricontrollano sempre la disponibilità. Esempio stile: `#jlms-course-calendar dl { display: grid; gap: .5rem; }`. Nessun valore di bozza, data dal browser o clock client deve autorizzare l’avvio.

## Editor e permessi

Tab amministrativo `#jlms-course-permissions`, regione `#jlms-course-effective-rights`, link condizionale `#jlms-course-options-link` alle opzioni native. Il riepilogo usa un elenco di definizioni; esempio `#jlms-course-effective-rights dl { display: grid; gap: .5rem; }`. Negli override mantenere readOnly/checkedOutByOther, disabilitazione dei campi e toolbar coerente. Il selettore Coursecategory estende il campo Category nativo; non rimpiazzarlo con un elenco privo di controllo ACL. Nessuna modifica del contratto frontend studente.

Nel layout amministrativo Programma, `#jlms-video-setup` identifica il riepilogo dei prerequisiti video vicino al form della lezione.

L’anteprima amministrativa usa `coursepreview/default.php`, con regioni `#jlms-course-preview` e `#jlms-course-preview-lesson`. Il Programma espone il collegamento `#jlms-course-preview-link`.

Nella scheda Permessi dell’editor, `#jlms-course-rules` contiene il campo ACL nativo Joomla e il messaggio sul salvataggio immediato. Gli override devono conservare token, asset effettivo, campo Rules e controlli di autorizzazione.

Nella0.31 la pagina docente usa `teacher/default.php` e il layout `teacher/courses.php`, con `#jlms-teacher-profile`, `#jlms-teacher-presentation`, `#jlms-teacher-fields` e `#jlms-teacher-courses`. Il form frontend del profilo usa `teacheredit/default.php` e `#jlms-teacher-profile-form`. Il Programma pubblicato offre `#jlms-edit-programme-form`. Conservare autorizzazioni, token e campi di revisione negli override.

## Amministrazione — duplicazioni

Nel template administrator/tmpl/curriculum/default.php, #jlms-duplicate-course contiene riepilogo e form della copia corso, #jlms-copy-course-title il titolo. Le azioni di copia modulo/lezione sono nel menu contestuale Azioni e aprono il form selezionato; non assumere form di copia ripetuti sotto ogni elemento. Nessun nuovo asset frontend o stile globale; mantenere task, token, ID di sorgente/destinazione e revisioni negli override. I comandi di modulo/lezione sono visibili solo nel programma di lavoro, quello del corso solo con creazione autorizzata nella categoria.

## Backend — elenco ed editor

administrator/tmpl/courses/default.php: #jlms-admin-courses, #jlms-course-list, #jlms-courses-empty; righe .jlms-admin-course con data-course-id, .jlms-admin-course-title e .jlms-admin-course-actions. Search Tools/grid/multiselect/toolbar e classi responsive del template Joomla; nessun foglio globale aggiunto. administrator/tmpl/course/edit.php: #jlms-course-editor-layout, #jlms-course-editor-sidebar, #jlms-course-programme, #jlms-course-programme-summary, #jlms-course-options. ID precedenti presentazione/accessi/metadati/calendario/permessi/associazioni conservati come regioni, anche se raccolti in meno schede. Adattare gli override che assumono la vecchia gerarchia. Il controllo Salva e apri programma usa il web component toolbar Joomla e il task course.programme, non JavaScript o validazione paralleli.

## Protezione delle modifiche non salvate

Gli override del programma devono mantenere `.jlms-focused-editor` con il form di modifica. Lo script dipende dal form e dai suoi controlli, non da un archivio locale dei contenuti. L'avviso usa la conferma tradotta del browser; richiede JavaScript e interazione utente. Per il comportamento d'uso vedere [AUTHORING.md](AUTHORING.md), per le prove [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Editor corso: campi Joomla

La scheda condizionale `jlms-course-fields-tab` contiene la regione `jlms-course-custom-fields` e il gruppo nativo `com_fields`. Gli override del form corso devono renderizzare i fieldset restituiti da Joomla, preservando nomi, validazioni e token; non creare campi paralleli o inviare valori disabilitati. Nessun nuovo stile globale.

## Riepilogo di pubblicazione amministrativo

Override `administrator/templates/NOME/html/com_jlms/publicationreview/default.php`. Regioni: `jlms-publication-review`, tabella `jlms-publication-changes`, form `jlms-publication-confirm-form`, checkbox `jlms-publication-confirm`. Preservare scope, ticket, token Joomla e conferma richiesta. La tabella usa caption/intestazioni e contenitore responsive nativi; nessun nuovo CSS globale o script. Non mostrare corpi, sorgenti video, risposte o nominativi nelle differenze.

### Recupero del programma storico

Nel contenitore amministrativo esistente: `#jlms-restore-programme` è il disclosure nativo, `#jlms-restore-programme-form` il form POST, `#jlms-restore-confirm` il checkbox con label e conferma obbligatoria. Si usano classi Joomla esistenti, senza stili globali nuovi. Gli override della vista curriculum devono conservare token, campi di scope/revisione e conferma; le versioni storiche non mostrano il comando che copia il programma attivo.
