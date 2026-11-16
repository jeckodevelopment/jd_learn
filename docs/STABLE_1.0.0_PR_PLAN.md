# Piano operativo delle PR — JD Learn 1.0.0

> **Aggiornamento organizzativo, 14 novembre 2026.** Il piano resta suddiviso in IMP (implementazione), STAB (stabilizzazione), DOC (documentazione), SEC (sicurezza/privacy) e REL (release). Gli ID sono identificativi di lavoro, **non numeri di PR**. Lo storico precedente alla normalizzazione è preservato integralmente in [STABLE_1.0.0_PR_PLAN_ARCHIVE.md](STABLE_1.0.0_PR_PLAN_ARCHIVE.md). Questo aggiornamento è documentale e **non modifica l'avanzamento funzionale**.

## Regole di esecuzione

1. Prima di aprire una PR, verificare in `main` che il requisito non sia già implementato. Collegare gli ID alla matrice [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) e alle issue aperte.
2. Ogni PR runtime va nella milestone GitHub **1.0.0**, con sezione **Comportamenti da testare** (prove sul sito Joomla, ruoli, esiti attesi e regressioni). Aggiornare guide, testbook, changelog e matrice *solo se interessati*.
3. Seguire CI e controlli pertinenti fino al merge; dopo il merge fornire link al pacchetto Joomla realmente installabile, checksum verificato ed evidenze. Un artefatto Actions può contenere uno ZIP interno da estrarre.
4. Distinguere **implementato**, **CI verificata** e **accettato manualmente**. Non attribuire aumenti percentuali alle PR soltanto documentali o a bug fix non incrementali.
5. README = presentazione/stato sintetico; CHANGELOG = modifiche versionate senza duplicati; IMPLEMENTATION_STATUS = percentuali e copertura; QA_TESTBOOK = collaudi; IMPLEMENTATION_HISTORY = evidenze storiche. Non duplicare registri PR in README e piano.

## Perimetro approvato della 1.0.0

**Incluso:** editor e versioni corsi, programma/lezioni, catalogo e percorsi, progressi essenziali, materiali, quattro provider video, iscrizioni/roster essenziali, Mail Templates Joomla e notifiche approvate (MAIL-01–11 con le estensioni approvate), attestati PDF essenziali CERT-01–08, integrazioni Joomla, installazione/update, manuali e gate sicurezza/privacy.

**Escluso:** quiz avanzati oltre il sottoinsieme già approvato, reportistica e KPI avanzati, import/export massivi, quinto provider video, newsletter generaliste, campagne arbitrarie e JD Learn Credentials/Open Badges. Le specifiche più ampie restano una roadmap, non un blocco della 1.0.0.

Riferimenti vincolanti: [specifica email approvata](MAIL_TEMPLATES_1.0.0_APPROVED.md), [inventario email](MAIL_TEMPLATES.md), [attestati essenziali](CERTIFICATES_1.0.0.md), [documentazione 1.0.0](PRODUCT_DOCUMENTATION_1.0.0.md), [gate privacy/sicurezza](SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md).

**Decisione sui placeholder MAIL-E:** un solo dizionario canonico, **senza alias retrocompatibili** perché non esiste ancora una release stabile; validazione di template default, upgrade e documentazione devono applicare questa scelta. Le precedenti proposte di alias sono superate.

## Gestione ciclo di vita approvata il 15 novembre 2026

Inclusa nella 1.0.0: archiviazione didattica di corsi e percorsi con blocco di iscrizioni/fruizione e conservazione dello storico, cestinazione/ripristino di corsi, percorsi e docenti, eliminazione fisica soltanto quando non esistono utenti collegati o dipendenze storiche. Distinguere l'archiviazione editoriale già presente dal nuovo blocco degli accessi. **LCM-01 (P0: primo gate fruizione PR #484, restanti controlli aperti), LCM-02/03/04 (P1), LCM-05 (gate)**; specifica e collaudi in [LIFECYCLE_1.0.0_APPROVED.md](LIFECYCLE_1.0.0_APPROVED.md). I requisiti nuovi non sono considerati implementati fino a evidenza runtime; stima globale invariata per la sola formalizzazione.

**LCM-01 primo incremento:** `CatalogService::enrolledCourse()` non consente più accesso con stato corso diverso da pubblicato; test di integrazione su stati `0/2/-2` e archivio aggiornati, insieme alla verifica che le finestre promozionali non revocano da sole una frequenza esistente. Non chiudere LCM-01 finché i percorsi laterali, storico e attestati non sono verificati, e non confondere la CI con l'accettazione Joomla.

## Backlog operativo e dipendenze

