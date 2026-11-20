## Diagnostica sicura dei messaggi falliti e codice di uscita 5

Il codice di uscita **5** dello Scheduler segnala l'esito negativo del task: da solo non individua la causa dell'invio. In JD Learn il task ritorna esito negativo se un invio o un'attività di preparazione fallisce; un messaggio in stato **Fallito** potrebbe aver raggiunto il limite di tentativi oppure essere stato scartato per un modello non valido.

La vista **Coda email** mostra ora, soltanto ai ruoli che hanno `core.manage`, la categoria diagnostica per record `retry` e `failed` quando il codice persistito è riconosciuto:
- **Modello email mancante / variabili non disponibili**: controllare i modelli mail Joomla e i placeholder canonici;
- **Invio non riuscito / trasporto**: controllare le impostazioni email globali Joomla e i log SMTP.

L'errore di trasporto, anche storico, non viene mai stampato direttamente: la coda usa una whitelist di due messaggi generici per non divulgare indirizzi o dati del server. Errori non riconosciuti non vengono mostrati. Questa diagnosi non prova l'accettazione SMTP, né riavvia messaggi con stato `sending` o `failed`.

**Collaudo da eseguire:** con SMTP funzionante creare un nuovo messaggio; poi simulare un rifiuto SMTP e un modello senza variabili valide. Verificare i due messaggi localizzati IT/EN e l'assenza di dettagli SMTP sensibili. Controllare che utenti non autorizzati non possano aprire Coda email e che gli stati esistenti non vengano alterati. Per i precedenti `sending` riconciliare come `retry` solo dopo conferma della mancata accettazione SMTP e se i tentativi sono inferiori a cinque; correggere prima la causa del fallimento.

## STAB-MAIL / blocco invii in stato «invio» — gestione errori transport (15/11/2026)

Nel dispatcher `MailQueue::dispatch()`, quando il mailer Joomla falliva, la gestione del retry tentava di eseguire `DatabaseQuery::bind(':error', <espressione ternaria>)`. Poiché Joomla passa il parametro per riferimento, la chiamata poteva generare un errore PHP **prima del salvataggio dello stato**. Il record rimaneva così `sending`, mentre il task poteva risultare fallito: lo stato non dimostrava l'accettazione SMTP. Il valore diagnostico sanitizzato viene ora assegnato prima a una variabile `$safeError` e poi collegato alla query per riferimento. Nessun messaggio SMTP, indirizzo o dettaglio del server viene conservato nell'errore pubblico.

**Collaudo Joomla/SMTP:** (1) con SMTP di prova configurato correttamente, creare un messaggio nuovo e verificare invio e stato `sent`; (2) simulare un rifiuto/errore SMTP *prima dell'accettazione del messaggio* e controllare che il task segnali il fallimento, con coda `retry` e `claimed_at` nullo oppure `failed` al quinto tentativo; (3) ripristinare SMTP e verificare la consegna senza duplicati; (4) verificare un template non valido: passaggio diretto a `failed`, errore generico senza dati sensibili; (5) verificare il percorso di riconciliazione manuale di un messaggio `sending` vecchio di almeno 15 minuti. **Non ritentare automaticamente le righe già in `sending`: l'accettazione da parte del server SMTP potrebbe essere avvenuta prima del crash.** Solo dopo riscontro esterno dell'effettiva mancata accettazione è possibile scegliere «Ritenta» con nota e conferma. Mai marcare «Inviato» senza evidenza. (6) Distinguere stato del job Joomla, stato del record mail e reale accettazione dal server SMTP: una risposta task «OK» con zero messaggi elaborati non certifica consegna.

CI strutturale e test di integrazione non sostituiscono i punti di verifica SMTP end-to-end sopra elencati.

# Invio email — attivazione attività pianificata (1.0.0)

JD Learn registra automaticamente il task Joomla `jlms.mail.dispatch` dal postflight del plugin `task/jlms` (quando il componente è disponibile). Il task viene creato **disabilitato**, con intervallo predefinito **5 minuti**. L'amministratore lo abilita in **Sistema → Attività pianificate** e configura il trasporto email in Joomla. L'attivazione non avviene durante l'installazione.

Il controllo è idempotente per *tipo di task* (non per titolo): se ne esiste almeno uno, installazioni e aggiornamenti **non modificano** frequenza, stato, parametri, titolo o cron. Le attività eventualmente duplicate vengono segnalate, non rimosse automaticamente. La disinstallazione del componente conserva i dati didattici; la configurazione scheduler esistente non viene cancellata automaticamente, per evitare rimozioni di attività personalizzate.

