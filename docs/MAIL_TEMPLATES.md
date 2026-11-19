## MAIL-08 — invio di prova idempotente su refresh (0.60.9-dev, 19/11/2026)

L'invio di prova dal pannello **Comunicazioni corso** riutilizza lo stesso identificativo UUID v4 per la medesima anteprima anche in seguito a refresh/redirect. L'identificativo viene assegnato **dal server** alla conferma dell'anteprima e salvato nella sessione Joomla per utente e corso. Un nuovo clic su **Anteprima** genera un nuovo identificativo; l'invio di prova della stessa anteprima, anche tramite POST ripetuti o refresh, conserva l'event key deterministica e sfrutta l'unicità su `#__jlms_mail_queue.event_key`. Non è previsto il rinvio automatico di un messaggio SMTP con esito incerto o in stato `sending`.

Una nuova anteprima esplicita costituisce una nuova operazione che può generare un diverso invio di prova: non è una funzione di marketing o di invio massivo. Nessun indirizzo degli iscritti viene letto dal campo test, che è ristretto all'attore autorizzato. Eseguire i collaudi reali Joomla/SMTP descritti nel testbook; la CI non dimostra consegna.

---

## Correzione P0 — composizione singola, variabili valide e anteprima (15 novembre 2026)

Dopo la PR #440 la pagina **Comunica agli iscritti** mostrava **due composizioni sovrapposte**: oggetto/corpo libero e modello Joomla modificabile. Questo generava confusione e poteva causare errori 422 nel passaggio a anteprima quando i campi del modello `{MESSAGE_SUBJECT}`, `{MESSAGE_BODY_TEXT}`, `{MESSAGE_BODY_HTML}` si riferivano implicitamente al vecchio editor separato.

La correzione **mantiene un solo editor**:
- il modello Joomla `com_jlms.course.message` viene caricato nella lingua dell'amministratore, con fallback nativo;
- solo una coppia **Oggetto dell'email** / **Messaggio (HTML Joomla)** è modificabile;
- le tre variabili interne di composizione del modello originario vengono sostituite, *solo nella copia locale*, da brevi testi introduttivi modificabili. Il modello globale rimane intatto; `{COURSE_TITLE}`, `{USER_FULLNAME}`, `{SITE_NAME}` e le relative versioni `_HTML` restano disponibili;
- la versione testuale alternativa viene generata automaticamente dall'HTML filtrato, non richiesta come terzo editor;
- conferma, hash, snapshot, test e invio SMTP ricevono la **stessa** copia del modello. Se si reintroducono i vecchi slot `MESSAGE_*` in una composizione diretta, un errore 422 specifico chiarisce come correggere il testo senza produrre un invio ricorsivo;
- i messaggi legacy precedenti conservano il loro percorso di elaborazione senza ritentativi automatici.

**Collaudo su sito Joomla:** 1) accedere a un corso e aprire `Comunica agli iscritti`; verificare che siano presenti soltanto oggetto, un editor per il messaggio e destinatari, con i valori del modello precaricati; 2) inserire `{USER_FULLNAME_HTML}`, `{COURSE_TITLE_HTML}` nel corpo e `{COURSE_TITLE}` nell'oggetto, quindi generare l'anteprima senza 422, verificando le sostituzioni; 3) controllare che il modello Joomla globale non sia stato modificato; 4) prova singola al solo amministratore autorizzato; 5) accodamento e invio tramite Scheduler con SMTP di test, controllando il contenuto effettivo, gli stati e l'assenza di duplicati; 6) HTML ostile, variabile sconosciuta e vecchi slot ricorsivi: messaggio controllato e nessuna accodatura; 7) schermata IT/EN, desktop/mobile Atum, ACL e CSRF. **L'accettazione manuale Joomla/SMTP resta distinta dal risultato dei test CI.**

## Composizione da modello Joomla — fase 2 (editor, anteprima e snapshot del job)

La vista `Comunica agli iscritti` precarica `com_jlms.course.message` da Joomla, espone **oggetto**, **testo semplice** e **HTML** modificabili solo per questa comunicazione (editor configurato in Joomla), oltre a oggetto/testo manuale e destinatari. L'amministratore vede l'anteprima renderizzata con il medesimo sistema di sostituzione dei placeholder di Joomla (esempio di destinatario e iframe `sandbox` per HTML). Il modello globale `#__mail_templates` non viene mai modificato.

In fase di conferma il contenuto sanificato del modello entra nell'hash dell'impatto e nel payload del job `course_communication`. Ciascun record in coda conserva lo stesso snapshot, per non cambiare oggetto/testo se l'amministratore modifica successivamente il modello globale. Il dispatcher usa il mailer di Joomla per i nuovi snapshot, mantiene ACL/consenso, retry, deduplica e non ritenta gli invii `sending` ambigui. I record storici privi di snapshot seguono il precedente percorso di Mail Templates per compatibilità.

**Limite esplicito:** lo snapshot contiene solo oggetto, testo e HTML del modello Joomla; preferenze aggiuntive del template globale (ad esempio allegati o layout specifici) non vengono copiate nel job. L'invio dei nuovi snapshot usa il trasporto del mailer configurato in Joomla, non crea un sistema SMTP alternativo. Una sola lingua per comunicazione manuale, con placeholder personalizzati per ogni destinatario. Modifiche al modello devono restare limitate alla singola spedizione. I messaggi già falliti non vengono ripristinati automaticamente.

**Collaudo sito Joomla obbligatorio:** modello personalizzato italiano → editor HTML precompilato, modifica locale, anteprima coerente, conferma, singolo job e SMTP di prova; cambiare il modello globale dopo la coda non altera il contenuto in consegna; invio di prova alla sola identità autorizzata; errore controllato per variabile sconosciuta, HTML/script non sicuro e soggetto non valido; corsi/utenti non autorizzati respinti; IT/EN/Atum/mobile; nessun duplicato, nessuna mutazione dei template globali, nessun retry silenzioso di `sending`.

## Composizione da modello Joomla — fase 1 (base sicura, 1.0.0)

La nuova classe `CourseMessageTemplate` carica la definizione effettiva di `com_jlms.course.message` (incluso il fallback Joomla al modello predefinito), restituisce una copia dei tre campi `subject`, `body`, `htmlbody` e ne valida contenuto e placeholder canonici senza alterare la configurazione globale. Il campo HTML viene filtrato con la funzione nativa Joomla `InputFilter`. Un modello non disponibile o una variabile sconosciuta provoca un errore controllato. Test PHPUnit/smoke sul template installato, test strutturali e matrice requisiti accompagnano la modifica.

**Questa PR è la base tecnica, non cambia ancora la schermata né il dispatcher.** La successiva PR dovrà integrare l'editor HTML nativo, anteprima personalizzata e snapshot per job/recipient al momento dell'invio. Fino a quel merge, non è corretto considerare collaudato il flusso di composizione da modello; continua a valere il percorso esistente. Protezioni imprescindibili: permessi corsi, CSRF, no sovrascrittura del modello globale, no duplicati di invio, variabili canoniche, escaping e verifica in Joomla/SMTP reale.

## MAIL-H / issue #416 — leggibilità della guida variabili

Nel sommario espandibile di ciascun modello email, il titolo tradotto e l'ID tecnico sono separati da un trattino lungo visibile: `Nome modello — com_jlms.nome_modello`. Gli ID dei template, i tag e i dati restano invariati. **Collaudo manuale Joomla IT/EN:** verificare la separazione dei testi in Atum su desktop/mobile, l'espansione delle schede e la leggibilità degli ID. Nessun invio SMTP coinvolto; copertura funzionale invariata.

## MAIL-C / STAB P0 — accodamento comunicazioni agli iscritti (issue #419)

L'azione **Accoda invio** della vista `coursemessage` falliva con HTTP 500 perché la verifica di idempotenza del job passava cast inline a `DatabaseQuery::bind()` (`(string)$courseId`, `(int)$actor->id`), che richiede variabili per riferimento. L'accodamento usa ora valori locali tipizzati, mantenendo invariati `mutation_id`, verifica dell'impatto/hash, lock sul corso, transazione e audit: non viene introdotto un canale alternativo di invio. La pagina separa visivamente anteprima e azioni, con pulsante di accodamento **primario** invece di rosso; la conferma esplicita resta obbligatoria. Nessun invio SMTP sincrono viene eseguito dal click: il task Joomla elabora i job e le email in coda.

