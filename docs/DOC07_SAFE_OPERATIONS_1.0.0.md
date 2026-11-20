# DOC-07 — Procedure amministrative sicure (JD Learn 1.0.0)

Questa guida copre le operazioni ad alto rischio presentate dalla dashboard **Configurazione & Strumenti**. Le istruzioni sono in sola lettura e rispettano le ACL Joomla correnti: non concedono autorizzazioni, non avviano processi, non alterano database o code. Il testo localizzato effettivamente mostrato si trova in `component/administrator/language/{it-IT,en-GB}/com_jlms.ini`, con prefisso `COM_JLMS_SAFE_OPERATIONS_`.

## Matrice procedure e verifiche di configurazione

| Operazione | Prerequisito e permessi | Verifica senza effetti | Condizione di errore e risposta sicura | Verifica successiva |
|---|---|---|---|---|
| Storage video locale | Gestore componente; backup; directory privata **esterna al webroot** | Controllare impostazioni storage e stato plugin; verificare utente di servizio e permessi filesystem | Root mancante/pubblica/non scrivibile: non pubblicare media; correggere il percorso senza togliere protezioni | Upload MP4 di prova e richieste GET/HEAD/Range per utente autorizzato; accesso negato a un altro utente |
| Invii e coda mail | `core.manage` del componente e `jlms.audit.view` per la voce diagnostica; consenso e modello validi | Verificare stato scheduler, duplicati, report di audit e configurazione trasporto Joomla | Stato fallito, task duplicati o template invalido: non reinviare manualmente e non manipolare stati SQL; correggere causa prima di un nuovo tentativo autorizzato | Un solo invio idempotente; audit e destinatario effettivo, evitando log con dati personali |
| Attestati PDF | `jlms.certificates.manage` e `core.manage` | Identificare attestato, versione e diagnosi PDF | PDF mancante o non pronto: non alterare record, revisioni o file di storage | Rigenerazione autorizzata tramite POST, conferma e motivazione; identità immutata; download solo dal proprietario |
| Corso: pubblicazione/archiviazione | `core.manage` del componente; dipendenze verificate | Inventario iscrizioni, collegamenti, revisioni e categorie | Riferimenti attivi o stato non coerente: preferire archiviazione e non cancellazione SQL | Dati storici e diritti di accesso conservati; catalogo coerente per ospiti e iscritti |
| Task scheduler | `core.manage` di `com_scheduler` | Dashboard mostra task mancanti, duplicati, disabilitati, falliti, in ritardo, regole invalide e ultimo/next execution UTC | Regole JSON non valide, duplicati, scadenza anomala: evitare run paralleli o email alternative; correggere da Joomla | Una sola istanza per tipo, esecuzione effettiva entro finestra, audit senza duplicati |

### Diagnostica disponibile

La dashboard mostra già gli stati del task Joomla attraverso `SchedulerHealth::assess()`, con chiavi localizzate `SchedulerHealth::guidanceKey()`. Verifica la struttura delle regole JSON, date UTC senza normalizzazione silenziosa e priorità di duplicati, ma **non** prova l'invio SMTP. Lo stato dei provider video deriva da `DashboardProviderStatus::summarize()`: non installato, disabilitato, non registrato, non configurato, pronto; `guidanceKey()` restituisce una prossima azione non mutativa. **Pronto non equivale alla verifica del player reale**.

La nuova sezione `#jlms-admin-safe-operations` rende una checklist procedurale con `data-jlms-safe-operation` per i soli ambiti concessi dalle ACL, senza pulsanti mutativi o link diretti a flussi distruttivi. La presenza di un'istruzione non prova il successo di una configurazione: non sono previsti probe esterni o scritture in background.

### Prove obbligatorie prima dell'accettazione

Eseguire con build candidata e account separati (amministratore autorizzato, docente delegato, gestore senza audit, utente senza permessi), in IT/EN. Registrare per ogni scenario build, tipo di browser, risultato, messaggio visibile, effetti nel DB e nei log, screenshot redatti e correzione; non includere segreti o indirizzi privati nelle evidenze. Verificare anche focus da tastiera e lettura dello `summary` nativo. Per storage, email, attestati e scheduler ripetere stato configurato e stato degradato. Il test statico `tests/doc07-safe-operations.py` impedisce assenza di chiavi localizzate e deriva della protezione ACL, ma **non** sostituisce il collaudo di server, permessi o backup/restore.

## Verifiche effettive di configurazione — DOC-07

La dashboard amministrativa espone anche **Verifiche di configurazione**, visibili esclusivamente a un'identità Joomla corrente autorizzata con `core.manage` su `com_jlms`. I dati provengono soltanto dalla lettura locale della configurazione Scheduler e del registro dei plugin video; nessuna prova esegue un task, invia email, legge token provider o raggiunge endpoint esterni.

| Controllo | Stati problematici rilevati | Risposta operativa |
| --- | --- | --- |
| Task Joomla | Assente, duplicato, disabilitato, regole invalide, fallito, scaduto, prossimo avvio sconosciuto | La tabella mostra il singolo stato con guida IT/EN e rimanda ai normali strumenti Joomla con permessi appropriati, senza ripetere invii |
| Provider video | Plugin non installato, disabilitato, non registrato, prerequisiti mancanti | La guida segnala cosa verificare, senza mostrare riferimenti a segreti o consigliare di aggirare CSP, privacy o ACL |
| Stato nominale | Task operativo o provider configurato | Il messaggio indica esplicitamente che l'esito non equivale a consegna SMTP, riproduzione remota o collaudo di accettazione |
| Stato inatteso | Valore non riconosciuto | Avviso conservativo e verifica amministrativa, mai successo implicito |

Le righe sono costruite da `ConfigurationDiagnostics::summarize()` in `component/administrator/src/Application/ConfigurationDiagnostics.php`, con soli identificatori di traduzione allowlist e severità predefinite. La vista codifica contestualmente tutti i valori. Il test `tests/doc07-configuration-diagnostics.php` verifica i casi limite, inclusi i duplicati e gli stati non riconosciuti; `tests/doc07-safe-operations.py` verifica assenza di azioni mutative, permessi, localizzazione, e istruzioni sicure.

**Limiti:** questa verifica è una diagnosi locale, non uno scanner del filesystem, un invio SMTP di prova, un test di disponibilità remota, una verifica di backup/ripristino o un collaudo manuale della versione 1.0.0. Queste prove restano da registrare separatamente nel QA Testbook.

## Compatibilità e manutenzione

Aggiungere nuove procedure solo aggiornando contestualmente: mapping ACL nel Dashboard HtmlView, markup in dashboard/default.php, stringhe IT/EN, test, questa matrice e `QA_TESTBOOK.md`. Non suggerire come soluzione disabilitare ACL, CSRF, CSP, scanner, controlli su file protetti o accessi. I messaggi diagnostici non devono interpolare chiavi segrete, path sensibili completi o token. Distinguere sempre «controllo read-only», «prova eseguita» e «accettazione manuale».
