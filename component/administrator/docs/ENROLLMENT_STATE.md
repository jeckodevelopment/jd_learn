# Sospensione e riattivazione

Dal backend **Corsi → Iscritti → Sospensione e riattivazione** si apre `view=enrollmentstate&id=ID_CORSO&enrollment_id=ID_ISCRIZIONE`. L’anteprima GET propone il target opposto per iscrizioni attive/sospese; `target_status=active|suspended` consente di richiedere esplicitamente una delle due anteprime. Il comando POST contiene sempre un target esplicito: non è un toggle dipendente dal momento del reinvio.

## Effetti e limiti

Sono ammesse attiva→sospesa, stato storico completed→sospesa e sospesa→attiva. Il completamento è sempre quello separato in `completed_at`, non viene ricalcolato o cancellato. Richieste, iscrizioni annullate/rifiutate/scadute non vengono approvate o riattivate da questo flusso. Annullamento, reset e proroghe formative restano da implementare. La creazione manuale è disponibile nel flusso distinto [MANUAL_ENROLLMENT.md](MANUAL_ENROLLMENT.md).

La sospensione blocca aula, nuove attività, salvataggi/consegne quiz, download e rilascio/rinnovo dei token attraverso i controlli runtime già esistenti. Una risorsa già trasferita al browser non viene recuperata e un token video già emesso rimane soggetto alla sua scadenza: nessuna revoca retroattiva garantita presso i provider. Risposte salvate e snapshot restano intatti; il timer continua. La scadenza server chiude i quiz temporizzati sulle risposte già confermate tramite il job esistente; un quiz senza timer resta aperto ma inutilizzabile fino a ripristino o annullamento autorizzato. La riattivazione non riapre tentativi conclusi né azzera o estende deadline.

Riattivare rimuove soltanto il blocco didattico e può lasciare altri vincoli ancora non soddisfatti: livelli Joomla, account, corso/categorie, versione/edizione e finestre continuano a essere verificati durante la fruizione. Non vengono assegnati gruppi né modificate finestre, iscrizione/versione/edizione, contatori, completamenti, progressi o attestati. Il motivo è interno: non appare nell’area personale o nei DTO studente.

## Anteprima, conferma e concorrenza

Servono account attivo, `core.manage` sul componente e `jlms.enrollments.manage` sul corso, ricaricati anche sui reinvii. Gli ID devono appartenere allo stesso corso. L’anteprima mostra stato prima/dopo, progressi conservati e numero di quiz aperti; non mostra risposte o chiavi di valutazione. Per applicare sono necessari motivo breve, conferma, token CSRF Joomla, UUID v4 della richiesta, revisione dell’iscrizione e hash dell’anteprima.

Il server prende il lock dell’iscrizione prima delle nuove letture di autorizzazione e della proiezione: lo stesso ordine dei writer didattici/quiz. Il piano include la revisione corrente e le revisioni/deadline dei quiz aperti. Un progresso, una consegna o un’altra modifica successivi all’anteprima richiedono di ricaricarla. Due comandi diversi concorrenti non applicano due volte la stessa transizione. Un reinvio identico con lo stesso UUID restituisce l’evento originale, anche dopo una successiva riattivazione, senza riapplicare lo stato storico; una richiesta diversa con lo stesso UUID è un conflitto. Anche il replay richiede i permessi correnti.

Limiti protettivi: motivo massimo500 byte UTF-8 senza markup/caratteri di controllo, body POST16KiB, massimo10000 eventi per iscrizione; oltre1000 quiz aperti l’azione resta bloccata. Non sono limiti commerciali o una certificazione di carico. Il comando è `POST administrator/index.php?option=com_jlms&task=enrollmentstate.apply`, campi `jform[course_id,enrollment_id,enrollment_revision,target_status,reason,mutation_id,impact_hash,confirmation]`. GET su apply405, CSRF/permessi403, ID estranei404, input malformato400/422, conflitto409, audit indisponibile503. Il backend core può mostrare il login se è stato revocato anche il diritto di ingresso. Errori tecnici non espongono SQL o dati interni.

## Persistenza e audit Joomla

Tabella `#__jlms_enrollment_state_changes`: evento con revisione progressiva, revisione di origine dell’iscrizione, stati prima/dopo, hash impatto, motivo, attore, UUID/hash richiesta e data UTC. Vincoli univoci su iscrizione/revisione e iscrizione/UUID; FK composita su iscrizione/versione. Nessun backfill o reinterpretazione dello storico precedente. Eventi append-only nel servizio; nessun endpoint di modifica/cancellazione degli eventi. L’attore è un riferimento storico nullable, secondo il modello già usato per le revisioni amministrative.

Cambio stato, incremento revisione, evento e Action Log nativo `com_jlms.enrollmentstate` vengono registrati nella stessa transazione. Se l’audit non è scritto e verificato, si annulla tutta l’operazione. Il log conserva solo prima/dopo e riferimento al motivo, non il testo del motivo o risposte quiz. La tabella dello storico è la fonte dell’idempotenza anche se un log core viene rimosso secondo la sua conservazione. Non vengono inviate email nella transazione; notifiche/outbox e lifecycle privacy generale rimangono da implementare.

## MVC, override e verifica

`EnrollmentStateService` è registrato nella DI del componente; FormModel, HtmlView, form e controller sono nativi Joomla. Risposte private/no-store. Override amministrativo `html/com_jlms/enrollmentstate/default.php`; ID stabili `jlms-enrollment-state`, `jlms-enrollment-state-policy`, `jlms-enrollment-state-impact`, `jlms-enrollment-state-form`, `jlms-enrollment-state-blocked`, `jlms-enrollment-state-history`, `jlms-enrollment-state-roster`. La tabella mostra le ultime20 modifiche con motivo escapato, attore e date UTC. Conservare escaping, conferma/token, dati dell’anteprima e i controlli del servizio negli override.

Test previsti su Joomla reale e sito effimero: autorizzazione per corso e revoca su replay, invalidazione dell’anteprima, doppio invio simultaneo/CAS, audit indisponibile con rollback, fruizione negata e ripristinata senza gruppi o proroghe, quiz aperto con risposte/deadline conservate, form/CSRF e conservazione al reinstall. Gli esiti della suite corrente sono nel [registro delle prove](IMPLEMENTATION_EVIDENCE.json). Non è completamento di ISC-02/03/05/07, report, attestati, privacy o certificazione browser/accessibilità/carico e patch Joomla corrente.
