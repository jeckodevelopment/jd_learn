# Contratto frontend CSS v1

Le regioni implementate in questo incremento sono `#jlms-courses` e `#jlms-course-grid`. Classi: `.jlms-catalog`, `.jlms-course-grid`. Le card, le regioni della scheda corso/aula e gli altri ID previsti saranno aggiunti insieme alle relative view.

Variabili: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`. Si possono impostare nel CSS personalizzato del template. Nessun selettore globale del prodotto modifica il template ospitante.

Override Joomla: `templates/NOME/html/com_jlms/courses/default.php`; preservare gli ID delle regioni e usare classi per elementi ripetuti. Non duplicare ID. Il JavaScript applicativo futuro userà attributi data-jlms, separati dai selettori estetici.

Aggiornare questa guida a ogni introduzione/modifica di stili e layout. Non modificare i file del core o del componente installato per una personalizzazione del sito.

## Backend del programma didattico0.2

La nuova view amministrativa usa `#jlms-admin-curriculum`, `#jlms-admin-course-outline`, `#jlms-version-form`, `#jlms-module-form` e `#jlms-lesson-form`. Gli elementi ripetuti usano `.jlms-admin-module` e `data-module-id`; nessun ID viene duplicato per ciascun modulo. I due form nativi usano ID di campo distinti.

Le classi Bootstrap del template amministrativo gestiscono le colonne: editor del modulo ed editor della lezione si dispongono verticalmente su schermi piccoli. Il singolo editor Joomla evita di istanziare un editor per ogni lezione. Override: `administrator/templates/NOME/html/com_jlms/curriculum/default.php`; preservare i nomi dei campi, i token e gli ambiti di versione.

## Regioni aggiunte in 0.3

| Vista | ID stabili | Classi ripetute |
|---|---|---|
| Catalogo | `jlms-courses`, `jlms-catalog-header`, `jlms-course-grid`, `jlms-category-filter`, `jlms-catalog-empty`, `jlms-catalog-pagination` | `jlms-course-card`, `jlms-course-card__title`, `jlms-course-card__category`, `jlms-course-card__summary` |
| Scheda | `jlms-course`, `jlms-course-header`, `jlms-course-title`, `jlms-course-category`, `jlms-course-summary`, `jlms-course-access`, `jlms-course-outline`, `jlms-course-back` | `jlms-module`, `jlms-module__title`, `jlms-module__lessons`, `jlms-lesson`, `jlms-lesson__title`, `jlms-lesson__requirement` |

Elementi ripetuti: `jlms-course-card-{courseId}`, `jlms-module-{moduleId}`, `jlms-lesson-{lessonId}`; gli ID non dipendono dal titolo o dalla lingua. Le regioni condizionali sono presenti solo quando pertinenti. Override nativi in `templates/NOME/html/com_jlms/courses/default.php` e `course/default.php`; gli override devono conservare escaping, link Joomla e assenza di dati protetti. WAM `com_jlms.site`; variabili CSS già documentate. Le future viste categorie/aula hanno il contratto progettato, ma non sono ancora implementate.

## Aula testo (0.4)

Override nativo: `templates/NOME/html/com_jlms/classroom/default.php`. Regioni: `jlms-classroom`, `jlms-classroom-header`, `jlms-learning-progress`, `jlms-progress-meter`, `jlms-course-completed` (condizionale), `jlms-classroom-layout`, `jlms-classroom-outline`, `jlms-lesson-content`, `jlms-lesson-title`, `jlms-lesson-body`, `jlms-complete-form`, `jlms-classroom-back`. Ripetizioni: `jlms-classroom-module-{id}`, `jlms-classroom-lesson-{id}`. Classi omonime per layout/moduli/lezioni; `aria-current` indica la lezione aperta. Griglia a due colonne da desktop e una colonna sotto 48rem, senza dimensioni fisse del testo. Usare le variabili WAM già documentate. Conservare token, key, ID di parent e filtro HTML negli override.

Scheda pubblica: `jlms-course-login` per ospiti, `jlms-start-form` per accesso automatico autorizzato. Il bottone non è mostrato quando manca il livello o l’account è bloccato.

## Materiali protetti (0.5)

