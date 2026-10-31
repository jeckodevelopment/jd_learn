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
