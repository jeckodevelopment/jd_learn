# LMS per Joomla — progettazione tecnica esecutiva

Versione documento 1.2 · 4 novembre 2026 · Baseline tecnica del primo rilascio

Questo documento traduce la specifica funzionale consolidata 1.4 e i contratti applicativi 1.1 in decisioni implementative. I nomi `jlms`, `com_jlms` e il namespace `Jlms` sono prefissi di lavoro neutrali. Non contiene nomi di committenti né dipendenze da prodotti concorrenti. Il pacchetto allegato è materiale di progettazione: schema, interfacce e contratti non costituiscono un componente già installabile o collaudato.

La specifica funzionale conserva la precedenza sulle funzionalità; questo documento fissa la loro realizzazione tecnica. Una divergenza va risolta aggiornando entrambi e registrando una decisione, senza cambiare silenziosamente il comportamento concordato. Vendita, abbonamenti e attribuzione dei gruppi da parte di estensioni commerciali restano fuori dal componente.

## 1. Baseline e decisioni architetturali

| ID | Decisione | Motivazione e conseguenza |
|---|---|---|
| ADR-001 | Joomla 6, MVC nativo, DI e Web Asset Manager | Nessun framework applicativo parallelo, nessuna modifica del core |
| ADR-002 | PHP 8.3 come minimo di linguaggio; 8.4 come ambiente di riferimento | Matrice CI su entrambi; niente funzionalità PHP successive al minimo |
| ADR-003 | MySQL 8.0.13+ e MariaDB 10.6+, InnoDB, utf8mb4 | CI anche su MySQL 8.4; PostgreSQL non supportato dal primo rilascio |
| ADR-004 | Monolite modulare nello stesso sito Joomla | Servizi applicativi interni, non microservizi; worker Scheduler/CLI sugli stessi dati |
| ADR-005 | Contratti video in lib_jlms, provider nel gruppo jlmsvideo | Installazione di nuovi provider senza modificare il componente |
| ADR-006 | Identità corso stabile e dati promozionali correnti modificabili; programmi pubblicati immutabili | Salva ordinario con storico Joomla, nuova versione per aggiornamenti al programma; iscrizioni e tentativi assegnati non cambiano |
| ADR-007 | Stato didattico nel DB, oggetti binari in storage protetto | Backup coordinato, nessun video nel DB o URL pubblico implicito |
| ADR-008 | Endpoint MVC same-origin, nessuna API REST esterna iniziale | Sessione/CSRF/ACL core; webservices nativi solo in futura estensione |
| ADR-009 | Outbox e job con consegna almeno una volta | Consumatori idempotenti; nessuna promessa di exactly-once per email/provider esterni |
| ADR-010 | Cache solo promozionale; dati personali no-store | La cache non deve conservare autorizzazioni o risultati di quiz |
| ADR-011 | Core Joomla per account, categorie, ACL, lingue e strumenti editoriali | Collegamenti tramite servizi core, niente tabelle duplicate |
| ADR-012 | Proprietà e accesso verificati prima di idempotenza e file/token | Una vecchia risposta non può aggirare una revoca |

Sono minimi di compatibilità, non raccomandazioni a installare versioni obsolete. Bloccare nella pipeline le patch esatte CMS/PHP/DB e le dipendenze realmente supportate; usare le patch di sicurezza disponibili. La documentazione Joomla consultata presenta 6.1 come ramo corrente e 6.2 come upcoming; non si fonda il progetto su API future. Le evidenze da sorgenti tag 6.0.0 sono riferimenti storici riproducibili, non dimostrazione della compatibilità con ogni 6.x. La release richiede collaudo sul minimo Joomla dichiarato e sulla minor corrente.

## 2. Distribuzione e struttura del repository

Il prodotto viene distribuito come `pkg_jlms`, contenente in ordine libreria contratti, componente, plugin di integrazione core, quattro provider. Il package controlla le dipendenze e impedisce che una rimozione isolata della libreria rompa le estensioni dipendenti. Installazione e configurazione sono distinte: nessuna attivazione di servizi remoti prima della configurazione.

| Percorso sorgente | Contenuto |
|---|---|
| packages/pkg_jlms | Manifest package, installer, language e costituenti generati |
| libraries/jlms/src/Contracts | DTO, interfacce, errori ed eventi pubblici versione 1 |
| component/administrator/services/provider.php | Registrazione MVCFactory, dispatcher, router, componente e servizi |
| component/administrator/src/Extension | JlmsComponent con capacità core applicabili |
| component/administrator/src/Controller, Model, Table, View | MVC di amministrazione; form/list/admin model Joomla |
| component/administrator/src/Application | Casi d’uso, policy, transazioni, mapper DTO |
| component/administrator/src/Domain | Regole didattiche, aggregati, stati ed errori senza HTTP |
| component/administrator/src/Infrastructure | Repository Joomla DatabaseInterface, storage, provider bridge, mail/outbox |
| component/administrator/forms, access.xml, config.xml | Form XML, filtri, permessi e configurazione core |
| component/administrator/sql | Installazione e aggiornamenti versionati |
| component/site/src/Controller, Model, View, Service | MVC pubblico, router e modelli di lettura autorizzati |
| component/site/tmpl | Layout di catalogo, categoria, corso, aula, quiz, percorsi, docenti, dashboard, attestati |
| component/site/layouts | Parti riutilizzabili renderizzate tramite LayoutHelper |
| media/com_jlms | joomla.asset.json, CSS, JS compilato, manifest CSS pubblico |
| plugins/jlmsvideo/{youtube,vimeo,cloudflarestream,html5} | Manifest, services/provider.php, src/Extension, adapter e lingue |
| plugins/{privacy,finder,actionlog,task,content,fields}/jlms | Solo integrazioni pertinenti ai gruppi core; non provider in system |
| tests/{unit,integration,e2e,security,performance} | Scenari della matrice funzionale e tecnica |
| build e docs | Packaging riproducibile, manifest dipendenze e documentazione |

Namespace del componente: `Jlms\Component\Jlms\Administrator` e `...\Site`; plugin `Jlms\Plugin\Jlmsvideo\Youtube` ecc. Libreria: `Jlms\Contracts`. Prima della prima release congelare nome e namespace in una sola modifica verificata su manifest, SQL, asset, lingue e override; non è una decisione che blocchi la progettazione.

Nessun file PHP eseguito via URL proprio: entry point Joomla. Nessun Composer/NPM richiesto al cliente. Le dipendenze runtime del prodotto devono essere incluse nel pacchetto con autoload isolato, evitando conflitti con le versioni del CMS; le dipendenze solo di sviluppo non vengono distribuite.

## 3. Flusso MVC e dependency injection

Il controller sceglie il task ammesso e metodo HTTP, verifica sessione/CSRF quando richiesti, valida la forma dell’input e costruisce il comando. Il servizio applicativo effettua i controlli di risorsa, applica le regole dominio e delimita la transazione. Il repository esegue query parametrizzate; il mapper forma un output esplicito. La view HTML prepara dati e WAM; il template esegue solo presentazione con escape contestuale.

Non inserire regole di completamento nei template, query nei controller o controlli ACL soltanto nei pulsanti. I modelli core possono gestire form, check-out, list state e paginazione; non devono chiamare save generici che aggirino il servizio applicativo. Gli eventi editoriali Joomla devono essere emessi una volta nel percorso ufficiale di salvataggio, non sia dal model sia dal servizio.

Registrare dipendenze tramite provider del componente. Per l’iniezione nei controller/model usare MVCFactory personalizzata compatibile col core, senza service locator globale sparso. Servizi stateless per richiesta, identità e policy non singleton permanenti. Il worker costruisce un contesto esplicito con owner e scope del job; non eredita una sessione amministratore né Super User fittizio.

## 4. Integrazioni core: mappa esecutiva

