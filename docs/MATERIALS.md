# Materiali protetti

## Consegna Nginx per file allegati

La consegna PHP esistente resta disponibile quando è abilitata esplicitamente. Per delegare a Nginx la trasmissione dei file allegati, configurare una location interna che punti alla stessa directory privata impostata in `material_storage_root`:

```nginx
location /jlms-private/ {
    internal;
    alias /percorso/assoluto/materiali/;
}
```

Nelle opzioni di JD Learn abilitare **Consegna interna Nginx dei materiali**, impostare `material_internal_prefix` a `/jlms-private/` e lasciare disabilitata la consegna PHP se non serve come alternativa operativa. Prima di confermare la configurazione, verificare che una richiesta HTTP diretta a `/jlms-private/<chiave>.blob` restituisca 404. La chiave viene generata dal componente e la location deve restare marcata `internal`.

Dopo aver verificato identità, iscrizione, corso, versione, disponibilità e stato della scansione, JD Learn valida chiave, dimensione e SHA-256 dell'asset e invia `X-Accel-Redirect`. Range e trasferimento del corpo sono gestiti da Nginx. Non viene emesso un URL pubblico e non è previsto fallback automatico: se prefisso o conferma non sono validi, la richiesta fallisce chiusa. Il PHP continua a verificare l'hash prima dell'inoltro; mantenere quindi privata e non modificabile dall'utente web la directory e i blob.

Il plugin Scheduler `plg_task_jlms` espone `jlms.materials.cleanup`. Dopo l’upgrade, verificare che il plugin **Task - JD Learn** sia abilitato, quindi creare da Sistema → Attività pianificate una voce “JD Learn: pulisci i file materiali orfani”. È possibile pianificarla, per esempio, una volta al giorno. Per siti senza traffico, usare l’esecuzione CLI/cron del Joomla Scheduler; l’attività non viene creata automaticamente.

Ogni esecuzione esamina al massimo 100 record (parametro `limit`, intervallo 1–100). Sono eleggibili solo asset di storage locale creati da oltre 24 ore e non referenziati da lezioni, allegati, template/documenti attestato o risultati dei job. Il database blocca le righe candidate durante il controllo e la rimozione; il blob viene eliminato solo dopo la validazione della chiave privata. Errori su file o riferimenti impediscono la cancellazione del record. Le sostituzioni dei materiali restano immutabili: gli asset ancora referenziati non sono raccolti.

Nella bozza è possibile collegare lo stesso materiale a un altro ambito del corso (corso o lezione) senza caricare una seconda copia. Il collegamento condivide l'asset privato immutabile ma mantiene titolo, descrizione e disponibilità del materiale sorgente; una duplicazione dello stesso asset nello stesso ambito viene rifiutata. La destinazione deve appartenere alla medesima versione in bozza. Il collegamento non modifica corsi già pubblicati e il cleanup conserva l'asset finché almeno un allegato lo usa.

La routine interviene solo su asset orfani registrati nel database. Non scandisce o cancella file sconosciuti nella directory di storage, non rimuove copie già scaricate dagli utenti e non sostituisce il backup né una policy di conservazione configurata dal sito.

## Collaudo Joomla

Nel sito di prova caricare un materiale in una lezione della bozza e usare **Collega materiale esistente** per renderlo disponibile anche nei materiali del corso o in un'altra lezione. Verificare che il titolo/file siano gli stessi, che il collegamento duplicato nello stesso ambito sia rifiutato, e che rimuovere un collegamento non cancelli l'altro. Sostituire poi il file originale e verificare che i collegamenti esistenti puntino all'asset precedente e non alterino versioni pubblicate. Creare un’attività pianificata con limite `100` e avviarla manualmente: asset ancora referenziati devono restare disponibili. Il CI verifica il percorso di pulizia creando un asset non referenziato oltre la soglia e invocando la routine nativa Scheduler.
