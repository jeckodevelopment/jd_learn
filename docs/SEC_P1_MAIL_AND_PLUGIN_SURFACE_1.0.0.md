# SEC-P1 — inventario delle code email e dei subscriber Joomla

**Baseline:** JD Learn `0.64.16-dev`, 20/11/2026. Questo documento separa i controlli implementati e verificabili dagli esiti di collaudo ancora mancanti. Non certifica il completamento di SEC-P1 né di SEC-P5.

## Coda SMTP — identità del destinatario

**Finding SEC-P1/MAIL-IDENTITY:** alcune notifiche di dominio venivano prodotte con email e nome, ma senza il relativo `recipient_user_id`. In assenza di tale identità, dopo una modifica del recapito o il blocco amministrativo non era possibile revalidare correttamente il destinatario al momento dell'invio. La tranche 0.64.15-dev aveva già corretto `CourseCommunicationService`; la 0.64.16-dev estende il controllo ai restanti produttori.

| Flusso di produzione | Servizio | Verifica introduzione/correzione |
|---|---|---|
| Richiesta iscrizione, iscrizione confermata, completamento lezione testuale | `LearningService` | ID dell'attore Joomla |
| Completamento quiz | `QuizAttemptService` | ID dell'attore Joomla |
| Completamento video | `VideoProgressService` | ID dell'attore Joomla |
| Iscrizione manuale | `ManualEnrollmentService` | ID utente assegnato |
| Approvazione/rifiuto iscrizione | `EnrollmentApprovalService` | ID del richiedente |
| Sospensione/riattivazione/cancellazione iscrizione | `EnrollmentStateService` | ID del titolare dell'iscrizione |
| Attestato disponibile | `CertificateIssuer` | ID titolare attestato |
| Promemoria fine corso | `CourseExpiryReminderService` | ID titolare iscrizione aggiunto alla query |
| Pubblicazione lezione | `LessonReleaseNotice` | ID già passato |
| Cambio versione corso | `CourseVersionChangeNotice` | ID già passato |
| Richiesta al gestore | `EnrollmentManagerNotice` | ID già passato e ACL al momento dell'invio |
| Docente assegnato | `TeacherAssignmentNotice` | ID già passato |
| Messaggi corso e messaggi di prova | `CourseCommunicationService` | ID introdotto nella 0.64.15-dev |
| Annuncio nuovo corso | `CourseAnnouncementService` | API dedicata `enqueueAnnouncement`, ID e revisione del consenso già vincolati |

`MailQueue::enqueue` rifiuta nuovi messaggi senza `recipient_user_id`; `MailQueue::dispatch` confronta per tutti i template il recapito della coda con quello dell'account Joomla corrente, non bloccato. Le righe legacy senza identificativo vengono `skipped` e i campi `recipient_email`, `recipient_name`, `template_data` sono azzerati. È una scelta fail-closed: una notifica storica non verificabile non viene inviata. La garanzia riguarda l'istante del controllo, non elimina la finestra temporale fino all'accettazione SMTP.

**Gate automatico:** `tests/sec-p1-mail-recipient-identity.py` censisce tutti i 13 produttori di `MailQueue::enqueue` e il produttore di `enqueueAnnouncement`, verifica che ogni produttore passi l'ID utente e impedisce nuove chiamate non classificate. `tests/joomla-smoke.php` verifica già lo stato corrente dell'account, email obsoleta, ID nullo/inesistente e blocco usando il database Joomla. Una CI verde dimostra l'invariante del codice e il comportamento della guardia, non la consegna SMTP reale.

## Subscriber e task Joomla — superficie da esaminare nel gate complessivo

| Plugin | Eventi dichiarati nel codice | Rischio da verificare | Stato |
|---|---|---|---|
| `privacy/jdlearn` | onPrivacyCollectAdminCapabilities, onPrivacyExportRequest, onPrivacyCanRemoveData, onPrivacyRemoveData | Scoping dati utente, attestati, retention, erasure e workflow nativo | Review codice parziale, accettazione privacy end-to-end pendente |
| `user/jdlearnconsent` | onContentPrepareForm, onUserAfterSave | Consenso opt-in mai implicito, account Joomla fresco, recapiti corretti | Guardie su creazione utente e richiesta verificate da codice/test; HTTP/SMTP pendenti |
| `task/jlms` | onTaskOptionsList, onExecuteTask, onContentPrepareForm | Isolamento task, privilegi Scheduler, condizioni di concorrenza | Task censiti: `jlms.quiz.expire`, `jlms.materials.cleanup`, `jlms.mail.dispatch`; test scheduler/SMTP completo pendente |
| `actionlog/jdlearn` | onJlmsCourseTransition | Identità autore, spoofing eventi, minimizzazione metadati | Il subscriber verifica client amministrativo, actor ID e azioni ammesse; review E2E pendente |

**Residui SEC-P1 per chiusura:** matrice ruoli/oggetti su tutti i 37 controller e relativi servizi, validazione di query SQL/SSRF/output su tutti gli ingressi, upload/file privati e provider video, verifica completa di plugin ed eventi, prove negative HTTP con guest/proprietario/estraneo/docente/manager delegato/amministratore e analisi di concorrenza su Scheduler/SMTP, riesame finale dei findings con classificazione e prove sulla build candidata. Nessun test manuale è dichiarato eseguito. La percentuale funzionale minima globale e i due criteri SEC-P1 ancora mancanti non devono essere modificati per questa tranche.