| Funzione core | Realizzazione nel componente | Vincolo di collaudo |
|---|---|---|
| Utenti, login, MFA, sessione | Identità Joomla; collegamento subject LMS al core user_id | Nessun database password; disabilitazione utente verificata sui task sensibili |
| Gruppi e livelli di accesso | course.content_access e catalog_access referenziano viewlevels; autorizzazione sui livelli correnti | Non confondere livello visivo con gruppo; nessuna scrittura di gruppi dall’iscrizione |
| ACL | Asset componente > categoria > corso; asset question/pathway; asset teacher con compatibilità dei parent esistenti | core.create/edit/edit.own/edit.state/delete e permessi jlms distinti; modifica del profilo da account collegato o segreteria autorizzata |
| Categorie | com_categories per corsi/domande/percorsi; docente senza selettore o gestione categorie | Conservare eventuali categorie tecniche .teacher preesistenti e ACL; nessuna tassonomia docente richiesta all’operatore |
| Tag | Servizio/observer core e content type per entità pubbliche | Solo metadati visibili, niente tag privati resi pubblici da Finder |
| Campi personalizzati | Contesti course/lesson/teacher/pathway, FieldsServiceInterface, gestore e plugin core; nessun designer/storage parallelo | Valori didattici congelati nella versione; campi promozionali del profilo corrente secondo accesso/lingua/ACL dei valori |
| Multilingua e associazioni | language e associazioni per identità course/teacher/pathway | Le traduzioni sono risorse distinte; niente progressi trasferiti automaticamente |
| Override e layout | tmpl e LayoutHelper; WAM e contratto CSS stabile | Override testato con template core e template alternativo |
| Menu, SEF, breadcrumb | RouterView e regole menu core; Pagina docente e Corsi di un docente con selettore del profilo, oltre alle viste già previste | Controllare autorizzazioni anche con ID/URL diretto; niente redirect aperti |
| Editor, content plugin e Media | Editor/filtri Joomla sul testo; media manager per immagini promozionali | Non rendere pubblici i materiali protetti tramite media manager |
| Workflow | WorkflowServiceInterface e associazioni core; stato editoriale separato da versione didattica | Solo transizione approvata pubblica una versione validata |
| Cronologia e check-in/out | Versionable model/table, content type e com_contenthistory su corso/docente; checked_out core | Confronto/ripristino editoriali anche sul corso pubblicato tramite salvataggio autorizzato; recupero di struttura differente in nuova bozza, mai mutare lo storico assegnato |
| Ricerca intelligente | plg_finder_jlms indicizza solo descrizioni e metadati promozionali | Nessun testo lezione protetta, quiz, file o URL firmato nell’indice |
| Privacy | plg_privacy_jlms esporta/cancella/anonimizza dati secondo policy | Non elimina in cascata attestati e prove senza policy; rispetta richieste core |
| Action Logs | plg_actionlog_jlms, payload minimi e chiavi traducibili | Mutazioni sensibili tracciate; nessuna risposta quiz/segreto nei log |
| Scheduler e CLI | plg_task_jlms e comandi registrati nel lifecycle console Joomla | Lock, lease, batch, retry e scope; nessun cron pubblico |
| Mail Templates | Template core per iscrizione, richiesta, completamento, attestato | Lingua destinatario, parametri allowlist, outbox e tracciamento invio |
| WAM/cache/config/language | Registrazione asset e form core, JText/Text, Language overrides | Nessuna stringa visibile hardcoded; accesso mai deciso dalla cache |

La dichiarazione delle interfacce in Extension da sola non integra queste funzioni. Per ogni capacità servono helper, manifest/config, observer/eventi e prove su creazione, modifica, cancellazione e ripristino. I nomi concreti di trait/firme sono verificati sul core target prima di codificarli; riutilizzare i pattern com_content senza copiare logica commerciale non pertinente.

I contenuti didattici non vengono salvati in `#__content`: si registrano i content type necessari agli strumenti core. Lezioni versionate e moduli ereditano ACL dal corso e non generano migliaia di asset ACL indipendenti. Entità con ACL autonoma hanno asset_id. Il context dei campi lezione usa ID fisico di bozza; alla pubblicazione si memorizzano definizioni rilevanti e valori nello snapshot. I parametri decorativi live restano separati dalle regole storiche.

### 4.1 Aggiornamento ordinario e profili docente — revisione 1.3

L’editor del corso pubblicato usa Salva/Salva e chiudi senza imporre ritiro o ricreazione. Il modello valida una allowlist di dati editoriali, conserva stato e puntatore alla versione didattica, aggiorna la proiezione pubblica corrente e registra una revisione tramite il servizio Content History di Joomla. Il ripristino passa dalle stesse ACL, dal checkout e dai controlli di concorrenza; non ripristina indiscriminatamente stato, puntatori o record di apprendimento.

Modifica programma crea o riapre una copia completa con nuovi ID fisici e logical_key coerenti; rimappa moduli, lezioni, quiz, revisioni domanda e associazioni ai materiali. I blob immutabili possono essere riusati, ma le associazioni e l’autorizzazione al download restano riferite alla nuova versione. Non si copiano iscrizioni, progressi, tentativi o attestati. Pubblica aggiornamento usa validazioni e transizione workflow autorizzata in una transazione breve; l’effetto sui nuovi iscritti è mostrato prima dell’azione. Conservare versione e risorse precedenti. Migrazione ed equivalenze sono un comando esplicito distinto.

Il docente rimane un profilo collegabile facoltativamente a un account Joomla e associato ai corsi; non coincide con un account o una categoria. Nei form ordinari non sono presenti creazione/selezione/gestione categorie docente. I contenitori tecnici preesistenti e i relativi asset si mantengono per compatibilità; il percorso di creazione non chiede di configurarli. Contesti Fields e gruppi di campi core forniscono i valori aggiuntivi. Il docente collegato con edit-own può modificare il proprio testo e i campi con core.edit.value, dal frontend o dal backend se dispone dell’accesso amministrativo. La segreteria richiede le capacità di gestione profili e i permessi sul record. Account, stato e visibilità restano protetti lato server.

Le viste teacher e teacher/courses ricevono l’ID del profilo dal menu/router nativo, applicano accesso/lingua/stato al profilo e alle query dei corsi e paginano gli elenchi. I campi si preparano con FieldsHelper secondo le ACL correnti. Non esporre email dell’account, dati dello studente o risorse riservate. La modifica del profilo usa form MVC/sessione/CSRF core e conserva l’input in caso di errore. La sola associazione corso-docente non attribuisce gruppi o permessi.

Queste sono prescrizioni tecniche; lo stato verificato della0.31 e le parti ancora mancanti si leggono in REQUIREMENTS_STATUS.csv e M01_ACCEPTANCE.md. In particolare non dedurre una conferma di impatto, migrazione o collaudo SEF completo dalla sola presenza di un pulsante o layout.

## 5. Policy di accesso e confini di fiducia

`AccessPolicy` espone assertCatalogVisible, assertCanEnroll, assertCanLearn, assertCanAttempt, assertCanDownload, assertCanManage e assertOwnCertificate. Il subject viene sempre dall’identità, mai da user_id inviato dal browser. Una risorsa figlia viene risolta insieme al suo corso/versione; non basta verificarne separatamente gli ID.

Per apprendere devono essere veri: utente attivo e autenticato; corso e categoria effettivamente disponibili; livello content_access attuale fra i livelli autorizzati; iscrizione attiva propria e alla versione indicata; periodo edizione compatibile; prerequisiti della lezione soddisfatti. L’approvazione di una richiesta o un’importazione non sostituiscono l’accesso Joomla. Gli autori non ottengono automaticamente i dati personali degli studenti.

La visibilità promozionale può essere più ampia dell’accesso al contenuto. Il livello del corso non può superare una restrizione di categoria non soddisfatta. L’anteprima è una pubblicazione esplicita della singola risorsa: nessuna derivazione automatica dal titolo visibile. Lezioni in anteprima non creano progressi anonimi e non conferiscono attestati.

Revoca: invalidare l’apprendimento alla richiesta seguente. Non fidarsi dei livelli già risolti e conservati nella sessione a tempo indeterminato; per task protetti ricalcolare dal core appartenenze/abilitazione utente e livelli, senza cache lunga del risultato. Una richiesta già in corso e token/video già consegnati hanno validità residua: dichiarare questa limitazione. L’attestato storico proprio usa una policy separata, anche dopo perdita del corso; la verifica pubblica è opzionale e minimizzata.

Permessi aggiuntivi: jlms.enrollments.manage, jlms.enrollments.import, jlms.enrollments.migrate, jlms.progress.reset, jlms.quiz.manage, jlms.assessment.correct, jlms.reports.view, jlms.reports.export, jlms.certificates.manage, jlms.certificates.revoke, jlms.materials.manage, jlms.teachers.manage, jlms.pathways.manage, jlms.audit.view. Configurazione con core.options; stato e output dei job richiedono ownership e permesso originario ancora valido. Definirli in access.xml con etichette/descrizioni traducibili. Ambito per categoria/corso quando applicabile, mai semplice controllo core.manage per tutte le operazioni.

## 6. Modello dati e convenzioni

Lo schema SQL allegato definisce 37 tabelle del componente. Identificativi interni BIGINT UNSIGNED; gli ID ricevuti/restituiti in JSON sono stringhe decimali per non perdere precisione JavaScript. Prima della conversione PHP validare formato e limite <=PHP_INT_MAX (runtime64bit richiesto); non castare una stringa fuori range. Il DB unsigned non autorizza ID applicativi oltre il limite supportato dai DTO PHP. ID core restano del tipo compatibile col core. Timestamps UTC DATETIME(6), API ISO 8601 con Z, visualizzazione nel fuso Joomla. UUID casuali solo per subject, chiavi logiche e sessioni, non sostituiscono ACL.

LONGTEXT JSON validati nell’applicazione evita differenze tra MySQL e MariaDB. Oggetti interni non diventano automaticamente payload pubblici. Nessuna FK verso tabelle core, per non interferire con lifecycle Joomla; FK RESTRICT interne dove possibile e validazioni semantiche nel servizio. Il dizionario CSV è l’elenco fisico delle colonne, tipi e default; gli stati e significati sono definiti qui.

