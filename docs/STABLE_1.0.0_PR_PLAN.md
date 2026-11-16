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


## Piano frontend UX approvato il 16 novembre 2026 — FE-01–FE-12

**FE-05 — Terza tranche PR #512 integrata (merge `fd20fb97981a868be9e065521eff03948e041b09`):** nuova vista privata `preferences`, menu Joomla IT/EN, riuso di `CourseAnnouncementConsent` e delle POST CSRF `mailconsent.request` e `mailconsent.revoke`. I redirect di richiesta, conferma e revoca tornano alla vista dedicata; il layout consenso è condiviso con la dashboard. Nessuna nuova regola di consenso, schema, endpoint o modifica delle notifiche operative. [CI 43923892421](https://github.com/jeckodevelopment/jd_learn/actions/runs/43923892421) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43923892421/artifacts/14255631013) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `ee1eb8ac0720c826e0ca6ebabc05354bf2a5d71a58838b2ec1ca0a61bb216678`. Dashboard riepilogativa e collaudo Joomla/SMTP restano da completare. Stima globale di riferimento 79,56% invariata.



**FE-05 — Seconda tranche PR #510 integrata (merge `0605c07b6c654e3ff006b9aed79b9bfbc601d676`):** vista autonoma privata `mycertificates` con voce menu Joomla IT/EN, riuso di `CertificateService::listForStudent`, endpoint download già protetto e partial condivisa con dashboard. Nessuna nuova query o ampliamento di accesso; collaudo Joomla su anonimato, multiutente, revoche, storico archiviato e download ancora necessario. [CI 43919671264](https://github.com/jeckodevelopment/jd_learn/actions/runs/43919671264) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43919671264/artifacts/14252108998), ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `2215a89c7676f421aeaa0bf0bd09857b16ec44f442c5fa2e44f654c2d33f6432`. La dashboard mantiene i blocchi preesistenti finché FE-05 non è completato; preferenze e riepilogo restano a piano. Il riferimento di avanzamento ponderato resta 79,56% fino a riconciliazione quantitativa e accettazione.


