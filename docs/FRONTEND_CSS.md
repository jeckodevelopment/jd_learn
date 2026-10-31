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