| Area | Tabelle principali | Regola di identità |
|---|---|---|
| Catalogo | courses, teachers, course_teachers, pathways, pathway_courses | Corso stabile tra versioni; catalogo e learning distinti |
| Didattica | course_versions, modules, lessons, prerequisites, editions | Ogni nodo appartiene a una sola versione; logical_key collega copie equivalenti |
| File | assets, attachments | Un asset è un blob immutabile; collegamenti corso o lezione |
| Quiz | questions, question_revisions, quizzes, quiz_questions | Revisione domanda immutabile, snapshot per tentativo |
| Studenti | subjects, enrollments, enrollment_requests | subject stabile; utente core anonimizzabile |
| Fruizione | lesson_progress, progress_sessions | Una proiezione per iscrizione/lezione; sessioni temporanee |
| Tentativi | attempts, active_attempts, attempt_questions, attempt_answers, quiz_mutations, assessment_revisions | Slot univoco del tentativo aperto, risposte salvate revisionate |
| Attestati | certificate_templates, certificates, certificate_documents | Emissione unica per iscrizione/versione |
| Migrazioni | version_equivalences, enrollment_migrations | Copia controllata con fonte preservata |
| Esecuzione | jobs, outbox, delivery_receipts, idempotency, anomalies | Lease, risultati idempotenti e anomalie esplicite |

`subjects.user_id` univoco nullable: crea un subject mediante insert con gestione del conflitto univoco; ritrova quello esistente. Non basarsi su query read-then-insert senza vincolo. Anonimizzazione imposta user_id NULL e rimuove identificatori nei dati secondo policy; gli enrollment collegati conservano subject_key senza email. Ogni modifica applicativa incrementa revision; una data modificata non sostituisce una revisione per il controllo concorrente.

## 7. Versioni, edizioni e pubblicazione

Il corso stabile conserva titolo/alias/lingua/categoria/ACL e riferimento alla versione corrente per nuove iscrizioni. course_versions conserva titolo didattico, regole, presentazione e campi necessari alla riproducibilità. Moduli, lezioni, quiz e allegati appartengono alla versione. Ogni copia conserva logical_key per gli elementi logicamente equivalenti, ma riceve ID fisici nuovi.

Stati versione: draft, published, retired. Published vieta cambiamenti strutturali: ordine, prerequisiti, sorgente video, regola di completamento, quiz, punti, soglie e file didatticamente rilevanti. Correzioni puramente editoriali seguono cronologia e audit; se cambiano il significato, nuova versione. Un file sostitutivo è un nuovo asset: non sovrascrivere il blob storico. Titoli promozionali live non riscrivono attestati o snapshot già emessi.

Ogni versione pubblicata dispone di un’edizione di default codice `default-vN`, non nullable, usata quando non si configurano coorti. Le edizioni aggiuntive consentono periodi/capienza e ripetizioni esplicite senza introdurre iscrizioni NULL difficili da rendere uniche. Nessuna edizione a pagamento. L’endpoint enrollment.start con solo course_id sceglie l’edizione default della current_version; una futura scelta manuale dell’edizione richiede un campo ammesso e UI coerente, non accetta un ID arbitrario.

Pubblicazione: lock sul corso e versione, check revision, workflow e permessi; validazione struttura non vuota, requisiti aciclici, lezioni obbligatorie, durata/rule provider coerenti, quiz completo, materiali pronti, template eventuale valido e snapshot campi. Calcolo structure_hash da serializzazione canonica; creazione/abilitazione edizione default e cambio current_version_id nello stesso commit. Validazioni remote lente vengono preparate prima; la transazione non attende API video. Un corso valutabile senza lezioni obbligatorie viene rifiutato. La modalità esplicita consultazione, course_versions.rules_json.mode=reference, consente zero obbligatori ma disabilita completamento e attestati; il progresso viene mostrato come non applicabile.

Migrazione studenti: preview restituisce mapping, differenze e conteggi; conferma genera un job con piano immutabile/hash. Per ogni iscrizione crea/ritrova iscrizione target in edizione della versione destinazione, trasferisce solo completamenti approvati nelle equivalenze e registra enrollment_migrations. Non muove gli attempt né modifica attestati sorgente. Il job idempotente può riprendere dal cursore; incongruenze vengono riportate per record. Nessun trasferimento automatico di quiz con domande o soglia diverse.

## 8. Stati e invarianti applicative

| Aggregato | Stati ammessi | Invariante |
|---|---|---|
| Iscrizione | active, completed, suspended, withdrawn, migrated | Perdita accesso Joomla è condizione corrente, non modifica dello storico |
| Richiesta | pending, approved, rejected, withdrawn | Approvazione crea/ritrova iscrizione ma non assegna gruppi |
| Lezione | started, completed | Una revisione/sorgente differente non eredita coverage implicitamente |
| Tentativo | open, submitted, timed_out, invalidated | Solo una transizione terminale effettiva; nessuna riapertura in place |
| Asset | quarantine, ready, rejected, pending_delete | Solo ready collegabile a pubblicazione/riproduzione |
| Attestato | pending_render, issued, revoked, render_failed | PDF generato da snapshot; revoke manuale motivato |
| Job | queued, running, retry_wait, succeeded, failed, cancelled | Lease obbligatoria per batch; termine/cancel non annulla commit precedenti |
| Outbox | pending, processing, delivered, retry_wait, delivery_unknown, failed | Evento univoco; effetto esterno non sempre deduplicabile |

AccessPolicy, CompletionPolicy, QuizPolicy, RetentionPolicy sono oggetti separati. Gli invarianti non dipendono dal percorso UI e si applicano anche a CSV, CLI, job e plugin. Percentuali arrotondate soltanto per visualizzazione: completamento video confronta covered_ms*100 >= duration_ms*90; quiz usa score*10000 >= max_points*pass_basis_points. max_points >0; non usare float per esiti.

## 9. Iscrizione e progressi del corso

EnrollmentService.start: verifica policy attuale e modo iscrizione, lock edizione se capienza limitata, risolve subject, cerca iscrizione univoca subject/edition e, se assente, crea con required_total dalla versione pubblicata. Reinvio restituisce la stessa iscrizione; una sospensione non viene annullata da “Inizia”. Nessuna scrittura dei gruppi Joomla. Non esistono iscrizioni a una bozza.

Progressi: le lezioni obbligatorie contano un’unità; opzionali non riducono/aumentano il denominatore. completed_total è una proiezione verificabile su lesson_progress, incrementata soltanto quando una lezione passa per la prima volta a completed. Al raggiungimento del totale si aggiorna l’iscrizione e si accoda un solo evento di completamento. Il ricalcolo amministrativo riconcilia le proiezioni; una perdita di idoneità successiva produce anomaly, non revoca silenziosa di un attestato.

Per un percorso: media dei progressi dei corsi, peso uguale. Corsi non accessibili non vengono trasformati in completati né omessi per gonfiare il risultato; UI distingue bloccato/non iniziato. Non è richiesto un attestato del percorso nel primo rilascio.

## 10. Telemetria video e concorrenza

Il componente emette progress_session_id da lesson.get o video.playback dopo policy. La sessione appartiene a iscrizione, lezione e source_revision; scadenza inattività 2 ore, rinnovabile dopo controllo accesso. Batch ogni 20 secondi, configurabile 15–30, jitter ±20%, flush su pausa/cambio pagina dove possibile. Nessuna dipendenza dal solo unload; una pagina chiusa prima dell’invio può perdere l’ultimo intervallo non persistito.

Per ogni sessione il client invia una sequenza di batch crescente senza salti (partenza 1). Mantiene al massimo un batch in volo; ritrasmette la stessa sequenza con lo stesso contenuto fino a conferma. Non usa localStorage per token/segreti/risposte quiz. Il server conserva sequence_no e hash dell’ultimo batch: stessa sequenza/hash è retry; stessa sequenza diverso hash =>409; sequenza precedente => stale 409 con prossimo valore, gap =>409 e richiesta di riallineamento. Una nuova sessione non riusa numeri della precedente.

Eventi position/play/pause/seeking/seeked/ended hanno event_id, posizione, tempo monotono e rate consentito. Il server ricostruisce intervalli soltanto tra campioni contigui in stato play, senza seek, con avanzamento compatibile col tempo trascorso e playback rate. seek aggiorna l’ancora senza attribuire il salto alla visione; ended da solo non completa. Max 100 eventi/64KiB per batch, posizioni entro durata, intervallo massimo 30 secondi alla velocità ammessa, tolleranza clock2s. Dati incoerenti rifiutati o esclusi dal conteggio con codice esplicito.

Gli intervalli sono [start_ms,end_ms), ordinati e uniti anche tra device. Si possono usare bucket conservativi di 1 secondo: conta soltanto il secondo interamente coperto, unione senza doppio conteggio; il DTO interno memorizza run di bucket, al massimo 10.000 run per lezione. Oltre il limite non approssimare verso l’alto: aggregazione conservativa o blocco diagnostico. Durata massima video R1 8 ore, limite configurabile con test di memoria. coverage_json non è un registro illimitato di eventi; eventi grezzi non vengono conservati oltre il batch.

Questa telemetria misura dichiarazioni del player/browser e limita duplicati/salti: non certifica attenzione, identità o visione antifrode. Una richiesta falsificata da un utente autorizzato non può essere considerata prova d’esame sorvegliato.

