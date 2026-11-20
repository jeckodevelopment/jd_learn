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

## Compatibilità e manutenzione

Aggiungere nuove procedure solo aggiornando contestualmente: mapping ACL nel Dashboard HtmlView, markup in dashboard/default.php, stringhe IT/EN, test, questa matrice e `QA_TESTBOOK.md`. Non suggerire come soluzione disabilitare ACL, CSRF, CSP, scanner, controlli su file protetti o accessi. I messaggi diagnostici non devono interpolare chiavi segrete, path sensibili completi o token. Distinguere sempre «controllo read-only», «prova eseguita» e «accettazione manuale».
