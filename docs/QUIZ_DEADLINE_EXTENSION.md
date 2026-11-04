# Proroga dei tentativi aperti

Il gestore può aggiungere tempo a un singolo tentativo con timer, soltanto mentre è aperto e prima della sua scadenza effettiva. È un'operazione distinta dal tempo aggiuntivo preventivo della0.11: non modifica i tentativi futuri, le regole pubblicate o lo snapshot originario.

## Flusso amministrativo

Dalla gestione dei tentativi selezionare quello aperto e seguire «Proroga del tentativo». Occorrono `core.manage` sul componente e, sul corso, entrambi `jlms.enrollments.manage` e `jlms.assessment.correct`, con ereditarietà e negazioni native Joomla. I permessi sono ricaricati su letture, applicazioni dirette e replay.

Scegliere1–1440minuti e aggiornare l'anteprima, che mostra la scadenza precedente, la nuova scadenza UTC e la durata effettiva totale. La proroga viene aggiunta alla scadenza esistente, non all'ora del clic. Per applicare occorrono motivazione operativa e conferma esplicita. Il motivo deve essere un riferimento all'autorizzazione, senza diagnosi/dati sanitari; una riga UTF-8 senza HTML, massimo500byte.

Limiti: al massimo48ore complessive dall'avvio, inclusi durata base, tempo individuale preventivo e tutte le proroghe; massimo1000proroghe per tentativo. L'iscrizione deve essere attiva o completata. Quiz senza timer, tentativi conclusi/annullati e tentativi con deadline già raggiunta non possono essere prorogati. Un tentativo logicamente scaduto è bloccato anche se nessun worker ha ancora materializzato la chiusura. Non esiste riapertura, accorciamento o modifica retroattiva della storia.

La proroga non assegna gruppi, livelli, iscrizioni, finestre didattiche o diritti commerciali. Un account bloccato o senza livello continua a non poter fruire del quiz. La regola di conclusione dopo la sola scadenza didattica rimane quella del flusso quiz; una proroga non scavalca revoche di sicurezza. Eventuali risultati già validi, completamenti o attestati non vengono modificati da questa operazione sul timer di un tentativo ancora aperto.

La pagina mostra le ultime20proroghe con precedente/nuova scadenza, autore e motivo. Ricerca, navigazione completa, esportazione e proroghe in massa restano successive.

## Regole immutabili e scadenza effettiva

`rules_snapshot_json` conserva la durata originaria, compreso il tempo individuale autorizzato prima dell'avvio. La proroga non cambia questo JSON, domande, risposte, punteggi o numero del tentativo. Una nuova riga in `quiz_deadline_extensions` registra precedente/nuova deadline, secondi aggiunti, revisione progressiva, hash dello snapshot originario, motivo, autore, UUID/hash del comando e data UTC. Il campo `attempts.deadline_at` è la proiezione della scadenza corrente.

Prima di prorogare si controlla che la proiezione coincida con l'ultima proroga, oppure con `started_at + rules.time_seconds` se non ci sono proroghe. Una discordanza o uno snapshot cambiato bloccano l'operazione; non si normalizzano silenziosamente dati storici.

La revisione delle risposte `attempts.revision` non cambia per una proroga: lo studente può continuare a salvare con la stessa revisione, perché le risposte non sono state modificate. La revisione dell'iscrizione invece aumenta, invalidando le anteprime amministrative precedenti; la storia delle proroghe ha una propria revisione. Autosave, consegna o altri cambiamenti dopo l'anteprima richiedono una nuova anteprima, con409 in caso di conflitto.

I lock seguono iscrizione → tentativo, come gli altri servizi quiz. Sotto lock si ricaricano identità/permessi e si controlla l'ora server corrente. Se la scadenza o una consegna hanno già chiuso il tentativo, non si applica la proroga. Se la proroga avviene prima, una consegna può riuscire successivamente con la nuova deadline. Due proroghe differenti dalla stessa anteprima non sommano il tempo due volte: una sola può riuscire.

Il worker Scheduler e i salvataggi leggono sempre la proiezione corrente. Superare la deadline originaria non chiude un tentativo correttamente prorogato; raggiungere la nuova deadline chiude sulle risposte già salvate. Le chiusure e le regole di valutazione restano idempotenti.

## Studente e sincronizzazione