## 11. Transazioni e ordine dei lock

Ogni servizio usa DatabaseInterface Joomla con query bind e transazioni. Tutte le mutazioni sullo stesso enrollment acquisiscono per primo il lock dell’iscrizione; poi aggregate figlie in ordine ID crescente, quindi idempotenza/mutation ledger e outbox. La serializzazione per iscrizione è accettabile a fronte di centinaia di studenti distinti; nessun lock globale del corso durante i progressi. L’iscrizione nuova blocca edition e usa vincolo univoco; subject è creato con upsert/gestione conflitto prima della transazione didattica.

| Caso | Letture/lock e scritture atomiche | Dopo commit |
|---|---|---|
| progress.save | Enrollment FOR UPDATE; lesson_progress e progress_session; valida batch; unione coverage, sequenza, resume, eventuale completamento, outbox | Restituisce revisione e next_sequence; nessuna chiamata provider |
| lesson.complete | Enrollment e lesson_progress; verifica regola manuale/prerequisiti; transizione unica e totali | Idempotent replay ricostruisce output autorizzato |
| quiz.start | Enrollment; slot active_attempts; limiti e attempt_no; crea snapshot e slot | Payload pubblico privo di grading |
| quiz.save | Enrollment, attempt; verifica open/deadline, mutation_id/hash e expected_revision; risposte e revision++ | Conferma salvata; nessun voto |
| quiz.submit/timeout | Enrollment, attempt; controlla stato/revision e scadenza; grading snapshot, terminale, rimozione slot, completamento/outbox | Pubblica risultato secondo feedback policy |
| Emissione attestato | Enrollment; idoneità; unique enrollment/version; inserisce snapshot e job PDF | Render fuori lock; aggiornamento CAS risultato |
| Ricalcolo | Enrollment; rilegge tentativi validi e progressi; aggiorna proiezioni/anomaly | Notifica amministrativa tramite outbox |

`SELECT FOR UPDATE` e query esatte devono essere implementate e collaudate su entrambi i motori. Non usare transazioni lunghe per upload, rete, PDF o interi CSV. Deadlock: massimo 2 retry dell’intera transazione solo per errori DB transitori riconosciuti e comando idempotente; jitter breve. Non ritentare automaticamente un invio email o l’intera richiesta già parzialmente eseguita fuori DB.

Non usare la row lock Joomla di check-out come lock DB per quiz/progressi. Check-out protegge editor umani, revision protegge aggiornamenti concorrenti, transazione protegge stati composti: servono tutti dove appropriato. Il contesto di sicurezza è rivalutato a ogni nuova richiesta e dopo attese anomale, senza una garanzia di revoca istantanea a metà di un commit già avviato.

## 12. Quiz: snapshot, timer e feedback

Ogni domanda ha revisioni immutabili con testo/opzioni e grading separati. Quiz pubblicato referenzia revisioni precise; start fissa domande, ordine opzioni, punti, soglia, durata, regola feedback e max_points nel tentativo. Eventuale sorteggio usa casualità server con selezione congelata, mai scelta dal client. Domande multiple: pieno punteggio solo se insieme selezionato coincide con insieme corretto; zero altrimenti, niente punti negativi. Default70%,3 tentativi,nessun timer; migliore esito valido per l’idoneità.

Lo slot `active_attempts` ha PK enrollment+quiz per evitare partial index non portabili. La presenza dello slot viene verificata assieme all’attempt; la manutenzione riconcilia slot incoerenti senza aprire un nuovo tentativo se quello originale è ancora aperto. Attempt_no è assegnato sotto lock enrollment; limitare tentativi sul conteggio dei tentativi validi secondo la policy, non sulla presenza del solo slot.

Save: il client mantiene revision confermata, mutation_id UUID e delta risposte. La stessa mutation ripetuta restituisce la stessa revisione; contenuto differente =>409. Una revisione obsoleta non sovrascrive: UI rilegge e propone ripresa, non fa merge silenzioso tra due schede. Una risposta vuota esplicita cancella la selezione; campo omesso lascia invariato. Quiz.submit invia soltanto attempt_id, expected_revision, idempotency_key; deve avere già ottenuto conferma del save. Un submit con nuove risposte viene rifiutato dallo schema.

Il server imposta deadline_at e confronta il proprio UTC. Al limite, chiude sulle ultime risposte salvate anche se il worker gira in ritardo; non accetta save oltre la scadenza. Nessuna proroga per refresh o disconnessione. Se perde accesso: nessun nuovo save/submit; un tentativo a tempo si chiude comunque alla scadenza con quanto già salvato; uno senza timer resta inaccessibile finché l’accesso non torna, salvo invalidazione amministrativa tracciata.

Il client riceve solo public_snapshot e risposte proprie; grading_snapshot non compare in HTML, JSON, log o cache. Feedback/soluzioni solo dopo superamento o esaurimento tentativi, salvo policy più restrittiva. Il risultato pubblico al proprietario espone punteggio/stato senza anticipare soluzioni. Rigrading è un job con jlms.assessment.correct, motivazione e audit; conserva esito precedente nell’audit, produce anomalie se influisce su attestati esistenti.

## 13. Servizi applicativi e responsabilità

| Servizio | Comandi/letture principali | Dipendenze e transazione |
|---|---|---|
| CatalogService | Corsi/categorie/percorso/docente/scheda | Query promozionali, language/access/filter; cache con ACL scope |
| LearningService | Dashboard, programma personale, lezione | AccessPolicy, enrollment/progress read model; no cache condivisa |
| EnrollmentService | Inizia, richiesta, approva, sospendi, CSV | SubjectRepository, edition lock, univocità |
| CourseAuthoringService | Salva, riordina, elimina bozza, workflow | Joomla forms/Table/check-out + regole versione |
| CourseVersionService | Duplica, nuova versione, preview/migrazione | Snapshot e mapping, job batch |
| ProgressService/CompletionService | Eventi, completamento, ricalcolo | Lock enrollment, intervalli, proiezioni/outbox |
| QuizService/QuestionBankService | Domande, start/save/submit/esito/regrade | Snapshot, timer, active slot, revision e mapper distinti |
| PlaybackService | Descriptor/renew/stream locale | Policy corrente, registry, segreti/config e storage |
| AttachmentService/StorageService | Upload, collega, elenco, download, cleanup | Quarantena, ACL parent, blob immutabili, Range |
| CertificateService | Emissione/lista/PDF/verifica/revoca | Snapshot, unique issuance, job rendering e hash pubblico |
| ReportingService | Metriche, CSV e avanzamento job | Scope corsi autorizzati, snapshot data e paginazione |
| JobService/OutboxService | Claim, batch, retry, stato/output | Lease/token CAS, handler allowlist |
| ProviderAdminService | Config/diagnostica/metadati | Permesso dedicato, segreti solo reference, SSRF e timeout |
| PrivacyService | Export/erase/anonimizza/retention | Plugin privacy Joomla, policy e inventario dati |

I servizi condividono porte AccessPolicy, Clock, TransactionManager, Repository, SecretProvider, Storage, EventOutbox, Translator, IdGenerator. Testare le regole con Clock/UUID controllabili, ma usare random_bytes nel runtime per codici e token. Nessun servizio restituisce una riga DB integrale; i DTO di lettura distinguono dati pubblici, propri e amministrativi.

## 14. Superficie HTTP e contratti

Il catalogo allegato mantiene 70 azioni: 25 frontend e45 backend. I percorsi sono task MVC proposti, non endpoint installati. La tabella è normativa per metodo, servizio, autorizzazione e idempotenza. Le view HTML/menu non vanno conteggiate come nuove API. Download e stream usano format=raw; JSON usa format=json e JsonResponse core.

Per POST JSON il controller legge un body limitato e verifica comunque il token Joomla nel parametro atteso da checkToken: il nome dinamico del token viene trasmesso nella query POST same-origin, valore1, oppure tramite form-urlencoded; non inventare un header X-CSRF generico senza integrazione verificata. Nessun token in log query. Content-Type JSON ammesso soltanto per task che lo prevedono; upload usa multipart e token form. GET non cambia iscrizioni/progressi/stati. CSRF non sostituisce ACL.

Risposta JSON: success, message, messages, data. In data restituire code e request_id per errori, result per successi dove previsto; HTTP reale coerente. Codici:200 lettura/update/replay,201 creato,202 job accettato,400 forma o metodo input invalido,401 sessione richiesta,403 diniego/CSRF,404 inesistente o nascosto,405 metodo task errato,409 conflitto revisione/stato/idempotenza,413 body/file troppo grande,415 tipo media non supportato,422 validazione semantica,429 limite con Retry-After,503 provider/storage temporaneamente indisponibile. 405/415 precisano il contratto senza modificare il catalogo funzionale precedente.

