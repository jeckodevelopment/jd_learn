# Audit funzionale JD Learn 1.0.0

Requisiti in perimetro: **214**. Criteri: **757**.

- Implementati: **255**.
- Mancanti con lacuna accertata: **10**.
- Esaminati con esito non verificabile: **2**.
- Da esaminare: **490**.

Percentuale funzionale globale: **non determinabile**.
Il calcolo pesa allo stesso modo ogni requisito. Gli esiti dell’audit sono distinti dall’esecuzione dei test e dall’accettazione manuale Joomla.

## Lacune confermate

- `CAT-01/language_available` — Combinazioni lingua e soli corsi disponibili. La query usa la lingua corrente e valida lo stato pubblicato. Il catalogo non offre il filtro per la sola fruizione disponibile e il modello non applica quel vincolo. La visibilità della scheda e l’erogazione sono indipendenti.
  - [component/administrator/src/Application/CatalogService.php#private function courseQuery](../component/administrator/src/Application/CatalogService.php)
  - [component/site/src/Model/CoursesModel.php#protected function populateState](../component/site/src/Model/CoursesModel.php)
  - [component/site/src/Model/CoursesModel.php#protected function getListQuery](../component/site/src/Model/CoursesModel.php)
- `CAT-02/actions` — Tutte le varianti di pulsanti secondo accesso e stato. La scheda sceglie fra login, un pulsante combinato di avvio/ripresa e richiesta di ammissione a partire dal livello e dalla modalità del corso. Non legge lo stato di iscrizione del discente per mostrare azioni distinte.
  - [component/site/src/View/Course/HtmlView.php#public function display](../component/site/src/View/Course/HtmlView.php)
  - [component/site/tmpl/course/default.php#COM_JLMS_START_RESUME](../component/site/tmpl/course/default.php)
  - [component/site/tmpl/course/default.php#COM_JLMS_ENROLLMENT_REQUEST_SUBMIT](../component/site/tmpl/course/default.php)
- `CAT-02/completion_criteria` — Visualizzazione completa regole di completamento e attestati disponibili. Il programma mostra lezioni richieste/opzionali e il calendario. La proiezione restituita alla scheda non include un riepilogo delle regole di completamento e della disponibilità dell’attestato.
  - [component/administrator/src/Application/CatalogService.php#public function outline](../component/administrator/src/Application/CatalogService.php)
  - [component/site/tmpl/course/default.php#jlms-course-outline](../component/site/tmpl/course/default.php)
- `SCR-01/sorts` — Ordinamenti editoriali, titolo, data e durata. La pagina consente solo recente e titolo. Durata e altri ordinamenti editoriali non sono selezionabili. Il filtro per fascia di durata non ordina i risultati.
  - [component/site/src/Model/CoursesModel.php#['recent', 'title']](../component/site/src/Model/CoursesModel.php)
  - [component/site/src/Model/CoursesModel.php#return $query->order](../component/site/src/Model/CoursesModel.php)
  - [component/site/tmpl/courses/default.php#name="filter[order]"](../component/site/tmpl/courses/default.php)
- `SCR-01/card` — Scheda include metadati principali e azione disponibile. La card espone titolo, categoria e, se configurati, riepilogo, durata e difficoltà. Offre il link alla scheda ma non proietta l’azione didattica disponibile per l’utente.
  - [component/site/tmpl/courses/default.php#jlms-course-card__title](../component/site/tmpl/courses/default.php)
  - [component/site/tmpl/courses/default.php#jlms-course-card__meta](../component/site/tmpl/courses/default.php)
- `SCR-01/empty_error` — Stati senza risultati, azzera filtri ed errore recuperabile. Il layout ha un messaggio per zero risultati e un collegamento per uscire dalla categoria. Non offre azzera filtri per la ricerca generale né uno stato di errore recuperabile nel catalogo.
  - [component/site/tmpl/courses/default.php#jlms-catalog-empty](../component/site/tmpl/courses/default.php)
  - [component/site/tmpl/courses/default.php#jlms-category-filter](../component/site/tmpl/courses/default.php)
- `SCR-03/access_cta` — Azione primaria coerente con stato/iscrizione/permessi. Il modello della scheda non distingue l’iscrizione esistente da quella da creare. Il layout usa la stessa azione per avvio e ripresa nella modalità automatica e mostra la richiesta nella modalità ad approvazione.
  - [component/site/src/View/Course/HtmlView.php#public function display](../component/site/src/View/Course/HtmlView.php)
  - [component/site/tmpl/course/default.php#COM_JLMS_START_RESUME](../component/site/tmpl/course/default.php)
  - [component/site/tmpl/course/default.php#COM_JLMS_ENROLLMENT_REQUEST_SUBMIT](../component/site/tmpl/course/default.php)
- `SCR-03/course_rules` — Regole di completamento e attestati disponibili presentate correttamente. Le indicazioni richiesta/opzionale nel programma non presentano le regole complessive di completamento o la disponibilità dell’attestato.
  - [component/administrator/src/Application/CatalogService.php#public function outline](../component/administrator/src/Application/CatalogService.php)
  - [component/site/tmpl/course/default.php#jlms-course-outline](../component/site/tmpl/course/default.php)
- `SCR-04/criterion_03` — Percentuale aggregata solo se lo studente ha iscrizioni e requisiti definiti per i corsi necessari. Il servizio restituisce l’aggregato a ogni utente autenticato. Un corso necessario privo di iscrizione entra comunque come zero nel calcolo. Manca il gate richiesto sulle iscrizioni e sui requisiti definiti.
  - [component/administrator/src/Application/PathwayService.php#hasPersonalProgress](../component/administrator/src/Application/PathwayService.php)
  - [component/administrator/src/Application/PathwayService.php#not_enrolled](../component/administrator/src/Application/PathwayService.php)
  - [component/administrator/src/Application/PathwayProgress.php#public static function summarize](../component/administrator/src/Application/PathwayProgress.php)
- `LIFE-01/criterion_03` — La purga è soggetta a permessi, politica di conservazione e riepilogo dell'impatto. Il modello applica core.delete e rifiuta i corsi con dipendenze. La toolbar usa un messaggio di conferma fisso. Manca un riepilogo dell’impatto calcolato per il corso selezionato.
  - [component/administrator/src/Model/CourseModel.php#public function purgeEmpty](../component/administrator/src/Model/CourseModel.php)
  - [component/administrator/src/View/Courses/HtmlView.php#ToolbarHelper::deleteList](../component/administrator/src/View/Courses/HtmlView.php)
  - [component/administrator/language/it-IT/com_jlms.ini#COM_JLMS_COURSE_PURGE_CONFIRM](../component/administrator/language/it-IT/com_jlms.ini)

## Verifiche ancora necessarie

- `LCM-03/external_references` — Audit dipendenze storiche esterne non coperte dai vincoli interni. La purga del percorso controlla le relazioni rappresentate dal modello. Un audit dei riferimenti di estensioni esterne richiede l’inventario delle integrazioni e delle relative tabelle.
  - [component/administrator/src/Model/PathwayModel.php#public function purgeEmpty](../component/administrator/src/Model/PathwayModel.php)
- `LCM-01/criterion_01` — Archiviazione corsi: blocco nuove iscrizioni e fruizione anche da endpoint diretti. L’archiviazione chiude ammissioni e didattica. I contesti di aula, quiz e risorse risalgono all’iscrizione e al corso, ma resta da tracciare la copertura di tutti gli endpoint diretti compresi gli ingressi amministrativi e i retry.
  - [component/administrator/src/Application/PublicationService.php#Preserve student history](../component/administrator/src/Application/PublicationService.php)
  - [component/administrator/src/Application/LearningService.php#private function authorisedEnrollment](../component/administrator/src/Application/LearningService.php)

## Implementazione riscontrata

- `ISC-04/public_preview` — Anteprima pubblica priva di effetti su completamenti/attestati. L’anteprima pubblica legge il corso, una lezione testuale della versione corrente con anteprima abilitata e gli allegati pubblicabili. Il percorso non richiama il flusso di iscrizione, completamento o emissione degli attestati.
  - [component/site/src/Model/LessonpreviewModel.php#public function getItem](../component/site/src/Model/LessonpreviewModel.php)
  - [component/administrator/src/Application/CatalogService.php#public function publicLessonPreview](../component/administrator/src/Application/CatalogService.php)
  - [component/administrator/src/Application/MaterialService.php#public function publicLessonPreviewList](../component/administrator/src/Application/MaterialService.php)
  - [tests/joomla-publication.php#publicLessonPreview](../tests/joomla-publication.php)
- `ISC-08/lesson_scope` — Restrizioni di lezione non possono ampliare l'accesso al corso. I contesti per quiz, video, audio e materiali protetti risolvono un’iscrizione autorizzata prima di risolvere una lezione, vincolata alla versione assegnata. Disponibilità e prerequisiti possono restringere ulteriormente quel contesto.
  - [component/administrator/src/Application/LearningService.php#private function authorisedEnrollment](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function quizContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function videoContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function audioLessonContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function resourceContext](../component/administrator/src/Application/LearningService.php)
- `REP-02/roster` — Elenco iscritti per corso con stato accesso e avanzamento. La query è limitata al corso scelto. Il layout mostra separatamente stato dell’iscrizione, stato didattico e percentuale quando definita. La fixture verifica righe di più iscritti ed esclusione di un altro corso.
  - [component/administrator/src/Application/EnrollmentRosterService.php#public function query](../component/administrator/src/Application/EnrollmentRosterService.php)
  - [component/administrator/tmpl/enrollments/default.php#COM_JLMS_ROSTER_PROGRESS](../component/administrator/tmpl/enrollments/default.php)
  - [tests/joomla-roster.php#another course is excluded](../tests/joomla-roster.php)
- `REP-02/dates` — Primo e ultimo evento registrati e mostrati correttamente. I flussi di lezione conservano il primo avvio e aggiornano l’ultima attività. La query del roster proietta entrambi e il layout distingue primo e ultimo evento, indicando UTC.
  - [component/administrator/src/Application/LearningService.php#started_at' => $enrollment->started_at ?: $now](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/VideoProgressService.php#started_at' => $enrollment->started_at ?: $date](../component/administrator/src/Application/VideoProgressService.php)
  - [component/administrator/src/Application/EnrollmentRosterService.php#e.last_activity_at](../component/administrator/src/Application/EnrollmentRosterService.php)
  - [component/administrator/tmpl/enrollments/default.php#COM_JLMS_ROSTER_FIRST](../component/administrator/tmpl/enrollments/default.php)
- `REP-02/acl_scope` — Accesso limitato ai corsi assegnati e ruoli autorizzati. Il servizio richiede la capacità di gestione sull’asset del corso e, per chi non ha gestione amministrativa del componente, un profilo docente attivo assegnato al corso. La fixture controlla diniego su altro corso e revoca di permesso.
  - [component/administrator/src/Application/EnrollmentRosterService.php#public function course](../component/administrator/src/Application/EnrollmentRosterService.php)
  - [tests/joomla-roster.php#direct course ID cannot bypass scope](../tests/joomla-roster.php)
  - [tests/joomla-roster.php#explicit course deny refreshes stale identity](../tests/joomla-roster.php)
- `SCR-14/criterion_01` — Roster essenziale per corso mostra stato e avanzamento dell'iscritto. La tabella visualizza stato di accesso e stato di avanzamento come campi distinti, usando il corso autorizzato come ambito della query.
  - [component/administrator/src/Application/EnrollmentRosterService.php#public function query](../component/administrator/src/Application/EnrollmentRosterService.php)
  - [component/administrator/tmpl/enrollments/default.php#COM_JLMS_ROSTER_PROGRESS](../component/administrator/tmpl/enrollments/default.php)
  - [tests/joomla-roster.php#course roster includes more than one student](../tests/joomla-roster.php)
- `SCR-14/criterion_03` — Il roster soddisfa gli scenari core AC-128 con stati e filtri previsti. La query valida e applica filtri su stato, avanzamento, versione e partecipante. I test verificano ricerca, isolamento del corso, paginazione e distinzione tra sospensione e avanzamento.
  - [component/administrator/src/Application/EnrollmentRosterService.php#public function filterOptions](../component/administrator/src/Application/EnrollmentRosterService.php)
  - [tests/joomla-roster.php#completion filter independent of expiration](../tests/joomla-roster.php)
  - [tests/joomla-roster.php#native list total and pagination](../tests/joomla-roster.php)
- `SCR-15/criterion_01` — Ammissione singola con controllo stato e autorizzazione. La decisione di ammissione autorizza l’operatore nel corso, vincola la richiesta alla sua edizione e richiede stato pendente e revisione corrente. Per approvare ricontrolla pubblicazione, ammissioni, edizione e accesso del discente.
  - [component/administrator/src/Application/EnrollmentApprovalService.php#public function decide](../component/administrator/src/Application/EnrollmentApprovalService.php)
  - [component/administrator/src/Application/EnrollmentApprovalService.php#Publication, category hierarchy](../component/administrator/src/Application/EnrollmentApprovalService.php)
- `SCR-15/criterion_02` — Sospensione singola con conservazione dello storico. La mutazione individuale verifica l’ambito e registra un evento audit. Modifica lo stato e la revisione dell’iscrizione lasciando intatti i dati di completamento e attività.
  - [component/administrator/src/Application/EnrollmentStateService.php#private function scope](../component/administrator/src/Application/EnrollmentStateService.php)
  - [component/administrator/src/Application/EnrollmentStateService.php#previous_status](../component/administrator/src/Application/EnrollmentStateService.php)
  - [component/administrator/src/Application/EnrollmentStateService.php#updateObject](../component/administrator/src/Application/EnrollmentStateService.php)
- `SCR-15/criterion_03` — Riattivazione singola senza riaprire accessi impropri. La riattivazione richiede iscrizione sospesa e corso pubblicato con erogazione attiva. L’applicazione ricontrolla il piano dopo i lock di corso e iscrizione, con il corso e l’iscrizione autorizzati.
  - [component/administrator/src/Application/EnrollmentStateService.php#private function scope](../component/administrator/src/Application/EnrollmentStateService.php)
  - [component/administrator/src/Application/EnrollmentStateService.php#course_delivery_enabled === 1](../component/administrator/src/Application/EnrollmentStateService.php)
  - [component/administrator/src/Application/EnrollmentStateService.php#public function apply](../component/administrator/src/Application/EnrollmentStateService.php)
- `LIFE-01/criterion_04` — Nessuna eliminazione a cascata non dichiarata. La purga controlla prima le dipendenze didattiche e poi rimuove esplicitamente soltanto le revisioni editoriali del corso. La conferma IT/EN dichiara tale rimozione e il test verifica la cancellazione delle revisioni del corso selezionato.
  - [component/administrator/src/Model/CourseModel.php#Remove only this item](../component/administrator/src/Model/CourseModel.php)
  - [component/administrator/language/it-IT/com_jlms.ini#COM_JLMS_COURSE_PURGE_CONFIRM](../component/administrator/language/it-IT/com_jlms.ini)
  - [tests/joomla-courses-ui.php#Confirmed purge removes only this course editorial revisions](../tests/joomla-courses-ui.php)
- `DOC-01/criterion_01` — Guida installazione descrive requisiti minimi Joomla, PHP e database. La sezione di installazione elenca Joomla, PHP, MySQL, MariaDB e motore/codifica richiesti.
  - [README.md#Requisiti documentati](../README.md)
- `DOC-01/criterion_03` — Procedure di upgrade, backup e rollback documentate. Il README rimanda all’upgrade e la procedura di rilascio distingue la verifica operativa dell’aggiornamento dal ripristino dei siti tramite backup.
  - [README.md#Per dettagli su upgrade](../README.md)
  - [docs/RELEASE_PROCEDURE.md#Gestione errori e recupero](../docs/RELEASE_PROCEDURE.md)
  - [docs/RELEASE_PROCEDURE.md#backup/ripristino verificati](../docs/RELEASE_PROCEDURE.md)
- `DOC-01/criterion_04` — Installazione pulita e aggiornamento con dati esistenti verificati con evidenze. Il workflow esegue installazione su fixture Joomla e upgrade del pacchetto con dati conservati. Le asserzioni retained rileggono dati e configurazioni dopo l’aggiornamento. L’esito qui riguarda la copertura automatica.
  - [.github/workflows/ci.yml#Upgrade the full package with retained data and enabled providers](../.github/workflows/ci.yml)
  - [tests/joomla-smoke.php#retained-data mode](../tests/joomla-smoke.php)
  - [tests/install-package.php#preserve the registration consent plugin state](../tests/install-package.php)
- `LCM-01/criterion_02` — preservare storico personale, frequenze, progressi e attestati. L’archiviazione cambia lo stato del corso attraverso il workflow e chiude le due abilitazioni sul corso conservando la versione pubblicata. La transazione non elimina iscrizioni, progressi o attestati.
  - [component/administrator/src/Application/PublicationService.php#Preserve student history](../component/administrator/src/Application/PublicationService.php)
  - [tests/joomla-publication-review.php#archive](../tests/joomla-publication-review.php)
- `LCM-02/criterion_06` — Controlli ACL sulle operazioni singole e massive. Le richieste singole e massive di cestinazione/ripristino passano dagli stessi controlli POST/CSRF e da core.edit e core.edit.state per ogni asset. Il rifiuto di una riga provoca il rollback. La purga singola richiede core.delete sul corso.
  - [component/administrator/src/Controller/CourseController.php#private function lifecycle](../component/administrator/src/Controller/CourseController.php)
  - [component/administrator/src/Model/CourseModel.php#public function changeStates](../component/administrator/src/Model/CourseModel.php)
  - [component/administrator/src/Model/CourseModel.php#public function purgeEmpty](../component/administrator/src/Model/CourseModel.php)