| ID | Priorità | Attività / criterio di chiusura |
|---|---|---|
| IMP-01 · IMP-02 | P0 | Verificare e completare i soli residui di editor corso, versioni, pubblicazione e programma, con persistenza e ACL corrette; nessuna duplicazione delle PR già integrate |
| IMP-03 · IMP-04 | P1 | Preview autorizzata e fruizione didattica core con progressi/ripresa coerenti, senza effetti collaterali nella sola anteprima |
| IMP-05 | P1 | Capability contract allineato ai quattro adapter e sorgente; trascrizione editoriale locale implementata da PR #456; resta il collaudo reale YouTube, Vimeo, Cloudflare, HTML5 prima del gate finale |
| IMP-06 | P1 | Allegati e materiali: ambiti, preview corso/lezione testuale con doppia selezione esplicita, permessi, storage/retention e cleanup |
| IMP-07 | P1 | Catalogo/percorsi, categorie/tag Joomla, ricerca, URL, accessibilità pubblica |
| IMP-08 | P1 | Iscrizioni e roster essenziali, filtri e ACL corso-scoped; nessuna reportistica avanzata |
| IMP-09-MAIL-A/B/C/D | P0/P1 | Ricognizione residui già approvati e in larga parte integrati: template, eventi, preferenze, outbox, comunicazioni manuali, consenso/annuncio, scadenze; collaudo Joomla/SMTP ancora distinto dalla CI |
| IMP-09-MAIL-E/F | P0 | Uniformazione placeholder senza alias, producer di contesto corso/lezione/docente e eventi di iscrizione/request/approvazione/rifiuto/annullamento; PR #349–#358, #361/#366/#367 e #464 integrate. Restano validazione in modifica/anteprima e collaudo Joomla/SMTP end-to-end |
| IMP-09-MAIL-G | P1 | Notifiche nuove lezioni, aggiornamenti significativi dopo migrazione e avvisi ai responsabili implementati in #397/#399/#401; restano i collaudi end-to-end Joomla/SMTP e la riconciliazione del blocco MAIL-G |
| IMP-09-MAIL-H | P0/P1 | Revisione editoriale/UX IT/EN, template, anteprime, sicurezza rendering e upgrade: #312/#403/#410/#464 integrate. Accettazione Joomla/SMTP pendente |
| IMP-09-CERT | P0/P1 | Ricognizione CERT-01–08 e regressioni della vista attestati; PR #300/#302/#304/#307 già documentate; issue #308 risolta da PR #475 (CI 4/4, collaudo Joomla pendente) |
| IMP-09 / JOO / SECDEV | P0/P1 | Integrazioni Joomla, ACL, localizzazione, privacy, installazione e upgrade senza regressioni |
| STAB | P0–P2 | Correzioni e rifiniture delle issue aperte, classificazione per reale impatto; blocchi P0 prima del rilascio |
| DOC-P1–P5 | Gate | Manuali e help contestuale IT/EN, controlli link e coerenza senza trasferire lo storico nel README |
| SEC-P1–P5 | Gate | Audit e hardening, retention/privacy, inventario cookie, video/CMP reale e verifica sicurezza |
| IMP-10 / REL | Gate | Manifest versioni/date/nome plugin #379, ZIP installabile, checksum, upgrade con dati, update server Joomla, test browser/SMTP/video, performance e release candidate |

### Issue aperte rilevate il 14 novembre 2026