Errori stabili: ACCESS_DENIED, RESOURCE_NOT_FOUND, INVALID_INPUT, VERSION_IMMUTABLE, REVISION_CONFLICT, IDEMPOTENCY_CONFLICT, PROGRESS_SEQUENCE_CONFLICT, SOURCE_REVISION_MISMATCH, QUIZ_ALREADY_CLOSED, QUIZ_DEADLINE_REACHED, PREREQUISITE_REQUIRED, PROVIDER_UNAVAILABLE, FILE_NOT_READY, JOB_SCOPE_DENIED. Nessuna traccia stack o SQL al browser; dettaglio correlato nei log redatti.

Gli schemi JSON allegati fissano i comandi critici e il descriptor video. additionalProperties=false; ID decimali stringa, UUID e timestamp validati, numeri limitati. La validazione schema non verifica proprietà, FK, ACL, prerequisiti o risposte corrette: queste restano nel servizio. Per i 70 task il catalogo specifica i campi logici; gli schemi completi dei form editoriali sono i form XML core da implementare seguendo il modello dati, non generici oggetti record senza allowlist.

Paginazione: default20,max100; page>=1, massimo offset10000 nelle liste interattive, esportazioni via job. Ordinamento solo tra campi allowlist, niente nomi SQL dal client. search max200, date ISO e filtri validati. Range solo singolo intervallo nei binari: multipart range non supportato =>416 coerente/documentato.

## 15. Idempotenza, limiti e retry

Idempotency scope_hash = SHA-256 del dominio, user/subject corrente, task, risorsa e chiave; request_hash è hash del comando canonico esclusi CSRF/request_id. Chiave casuale UUID per intenzione, non per retry. Autorizzazione prima del lookup; il risultato persistito contiene ID e campi minimi, mai playback token, segreti o soluzioni. Replay ricostruisce la risposta con policy attuale. Conservazione dedupe standard24h; vincoli univoci/stati impediscono duplicati anche oltre questa finestra.

Per quiz.save il ledger è quiz_mutations, mantenuto per la vita del tentativo; per progressi la sequenza è per sessione, non la chiave generica. Idempotenza e modifica DB del comando nello stesso commit. Se il client non conosce il risultato può ritentare con la stessa chiave; non genera una nuova iscrizione/tentativo. Operazioni lente tornano202 con job_id e status_url solo autorizzato.

Limiti iniziali per utente/risorsa: progress6/min per sessione con burst3, quiz.save30/min, start/submit10/min, playback12/min; verify pubblico20/min per IP con ulteriore soglia globale. Sono valori configurabili da validare sotto carico; non memorizzare dati sensibili nel rate store. Se distribuito su più webnode usare store condiviso affidabile, non array locale PHP. Rispettare429 e backoff esponenziale jitter; non ritentare409 senza risoluzione.

## 16. Provider video inclusi nel pacchetto base

| Plugin | Sorgente canonica | Riproduzione e sicurezza | Configurazione/limiti |
|---|---|---|---|
| YouTube | video_id singolo validato, host input ammessi | IFrame API, HTTPS, origine sito; embed solo dopo consenso richiesto | No API key per base; no playlist/canali/live; unlisted condivisibile; niente garanzie anti-ads/DRM |
| Vimeo | video_id e privacy_hash quando richiesto | Player SDK, domain restrictions e referrer compatibile | Nessun upload remoto; restrizione dominio non equivale ad autorizzazione individuale |
| Cloudflare Stream | UID VOD validato | Player API, requireSignedURLs per video protetti; token900s, rinnovo120s prima | Account/playback domain; mode api-token oppure local-signing-key, riferimenti segreti separati |
| HTML5 locale | asset_id immutabile MP4 | Video element e delivery protetta, Range206/416 | Non accetta URL/percorso arbitrario; codec/container realmente collaudati, storage e banda del sito |

Le firme PHP e l’adapter TypeScript sono in contracts/. Metadata fetch è opzionale ed esplicito, con timeout connect3s/total8s, risposta<=1MiB, redirect al massimo2 verso allowlist ripetutamente verificata. Normalizzazione URL non effettua fetch arbitrario. Nessuna TLS verification disattivata; rifiuto loopback/private/link-local dopo risoluzione e durante connessione, protezione DNS rebinding tramite client/egress appropriato, non solo regex sull’host.

Cloudflare: controllare configurazione requireSignedURLs e non offrire UID pubblico come fallback se firma fallisce. Il rinnovo rivalida enrollment/accesso e può essere negato lasciando scadere il token già consegnato. Parametri del player sono filtrati per provider, non HTML libero. Metadata cache max1ora con error TTL30s e invalidazione manuale; metadata non decide mai ACL. Sorgente/durata cambiate creano nuova source_revision e, se didatticamente rilevanti, nuova versione del corso.

Provider disabilitato: le lezioni interessate mostrano errore controllato, diagnostica elenca dipendenze. Nessuna cancellazione dei riferimenti o completamento manuale automatico. Il contratto non è una sandbox: installare plugin solo attendibili. I quattro plugin hanno propri test/provider QA e documentazione inclusi, anche se versionati separatamente.

## 17. Materiali, caricamento e storage

Gli allegati hanno sempre course_version_id; lesson_id NULL significa materiale generale, altrimenti deve appartenere alla stessa versione (FK composita). Non accettare scope_type e owner_id senza risolvere il parent dal DB. Materiali generali accessibili solo dopo iscrizione salvo preview esplicita; materiali lezione richiedono anche prerequisiti. L’asset può essere riutilizzato senza duplicare blob, ma i download si autorizzano sul collegamento, mai sul solo asset_id.

Upload multipart autorizzato: limite20MiB di default e limiti PHP/webserver coerenti, nome casuale, hash SHA-256 streaming, controlli estensione/MIME/firma e struttura dove pertinente, quarantena fuori webroot, antivirus se configurato. DOCX/XLSX/PPTX/ODF sono container ZIP: validazione struttura bounded, nessuna estrazione/esecuzione di macro o archivi. ZIP generico scaricabile come attachment, mai aperto server-side. Rifiutare doppi nomi/traversal/NUL, HTML/SVG e file eseguibili; Content-Disposition usa nome sanificato ASCII+UTF-8 correttamente codificato, nosniff.

Asset ready solo dopo controlli obbligatori; se un controllo configurato obbligatorio è indisponibile, resta quarantena. Scanner assente è stato diagnostico esplicito, non “scansione riuscita”. Il Media Manager core gestisce solo immagini promozionali consentite. Evitare copia degli allegati protetti in /images o link diretti permanenti.

Storage locale predefinito: directory configurata fuori document root, accesso OS minimo, directory non indicizzabile/eseguibile. Preferire X-Accel-Redirect/X-Sendfile con location interna dopo autorizzazione; alternativa object storage con URL firmati brevi configurati e scadenza documentata. Il PHP fallback è abilitazione esplicita e limitato: chiudere il lock sessione dopo aver letto identità/token/policy, stream a blocchi senza caricare in memoria l’intero file. Non lasciare sessione chiusa prima delle verifiche sensibili.

Download: verifica risorsa, parent, stato, periodo e policy a ogni nuova richiesta/Range; Content-Length corretto,206 e Content-Range per range valido,416 con bytes */size altrimenti. Non fare proxy dei video esterni via video.stream. File e PDF sono conservati finché referenziati da versioni, attestati o job; cleanup marca pending_delete e ricontrolla riferimenti sotto lock prima della cancellazione fisica. Mai eliminare un blob ancora usato da una versione storica.

## 18. Attestati e verifica pubblica

Template con JSON strutturato e asset approvati, niente PHP/template arbitrario né fetch di font/immagini esterne durante il rendering. Libreria PDF scelta e bloccata alla versione verificata prima dell’implementazione; deve supportare Unicode, font incorporati e rendering senza rete. I layout devono essere collaudati sui nomi lunghi e sulle lingue previste.

Al completamento il servizio verifica abilitazione attestato e inserisce una sola emissione con una root certificates e il documento iniziale con seriale, identità/titolo/regole/template congelati e issued_at. La generazione PDF è job idempotente: scrive un nuovo blob temporaneo, ne verifica hash e stato e lo collega con CAS; il retry non crea un nuovo seriale. Un errore cambia render_failed e conserva il diritto a ritentare. La verifica pubblica non dipende dal contenuto attuale del corso.

Codice verifica: random_bytes(32), codifica base64url, DB conserva solo SHA-256 del codice. URL completo generato una volta nel job/contesto protetto e gestito come dato riservato di pubblicazione; per riprodurlo nel PDF il codice viene incluso in una busta cifrata del payload job, mai in chiaro o come hash impossibile da invertire. La cifratura autenticata usa la libreria nativa Sodium mantenuta, nonce casuale e key_id risolto dal SecretProvider fuori DB; ext-sodium diventa requisito del rendering con verifica pubblica. Non implementare primitive crittografiche proprie. La busta è rimossa dopo il PDF e non esportata nei risultati job. Una volta renderizzato il PDF, eliminare il materiale temporaneo segreto; il PDF rimane il riferimento. Non scrivere codice completo nei log. Verifica pubblica opzionale, nessun elenco studenti, output minimo concordato nella privacy policy e rate limit.

Revoca soltanto con jlms.certificates.revoke, motivazione e audit; il PDF può restare storicamente scaricabile con stato revocato chiaramente indicato nella UI e verifica. Un ricalcolo non modifica retroattivamente i dati congelati: apre un’anomalia da risolvere.

