# Piano migrazioni 1.0

Lo schema è una baseline da integrare nell’installer del componente; non esiste una release software 1.0 precedente. Non eseguire direttamente su un sito in produzione. Il placeholder `#__` viene sostituito da Joomla. Non creare copie delle tabelle core.

1. Preflight: compatibilità CMS/PHP/DB, estensioni PHP, charset, privilegi CREATE/ALTER/INDEX, spazio e storage esterno al webroot, manutenzione installazione. Impedire worker e pubblicazioni durante la migrazione.
2. Installazione nuova: creare le tabelle, poi FK dopo entrambe le estremità; registrare categorie root/asset/content type/workflow/mail template tramite API Joomla. Nessuna utenza, gruppo commerciale o livello di abbonamento preconfigurato.
3. Installer Joomla: dichiarare `sql/install.mysql.utf8mb4.sql` nel manifest e directory `sql/updates/mysql`. Baseline `1.0.0.sql` priva di DDL aggiuntivo; lo stato schema è gestito dal meccanismo core, separato dalla versione documento.
4. Le DDL MySQL/MariaDB non sono un’unica transazione reversibile. In caso di errore interrompere l’attivazione del componente. Un recovery controlla colonne/indici/FK in information_schema e applica soltanto i passi mancanti; non ignorare errori generici. Gli IF NOT EXISTS delle CREATE non dimostrano compatibilità di una tabella già presente.
5. Aggiornamenti: file SQL ordinati numericamente e mai riscritti dopo pubblicazione; expand (nullable/nuova tabella), backfill a batch riprendibile via script/CLI con checkpoint, verifica conteggi/hash, switch applicativo, contract in release successiva. Vietati DROP/CASCADE e backfill massivi in una richiesta HTTP.
6. Installare libreria contratti, componente, plugin di integrazione e provider. L’installer non chiama API remote e non abilita provider incompleti. Il postflight diagnostica dipendenze e registra gli asset; il sito non deve essere esposto con uno schema parziale.
7. Collaudo obbligatorio: nuova installazione e ripresa di errore dopo ogni fase, upgrade diretto da ogni release supportata, prefissi non standard, entrambi i motori, ripristino del backup con asset e segreti, disinstallazione conservativa e reinstallazione. Non manipolare manualmente #__schemas per simulare un upgrade riuscito.
8. Disinstallazione conserva dati e file per default. La cancellazione irreversibile è un comando amministrativo separato con autorizzazione, riepilogo dei dati e conferma esplicita. Rimuovere prima riferimenti core tramite API e FK in ordine inverso, poi dati e solo file non referenziati. Non eseguire un DROP automatico al semplice uninstall del package.

## Contratti dei dati

Ogni `_json` è LONGTEXT UTF-8 con serializzazione JSON canonica, validazione applicativa e limite documentato. Nessun uso di JSON_TABLE, indici JSON, generated column o partial index. Revisioni >=1, ordinamenti >=0, punti interi positivi, percentuali in basis point (7000=70%). Gli enum VARCHAR sono chiusi dal validatore applicativo; le FK proteggono l’integrità strutturale, non l’ACL. `NULL` è assenza, non data zero.

FK esclusivamente fra tabelle del componente, RESTRICT. Le relazioni a utenti/categorie/asset ACL/viewlevels sono verificate via API core; la loro cancellazione produce blocco amministrativo o anonimizzazione, mai cascade implicito. Il puntatore courses.current_version_id deve appartenere allo stesso corso (FK composita). Le incoerenze semantiche ulteriori, ad esempio attempts.quiz_id nella stessa versione dell’iscrizione, sono validate e testate nel servizio dentro la transazione.

Le colonne `user_id` nullable consentono anonimizzazione. subject_key è UUID casuale stabile per l’utente all’interno del dominio LMS, non hash dell’email; per utenti attivi viene riusato interrogando le iscrizioni esistenti sotto il lock applicativo dell’utente. Per una unicità robusta occorre la tabella subjects definita nello schema e il suo vincolo univoco user_id. Nessuna impersonificazione usando subject_key ricevuto dal client.
