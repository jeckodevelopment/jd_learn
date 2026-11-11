# Materiali protetti

Il plugin Scheduler `plg_task_jlms` espone `jlms.materials.cleanup`. Dopo l’upgrade, verificare che il plugin **Task - JD Learn** sia abilitato, quindi creare da Sistema → Attività pianificate una voce “JD Learn: pulisci i file materiali orfani”. È possibile pianificarla, per esempio, una volta al giorno. Per siti senza traffico, usare l’esecuzione CLI/cron del Joomla Scheduler; l’attività non viene creata automaticamente.

Ogni esecuzione esamina al massimo 100 record (parametro `limit`, intervallo 1–100). Sono eleggibili solo asset di storage locale creati da oltre 24 ore e non referenziati da lezioni, allegati, template/documenti attestato o risultati dei job. Il database blocca le righe candidate durante il controllo e la rimozione; il blob viene eliminato solo dopo la validazione della chiave privata. Errori su file o riferimenti impediscono la cancellazione del record. Le sostituzioni dei materiali restano immutabili: gli asset ancora referenziati non sono raccolti.

Nella bozza è possibile collegare lo stesso materiale a un altro ambito del corso (corso o lezione) senza caricare una seconda copia. Il collegamento condivide l'asset privato immutabile ma mantiene titolo, descrizione e disponibilità del materiale sorgente; una duplicazione dello stesso asset nello stesso ambito viene rifiutata. La destinazione deve appartenere alla medesima versione in bozza. Il collegamento non modifica corsi già pubblicati e il cleanup conserva l'asset finché almeno un allegato lo usa.

La routine interviene solo su asset orfani registrati nel database. Non scandisce o cancella file sconosciuti nella directory di storage, non rimuove copie già scaricate dagli utenti e non sostituisce il backup né una policy di conservazione configurata dal sito.

## Collaudo Joomla

Nel sito di prova caricare un materiale in una lezione della bozza e usare **Collega materiale esistente** per renderlo disponibile anche nei materiali del corso o in un'altra lezione. Verificare che il titolo/file siano gli stessi, che il collegamento duplicato nello stesso ambito sia rifiutato, e che rimuovere un collegamento non cancelli l'altro. Sostituire poi il file originale e verificare che i collegamenti esistenti puntino all'asset precedente e non alterino versioni pubblicate. Creare un’attività pianificata con limite `100` e avviarla manualmente: asset ancora referenziati devono restare disponibili. Il CI verifica il percorso di pulizia creando un asset non referenziato oltre la soglia e invocando la routine nativa Scheduler.