**Comportamenti da collaudare sul sito Joomla (IT/EN, desktop/mobile):** aprire Corsi → Comunica agli iscritti; selezionare iscritti attivi, inserire oggetto e messaggio, ottenere anteprima; cliccare **Accoda invio** con conferma e verificare messaggio di successo, creazione di un solo job e assenza di 500. Eseguire due volte la stessa richiesta identificata dal medesimo `mutation_id` per verificare la deduplica; ricaricare la pagina dopo conferma e verificare nessun secondo job senza nuova anteprima. Provare un hash non corrispondente, un destinatario non più attivo, un ruolo non autorizzato e una richiesta senza conferma: nessun accodamento. Verificare l'invio di prova separato, quindi l'esecuzione del task configurato con SMTP di test, destinatari consentiti e nessuna consegna doppia. Controllare spaziatura, etichetta checkbox e posizione dei pulsanti. **Accettazione Joomla/SMTP pendente; CI non è prova di invio effettivo.**

## MAIL-H / UX — titoli amministrativi non duplicati (issue #411)

Nelle viste **Audit coda email** e **Variabili modelli email** il titolo pagina è gestito esclusivamente dalla toolbar nativa Joomla. Il titolo H1 duplicato nel corpo è stato eliminato, mantenendo testo di aiuto, filtri, sezioni espandibili, didascalie delle tabelle e controlli ACL. La stessa regola è applicata alla vista **Gestione dipendenze categorie**, senza alterare il titolo H2 della categoria selezionata né le azioni di ricollocazione. I controller e gli endpoint restano invariati. Verificare su Atum in italiano e inglese, con vista vuota e dati presenti, che compaia un unico titolo di pagina e che gli elementi interattivi siano intatti. I collaudi manuali Joomla restano da eseguire.

## MAIL-H — accesso alla comunicazione agli iscritti (issue #383)

La vista amministrativa `coursemessage`, raggiunta da **Corsi → Iscritti al corso → Comunica agli iscritti**, viene registrata esplicitamente nella whitelist del `DisplayController` e associata alla voce principale **Corsi** nella navigazione nativa Atum. Prima della correzione, la GET era bloccata con `404 JERROR_LAYOUT_PAGE_NOT_FOUND` nonostante esistessero model, view e template. L'intervento modifica **solo routing e contesto del menu**: la selezione dei destinatari, l'autorizzazione course-scoped, il CSRF dei POST, la preview e l'accodamento rimangono governati dai servizi e controller preesistenti. La whitelist per le viste non autorizzate rimane attiva.

**Collaudo richiesto:** accesso con gestore autorizzato a un corso che abbia iscritti attivi, controllo del rendering della vista e del focus Corsi; accesso negato a gestore di altro corso o a ruolo non autorizzato; anteprima non inviante, prova verso destinatario di test, conferma e accodamento una sola volta; verifica coda e Scheduler separatamente, senza invii accidentali a destinatari reali. Accettazione Joomla/SMTP ancora da eseguire.

## MAIL-H — escaping per contesto dei placeholder Joomla (candidata `0.57.7-dev`, PR #410)

In fase di invio, Joomla `MailTemplate::addUnsafeTags()` richiede di escapare i valori non fidati nel corpo HTML. JD Learn ora marca **solo** i token canonici di tipo `text`, `date`, `url` (es. `{USER_FULLNAME}`, `{COURSE_TITLE}`, `{DASHBOARD_URL}`). I valori già escapati `html-escaped`, `html-escaped-url` e i frammenti `html-fragment` sanitizzati a monte (es. `{USER_FULLNAME_HTML}`, `{MESSAGE_BODY_HTML}`, `{COURSE_LESSONS_HTML}`) non vengono escapati una seconda volta. Il subject e il plain text mantengono la sostituzione naturale senza HTML entities aggiuntive. I template personalizzati nel gestore Joomla non vengono riscritti.

**Indicazione agli amministratori:** in HTML usare preferibilmente `{USER_FULLNAME_HTML}` e `{COURSE_TITLE_HTML}` quando disponibili, oppure i token grezzi che Joomla escapa automaticamente. Per i blocchi HTML forniti da JD Learn impiegare solo i token tipizzati documentati; non si autorizza markup libero da input dell'utente. I collegamenti rimangono protetti dalle autorizzazioni lato Joomla e il rendering non modifica ACL o privacy. Il controllo automatico copre la sostituzione reale Joomla di input malevolo, frammenti sanitizzati e HTML già escapato; il collaudo SMTP su un sito reale resta necessario.

## MAIL-H — anteprima annuncio nella toolbar corso (issue #363, candidata 0.57.5-dev)

La vista **Editor corso** presenta l'accesso **Anteprima email di annuncio** esclusivamente nella toolbar Joomla nativa, tramite `LinkButton`, quando il corso esiste, è pubblicato e non è in checkout da un altro amministratore. Rimossa la duplicazione nel corpo del form. Il collegamento apre la stessa vista amministrativa protetta `courseannouncement` senza salvare né inviare email. Il sistema mantiene Joomla Mail Templates come editor dei modelli, il flusso di consenso e la conferma esplicita di accodamento esistenti. Regressioni automatiche su toolbar e visibilità; accettazione manuale Joomla pendente.

## MAIL-G — richiesta di iscrizione ai responsabili (candidata 0.57.4-dev, PR #401)

Il template nativo `com_jlms.enrollment.manager_request` è destinato soltanto a **utenti Joomla autorizzati alla gestione delle iscrizioni del corso**: amministratori con ACL specifico e docenti effettivamente assegnati con lo stesso permesso. La ricerca prende candidati dai gruppi Joomla con grant pertinenti e dalle assegnazioni docente, ma ogni account passa `EnrollmentRosterService::course()` prima dell'accodamento e viene nuovamente validato al dispatch. I responsabili non devono ricevere email per corsi che non amministrano. Un account docente privo di permesso sulla gestione iscrizioni non è idoneo.

**Trigger:** nuova richiesta `pending` in modalità approvazione, ancora in attesa al momento dello Scheduler, entro due giorni dalla creazione. La conferma/rifiuto dello studente segue i template già esistenti `com_jlms.enrollment.approved`/`.rejected`; non ci sono duplicazioni di questi eventi. Chiave idempotente SHA-256 `enrollment.manager.request:<request_id>:<manager_user_id>`; nuovo manager non eredita email relative a vecchie richieste oltre la finestra. Globale `mail_manager_request_enabled` è OFF; per corso `mail_manager_request` accetta `inherit/enabled/disabled`. Un'eventuale perdita di permesso, blocco account, cambio email, chiusura richiesta o disabilitazione della notifica prima del dispatch causa `skipped` e la redazione dei dati identificativi in coda.

**Privacy:** email con titolo corso, nome del destinatario autorizzato e URL al registro iscrizioni amministrativo (`COURSE_MANAGEMENT_URL`), soggetto ai normali login e ACL Joomla; **nessun nome, indirizzo, motivazione o dato personale del richiedente**. Tag disponibili: `USER_FULLNAME`, `USER_FULLNAME_HTML`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `COURSE_MANAGEMENT_URL`, `SITE_NAME`, `SITE_NAME_HTML`.

**Limiti:** la prima implementazione non aggiunge un pannello di destinatari selezionabili né un workflow parallelo all'approvazione; nessun invio garantito quando la richiesta viene risolta prima della prossima esecuzione dello Scheduler. Resta da registrare il collaudo Joomla/SMTP manuale.

## MAIL-G — aggiornamento didattico significativo (candidata 0.57.3-dev, PR #399)

`com_jlms.course.updated` viene accodato solo in seguito alla **migrazione esplicita e confermata** di un'iscrizione verso una versione didattica pubblicata, se la versione di destinazione ha differenze sostanziali (lezioni, materiali, quiz, moduli, regole). Pubblicare una nuova versione non cambia automaticamente il programma degli iscritti alla precedente e **non** genera notifiche; nemmeno modifiche di bozza, salvataggi e modifiche soltanto alla presentazione del corso generano l'avviso.

