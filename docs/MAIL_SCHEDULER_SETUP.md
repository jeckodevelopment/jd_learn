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

**Gate**: CI e verifiche strutturali non equivalgono al collaudo Joomla/SMTP. Non attribuire copertura accettata finché gli scenari non sono eseguiti sul sito di prova.
