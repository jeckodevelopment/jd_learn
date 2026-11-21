# SEC-P2 / SEC-P3 — consolidamento di sicurezza e privacy (1.0.0)

Stato: **implementazione parziale verificabile; gate release aperti**. La PR associata non costituisce un audit indipendente, una prova di conformità GDPR/ASVS o un collaudo della candidata finale.

## SEC-P2 — storage video legacy e hardening deployment

La correzione SEC-P1/VIDEO-ROOT vieta nuovi MP4 in chiaro con `video_storage_root` mancante o sotto il webroot; non protegge retroattivamente file già presenti. Eseguire sul server di prova (fuori da CI, senza condividere nomi dei file o dati personali):

```bash
python3 tools/audit_legacy_video_storage.py /percorso/assoluto/joomla/public
```

Codici: 0 = nessun file `.blob` corrispondente nel percorso ispezionato; 2 = file legacy o symlink da esaminare; 3 = ispezione impossibile. Il tool non legge il contenuto, non elenca i nomi, non sposta e non elimina nulla. Verifica solo `media/com_jlms/private` e **non prova l'assenza di file in altri percorsi**, di copie/CDN, di backup esposti o di errori di configurazione webserver. Trattare codice 2 o 3 come blocco del rilascio fino alla verifica manuale.

Mitigazione operativa: prima limitare l'accesso HTTP al percorso, poi predisporre storage video esterno al webroot, verificare mapping/integrità dei file e ACL GET/HEAD/Range, trasferire eventuali file legacy con backup e checksum, ripetere la prova e registrare verbale di migrazione redatto. Non cancellare file legacy per tentativi, perché i riferimenti DB possono essere attivi.

**Altri gate SEC-P2 non ancora superati:** inventario esatto delle dipendenze, scan di segreti e vulnerabilità sul commit candidato e artefatto ZIP, verifica CSP/header/hosting/webhook, esclusione dati sensibili dai log, finding critical/high con proprietario e decisione motivata. Non dichiarare SEC-P2 completo sulla sola ispezione video.

## SEC-P3 — rimozione privacy fail-closed

Il plugin Joomla Privacy applicava il controllo di conservazione tramite `onPrivacyCanRemoveData`, ma l'handler `onPrivacyRemoveData` non rileggeva tali dipendenze prima della transazione. Ora entrambi gli eventi condividono `hasRemovalDependencies`: attestati con documento, job di proprietà e outbox correlate all'iscrizione impediscono la rimozione. Anche l'invocazione diretta dell'evento rifiuta l'operazione con errore generico, senza modificare dati. Non cambia l'export né la politica del titolare.

Test: `tests/joomla-smoke.php` crea un job isolato, verifica il blocco sia per la preflight sia per una chiamata diretta all'evento di rimozione, poi elimina il job di fixture ed esercita la rimozione legittima. `tests/sec-p2-p3-guard.py` protegge la presenza del controllo prima della transazione e testa il report non distruttivo dello storage.

**Residui SEC-P3:** classificazione e tempi di conservazione approvati dal titolare per progressi, attestati, code, token, log e backup; verifica dei token di revoca e degli eventuali identificativi indiretti, test con richieste Joomla reali pending/confirmed/revoked, export, erasure, backup/restore e trattamento delle eccezioni legittime. Non eseguire cancellazioni indiscriminate degli attestati: i vincoli vanno prima definiti e provati.

## Accettazione della PR

- CI Python del gate e test Joomla DB verdi per entrambe le versioni PHP/database previste.
- Installare ZIP della PR sopra un ambiente di prova e riprodurre il job che blocca la rimozione; verificare che la rimozione autorizzata senza dipendenze continui a funzionare.
- Eseguire audit storage su ambiente di prova e allegare soltanto **conteggi** e decisioni (mai percorsi reali o dati degli utenti).
- SEC-P2 e SEC-P3 restano **missing/parziali** nella matrice finché gli altri criteri del gate non sono comprovati e l'accettazione manuale non è registrata.

Documentazione collegata: `SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md`, `JOOMLA_PRIVACY_CONSENT_INTEGRATION.md`, `QA_TESTBOOK.md`.

**Nota di verifica CI:** il gate dei metadati usa l'evento PR originale: le esecuzioni annullate o rilanciate con un payload precedente non attestano lo stato aggiornato di descrizione, milestone e label. Occorre una nuova esecuzione su evento aggiornato.

## Tranche successiva — mail queue e package gate (PR candidata)

