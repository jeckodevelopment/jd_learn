# Iscrizione manuale singola — incremento 0.19

Dal backend **Corsi → Iscritti → Iscrizione manuale** si apre `view=manualenrollment&id=ID_CORSO`. Il form Joomla seleziona l’ID di un account esistente e una lingua sito installata, poi prepara un’anteprima. Non crea account/password, non assegna gruppi, non invia messaggi e non importa CSV.

## Ammissione e perimetro

Servono account operatore attivo, `core.manage` sul componente e `jlms.enrollments.manage` sul singolo corso, ricontrollati anche sui reinvii. Il destinatario viene ricaricato dai conti Joomla: deve esistere ed essere attivo. Nel percorso standard deve già essere autorizzato per catalogo, categoria/antenati, lingua e contenuti. Dalla0.20 la [preassegnazione esplicita](PREASSIGNMENT.md) consente l’assegnazione senza i livelli correnti, con avviso e fruizione ancora protetta. Il form non espone un elenco globale di account, email o gruppi: in questa prima versione l’operatore indica l’ID nativo. Il selettore ricercabile resta da completare.

La destinazione è esclusivamente la versione pubblicata corrente con regole già supportate e la sua edizione `default-vN`, aperta e nella finestra valida. Non sono ammessi ID arbitrari di versione/edizione. Sono supportati corsi in modalità auto o explicit; approval rimane chiusa finché il flusso richieste/approvazioni non sarà implementato. La lingua selezionata deve consentire la consultazione del corso e rispettare le disponibilità native quando il filtro lingua è attivo; non traduce il corso né trasferisce progressi.

L’anteprima mostra account, corso, versione/edizione, occupazione/capienza ed eventuale iscrizione esistente. Non carica corpi di lezioni, risposte, regole di valutazione o URL media. Un’iscrizione già presente nella stessa edizione, anche sospesa/annullata/completata, non viene duplicata, riattivata o resettata. Per la sospensione usare il flusso dedicato, per una nuova frequenza servirà una nuova edizione. I posti occupati seguono la stessa regola di Inizia: tutte le iscrizioni tranne cancelled consumano capienza.

## Conferma e concorrenza

Il comando richiede motivo interno, conferma esplicita, token CSRF Joomla, UUID v4 e hash dell’anteprima. Solo `POST administrator/index.php?option=com_jlms&task=manualenrollment.apply`, con `jform[course_id,user_id,language,reason,mutation_id,impact_hash,confirmation]`; niente modifica tramite GET.

Il lock dell’edizione è quello usato da Inizia. Dopo l’attesa si rileggono operatore, destinatario, corso/versione/edizione, capienza e duplicati, prima di creare dati. Una nuova versione corrente, un cambio di finestra/capienza o un altro ingresso possono invalidare l’anteprima. Richieste concorrenti non superano la capienza e una corsa fra Inizia e iscrizione manuale produce una sola iscrizione. Il vincolo subject/edizione resta la protezione database finale.

L’UUID è reclamato atomicamente, con hash del comando e marker interno della transazione. Stessa richiesta e stesso UUID restituiscono l’iscrizione originale, senza creare altre righe o log; un payload diverso è conflitto. Anche il replay richiede diritti correnti dell’operatore e del destinatario e disponibilità corrente del corso. Se l’iscrizione è stata sospesa nel frattempo, il replay non la riattiva. Il marker interno non è una credenziale e non entra nel DTO o nell’HTML.

Subject, iscrizione, registrazione della richiesta e Action Log vengono creati nella stessa transazione. Un errore di audit annulla tutto, compreso il subject eventualmente nuovo. I subject concorrenti sono risolti con vincolo univoco e lettura bloccante corrente. Nessun record incompleto viene commesso. La creazione imposta origin=manual, created_by/modified_by=operatore, status=active, contatori iniziali senza progressi inventati; le finestre dell’edizione continuano ad applicarsi senza modificarle. La fruizione successiva ricontrolla sempre l’accesso.