**FE-05 — Prima tranche integrata (PR #508; CI 4/4 verde; collaudo Joomla pendente):** introdurre `mycourses` come vista autonoma privata e selezionabile dal menu Joomla, riusando interamente `DashboardModel`, `DashboardService` e una sola partial delle card già mostrate in dashboard. Privacy headers, ACL, filtri, paginazione, storico e download/redirect di corso esistenti non devono cambiare. La dashboard corrente conserva per compatibilità gli altri blocchi, finché attestati, preferenze e riepilogo non saranno separati nelle tranche successive. Test XML, routing, contratto CSS e regressioni dashboard; collaudo Joomla e menu IT/EN pendenti. Requisiti **STU-01 e UI-02** collegati alla PR #508 nel CSV, guide e testbook aggiornati. Merge `6cbecb8b3c1a6a4ba78d2729b89750ce6522be27`; [CI 43916411402](https://github.com/jeckodevelopment/jd_learn/actions/runs/43916411402) 4/4 verde, [gate CSV](https://github.com/jeckodevelopment/jd_learn/actions/runs/43916411436) verde. [Artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43916411402/artifacts/14250946163), ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `54ebbd7c239bfaa7e087ed18df9d65844de6951ff33cf3bb1a923f6f42f86316`. **FE-05 non concluso** con questa tranche: collaudo Joomla e separazione attestati/preferenze/riepilogo pendenti.


**FE-04 — Seconda tranche integrata (PR #506; CI 4/4 verde; collaudo Joomla pendente):** salvataggi `lesson.begin` e ripresa audio con conferma discreta temporanea (5 s), errori persistenti fino a salvataggio riuscito, indicazione di salvataggio in corso e ARIA differenziata. Retry e chiavi idempotenti preservati; nessuna modifica agli endpoint o agli ACL. Verifiche statiche in CI e casi manuali di fallimento/rete nel testbook. Matrice STU-03 e UI-04 collegata alla PR #506. Merge `97aac9b4ac9e1963f6d372de8bc4780734928b12`; [CI 43902131817](https://github.com/jeckodevelopment/jd_learn/actions/runs/43902131817) 4/4, [gate CSV](https://github.com/jeckodevelopment/jd_learn/actions/runs/43902131825) verde. [Artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43902131817/artifacts/14243015359), ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `ff5b39470a9ebceb31f65dbaf871d1373424490d4a4839f0cfdea26e36b27a42`. **Collaudo manuale non eseguito; FE-04 non completamente accettato.**


**FE-04 — Prima tranche integrata (PR #504, CI 4/4 verde; collaudo Joomla pendente):** navigazione manuale Precedente/Successiva, immediatamente adiacente nella scaletta e mai mutativa; accesso solo se la finestra lezione è disponibile e non sono bloccati prerequisiti; stato non interattivo in caso contrario. Link GET Joomla, nessuna azione automatica di completamento. Test strutturale, traduzioni IT/EN, CSS responsive, regressioni su player, quiz e progressi. Matrice STU-03/UI-04, testbook, contratto CSS e changelog aggiornati nella PR #504. Merge `de7d587d70df22b3b1c9f8cdf30ae7b71229e628`; [CI 43894400917](https://github.com/jeckodevelopment/jd_learn/actions/runs/43894400917) 4/4; [gate CSV](https://github.com/jeckodevelopment/jd_learn/actions/runs/43894401046) superato. [Artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43894400917/artifacts/14239625032), ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `8865c84d1a6967409fc3195d2114c9cd166991ad53b1eb4d438272b7b3139c4b`. Accettazione Joomla pendente, FE-04 non completo.


**FE-03 — Prima tranche integrata (PR #502, CI 4/4 verde; collaudo Joomla pendente):** programma aula per moduli `details/summary` (modulo corrente aperto), toggle responsive per larghezza contenitore con fallback viewport/no JavaScript, sidebar sticky limitata al componente e fallback statico, intestazione compatta con i soli docenti pubblicabili. Preservati i controlli e servizi di progressi, player, quiz e materiali. Test strutturale dedicato, verifiche manuali su ACL, lingue, template Joomla, desktop/mobile e zoom. UI-04 e STU-01 associati alla PR #502 nella matrice requisiti. Merge `1f00b68ad3324020f2f1d3c517584cbf4564cda3`; [CI completa](https://github.com/jeckodevelopment/jd_learn/actions/runs/43890696148), [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43890696148/artifacts/14238120151) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `0798aaf5be2584ca542ed73af32c77d20a03282a204a221eb2a8127b4c6fad88`. Non dichiarare FE-03 conclusa senza accettazione sul sito di prova.


**FE-02 — Prima tranche integrata (PR #500; CI 4/4 verde; collaudo Joomla pendente):** pannello di iscrizione laterale con sticky/fallback, layout in griglia per spazio del componente Joomla e programma pubblico a moduli espandibili con `details`. Il servizio pubblico non restituisce titoli delle lezioni con finestra non attiva o rilascio relativo; nessun corpo/URL didattico esposto. La PR deve mantenere l'ordine dei campi Joomla nativi rispetto all'iscrizione (regressione HTTP intercettata e corretta), i form/csrf già presenti, CAT-02 e UI-04 aggiornati nella matrice; merge `03d4fea5408e1e45684f0b7637c6a4d3df516320`, [CI 43887834828](https://github.com/jeckodevelopment/jd_learn/actions/runs/43887834828), [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43887834828/artifacts/14234663916) contenente `pkg_jlms-0.57.7-dev.zip`, SHA-256 `f59ca41929dcbb37995f2570547a7e9dd12d3a17f1b4a0be89fdabd74ef26f10`. Le prove manuali su template e ACL restano da eseguire. L'esito CI non equivale ad accettazione e non modifica da solo la percentuale ponderata globale.



**FE-01, seconda tranche integrata (PR #498; CI 4/4 verde; collaudo Joomla pendente):** filtro per categoria pubblica con visibilità controllabile dalle opzioni generali e della singola voce menu; nessuna categoria riservata nell'elenco e nessun ampliamento di ACL tramite GET. Metadato livello ricavato dalla versione pubblicata e mostrato solo quando esiste. Verifica statica e collaudo Joomla su menu multipli, restrizioni ACL, categorie annidate e paginazione. CAT-01 e UI-02 associati alla PR #498 nella matrice CSV. Merge `f7f49579f81770b7241096ee4e50ae96b36d36c7`, [CI](https://github.com/jeckodevelopment/jd_learn/actions/runs/43881944473), [pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43881944473/artifacts/14233212517) (ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `15e769bbc0fa75f29652644039612ed3715699fa95b0a57879e791ce54481b24`). Non considerare completato FE-01 senza accettazione manuale.


**FE-01 — Prima tranche integrata (PR #496; CI 4/4 verde, accettazione Joomla pendente):** catalogo corsi a tre/due/una colonna per container, copertine e testi opzionali, ricerca/filtri attivabili per singola voce menu con ereditarietà dalle opzioni generali, paginazione e ordinamento iniziale configurabili. Merge `c8dae63b2fb6748e2dc9c54344023ed621667014`; [Actions 43879445918](https://github.com/jeckodevelopment/jd_learn/actions/runs/43879445918); [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43879445918/artifacts/14232580225), ZIP interno `pkg_jlms-0.57.7-dev.zip` SHA-256 `c3566d39b0ab0491686bf7efea0ce8d59fe6bad776e124af2f098ea14b635bfd`. Menu IT/EN e contratto CSS da collaudare su Joomla; opzioni di categoria specifiche e rifiniture dei metadati estesi rimangono interventi successivi. CAT-01 e UI-02 collegati alla PR #496 nella matrice CSV; la suite CI non sostituisce la verifica dei parametri per-menu sul sito Joomla. Stima globale invariata in assenza di nuova riconciliazione ponderata e accettazione manuale.


**FE-10, seconda tranche (PR #494 integrata; CI completa 4/4 verde, accettazione Joomla pendente):** inventario tecnico di viste/override e selettori frontend realmente utilizzati, test di compatibilità statici per hook JS↔markup e Web Asset Manager, collegamenti alla guida CSS. Nessuna modifica a ACL o output applicativo. Verifica: [Actions 43873221562](https://github.com/jeckodevelopment/jd_learn/actions/runs/43873221562); artefatto installabile [JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43873221562/artifacts/14228857358), ZIP interno `pkg_jlms-0.57.7-dev.zip` SHA-256 `909c2cb38160731b91fa052ae7d4463e063ca7fe0f875fad15f603c7238d24af`. La ricognizione statica non completa il collaudo su diversi template Joomla e non abilita automaticamente FE-01; vedere [FRONTEND_CONTRACT_INVENTORY.md](FRONTEND_CONTRACT_INVENTORY.md).

**Fonte funzionale:** [FRONTEND_UX_SPECIFICATION.md](FRONTEND_UX_SPECIFICATION.md). La realizzazione è pianificata per la 1.0.0 dove indicato; il codice preesistente resta baseline, non va duplicato. Le attività FE sono **ID di lavoro**, non numeri di PR e non prove di implementazione. La modalità immersiva completa rimane valutazione successiva e non gate di rilascio.

| Ordine suggerito | ID | Ambito e criteri principali | Gate e dipendenze | Tracciatura |
|---|---|---|---|---|
| 1 | FE-10 | Inventario CSS/JS/override, classi stabili, componenti condivisi, un solo stile nativo; nessuna interferenza col template | Test override, Joomla, WAM e regressioni | FE-10, UI-02 |
| 2 | FE-01 | Catalogo corsi: griglia responsive, ricerca e filtri opzionali **per voce menu**, paginazione, opzioni ereditarie | Menu IT/EN, istanze multiple, ACL, GET/SEF | FE-01, CAT-01 |
| 3 | FE-02 | Scheda corso: outline pubblico autorizzato e pannello iscrizione sticky con fallback | Corso pubblico/riservato, iscrizione, visibilità docenti | FE-02, CAT-01 |
| 4 | FE-03 | Aula: intestazione con docenti, sidebar richiudibile/sticky, moduli espandibili, mobile | Template diversi, input tastiera, revoche, video/audio/testo | FE-03, STU-01 |
| 5 | FE-04 | Aula: navigazione manuale, azioni contestuali, feedback salvataggio discreto ed errori persistenti | Nessun credito da navigazione; player/quiz, privacy | FE-04, STU-03 |
| 6 | FE-05 | Dashboard riepilogativa + viste autonome I miei corsi, I miei attestati, Preferenze, XML menu | Storico corsi archiviati, download protetto, URL notifica | FE-05, STU-01, CERT-05 |
| 7 | FE-06 | Navigazione personale interna opzionale configurabile, senza duplicare menu Joomla | Routing, menu mancanti, accessibilità, dispositivi | FE-06, UI-02 |
| 8 | FE-07 | Percorsi: griglia, copertina dedicata facoltativa, timeline verticale e progresso personale autorizzato | Proiezione dati, ACL, nessuna iscrizione di massa implicita | FE-07 |
| 9 | FE-08 | Categorie: sottocategorie e corsi insieme, configurazione di visibilità menu, conteggi pubblici | Gerarchie, lingua, paginazioni distinte | FE-08 |
| 10 | FE-09 | Elenco pubblico docenti con voce menu e profili sicuri, riuso schede | Privacy account, pubblicazione, ACL | FE-09 |
| 11 | FE-11 | Parametri menu e fallback generali unificati, catalogo e categorie, menu IT/EN | Upgrade/menu esistenti, per-menu override, browser | FE-11, UI-02 |
| 12 | FE-12 | Gate integrato UX: accessibilità, responsive, sicurezza, override, documentazione e collaudi manuali | Solo dopo completamento e CI delle PR funzionali | FE-12, SEC-P1–P5 |

**Disciplina di esecuzione:**
1. Prima del runtime verificare copertura reale dei componenti preesistenti e scegliere PR piccole, non una riscrittura totale del frontend. I FE possono essere raggruppati in PR quando l'intervento è atomico; ordine flessibile secondo dipendenze.
2. Ogni PR runtime: milestone 1.0.0, numero reale della PR in **REQUIREMENTS_STATUS.csv** per gli ID coinvolti, sezione **Comportamenti da testare** (browser Joomla, ACL, menu IT/EN, tablet/mobile, template diversi, regressioni), test automatici, guida CSS e changelog aggiornati prima del merge.
3. Ogni PR va seguita fino a CI finale verde e merge, senza scambiare la CI per collaudo; dopo il merge verificare l'artefatto **ZIP installabile** e fornire link e checksum. In caso di test bloccanti non integrare fino alla correzione.
4. Gli interventi FE pianificati **non costituiscono nuove righe di requisito canonico** e non implicano aumento della percentuale o accettazione. La matrice CSV rimane invariata in questa PR documentale, in conformità a REQUIREMENTS_STATUS_GUIDE.md; prima delle PR runtime verificare la mappatura ai requisiti esistenti e riconciliare anche baseline/piano/contatori se servono ID aggiuntivi. Aggiornare le stime soltanto dopo evidenza incrementale. La **modalità immersiva** è un'opzione futura da studiare, non un secondo layout obbligatorio per la 1.0.0.
5. README sintetico, CHANGELOG solo modifiche effettive, CSS contract come contratto implementato, specifica UX come target; nessun riferimento a prodotti o installazioni specifiche.

## Gestione ciclo di vita approvata il 15 novembre 2026

Inclusa nella 1.0.0: archiviazione didattica di corsi e percorsi con blocco di iscrizioni/fruizione e conservazione dello storico, cestinazione/ripristino di corsi, percorsi e docenti, eliminazione fisica soltanto quando non esistono utenti collegati o dipendenze storiche. Distinguere l'archiviazione editoriale già presente dal nuovo blocco degli accessi. **LCM-01 (P0: primo gate fruizione PR #484 integrata, restanti controlli aperti), LCM-02/03/04 (P1), LCM-05 (gate)**; specifica e collaudi in [LIFECYCLE_1.0.0_APPROVED.md](LIFECYCLE_1.0.0_APPROVED.md). I requisiti nuovi non sono considerati implementati fino a evidenza runtime; stima globale invariata per la sola formalizzazione.

**LCM-01 primo incremento (PR #484, CI 4/4 verde, collaudo Joomla pendente):** `CatalogService::enrolledCourse()` non consente più accesso con stato corso diverso da pubblicato; test di integrazione su stati `0/2/-2` e archivio aggiornati, insieme alla verifica che le finestre promozionali non revocano da sole una frequenza esistente. Non chiudere LCM-01 finché i percorsi laterali, storico e attestati non sono verificati, e non confondere la CI con l'accettazione Joomla.


**LCM-01 secondo incremento, PR #486 integrata (CI 4/4 verde, Joomla/SMTP pendente):** negare nuove migrazioni delle iscrizioni e notifiche didattiche (eventi nuova lezione e nuova versione) quando lo stato del corso non è `1`, anche se eventi erano già accodati prima dell'archiviazione. Aggiornare test, manuali e requisiti; restano da verificare storia personale, attestati, tutti gli endpoint laterali e le richieste concorrenti prima di dichiarare concluso il gate LCM-01.


**LCM-01 storico personale, PR #488 integrata (CI 4/4, collaudo Joomla pendente):** preservare le percentuali storiche per corsi di apprendimento, ma non attribuire una percentuale ai corsi di sola consultazione quando lo stato attuale è non pubblicato/archiviato/cestinato. Test di dashboard Joomla su stati 0/2/-2, ripresa negata, storico invariato; verifica PDF e collaudo manuale restano aperti. Nessuna chiusura automatica del requisito.

**LCM-01 — attestati storici, PR #490 integrata (CI 4/4 verde):** ampliare test Joomla per elenco e download privato del PDF con stati corso 0/2/-2, ACL del titolare, identità documento invariata e persistenza dopo upgrade. È verifica di non-regressione, non incremento di funzionalità; collaudo manuale e backup storage restano nel gate LCM-05.

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
