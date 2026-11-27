**CORE-20 — ricerca e filtri nell’elenco Percorsi (PR #1088 candidata, 27/11/2026):** completata la vista Search Tools Joomla per percorsi: ricerca titolo/alias/ID, filtri categoria/accesso/lingua/stato, ordinamento e paginazione con limiti server-side. La toolbar di lifecycle esistente resta invariata; batch avanzati differiti. In attesa di CI e accettazione Joomla. Nessun criterio o percentuale promosso prima dei gate.

**CORE-20 — selettori amministrativi ricercabili (PR #1087, CI #4836 e matrice #2245 verdi, 27/11/2026):** layout nativo Joomla searchable attivato nei quattro filtri Search Tools della lista Corsi e nel selettore multi-docente del form; `tests/courses-ui-http.py` verifica il rendering dei controlli. Artefatto `JD-Learn-0.64.22-dev`: https://github.com/jeckodevelopment/jd_learn/actions/runs/46005172831/artifacts/15201566250. CORE-20 resta Parziale, il collaudo manuale è in attesa e le percentuali restano invariate.

**CORE-09 — tag Joomla per corsi, docenti e percorsi (PR #1081, CI #4747 verde, 27/11/2026):** implementati campo, type alias e mapping UCM per `com_jlms.pathway`, persistenza Joomla nativa, reidratazione, conservazione nei salvataggi parziali e validazione degli ID. Fixture di associazione/rimozione/riassegnazione; CI 4/4 verde. `CORE-09/tags_context` promosso a `implemented`. Sviluppo documentato: 65,04% (510 implementati, 9 mancanti, 238 non verificabili); accettazione manuale invariata 4,67% (10/214, zero completi). Restano AC-19/AC-64 sul pacchetto Joomla installato, ricerca tag, ACL/lingua e upgrade. Dettaglio: `docs/CORE09_PATHWAY_TAGS_2026-11-26.md`.

**MAIL-G — progressione batch avvisi responsabili (PR #1080 merged, 26/11/2026):** le richieste già accodate non saturano il batch Scheduler; CI verde su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. Fix runtime NOT-01/CORE-19; criteri, percentuale e collaudo SMTP/Joomla non promossi.

**CORE-03 — revoca entitlement cross-context (PR #1079 merged, 26/11/2026):** estesa `tests/dashboard-http.py` per provare nella stessa sessione la revoca/ripristino attraverso ripresa, classroom, cronologia personale, scheda corso e catalogo. Il criterio resta `unverifiable` e la percentuale non cambia prima dell'esito CI e di una copertura completa; i test hosting/ruoli restano distinti dal collaudo automatico. Piano QA in `docs/CORE_03_CROSS_CONTEXT_REVOCATION_2026-11-26.md` e `docs/QA_TESTBOOK.md`.

**CORE-21/private_cache — verifica implementativa riconciliata (26/11/2026):** le PR #1032/#1064 forniscono manifesto di task/vista privata, gate no-store/noindex e fixture HTTP multi-sessione; CI #4693 completa verde sulle due matrici Joomla. `CORE-21/private_cache` è ora implementato sul perimetro software. Il residuo di invalidazione cache multilivello del requisito, il reverse proxy/CDN reale e il collaudo Joomla restano distinti. Percentuale sviluppo documentato: 64,81% (509 implementati, 9 mancanti, 239 non verificabili); accettazione manuale invariata a 4,67% (10/214, zero completi).

**SEC-P5/criterion_01 — test negativi E2E (PR #1076 merged, 26/11/2026):** registro di cinque suite Joomla (roster/ACL, apprendimento e revoca, quiz/identità, video/CSRF, materiali privati/upload) e gate fail-closed che verifica comandi e asserzioni. CI #4693 verde su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. Criterio riconciliato come implemented; sviluppo documentato aggiornato a 64,58% (508 implementati, 9 mancanti, 240 non verificabili). Collaudo Joomla manuale invariato a 4,67% (10/214 con esito, 0 integralmente accettati). Penetration/risk review, verifica hosting e go/no-go restano aperti.

**DOC-10 — allineamento note e changelog della candidata (PR #1074 merged (26/11/2026)):** il record di evidenza lega ora anche l'hash del changelog e richiede una sezione versionata per la candidata con le stesse note immesse nel workflow. Il test di release copre mismatch e integrità del changelog insieme a ZIP, feed e note. La candidata resta `candidate_not_approved`; criteri DOC-10/01–04 riconciliati come implemented; avanzamento documentato 64,46% (507/757 criteri implementati, 10 lacune, 240 non verificabili). La release stabile e l'accettazione manuale restano separate.

**CORE-10 — test Content History dei campi docente (PR #1073 merged, 26/11/2026):** `tests/joomla-editorial.php` verifica ora una revisione con campo nativo docente, modifica successiva e ripristino con `TeacherModel::loadHistory()`. Il criterio completo resta Parziale/unverifiable; non si promuove l'accettazione Joomla né la percentuale. Dettaglio: [CORE10_TEACHER_HISTORY_2026-11-26.md](CORE10_TEACHER_HISTORY_2026-11-26.md).

# Piano operativo delle PR — JD Learn 1.0.0

**Issue #379 — uniformità dei metadati, P1:** una sola versione/data di rilascio e nomi pubblici `JD Learn - ...` per tutte le 11 parti Joomla. Build deterministica `tools/build.py`, controllo degli ZIP annidati `tests/extension-manifest-metadata.py`, workflow release con data esplicita, test di installazione e upgrade CI. Registrare il collaudo della Gestione estensioni su Joomla prima dell'accettazione finale. Milestone 1.0.0; governance PR/label in `docs/RELEASE_METADATA.md`.

**Accettazione CERT-03/05/08 (17/11/2026, PR #566):** dopo l'installazione dell'artefatto della [PR #566](https://github.com/jeckodevelopment/jd_learn/pull/566), confermato il download funzionante dell'attestato preesistente bloccato da HTTP 503 con logo PNG. Diagnosi originale log: `phase=logo`, runtime `error_code=503`. Merge `81df4e888e12b48a6ac25705afa5e18d71cf2c67`; [CI 4/4 verde](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701); [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701/artifacts/14338442764). **Non ancora documentate come verificate:** conferma backend dello stato `ready`, immutabilità seriale e snapshot mediante confronto, altri formati e trasparenza, ACL/HEAD, backup/ripristino e recupero di completamenti senza emissione.


**PR #551 integrata — issue #547:** merge `2e9a65ab8d9044f9502950514c0a24419d04d03b`, CI completa 4/4 verde [run 44049557136](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136), gate CSV verde [run 44049557129](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557129); [pacchetto installabile JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136/artifacts/14312389981), digest archivio Actions `sha256:01ebeb70fae56e88caa1cbe54ae5d4f6eb7d408e824b8a0a94d7167dfa32da25` (non checksum interno dello ZIP Joomla). Il ritentativo esplicito è possibile solo dopo riparazione di template per iscrizione e completamento con errore validato pre-SMTP, nota e conferma, mai per trasporto incerto; collaudo Joomla/SMTP e correzione reale del modello Rigoletto rimangono pendenti. Avanzamento globale 79,56% invariato.

**MAIL-10 / issue #547 — retry manuale controllato (PR #551, CI e collaudo Joomla pendenti):** le sole notifiche `com_jlms.enrollment.confirmed` e `com_jlms.course.completed` in stato `failed` con categoria errore di validazione modello **prima dell'SMTP** possono essere riportate a `retry` dall'amministratore autorizzato, con conferma esplicita e nota audit (10–1000 caratteri), dopo la riparazione del template Joomla. Prima del cambio stato il server controlla template corrente, lingua, dati, nomi dei token e recapito; aggiornamento SQL atomicamente condizionato a stato/errore/tentativi. Non ritentare errori di trasporto, invii incerti, `sending` non riconciliati, record esauriti o altre categorie. Il task di invio Joomla resta l'unico mittente, nessun invio immediato dal pulsante. Avanzamento 79,56% invariato.

- **PR #545 (mail completamento video)** integrata al commit `f0415c45dd512797c4f5882d97d430c58bdc29b0`; CI 4/4 verde [run 44045664274](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274), gate CSV [run 44045664187](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664187), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274/artifacts/14311024216). Token `COMPLETION_DATE` e `DASHBOARD_URL` ora disponibili nell'evento di completamento generato da `VideoProgressService`. Collaudo Joomla/SMTP ancora da eseguire.
- **PR #548 (diagnostica template mail, issue #547)** integrata al commit `12edaa91c2ba95e0ea159d897b6f9cabcb9d304e`; CI 4/4 verde [run 44046787217](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217), gate CSV [run 44046787244](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787244), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217/artifacts/14311582918). Diagnostica admin dei token non risolti/assenza modello senza esporre contenuto delle email. Il modello `com_jlms.enrollment.confirmed` fallito in installazione Joomla di prova richiede controllo e correzione nell'installazione; nessun invio storico riaccodato automaticamente.
- **PR #549 (diagnostica attestati, issue #546)** integrata al commit `013267b788e2033fa5f11ef89bdf3b284c8d47d1`; CI 4/4 verde [run 44048059581](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581), gate CSV [run 44048060095](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048060095), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581/artifacts/14311889571). Lista protetta e limitata delle iscrizioni completate senza attestato, con diagnosi dei gate; nessuna emissione retroattiva forzata. Collaudo Joomla e causa precisa del corso Rigoletto ancora pendenti.

Avanzamento globale di riferimento **79,56%**, invariato: gli incrementi sono correzioni/diagnostica. Issue #546 e #547 rimangono aperte per verifica sul sito di prova.

**CERT-06 / P0 #546 — diagnosi attestati mancanti (PR #549, CI e collaudo Joomla pendenti):** nuova sezione in JD Learn → Attestati, accessibile solo con `core.manage` e `jlms.certificates.manage`, che mostra in sola lettura le ultime 25 iscrizioni realmente completate senza emissione completa. Espone la causa: regole della versione pubblicata senza rilascio attestati, preferenze globali/corso disabilitate, modello attestato assente, configurazione invalida oppure emissione attesa ma assente/documento non collegato. Nessuna emissione automatica retroattiva, modifica delle regole storiche, invio email o variazione della percentuale di completamento. Test nativi Joomla su ACL, limiti e categorie di esito. Avanzamento globale 79,56% invariato.

**MAIL-10 — issue #547 (PR #548, test sito Joomla pendente):** nella Coda email amministrativa, per messaggi `failed`/`retry` classificati come errore modello, JD Learn ora rilegge in sola lettura il template Joomla effettivo della lingua della riga e mostra se è assente oppure i **nomi** dei tag canonici non disponibili (massimo 12). Non espone indirizzi, valori delle variabili, dati SMTP, token o eccezioni. Non riscrive i modelli personalizzati, non introduce alias legacy, non modifica lo stato della coda né riavvia gli invii. Verificare anche le varianti lingua e correggere esplicitamente i template obsoleti.

**MAIL P0 — completamento video (PR in corso, 17/11/2026):** la notifica `com_jlms.course.completed` generata da `VideoProgressService` non forniva `COMPLETION_DATE` e `DASHBOARD_URL`, nonostante il modello standard Joomla li richieda. Corretto il produttore affinché fornisca entrambi i campi (data reale del completamento persistita e URL autenticato della dashboard), con test regressione. Il messaggio di iscrizione `com_jlms.enrollment.confirmed` presente nello screenshot è un evento distinto: il relativo produttore fornisce già i tag canonici, e la causa va verificata sul modello effettivo `#__mail_templates` personalizzato dell'installazione, evitando di sovrascrivere template amministrativi o reintrodurre alias. L'emissione attestati dipende dalle regole immutabili della versione pubblicata, dal consenso configurazione `certificate_enabled` globale/per corso e dalla presenza di un modello attestato attivo; verificare le precondizioni prima di parlare di regressione. Avanzamento 79,56% invariato.

**PR #543 integrata (P0 YouTube #532):** merge `a695e4c24179b1933c3856c65f802baab1ab7deb`; CI completa 4/4 verde [run 43984520221](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221), gate CSV verde [run 43984520239](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520239). [Pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221/artifacts/14285480650); digest dell'archivio Actions `sha256:21074ebe6c878571a47e907e05926edd7598d168b6a6b97f742daa1def8c78ad` (non checksum dello ZIP Joomla interno). Aggiunto il permesso `autoplay` all'iframe YouTube ma confermato `autoplay=0`: serve un click utente, consenso preservato. **La riproduzione e il completamento su installazione Joomla di prova sono da collaudare, issue #532 aperta**. Avanzamento 79,56% invariato.

**P0 #532 — permesso riproduzione IFrame YouTube (PR #543, collaudo installazione Joomla di prova pendente):** il player JD Learn dichiarava `allow="encrypted-media; fullscreen; picture-in-picture"`, omettendo `autoplay` richiesto dagli esempi ufficiali Google per gli iframe YouTube. La configurazione ora delega il permesso `autoplay` all'iframe, senza attivare autoplay automatico (il parametro resta `autoplay=0`); SDK, consenso, `Referer`, ACL e calcolo dei progressi invariati. Test di regressione sull'attributo `allow` e sul parametro autoplay. L'eventuale blocco residuo va diagnosticato con evidenza dell'evento YouTube, non con ulteriori modifiche speculative. Percentuale globale 79,56% invariata.

**PR #541 integrata — P0 #532:** merge `2ab0ffa2589a22013a26b2ad9ee7bcefe11c3fd6`; CI 4/4 verde [run 43983276857](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857), integrità CSV [run 43983276867](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276867). [Pacchetto installabile JD Learn 0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857/artifacts/14283797967), digest archivio Actions `sha256:f9feaf56a8d380d2ee0c101304b243613c10402bd57554cfe2ef2e1878fcc24c` (non checksum interno del pacchetto ZIP Joomla). **Riproduzione video e completamento da verificare manualmente su installazione Joomla di prova prima di chiudere issue #532**. Avanzamento globale 79,56%, invariato.

**P0 #532 — race YouTube IFrame API (PR #541, CI e test live pendenti):** l'adapter collegava `YT.Player` solo dopo `iframe.onload`, evento di navigazione della pagina YouTube e non garanzia di handshake dell'IFrame API. Ora la API viene collegata subito dopo l'inserimento dell'iframe nel DOM, prima del completamento della navigazione; referrer policy, dominio `youtube-nocookie.com`, ID video, consenso e telemetria restano invariati. Evidenza: l'utente vede il poster, Play resta su spinner in Safari e Chrome; YouTube diretto funziona. L'esito reale della modifica deve essere confermato sul sito di prova. Avanzamento globale 79,56% invariato.

**PR #539 integrata (P0 #532; merge `bebffec3eb01b3eead93d6a8e96833b5d49eec6e`):** CI verifica completa 4/4 verde [run 43980525575](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575), gate CSV [run 43980525067](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525067). [Artefatto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575/artifacts/14282058958), digest dell'archivio GitHub Actions `sha256:3f87d34fd71960a20bfc3adbf75d3a523c04c6c0f62eff795be57a95811c1713` (NON checksum dello ZIP Joomla interno). Il frontend serializza `lesson.begin` e `video.playback` per la stessa lezione/iscrizione; test automatici verificati. **Il collaudo reale su installazione Joomla di prova dell'avvio e del completamento video è pendente; issue #532 rimane aperta.** Avanzamento globale 79,56% invariato.

**P0 #532 — PR #539, in verifica CI / collaudo Joomla pendente (16/11/2026):** il frontend ora attende la conclusione della richiesta idempotente `lesson.begin` della stessa iscrizione/lezione prima di richiedere `video.playback`. La modifica mira a evitare scritture concorrenti su iscrizione/progresso e non cambia i calcoli di copertura o i privilegi. L'HTTP 500 live non è ancora attribuito con certezza a tale concorrenza; serve collaudo su Joomla e verifica separata di `video.save` e completamento. Avanzamento globale di riferimento 79,56% invariato.

> **Aggiornamento organizzativo, 14 novembre 2026.** Il piano resta suddiviso in IMP (implementazione), STAB (stabilizzazione), DOC (documentazione), SEC (sicurezza/privacy) e REL (release). Gli ID sono identificativi di lavoro, **non numeri di PR**. Lo storico precedente alla normalizzazione è preservato integralmente in [STABLE_1.0.0_PR_PLAN_ARCHIVE.md](STABLE_1.0.0_PR_PLAN_ARCHIVE.md). Questo aggiornamento è documentale e **non modifica l'avanzamento funzionale**.

**P0 YouTube #532 — PR #533 integrata:** merge `ff4e6a376f5b59e2e4ba5d2af63f1cca9832fb2f`; [CI completa 43958890176](https://github.com/jeckodevelopment/jd_learn/actions/runs/43958890176) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43958890176/artifacts/14273243043) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `a967b7d871ac5f4a2ff6be69b11ded00d2cb9706e4b8400f6feff985ba616fac`. Diagnostica editoriale YouTube distinta per CSP/SDK/referrer/embed/timeout e messaggi server 4xx controllati; Referrer-Policy impostata alla raccomandazione Google. **Non c'è ancora una verifica su player YouTube live nel sito interessato: issue P0 #532 resta aperta e la funzionalità non è accettata manualmente.** Percentuale globale di riferimento 79,56% invariata.

**P0 YouTube #532 — PR #535 integrata:** merge `26f6b94b46c77e2468cc5ab1ede2fc4b5078b37e`; [CI 43963159315](https://github.com/jeckodevelopment/jd_learn/actions/runs/43963159315) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43963159315/artifacts/14274983577) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `a114132bf1fa303718b1d0954eecce6d6b0c2b54b6e1a49eba7023c8e632b602`. Corretto il registro WAM fermo a 0.45.0 e introdotta una chiave di cache basata su versione e contenuto degli asset/componenti/provider a ogni build; diagnostica dell'editor distinta per HTTP server, rete, JSON, adapter e SDK. **La issue #532 resta aperta** finché non si verifica l'embed YouTube sul sito Joomla reale; non è un collaudo live. Avanzamento globale di riferimento 79,56% invariato.

**P0 YouTube #532 — PR #537 integrata:** merge `eb4cafa7b0df6aeffc726522eaea50b61c0bade8`; [CI 43970838888](https://github.com/jeckodevelopment/jd_learn/actions/runs/43970838888) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43970838888/artifacts/14279810203) `JD-Learn-0.57.7-dev` (archivio Actions SHA-256 `3e7ec0b42645dcb7fdbb1f13cc96cd48eebad8f47c8a556acd88dd5b07591ca9`; digest dello ZIP interno non verificato qui). Corretto il binding del trasporto metadata su Safari; test JS e collegamento VID-02 nel registro canonico. **Issue P0 #532 aperta** finché una lezione YouTube non viene rilevata, salvata e riprodotta nell'ambiente Joomla reale. Avanzamento 79,56% invariato.

## Regole di esecuzione

1. Prima di aprire una PR, verificare in `main` che il requisito non sia già implementato. Collegare gli ID alla matrice [REQUIREMENTS_STATUS.csv](REQUIREMENTS_STATUS.csv) e alle issue aperte.
2. Ogni PR runtime va nella milestone GitHub **1.0.0**, con sezione **Comportamenti da testare** (prove sul sito Joomla, ruoli, esiti attesi e regressioni). Aggiornare guide, testbook, changelog e matrice *solo se interessati*.
3. Seguire CI e controlli pertinenti fino al merge; dopo il merge fornire link al pacchetto Joomla realmente installabile, checksum verificato ed evidenze. Un artefatto Actions può contenere uno ZIP interno da estrarre.
4. Distinguere **implementato**, **CI verificata** e **accettato manualmente**. Non attribuire aumenti percentuali alle PR soltanto documentali o a bug fix non incrementali.
5. README = presentazione/stato sintetico; CHANGELOG = modifiche versionate senza duplicati; IMPLEMENTATION_STATUS = percentuali e copertura; QA_TESTBOOK = collaudi; IMPLEMENTATION_HISTORY = evidenze storiche. Non duplicare registri PR in README e piano.
6. **Riconciliazione documentale obbligatoria:** prima di dichiarare conclusa una PR, verificare lo stato della riga interessata in `REQUIREMENTS_STATUS.csv` (implementazione, prova automatica, accettazione manuale sempre separate), aggiornare `CHANGELOG.md` e `docs/IMPLEMENTATION_STATUS.md` per i cambiamenti pertinenti, mantenere gli scenari in `docs/QA_TESTBOOK.md` e registrare SHA merge/CI/artefatti in `docs/IMPLEMENTATION_HISTORY.md`. Se l'evidenza finale nasce dopo il merge, eseguire una PR documentale successiva; non dichiarare allineamento prima della sua integrazione. `docs/REQUIREMENTS_RECONCILIATION.json` conserva le baseline datate: ricalcolare dati aggregati soltanto con una nuova ricognizione completa, senza sovrascrivere conteggi storici.


**AUT-03 — avvisi trascrizioni integrati:** PR #530 integrata (merge `771b6cd71a1c4dc07d4202c90d57dd4c140a6cab`), [CI 43955475409](https://github.com/jeckodevelopment/jd_learn/actions/runs/43955475409) 4/4 verde, [pacchetto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43955475409/artifacts/14271932231) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `6421b61d36d8c6b245538474f8747bad434b665d62da3183ccaa0bb6ec3f813c`. **Collaudo manuale Joomla ancora da eseguire**; riferimento globale 79,56% invariato. L'assenza della trascrizione editoriale audio/video compare nel preflight come avviso non bloccante, senza dedurre le caption esterne.

## Perimetro approvato della 1.0.0

**Incluso:** editor e versioni corsi, programma/lezioni, catalogo e percorsi, progressi essenziali, materiali, quattro provider video, iscrizioni/roster essenziali, Mail Templates Joomla e notifiche approvate (MAIL-01–11 con le estensioni approvate), attestati PDF essenziali CERT-01–08, integrazioni Joomla, installazione/update, manuali e gate sicurezza/privacy.

**Escluso:** quiz avanzati oltre il sottoinsieme già approvato, reportistica e KPI avanzati, import/export massivi, quinto provider video, newsletter generaliste, campagne arbitrarie e JD Learn Credentials/Open Badges. Le specifiche più ampie restano una roadmap, non un blocco della 1.0.0.

Riferimenti vincolanti: [specifica email approvata](MAIL_TEMPLATES_1.0.0_APPROVED.md), [inventario email](MAIL_TEMPLATES.md), [attestati essenziali](CERTIFICATES_1.0.0.md), [documentazione 1.0.0](PRODUCT_DOCUMENTATION_1.0.0.md), [gate privacy/sicurezza](SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md).

**Decisione sui placeholder MAIL-E:** un solo dizionario canonico, **senza alias retrocompatibili** perché non esiste ancora una release stabile; validazione di template default, upgrade e documentazione devono applicare questa scelta. Le precedenti proposte di alias sono superate.


## Piano frontend UX approvato il 16 novembre 2026 — FE-01–FE-12

**FE-12 — seconda tranche HTTP integrata:** PR #528 integrata (merge `48418ddbf4dfa5449a4b8e008a94d64a75c1d224`); [CI 43952710335](https://github.com/jeckodevelopment/jd_learn/actions/runs/43952710335) 4/4 verde; [artefatto](https://github.com/jeckodevelopment/jd_learn/actions/runs/43952710335/artifacts/14269271284) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `f2043d79998e85fe62e2f0930f5c8e965726350029f78c61a7b68427ae7d8650`. Corretto HTTP 404 della vista `teachers` nell'allowlist `DisplayController`; verificati su Joomla disposable le viste `categories`, `pathways`, `teachers`, relativi markup e asset WAM. FE-12 resta aperto: 10 scenari manuali non eseguiti; avanzamento globale 79,56% invariato.


**FE-12 — gate automatico integrato:** PR #526 integrata (merge `fe32457f939c32a439d75192f65074b1dc960c24`); [CI 43946500653](https://github.com/jeckodevelopment/jd_learn/actions/runs/43946500653) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43946500653/artifacts/14264109572) contenente `pkg_jlms-0.57.7-dev.zip`, SHA-256 `2e318ed0ed11870a2bad26e3307dd593e9d626adf790b8390a0980824a2132a2`. **FE-12 non accettato manualmente:** i 10 scenari sono ancora da eseguire. Stima globale di riferimento 79,56% invariata. Verificati staticamente contratti MVC/menu IT/EN, router, WAM, CSS, SEO privato; corretta traduzione mancante del menu Catalogo percorsi. Il gate finale resta aperto fino al collaudo Joomla, WCAG/override/ACL runtime.


**FE-11 — PR #524 integrata (merge `65530bf08a68b37e73d4e6c441949d0660f7e5b1`); [CI 43944091496](https://github.com/jeckodevelopment/jd_learn/actions/runs/43944091496) 4/4 verde; [pacchetto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43944091496/artifacts/14264592103) contenente `pkg_jlms-0.57.7-dev.zip`, SHA-256 `8eb55d809ebb855a6a5537e0d864d3f93c426eacea922d46305e82ee0eb7b515`. Collaudo manuale Joomla pendente; riferimento globale 79,56% invariato.** Le opzioni per sottocategorie/corsi nella pagina categoria ereditano i default globali con override Globale/Sì/No per voce menu; anche ricerca/filtri, ordinamento, page size e ordinamento predefinito sono configurabili nella voce menu categoria. Il menu applica gli override solo se la categoria corrisponde all'ID attivo; nessuna abilitazione via GET o modifica ACL. Test frontend aggiornata e nuova copertura FE-11.


**FE-09 — PR #522 integrata:** directory pubblica docenti con menu Joomla IT/EN, lista paginata, foto facoltative e link ai profili. ACL, lingua e categorie controllate da `TeacherProfiles::available`, nessun dato degli account Joomla. Merge `a8fa4b80d88e7e1ea2f5c691439730765dfc10fb`; [CI 43940346249](https://github.com/jeckodevelopment/jd_learn/actions/runs/43940346249) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43940346249/artifacts/14261918912) ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 verificato `6ec7bb1f65b8b9d49f8d33915e837850dc9e48fdbf73b92ebaf4ca1ca089dd8c`. Collaudo manuale Joomla pendente; avanzamento globale 79,56% invariato.

**FE-08 — PR #520 integrata (merge `231ed6c1f7d5ea3468e616a94ba3221230575eb5`):** nelle opzioni della voce menu Joomla di tipo `category` sono disponibili due impostazioni indipendenti, `category_show_subcategories` e `category_show_courses` (Sì/No, default Sì). La pagina categoria conserva la visualizzazione congiunta e le due paginazioni distinte quando entrambe sono attive; la sezione nascosta non effettua le rispettive query di elenco. L'opzione è applicata solo se la voce menu attiva corrisponde esattamente all'ID categoria, non è abilitabile tramite GET e non modifica i controlli ACL ereditati. Le schede di sottocategorie e corsi usano le classi pubbliche di card. Nessun conteggio non fondato o nuova tabella. [CI 43937676157](https://github.com/jeckodevelopment/jd_learn/actions/runs/43937676157) 4/4 verde; [pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43937676157/artifacts/14262196438) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `ef30b163772df6a3dbadd8b6549ceb96c62d4b1b13539db4595811314ceb9b06`. Il collaudo manuale Joomla rimane pendente; stima globale di riferimento 79,56% invariata.



**FE-07 — PR #518 integrata (merge `4b01daddaf5ebff7987c6cddfcab1c73758968ff`):** catalogo pubblico con schede, introduzione e copertina dedicata facoltativa; campo immagine media Joomla nel backend, alt/decorativa e validazione locale tramite CourseArtwork, con dati salvati in params_json esistente. Dettaglio con copertina, sequenza verticale ordinata, numerazione editoriale senza prerequisiti impliciti e progressi personali solo per autenticati quando presenti nella proiezione PathwayService/PathwayProgress. Nessuna iscrizione cumulativa, schema o bypass ACL. Aggiunta voce menu Joomla per dettaglio percorso, traduzioni IT/EN, CSS scoped, test e QA. Ricerca/filtri per catalogo percorsi non introdotti in questa tranche; [CI 43935233607](https://github.com/jeckodevelopment/jd_learn/actions/runs/43935233607) 4/4 verde; [artefatto Joomla](https://github.com/jeckodevelopment/jd_learn/actions/runs/43935233607/artifacts/14258414878) con ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `46e7d0a91fc95b0e294121c06caa7769b03ff631ef28eb448b7db1c2be6cd8c5`. Collaudo manuale Joomla pendente e avanzamento globale di riferimento 79,56% invariato.



**FE-06 — PR #516 integrata (merge `67985316915ef6d60b25ab0c2de358129f0bf8ee`):** navigazione personale opzionale condivisa fra dashboard, mycourses, mycertificates e preferences; impostazione Sì/No per singola voce menu Joomla (default No). Rendering esclusivamente se la voce menu attiva corrisponde alla vista; link Joomla SEF e voce corrente non cliccabile con aria-current=page. Nessuna nuova sidebar globale o controllo ACL. [CI 43932007606](https://github.com/jeckodevelopment/jd_learn/actions/runs/43932007606) 4/4 verde, compreso HTTP Joomla di attivazione menu; [artefatto](https://github.com/jeckodevelopment/jd_learn/actions/runs/43932007606/artifacts/14258847023) con ZIP interno pkg_jlms-0.57.7-dev.zip, SHA-256 `6b2add93bb50e901ec33c64f685483dbd9718e094d6222951d7cdd085b06885e`. Collaudo manuale Joomla pendente; riferimento globale 79,56% invariato.


**FE-05 — Quarta tranche PR #514 integrata (merge `cfad66f49620beb205cecb24425215d4030d21d3`):** la vista `dashboard` diventa **La mia formazione**, con contatori completi per non iniziati/in corso/completati ricavati da una query owner-scoped, totale attestati propri dal servizio esistente e link alle viste autonome `mycourses`, `mycertificates`, `preferences`; ultima attività riprendibile solo dopo proiezione ACL già esistente. Il dettaglio, i filtri, la paginazione e le azioni dei singoli corsi restano nella vista `mycourses`, senza duplicazione. I test HTTP storici vengono trasferiti alla vista `mycourses` mantenendo le verifiche di autorizzazione. Nessuna gamification, modifica al database o invio mutativo per GET. [CI 43928472754](https://github.com/jeckodevelopment/jd_learn/actions/runs/43928472754) 4/4 verde; [artefatto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/43928472754/artifacts/14256409058) ZIP interno `pkg_jlms-0.57.7-dev.zip`, SHA-256 `c4ab11d7b3e57064768b29a8cbee17677134840278b0acfd9759cb77f563e4a8`. FE-05 implementato nel perimetro delle quattro viste, con accettazione Joomla ancora pendente; stima di riferimento 79,56% invariata fino a riconciliazione ponderata.



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


### Correzione certificati pre-release (issue #553)

La pubblicazione delle nuove versioni didattiche deve congelare il valore effettivo di `certificate_enabled` dopo l'applicazione dell'ereditarietà globale/per corso. La modifica non interviene automaticamente sulle versioni già pubblicate; la gestione di completamenti storici privi di attestato richiede una procedura amministrativa distinta con autorizzazione, audit e idempotenza. Test di accettazione in `docs/QA_TESTBOOK.md`.


### Recupero amministrativo attestati storici (#553)

Un completamento precedente alla correzione del flag versione può essere gestito da amministratore con permesso `jlms.certificates.manage`, motivazione obbligatoria e conferma esplicita. L'emissione è idempotente, preserva data del completamento e snapshot originario, produce audit e coda mail. Non esiste backfill automatico. Test manuale in `docs/QA_TESTBOOK.md`.


### Regression fix attestati storici (#558)

La PR #559 corregge il binding dei parametri nelle query di recupero e aggiunge un test Joomla end-to-end sul servizio di emissione, sull'audit e sull'idempotenza; validare entrambi i database prima del merge.


### Regressione download PDF attestati storici (issue #560)

Il renderer deve accettare timestamp persistiti SQL `DATETIME(6)` senza alterare lo snapshot; il test Joomla deve verificare il percorso positivo di primo rendering da `pending_render`, il successivo `ready`, l'asset privato, la ripetibilità del download e l'ACL. Lo storage resta obbligatoriamente configurato fuori dal document root, senza fallback pubblico.


### Storage privato gestito automaticamente — PR #563, issue #562

La configurazione standard di attestati e materiali non può dipendere dalla disponibilità di cartelle esterne alla document root. Creare al bisogno cartella gestita sotto l'installazione Joomla, file cifrati e PHP-guarded, denied direct access su Apache/LiteSpeed; preservare accesso autenticato e compatibilità dei blob esistenti in storage esterno configurato. Test su Joomla per attestato `pending_render` senza `material_storage_root`, stato `ready` e PDF effettivo; collaudo su hosting condiviso obbligatorio.


### Correzione 503 persistente PDF su shared hosting — issue #564, PR #565

La generazione e rigenerazione PDF usa direttamente lo storage gestito senza file temporanei del sistema operativo; testare `pending_render` → `ready` anche con `open_basedir`, validazione PDF e diagnostica per fase priva di dati sensibili. Copertura nei test Joomla prima del merge e collaudo manuale separato.

### Recupero puntuale attestati mancanti — sviluppo 17/11/2026

In Attestati → Corsi completati senza attestato, l'azione `recoverMissing` tratta solo `eligible_missing`: completamento persistito, versione pubblicata in modalità apprendimento, attestati previsti nello snapshot, preferenze abilitate, template esistente, nessuna emissione precedente. POST CSRF, ACL, motivazione e conferma, lock transazionale, audit, data originale e download successivo. Non è backfill in massa e non modifica `pending_render` già emessi. Test e accettazione Jira/Joomla distinti; tenere la copertura parziale fino al collaudo.

### Player YouTube focalizzato sulla lezione (integrazione novembre 2026)

Configurazione `rel=0` sul player `youtube-nocookie` per limitare le raccomandazioni al canale; riquadro nativo sotto il player con conferma solo dopo `ended` e salvataggio `lesson_completed=true`, link alla prossima lezione esclusivamente se accessibile. Nessuna sovrapposizione o alterazione dei controlli YouTube; test end-state, replay, rinnovo sessione, consensi e regressioni. Manuale `docs/VIDEO.md`, QA `docs/QA_TESTBOOK.md`, matrice requisiti `docs/REQUIREMENTS_STATUS.csv`.

### P0 — ripristino riproduzione video dopo PR #571

La sostituzione dell'iframe YouTube nello stesso spazio (PR #571) ha causato una regressione dichiarata sul sito di prova. PR #572 ripristina il runtime precedentemente collaudato (#570), lasciando il riquadro fine-lezione esterno. Non riproporre il rimpiazzo del player in 1.0.0 senza prove di avvio/Play/replay SDK reali. Il successivo miglioramento deve mantenere distinta la logica di autorizzazione e telemetria dalla presentazione UI, ed essere verificato in un browser reale Joomla prima dell'accettazione.

### P0 — replay delle lezioni video completate

Riscontro Joomla: le lezioni YouTube mai completate partono; una lezione già completata appare per un attimo e poi risulta nera, con messaggio «Lezione video completata». Correzione mirata: evitare di riapplicare il `resume_ms` finale nei grant delle lezioni completate, restituendo `lesson_completed=true` e posizione iniziale zero; preservare il resume delle lezioni parziali e tutte le prove di frequenza. Test backend per riapertura senza azzeramento e controllo frontend del seek; verifica su browser reale obbligatoria prima dell'accettazione. Priorità P0, milestone 1.0.0.

### Accettazione P0 video completati — 17 novembre 2026

La PR #573 è integrata e **collaudata positivamente su Joomla reale**: una lezione YouTube completata viene riaperta e riprodotta dall'inizio senza schermata nera. La ripresa dei video non completati era già confermata funzionante. La successiva PR di consolidamento aggiunge il test frontend esplicito sul seek, per impedire di reintrodurre la regressione; non modifica il runtime. Avanzamento globale 79,56% invariato: chiusa la specifica regressione, restano aperti i gate di accettazione non correlati.

### LCM-03 — prima tranche: archiviazione percorsi

L'editor percorso ora prevede lo stato `2 = Archiviato`, con verifica ACL `core.edit.state`, controllo di revisione e salvataggio tramite modello nativo. L'elenco rende visibile lo stato; la proiezione pubblica continua ad ammettere esclusivamente `state = 1`. Archiviare non modifica i corsi associati, le iscrizioni né i progressi; il ripristino conservativo passa per `0 = Non pubblicato`, e soltanto una successiva pubblicazione rende visibile il percorso. Test nativi Joomla coprono archive → 404 → restore → republish e persistenza associazioni. **Residuo LCM-03:** cestinazione, ripristino da cestino e cancellazione fisica condizionata con dipendenze storiche. Collaudo reale ancora richiesto.

### LCM-03 — seconda tranche: cestino e ripristino percorsi

Percorsi formativi: stato nativo `-2 = Cestinato` con controllo server-side dei permessi `core.edit.state`, filtro amministrativo dedicato, conservazione di relazioni e storico, non visibilità pubblica. Ripristino conservativo esclusivamente a `0 = Non pubblicato` prima della ripubblicazione esplicita; transizione diretta `-2 → 1` respinta dal backend. Test di integrazione Joomla su stato, accessi diretti e associazioni. Restano selezione multipla e cancellazione fisica condizionata: il requisito LCM-03 rimane Parziale e l'accettazione reale è distinta dalla CI.

### LCM-03 — azioni massive e rimozione protetta

Tranche successiva alle PR #575/#576: azioni della toolbar Joomla per cambio stato di più percorsi, con POST CSRF, ACL per record, controlli sullo stato e transazione all-or-nothing; il ripristino dal cestino resta Non pubblicato. Cancellazione fisica solo su percorso **singolo**, già cestinato e senza corsi associati, con permesso `core.delete` e conferma UI. Non sono mai cancellati corsi e iscrizioni a cascata. Sono richieste la prova sul sito reale, l'ispezione di eventuali riferimenti esterni ed evidenze di audit prima di dichiarare LCM-03 completamente accettato.

### LCM-04 — prima tranche gestione del ciclo di vita dei docenti

Implementati stati 1/0/-2 senza Archiviato, filtro di stato Cestinati, selezione multipla con toolbar Joomla e transizioni atomiche autenticabili server-side (POST/CSRF, ACL per elemento e revisione). La riattivazione dal cestino è sempre Non pubblicato; non vengono alterati account Joomla, associazioni ai corsi né snapshot didattici. Test Joomla nativi aggiunti. **Residui LCM-04:** cancellazione definitiva condizionata e audit esteso dei riferimenti storici/integrazioni, collaudo manuale rinviato per decisione dell'utente.

### LCM-04 — cancellazione definitiva protetta dei docenti

La tranche successiva alla PR #578 introduce la cancellazione singola, confermata, soltanto di un profilo docente Cestinato senza associazioni a corsi correnti o riferimenti a versioni didattiche immutabili. ACL per asset docente, CSRF, POST, checkout e verifica transazionale sono obbligatori. Nessun utente Joomla o dato didattico viene eliminato a cascata. Test nativi Joomla per divieto in presenza di assegnazioni o snapshot e per rimozione di profilo davvero inutilizzato. **Residuo:** audit dei riferimenti esterni/estensioni e accettazione Joomla reale; collaudi manuali differiti dall'utente.

### LCM-05 — primo incremento test di isolamento percorsi (17/11/2026)

Estendere `tests/joomla-pathways.php` con accessi diretti anonimi negati ai percorsi archiviati e cestinati e controlli di invariabilità degli stati dei corsi associati durante archiviazione, cestinazione ed eliminazione del percorso vuoto. Non modificare runtime, percentuale di avanzamento o stato di accettazione. Restano nel gate LCM-05: ACL/CSRF per controller, catalogo/Finder/SEO, audit endpoint, upgrade e collaudi Joomla reali.

### LCM-05 — secondo incremento: autorizzazioni server-side percorsi

Estendere il test disposable Joomla dei percorsi con tentativi anonimi di ripristino massivo e cancellazione definitiva di un percorso vuoto già cestinato. Il modello deve negare entrambe le mutazioni conservando stato, record e associazioni. Controlli HTTP/CSRF, ACL specifici, Finder, upgrade e collaudo reale rimangono aperti. Nessun cambiamento runtime o incremento della percentuale storica sulla base del solo test.

### LCM-05 — HTTP/CSRF su controller percorsi (terzo incremento, 17/11/2026)

Il test HTTP disposable esercita le sei azioni amministrative di cambio stato e cancellazione di un percorso senza passare dalla toolbar: GET deve essere respinto con HTTP 405; POST senza token CSRF con HTTP 403. Il percorso deve rimanere leggibile dall'elenco amministrativo dopo il rifiuto. Non è una modifica runtime. Restano ACL per record, casi con token valido e malformato, Finder/SEO e collaudi manuali.

### LCM-05 — quarto incremento: transizioni HTTP autorizzate dei percorsi

Estendere il fixture HTTP Joomla per esercitare archive → restore (Non pubblicato) → publish con il token CSRF recuperato dal modulo amministrativo nativo. Controllare la visibilità pubblica dopo ogni operazione e preservare il percorso pubblicato per i test già presenti. Nessun cambiamento runtime. Restano ACL per record, token scaduti, Finder/SEO, upgrade e collaudo manuale.

### Misurazione soddisfacimento requisiti 1.0.0 — ricognizione strutturata

L'indicatore principale di sviluppo deve essere calcolato da criteri approvati, non da medie discrezionali delle macroaree né dal volume di PR. `docs/FUNCTIONAL_CRITERIA_1.0.0.json` avvia la ricognizione con evidenze puntuali per LCM-03/04/05; `tools/functional_progress.py` blocca la pubblicazione della percentuale globale fino al completamento dell'audit dell'intera baseline in perimetro. Il 79,56% è solo **stima storica non riconciliata**, da sostituire quando ogni requisito è stato valutato. Non introdurre un cambio SemVer per attività che non modificano il pacchetto distribuito; rispettare issue #160 e le regole release già approvate.

### Seconda tranche di riconciliazione funzionale — catalogo e authoring

Esteso `FUNCTIONAL_CRITERIA_1.0.0.json` a CAT-01/02/03/04 e AUT-01/02/03/04/05, con 42 criteri per le due aree. Tre assi distinti: criterio implementato con riferimenti a codice/test; criterio da verificare; collaudo Joomla reale. La nuova copertura di audit è 12/214 requisiti in perimetro; non interpretarla come percentuale di software completato. Prossima ricognizione: iscrizioni, avanzamento didattico, materiali/video, notifiche email, attestati e integrazioni; riesaminare le righe LCM-01/02 ancora erroneamente indicate come `Non iniziata` se il codice e gli scenari dimostrano implementazioni già presenti.

### Terza tranche audit criteri funzionali: accessi/iscrizioni, esperienza studente, avanzamento

Scomposti in `FUNCTIONAL_CRITERIA_1.0.0.json` 15 requisiti ISC/STU/PRO del perimetro 1.0.0 (47 criteri; 31 con evidenze iniziali, 16 da verificare). Verificare successivamente l'aderenza puntuale di ciascun criterio, con particolare attenzione a ACL e revoche di sessione, rinnovo e versioni, anteprime, percorsi di ripresa, disponibilità e concorrenza multidispositivo. Le attività espressamente rinviate alla fase 2 sono escluse dal calcolo di 1.0.0. Il valore globale rimane `null` finché il perimetro completo non sarà verificato. La prossima tranche comprende lezioni, video, materiali, email, attestati e integrazioni.

### Quarta tranche audit criteri: lezioni, player video, materiali

Aggiungere alla riconciliazione `FUNCTIONAL_CRITERIA_1.0.0.json` i requisiti LES-01–04, VID-01–08 e MAT-01–08 con 68 criteri espliciti. Per ogni criterio implementato registrare almeno un riferimento verificabile a codice o test e lasciare `unassessed` i casi senza copertura sufficientemente stabilita. Riesaminare successivamente casi reali di provider YouTube/Vimeo/Cloudflare/HTML5, sicurezza file, Range, accessi e versionamento; non attribuire collaudi manuali. Copertura audit progressiva 47/214, **non** percentuale di sviluppo. Prossime aree: email/notifiche, attestati, integrazione Joomla e altri gate core. La governance SemVer vigente non cambia.

### Quinta tranche dell'audit funzionale: MAIL/NOT, CERT e JOO

L'audit per criteri in `FUNCTIONAL_CRITERIA_1.0.0.json` copre 73/214 requisiti 1.0.0, inclusi i 26 requisiti notifiche, attestati e Joomla della quinta tranche. Sono necessari riesame dei criteri `unassessed`, prove SMTP e download PDF, controllo ACL, idempotenza, installazione e backup/upgrade. I requisiti ATT di fase 2 sono deliberatamente esclusi; CERT riguarda invece l'attestato core della 1.0.0. L'indicatore globale rimane non calcolabile fino alla riconciliazione completa e non deve essere confuso con la copertura dell'audit. Il versioning SemVer approvato resta invariato.

### Sesta tranche — revisione funzionale sicurezza/integrazioni core

Sono scomposti in `FUNCTIONAL_CRITERIA_1.0.0.json` tutti i requisiti `SIC-01/02`, `PRI-01–03`, `CFG-01–06`, `CORE-01–23`, per 75 criteri. Copertura progressiva 107/214, non percentuale completamento del software. Rimangono 107 requisiti da decomporre, più riesame semantico ed esame dei criteri `unassessed`. Le verifiche sull'autenticazione, ACL, privacy, cache, hosting e upgrade non si considerano superate dalla mera esistenza di servizi o test. Nessuna nuova versione applicativa per modifiche all'audit; rispettare SemVer concordato.

### Settima tranche di ricognizione: UI, accessibilità e performance

Il registro dei criteri è esteso a 129/214 requisiti 1.0.0, includendo STU-05, PRO-04/05, ACC-01, UI-01–07 e PERF-01–11. Restano **85 requisiti non scomposti**, criteri `unassessed` da indagare e verifica semantica delle prime evidenze. I vincoli prestazionali (utenti concorrenti, scenario 60/20/15/5, p95) e WCAG non si certificano con la sola presenza di CSS, codice di paginazione o test unitari: occorrono misure e audit dedicati su ambienti rappresentativi. Mantenere la percentuale globale nulla e la versione SemVer invariata per PR di sola documentazione/test.

**DOC-03/criterion_03 — schermate illustrative accessibili (PR #1082, 27/11/2026):** quattro illustrazioni SVG schematiche per catalogo, area personale, aula e profilo docente, con descrizione SVG e testo alternativo nelle guide. Gate automatico in `tools/check.py`; stato criterio non promosso prima della CI. Le illustrazioni sono fittizie, non catture del sito. Confronto con UI installata e walkthrough non sviluppatore restano accettazione manuale. Dashboard invariata finché il gate non passa.