Il DTO distingue `original_time_seconds`, `time_seconds` effettivi ed `extension_seconds`; mantiene separati i secondi individuali preventivi e la durata del prossimo tentativo. Non invia motivo, autore, hash o storia delle autorizzazioni allo studente.

Il browser riceve la nuova deadline dal normale salvataggio; se non ci sono risposte cambiate, sincronizza lo stato a intervalli di20secondi. Al raggiungimento della scadenza locale esegue una lettura autorevole, senza inviare nuove risposte: può scoprire una proroga già applicata e continuare, preservando le selezioni locali. Timer e avvisi vengono riallineati all'ora server e la nuova durata è annunciata in una regione `role=status`. Gli avvisi della deadline precedente vengono rimossi e riarmati sulle nuove soglie.

La sincronizzazione usa `quiz.state` con `compact=1`, `attempt_id`, `enrollment_id` e `lesson_id`: stesso accesso autenticato e `private, no-store`, ma nessuna ritrasmissione di domande o chiavi. Non sovrascrive selezioni né baseline del salvataggio. Se trova una diversa revisione delle risposte, ferma la scheda con un conflitto; se il tentativo è chiuso, ricarica il risultato. Non adotta silenziosamente risposte provenienti da un'altra scheda.

Resta una sincronizzazione a richieste, non push. La perdita di autorizzazione ferma le operazioni e richiede ricaricamento; nessuna proroga riabilita il client. Errori temporanei hanno backoff limitato, senza richieste di salvataggio aggiuntive per ogni tick. I test JavaScript con DOM/trasporto simulati non certificano tutti i browser o tutte le tecnologie assistive.

## MVC, comandi e audit

`QuizDeadlineService::editor` prepara impatto e storia autorizzati; `extend` applica. Vista/modello/form nativi `quizextend`; task `quizextend.apply`, POST con sessione Joomla, CSRF e corpo massimo16KiB:

| Campo | Vincolo |
|---|---|
| `course_id`, `lesson_id`, `enrollment_id`, `attempt_id` | ID positivi e relazioni verificate |
| `enrollment_revision`, `attempt_revision` | Revisioni dell'anteprima |
| `minutes` | Intero1–1440 |
| `impact_hash` | SHA256 dell'impatto corrente, non autorizzazione autonoma |
| `mutation_id` | UUIDv4 canonico minuscolo |
| `reason` | Motivo operativo obbligatorio, massimo500byte |
| `confirmation` | Esattamente1 |

Il comando è vincolato ad autore, parent, revisioni, minuti, motivo e hash d'impatto. Stesso UUID/corpo restituisce la stessa proroga senza aggiungere altro tempo, anche dopo ulteriori proroghe o chiusura. Stesso UUID con contenuto differente restituisce409. Ogni replay ricontrolla permessi e relazioni. Non esiste un endpoint del sito con cui lo studente possa impostare la deadline.

Action Logs Joomla, contesto `com_jlms.quizextend`, registra autore, tentativo, deadline precedenti/successive, secondi aggiunti, riferimento al motivo e correlazione. Non duplica il motivo libero o le risposte. Il modello nativo è usato con l'adattatore senza email; la verifica del log per ID e correlazione è obbligatoria. Se fallisce, riga di proroga e proiezione vengono annullate nella stessa transazione. Nessuna notifica esterna o tabella di audit parallela.

Tabella `#__jlms_quiz_deadline_extensions`, con FK al tentativo e unicità per tentativo/revisione e tentativo/UUID. Nessuna colonna precedente è alterata. Installer riprendibile e disinstallazione conservativa mantengono evidenze e autorizzazioni. Per lo schema corrente e i relativi conteggi vedere [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Verifica e limiti residui

CI: aggiornamento dalla0.12, storia e snapshot conservati, deadline effettiva con clock controllato, nessuna riapertura alla soglia, quiz senza timer, anteprime obsolete, replay/ACL/revoche, rollback dell'audit, due processi per proroghe concorrenti e proroga/scadenza, HTTP con form/CSRF e stato compatto. Il client è verificato anche sul recupero di una proroga al vecchio timeout senza perdita delle risposte locali. Le suite precedenti di quiz, annullamento, tempo preventivo, video e materiali restano eseguite. Esiti effettivi nelle Actions della PR.

Restano rivalutazioni dei punteggi, flussi di anomalie/attestati, operazioni in massa, privacy/reporting completi e certificazione della patch Joomla corrente, accessibilità/browser e carico300/500. Il componente completo è ancora in sviluppo.