## Dati e audit

Nuova tabella additiva `#__jlms_manual_enrollments` (totale41tabelle/58FK): riferimento univoco all’iscrizione/versione, attore, UUID/hash richiesta, marker di claim, hash impatto, motivo e data UTC. FK composita sull’iscrizione/versione, UUID globalmente univoco e al massimo una registrazione manuale per iscrizione. I riferimenti risultato sono nullable solo durante la transazione di claim: il servizio non conferma mai una riga senza risultato. L’evento di creazione non viene modificato dai replay o dalle successive transizioni di stato. Non vengono reinterpretate iscrizioni storiche né eseguiti backfill.

Action Logs nativo `com_jlms.manualenrollment`, con operatore, ID iscrizione e riferimento al motivo. Il testo del motivo rimane nel record interno, visibile agli operatori autorizzati nel riepilogo dell’iscrizione; nessun motivo nell’area personale studente. Nessuna email nella transazione o implicita alla creazione. Invii opzionali/outbox, import e lifecycle privacy generale rimangono da implementare.

## MVC e personalizzazione

`ManualEnrollmentService` è registrato nella DI. MVC, FormModel, Form/LanguageField delle lingue sito installate, sessione/CSRF, asset/ACL e utenti sono core Joomla. Il controller accetta soltanto apply. Risposte private/no-store ed errori tecnici generici senza SQL. Anonimi negati dal servizio401 (backend può presentare login), permessi/account/livello/finestra403, corso/account mancanti404, input400/422, anteprima/duplicati/capienza/modalità non disponibile409, audit indisponibile503. Motivo massimo500byte UTF-8 senza markup/caratteri di controllo; body POST16KiB.

Override amministrativo `html/com_jlms/manualenrollment/default.php`. ID stabili: `jlms-manual-enrollment`, `jlms-manual-policy`, `jlms-manual-selection`, `jlms-manual-impact`, `jlms-manual-form`, `jlms-manual-blocked`, `jlms-manual-existing`, `jlms-manual-receipt`, `jlms-manual-roster`; link in elenco `jlms-roster-manual`. Gli stili sono quelli nativi del template amministrativo. Conservare escaping, anteprima, conferma/token e i controlli del servizio.

## Collaudo e limiti

Prove su sito effimero: diritti per corso, destinatario senza livello/bloccato, lingua e disponibilità, nessuna scrittura nell’anteprima, rollback completo senza audit, replay e revoche, iscrizione già sospesa, richieste simultanee, capienza, corsa con Inizia, accesso studente e conservazione di origine/registrazione/audit all’aggiornamento. Esiti effettivi nelle Actions della PR di incremento. ISC-01 e gestione completa iscrizioni restano parziali: import CSV, selettori ricercabili, approvazioni, edizioni/coorti aggiuntive e notifiche sono ancora pendenti, come collaudi browser/accessibilità completi, patch corrente Joomla e carico300/500.

## Opzione0.20

Vedere [PREASSIGNMENT.md](PREASSIGNMENT.md) per `filter[preassignment]`/`jform[preassignment]`, l’avviso e le regole di replay. Default0/assente mantiene la politica e gli hash precedenti; nessun nuovo campo obbligatorio per i chiamanti standard.

## Stabilizzazione concorrenza0.22

Avvio automatico e ammissione manuale acquisiscono il lock dell’edizione prima che una lettura consistente crei lo snapshot transazionale. I candidati versione/edizione sono risolti fuori dalla transazione e ricontrollati dopo l’attesa, insieme all’identità/accessi correnti. In questo modo un’ammissione appena confermata dal precedente titolare del lock viene vista e riutilizzata; non si tenta un secondo inserimento. Test deterministico `tests/joomla-manual-enrollment.php` con worker bloccato e commit concorrente; restano tutte le prove di capienza, UUID, autorizzazioni e rollback/audit.