Regioni: `jlms-course-materials` nella scheda (nessun metadato protetto); `jlms-classroom-course-materials` e `jlms-lesson-resources` nell’aula, raggruppate da `jlms-material-groups`. Ripetizioni `jlms-material-{attachmentId}` e classi `.jlms-material`, `.jlms-material__title`, `.jlms-material__meta`, `.jlms-material__action`, `.jlms-material-list`. Ogni ambito ha paginazione distinta (`course_material_page`, `lesson_material_page`). Override `course/default.php` e `classroom/default.php` nativi; mantenere i link del componente con enrollment_id, mai un URL filesystem/storage diretto. Le classi e variabili WAM rimangono compatibili con 0.4.

## Player video (0.6)

L'override `classroom/default.php` distingue testo e video: solo il testo include `jlms-complete-form`. Il video usa `jlms-video-region`, `jlms-video-consent`, `jlms-video-load`, `jlms-video-stop`, `jlms-player-status` e `jlms-player`; classe `.jlms-player`. Preservare questi ID unici, pulsanti type=button, stato aria-live e le script options/WAM `com_jlms.video`. Il contenitore player ha aspect ratio16/9 soltanto quando non è vuoto. Gli stili possono cambiare senza inserire iframe remoti nel markup o esporre sorgenti. Vedere `VIDEO.md` per consenso/CMP e lifecycle.

## Cloudflare Stream (0.7)

Riutilizza le regioni del player0.6 senza introdurre ID duplicati o stili globali. `jlms-player-status` annuncia anche il rinnovo; dopo la ricreazione dell'iframe la posizione viene ripristinata e la riproduzione rimane in pausa. Gli override mantengono WAM, script options, controlli di consenso e stato aria-live. Non inserire token o credenziali nei layout: il descrittore temporaneo arriva da POST autorizzato.

## HTML5 e form video (0.8)

Il player locale riutilizza tutte le regioni aula0.6: `jlms-player` contiene un elemento video nativo con controlli, playsinline e preload metadata dopo caricamento esplicito. CSS `.jlms-player` e `#jlms-player video` mantengono il rapporto16/9 senza selettori globali. Gli override non inseriscono URL filesystem o video pubblici.

Nel backend `curriculum/default.php`: `jlms-video-file-{lessonId}`, `jlms-video-upload-{lessonId}`, `jlms-video-input-{lessonId}`, classe `.jlms-video-file`. Il form upload esiste solo in bozza; quello di rescan può essere disponibile su una lezione pubblicata per lo stato di sicurezza. Conservare enctype, nomi campo, parent, revisione e token Joomla; nessun ID è duplicato. Non rendere modificabili durata e asset del video locale nel form lezione: sono server-owned.

## Authoring quiz (0.9)

Override backend nativi `questionbank/default.php` e `quiz/default.php`. Regioni: `jlms-question-bank`, `jlms-question-list`, `jlms-question-form`, `jlms-question-lock-status`, `jlms-quiz-editor`, `jlms-quiz-form`, `jlms-quiz-delete-form`. Sono ID unici; righe/opzioni ripetute usano il layout subform Joomla. Non aggiungere risposte corrette ai layout pubblici né spostare logica di permesso nei template. Preservare token, nomi dei campi, revisioni e parent; gli override devono conservare i controlli del flusso e non pubblicare chiavi o spiegazioni protette. Nessun nuovo CSS globale.

## Quiz studenti (0.10)

Override nativo `com_jlms/quiz/default.php`. ID unici `jlms-quiz`, `jlms-quiz-header`, `jlms-quiz-rules`, `jlms-quiz-availability`, `jlms-quiz-status`, `jlms-quiz-timer`, `jlms-quiz-result`, `jlms-quiz-form`, `jlms-quiz-questions`, `jlms-quiz-actions`, `jlms-quiz-save`, `jlms-quiz-submit`, `jlms-quiz-start`, `jlms-quiz-history`, `jlms-quiz-back`. Regioni condizionali possono mancare. Righe `jlms-quiz-question-{snapshotId}`, opzioni `jlms-quiz-option-{snapshotId}-{optionId}`; classi `.jlms-quiz-question`, `__prompt`, `__option`, `__feedback`, `.jlms-quiz-clear`. Nell’aula `jlms-lesson-quiz` collega la pagina dedicata.

