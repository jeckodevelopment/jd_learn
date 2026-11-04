# Preassegnazione senza accesso corrente

Dal backend **Corsi → Iscritti → Iscrizione manuale**, selezionare utente/lingua e, soltanto se voluto, **Consenti preassegnazione senza accesso corrente**. L’opzione è disattivata per default. Senza opzione resta la regola della0.19: il destinatario deve poter accedere, anche sui replay. Il percorso approvazioni rimane chiuso.

## Assegnazione e diritto di fruizione

La preassegnazione registra un’iscrizione didattica alla versione pubblicata corrente e all’edizione predefinita; non è un livello di accesso alternativo. Il destinatario deve esistere ed essere attivo. L’operatore deve avere `core.manage` sul componente e `jlms.enrollments.manage` sul corso, ricaricati anche dopo il lock e sui reinvii. Nessun gruppo Joomla viene creato o assegnato.

Con l’opzione si può assegnare un corso pur mancando livelli di consultazione del catalogo/categoria/contenuti o della lingua pubblicata. Sono comunque obbligatori: lingua sito installata e, con Filtro lingua attivo, contenuto lingua pubblicato; corso/versione disponibili, categoria/antenati pubblicati e coerenti per lingua, livelli configurati esistenti, edizione aperta e finestra valida, modalità auto/explicit e regole didattiche supportate. Una configurazione mancante, una categoria ritirata, un account bloccato o un corso in approvazione non diventano ammissibili tramite l’opzione. Nessun anteprima pubblica o URL media viene creato.

L’anteprima espone `preassignment` e `has_current_access`. Se manca accesso, l’avviso locale `jlms-manual-access-warning` spiega che il titolo assegnato sarà visibile nello storico privato, mentre lezioni/risorse/risposte rimangono protette. Questa esposizione del titolo è una conseguenza della scelta dell’operatore autorizzato, non pubblicazione del corso nel catalogo. L’avviso descrive la disponibilità verificata nell’anteprima, non lo stato storico al momento di una precedente iscrizione.

Lo stato dell’iscrizione resta active, con origin=manual e zero completamenti iniziali; non si introduce uno stato commerciale o un diritto proprietario. La capienza è consumata secondo le regole già esistenti. Aula, completamento, quiz, materiali e rilascio/rinnovo video continuano a controllare i diritti correnti tramite i servizi esistenti. Risorse già trasferite/token validi seguono i limiti già documentati, senza promesse di revoca retroattiva.

Se il gestore o un’estensione attribuisce successivamente i gruppi corretti, lo studente usa la stessa iscrizione/versione; il LMS non interviene sui gruppi. Dopo una revoca l’accesso è di nuovo negato e i progressi restano. Una sospensione, un account bloccato o una scadenza non vengono rimossi dalla preassegnazione o dai replay.

## Contratto, concorrenza e compatibilità

La selezione GET aggiunge `filter[preassignment]=0|1`. Il POST nativo `manualenrollment.apply` aggiunge `jform[preassignment]=0|1` alla firma documentata in MANUAL_ENROLLMENT.md. Campo assente =0 per compatibilità. Altri valori/array sono rifiutati400; ragione, conferma e CSRF rimangono obbligatori.

`ManualEnrollmentService::editor/apply` aggiungono l’argomento finale opzionale booleano `preassignment=false`, preservando i chiamanti esistenti. Con false, formato dell’hash anteprima e dell’hash richiesta restano quelli della0.19; i nuovi booleani DTO sono additivi. Con true, scelta e disponibilità corrente sono vincolate all’anteprima, e la scelta è inclusa nell’hash della richiesta. Un cambiamento dell’eligibilità dopo l’anteprima richiede nuova verifica409; non si conferma silenziosamente una situazione diversa da quella presentata.

Un replay identico esplicito restituisce la stessa iscrizione anche dopo attribuzione o perdita del livello, purché operatore/account e disponibilità editoriale siano ancora validi. Non effettua nuove ammissioni, non riattiva iscrizioni e non concede diritti. Cambiare l’opzione non può riusare lo stesso UUID: quando il percorso di default è ora autorizzato restituisce conflitto409, altrimenti viene negato prima dalle normali regole403/404. I replay di comandi standard continuano a richiedere l’accesso del destinatario.

Lock edizione, capienza, duplicati, subject/receipt e audit sono gli stessi dell’ammissione manuale. Tutte le scritture sono atomiche; nessuna email automatica e nessuna modifica allo schema41tabelle/58FK. La verifica editoriale privata è raggiunta solo dopo lo scope amministrativo: non è un endpoint pubblico o un modo per saltare i controlli di fruizione.

## Audit, override e verifiche

I nuovi Action Logs `com_jlms.manualenrollment` includono `preassignment_requested` e `access_at_assignment` come snapshot dell’operazione, oltre ai riferimenti minimizzati esistenti. La ricevuta mantiene UUID/hash e riferimento al motivo; i nuovi flag storici sono nei log core e seguono la loro conservazione. I log precedenti non vengono riclassificati né ricreati dai replay. Il motivo resta interno e non entra nello storico studente.

Il form, checkbox, sessione e token sono nativi Joomla. Aggiornare gli override amministrativi del layout manualenrollment per conservare selezione, campo nascosto, avviso, conferma e token. ID nuovo `jlms-manual-access-warning`, classe core `alert alert-warning`, stato accessibile `role=status`; nessun CSS globale aggiunto. Il runtime non si affida alla presenza del checkbox o dell’avviso HTML per decidere l’accesso.

Le prove dell’incremento verificano default invariato, avviso/opt-in, hash compatibili, rifiuto di account/contenuti/lingue indisponibili, capienza/retry simultanei, rollback senza audit, ammissione senza gruppi, accesso negato, successiva attribuzione/revoca esterna e conservazione dei progressi/audit. Esiti effettivi nelle Actions della PR0.20. Non equivale a completamento dell’import CSV, di ISC-01 o del collaudo completo del prodotto.
