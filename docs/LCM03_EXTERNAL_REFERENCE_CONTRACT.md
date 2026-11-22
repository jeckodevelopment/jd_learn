# LCM-03 — Contratto delle dipendenze esterne dei percorsi

Il percorso può essere archiviato o cestinato senza modificare lo stato dei corsi che contiene. La cancellazione fisica rimane un'operazione distinta, disponibile soltanto per un percorso **cestinato e senza relazioni interne** e con permesso `core.delete` sull'asset.

## Integrazioni supportate dalla protezione automatica

Nel database MySQL/MariaDB, durante la stessa transazione della purga JD Learn interroga `information_schema.KEY_COLUMN_USAGE` per individuare tutte le chiavi esterne che fanno riferimento alla colonna `id` della tabella del percorso. Prima di cancellarlo controlla le righe delle tabelle referenzianti. Se ne esiste anche una sola, **la purga è negata**, anche se la FK è configurata con `ON DELETE CASCADE`. Il controllo include la tabella delle relazioni corso-percorso; restano distinti i controlli delle associazioni multilingua Joomla.

Una mancata lettura dei metadati del database o un errore SQL impedisce l'eliminazione (fail-closed). La chiave esterna va quindi installata con lo schema dell'integrazione e deve referenziare la colonna `id` del percorso. Le integrazioni possono preferire `ON DELETE RESTRICT`, che resta un secondo livello di protezione.

## Esclusioni esplicite

Nessun software può individuare in modo affidabile riferimenti opachi che un'estensione memorizza senza una FK (JSON, testo libero, API esterne, archivi fuori dal database) se quell'estensione non dichiara una relazione. **Questi riferimenti non sono inclusi nel contratto della versione 1.0.0**, non sono cancellati da JD Learn e richiedono audit e collaudo sulle installazioni effettive. Un integratore che deve preservare uno storico deve dichiarare la dipendenza con una FK di database oppure evitare la purga.

## Evidenze e controlli

`tests/joomla-pathways.php` crea una tabella di integrazione di prova con FK `ON DELETE CASCADE`, inserisce una relazione storica verso un percorso cestinato e verifica che la purga sia rifiutata e che entrambi i record restino presenti. Dopo la rimozione esplicita della fixture, la suite esistente prova la cancellazione del solo percorso senza relazioni. Le prove girano esclusivamente su un Joomla disposable.

**Accettazione manuale:** separata. Verificare permessi, aggiornamento e ciascuna estensione installata; non attribuire copertura alle integrazioni senza FK o riferimenti dichiarati.
