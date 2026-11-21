# SEC-P3 — Registro operativo privacy e conservazione dei dati JD Learn 1.0.0

**Stato:** contratto tecnico di sviluppo e prove automatizzate. **Non** è un'informativa legale, un attestato GDPR, una decisione sui periodi di conservazione o la prova che la richiesta sia stata eseguita su un sito di produzione. Il titolare deve approvare conservazione, basi giuridiche e processi operativi del proprio sito.

## Confini e inventario versionato

`SEC_P3_DATA_INVENTORY_1.0.0.json` classifica **tutte le 47 tabelle dichiarate nello schema SQL installabile** secondo finalità, destinatario della gestione, comportamento di cancellazione e retention ancora da approvare. `tests/sec-p3-data-inventory.py` confronta il registro con il DDL e fallisce quando una nuova tabella non è censita, quando ne manca una o quando le descrizioni sono lasciate vuote. Il controllo non prova che i dati siano assenti in backup, cache, provider esterni o tabelle aggiunte da estensioni di terze parti; gli upgrade SQL e i dati reali necessitano di analisi separata.

- **Editoriale (18 tabelle):** corsi, lezioni, contenuti, autori, versioni, docenti e materiali. Pubblicazioni e log di autoria possono restare dopo la dissociazione dell'account; verificare per ogni installazione i riferimenti storici a user ID.
- **Didattica (15 tabelle):** iscrizioni, sessioni, progressi, tentativi e risposte, rettifiche, audit, migrazioni e variazioni di stato. `onPrivacyRemoveData` dissocia gli utenti dalle iscrizioni, rimuove le sessioni, svuota i contenuti delle risposte e redige alcuni identificativi di operatori; non elimina in massa gli esiti accademici.
- **Attestati (3 tabelle):** l'esistenza di documenti emessi blocca la rimozione automatica, tutelando storico, numero seriale e documento. Una domanda di cancellazione con documento richiede valutazione legittima e intervento autorizzato, non manipolazione diretta dei record.
- **Job, audit e code (7 tabelle):** i job di proprietà e l'outbox riferita a iscrizioni bloccano la rimozione finché non sono risolte le dipendenze. Payload, receipt e log devono essere verificati per riferimenti indiretti; nessuna scadenza automatica viene inventata.
- **Email e consensi (4 tabelle):** la coda attiva `pending/retry/sending` blocca la rimozione; le righe terminali perdono email, nome, payload, consenso, note e diagnostici. Token di revoca e consenso correnti vengono rimossi. `event_key`, stato, timestamp e tentativi rimangono solo per finalità tecniche, senza associazione al destinatario.

Le relazioni editoriali facoltative con account Joomla (`created_by`, `modified_by`, associazione docente e soggetto, attore di revisioni e riconciliazioni email) vengono ora dissociate nella transazione di rimozione, preservando contenuti e identità didattiche non legate a un login. I registri con colonne obbligatorie di attribuzione (audit certificati, equivalenze approvate e campagne) impediscono ora automaticamente la rimozione finché il titolare non ha definito e applicato una decisione legittima di retention o trasformazione dei registri. Il blocco è condiviso da preflight ed evento di rimozione diretto; nessun audit immutabile viene alterato in silenzio. Non modificare gli audit immutabili con SQL arbitrario.

## Esercizio dei diritti via Joomla

1. Abilitare il plugin Privacy JD Learn e il componente Privacy Joomla. La richiesta del titolare deve seguire la procedura Joomla con verifica dell'identità; non esporre nuovi endpoint pubblici né aggirare le ACL. Esportazione: domini iscrizioni, richieste, progressi, quiz/tentativi/risposte, attestati, consenso e metadati delle consegne email dell'interessato. I token, i dati SMTP e i payload email non vengono esportati.
2. Prima di una rimozione, salvare evidenza redatta dei dati richiesti, approvazione e obblighi di conservazione. `onPrivacyCanRemoveData` fornisce un blocco generico se sono presenti attestati documentali, job, outbox o email attive. `onPrivacyRemoveData` riapplica il blocco prima della transazione. Non eseguire SQL manuali per forzare un passaggio negato.
3. Se il blocco dipende da messaggi attivi, risolverli mediante la procedura amministrativa prevista, evitando invii duplicati. Se dipende da attestati o obblighi del titolare, richiedere decisione motivata, definire limitazione del trattamento o conservazione legittima e documentare l'esito; **non inventare uno sblocco automatico**.
4. Una volta consentita la rimozione, verificare che i dati diretti dell'utente siano dissociati o cancellati come previsto, mantenendo solo i fatti didattici tecnici necessari. Confrontare una seconda esportazione e gli identificativi indiretti sulla copia di prova; registrare eventuali campi conservati e motivazione. Ripetere la richiesta non deve reintrodurre dati.
5. Rettifica, accesso, limitazione e opposizione non si riducono a `onPrivacyRemoveData`: utilizzare le procedure del titolare e di Joomla per verificare i documenti storici, dati del corso, proiezioni e accesso; annotare la base giuridica e i tempi di risposta adottati sul sito.

