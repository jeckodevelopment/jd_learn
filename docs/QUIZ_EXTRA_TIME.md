# Tempo aggiuntivo individuale

Il gestore può autorizzare un numero di minuti aggiuntivi per una specifica iscrizione e uno specifico quiz pubblicato. Non è un'estensione di abbonamenti, livelli di accesso o finestre didattiche. Le specifiche QUI-08 e CFG-01 restano la baseline; il flusso descritto attua l'autorizzazione preventiva individuale e i primi avvisi del timer, senza certificare l'accessibilità completa.

## Gestione nel backend

Aprire il quiz pubblicato dall'editor e seguire «Tempo aggiuntivo quiz». Il collegamento appare solo a chi possiede sul corso entrambi `jlms.enrollments.manage` e `jlms.assessment.correct`; occorre anche `core.manage` sul componente. I permessi nativi ereditano da componente/categorie/corso e sono ricaricati anche su accessi diretti, salvataggi e replay. Non servono permessi sulla banca delle risposte per gestire il tempo.

La vista `quiztime` elenca le iscrizioni della stessa versione del quiz: pagine a cursore da20, nome Joomla, ID e stato. Non espone email, chiavi o risposte. Scegliere l'iscrizione e indicare minuti e motivazione operativa. La motivazione deve essere un riferimento all'autorizzazione, senza diagnosi o altri dati sanitari; testo UTF-8 su una riga, massimo500byte, nessun HTML. Il campo è riservato ai gestori e non viene inviato allo studente.

Minuti aggiuntivi interi0–1440. Zero ripristina il tempo ordinario per i tentativi successivi e conserva lo storico. Un quiz senza limite non accetta minuti aggiuntivi positivi. Durata base massima24ore, tempo aggiuntivo massimo24ore, durata effettiva massima48ore. Non esiste un diritto trasferibile ad altri quiz, versioni, edizioni o iscrizioni.

La modifica vale **solo per i tentativi avviati dopo il salvataggio**. Se esiste un tentativo attivo, il backend mostra la sua durata e deadline UTC e spiega che rimarranno invariate. Non cambia nemmeno un tentativo concluso o scaduto, non lo riapre e non restituisce un tentativo consumato. Per un tentativo ancora aperto e non scaduto usare il flusso distinto [Proroga](QUIZ_DEADLINE_EXTENSION.md), che conserva lo snapshot originario. Non modificare righe SQL o snapshot.

## Snapshot e timer

Prima dell'avvio lo studente vede la durata effettiva e i secondi aggiuntivi autorizzati. All'avvio il server legge l'ultima autorizzazione sotto lo stesso lock dell'iscrizione usato dai salvataggi amministrativi, poi congela `base_time_seconds`, `extra_time_seconds`, `time_adjustment_id` e `time_seconds` nelle regole del tentativo. Il riferimento è tecnico, non include motivazione, diagnosi o autore nel DTO pubblico.

La deadline è `started_at + time_seconds`. Una revoca o un aumento successivi non riscrivono questo snapshot. Il riepilogo distingue la durata del tentativo mostrato da quella applicabile al prossimo tentativo. Nessuna autorizzazione extra scavalca blocco account, revoca del livello Joomla, sospensione, chiusura del corso o gli altri controlli di sicurezza. La chiusura da Scheduler usa la deadline già congelata, indipendentemente da successive modifiche.

Snapshot della0.10 privi dei nuovi campi restano leggibili: il tempo base effettivo è quello già salvato e il tempo aggiuntivo è zero. Regole, date, punteggi e risposte non vengono riscritti durante l'aggiornamento.

Nel browser gli avvisi a cinque minuti (per tentativi di almeno cinque minuti) e a un minuto usano una regione `role=alert`, separata dalla conferma di autosalvataggio. Non si ripetono ogni secondo. Alla ripresa con meno di un minuto residuo appare l'avviso pertinente. Timer/avvisi sono informativi: sospendere JavaScript, cambiare l'orologio o chiudere la scheda non proroga la deadline server. I controlli HTML nativi rimangono utilizzabili da tastiera; il collaudo approfondito con screen reader/browser/zoom e le altre misure di accessibilità restano da completare.

## Servizi, persistenza e integrità

`QuizTimeService`: `editor` per elenco/selezione/storico riservati; `save` per autorizzare o ritirare; `rules` per risolvere l'ultima autorizzazione all'avvio. Nessuna API del sito consente allo studente di impostare il tempo. Task amministrativo `quiztime.save`, POST con sessione Joomla e token CSRF, form XML `quiztime.xml` e campi allowlistati:

| Campo | Regola |
|---|---|
| `course_id`, `lesson_id`, `enrollment_id` | ID decimali positivi; relazioni ricontrollate dal server |
| `revision` | Ultima revisione letta,0 se mai configurato |
| `extra_minutes` | Intero0–1440 |
| `reason` | Motivazione operativa obbligatoria, massimo500byte |
| `mutation_id` | UUIDv4 canonico minuscolo |

Corpi HTTP limitati a16KiB. Revisione obsoleta o UUID riutilizzato con contenuto diverso:409. Ripetere lo stesso UUID/corpo restituisce la stessa autorizzazione senza duplicare storia o log. Il replay ricontrolla i permessi. Fino a10.000 revisioni per coppia; al limite l'operazione è rifiutata, senza troncamento. Il backend mostra le ultime20revisioni; ricerca/esportazione e navigazione completa dello storico sono successive.

Tabella `#__jlms_quiz_time_adjustments`: ID, iscrizione/versione, quiz, revisione, secondi aggiuntivi, motivo, autore, UUID, hash della richiesta e data UTC. I valori sono append-only tramite questo servizio; unicità per iscrizione/quiz/revisione e iscrizione/quiz/UUID, FK iscrizione/versione e quiz. Le relazioni fra quiz e versione sono verificate anche dal servizio. L'installer riprendibile crea/valida la nuova tabella e i due vincoli e conserva tutto a reinstallazione/disinstallazione. La definizione SQL distribuita contiene il vincolo effettivo; per i conteggi dello schema corrente vedere [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

Le scritture bloccano l'iscrizione prima di ricaricare identità e permessi core; avvio e autorizzazione vedono uno stato coerente anche quando attendono un'altra transazione. I valori e i parent sono bindati, il testo è validato e i layout fanno escape contestuale. Le pagine amministrative e studente sono `private, no-store`.

## Action Logs Joomla

`AssessmentAudit` usa il modello nativo `com_actionlogs`, con un adattatore che disabilita le notifiche durante la transazione. Il contesto è `com_jlms.quiztime`; l'installer registra `com_jlms` nell'elenco delle estensioni dei log senza modificare preferenze email, retention o plugin del sito.

Il log nativo conserva autore, oggetto, secondi precedenti/successivi, codice motivo, riferimento alla riga di autorizzazione e correlazione casuale. Non duplica il testo libero del motivo, il nome dello studente o le risposte. Il modello core può ignorare errori d'inserimento: l'adattatore verifica quindi che la registrazione sia presente; se manca, l'intera autorizzazione viene annullata. Non esiste una tabella di audit parallela. La tabella delle autorizzazioni è lo storico della regola didattica necessaria a ricostruire la decisione, distinto dal log operativo.

I log si consultano e si gestiscono con Action Logs Joomla e le sue politiche. La motivazione completa è visibile solo nella vista del corso autorizzata. La configurazione del core può eliminare log secondo la retention del sito; il flusso descritto non aggiunge cancellazione/privacy/export automatici né il plugin Action Logs generale per tutte le operazioni LMS. Nessuna email viene inviata da questa operazione; eventuali notifiche future richiederanno un outbox transazionale separato.

## Verifiche e limiti

La pipeline corrente verifica lettura e conservazione di snapshot/deadline dopo l’aggiornamento. Esegue i test esistenti e controlli su autorizzazione/ritiro, conflitti/replay, separazione dei motivi, revoca ACL e livelli, deadline effettiva, scadenza, log nativo, rollback in caso di audit fallito, form/CSRF HTTP e reinstallazione con storico conservato. Gli avvisi sono verificati con test JavaScript; non equivalgono a certificazione con tecnologie assistive reali.

Restano rivalutazioni dei punteggi, politiche privacy complete, reporting, paginazione completa dello storico e collaudo di carico e accessibilità su browser reali. Il LMS completo e la compatibilità con la patch Joomla corrente non sono ancora certificati per la produzione.

È disponibile l’annullamento tracciato dei tentativi, documentato in [QUIZ_CANCELLATION.md](QUIZ_CANCELLATION.md); non modifica retroattivamente le autorizzazioni al tempo.

Le proroghe autorizzate dei tentativi ancora aperti e non scaduti sono disponibili nel flusso distinto [QUIZ_DEADLINE_EXTENSION.md](QUIZ_DEADLINE_EXTENSION.md). Non modificano lo snapshot originario né riaprono tentativi.