La dashboard espone anche un pannello Stato delle attività pianificate JD Learn per tutti e tre i tipi (quiz, materiali, email), con ultima e prossima esecuzione, duplicati e ritardi superiori a 10 minuti rispetto alla scadenza calcolata da Joomla Scheduler. Il pannello è in sola lettura, visibile solo a chi dispone di core.manage sul componente. Non attiva cron, non cambia configurazione e non garantisce invii SMTP. L'avviso preesistente nella sezione Comunicazioni segnala esclusivamente l'invio email: viene mostrato un piccolo avviso con link allo Scheduler Joomla se il task è assente, disabilitato, se il plugin è disabilitato, l'ultima esecuzione è fallita o ci sono duplicati. La vista Coda email espone stato, frequenza se espressa in minuti, ultima esecuzione e link alle attività native. Un task abilitato ma mai eseguito viene segnalato come in attesa nella Coda email, **senza allarme nella dashboard**: non è automaticamente un guasto.

Il controllo descrive l'ultima esecuzione, **non** dimostra consegna SMTP né disponibilità del runner Joomla (CLI/cron o Lazy Scheduler). La configurazione del trasporto e dell'esecuzione è esterna a JD Learn.

## Collaudo manuale obbligatorio

1. Installare il pacchetto completo su Joomla 6 pulito: verificare un solo task `jlms.mail.dispatch` disabilitato, frequenza 5 minuti, senza invii spontanei.
2. Abilitare il plugin Task - JD Learn e il task; verificare assenza di avviso in dashboard prima del primo run, stato «in attesa» nella Coda email.
3. Eseguire il job con SMTP di prova e messaggio in coda: ultima esecuzione riuscita, messaggio inviato una volta; verificare che il mailer Joomla e i retry funzionino.
4. Disabilitare task e plugin separatamente; verificare avviso contestuale, link nativo e corretta differenziazione in Coda email.
5. Provocare un errore del task: stato di errore nell'ultima esecuzione e avviso; ripristinare SMTP e verificare recovery.
6. Modificare la frequenza a 10 minuti, disabilitare il task e aggiornare JD Learn: nessuna alterazione dell'attività o duplicazione.
7. Creare manualmente un secondo task dello stesso tipo: segnalazione duplicati senza cancellazioni, invii idempotenti.
8. Verificare dashboard e Coda email con ACL diversi, lingue IT/EN, mobile e accessibilità; solo ruoli autorizzati vedono i dettagli operativi.
9. Simulare installazione parziale senza componente e disinstallazione/reinstallazione; verificare che il task esistente venga preservato.
10. Verificare con un cron Joomla realmente attivo la differenza tra task «abilitato» e task «eseguito».

## Integrazione confermata