**Preferenze:** `mail_course_update_enabled` globale OFF per impostazione predefinita; override `mail_course_update` per corso `inherit/enabled/disabled`. Lo Scheduler consulta le migrazioni degli ultimi due giorni, deduplica per ID migrazione e accoda solo quelle di iscritti attivi. Prima del dispatch controlla nuovamente email e blocco utente, stato dell'iscrizione/edizione, versione, finestre di frequenza, tassonomia e `content_access` Joomla e preferenze. Se non più idoneo, il messaggio viene scartato e i dati del destinatario eliminati dalla coda. La mail riporta unicamente il titolo del corso e il link all'area personale autenticata, **non** elenca materiali o lezioni riservate. Variabili canoniche: `USER_FULLNAME`, `USER_FULLNAME_HTML`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `DASHBOARD_URL`, `SITE_NAME`, `SITE_NAME_HTML`.

**Limitazioni:** non è un sistema di newsletter; la migrazione non automatica è un prerequisito. Restano il collaudo SMTP/Joomla e le notifiche ai responsabili delle richieste. I dettagli di QA sono nel testbook.

## MAIL-G — disponibilità delle lezioni pianificate (candidata 0.57.2-dev, PR #397)

Il modello nativo `com_jlms.lesson.available` informa facoltativamente gli iscritti attivi **alla propria versione didattica** quando una lezione con `publish_up` futuro o un rilascio relativo (`release_after_days`) diventa accessibile. L'iscrizione deve essere precedente al rilascio e la versione pubblicata prima della disponibilità; non si generano avvisi per lezioni già disponibili alla prima pubblicazione, per salvataggi ordinari, modifiche di bozza o per iscritti a versioni diverse. Lo Scheduler considera gli eventi degli ultimi 2 giorni per evitare invii retroattivi indefiniti e usa la coda esistente con chiave stabile per iscrizione+lezione. Il link conduce soltanto all'area personale autenticata; non contiene materiali privati.

**Configurazione:** `mail_lesson_release_enabled` globale è **OFF** per default; `mail_lesson_release` per corso permette `inherit/enabled/disabled`. Al momento del dispatch il servizio ricontrolla account, email, iscrizione attiva, edizione, versione, calendario, preferenze, tassonomia Joomla e `content_access`, poi marca `skipped` se non più autorizzato. I token canonici sono `USER_FULLNAME`, `USER_FULLNAME_HTML`, `LESSON_TITLE`, `LESSON_TITLE_HTML`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `DASHBOARD_URL`, `SITE_NAME`, `SITE_NAME_HTML`. L'HTML usa varianti escaped. Il payload include ID tecnici per verifiche, non utilizzabili come tag esposti. Il default del modello è modificabile dal gestore nativo Joomla; il normale upgrade non sovrascrive le personalizzazioni.

**Limiti:** questo incremento non implementa ancora notifiche per modifiche didattiche significative o verso responsabili; il collaudo manuale Joomla/SMTP è pendente. Il collaudo previsto è in [QA_TESTBOOK.md](QA_TESTBOOK.md).

