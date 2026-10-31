# Quiz per gli studenti — incremento 0.10

La pubblicazione tramite workflow Joomla include ora quiz valutativi ed esercitazioni. Il programma deve avere almeno una lezione obbligatoria. Un quiz obbligatorio deve essere valutativo, con soglia positiva; un'esercitazione è facoltativa. I controlli su corso, lingua, categorie della banca, permessi e revisioni precedono la pubblicazione. La configurazione e il manifest del pool entrano nell'hash della versione pubblicata. Modificare una domanda della banca crea una nuova revisione: non cambia il pool già pubblicato.

## Tentativi e valutazione

Dall'aula aprire il quiz. La lettura iniziale mostra regole e disponibilità senza consumare tentativi né mostrare domande. «Inizia un nuovo tentativo» è esplicito. Numero massimo, pausa, soglia esatta, durata e regola di feedback provengono dalla configurazione pubblicata. Zero tentativi significa illimitati; zero secondi significa assenza di limite temporale.

L'avvio blocca la sola iscrizione, controlla il diritto corrente e crea una riga attiva unica per iscrizione/quiz. Più schede riprendono lo stesso tentativo. Il server sceglie il sottoinsieme dal pool esplicito, mescola domande/opzioni se richiesto e salva ordine, testo, opzioni, punti, correzione e regole in snapshot separati. La ripresa legge questi snapshot, senza effettuare una nuova estrazione. Non viene salvato un seed riutilizzabile: sono gli elementi effettivamente estratti a rendere la ripresa riproducibile.

Ogni risposta è un insieme di identificatori opzione appartenenti alla specifica domanda del tentativo. Singola/vero-falso ammettono al massimo un'opzione; multipla fino a20. Insieme esatto o zero punti, nessun credito parziale o negativo; omissioni zero. La percentuale di superamento confronta interi `punti × 10000 >= massimo × soglia`; il migliore si confronta per prodotti incrociati anche se estrazioni diverse hanno massimi differenti. Il risultato non dipende dall'arrotondamento del display.

Il primo superamento completa la lezione; le esercitazioni completano la lezione facoltativa alla consegna. Il contatore del corso e la prima data di completamento vengono aggiornati una sola volta nella stessa transazione. Tentativi successivi inferiori non eliminano il completamento né il migliore. Nessuna assegnazione di gruppi, attestato o invio email avviene in questo flusso.

## Salvataggio, concorrenza e scadenza

Il client richiede JavaScript, salva ogni20secondi e permette il salvataggio esplicito. La conferma riporta l'ora dell'ultimo salvataggio riconosciuto. Nessuna garanzia di salvataggio alla chiusura improvvisa della pagina: lo studente deve attendere conferma. Le risposte non vengono conservate in localStorage. La consegna chiede conferma ricordando le omissioni.

Avvio e mutazioni usano UUIDv4. Ogni mutazione include revisione del tentativo e hash canonico di operazione/revisione/risposte. Stesso UUID con altro contenuto restituisce409; una revisione obsoleta non sovrascrive l'altra scheda. I retry di rete mantengono UUID e corpo identici. Dopo quattro tentativi di trasporto l'azione rimane incerta e può essere ritentata; un conflitto confermato ferma la scheda e richiede ricaricamento. La consegna definitiva è idempotente. Ogni replay ricontrolla l'accesso prima di leggere il risultato.

La deadline UTC è calcolata e applicata dal server. Il timer visibile usa l'ora server come riferimento ed è soltanto informativo. Al raggiungimento della deadline, la richiesta o il worker chiudono sulle risposte già salvate, ignorando risposte nuove arrivate in ritardo. Chiudere la pagina non sospende il tempo.

La scadenza della finestra didattica dell'iscrizione/edizione vieta nuovi tentativi, ma consente di finire quello già iniziato entro la sua deadline tramite la pagina quiz. Non scavalca inizio futuro, revoca del livello Joomla, blocco account, sospensione iscrizione, chiusura edizione, ritiro del corso/categoria o altra revoca di sicurezza. Anche la lettura dello storico richiede accesso corrente. Il worker può chiudere un tentativo di un account poi bloccato usando esclusivamente risposte già acquisite: non accetta nuove risposte e non concede accessi.

## Feedback e dati protetti

Durante un tentativo aperto non vengono restituiti chiavi, spiegazioni, punti per domanda o hash della correzione. Anche lo storico di un tentativo chiuso nasconde questi dati quando un altro tentativo dello stesso quiz è aperto. Politiche: `never` non rilascia mai correzioni; `pass_or_exhausted` le rilascia dopo un superamento o dopo esaurimento di un limite finito; `after_submit` è ammessa soltanto per esercitazioni. Con tentativi illimitati e nessun superamento il feedback predefinito resta chiuso. Un feedback già visto non può essere reso sconosciuto allo studente: per nuovi test riservati occorre una versione/pool differente.

