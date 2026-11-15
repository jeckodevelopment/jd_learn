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

La dashboard mostra esclusivamente nella sezione Comunicazioni un piccolo avviso con link allo Scheduler Joomla se il task è assente, disabilitato, se il plugin è disabilitato, l'ultima esecuzione è fallita o ci sono duplicati. La vista Coda email espone stato, frequenza se espressa in minuti, ultima esecuzione e link alle attività native. Un task abilitato ma mai eseguito viene segnalato come in attesa nella Coda email, **senza allarme nella dashboard**: non è automaticamente un guasto.

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
