# Materiali protetti

Il plugin Scheduler `plg_task_jlms` espone `jlms.materials.cleanup`. Dopo l’upgrade, verificare che il plugin **Task - JD Learn** sia abilitato, quindi creare da Sistema → Attività pianificate una voce “JD Learn: pulisci i file materiali orfani”. È possibile pianificarla, per esempio, una volta al giorno. Per siti senza traffico, usare l’esecuzione CLI/cron del Joomla Scheduler; l’attività non viene creata automaticamente.

Ogni esecuzione esamina al massimo 100 record (parametro `limit`, intervallo 1–100). Sono eleggibili solo asset di storage locale creati da oltre 24 ore e non referenziati da lezioni, allegati, template/documenti attestato o risultati dei job. Il database blocca le righe candidate durante il controllo e la rimozione; il blob viene eliminato solo dopo la validazione della chiave privata. Errori su file o riferimenti impediscono la cancellazione del record. Le sostituzioni dei materiali restano immutabili: gli asset ancora referenziati non sono raccolti.

La routine interviene solo su asset orfani registrati nel database. Non scandisce o cancella file sconosciuti nella directory di storage, non rimuove copie già scaricate dagli utenti e non sostituisce il backup né una policy di conservazione configurata dal sito.

## Collaudo Joomla

Nel sito di prova caricare e sostituire un materiale e verificare subito che il file sostituito non venga sovrascritto. Creare un’attività pianificata con limite `100` e avviarla manualmente: file e record attivi devono restare invariati. Il CI verifica il percorso di pulizia creando un asset non referenziato oltre la soglia e invocando la routine nativa Scheduler.
