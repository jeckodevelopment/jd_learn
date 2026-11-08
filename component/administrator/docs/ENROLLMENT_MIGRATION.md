# Migrazione esplicita fra versioni

Dal roster del corso, una riga assegnata a una versione precedente espone **Migrare alla versione corrente**. La pagina prepara un’anteprima per una sola iscrizione; non è un’operazione massiva.

## Anteprima e conferma

Per ogni lezione non quiz completata nella versione origine, selezionare la lezione equivalente nella versione corrente oppure scegliere di non trasferire quel completamento. La conferma richiede un motivo, token CSRF, ACL `jlms.enrollments.migrate`, revisione sorgente e hash dell’anteprima. Se lo stato cambia dopo l’anteprima, occorre ricaricarla.

La migrazione si applica soltanto alla versione corrente pubblicata, alla sua edizione predefinita disponibile e non piena. Viene bloccata se la consegna è sospesa, se l’iscrizione non è migrabile, se esiste già un’iscrizione di destinazione, se ci sono tentativi quiz aperti o se un’equivalenza confligge con una mappatura approvata.

## Dati e garanzie

- Crea una nuova iscrizione alla versione corrente e copia soltanto lo stato `completed` delle lezioni non quiz mappate e confermate.
- Non copia risposte, tentativi, coverage/resume video, sessioni o attestati. L’iscrizione e tutti i progressi di origine restano invariati nello storico; il suo stato diventa `migrated`.
- L’iscrizione di destinazione resta soggetta all’identità e ai livelli di accesso Joomla correnti; la migrazione non assegna gruppi e non concede accessi.
- Salva le equivalenze approvate, un job di risultato singolo, il mapping immutabile e la relazione origine/destinazione. Inserimenti, stato sorgente e Action Log sono atomici. Una richiesta identica restituisce la stessa iscrizione di destinazione.
- L’operazione è limitata a una singola iscrizione, massimo100 mappature e1000 completamenti sorgente; non avvia worker, import CSV o batch.

## Collaudo manuale in Joomla

1. Pubblicare la versione 1 con una lezione testo obbligatoria e avviare un’iscrizione; completare la lezione.
2. Creare e pubblicare la versione 2. Dal roster, aprire la riga della versione 1 e scegliere **Migrare alla versione corrente**.
3. Selezionare l’equivalenza della lezione e aggiornare l’anteprima. Verificare il conteggio e che siano trasferiti soltanto i completamenti indicati.
4. Confermare con un motivo: la riga origine deve risultare `Migrata`, con progressi originali intatti; compare una nuova iscrizione in v2 con il solo completamento mappato.
5. Ripetere la prova scegliendo “non trasferire”; il completamento resta solo sulla fonte. Verificare che tentativi quiz aperti, destinazione già assegnata, edizione non disponibile/piena, accesso negato, hash obsoleto e doppio invio con payload differente non producano scritture parziali.

La suite CI usa un sito Joomla/MySQL disposable e verifica anche il replay idempotente, il rifiuto di mapping estranei, la conservazione byte-per-byte della fonte e la permanenza del risultato dopo l’upgrade del pacchetto.