| Issue | Priorità proposta | Destinazione |
|---|---|---|
| [#379](https://github.com/jeckodevelopment/jd_learn/issues/379) — versioni/date/nomi manifest | P2 (label GitHub) | IMP-10 / REL |
| [#312](https://github.com/jeckodevelopment/jd_learn/issues/312) — traduzioni Mail Templates | P1 | MAIL-H |
| [#308](https://github.com/jeckodevelopment/jd_learn/issues/308) — elenco attestati Joomla | **Completata (PR #475)** | CERT-01/CERT-06; collaudo Joomla pendente |
| [#363](https://github.com/jeckodevelopment/jd_learn/issues/363) — pulsante annuncio fuori toolbar | P1 | MAIL-H, fix in PR candidata; collaudo editor/ACL pendente |
| [#359](https://github.com/jeckodevelopment/jd_learn/issues/359) — etichetta dashboard | P2 | STAB |

Le priorità sono **proposte di pianificazione**, non sostituiscono label GitHub non verificate. Dopo ogni fix ricontrollare se l'issue sia effettivamente risolta prima di chiuderla.

## Riconciliazione del registro requisiti (14 novembre 2026)

La **fonte di verità** è [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv), non il presente piano. Il registro è passato da 219 a **244** ID aggiungendo gli addenda approvati MAIL-01–10, DOC-01–10 e SEC-P1–SEC-P5 prima presenti soltanto nelle specifiche. Le associazioni per le PR #344, #362, #364, #365, #368, #375 e #377 e le correzioni correlate sono state ricondotte alle righe pertinenti. Il controllo GitHub Actions richiede ora, per ogni PR runtime, una modifica del CSV e un'associazione del proprio numero effettivo alla colonna `PR implementazione`. Vedere [guida registro](REQUIREMENTS_STATUS_GUIDE.md) e [regole di tracciabilità](REQUIREMENTS_TRACEABILITY.md).

Questo passaggio **non** conclude la ricognizione semantica integrale di tutti i 229 requisiti: i nuovi criteri email, documentali e di sicurezza sono prudentemente Parziale/Non iniziata, non accettati; ogni futura PR deve allineare code, residui, evidenze e percentuale motivata. Il numero di requisiti non va usato come denominatore diretto della stima pesata.

## Gate di rilascio 1.0.0

- Matrice dei requisiti inclusi riconciliata con PR e codice; nessun requisito P0 non implementato o non classificato.
- Installer e upgrade Joomla con dati storici effettivamente verificati; manifest dei sottopacchetti coerenti alla versione 1.0.0 e con data/nome corretti.
- Quattro provider video, percorsi di accesso/ACL, email end-to-end su SMTP reale (retry/deduplica/consenso), PDF attestati e ruoli collaudati.
- Sicurezza e privacy SEC-P1–P5 completate con evidenze; documentazione DOC-P1–P5 disponibile e verificata.
- CI verde **e** collaudi di accettazione separatamente registrati in [QA_TESTBOOK.md](QA_TESTBOOK.md); pacchetto, checksum, note di rilascio e updater verificati.
- Nessuna 1.0.0 dichiarata rilasciata soltanto perché la build o la CI funzionano.

## Verifica documentale

Il comando `python3 tools/check.py` esegue anche `tools/check_docs_hygiene.py`, che controlla la struttura del README e l'unicità e l'ordinamento delle versioni del changelog. Il controllo integra, ma non sostituisce, i test di installazione e accettazione Joomla.

## Stato e rendicontazione

La stima implementativa più recente è **80% (79,56% non arrotondato; area email 72%)**, ricalcolata dopo la PR #464 che amplia i producer di contesto approvati per i template email. Rispetto alla precedente riconciliazione (79,33%; email 70%) il delta è +0,22 punti globali e +2 punti nell'area email. Non equivale ad accettazione della release: i collaudi Joomla/SMTP restano separati e pendenti. Fonti correnti per pesi, conteggi ed evidenze: [IMPLEMENTATION_EVIDENCE.json](IMPLEMENTATION_EVIDENCE.json), [REQUIREMENTS_RECONCILIATION.json](REQUIREMENTS_RECONCILIATION.json), [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) e [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv). Lo storico tecnico precedente è conservato nell'[archivio del piano](STABLE_1.0.0_PR_PLAN_ARCHIVE.md).

## Integrazione MAIL-SCHED — PR #424 (integrata, collaudo pendente)

Per la 1.0.0: preconfigurare il task `jlms.mail.dispatch` disabilitato a 5 minuti, rispettare le modifiche degli amministratori in upgrade, rendere visibile un avviso solo nella sezione Comunicazioni della dashboard e mostrare i dettagli nello storico Coda email. Tracciamento: CORE-18, MAIL-09. Evidenze, comportamenti da testare e limiti in [MAIL_SCHEDULER_SETUP.md](MAIL_SCHEDULER_SETUP.md). **Implementazione integrata:** merge `5e61c0a32828d5b4387faf12ca2d1efdc964288a`, CI 4/4 [43725870964](https://github.com/jeckodevelopment/jd_learn/actions/runs/43725870964); **non accettato** fino al collaudo Joomla 6/SMTP; non modificare la percentuale globale sulla base del solo codice.

## STAB-MAIL-RECONCILE — PR #427 integrata, collaudo pendente

Correzione regressione nel salvataggio della riconciliazione manuale dei messaggi in stato `sending`: bind SQL per riferimento compatibile con Joomla. Collegamento MAIL-09/MAIL-10 nel registro `REQUIREMENTS_STATUS.csv`; test di regressione statico in `tools/check.py`. Non cambiare la gestione dei claim SMTP ambigui, i 15 minuti di soglia o il limite di 5 tentativi. Prova manuale Joomla/SMTP necessaria per l'accettazione.

PR #427 integrata: merge `58811c6817a91b372e0814479281e068d6a037f5`, CI [43728360557](https://github.com/jeckodevelopment/jd_learn/actions/runs/43728360557) 4/4 verde; stima globale 79,33% invariata. Resta collaudo Joomla/SMTP.

## IMP-04 — trascrizione delle lezioni audio

PR #451 integrata (merge `71c4afcf8cff36058511209b9f2e9dcb34c01b1d`): trascrizione audio facoltativa per LES-01/SCR-06, storage versionato, filtro HTML e resa accessibile in aula. CI completa superata nel run [43758841101](https://github.com/jeckodevelopment/jd_learn/actions/runs/43758841101); pacchetto `pkg_jlms-0.57.7-dev.zip`, SHA-256 `b12aec38c78fbb1157d4dee0e2c64df1e1d12192ff5a48ad462a5d7aae6d0ffd`, nell'[artefatto](https://github.com/jeckodevelopment/jd_learn/actions/runs/43758841101/artifacts/14179616865). Accettazione manuale Joomla ancora pendente; macro-requisito parziale, nessuna variazione della stima globale 79% (79,33%).