## 19. Scheduler, job, outbox e notifiche

Handler ammessi: migration_enrollments, import_enrollments, export_reports, render_certificate, regrade_quiz, reconcile_progress, expire_attempts, dispatch_notifications, privacy_export, privacy_erase, retention_cleanup, metadata_refresh. Ogni handler valida payload/schema; il browser non sceglie un nome di classe eseguibile. I task Scheduler core e CLI possono essere invocati da processi distinti; impedire sovrapposizioni dello stesso batch via lease DB.

Claim portabile: transazione breve seleziona candidato queued/retry_wait con available_at<=now ordinato per id FOR UPDATE; aggiorna running, lease_token casuale, lease_until e attempts. Nessun requisito SKIP LOCKED per il minimo DB. Lavorazione fuori transazione, batch100righe o budget20s di default; aggiornamento cursore/fine solo se lease_token corrisponde. Lease60s con heartbeat20s, handler che non può terminare entro lease deve rinnovare o fermarsi. Ogni scrittura di effetto del batch verifica lease/chiave univoca: il vecchio worker non sovrascrive un nuovo worker.

Retry massimo5 con ritardo30s,2m,10m,30m,2h e jitter; errori permanenti/ACL mancanti =>failed senza retry cieco. Per jobs owner/scope vengono rivalidati all’avvio e tra batch e all’output, senza concedere automaticamente accesso se il proprietario perde permessi. Eccezioni di sistema esplicite: scadenza tentativi e retention operano col contesto tecnico limitato al loro handler, non come utente universale. Export cancellato non rende scaricabile un file parziale.

Outbox: evento nello stesso commit del cambiamento didattico, event_key univoca su tipo+aggregato+transizione. Consumatori idempotenti creano un job/ricevuta univoca. Mail Templates core con parametri allowlist e lingua destinatario. Se il trasporto non supporta idempotenza e il timeout segue un’accettazione incerta, stato delivery_unknown: non garantire email esattamente una volta né ritentare ciecamente. La deduplicazione DB non rende atomico un servizio esterno.

## 20. Reporting, esportazioni e importazioni

Report interattivi aggregati e paginati per corsi/categorie autorizzati. Nome/email studente solo con permesso report e ambito pertinente. Query indicizzate, niente N+1 per le lezioni di ogni studente; indici sulle sequenze di filtro in schema. Per periodi grandi creare job con as_of e filtri congelati, così UI e file dichiarano il periodo dei dati.

CSV esportati con encoding UTF-8 documentato, header stabili, escaping RFC4180 e neutralizzazione formule su valori testuali che iniziano con =,+,-,@, tab o CR. Download con ACL del job e scadenza, file protetti. CSV import: dry run, mapping colonne allowlist, identificazione utenti core esistenti, errori per riga e riepilogo; non crea password, utenti o gruppi implicitamente. Applicazione a batch riprendibile e idempotente con chiave per riga; nessuna query dinamica da intestazioni.

Le statistiche non leggono continuamente eventi grezzi. lesson_progress e enrollments sono proiezioni transazionali; i report costosi producono snapshot. Un comando di riconciliazione confronta conteggi e segnala divergenze senza cancellare prove originali.

## 21. Frontend, CSS, override e accessibilità

View pubbliche: categories, category, courses, course, classroom, quiz, dashboard, pathway, teacher, certificates, verification. Ogni root ha un solo ID `jlms-<view>`; le aree contrattuali usano ID stabili all’interno della view e classi per elementi ripetuti. Non derivare ID dal titolo o traduzione. Per card ripetute usare classi/data-entity-id, eventuali ID con suffisso numerico univoco. Non duplicare jlms-course-materials in lista e card.

| View | ID/regioni pubbliche stabili |
|---|---|
| Categorie/catalogo | #jlms-categories, #jlms-category, #jlms-courses, #jlms-catalog-filters, #jlms-course-grid, #jlms-pagination |
| Scheda corso | #jlms-course, #jlms-course-header, #jlms-course-description, #jlms-course-outline, #jlms-course-teachers, #jlms-course-materials, #jlms-course-enroll |
| Aula | #jlms-classroom, #jlms-lesson-navigation, #jlms-lesson-content, #jlms-player, #jlms-lesson-resources, #jlms-classroom-course-materials, #jlms-learning-progress |
| Quiz | #jlms-quiz, #jlms-quiz-status, #jlms-quiz-questions, #jlms-quiz-actions, #jlms-quiz-result |
| Personale/altre | #jlms-dashboard, #jlms-pathway, #jlms-teacher, #jlms-certificates, #jlms-certificate-verification |

Manifest CSS versione1 documenta classi .jlms-course-card, .jlms-material, .jlms-material__title, __meta, __action e variabili --jlms-color-primary, --jlms-color-text, --jlms-color-surface, --jlms-space-unit, --jlms-radius, --jlms-content-width. Evitare selettori globali e !important; JS usa data-jlms-* e non classi estetiche. Una modifica incompatibile di ID/layout richiede changelog/major del contratto frontend e guida migrazione override.

Semantica HTML, gerarchia titoli, label/errori associati, tastiera, focus, live region per salvataggi, rispetto reduced-motion e contrasto adeguato. Obiettivo WCAG2.2AA sulle parti controllate dal prodotto; player terzi verificati per quanto possibile e limiti documentati. Testare mobile320px e desktop, zoom200/400%, testi lunghi, lettore schermo e assenza JS per navigazione. Quiz/video richiedono JS con messaggio utile; non mostrare completamento falso in errore JS.

## 22. Lingue, notifiche e consenso

Pacchetto con it-IT ed en-GB; file .ini/.sys.ini per componente/libreria/plugin, chiavi stabili, pluralizzazione e date core. Default del prodotto traducibile via language overrides. Lingua del corso e lingua interfaccia distinte; non forzare trasferimento di iscrizione all’associazione tradotta. Mail nella lingua dell’utente quando disponibile, fallback documentato.

L’integrazione del consenso usa un bridge configurabile al sistema del sito: prima del consenso richiesto non caricare iframe, SDK, poster remoto o preconnect del provider. Revoca distrugge player/listener e sospende telemetria della sessione; ciò non cancella dati storici. HTML5 locale non implica automaticamente consenso marketing. Il plugin privacy Joomla gestisce i dati personali LMS, non sostituisce il sistema di consenso e la policy legale del sito.

## 23. Privacy, conservazione e cancellazioni

Inventario: utenti/subject, iscrizioni, progressi, tentativi/risposte, richieste, attestati/identità congelata, log/receipt, job/export. Esportazione privacy include soltanto dati dell’interessato con descrizioni e relazioni; niente answer key, segreti provider, dati di altri studenti. Le risposte proprie possono essere esportate senza le soluzioni corrette non pubblicabili.

Default tecnici: idempotenza24h; sessioni progressi24h dopo scadenza; export/report7giorni; quarantena abbandonata7giorni; log tecnici30giorni; risultati job/outbox redatti90giorni. Iscrizioni, tentativi e attestati non vengono cancellati automaticamente per una revoca di accesso. Retention didattica configurabile dal gestore, disattivata finché non approvata una policy del sito; non inventare una durata legale universale. Nessun IP completo nei progressi; rate limiting con minimizzazione e TTL.

Richiesta di cancellazione tramite privacy core: verifica obblighi del titolare, erasure ove consentita, altrimenti anonimizzazione/minimizzazione e motivazione esposta al workflow privacy. identity_snapshot degli attestati contiene dati personali e deve essere considerato esplicitamente: se la policy ne richiede la rimozione non basta user_id=NULL; invalidare la verifica pubblica e applicare conservazione/cancellazione del PDF secondo decisione tracciata. Backup con finestre di retention, ripristino e riapplicazione dei tombstone di erasure: non reintrodurre dati cancellati senza controllo.

## 24. Sicurezza applicativa e sviluppo sicuro

Threat model minimo: accesso orizzontale ad altrui iscrizioni/quiz/file/job; incremento permessi mediante ID manipolati; token/answer key in cache e log; CSRF/XSS/SQLi; SSRF dei provider; upload/traversal/ZIP bomb; replay/race; dipendenze malevole; furto segreti e abuso export. Ogni minaccia ha controlli, proprietario e prova negativa associata.

Query bind per valori e allowlist per identificatori SQL; filtri input non sostituiscono escape output. HTML editor passa filtri Joomla per ruolo; titoli/nomi escaped, URL con schemi ammessi, opzioni JS via serializzazione JSON sicura e WAM, mai concatenazione nello script. Content Security Policy compatibile con i quattro player, host limitati per script/frame/connect e niente apertura generica *. Rimuovere debug e stack in produzione.

SecretProvider legge env o configurazione fuori webroot; riferimenti nei parametri XML non sono segreti. Un campo password Joomla non è crittografia automatica del DB. Se serve cifratura DB usare libreria mantenuta, chiave esterna e piano rotazione; no crypto artigianale. URL firmati e privacy_hash Vimeo non nei log; redazione a livello applicazione/reverse proxy. Trasporto HTTPS, cookie/sessione configurati sicuri tramite Joomla e infrastruttura, nessun token in localStorage.