PR [#424](https://github.com/jeckodevelopment/jd_learn/pull/424) integrata in `main` il 15 novembre 2026, commit `5e61c0a32828d5b4387faf12ca2d1efdc964288a`. CI [43725870964](https://github.com/jeckodevelopment/jd_learn/actions/runs/43725870964) **4/4 verde**, controllo CSV [43725870834](https://github.com/jeckodevelopment/jd_learn/actions/runs/43725870834) verde. Pacchetto installabile `pkg_jlms-0.57.7-dev.zip` nell'[artefatto 14166561507](https://github.com/jeckodevelopment/jd_learn/actions/runs/43725870964/artifacts/14166561507). **Stima globale 79,33%, email 70%, delta 0** (hardening operativo, non nuova copertura funzionale). Accettazione manuale Joomla/SMTP da eseguire.

**Gate**: CI e verifiche strutturali non equivalgono al collaudo Joomla/SMTP. Non attribuire copertura accettata finché gli scenari non sono eseguiti sul sito di prova.


## Diagnosi email completamento video e modello iscrizione (17/11/2026)

**MAIL P0 — completamento video (PR in corso, 17/11/2026):** la notifica `com_jlms.course.completed` generata da `VideoProgressService` non forniva `COMPLETION_DATE` e `DASHBOARD_URL`, nonostante il modello standard Joomla li richieda. Corretto il produttore affinché fornisca entrambi i campi (data reale del completamento persistita e URL autenticato della dashboard), con test regressione. Il messaggio di iscrizione `com_jlms.enrollment.confirmed` presente nello screenshot è un evento distinto: il relativo produttore fornisce già i tag canonici, e la causa va verificata sul modello effettivo `#__mail_templates` personalizzato dell'installazione, evitando di sovrascrivere template amministrativi o reintrodurre alias. L'emissione attestati dipende dalle regole immutabili della versione pubblicata, dal consenso configurazione `certificate_enabled` globale/per corso e dalla presenza di un modello attestato attivo; verificare le precondizioni prima di parlare di regressione. Avanzamento 79,56% invariato.

- L'errore «Modello email mancante o variabili non disponibili» in Coda email è una categoria aggregata: non distingue un modello inesistente da un token non presente nei dati. Verificare i modelli Joomla effettivamente salvati, incluse eventuali varianti di lingua precedenti ai tag canonici. Le modifiche ai template degli amministratori non sono mai sovrascritte dall'aggiornamento del componente.
- Il messaggio fallito rimane `failed` per evitare invii inattesi. Dopo correzione del modello, testare con un **nuovo evento**, verificando il Task JD Learn e la configurazione SMTP.


## Diagnostica esatta del modello email in Coda — issue #547

**MAIL-10 — issue #547 (PR #548, test sito Joomla pendente):** nella Coda email amministrativa, per messaggi `failed`/`retry` classificati come errore modello, JD Learn ora rilegge in sola lettura il template Joomla effettivo della lingua della riga e mostra se è assente oppure i **nomi** dei tag canonici non disponibili (massimo 12). Non espone indirizzi, valori delle variabili, dati SMTP, token o eccezioni. Non riscrive i modelli personalizzati, non introduce alias legacy, non modifica lo stato della coda né riavvia gli invii. Verificare anche le varianti lingua e correggere esplicitamente i template obsoleti.

I messaggi storici `failed` mantengono la categoria di errore registrata e permettono all'amministratore autorizzato di identificare i nomi dei segnaposto non disponibili, confrontandoli con i modelli nativi Joomla. Correggere il modello selezionato per la lingua del messaggio senza introdurre sinonimi né sovrascrivere il contenuto.


## Post-merge PR #545 e #548 — collaudo SMTP pendente

**PR #545 (mail completamento video)** integrata al commit `f0415c45dd512797c4f5882d97d430c58bdc29b0`; CI 4/4 verde [run 44045664274](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274), gate CSV [run 44045664187](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664187), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274/artifacts/14311024216). Token `COMPLETION_DATE` e `DASHBOARD_URL` ora disponibili nell'evento di completamento generato da `VideoProgressService`. Collaudo Joomla/SMTP ancora da eseguire.

**PR #548 (diagnostica template mail, issue #547)** integrata al commit `12edaa91c2ba95e0ea159d897b6f9cabcb9d304e`; CI 4/4 verde [run 44046787217](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217), gate CSV [run 44046787244](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787244), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217/artifacts/14311582918). Diagnostica admin dei token non risolti/assenza modello senza esporre contenuto delle email. Il modello `com_jlms.enrollment.confirmed` fallito in installazione Joomla di prova richiede controllo e correzione nell'installazione; nessun invio storico riaccodato automaticamente.


## Ritentativo sicuro di email già fallite per errore modello — issue #547

**MAIL-10 / issue #547 — retry manuale controllato (PR #551, CI e collaudo Joomla pendenti):** le sole notifiche `com_jlms.enrollment.confirmed` e `com_jlms.course.completed` in stato `failed` con categoria errore di validazione modello **prima dell'SMTP** possono essere riportate a `retry` dall'amministratore autorizzato, con conferma esplicita e nota audit (10–1000 caratteri), dopo la riparazione del template Joomla. Prima del cambio stato il server controlla template corrente, lingua, dati, nomi dei token e recapito; aggiornamento SQL atomicamente condizionato a stato/errore/tentativi. Non ritentare errori di trasporto, invii incerti, `sending` non riconciliati, record esauriti o altre categorie. Il task di invio Joomla resta l'unico mittente, nessun invio immediato dal pulsante. Avanzamento 79,56% invariato.

**Operativamente:** correggere prima il template in Sistema → Modelli email, verificare l'iscrizione ancora valida e usare il comando solo sul record `failed` pertinente. Non equivale ad un invio immediato; abilitare il task `jlms.mail.dispatch` e SMTP. Nessun retry automatico di errori SMTP o consegne dall'esito incerto.


## PR #551 post-merge — CI, pacchetto e recupero del messaggio fallito

**PR #551 integrata — issue #547:** merge `2e9a65ab8d9044f9502950514c0a24419d04d03b`, CI completa 4/4 verde [run 44049557136](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136), gate CSV verde [run 44049557129](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557129); [pacchetto installabile JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136/artifacts/14312389981), digest archivio Actions `sha256:01ebeb70fae56e88caa1cbe54ae5d4f6eb7d408e824b8a0a94d7167dfa32da25` (non checksum interno dello ZIP Joomla). Il ritentativo esplicito è possibile solo dopo riparazione di template per iscrizione e completamento con errore validato pre-SMTP, nota e conferma, mai per trasporto incerto; collaudo Joomla/SMTP e correzione reale del modello Rigoletto rimangono pendenti. Avanzamento globale 79,56% invariato.


## SEC-P1 — permesso esplicito per la coda email globale (20/11/2026)

Dalla versione `0.64.9-dev`, il solo `core.manage` sul componente **non basta** per vedere la coda globale o riconciliare messaggi. Serve anche il permesso già disponibile `jlms.audit.view` su `com_jlms`. Un operatore delegato di un singolo corso non deve poter consultare email mascherate, diagnostici e note appartenenti ad altri corsi. Il collegamento dashboard è nascosto ai non autorizzati; i Super User continuano a poter gestire i messaggi. Agli eventuali amministratori operativi non Super User attribuire il diritto esplicitamente da **Configurazione → Permessi** in Joomla. Questa modifica non apre nuove possibilità di invio, non altera SMTP o il comportamento di retry e non sostituisce il collaudo delle riconciliazioni in ambiente di prova.