**SEC-P3 — trattamento della coda email:** il plugin Privacy ora rifiuta richieste quando esistono righe di `#__jlms_mail_queue` con `recipient_user_id` corrispondente e stato `pending`, `retry` o `sending`. Questa verifica è comune a preflight e removal. Per le righe terminali, durante la transazione di rimozione elimina l'associazione utente, indirizzo, nome, timestamp del consenso, payload `template_data` e note di riconciliazione. I token di revoca vengono eliminati esplicitamente prima del consenso (già soggetto a CASCADE FK). Preserva event key, stato e contatori per la diagnostica priva di destinatario. La regressione Joomla crea sia una riga pending sia una terminale su utente isolato e verifica il blocco e la redazione.

**Limite:** il blocco su code non terminali richiede la risoluzione del processo di dispatch da parte dell'amministratore; evitare cancellazioni manuali dirette. Una rimozione da Joomla Privacy non sana automaticamente backup, log SMTP esterni né dati memorizzati in altri sistemi. Attestati/documenti e job mantengono il blocco introdotto nella tranche precedente. La policy di retention del titolare e il ciclo completo export/erasure rimangono da collaudare.

**SEC-P2 — gate del package:** `tools/sec_p2_package_scan.py` ispeziona la ZIP Joomla prodotta da `tools/build.py` e tutte le ZIP figlie entro limiti di profondità/dimensione. Rifiuta percorsi di traversal, file tipicamente segreti (`.env`, chiavi private/certificati p12, .git) e firme note di chiavi private/tokens AWS/GitHub; messaggi di errore riportano soltanto conteggi. `tests/sec-p2-package-scan.py` comprende fixture positive/negative e viene eseguito obbligatoriamente nel preflight di `tools/check.py`. Non sostituisce SCA dipendenze, analisi di vulnerabilità upstream, scansione completa dei segreti su Git history o ispezione CSP/header/runtime.

**Accettazione:** CI Joomla PHP 8.3/MySQL e PHP 8.4/MariaDB; test codice di privacy, package e fixtures; collaudo manuale per l'esercizio di richieste com_privacy, email realmente inviate e conservazione; nessuna attribuzione di completezza SEC-P2 o SEC-P3 prima dei rispettivi gate.

## SEC-P3 — minimizzazione export e rimozione della coda email (21/11/2026)

L'export tramite plugin Joomla Privacy include ora un dominio separato per le consegne email del richiedente, limitato a template ID, stato e timestamp di creazione/invio. La query usa esclusivamente `recipient_user_id`: non espone indirizzi, altri destinatari, variabili del template, eccezioni di trasporto, note amministrative o token. Gli eventi con identificativo destinatario già anonimizzato non sono più collegabili all'interessato e pertanto non compaiono nel dominio.

La rimozione, consentita soltanto in assenza delle dipendenze di retention già protette, azzera anche `last_error` nelle righe terminali della coda. Lo stato tecnico della consegna e i contatori rimangono per l'audit; le righe `pending`, `retry` e `sending` restano bloccanti. Regressione strutturale: `tests/sec-p2-p3-guard.py`; i test reali su Joomla con utente di prova e dati storici restano da completare.

**Stato:** hardening parziale SEC-P3; non dichiarare i criteri 01/02 implementati né aumentare la percentuale in assenza del restante inventario di retention, backup, diritti e collaudi. Nessuna modifica SQL o versione runtime: il comportamento resta backward compatible.

**Tracciabilità PR #850:** milestone 1.0.0, priorità P0, criteri SEC-P3 conservati come missing fino alla verifica integrale; gli scenari manuali specifici sono nel QA Testbook. La verifica CI deve essere eseguita su un evento PR successivo all'applicazione della milestone.

## SEC-P3 — Inventario, prove multiutente e procedure di backup (21/11/2026)

Tranche di chiusura software su branch SEC-P3: inventory machine-readable delle 47 tabelle con controllo anti-deriva integrato in `tools/check.py`, guida operativa IT/EN per esportazione/rimozione, retention, eccezioni e replay dei diritti dopo restore. Il test Joomla disposable usa due utenti distinti e verifica scoping dell'export, stati di consenso, blocchi su mail attive, token, e isolamento della cancellazione; non equivale al collaudo dell'hosting reale. Riferimenti: `docs/SEC_P3_DATA_INVENTORY_1.0.0.json`, `docs/SEC_P3_PRIVACY_OPERATIONS_1.0.0.md`, `tests/sec-p3-data-inventory.py`, `tests/joomla-smoke.php`.