Verifica ASVS5.0.0 come catalogo controlli, senza dichiarare certificazione. Review obbligatoria per policy/token/SQL/upload/quiz. Pipeline: lint PHP/JS, PSR12, static analysis, scan segreti, dipendenze Composer/NPM bloccate e inventario/SBOM, unit/integration/E2E/negative/load, test installer/upgrade. Findings critical/high pertinenti e confermati bloccano rilascio finché risolti o con eccezione motivata e approvata dal responsabile; non chiamare “sicuro” il solo codice che passa un linter.

## 25. Efficienza, cache e dimensionamento

Target:300 studenti attivi per60minuti,500 per10minuti; mix60%video,20%quiz,15%navigazione,5%completamenti. Dataset10.000 utenti,500 corsi,100.000 iscrizioni con distribuzione realistica e alcuni corsi popolari. A 20s,300 player continuamente attivi producono circa15batch/s;500circa25batch/s, cui aggiungere quiz e navigazione. È un calcolo di carico, non un benchmark superato.

Soglie p95: pagina1,5s, save progressi/risposte800ms, submit2s; errori inattesi<1%, zero perdita/duplicazione confermata o dati tra utenti. Misurare dal client di test con warming/cache, latenza rete, DB e hardware dichiarati. Media provider e CDN sono misurati separatamente; non trasferire video Cloudflare/YouTube/Vimeo attraverso PHP Joomla.

Nessun heartbeat provider dal server a ogni progress.save; risolvere metadata fuori dal percorso critico. Riutilizzare lookup per richiesta, query aggregate per outline e progressi, bound pagination, indici prima di aumentare hardware. Per il carico su un unico corso evitare lock sul corso per ogni studente. Chiudere la sessione per stream lunghi; valutare session handler condiviso e richieste concorrenti della stessa sessione nei test reali.

Cache promozionale: TTL massimo5minuti, chiave lingua+menu+filtri+livelli autorizzati e stato categoria/corso; invalidazione al salvataggio/pubblicazione/ACL. Nessun output personale/token/materiale protetto in page cache/CDN condivisa. Pagine con aula o personalizzazione dichiarano no-store. Job/worker in numero limitato configurabile (inizio2) per non saturare DB/PHP. Transazioni con tempi e lock_wait misurati, slow query redatte e analisi EXPLAIN su entrambi i DB.

## 26. Osservabilità e operatività

request_id casuale correlato in response/log; job_id/aggregate_id dove necessario. Metriche: latenza per task,4xx/5xx,409,429, tempi/lock DB, batch rifiutati/duplicati, quiz aperti oltre deadline, età coda/outbox, lease scadute, errori provider, PDF falliti, storage libero e anomalie progressi. Mai usare nome/email/token come label metrica.

Health amministrativo: versioni/dipendenze, compatibilità schema, directory protetta, scanner, segreti presenti senza mostrarli, provider configurati, Scheduler ultimo run, coda e disco. Diagnostica remota soltanto su richiesta amministrativa con timeout, non a ogni visita pubblica. Alert su coda>5 minuti per timeout quiz/attestati, errori5xx>1% in5minuti e storage<15%, soglie da tarare in esercizio.

Backup DB+blob+config segreti con inventario/hash coerente e test di restore; chiavi in archivio separato protetto. Obiettivi iniziali da validare sull’infrastruttura: RPO24h e RTO4h, migliorabili dal gestore. Ripristino in ambiente isolato, poi riconciliare job/outbox, impedire reinvio email massivo e rivedere lease. Aggiornamento con manutenzione, backup e migrazione verificata; rollback software solo con schema compatibile, altrimenti ripristino coordinato.

## 27. Piano di implementazione e dipendenze

| Fase | Output concreto | Criterio d’uscita |
|---|---|---|
| T1 Fondazioni | Repository, CI, package, lib contratti, componente MVC, install/schema | Install/uninstall conservativo e DI su Joomla target |
| T2 Catalogo core | Corso/categorie/docenti/percorsi, ACL/lingue/fields/tags/workflow/override | Catalogo con URL diretti e core integration verificati |
| T3 Versioni e file | Draft/publish/copy, asset/quarantena/download, prerequisiti | Storico immutabile, download non aggirabile |
| T4 Iscrizioni/progressi | Policy, edizioni default, enrollment, aula, transazioni/intervalli | Retry/race/device e revoca superati |
| T5 Video base | Quattro plugin+SDK/asset, consenso, token/renew/Range | Tutti i40scenari provider eseguiti e risultati registrati |
| T6 Quiz | Question bank/revisioni, snapshot, save/submit/timer/feedback | Race submit/timeout, no answer leak, nessuna perdita save confermato |
| T7 Attestati/job/report | Outbox, Scheduler/CLI, PDF, verifica, import/export | Retry/lease/idempotenza e PDF/CSV autorizzati |
| T8 Privacy e rilascio | Privacy/actionlog/retention, upgrade, security/load/manuali | Matrici funzionale/tecnica e prove di carico documentate |

Le integrazioni core non si rimandano tutte a T8: ACL/lingue/storia/workflow condizionano il modello da T1/T2. Privacy e audit accompagnano ogni fase; T8 completa i test e la documentazione. Ogni fase include aggiornamento di manuali e tracciabilità, non solo codice. Nessuna stima di giorni fittizia senza team e infrastruttura reali.

## 28. Piano di verifica esecutiva

Il catalogo funzionale precedente contiene118scenari; quello provider40. L’allegato tecnico aggiunge casi su vincoli/lock/schema/lease e lifecycle. Questi scenari di prodotto sono pianificati, non automaticamente superati. Le fixture dei contratti documentali vengono invece eseguite localmente con un validatore limitato al vocabolario usato. Ogni risultato deve includere build, CMS/PHP/DB, fixture, comando, osservato e allegato evidenza. Un record “Da eseguire” non è un test passato.

Verifiche richieste: installazione fresca e recovery DDL; FK e vincoli unici; controllo ACL diretto e revoca con sessione esistente; due device; replay/gap/source revision; due start e submit concorrenti; timeout/save nello stesso istante; migrazione immutabile; sostituzione file storico; Range/session lock; job scaduto con vecchio worker; privacy/export; cache con lingue/livelli diversi; consumo provider reale e consenso; performance dataset/mix dichiarati.

Gli allegati sono stati controllati per coerenza dei riferimenti, nomi task, struttura JSON e contenuto del pacchetto. I controlli locali effettivamente eseguiti sono registrati in verification/esito_verifiche.json. L’ambiente locale non dispone di PHP, MySQL/MariaDB o Joomla in esecuzione: nessun lint PHP o esecuzione DDL/integrazione/carico viene dichiarato superato. Il successivo sviluppo deve aggiungere queste verifiche alla CI.

## 29. Parametri e decisioni ancora da congelare prima della release

Non rimangono scelte funzionali necessarie per iniziare a implementare la baseline. Alcuni valori appartengono al prodotto o al deployment e vanno registrati prima della release: nome/namespace definitivi; dominio e titolare update server; licenza e compatibilità licenze dipendenze (proposta GPL-2.0-or-later con verifica); libreria PDF mantenuta; patch esatte CMS/PHP/DB; host/cache/sessione/shared storage reali; provider configurati/credenziali e domini video; policy privacy/retention e pubblicità del verificatore attestati; sistema di consenso del sito; capienze e calendario edizioni se usate.

Sono parametri con default o attività verificabili, non autorizzazioni commerciali richieste all’utente a ogni passaggio. Non introdurre abbonamenti o pagamento nel componente per risolverli. Gli eventuali ampliamenti (REST, SCORM/xAPI, lezioni live, sorveglianza esami, upload video remoto) richiedono change request; non sono capacità implicite di questo rilascio.

## 30. Fonti tecniche e allegati