I template escapano etichette e metadati; prompt e spiegazione sono filtrati dal core prima degli snapshot. Il catalogo non riceve né domande né spiegazioni. Ogni pagina e risposta JSON usa `private, no-store`.

## Servizi e task HTTP

Servizi DI: `QuizSnapshotService` valida/congela il pool; `QuizAttemptService` implementa `state`, `start`, `mutate`, `expireDue`. Tutte le transizioni bloccano iscrizione prima del tentativo; nessuna chiamata esterna avviene nei lock.

| Task `index.php?option=com_jlms&format=json&task=…` | Metodo | Dati oltre alla sessione Joomla |
|---|---|---|
| `quiz.state` | GET | `enrollment_id`, `lesson_id`, `attempt_id` opzionale |
| `quiz.start` | POST | `enrollment_id`, `lesson_id`, `mutation_id` UUIDv4 e token CSRF |
| `quiz.save` | POST | Come start, più `attempt_id`, `revision`, `answers_json` |
| `quiz.submit` | POST | Come save; il server calcola e chiude |

`answers_json` è una mappa ID domanda snapshot → array ID opzioni; array vuoto cancella una selezione, chiavi omesse lasciano le risposte salvate. Identificativi di domanda della banca non sostituiscono quelli dello snapshot. La lettura può materializzare la chiusura di un tentativo scaduto. Errori controllati400/401/403/404/405/409/413/422/429; errori imprevisti generici con correlazione, senza SQL, risposte o dati privati nei log. Questi sono task del sito autenticati con sessione: non sono ancora il plugin Joomla Web Services previsto dalla specifica.

Limiti: pool1–200, somma definizioni4MiB controllata con aggregazione SQL prima del caricamento; singola definizione256KiB; risposte512KiB, corpo HTTP1MiB,50.000 mutazioni per tentativo, storico20voci, worker massimo100tentativi. Il runtime non interroga l'intera banca per estrarre. Questi limiti riducono il lavoro di una richiesta, non costituiscono certificazione di carico300/500studenti.

## Plugin base `task/jlms`

Il pacchetto distribuisce anche `plg_task_jlms`, plugin Joomla Scheduler con routine `jlms.quiz.expire`. È inattivo alla prima installazione: abilitarlo in Sistema → Plugin, quindi creare in Attività pianificate una voce «JD Learn: chiudi quiz scaduti». Parametro `limit`:1–100, default100. Pianificare ad esempio ogni minuto. Usare l'esecuzione CLI/cron nativa Joomla per siti che richiedono elaborazione senza visitatori; il lazy scheduler dipende dalle visite. Nessun job del sito viene creato o modificato automaticamente dall'installer.

La routine seleziona per indice stato/deadline/ID, apre una transazione per tentativo e continua dopo un errore registrando soltanto una correlazione casuale. Il log dell'attività contiene conteggi, senza risposte o identità. Restituisce lo stato nativo OK oppure KNOCKOUT in presenza di errori. Se c'è altro lavoro lo segnala nel log e la successiva esecuzione processa il seguito; dimensionare frequenza/limite sulla coda. Esecuzioni duplicate non duplicano voti o completamento.

Anche con attività inattiva la prima richiesta successiva applica la scadenza, ma non viene promessa una chiusura in background finché il gestore non configura l'esecuzione. La chiusura può materializzarsi dopo la deadline: la data didattica e le risposte ammesse restano quelle della deadline.

## Limiti e verifica

Restano tempi aggiuntivi individuali, rivalutazioni e invalidazioni tracciate, audit/actionlog/privacy, storico senza entitlement, reporting, paginazione completa dello storico, disponibilità specifica del quiz distinta dalla finestra iscrizione/edizione, plugin Web Services, navigazione/accessibilità approfondita, collaudo browser reale e carico. Nessun proctoring.

La pipeline verifica dominio, database e installazione Joomla6.0.0 con PHP8.3/8.4 e MySQL8.4/MariaDB10.6; esercita avvio/ripresa, CAS/replay, scadenza con orologio controllato, feedback, revoca corrente, due processi concorrenti, dispatch nativo Scheduler, HTTP con login Joomla e upgrade con snapshot/risultati conservati. I test del trasporto JavaScript usano doubles di rete: non equivalgono a collaudo browser o test di carico. I risultati effettivi sono quelli delle Actions della PR, non l'esistenza dei test. La patch Joomla corrente resta da certificare prima della produzione.
