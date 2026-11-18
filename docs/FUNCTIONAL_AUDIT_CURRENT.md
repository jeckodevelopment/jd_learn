# Audit funzionale JD Learn 1.0.0

Requisiti in perimetro: **214**. Criteri: **757**.

- Implementati: **243**.
- Mancanti con lacuna accertata: **1**.
- Esaminati con esito non verificabile: **2**.
- Da esaminare: **511**.

Percentuale funzionale globale: **non determinabile**.
Il calcolo pesa allo stesso modo ogni requisito. Gli esiti dell’audit sono distinti dall’esecuzione dei test e dall’accettazione manuale Joomla.

## Lacune confermate

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

- `ISC-08/lesson_scope` — Restrizioni di lezione non possono ampliare l'accesso al corso. I contesti per quiz, video, audio e materiali protetti risolvono un’iscrizione autorizzata prima di risolvere una lezione, vincolata alla versione assegnata. Disponibilità e prerequisiti possono restringere ulteriormente quel contesto.
  - [component/administrator/src/Application/LearningService.php#private function authorisedEnrollment](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function quizContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function videoContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function audioLessonContext](../component/administrator/src/Application/LearningService.php)
  - [component/administrator/src/Application/LearningService.php#public function resourceContext](../component/administrator/src/Application/LearningService.php)
- `LIFE-01/criterion_04` — Nessuna eliminazione a cascata non dichiarata. La purga controlla prima le dipendenze didattiche e poi rimuove esplicitamente soltanto le revisioni editoriali del corso. La conferma IT/EN dichiara tale rimozione e il test verifica la cancellazione delle revisioni del corso selezionato.
  - [component/administrator/src/Model/CourseModel.php#Remove only this item](../component/administrator/src/Model/CourseModel.php)
  - [component/administrator/language/it-IT/com_jlms.ini#COM_JLMS_COURSE_PURGE_CONFIRM](../component/administrator/language/it-IT/com_jlms.ini)
  - [tests/joomla-courses-ui.php#Confirmed purge removes only this course editorial revisions](../tests/joomla-courses-ui.php)
- `LCM-01/criterion_02` — preservare storico personale, frequenze, progressi e attestati. L’archiviazione cambia lo stato del corso attraverso il workflow e chiude le due abilitazioni sul corso conservando la versione pubblicata. La transazione non elimina iscrizioni, progressi o attestati.
  - [component/administrator/src/Application/PublicationService.php#Preserve student history](../component/administrator/src/Application/PublicationService.php)
  - [tests/joomla-publication-review.php#archive](../tests/joomla-publication-review.php)
- `LCM-02/criterion_06` — Controlli ACL sulle operazioni singole e massive. Le richieste singole e massive di cestinazione/ripristino passano dagli stessi controlli POST/CSRF e da core.edit e core.edit.state per ogni asset. Il rifiuto di una riga provoca il rollback. La purga singola richiede core.delete sul corso.
  - [component/administrator/src/Controller/CourseController.php#private function lifecycle](../component/administrator/src/Controller/CourseController.php)
  - [component/administrator/src/Model/CourseModel.php#public function changeStates](../component/administrator/src/Model/CourseModel.php)
  - [component/administrator/src/Model/CourseModel.php#public function purgeEmpty](../component/administrator/src/Model/CourseModel.php)