## Retention, backup, log, destinatari esterni

La versione 1.0.0 **non deve fissare arbitrariamente** scadenze di conservazione valide per tutte le installazioni. Prima della messa in produzione, il titolare deve approvare e mettere in atto una tabella delle retention per risultati didattici, attestati, richieste di iscrizione, email, token, code, log web/SMTP, backup e archivi. Ogni voce deve contenere finalità, base giuridica, tempo o evento di scadenza, destinatario/responsabile, procedura di cancellazione o anonimizzazione, gestione delle sospensioni legali e prove disponibili.

**Backup:** le richieste privacy non modificano automaticamente copie esterne o ripristini storici. Verificare protezione e accessi, scadenza dei backup, procedure di distruzione al termine del periodo e, soprattutto, un **registro di richieste da riapplicare dopo ogni restore** prima di riaprire il sito agli utenti. Il registro deve essere custodito separatamente dai backup ripristinati ed evitare dati non necessari. Non effettuare cancellazioni distruttive su backup non validati.

**Provider e hosting:** distinguere la responsabilità di Joomla/JD Learn da SDK video, servizi SMTP, sistema operativo, CDN e sistemi di log. Verificare eventuali trasferimenti, accordi di trattamento e termini di conservazione; la presente implementazione non può eliminare dati detenuti da terze parti.

## Verifiche automatiche / collaudo da svolgere

- `python3 tests/sec-p3-data-inventory.py`: corrispondenza tra inventario e schema installabile, tre fixture negative; eseguita dal preflight.
- `tests/joomla-smoke.php` su entrambi gli ambienti CI: plugin com_privacy, export per identità diverse, rimozione con e senza dipendenze, record in coda, protezione dell'altro destinatario, redazione dei diagnostici.
- **Manuale ancora in attesa:** account di prova con iscrizioni, progressi, quiz, attestati e consenso in stati pending/confirmed/revoked; esportazione e rimozione dalla UI di Joomla, ACL di operatori, diniego motivato per documenti, verifica SMTP reale, log, copie e ripristino su hosting effettivo. Documentare ogni prova con commit, versione Joomla, data e risultati, senza dati identificanti.

Non marcare il gate di rilascio accettato in assenza di queste evidenze operative.

---

## English — administrator operations

**This is an implementation and testing guide, not a site-specific privacy notice or a GDPR compliance certification.** The site controller must approve lawful retention periods and operational deletion processes. The machine-readable inventory covers all 47 tables declared in the distributed installation schema; CI rejects unclassified schema additions.

Use Joomla Privacy to verify the requester's identity and export JD Learn enrollment, progress, quiz, certificate, consent and recipient-scoped mail delivery metadata. Never export mail templates, SMTP diagnostics, unsubscribe tokens or another person's data. Before processing erasure, confirm retention obligations and use Joomla's preflight. Certificate documents, owned jobs, enrollment outbox and active mail rows block erasure. Never bypass these safeguards by editing the database directly. When permitted, terminal mail rows are de-identified, unsubscribe tokens and consent records removed, and learning records are handled conservatively.

**Backups, server logs and external services are not automatically erased by the plugin.** Approve retention schedules and maintain a separate, restricted erasure-replay record to be applied before bringing a restored site online. Validate the actual Joomla UI with two test accounts and different consent states, including export, pending-message blockers, issued certificates, second export, and operator permissions. Manual acceptance stays pending until observed on the target installation.

## Consultazione autorizzata dei consensi correnti

Nel backend **JD Learn → Coda email**, un operatore che possiede sia `core.manage` sia `jlms.audit.view` trova il pannello richiudibile *Consensi alle notifiche sui nuovi corsi*. Il pannello mostra soltanto ID utente, stato MAIL-11, origine, versione informativa e timestamp, con filtro per ID e paginazione. La fonte rimane `#__jlms_mail_consents` e non è uno storico di tutte le revisioni o il registro del consenso alla privacy policy del sito. Gli amministratori privi del permesso di audit globale non possono consultarlo. La revoca rimane nei flussi approvati per gli utenti; il pannello non consente mutazioni.

**English:** In **JD Learn → Mail queue**, operators holding both `core.manage` and `jlms.audit.view` can expand the *New-course notification consent* panel. This read-only, paginated view shows the current MAIL-11 state, origin, policy version and timestamps by Joomla user ID; it exposes no addresses, tokens or message payloads. It is not a historical consent log and does not replace Joomla's site-policy privacy consent registry. Changes and revocations continue through the existing learner flows.
