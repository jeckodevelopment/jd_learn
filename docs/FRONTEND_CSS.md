# Contratto frontend CSS v1

Le regioni implementate in questo incremento sono `#jlms-courses` e `#jlms-course-grid`. Classi: `.jlms-catalog`, `.jlms-course-grid`. Le card, le regioni della scheda corso/aula e gli altri ID previsti saranno aggiunti insieme alle relative view.

Variabili: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-space-unit`, `--jlms-radius`, `--jlms-content-width`. Si possono impostare nel CSS personalizzato del template. Nessun selettore globale del prodotto modifica il template ospitante.

Override Joomla: `templates/NOME/html/com_jlms/courses/default.php`; preservare gli ID delle regioni e usare classi per elementi ripetuti. Non duplicare ID. Il JavaScript applicativo futuro userà attributi data-jlms, separati dai selettori estetici.

Aggiornare questa guida a ogni introduzione/modifica di stili e layout. Non modificare i file del core o del componente installato per una personalizzazione del sito.

## Backend del programma didattico0.2

La nuova view amministrativa usa `#jlms-admin-curriculum`, `#jlms-admin-course-outline`, `#jlms-version-form`, `#jlms-module-form` e `#jlms-lesson-form`. Gli elementi ripetuti usano `.jlms-admin-module` e `data-module-id`; nessun ID viene duplicato per ciascun modulo. I due form nativi usano ID di campo distinti.

Le classi Bootstrap del template amministrativo gestiscono le colonne: editor del modulo ed editor della lezione si dispongono verticalmente su schermi piccoli. Il singolo editor Joomla evita di istanziare un editor per ogni lezione. Override: `administrator/templates/NOME/html/com_jlms/curriculum/default.php`; preservare i nomi dei campi, i token e gli ambiti di versione.
