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

## Backlog operativo e dipendenze

| ID | Priorità | Attività / criterio di chiusura |
|---|---|---|
| IMP-01 · IMP-02 | P0 | Verificare e completare i soli residui di editor corso, versioni, pubblicazione e programma, con persistenza e ACL corrette; nessuna duplicazione delle PR già integrate |
| IMP-03 · IMP-04 | P1 | Preview autorizzata e fruizione didattica core con progressi/ripresa coerenti, senza effetti collaterali nella sola anteprima |
| IMP-05 | P1 | Quattro provider video e capability contract; prove reali YouTube, Vimeo, Cloudflare, HTML5 prima del gate finale |
| IMP-06 | P1 | Allegati e materiali: ambiti, preview, permessi, storage/retention e cleanup |
| IMP-07 | P1 | Catalogo/percorsi, categorie/tag Joomla, ricerca, URL, accessibilità pubblica |
| IMP-08 | P1 | Iscrizioni e roster essenziali, filtri e ACL corso-scoped; nessuna reportistica avanzata |
| IMP-09-MAIL-A/B/C/D | P0/P1 | Ricognizione residui già approvati e in larga parte integrati: template, eventi, preferenze, outbox, comunicazioni manuali, consenso/annuncio, scadenze; collaudo Joomla/SMTP ancora distinto dalla CI |
| IMP-09-MAIL-E/F | P0 | Uniformazione placeholder senza alias, evento di iscrizione/request/approvazione/rifiuto/annullamento e verifiche upgrade; PR #349–#358 e #361/#366/#367 sono evidenze da riconciliare, non da reimplementare |
| IMP-09-MAIL-G | P1 | Completare notifiche nuove lezioni/cambi significativi e notifiche ai responsabili; PR #376 copre il primo evento docente, non l'intero blocco |
| IMP-09-MAIL-H | P0/P1 | Revisione editoriale e UX IT/EN, template e anteprime, sicurezza rendering e upgrade; correlare #312, #363, #364 |
| IMP-09-CERT | P0/P1 | Ricognizione CERT-01–08 e regressioni della vista attestati; PR #300/#302/#304/#307 già documentate; issue #308 separata |
| IMP-09 / JOO / SECDEV | P0/P1 | Integrazioni Joomla, ACL, localizzazione, privacy, installazione e upgrade senza regressioni |
| STAB | P0–P2 | Correzioni e rifiniture delle issue aperte, classificazione per reale impatto; blocchi P0 prima del rilascio |
| DOC-P1–P5 | Gate | Manuali e help contestuale IT/EN, controlli link e coerenza senza trasferire lo storico nel README |
| SEC-P1–P5 | Gate | Audit e hardening, retention/privacy, inventario cookie, video/CMP reale e verifica sicurezza |
| IMP-10 / REL | Gate | Manifest versioni/date/nome plugin #379, ZIP installabile, checksum, upgrade con dati, update server Joomla, test browser/SMTP/video, performance e release candidate |

### Issue aperte rilevate il 14 novembre 2026

| Issue | Priorità proposta | Destinazione |
|---|---|---|
| [#364](https://github.com/jeckodevelopment/jd_learn/issues/364) — anteprima annuncio: 404 | P0 | MAIL-H / STAB |
| [#379](https://github.com/jeckodevelopment/jd_learn/issues/379) — versioni/date/nomi manifest | P0 | IMP-10 / REL |
| [#312](https://github.com/jeckodevelopment/jd_learn/issues/312) — traduzioni Mail Templates | P1 | MAIL-H |
| [#368](https://github.com/jeckodevelopment/jd_learn/issues/368) — stringa sidebar | P1 | STAB |
| [#308](https://github.com/jeckodevelopment/jd_learn/issues/308) — elenco attestati Joomla | P1 | CERT / STAB |
| [#377](https://github.com/jeckodevelopment/jd_learn/issues/377) — UI docenti | P1 | STAB (rivalutare dopo eventuale PR già integrata) |
| [#363](https://github.com/jeckodevelopment/jd_learn/issues/363) — pulsante annuncio fuori toolbar | P1 | MAIL-H |
| [#365](https://github.com/jeckodevelopment/jd_learn/issues/365) — azioni di salvataggio corso | P2 | STAB |
| [#359](https://github.com/jeckodevelopment/jd_learn/issues/359) — etichetta dashboard | P2 | STAB |

Le priorità sono **proposte di pianificazione**, non sostituiscono label GitHub non verificate. Dopo ogni fix ricontrollare se l'issue sia effettivamente risolta prima di chiuderla.

## Gate di rilascio 1.0.0

- Matrice dei requisiti inclusi riconciliata con PR e codice; nessun requisito P0 non implementato o non classificato.
- Installer e upgrade Joomla con dati storici effettivamente verificati; manifest dei sottopacchetti coerenti alla versione 1.0.0 e con data/nome corretti.
- Quattro provider video, percorsi di accesso/ACL, email end-to-end su SMTP reale (retry/deduplica/consenso), PDF attestati e ruoli collaudati.
- Sicurezza e privacy SEC-P1–P5 completate con evidenze; documentazione DOC-P1–P5 disponibile e verificata.
- CI verde **e** collaudi di accettazione separatamente registrati in [QA_TESTBOOK.md](QA_TESTBOOK.md); pacchetto, checksum, note di rilascio e updater verificati.
- Nessuna 1.0.0 dichiarata rilasciata soltanto perché la build o la CI funzionano.

## Stato e rendicontazione

L'ultima stima nel piano precedente era **circa 80% di copertura implementativa**, non un'accettazione della release; rimane **invariata** per questa revisione documentale. La baseline numerica, pesi e verifiche vanno letti in [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md) e [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv), senza sommare autonomamente percentuali locali. Lo storico tecnico precedente è conservato nell'[archivio del piano](STABLE_1.0.0_PR_PLAN_ARCHIVE.md).
