# Materiali protetti

Il plugin Scheduler `plg_task_jlms` espone `jlms.materials.cleanup`. Dopo l’upgrade, verificare che il plugin **Task - JD Learn** sia abilitato, quindi creare da Sistema → Attività pianificate una voce “JD Learn: pulisci i file materiali orfani”. È possibile pianificarla, per esempio, una volta al giorno. Per siti senza traffico, usare l’esecuzione CLI/cron del Joomla Scheduler; l’attività non viene creata automaticamente.

Ogni esecuzione esamina al massimo 100 record (parametro `limit`, intervallo 1–100). Sono eleggibili solo asset di storage locale più vecchi del periodo configurato in **Opzioni → JD Learn → Materiali → Conservazione dei file orfani** (default 24 ore, intervallo 24–8760 ore) e non referenziati da lezioni, allegati, template/documenti attestato o risultati dei job. Il database blocca le righe candidate durante il controllo e la rimozione; il blob viene eliminato solo dopo la validazione della chiave privata. Errori su file o riferimenti impediscono la cancellazione del record. Le sostituzioni dei materiali restano immutabili: gli asset ancora referenziati non sono raccolti.

Nella bozza è possibile collegare lo stesso materiale a un altro ambito del corso (corso o lezione) senza caricare una seconda copia. Il collegamento condivide l'asset privato immutabile ma mantiene titolo, descrizione e disponibilità del materiale sorgente; una duplicazione dello stesso asset nello stesso ambito viene rifiutata. La destinazione deve appartenere alla medesima versione in bozza. Il collegamento non modifica corsi già pubblicati e il cleanup conserva l'asset finché almeno un allegato lo usa.

La routine interviene solo su asset orfani registrati nel database. La soglia configurabile è un'attesa minima prima della rimozione, non una garanzia di cancellazione all'ora esatta: dipende dall'esecuzione pianificata e dal limite per batch. Non scandisce o cancella file sconosciuti nella directory di storage, non rimuove copie già scaricate dagli utenti e non sostituisce il backup.

## Collaudo Joomla

Nel sito di prova caricare un materiale in una lezione della bozza e usare **Collega materiale esistente** per renderlo disponibile anche nei materiali del corso o in un'altra lezione. Verificare che il titolo/file siano gli stessi, che il collegamento duplicato nello stesso ambito sia rifiutato, e che rimuovere un collegamento non cancelli l'altro. Sostituire poi il file originale e verificare che i collegamenti esistenti puntino all'asset precedente e non alterino versioni pubblicate. Creare un’attività pianificata con limite `100` e avviarla manualmente: asset ancora referenziati devono restare disponibili. Il CI verifica il percorso di pulizia creando un asset non referenziato oltre la soglia e invocando la routine nativa Scheduler.


## Scansione antivirus ClamAV

Se l'amministratore rende obbligatoria la scansione, JD Learn invia il blob al demone ClamAV configurato tramite socket Unix locale usando il protocollo INSTREAM. Solo l'esito `clean` porta il file in stato pronto; una firma rilevata lo rifiuta e un socket non disponibile lascia l'asset in quarantena. L'esito viene registrato con engine e risultato, senza salvare il contenuto del file nei log. Il test CI usa ClamAV reale e il file di prova EICAR; non è una certificazione antivirus del server di produzione, che deve mantenere aggiornate le definizioni.

Per il collaudo sul sito di prova, abilita **Scansione antivirus obbligatoria**, imposta il socket Unix locale di ClamAV, carica un file innocuo e verifica l'esito pulito. Usa poi il file di test EICAR solo su un sito/dispositivo usa-e-getta: il sistema deve rifiutarlo, impedirne download e pubblicazione. Arresta il demone e prova un altro caricamento: deve restare in quarantena e non essere fruibile.