La PR [#375](https://github.com/jeckodevelopment/jd_learn/pull/375) ha spostato nella toolbar il collegamento ai modelli filtrati del componente, mantenendolo visibile solo con `core.manage` e `core.edit` su `com_mails`.

> **Decisione successiva (14 novembre 2026, PR #349):** prima del rilascio si adotta **un solo vocabolario di variabili**, senza alias. Sostituiti `{NAME}`→`{USER_FULLNAME}`, `{COURSE}`→`{COURSE_TITLE}`, `{SITENAME}`→`{SITE_NAME}` e i relativi tag HTML. Il catalogo storico sottostante descrive le versioni precedenti e non è il riferimento canonico. I template già personalizzati in installazioni di prova vanno riallineati manualmente o ripristinati; non è prevista compatibilità retroattiva. Le notifiche e il collaudo di invio rimangono da verificare.\n\n# Template email di JD Learn

La PR [#288](https://github.com/jeckodevelopment/jd_learn/pull/288), inclusa in 0.56.0-dev, registra i modelli nel sistema nativo **Joomla → Sistema → Modelli email** e usa il mailer e la configurazione SMTP globali del sito. Non esistono credenziali SMTP separate in JD Learn.

## Modelli forniti

| ID | Evento | Variabili disponibili |
|---|---|---|
| `com_jlms.enrollment.requested` | Richiesta di iscrizione ricevuta | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.confirmed` | Iscrizione confermata | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{COURSE_URL}` |
| `com_jlms.course.completed` | Corso completato | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.suspended` | Accesso al corso sospeso | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.reactivated` | Accesso al corso riattivato | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.certificate.available` | Attestato emesso e disponibile nell’area personale | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{CERTIFICATE_URL}` |

I modelli iniziali includono testo semplice e HTML. Il gestore Joomla consente di modificarli e creare le relative traduzioni. L'installer registra solo gli ID mancanti: non sostituisce modifiche editoriali o traduzioni esistenti quando il componente viene aggiornato.

## Perimetro e stato

La PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291), integrata in 0.56.1-dev, collega la conferma dell'iscrizione automatica e il completamento ai template mediante una coda persistente. Il task Joomla `JD Learn: send queued emails` usa il mailer globale del sito, ritenta errori transitori fino a cinque tentativi con attesa progressiva e registra l'esito. La chiave univoca impedisce di accodare due volte la stessa transizione. Se il processo si interrompe dopo la presa in carico o dopo l'accettazione SMTP ma prima della conferma DB, il messaggio resta `sending` per evitare un reinvio cieco; richiede riconciliazione operativa. Un'eccezione durante l'invio SMTP può avere esito ambiguo: SMTP non offre una garanzia exactly-once. Le preferenze globali/per corso coprono cinque eventi includendo la disponibilità dell’attestato (CERT-D); restano da collegare altri eventi approvati e retention. La PR [#332](https://github.com/jeckodevelopment/jd_learn/pull/332), integrata in 0.56.17-dev, aggiunge audit consultabile e riconciliazione controllata degli elementi `sending` stale. L’operatore può confermare `sent` con nota obbligatoria oppure rimettere in coda con retry limitati; destinatari mascherati, registrazione di autore/motivo e pulizia dei dati personali dopo invio. La conferma SMTP rimane una decisione operativa quando l’esito è ambiguo. La notifica CERT-D entra nell’outbox solo per una nuova emissione e usa la lingua del profilo Joomla. Il task Scheduler consegna tramite MailTemplate; la posta non viene inviata durante il completamento e un errore di accodamento non annulla l’attestato. Credentials/Open Badges, newsletter e tracking restano fuori dal perimetro.

## Dati diagnostici ed errori di trasporto (SEC-P1 / 0.56.18-dev, PR #338 integrata)

Le eccezioni del mailer possono includere indirizzi destinatario, risposte SMTP o dettagli del provider. Il dispatcher ora memorizza nella coda solo un messaggio generico e la vista di audit non seleziona il testo diagnostico storico. La migrazione 0.56.18 sostituisce gli errori preesistenti con lo stesso messaggio generico. I dettagli tecnici completi non vengono mostrati da JD Learn; l'operatore deve consultare i log del mailer secondo le regole di accesso e retention del sito Joomla.

Il controllo manuale deve provocare un errore SMTP con una stringa riconoscibile contenente un indirizzo di prova, poi verificare che tale stringa non compaia nella coda o nell'output dell'audit e che i record legacy siano redatti dopo l'upgrade. Il collaudo reale resta pendente.

Per il collaudo manuale vedere [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09--core-18-template-email-joomla).


## Preferenze per le notifiche attualmente collegate

La PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295), integrata in 0.56.2-dev, aggiunge due impostazioni globali in configurazione JD Learn (conferma iscrizione automatica e completamento) e override per corso con tre opzioni: usa impostazione globale, abilita, disabilita. L'override del corso prevale sul valore globale; se impostato su eredita, vale il valore del sito. La decisione è applicata quando si verifica la transizione e prima di inserire l'email in coda. CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698); il collaudo manuale resta pendente. Pacchetto [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`.

Le preferenze globali e per corso coprono quattro eventi dopo #297. La PR #299 collega la conferma per l’iscrizione amministrativa singola, riusando le preferenze di conferma. Richieste/approvazioni, annullamenti, modifiche lezioni e scadenze restano non collegati.

## Sospensione, riattivazione e iscrizione amministrativa (0.56.4-dev, #297/#299)

La PR #297 registra due nuovi template nel gestore Mail Templates Joomla e accoda le email quando un amministratore sospende o riattiva un'iscrizione. La scrittura usa la stessa transazione della modifica stato; un errore dell'outbox non deve lasciare stato e notifica disallineati. Ogni evento ha idempotency key legata alla riga di audit della transizione. Le impostazioni globali e gli override per corso seguono le preferenze introdotte in #295.

La PR #299 collega anche l’iscrizione amministrativa singola al template `com_jlms.enrollment.confirmed`. La notifica entra nell’outbox nella stessa transazione dell’iscrizione e usa la chiave evento deduplicata. Un preassegnamento senza accesso corrente non invia una conferma che possa far credere allo studente di poter entrare nel corso. L’evento eredita preferenze globali/per corso dell’iscrizione. Richieste/approvazioni e annullamenti restano da collegare.

## Requisiti approvati per la 1.0.0 (12 novembre 2026)

La registrazione dei template nella PR #288 e la coda dei due eventi core nella #291 sono **solo una parte implementata**, non l'intero sistema di notifiche. Sono approvati i requisiti MAIL-01–MAIL-10 e la sequenza IMP-09-MAIL-A/B/C/D del [piano PR](STABLE_1.0.0_PR_PLAN.md#approvazione-funzionale-12-novembre-2026--mail-templates-e-notifiche-100). Copertura stimata dopo PR #297: MAIL-01 100%; MAIL-02 aggiornata al 45%; MAIL-03/04 0%; MAIL-05 45%; MAIL-06/07/08 0%; MAIL-09 70%; MAIL-10 25%. Media area aggiornata a 28,5%. Il collegamento della conferma per assegnazione amministrativa è candidato 0.56.4-dev e non è conteggiato prima del merge. Le stime misurano l'implementazione, non l'accettazione Joomla.

- **Joomla come unica fonte per i Mail Templates:** registrazione di ID `com_jlms.*` e tag nei componenti, modifica di oggetto/corpo HTML e testo, traduzioni e invio tramite mailer Joomla; nessun editor template o trasporto SMTP proprietario.
- **Modello B controllato:** regole di notifica attive/disattive globali, override per corso che eredita le impostazioni globali, nessun evento custom arbitrario. Il contenuto variabile delle comunicazioni operative manuali può essere composto dentro JD Learn e inserito in un Mail Template Joomla dedicato.
- **Eventi iscrizione:** richiesta, conferma, iscrizione manuale, sospensione, riattivazione e annullamento; **corso:** completamento; **facoltativi:** nuova lezione, modifica didattica significativa, avviso singolo per scadenza. Generare notifiche solo su cambiamento rilevante e per destinatari interessati. L'email non deve determinare il successo dell'azione didattica.
- **Comunicazione operativa manuale:** dalla scheda del corso, amministratore o docente con permesso Joomla specifico e associazione al corso, a tutti gli iscritti attivi o a destinatari selezionati all'interno del corso. Oggetto e testo redazionale, anteprima, conteggio, conferma esplicita, test autorizzato; nessuna lista arbitraria, newsletter o invii promozionali.
- **Consegna affidabile:** outbox persistente, elaborazione per piccoli lotti tramite Scheduler Joomla su hosting ordinario Apache/LiteSpeed, retry limitato con backoff, identificativo idempotente per evento e destinatario, prevenzione doppi click; log degli esiti ed errori minimizzato con retention. I permessi e i filtri sono verificati server-side.
- **Esclusioni:** invii massivi non contestuali a un corso, newsletter, segmentazioni marketing, tracking aperture/click e solleciti ricorrenti di inattività; restano fuori anche attestati e Credentials.

### Criteri di collaudo previsti (non ancora eseguiti)

1. Installazione e upgrade preservano modelli Joomla, contenuti personalizzati e varianti IT/EN; invio usa configurazione globale Joomla.
2. Iscrizione, sospensione, riattivazione, annullamento e completamento generano al massimo una notifica per effettiva transizione e destinatario, anche con richieste ripetute.
3. Opzioni globali e override del corso disattivano gli eventi attesi; una modifica editoriale ordinaria non genera una notifica.
4. Docente autorizzato può comunicare solo agli iscritti attivi dei propri corsi; URL manipolati, destinatari esterni e studenti di corsi diversi sono respinti.
5. Anteprima, conteggio e test non inviano al roster reale; il comando invia soltanto dopo conferma esplicita, con audit e gestione del doppio click.
6. Con SMTP temporaneamente indisponibile le azioni sul corso rimangono persistite; la coda registra l'errore, ritenta nei limiti e non duplica consegne già riuscite.
7. I job gestiscono centinaia di iscritti senza invio HTTP monolitico; verificare permessi e privacy dei log, pulizia/retention e comportamento multi-lingua.



## Notifica attestato (CERT-D)

Alla prima emissione del documento, JD Learn aggiunge all’outbox una sola notifica con chiave stabile legata a certificato e documento. Un replay del completamento trova l’emissione esistente e non accoda di nuovo. Il template nativo `com_jlms.certificate.available` rimane modificabile e offre link all’area personale; la consegna usa il task Joomla già esistente. L’impostazione globale è attiva per default e il corso può ereditarla, abilitarla o disabilitarla. Un errore SMTP è gestito dal worker con retry; l’emissione non dipende dall’esito di consegna. Il link porta alla dashboard autenticata e non espone il PDF.


## MAIL-11 — Annuncio disponibilità nuovo corso (approvato 13 novembre 2026)

**Stato implementativo: completato per MAIL-11.1–11.5 nella PR #327 (main 0.56.16-dev); accettazione manuale Joomla/SMTP non eseguita.** La notifica serve a presentare un corso appena pubblicato agli utenti che ne hanno fatto richiesta; non è un invio marketing generalista né una newsletter. Il piano e i criteri MAIL-11.1–11.5 sono in [STABLE_1.0.0_PR_PLAN.md](STABLE_1.0.0_PR_PLAN.md#estensione-approvata-13-novembre-2026--mail-11-nuovo-corso-disponibile).

### Template nativo Joomla

Proposto ID `com_jlms.course.published`. Il default HTML presenta in quest'ordine: immagine anteprima del corso (URL HTTPS pubblico con testo alternativo), titolo, introduzione, elenco ordinato delle lezioni pubbliche, pulsante/link verso la scheda pubblica del corso, spiegazione del motivo dell'invio e collegamento di revoca. L'amministratore può togliere o riordinare i blocchi modificando il modello in **Sistema → Modelli email** di Joomla; non introdurre editor dei modelli separato. Il plain text deve rimanere leggibile e contenere link e programma. Variabili previste, da rendere compatibili con i placeholder già usati dall'estensione:

| Placeholder | Contenuto/fallback |
|---|---|
| `{NAME}` | Nome destinatario, con fallback neutro |
| `{COURSE_TITLE}` | Titolo del corso, escapato |
| `{COURSE_IMAGE}` | Immagine HTML pubblica opzionale, URL assoluto HTTPS, alt e fallback senza immagine |
| `{COURSE_INTRO}` | Introduzione sanitizzata, con fallback descrizione sintetica |
| `{COURSE_LESSONS}` | Elenco HTML ordinato dei **soli titoli e descrizioni pubblici** delle lezioni, senza materiale protetto; fallback appropriato |
| `{COURSE_URL}` | URL pubblico completo della scheda del corso |
| `{COURSE_DURATION}` | Durata prevista se disponibile; altrimenti blocco vuoto |
| `{SITE_NAME}` | Nome configurato del sito |
| `{UNSUBSCRIBE_URL}` | Link sicuro di revoca, senza credenziali o dati identificativi nell'URL |

### Preferenze privacy e invio

- Una sola preferenza utente per `nuovi corsi`, distinta dalle notifiche operative di iscrizione, tracciata al livello utente. Default **non selezionato**.
- Offrire l'adesione in registrazione utente Joomla, iscrizione al corso e profilo personale, senza forzare il consenso né duplicare lo stato; revoca disponibile in qualunque momento.
- Richiedere double opt-in prima dell'idoneità all'invio e conservare evidenza minimizzata di opt-in/opt-out, data, origine, versione informativa e conferma; definire retention coerente con la policy del sito.
- Solo amministratori con permesso adeguato possono scegliere esplicitamente di notificare, vedere anteprima e platea eleggibile e confermare l'invio **dopo** la prima pubblicazione effettiva del corso; non inviare automaticamente alla modifica o ripubblicazione.
- Invio batch tramite outbox/Scheduler Joomla esistenti, deduplica per corso+destinatario+annuncio e controllo nuovamente dell'opt-in confermato e della visibilità del corso all'atto del recapito; non esporre gli indirizzi agli altri destinatari.
- Nessuna profilazione automatica, mailing list esterna, tracciamento apertura/click o invio a utenti non opt-in; mai includere titoli di lezioni riservate o collegamenti a contenuti privati.

**Collaudo sul sito Joomla:** confronto email HTML/testo IT/EN, rendering con immagini bloccate, corsi senza foto/intro/lezioni pubbliche, template admin preservato su upgrade, opt-in/pendente/revocato, conferma e revoca, nuovo corso vs aggiornamento, corso privato, ACL e link sicuro, SMTP indisponibile, retry, coda con centinaia di utenti, deduplicazione, variazione preferenze prima dell'invio. Non segnare come superato fino a prova eseguita.


## Comunicazione operativa per corso — MAIL-C

La PR [#313](https://github.com/jeckodevelopment/jd_learn/pull/313) integrata nella 0.56.9-dev aggiunge il template Joomla com_jlms.course.message, componibile dal roster per gli iscritti attivi selezionati. L'invio di prova è riservato all'operatore corrente; l'invio reale richiede preview e conferma, crea un job idempotente e usa il task Scheduler/outbox. Preferenze: mail_communication_enabled globale e mail_communication per corso. I destinatari e le autorizzazioni vengono rivalidati all'elaborazione. Variabili supportate: NAME, NAME_HTML, COURSE, COURSE_HTML, MESSAGE_SUBJECT, MESSAGE_BODY_TEXT, MESSAGE_BODY_HTML, SITENAME, SITENAME_HTML. MAIL-11 (notifica facoltativa nuovo corso con opt-in) è separata da MAIL-C ed è implementata in main 0.56.16-dev; il collaudo manuale resta pendente.


### MAIL-11.1 — template annuncio nuovo corso

La PR [#315](https://github.com/jeckodevelopment/jd_learn/pull/315), integrata in 0.56.10-dev, registra `com_jlms.course.published` in Joomla → Sistema → Modelli email. Il default HTML include immagine opzionale, titolo, introduzione, elenco delle sole lezioni pubbliche, CTA e revoca; il plain text riporta URL e programma. Il servizio di invio dovrà costruire placeholder HTML con escaping e validare gli URL HTTPS. L’installer non sovrascrive personalizzazioni. Il template è collegato alle campagne approvate dall'amministratore a partire dalla PR #327.

### MAIL-11.2/11.3 — richiesta e conferma dell’adesione (PR #317, 0.56.11-dev)

La PR #317 integrata aggiunge una preferenza facoltativa nell’Area personale, non preselezionata e separata dalle notifiche operative. La richiesta resta `pending` finché l’utente apre il collegamento di conferma ricevuto all’indirizzo Joomla; il token casuale è conservato solo come SHA-256, è monouso e scade dopo 24 ore. Alla conferma sono registrati stato, timestamp, origine e versione dell’informativa. Lo stato `confirmed` è l’unico autorizzabile a ricevere annunci; l’utente può revocare dal proprio account. Le PR #319 e #323 collegano rispettivamente iscrizione al corso e registrazione Joomla; le PR #321, #325 e #327 completano revoca via link, anteprima e gestione delle campagne. Il plugin Privacy esporta e rimuove i dati di consenso; il token non è esportato.

La candidata registra inoltre `com_jlms.course.consent` in Joomla Mail Templates, senza sovrascrivere personalizzazioni. La campagna richiede conferma esplicita e usa outbox/Scheduler, con rivalidazione del consenso e della visibilità prima dell'invio. Prove Joomla e SMTP ancora pendenti: seguire [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09-mail-d--mail-112113-consenso-e-double-opt-in).


### Stato riconciliato MAIL-11 (main 0.56.16-dev)

| Criterio | Evidenza implementativa | Accettazione |
|---|---|---|
| MAIL-11.1 | PR #315: modello `com_jlms.course.published` nativo Joomla | Joomla/SMTP pendente |
| MAIL-11.2–11.3 | PR #317, #319, #321, #323: opt-in area personale, iscrizione e registrazione; double opt-in e revoca | Joomla/SMTP pendente |
| MAIL-11.4 | PR #325, #327: anteprima del corso pubblico, scelta e conferma esplicita dell'amministratore | Joomla/SMTP pendente |
| MAIL-11.5 | PR #327: campagna unica, coda idempotente, rivalidazione destinatario/consenso/visibilità al dispatch, retry | Joomla/SMTP pendente |

Baseline documentata: [piano 1.0.0](STABLE_1.0.0_PR_PLAN.md), [stato implementativo](IMPLEMENTATION_STATUS.md) e [QA Testbook](QA_TESTBOOK.md). Stima globale **78,00%**, area email **58%**: questi numeri non aumentano per questa correzione documentale. La PR #329 sull'audit e riconciliazione dei messaggi in stato `sending` è separata e non viene anticipata come integrata.

**Configurazione invio:** `MailQueue::dispatch()` e `CourseAnnouncementConsent::request()` istanziano `Joomla\CMS\Mail\MailTemplate` e ne chiamano `send()`. Utilizzano il trasporto configurato globalmente in Joomla (PHP Mail, Sendmail o SMTP); JD Learn non gestisce credenziali SMTP proprie. Le richieste di double opt-in sono inviate nel flusso di richiesta; le campagne e le notifiche operative sono elaborate dal task Scheduler. La prova con SMTP reale e la verifica dei limiti di retry restano collaudi manuali.


## Dati diagnostici ed errori di trasporto (SEC-P1 / PR #338 integrata, 0.56.18-dev)

La PR #338 è integrata: la coda salva un messaggio generico, la vista di audit non seleziona `last_error` e la migrazione redige i valori storici. CI standard 4/4 nel [run 43522457570](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570). Il collaudo Joomla/SMTP resta pendente; review sistematica e gate SEC-P1–P5 restano aperti.


## MAIL-04 — promemoria operativo scadenza corso

Il template `com_jlms.course.expiring` usa il mailer globale e i Mail Templates nativi Joomla. È inviato una sola volta per iscrizione e scadenza, soltanto se la funzione è abilitata globalmente e l’override del corso lo consente. La soglia è configurabile tra 1 e 30 giorni (default 3); la funzione è disabilitata globalmente per impostazione predefinita. Il task di invio ri-verifica che l’iscrizione sia ancora attiva, incompleta e non scaduta prima della consegna. Variabili: `{NAME}`, `{COURSE}`, `{COURSE_ENDS_AT}`, `{COURSE_URL}`, `{SITENAME}`.


## Accesso rapido ai modelli dalla dashboard JD Learn

Nel backend aprire **JD Learn → Dashboard → Comunicazioni → Template email**. Il collegamento apre la vista nativa `com_mails`, preimpostando il filtro estensione a `com_jlms`; non è un editor JD Learn separato e l'amministratore può cambiare il filtro dalla pagina Joomla. È visibile solo agli utenti con permessi di gestione e modifica del componente `com_mails`. La **Coda email** resta una funzione distinta che mostra tentativi e stati di invio, senza cambiare contenuto ai template.

La compatibilità del filtro nell'ambiente Joomla target e la traduzione di titolo/descrizione (issue #312) vanno verificate sul sito di prova: il collegamento non risolve da solo le chiavi lingua non tradotte.


## Decisione approvata il 14 novembre 2026 — completamento template e variabili

La specifica vincolante per la **1.0.0** è [MAIL_TEMPLATES_1.0.0_APPROVED.md](MAIL_TEMPLATES_1.0.0_APPROVED.md). Include un registro centralizzato dei placeholder con alias legacy e documentazione amministrativa, eventi di annullamento ed esiti approvazione/rifiuto, notifiche facoltative di nuove lezioni/cambi didattici, notifiche a docenti e amministratori, revisione editoriale e correzione issue [#312](https://github.com/jeckodevelopment/jd_learn/issues/312). I blocchi sono IMP-09-MAIL-E/F/G/H nel piano stabile. **Questa sezione è una decisione approvata, non prova di implementazione:** l'inventario runtime sopra resta valido fino al merge delle PR applicative e i collaudi Joomla/SMTP restano pendenti.


\n### MAIL-E — uniformazione senza alias\n\nLa PR #349 migra le dichiarazioni dei template e i generatori dati degli eventi oggi collegati a `{USER_FULLNAME}`, `{COURSE_TITLE}`, `{SITE_NAME}` e varianti HTML `{USER_FULLNAME_HTML}`, `{COURSE_TITLE_HTML}`, `{SITE_NAME_HTML}`. Rimuove lo strato di alias inizialmente proposto. Le altre variabili approvate nella [specifica 1.0.0](MAIL_TEMPLATES_1.0.0_APPROVED.md) saranno disponibili solo dopo effettiva implementazione; non dichiararle funzionanti prima delle PR successive.\n

## MAIL-E — catalogo tipizzato delle variabili effettivamente disponibili

Il registro read-only `MailTemplateVariableCatalog::forTemplate($templateId, $language)` legge l'elenco dei tag direttamente da `MailTemplateInstaller::tagsFor()` e restituisce per ogni segnaposto **tipo** e **descrizione IT/EN**. I test PHP assicurano che ogni segnaposto registrato in ogni template abbia una descrizione in entrambe le lingue e che l'elenco dei tag non diverga da quello nativo Joomla. Questo è un primo incremento infrastrutturale: **non aggiunge ancora variabili nei payload e non espone automaticamente una guida nella schermata amministrativa Joomla**.

### Riferimento per gli amministratori (template attualmente registrati)

| Tipo di email | Variabili già registrate |
|---|---|
| Richiesta iscrizione | `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME` |
| Conferma iscrizione | `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME`, `COURSE_URL` |
| Sospensione / riattivazione | `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME` |
| Completamento | `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME` |
| Attestato disponibile | `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME`, `CERTIFICATE_URL` |
| Scadenza | `USER_FULLNAME`, `COURSE_TITLE`, `COURSE_ENDS_AT`, `COURSE_URL`, `SITE_NAME` |
| Messaggio operativo | `USER_FULLNAME`, `USER_FULLNAME_HTML`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `MESSAGE_SUBJECT`, `MESSAGE_BODY_TEXT`, `MESSAGE_BODY_HTML`, `SITE_NAME`, `SITE_NAME_HTML` |
| Annuncio nuovo corso | `USER_FULLNAME`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `COURSE_IMAGE_HTML`, `COURSE_INTRO_TEXT`, `COURSE_INTRO_HTML`, `COURSE_LESSONS_TEXT`, `COURSE_LESSONS_HTML`, `COURSE_URL`, `SITE_NAME`, `SITE_NAME_HTML`, `UNSUBSCRIBE_URL`, `UNSUBSCRIBE_URL_HTML`, `UNSUBSCRIBE_URL_TEXT` |
| Conferma consenso | `USER_FULLNAME`, `CONFIRM_URL`, `SITE_NAME` |

**Attenzione:** i tag `*_HTML` rappresentano valori escaped o frammenti HTML predisposti dal componente: non vanno usati come HTML arbitrario. Un tag non presente nell'elenco del modello non deve essere considerato disponibile. `COURSE_ENDS_AT` è una data, mentre `COURSE_URL` e i link di consenso sono URL; la disponibilità del dato dipende dallo specifico evento. La specifica delle nuove variabili non è prova che siano già disponibili. Il confronto con il backend e il collaudo SMTP rimangono da eseguire.


## MAIL-E — controllo segnaposto prima dell'invio (incremento PR #351)

Il dispatcher della coda consulta il modello Joomla effettivamente selezionato per lingua (con fallback `*`) e, **prima di SMTP**, confronta i segnaposto nel suo oggetto, testo e HTML con i tag dichiarati in `MailTemplateInstaller::tagsFor()` e i dati realmente presenti nel payload. Vengono verificati soltanto i segnaposto JD Learn in forma `{UPPER_SNAKE_CASE}`, per non interpretare arbitrariamente altri costrutti editoriali. Tag non dichiarati, non disponibili per l'evento o dati non scalari causano **blocco dell'invio**, senza generare un messaggio con variabili irrisolte. Gli errori di modello sono **definitivi nella coda** (senza retry SMTP) e la diagnostica non registra destinatari, token o contenuti delle email; l'operatore deve correggere il modello e valutare esplicitamente un nuovo invio. Gli errori di trasporto mantengono le precedenti regole di retry.

Questo è un controllo **al dispatch**, non un editor/validatore preventivo nel modulo amministrativo dei template. Rimangono da implementare la validazione in fase di modifica/anteprima, una guida contestuale Joomla e le nuove variabili approvate ma non ancora prodotte. **Test Joomla/SMTP manuali richiesti**: template personalizzato con token non supportato, token registrato ma non fornito, template valido, HTML/oggetto e coda; nessun avanzamento automatico dell'accettazione.


## MAIL-E — guida alle variabili nel backend amministrativo (PR incrementale)

In **JD Learn → Coda email** è disponibile una sezione espandibile **Guida alle variabili dei modelli email**. Ogni modello registrato ha una tabella con il segnaposto canonico, il tipo di dato e la descrizione nella lingua dell'amministrazione (italiano/inglese). L'elenco dei modelli è letto da `MailTemplateInstaller::templateIds()` e il contenuto da `MailTemplateVariableCatalog::forTemplate()`: **non esiste un elenco separato da aggiornare manualmente**. Questo risolve anche il filtro della coda, che prima ometteva `com_jlms.course.expiring`.

La guida è **in sola lettura**, non introduce un editor parallelo e non cambia i permessi per modificare i Mail Templates Joomla. È visibile a chi dispone dell'accesso autorizzato alla coda JD Learn; i dati della guida sono soltanto descrizioni statiche, senza destinatari o contenuti email. Gli amministratori modificano i modelli attraverso la funzionalità nativa **Sistema → Modelli email**. I nomi delle variabili sono univoci e **non esistono alias**.

**Limiti:** i tag elencati sono quelli registrati, ma alcune variabili dipendono dall'evento effettivo; nuove variabili e relativa popolazione restano da sviluppare. La guida non è ancora incorporata nell'editor core di `com_mails` e non risolve la traduzione di titolo/descrizione segnalata nell'issue #312. **Collaudo richiesto:** guida in IT/EN, permessi di accesso, accessibilità da tastiera, filtri della coda inclusa scadenza, confronti con i Mail Templates e responsive. La CI non equivale all'accettazione manuale Joomla.


## Correzione collocazione guida (PR #356 — decisione successiva)

La guida dei segnaposto **non appartiene alla Coda email**: quest'ultima resta dedicata a stati, filtri e gestione operativa degli invii. La guida è ora una pagina autonoma **JD Learn → Dashboard → Comunicazioni → Guida alle variabili email** (`option=com_jlms&view=mailvariables`), accanto al collegamento ai **Modelli email** nativi di Joomla. Da questa pagina è possibile aprire i Modelli email soltanto se l'utente possiede anche i permessi `core.manage` e `core.edit` di `com_mails`. L'accesso alla guida richiede `core.manage` su `com_jlms`; non contiene destinatari né messaggi.

Il catalogo di variabili e i suoi nomi canonici restano invariati e provengono dalle definizioni registrate; non è stato introdotto un editor di template parallelo. La documentazione precedente che indicava la guida dentro la Coda email è **superata**. Verifiche Joomla da eseguire: navigazione dalla dashboard, gestione dei permessi, accessibilità, traduzioni IT/EN, layout, filtro della coda indipendente e assenza di contenuti privati.


## MAIL-E — due nuove variabili alimentate da dati persistiti (PR di sviluppo)

I template di **conferma iscrizione** (`com_jlms.enrollment.confirmed`) e **completamento corso** (`com_jlms.course.completed`) espongono rispettivamente `{ENROLLMENT_DATE}` e `{COMPLETION_DATE}`. I valori sono ottenuti dagli stessi timestamp UTC (`YYYY-MM-DD HH:MM:SS`) registrati nella tabella delle iscrizioni, non da una nuova data calcolata al momento della consegna email. Anche le iscrizioni manuali inseriscono il proprio `created_at` nel payload. Le variabili sono registrate nel catalogo tipizzato IT/EN, presenti sia nei corpi in testo semplice sia HTML e sono disponibili per i nuovi messaggi accodati dopo l'aggiornamento.

**Ambito e limiti:** non viene dedotto il nome proprio dall'intero nome dell'utente; `USER_FIRSTNAME` resta escluso fino a quando esisterà un campo affidabile. La data è esplicitamente **UTC**, non il formato locale dell'utente. I template Joomla già personalizzati e installati non vengono sovrascritti: per usare i nuovi segnaposto si deve verificare l'aggiornamento dell'elenco dei tag registrati nel database Joomla e personalizzare consapevolmente il modello. **Collaudo manuale Joomla/SMTP:** nuovo sito, upgrade, nuova iscrizione manuale/automatica, completamento corso e assenza di valori errati; controllare lingua, contenuto HTML/testo e retry della coda. Non confondere questo incremento con l'implementazione dell'intero catalogo di nuove variabili.


## MAIL-E — aggiornamento sicuro dei tag di template esistenti (PR #358)

In fase di installazione e aggiornamento, `MailTemplateInstaller::ensureRegistered()` continua a creare **solo** i modelli mancanti, e `MailTemplateTagUpdater::synchronize()` aggiorna **solo** l'elenco `params.tags` delle righe esistenti in `#__mail_templates`, comprese le traduzioni per lingua. Il sistema inserisce i segnaposto canonici effettivamente dichiarati per ciascun template, compresi `ENROLLMENT_DATE` e `COMPLETION_DATE`, preservando eventuali tag aggiuntivi non obsoleti. Elimina dai soli elenchi dei tag i nomi storici non supportati `NAME`, `COURSE`, `SITENAME` e varianti `*_HTML`: non sono alias. Non modifica i campi `subject`, `body`, `htmlbody`, gli allegati, la lingua né le altre preferenze `params` (mittente, formato, ecc.). L'operazione è idempotente; metadata JSON non valido genera un errore controllato senza sovrascrivere la riga.

**Importante:** i testi personalizzati che contengono vecchi placeholder non vengono riscritti; gli amministratori devono sostituirli esplicitamente, perché la validazione al dispatch li rifiuta. I nuovi segnaposto divengono disponibili nell'elenco del gestore Joomla dopo l'upgrade, ma non vengono inseriti automaticamente in email già personalizzate. La verifica dei permessi Joomla, delle lingue e dei template personalizzati in un sito reale resta un **collaudo manuale pendente**. I test PHP coprono fusione e idempotenza dei metadati; la CI di integrazione verifica la capacità di installare il componente.


## MAIL-E — DASHBOARD_URL per completamento e riattivazione

Gli eventi `com_jlms.course.completed` e `com_jlms.enrollment.reactivated` rendono disponibile la variabile canonica **`{DASHBOARD_URL}`** nella versione testo e HTML dei modelli predefiniti. Il valore proviene da `Uri::root()` di Joomla e punta alla dashboard personale, che richiede autenticazione e verifica autonomamente le autorizzazioni. Il link non contiene token o informazioni riservate ed è popolato esclusivamente per gli eventi pertinenti; una sospensione non fornisce questo link come invito ad accedere. I segnaposto sono descritti nel catalogo IT/EN e vengono sincronizzati nei modelli esistenti dal meccanismo di upgrade già integrato in PR #358.

Il link non è una scorciatoia verso contenuti senza autorizzazione né un'autorizzazione implicita a un corso. Gli amministratori possono rimuoverlo dai template nativi Joomla senza perdere le personalizzazioni durante gli aggiornamenti. **Verifiche manuali richieste:** iscrizione di prova, completamento, sospensione e riattivazione reali; corretto URL assoluto nel sito Joomla anche in sottodirectory; mail HTML/testo, lingue, permessi effettivi alla dashboard dopo login e conservazione delle personalizzazioni esistenti. La CI non sostituisce il collaudo SMTP.


## MAIL-F — Annullamento amministrativo di un'iscrizione (incremento PR #361)

Il backend **Corsi → Iscritti** offre un collegamento **Annulla iscrizione** soltanto per iscrizioni in stato `active` o `suspended`, prive di data di completamento. La stessa pagina nativa **Gestione stato iscrizione** mostra una **anteprima dell'impatto** e un avviso di irreversibilità prima della conferma POST con CSRF, mutazione UUID, revisione e hash dell'anteprima. Il servizio applicativo ripete tutte le autorizzazioni e i controlli dopo un lock sul record. L'annullamento è vietato su iscrizioni completate, già annullate o con quiz ancora aperti; **non elimina progressi, tentativi, cronologia, attestati e collegamenti**. Da `cancelled` non è consentita riattivazione tramite il flusso di sospensione. La stessa richiesta ripetuta mantiene l'ID del primo evento e non accoda duplicati; ripetizioni con contenuto diverso danno conflitto.

La transizione effettiva genera **una notifica idempotente** via `MailQueue` e Scheduler con modello Joomla `com_jlms.enrollment.cancelled` (oggetto, testo e HTML personalizzabili) e preferenza `mail_cancellation_enabled` globale + `mail_cancellation` per corso (eredita/abilitata/disabilitata). Non inserisce il motivo interno nell'email e non crea nuove regole di consenso per le comunicazioni operative. Il testo non contiene dati di altri studenti. Gli errori di invio seguono la coda e l'audit già esistenti. **Non implementa** approvazione/rifiuto richieste (modalità pending oggi non disponibile), né una revoca con un suo distinto contratto: questi restano altri incrementi MAIL-F.

**Collaudi Joomla manuali da eseguire:** conferma/cancellazione su iscrizione attiva o sospesa, visualizzazione e traduzioni IT/EN, divieto con quiz aperti o completamento, impossibilità di accesso studente dopo annullamento, integrità cronologia, ACL di corso, anti-CSRF, idempotenza della doppia conferma, preferenze globali/per corso, assenza di motivo riservato nell'email e invio SMTP da coda, conservazione template custom in upgrade. CI positiva non equivale ad accettazione manuale.

## MAIL-F — Richiesta di iscrizione con approvazione (incremento pending)

Nei corsi pubblicati in modalità `approval`, la scheda pubblica propone a un utente autenticato e autorizzato il pulsante **Richiedi iscrizione**. Il comando POST richiede token CSRF, identifica l'utente tramite identità Joomla aggiornata, verifica accesso, versione pubblicata, edizione aperta e finestra temporale; crea una riga in `#__jlms_enrollment_requests` in stato `pending` con vincolo univoco soggetto/edizione. Ripetere la richiesta restituisce la stessa riga senza accodare notifiche duplicate. Una richiesta già decisa non viene ripristinata implicitamente.

L'evento `com_jlms.enrollment.requested` usa il Mail Template Joomla e la coda persistente esistente con preferenze globali/per corso `mail_request_enabled` / `mail_request`. La richiesta non crea iscrizione attiva, non consuma posti, non garantisce ammissione e non concede accesso didattico. L'approvazione/rifiuto amministrativo e le relative notifiche sono un incremento separato di MAIL-F. Collaudo Joomla e SMTP ancora pendente.

## MAIL-F — Decisioni amministrative di approvazione/rifiuto

Le richieste `pending` sono gestite dal roster amministrativo del singolo corso. La decisione è atomica rispetto a iscrizione, audit e notifica e rivisita accesso, versione, finestre e disponibilità prima di creare un'iscrizione. Il rifiuto non crea accessi. La coda utilizza i template nativi modificabili `com_jlms.enrollment.approved` e `com_jlms.enrollment.rejected`, con preferenze globali/per corso `mail_approval_enabled`/`mail_approval` e `mail_rejection_enabled`/`mail_rejection`. I dati della motivazione amministrativa non sono nei payload di invio. Collaudo SMTP e UX pendente.


**MAIL-F post-merge:** PR [#367](https://github.com/jeckodevelopment/jd_learn/pull/367) integrata in main il 14 novembre 2026, merge `902480be3eec7aa7cdf0bd8b6ba4603cab7172c5`. CI standard **4/4 superata** nel [run 43617948055](https://github.com/jeckodevelopment/jd_learn/actions/runs/43617948055), compresi PHP 8.3/MySQL, PHP 8.4/MariaDB, Chromium e validazione dei requisiti [run 43617948126](https://github.com/jeckodevelopment/jd_learn/actions/runs/43617948126). Pacchetto installabile [JD-Learn-0.56.19-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43617948055/artifacts/14123305769). Implementati approvazione/rifiuto delle richieste pending, capienza serializzata, ACL, audit nativo e due template email Joomla; l'accettazione manuale Joomla/SMTP resta pendente. La crescita implementativa globale è stimata **intorno al 80%**: stima ragionata dalla baseline 78,67% con la macroarea email approssimativamente 64%→72% sui nove blocchi ugualmente pesati; il delta non equivale ad accettazione e richiede una ripesatura formale di tutte le righe della matrice. MAIL-G/H, privacy e release gate restano aperti.

## MAIL-G — Notifica al docente assegnato (incremento 0.56.21-dev)

L'incremento MAIL-G introduce il template Joomla **`com_jlms.teacher.assigned`**, modificabile dall'editor nativo (oggetto, testo e HTML). Viene accodato **solo dopo una nuova associazione** di profilo docente a un corso esistente, non per gli ordinari salvataggi, riorganizzazioni dei docenti già assegnati, docenti senza account Joomla collegato o utenze bloccate. Il contenuto include solo `USER_FULLNAME`, `COURSE_TITLE`, `SITE_NAME`, senza dati degli studenti e senza link che attribuiscano automaticamente accesso amministrativo. Un docente collegato a più profili riceve una sola mail per singola revisione di assegnazione.

La preferenza globale `mail_teacher_assignment_enabled` e l'override di corso `mail_teacher_assignment` controllano l'invio. Accodamento nella stessa transazione di modifica del corso e chiave di idempotenza per corso/revisione/account; al dispatch si rivalida che il profilo sia ancora associato al corso, che l'account non sia bloccato e che la mail non sia cambiata. In caso contrario l'elemento è saltato e i dati di destinatario/payload vengono svuotati. Nessun docente riceve privilegi aggiuntivi dalla notifica.

**Collaudi manuali da eseguire:** assegnazione nuova a docente con account, profilo non collegato, modifica del corso senza nuova assegnazione, ripetizione/rimozione prima dell'invio, account bloccato, preferenze globali/per corso, template personalizzato, prova SMTP in IT/EN. Gli altri eventi MAIL-G (nuova lezione, variazione significativa, avviso ai responsabili) restano da implementare.

**Post-merge MAIL-G (#376, 14 novembre 2026):** PR [#376](https://github.com/jeckodevelopment/jd_learn/pull/376) integrata con merge `387b6d28a66f64ab89d2eba50849d09bfff98c06`. CI [43630634077](https://github.com/jeckodevelopment/jd_learn/actions/runs/43630634077) **4/4 verde** (compreso test Joomla con notifica unica al docente con account associato; nessuna notifica al docente senza account); [validazione requisiti](https://github.com/jeckodevelopment/jd_learn/actions/runs/43630633929) superata. Artefatto [JD-Learn-0.56.21-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43630634077/artifacts/14128431017), che è un **contenitore Actions da estrarre** per ottenere `pkg_jlms-0.56.21-dev.zip` realmente installabile; SHA-256 dello ZIP interno `5fc55817dab76cce76b85b27b6cfde2ee5350e3084a1c567e2a4410414fb100c`. Accettazione SMTP/utente su Joomla 6.2.0 **pendente**, mentre l'installazione della precedente 0.56.20-dev è stata confermata dall'utente. **Stima sviluppo: ~80% (arrotondata), delta stimato +0,2 punti percentuali non formalizzati rispetto al post-MAIL-F**; il solo evento docente di MAIL-G non completa tutto il blocco.


## MAIL-H — localizzazione nomi dei modelli (PR #395 / issue #312)

I 14 Mail Templates del componente registrano ora titolo e descrizione nelle stringhe di lingua **administrator `com_jlms.ini` e `com_jlms.sys.ini`**, in italiano e inglese. Joomla usa queste stringhe nel gestore nativo **Sistema → Modelli email** (lista e dettaglio). I codici sono della forma `COM_JLMS_MAIL_<EVENTO>_TITLE` e `COM_JLMS_MAIL_<EVENTO>_DESC`, derivati dagli ID `com_jlms.*` sostituendo i punti con `_`. La PR non modifica subject/body, i placeholder canonici, le personalizzazioni del sito o il trasporto SMTP. Test manuale IT/EN e upgrade documentato in [QA_TESTBOOK.md](QA_TESTBOOK.md).

### Errore 422 su un modello di comunicazione già installato

Il compositore converte nella copia locale `{NAME}`, `{COURSE}` e `{SITENAME}` nei rispettivi `{USER_FULLNAME}`, `{COURSE_TITLE}` e `{SITE_NAME}`, anche nelle varianti `_HTML`. Vale anche per i modelli personalizzati o tradotti conservati da un aggiornamento. I tre slot `MESSAGE_*` sono sostituiti da testo modificabile. La versione in testo semplice usa le varianti senza escaping HTML. Per i nuovi modelli usa le variabili canoniche mostrate nell'editor. Se un'altra variabile causa il 422, il messaggio indica quali segnaposto sostituire.

Per il collaudo su Joomla, aggiorna un sito di prova che abbia già un modello `com_jlms.course.message` con oggetto `{COURSE}: {MESSAGE_SUBJECT}` e corpo HTML con `{NAME_HTML}`, `{COURSE_HTML}`, `{SITENAME_HTML}` e `{MESSAGE_BODY_HTML}`. Con accesso al registro di un corso e un iscritto attivo, apri **Comunicazione agli iscritti**, modifica il testo e chiedi l'anteprima. Verifica i tag canonici nell'editor e i valori sostituiti nell'anteprima. Ripeti con una variante IT/EN personalizzata, verifica che il modello in Joomla conservi il testo originale, quindi prova il 422 inserendo `{COURSE_URL}`. Rimuovi la variabile, invia una prova al tuo account e accoda una comunicazione di prova per un solo iscritto. Esegui lo Scheduler e verifica oggetto, HTML e parte in testo semplice.


## MAIL-E — variabili di contesto corso, lezione e docente

La PR [#464](https://github.com/jeckodevelopment/jd_learn/pull/464), integrata, completa i produttori sicuri. I modelli `com_jlms.lesson.available`, `com_jlms.teacher.assigned` e `com_jlms.course.published` dichiarano i nuovi placeholder solo nei contesti pertinenti. L'arricchimento avviene al confine della coda, così i valori sono disponibili sia per invii nuovi sia per template Joomla aggiornati; l'upgrade sincronizza i tag senza riscrivere oggetto, testo o HTML personalizzati.

| Variabile | Origine e fallback | Uso |
|---|---|---|
| `COURSE_DESCRIPTION` | Testo estratto dalla descrizione editoriale del corso, filtrata in salvataggio; vuota se assente | Lezione, docente, annuncio |
| `COURSE_DURATION` | Stima in minuti configurata; vuota se non impostata | Lezione, docente, annuncio |
| `COURSE_START_DATE` | Inizio calendario configurato, formato UTC `Y-m-d H:i:s UTC`; vuota se assente/non valido | Lezione, docente, annuncio |
| `LESSON_DESCRIPTION` | Sempre vuota: non si riusa il corpo didattico protetto come descrizione email | Avviso lezione |
| `LESSON_URL` | URL alla vista aula autenticata, con ID iscrizione e lezione selezionate; vuoto senza entrambi gli ID | Avviso lezione |
| `TEACHER_NAME` | Titolo del profilo assegnato; vuoto se non presente | Assegnazione docente |
| `CONTACT_EMAIL` | `mailfrom` del sito Joomla solo se è un indirizzo valido; altrimenti vuoto | Lezione, docente, annuncio |

Tutti i valori di testo vengono escapati dal renderer Joomla nei template HTML. Non esiste un campo affidabile per il nome proprio: `USER_FIRSTNAME` non è registrato e i modelli devono usare un saluto neutro se non vogliono mostrare il nome completo.


## Migrazione token legacy nei modelli Joomla (issue #554)

Durante installazione/upgrade, la sincronizzazione dei Mail Templates aggiorna nei soli modelli `com_jlms.*` i placeholder esatti precedenti: `{NAME}` → `{USER_FULLNAME}`, `{COURSE}` → `{COURSE_TITLE}`, `{SITENAME}` → `{SITE_NAME}`, incluse le rispettive varianti `_HTML`. La modifica riguarda oggetto, corpo testuale e HTML di tutte le righe lingua esistenti. Ogni altro testo, contenuto personalizzato, allegato, mittente e opzione del modello resta invariato. L'operazione è idempotente e non introduce alias runtime. Dopo l'upgrade verificare in Sistema → Modelli email il modello interessato, riprovare un messaggio in errore con le funzionalità di ritentativo controllato, quindi verificare la consegna SMTP. La modifica non riaccoda automaticamente invii falliti.