Fonti primarie consultate il30ottobre2026: [requisiti Joomla](https://manual.joomla.org/docs/get-started/technical-requirements/), [MVCFactory](https://manual.joomla.org/docs/building-extensions/components/mvc/mvc-factory/), [package](https://manual.joomla.org/docs/building-extensions/install-update/installation/package/), [manifest](https://manual.joomla.org/docs/building-extensions/install-update/installation/manifest/), [query sicure](https://manual.joomla.org/docs/security/secure-db-queries/), [CSRF](https://manual.joomla.org/docs/security/csrf-protection/). Sono fonti dei pattern core; le scelte di versione didattica, DTO e schema sono decisioni di questo progetto.

Le fonti provider e la specifica completa dei loro parametri restano nel documento Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md incluso. Evidenze_Joomla_6.json registra file/tag/hash delle verifiche documentali precedenti. Nessuna fonte esterna prova che il componente progettato abbia già superato i test.

Allegati: contracts/Binding_servizi_70_task.md; sql/install.mysql.utf8mb4.sql; sql/dizionario_tabelle.csv; sql/migrazioni.md; contracts/VideoProviderInterface.php; contracts/player-adapter.d.ts; contracts/registrazione-plugin.md; schemas/commands.schema.json e fixtures; catalogo70endpoint; verification/Catalogo_verifiche_tecniche.csv; esito_verifiche.json; README e SHA256SUMS.


## 31. Precisazioni del modello e operazioni amministrative

### 31.1 Documenti sostitutivi, emissione e audit

`certificates` rappresenta il diritto/emissione unico per enrollment/version. `certificate_documents` conserva le rappresentazioni immutabili numerate: originale document_no=1, sostitutivo 2 ecc., nuovo seriale/hash verifica, identità/template congelati e replaces_document_id. `certificates.current_document_id` punta alla rappresentazione corrente appartenente alla stessa emissione (FK composita). Nessuna nuova emissione didattica al solo cambio di nome; certificate.replace crea nuovo documento e audit sotto lock dell’emissione, collega lo storico e marca il precedente superseded dopo render riuscito. Aggiungere superseded agli stati documento oltre pending_render/issued/revoked/render_failed. La verifica pubblica cerca il documento tramite hash e segnala sostituito/revocato senza pubblicare dati aggiuntivi; il download ordinario restituisce il corrente, quello storico soltanto con azione amministrativa autorizzata o regola esplicita del sito.

Gli audit semantici (annullamento/rigrading/reset/revoca/sostituzione) sono eventi Action Logs core con actor, oggetto, ragione, before/after minimi e correlation id; nessuna tabella audit parallela. I dati necessari a riprodurre il grading originario restano negli snapshot e nel payload protetto del job di correzione; conservare punteggi originari e regola nuova nel registro di rivalutazione descritto sotto, non solo una stringa di log.

### 31.2 Calendario, contenuti e regole JSON

Accesso temporale = intersezione disponibilità corso, edizione, iscrizione starts_at/ends_at e lezione publish_up/publish_down, più eventuale release_after_days dalla started_at dell’iscrizione. NULL assenza limite. Inizio incluso, fine esclusa. Prima della pubblicazione verificare raggiungibilità di ogni obbligatorio anche rispetto a prerequisiti e drip; per iscrizioni amministrative con scadenza più breve mostrare errore o richiedere correzione esplicita, senza rendere il corso completabile con attività mancanti.

lesson.kind: text, video, audio, document, quiz. Audio e documento referenziano media_asset_id, con consegna protetta e completion_rule=manual; testo usa body filtrato. Video usa provider/source_json e completion_rule=video_coverage. Quiz usa quiz.pass. Lezioni opzionali possono avere regola propria senza entrare nel totale. Compiti con valutazione manuale sono fase successiva, non tabelle mancanti del primo rilascio.

course_versions.rules_json schema logico chiuso: mode learning/reference; sequence_mode free/sequential; video_threshold_basis_points default9000; certificate_enabled false; certificate_template_id nullable; completion_policy all_required. presentation_json conserva obiettivi/destinatari/prerequisiti dichiarativi/difficoltà/durata stimata/immagine e docenti pubblicati. Non confondere durata stimata con durata sorgente validata. lessons.params_json può contenere transcript_asset_id, caption_asset_ids con lingua e poster_asset_id: oggetti validati e collegati a storage protetto; non URL liberi. Questi riferimenti JSON vanno censiti nel ReferenceScanner prima di rimuovere asset, insieme a snapshot template e versioni; una FK non protegge riferimenti dentro JSON.

I documenti JSON hanno versione `_schema=1` e limite: course rules64KiB, presentation256KiB, fields snapshot256KiB per record, source8KiB, params64KiB, grading/public question256KiB per domanda, job payload1MiB salvo blob import esterno, coverage256KiB. Se richiesto più spazio non troncare:422 o job/blob dedicato. Il JSON canonico ordina chiavi oggetto, preserva ordine array, UTF-8, niente float per valori di grading. Non usare serialize/unserialize PHP per dati persistiti ricevuti dall’esterno.

### 31.3 Reset, annullamenti, rigrading e import preview

Enrollment.reset preserva l’originale e crea una nuova edizione/nuova iscrizione anche sulla stessa versione. Registra origine, motivo e mapping in enrollment_migrations (policy kind=reset); non azzera in place tentativi/attestati. Se è soltanto riattivazione, usare suspend/reactivate e conservare la stessa iscrizione. L’endpoint enrollment.suspend deve distinguere il target status nel comando form validato; la descrizione logica precedente reason non autorizza un toggle ambiguo: aggiungere esplicitamente `target_status=active|suspended` al binding del task.

quiz.cancelAttempt imposta invalidated e rimuove lo slot se era attivo, senza cancellare snapshot; `return_attempt` viene registrato come conteggio_consumato=false. Aggiungere in attempts counted_for_limit boolean e invalidation_reason/invalidated_by/at per una decisione riproducibile. QuizService conta soltanto tentativi con counted_for_limit=true; un tentativo invalidato senza restituzione resta conteggiato.

Rigrading preserva il risultato precedente nella tabella assessment_revisions aggiuntiva (definita nel DDL) con job, actor, ragione, snapshot regola correttiva, score/passed precedente e nuovo. Il rendering pubblico del tentativo usa l’ultima revisione efficace; lo snapshot originale non viene mutato. La valutazione correttiva non cambia retroattivamente il testo visualizzato. Preview del rigrading non applica modifiche.

ImportPreview crea un job/record privato con risultato dry run, hash CSV e mapping, owner e scadenza24h. Commit rilegge lo stesso blob immutabile e verifica hash/owner/scope/limiti; non accetta CSV sostituito dal browser. Default CSV10MiB/10.000 righe. Import header canonico e delimitatore esplicito come specifica funzionale; nessuna riga sovrascrive automaticamente iscrizioni già presenti. starts_at/ends_at sono applicati all’iscrizione e verificati nell’intersezione temporale.

### 31.4 Persistenza delle revisioni di valutazione

Lo schema fisico include 37 tabelle; assessment_revisions registra lo storico delle correzioni amministrative. Il conteggio è verificato sul DDL e sul dizionario, senza implicare un componente già implementato. La tabella contiene solo correzioni amministrative e riferimenti: un submit normale conserva il primo esito in attempts. Le modifiche correttive aggiornano proiezioni/completamento e registrano questa riga nella stessa transazione, con previous e resulting score; la chiave attempt+revision_no è unica.


## 32. Dettagli vincolanti dei binding e dei dati storici

Il catalogo70task è l’inventario funzionale iniziale. Questo documento precisa i seguenti binding senza aggiungere task: progress.save include sequence; enrollment.suspend include target_status e rifiuta un toggle implicito; attachment.save include attachment_id quando aggiorna un collegamento esistente; altrimenti crea un nuovo collegamento e richiede idempotency_key per evitare doppi inserimenti al retry. Nei form XML distinguere creation/update e gli expected_revision obbligatori. Le precisazioni sono riportate nel catalogo copiato in questo pacchetto; non vanno perse leggendo solo il documento precedente.

Il certificato root è l’unica emissione didattica; seriali/hash/identità e stato visualizzato appartengono ai certificate_documents. certificate_id dei task pubblici risolve la root e il documento corrente, mentre verification_code risolve esattamente il documento verificato. La sostituzione deve lasciare valida la rappresentazione precedente finché il nuovo PDF è pronto: failed non sposta current_document_id. Il rendering iniziale usa doc 1. Collegare un document a un’altra root via current_document_id è impedito dalla FK; replaces_document_id deve appartenere alla stessa root anche quando validato nel servizio.

Rigrading legge attempts originale e ultima assessment_revision efficace; scrive una nuova revisione, mai il grading originale del tentativo. Le proiezioni di superamento usano la revisione efficace e la regola migliore tra tentativi non invalidati. Salvataggi del tentativo chiuso non si riaprono per effetto del rigrading. subject_key e user_id ridondante sulle iscrizioni devono corrispondere: l’owner viene risolto da subjects, user_id è solo una proiezione indicizzabile, riconciliata e anonimizzata insieme. Non considerare mai una sua discordanza come concessione di accesso.

Nomi dei constraint SQL includono #__ per evitare collisioni tra più siti Joomla nello stesso database. Preflight limita la lunghezza del prefisso in modo che nomi tabella/indice/constraint risultino <=64caratteri dopo sostituzione. Il dizionario include anche subjects. Le migrazioni richiedono test sul SQL mode strict effettivo; non disabilitare foreign_key_checks per mascherare incoerenze.

## Aggiornamento amministrazione1.4

CoursesModel/ListModel, form filter_courses e layout Search Tools core gestiscono stato/sessione, filtri, ordinamento allowlist e paginazione. Query e totali applicano lo stesso scope ACL corrente; la chiave cache include filtri e scope. Filtri autore/categoria derivano dai record modificabili; selezione del Programma validata dal controller su POST/CSRF e singolo ID autorizzato. Il titolo usa course.edit nativo. Il task course.programme è mappato al save del FormController con postSaveHook verso curriculum solo dopo successo; validazione/errore/storico restano nel modello originale. Viste/layout compatti riusano form e classi backend, senza CSS globale o scritture di stato dai template. Il riordino del programma e il collaudo completo browser/accessibilità rimangono separati.