Gli script dipendono da ID form/status/timer/azioni e da `data-question-id`, input/valori, fieldset e `.jlms-quiz-clear`: conservarli o sostituire anche l’adattatore client. Le regole e gli snapshot sono verificati sul server; non derivare autorizzazioni dallo stato DOM. Il DTO rilascia correzioni solo quando ammesse; un override non deve leggere la banca direttamente. CSS limitato alla regione quiz.

## Tempo individuale e avvisi (0.11)

Frontend: `jlms-quiz-time-allowance` mostra tempo individuale del tentativo e durata del prossimo; `jlms-quiz-warning` è la regione `role=alert` per avvisi a cinque/un minuto. Conservare ruolo e `aria-live`, senza unire questa regione alla conferma del salvataggio. La durata visualizzata non autorizza una deadline differente sul server.

Backend: override `quiztime/default.php`, ID `jlms-quiz-time-editor`, `jlms-quiz-time-policy`, `jlms-quiz-time-enrollments`, `jlms-quiz-time-active`, `jlms-quiz-time-form`, `jlms-quiz-time-history`; nell’editor quiz `jlms-quiz-time-link`. Non pubblicare motivazioni operative nei template del sito. Preservare parent, revisione, UUID e token del form. Nessuna nuova classe CSS globale.

## Annullamento tentativi (0.12)

Frontend: `jlms-quiz-invalidated` spiega che il tentativo è annullato e il punteggio eventualmente mostrato è storico. Preservare il controllo sul campo `valid`/stato del DTO: non presentare un superamento annullato come efficace né mostrare correzioni non rilasciate.

Backend: override `quizreview/default.php`; ID `jlms-quiz-review`, `jlms-quiz-review-policy`, `jlms-quiz-review-enrollments`, `jlms-quiz-review-attempts`, `jlms-quiz-review-preview`, `jlms-quiz-review-blocked`, `jlms-quiz-review-form`, `jlms-quiz-review-record`. `jlms-quiz-review-link` collega dalla gestione tempo. Conservare parent, revisioni, scelta di restituzione, hash, UUID, conferma e token. I link di anteprima non eseguono mutazioni; non trasformarli in annullamenti automatici. Nessuna nuova classe CSS globale.

## Proroga del tentativo (0.13)

Frontend: `jlms-quiz-duration` contiene i secondi effettivi e viene aggiornato dalla sincronizzazione; `jlms-quiz-extension` annuncia la proroga con `role=status`, distinto da salvataggio e avvisi. Conservare ID e regioni live negli override o sostituire anche l’adattatore client. La sincronizzazione non deve sostituire le risposte locali né allentare i controlli server.

Backend: override `quizextend/default.php`, ID `jlms-quiz-extend`, `jlms-quiz-extend-policy`, `jlms-quiz-extend-preview-form`, `jlms-quiz-extend-minutes`, `jlms-quiz-extend-impact`, `jlms-quiz-extend-blocked`, `jlms-quiz-extend-form`, `jlms-quiz-extend-history`; collegamento `jlms-quiz-extend-link` dalla revisione del tentativo. Conservare parent, revisioni, minuti, hash, UUID, conferma e CSRF. L’anteprima GET non esegue proroghe. Nessuna nuova classe CSS globale.

## Incremento0.14 — lingua

Il cambio lingua dei corsi usa il modulo core `mod_languages`: stile e override seguono il contratto del modulo Joomla. I contenitori pubblici `jlms-course` e `jlms-courses` restano gli stessi nelle traduzioni. Nel backend `jlms-course-associations` contiene i campi nativi delle traduzioni. Vedere MULTILINGUAL.md per fallback e perimetro.

## Categorie (0.15)

Override nativi `categories/default.php` e `category/default.php`. Nuove regioni:

| Vista | ID stabili | Ripetizioni |
|---|---|---|
| Elenco | `jlms-categories`, `jlms-categories-header`, `jlms-categories-description`, `jlms-categories-list`, `jlms-categories-pagination`, `jlms-categories-empty`, `jlms-categories-parent`, `jlms-categories-catalog` | `jlms-category-card-{id}`, classi `jlms-category-card`, `jlms-category-card__title`, `jlms-category-card__description` |
| Scheda | `jlms-category`, `jlms-category-header`, `jlms-category-title`, `jlms-category-description`, `jlms-subcategories`, `jlms-subcategories-list`, `jlms-subcategories-empty`, `jlms-subcategories-pagination`, `jlms-category-courses`, `jlms-category-course-grid`, `jlms-category-courses-empty`, `jlms-category-courses-pagination`, `jlms-category-back` | `jlms-subcategory-{id}`, `jlms-course-card-{id}` |

Le regioni vuote/di ritorno sono condizionali. Il catalogo aggiunge `jlms-catalog-categories`; i precedenti link categoria mantengono ID/classi e aprono la nuova scheda. Griglia responsive `.jlms-category-grid` con variabili CSS esistenti e focus visibile. Nessun ID dipende dal titolo o dalla lingua; nessun ID è duplicato nella stessa pagina. Le paginazioni corsi e figli devono conservare parametri distinti. Pathway e selettore lingua sono resi dai moduli core, personalizzabili tramite i loro override Joomla.

## Area personale (0.16)

Override `templates/NOME/html/com_jlms/dashboard/default.php`. Regioni: `jlms-dashboard`, `jlms-dashboard-header`, `jlms-dashboard-filters`, `jlms-my-courses`, `jlms-dashboard-not-started`, `jlms-dashboard-in-progress`, `jlms-dashboard-completed`, `jlms-dashboard-empty`, `jlms-dashboard-pagination`, `jlms-dashboard-catalog`. Gruppi e stato vuoto sono condizionali.

Ripetizioni `jlms-my-course-{enrollmentId}`, `jlms-my-course-progress-{enrollmentId}`, `jlms-my-course-resume-{enrollmentId}`; classi `.jlms-my-course-grid`, `.jlms-my-course`, `.jlms-my-course__title`, `.jlms-my-course__access`, `.jlms-my-course__progress`, `.jlms-my-course__action`. Nessun ID dipende dal titolo o dalla lingua. Griglia responsive e focus con le variabili esistenti. Il form filtra tramite `filter[progress]`; conservare menu/router, intestazioni private, escaping e distinzione fra avanzamento e disponibilità. Il link di ritorno nell'aula è `jlms-classroom-dashboard`.

## Backend iscritti 0.17

La vista amministrativa usa ID e override descritti in [ENROLLMENT_ROSTER.md](ENROLLMENT_ROSTER.md). Tabella responsive e form adottano gli stili core del template amministrativo. Nessun nuovo stile frontend.

## Backend stato iscrizione0.18

Gli ID/override del flusso anteprima/conferma sono documentati in [ENROLLMENT_STATE.md](ENROLLMENT_STATE.md). Form, alert e tabella responsive usano gli stili core del template amministrativo; nessun CSS pubblico aggiunto.

## Backend iscrizione manuale0.19

ID, form e override descritti in [MANUAL_ENROLLMENT.md](MANUAL_ENROLLMENT.md). Stili del template amministrativo nativi; nessun CSS globale frontend aggiunto.

## Avviso preassegnazione0.20

Il layout amministrativo manualenrollment aggiunge `#jlms-manual-access-warning` (`alert alert-warning`, `role=status`) quando manca l’accesso corrente. [PREASSIGNMENT.md](PREASSIGNMENT.md) descrive campo opzionale e override da aggiornare; nessun nuovo CSS pubblico.

## Backend pubblicazione0.20.1

Override amministrativi `course/edit.php` e `curriculum/default.php`. Nuovi ID: `jlms-course-publication-help`, `jlms-course-publication-heading`, `jlms-course-programme-link` (solo corso salvato), `jlms-publication-status`, `jlms-publication-heading`, `jlms-publication-notice` (solo impedimento). Preservare avvisi, token, ACL e transizione nativa. Stili alert/btn del template amministrativo, nessun CSS pubblico aggiunto. Gli override esistenti vanno confrontati per ricevere navigazione e diagnostica.
