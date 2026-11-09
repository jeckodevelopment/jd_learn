# Testbook manuale Joomla — JD Learn

Registro cumulativo delle verifiche da eseguire su un **sito Joomla di prova usa e getta**. Si aggiorna a ogni PR con comportamenti runtime, aggiungendo le prove descritte nella PR e mantenendo il collegamento al requisito/scenario esistente. Non crea criteri funzionali nuovi. Un test è superato solo dopo esecuzione manuale sul sito e registrazione dell’esito qui; CI e test HTTP sono evidenze separate.

## Come registrare un’esecuzione

Prima del test annotare: data, versione e checksum del pacchetto, versione/patch Joomla, template e browser, ruoli/account e dati di prova. Per ogni prova indicare **Superato / Fallito / Bloccato**, breve esito e riferimento a screenshot/log senza dati personali. Usare account e contenuti fittizi; per prove con alterazioni DB usare esclusivamente un’istanza eliminabile. Dopo upgrade verificare anche i dati preesistenti richiesti dalla procedura.

| Campo | Valore da compilare |
|---|---|
| Esecuzione / operatore | Da compilare |
| Data e ora | Da compilare |
| Build / versione / SHA-256 pacchetto interno | Da compilare |
| Joomla / PHP / database | Da compilare |
| Template / browser / viewport | Da compilare |
| Account e permessi usati | Da compilare, senza password |
| Esito complessivo / evidenze | Da compilare |

## PR #132/#134 — lezioni audio autonome e ripresa posizione

Usare corso e account fittizi su Joomla di prova. Il candidato aggiunge il tipo lezione Audio, file MP3/OGG e riproduzione con URL privata.

1. Con un docente autorizzato, creare bozza, modulo e lezione Audio; verificare tipo e traduzioni IT/EN.
2. Tentare la pubblicazione senza file audio pronto; atteso: preflight e pubblicazione bloccati.
3. Caricare un MP3 o OGG nella gestione materiali della lezione; verificare rifiuto di WAV e di file MP3 rinominati. Pubblicare.
4. Con studente iscritto, riprodurre e cercare in avanti/indietro, quindi segnare completata; verificare che il progresso persista.
5. Provare accesso al player da anonimo, utente non iscritto, lezione differente e allegato disattivato; atteso: nessun contenuto. Verificare richieste Range durante seek e risposta privata/no-store.
6. Impostare una disponibilità futura per l’allegato e verificare che il player non fornisca audio prima dell’inizio.
7. Con la versione 0.41.34-dev, ascoltare per almeno 15 secondi, mettere in pausa e ricaricare la lezione: atteso, ripresa dall’ultimo punto confermato. Spostarsi indietro e ricaricare: il nuovo punto deve riflettere il seek confermato. Controllare che il completamento richieda ancora il pulsante esplicito e che il solo ascolto non completi lezione o corso.
8. Riaprire la stessa lezione con un altro utente/iscrizione: il punto di ripresa deve restare isolato per iscrizione. Alterare i parametri di lezione/iscrizione o richiamare l’endpoint senza sessione/CSRF: atteso, richiesta negata e nessun dato di progresso esposto.

**Stato:** PR #132 integrata in 0.41.33-dev nel commit `2d0984bc4adf0ee420eed3cdc7a0356f97813e39`. CI standard superata nel [run 42609983073](https://github.com/jeckodevelopment/jd_learn/actions/runs/42609983073): controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. Artefatto [JD-Learn-0.41.33-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42609983073/artifacts/13669353295), wrapper da estrarre una volta; SHA-256 interno `6e80193338036652f6fa1550da29ae9859bfc8a30bb3b47f354111429de09880`, wrapper `fff003d89ecac607a5a57b7b29972dcd56a3c46cecb2dbc00bcec550aa1073e9`. Nessun collaudo manuale Joomla registrato. La PR [#134](https://github.com/jeckodevelopment/jd_learn/pull/134) integra la ripresa audio in 0.41.34-dev nel commit `ba703f361a8530a0d8f4fb6805025492f78657d6`. CI standard 4/4 superata nel [run 42613814879](https://github.com/jeckodevelopment/jd_learn/actions/runs/42613814879). Pacchetto [JD-Learn-0.41.34-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42613814879/artifacts/13671314050), wrapper da estrarre una volta, SHA-256 interno `972b74d91132503634538d2eec68edb149cee7e89fa00b438255ccbd1981f9f1`, scade il 23 novembre 2026. Nessun collaudo manuale Joomla registrato; ascolto/fine player non completano automaticamente. Trascrizioni non incluse. Trascrizioni non incluse.

## Registro PR

### PR #130 — lezioni-documento

Riferimenti: M01/M02, LES-01–04, MAT-01–08. Usare corso e account fittizi su Joomla di prova.

1. Con ruolo docente autorizzato, creare una bozza, un modulo e una lezione di tipo **Documento**. Verificare che il tipo sia disponibile in italiano e inglese e che il form salvi senza mostrare i campi provider video.
2. Tentare la pubblicazione senza allegati della lezione. Atteso: pubblicazione negata e blocco di preflight che richiede un documento.
3. Aprire **Materiali** dalla lezione, caricare un PDF, attendere lo stato pronto e pubblicare. Atteso: pubblicazione completata, con le normali verifiche di scansione e disponibilità.
4. Con uno studente iscritto, aprire la lezione, scaricare il file protetto e segnare la lezione completata. Atteso: avanzamento persistente e completamento corso coerente.
5. Provare il download da sessione anonima e da account non iscritto. Atteso: accesso negato; date di disponibilità e scansione obbligatoria sono rispettate.

**Stato:** PR #130 integrata nel commit `0c3c0109681cdaca6673dded3c10941c06a2ae20`; CI standard superata nel [run 42601315214](https://github.com/jeckodevelopment/jd_learn/actions/runs/42601315214). Pacchetto [JD-Learn-0.41.32-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42601315214/artifacts/13666127495), wrapper da estrarre una volta; checksum SHA-256 pacchetto interno `3cc98f6837810f72c366b98a4ec28d2c4645ccd78d03ec546ef336d44c351a71`. Nessun esito manuale registrato.


### PR #100 — attivazione e lingue del plugin Privacy Joomla

Riferimenti: PRI-02, CORE-08; correzione del comportamento già incluso, senza introdurre nuove capacità privacy. Usare due installazioni Joomla usa e getta: una pulita e una con JD Learn già installato e `JD Learn - Privacy` intenzionalmente disabilitato.

1. Installazione pulita: installare il pacchetto completo 0.41.21-dev e aprire **Sistema → Gestisci → Plugin**. Atteso: **JD Learn - Privacy** risulta abilitato; i quattro plugin video conservano la loro procedura di configurazione e stato previsto.
2. Aprire **Utenti → Privacy → Funzionalità** con lingua amministrativa italiana. Atteso: titolo **JD Learn** e descrizioni leggibili per iscrizioni/richieste, progressi, tentativi/risposte e attestati; nessuna chiave `PLG_PRIVACY_...` visibile.
3. Cambiare la lingua amministrativa in inglese e riaprire la pagina. Atteso: gli stessi quattro elementi sono tradotti in inglese, senza chiavi grezze.
4. Sul sito già installato, disabilitare intenzionalmente il plugin e aggiornare il pacchetto. Atteso: il plugin resta disabilitato; gli aggiornamenti non sovrascrivono la scelta dell'amministratore. Riabilitarlo per i controlli successivi.
5. Con un account di prova soggetto a un impedimento di rimozione (documento attestato, job o notifica), richiedere la rimozione privacy. Atteso: il motivo è localizzato nella lingua backend e nessun dato cambia prima della risoluzione dell'impedimento.
6. Con dati privacy di prova, verificare che export e rimozione continuino a mostrare i domini tradotti e a rispettare i comportamenti di conservazione descritti nella sezione PR #92.

**Stato:** PR #100 integrata in main 0.41.20-dev; la CI originale non copriva lo stato reale di attivazione né la traduzione. Le regressioni della PR #102 hanno identificato l'hook package inefficace e poi il caricamento lingua non risolto. PR #102 ha spostato l'attivazione nel postflight del manifest plugin e carica `plg_privacy_jdlearn` nella lingua usata da Joomla. CI completa superata sulla revisione integrata in 0.41.21-dev (run [42503477910](https://github.com/jeckodevelopment/jd_learn/actions/runs/42503477910)). **Esito sito Joomla:** ricollaudo ancora da eseguire, incluse entrambe le lingue e l'upgrade con plugin intenzionalmente disabilitato.

### PR #102 — regressione installazione e traduzioni Privacy

PR di correzione e test per PRI-02/CORE-08. La suite prima di questa PR preattivava il plugin nel fixture e lo forzava nuovamente attivo subito dopo l'installazione, nascondendo il difetto. La build **0.41.21-dev** sposta l'attivazione nel postflight del plugin e carica il dominio lingua esplicito; gli upgrade mantengono lo stato esistente. PR [#102](https://github.com/jeckodevelopment/jd_learn/pull/102) è stata integrata in `0a05f004823156abc750981a11db6598a8cf9972`; CI standard superata.

1. Su un Joomla 6 usa e getta pulito installare 0.41.21-dev. Atteso: **JD Learn - Privacy** è attivo; YouTube, Vimeo, Cloudflare Stream e HTML5 non vengono attivati automaticamente.
2. Aprire **Utenti → Privacy → Funzionalità** in italiano e inglese. Atteso: titolo e quattro descrizioni tradotti, nessuna chiave tecnica.
3. Su un'installazione esistente disattivare manualmente JD Learn - Privacy e aggiornare a 0.41.21-dev. Atteso: rimane disattivato; l'upgrade rispetta la scelta dell'amministratore.
4. Con utente di prova bloccato da documento attestato/job/notifica, verificare motivo localizzato e nessuna modifica ai dati finché l'impedimento resta.
5. Ripetere export e rimozione con dati di prova e verificare domini, minimizzazione e conservazione descritti in PR #92.

**Stato:** i quattro job standard della CI sono superati sul commit PR `8b962c62f3af95100c1da7b1794297df66452d78`, incluso il test install-package senza preattivazione e lo smoke test delle stringhe. Il pacchetto installabile è [artefatto CI 13626378474](https://github.com/jeckodevelopment/jd_learn/actions/runs/42503477910/artifacts/13626378474), SHA-256 interno `40c1743ba9556a6fd96eeba9da1c403edc2f67c91d9b93773daac539c9f380f7`. Collaudo manuale Joomla ancora da registrare.

### Issue #113 — ACL della sidebar nativa (PR #128, 0.41.31-dev)

Riferimenti: [issue #113](https://github.com/jeckodevelopment/jd_learn/issues/113), CORE-20 / AC-132. Il plugin `plg_system_jlmsmenu` deve filtrare le voci della sidebar secondo i permessi già applicati alle destinazioni senza cambiare gli altri menu Joomla.

1. Su una Joomla 6 usa e getta, installare il pacchetto pulito. In **Sistema → Gestisci → Plugin**, verificare che **JD Learn - ACL menu amministrativo** sia abilitato.
2. Con account completo, verificare le voci Dashboard, Corsi, Categorie, Banca domande, Docenti, Campi aggiuntivi e Plugin video.
3. Con account che ha `core.manage` su `com_jlms` ma non `jlms.quiz.manage`, verificare che Banca domande non compaia in sidebar né dashboard; provare l’URL diretto e verificare il diniego.
4. Rimuovere a turno i permessi `core.manage` su `com_jlms.course`, `com_fields` e `com_plugins`. Atteso: scompaiono dalla sidebar e dashboard rispettivamente Categorie corsi, Campi aggiuntivi e Plugin video.
5. Verificare che i menu di Joomla non collegati a JD Learn, incluse le categorie contenuti, restino invariati.
6. Aggiornare un sito con plugin attivo: deve restare attivo. Disattivarlo e aggiornare di nuovo: deve restare disattivato.

**Stato:** PR #128 integrata in main 0.41.31-dev nel commit `26c7ad5264dfbbdbdab9c446162220567df2d30e`; CI standard superata nel [run 42594246627](https://github.com/jeckodevelopment/jd_learn/actions/runs/42594246627), inclusi test installazione/upgrade e preservazione dello stato plugin. Collaudo sul sito Joomla di prova ancora pendente.

### Issue #105 — dashboard amministrativa

Riferimenti: [issue #105](https://github.com/jeckodevelopment/jd_learn/issues/105), CORE-20, UI-02, SCR-13 e AC-132. La dashboard deve agevolare l'accesso alle sole funzioni già presenti; statistiche e attestati sono fase 2.

1. Su un Joomla 6 di prova, aprire **Componenti → JD Learn**. Atteso: si apre la dashboard, non l'elenco corsi.
2. Verificare i riquadri con icone per Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video. Aprire ciascun collegamento; deve arrivare alla relativa gestione nativa e conservare filtri/contesti previsti.
3. Con un account limitato, verificare che i riquadri senza permesso non compaiano. Provare l'URL diretto: Joomla deve negare l'accesso. Ripetere con account autorizzato per ciascuna destinazione.
4. Verificare tutte le etichette in italiano e inglese, navigazione da tastiera, focus visibile e griglia su finestra desktop e stretta/mobile.
5. Espandere **Stato dei provider video**: il riepilogo esistente deve continuare a mostrare provider e configurazione senza diventare un nuovo flusso o requisito.
6. Verificare che non compaiano riquadri Statistiche o Attestati, rinviati alla fase 2.

**Anomalie rilevate sul pacchetto 0.41.23-dev e correzioni incluse in 0.41.24-dev:** la voce radice **JD Learn** nella sidebar apriva ancora Corsi dopo l'aggiornamento; i riquadri potevano portare a un URL con `&amp;` letterale; l'introduzione mostrava una frase sullo storico didattico non pertinente alla dashboard. Verificare la correzione con queste regressioni:

7. Aggiornare il sito da 0.41.23-dev a 0.41.24-dev. Fare clic sulla voce radice **JD Learn** della sidebar. Atteso: si apre la dashboard; Corsi resta una voce figlia distinta, senza duplicati.
8. Aprire tutti i sei riquadri. Atteso: ogni indirizzo nel browser contiene parametri separati da `&`, porta alla vista corretta e non contiene il testo letterale `&amp;`.
9. Verificare l'introduzione in italiano e inglese. Atteso: descrive gli strumenti amministrativi e non mostra «Storico didattico e disponibilità attuale dell’aula sono mostrati separatamente.»
10. Verificare nel sottomenu laterale di JD Learn che **Dashboard** sia la prima voce, sopra **Corsi**; fare clic e controllare che apra la dashboard. Ripetere in italiano e inglese e dopo l'aggiornamento da 0.41.24-dev.

**Stato:** PR [#108](https://github.com/jeckodevelopment/jd_learn/pull/108), [#110](https://github.com/jeckodevelopment/jd_learn/pull/110) e [#112](https://github.com/jeckodevelopment/jd_learn/pull/112) integrate; tutti i quattro job CI standard superati nel [run 42520306552](https://github.com/jeckodevelopment/jd_learn/actions/runs/42520306552). L’utente ha comunicato il superamento del collaudo manuale Dashboard in data 8 novembre 2026; non sono stati forniti dettagli aggiuntivi su versione Joomla, browser o account.

**Aggiornamento issue #113 / PR #126 integrata in 0.41.30-dev:** la scorciatoia Banca domande richiede anche `jlms.quiz.manage`; Categorie corsi e Campi aggiuntivi richiedono i rispettivi permessi di gestione e l’accesso a JD Learn. CI standard superata nel [run 42589362926](https://github.com/jeckodevelopment/jd_learn/actions/runs/42589362926), quattro job verdi. Il pacchetto [JD-Learn-0.41.30-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42589362926/artifacts/13660408807) contiene il package installabile nel wrapper ZIP da estrarre una volta; checksum SHA-256 interno `5c43dff45c5d121e4f96aa897926e7db8fbf7fec6799406cb7b2a512aab5770c`, 511075 byte; artefatto in scadenza il 23 novembre 2026. Il sottomenu amministrativo nativo non è ancora filtrato per le azioni specifiche: il collaudo della visibilità delle voci sidebar resta aperto.

Per il controllo aggiuntivo, usare un gruppo amministrativo di prova con `core.manage` su JD Learn ma senza `jlms.quiz.manage`, quindi confrontarlo con un account autorizzato:

11. Aprire la dashboard con entrambi gli account. Atteso: **Banca domande** compare soltanto per l’account autorizzato; i link a categorie/campi appaiono solo se sono concessi anche i rispettivi permessi nativi.
12. Con l’account senza `jlms.quiz.manage`, provare l’URL diretto della banca domande. Atteso: accesso negato dal componente. Verificare inoltre che la sidebar nativa possa ancora mostrare la voce: questa parte resta da completare nell’issue #113.

### Issue #99 — accesso centralizzato ai campi aggiuntivi

Riferimento: CORE-10 / AC-129 e [issue #99](https://github.com/jeckodevelopment/jd_learn/issues/99). Usare un Joomla 6 di prova con un ruolo che abbia `core.manage` su `com_fields`, e un ruolo di confronto senza tale permesso. La correzione non crea campi o gruppi: verifica solo navigazione e filtro dei contesti nativi.

1. Aprire JD Learn → **Campi aggiuntivi** nella sidebar. Atteso: si apre la lista nativa di Joomla `com_fields`, inizialmente nel contesto corsi.
2. Usare il filtro contesto della lista e scegliere in sequenza Corsi, Docenti e Lezioni. Atteso: il contesto selezionato resta quello della rispettiva entità JD Learn e la lista mostra solo i campi di quel contesto.
3. Tornare a Corsi e Docenti. Atteso: le liste non mostrano più i link ai campi aggiuntivi né elementi inseriti fuori dalla toolbar; ricerca, filtri e azioni esistenti restano disponibili.
4. Aprire Programma e verificare che il collegamento contestuale ai campi delle lezioni continui a portare alla lista nativa nel contesto lezioni.
5. Con il ruolo privo di `core.manage` su `com_fields`, provare la voce sidebar e l'URL diretto. Atteso: Joomla nega la gestione dei campi; non si ottiene accesso cambiando il contesto nell'URL.

**Stato:** PR [#104](https://github.com/jeckodevelopment/jd_learn/pull/104) integrata nel commit `4a924e87c046d076cd48ec2e63d125f5d25657e1`; CI standard superata nel run [42507760027](https://github.com/jeckodevelopment/jd_learn/actions/runs/42507760027). Il pacchetto [0.41.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42507760027/artifacts/13628566353) ha SHA-256 interno `2d02d554f134e10087df79cb45fa334b68c9b809b4fa2794b34993d6f817b388`; scade il 22 novembre 2026. Collaudo manuale Joomla ancora da eseguire e registrare.

### AC-129 — accesso ai campi aggiuntivi di corsi, docenti e lezioni (implementato; collaudo manuale pendente)

Riferimento: CORE-10; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native).

1. Da JD Learn, aprire la voce sidebar **Campi aggiuntivi**; la lista Joomla deve aprirsi sui campi corsi.
2. Usare il filtro contesto per scegliere Corsi, Docenti e Lezioni; ciascuna selezione deve mostrare il contesto corretto.
3. Verificare che i link non compaiano più negli elenchi Corsi e Docenti e che Programma mantenga l’accesso contestuale alle lezioni.
4. Con un ruolo senza `core.manage` su `com_fields`, verificare il diniego dalla voce e dall’URL diretto.

**Stato:** PR #104 integrata in main 0.41.22-dev; CI standard superata. Il collaudo manuale è dettagliato nella sezione Issue #99 e resta da registrare.

### AC-130 — vista standard per i docenti (PR #116 integrata in 0.41.26-dev; collaudo Joomla pendente)

Riferimenti: AUT-01, SCR-13; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native).

1. Aprire Docenti: deve apparire un elenco, con form di modifica/creazione distinto e accesso a quest’ultimo mediante toolbar/azione standard.
2. Provare ricerca per titolo e ID (`id:...`), filtro pubblicato/non pubblicato, lingua, ordinamento per titolo/stato/lingua/modifica/ID, paginazione e ritorno all’elenco; controllare che risultati e totali corrispondano ai filtri.
3. Aprire e salvare un profilo esistente, quindi annullare una modifica: verificare persistenza e annullamento senza cambiare associazioni al corso.
4. Ripetere con ruoli autorizzati e non autorizzati. Verificare navigazione da tastiera e viewport mobile.
5. Controllare che nessuna categoria docente sia richiesta o esposta.

**Stato:** PR [#116](https://github.com/jeckodevelopment/jd_learn/pull/116) integrata; tutti i quattro job della [CI standard 42524671164](https://github.com/jeckodevelopment/jd_learn/actions/runs/42524671164) superati. Il pacchetto [0.41.26-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42524671164/artifacts/13634039771) ha SHA-256 interno `869c40a705602af9c1c7e2bd1562e407fce75d38038cefcf67085156c3230ab5` (503665 byte), disponibile fino al 22 novembre 2026. La lista usa Search Tools e toolbar standard Joomla; il form resta separato e accessibile dal titolo/toolbar. La categoria docente non è esposta. Verificare ACL e ricerca sul sito; il cambio stato del profilo resta disponibile nel form secondo i permessi, senza introdurre azioni massive.

### AC-131 — elenco separato per la banca domande (PR #120 integrata in 0.41.27-dev; collaudo Joomla pendente)

Riferimenti: QUI-02, SCR-13; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native). Questa prova riguarda l’amministrazione delle domande esistenti, non nuovi comportamenti didattici quiz.

1. Aprire Banca domande: deve mostrare l’elenco senza il form di creazione incorporato; usare il comando standard per aprire separatamente il form di nuova domanda.
2. Tornare all’elenco e aprire una domanda esistente in modifica; annullare e salvare una modifica controllando stato e feedback.
3. Duplicare una domanda: deve essere creata una nuova definizione indipendente, lasciando invariata la sorgente e le revisioni/tentativi già esistenti.
4. Eliminare una domanda senza dipendenze: richiedere conferma e verificare che domanda, revisioni e asset siano eliminati insieme. Ripetere con domanda usata da quiz o tentativi: il comando è disabilitato e la richiesta diretta deve essere bloccata, senza cancellazioni parziali.
5. Verificare ricerca per testo/ID, filtri categoria/stato/lingua, ordinamento, paginazione, toolbar, CSRF e diniego con ruolo non autorizzato. Ripetere in italiano/inglese, da tastiera e viewport mobile.

**Stato:** PR [#120](https://github.com/jeckodevelopment/jd_learn/pull/120) integrata in main 0.41.27-dev; tutti e quattro i job della [CI standard](https://github.com/jeckodevelopment/jd_learn/actions/runs/42527985085) superati su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium controllato. Il pacchetto [JD-Learn-0.41.27-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42527985085/artifacts/13636515713) contiene lo ZIP installabile, SHA-256 interno `2d5f53f22445eda2445025346fc42878d7652124befd7dc0bd17dd7224ff4506` (509376 byte), disponibile fino al 22 novembre 2026. La lista e il form sono separati, con Search Tools e azioni crea/duplica/elimina. Le duplicazioni creano una bozza indipendente; l’eliminazione è permessa solo se nessuna revisione è usata da quiz o tentativi. Nessun nuovo tipo di domanda, regola di valutazione o flusso studente è incluso. Collaudo manuale Joomla ancora pendente.

### AC-133 — conteggio corsi e filtro docente (issue #117)

Riferimento: [issue #117](https://github.com/jeckodevelopment/jd_learn/issues/117), AUT-01 e SCR-13. Usare un Joomla 6 di prova con un corso assegnato a un docente e un utente amministrativo autorizzato a gestire quel corso. Preparare anche un account con ambito ACL più ristretto.

1. Aprire JD Learn → Docenti in italiano. Atteso: intestazione backend e titolo della scheda browser mostrano **Docenti**, senza `COM_JLMS_TEACHERS`. Ripetere in inglese e verificare **Teachers**.
2. Verificare la colonna Corsi per docenti con corsi assegnati e senza corsi. Il numero deve corrispondere ai corsi accessibili nella vista corrente. Fare clic sul numero: si apre Corsi e restano solo quelli assegnati a quel docente.
3. Aprire JD Learn → Corsi e usare il filtro Docente. Selezionare il docente di prova: devono apparire solo i corsi a lui associati. Combinare il filtro con ricerca, stato e categoria; azzerare i filtri e verificare che l’elenco torni completo.
4. Ripetere con l’account a permessi ridotti. Conteggio e link devono mostrare solo corsi gestibili da quell’account; un ID docente arbitrario o una modifica manuale dell’URL non devono ampliare i risultati. I profili non visibili non devono esporre dettagli privati nelle opzioni del filtro.
5. Verificare navigazione da tastiera, testi accessibili del conteggio e resa della colonna su viewport desktop e stretta.

**Stato:** issue #117 / AC-133 è integrata nella 0.41.28-dev dalla PR #122; collaudo manuale Joomla pendente.

### AC-134 — traduzioni della pagina Opzioni (issue #115)

Riferimento: issue [#115](https://github.com/jeckodevelopment/jd_learn/issues/115), CORE-20. Usare Joomla 6 di prova con interfaccia amministrativa disponibile in italiano e inglese e un Super User autorizzato alle Opzioni del componente.

1. Impostare l’italiano come lingua amministrativa dell’account e aprire **Componenti → JD Learn → Opzioni**. Atteso: titolo della scheda browser e intestazione mostrano **JD Learn: Opzioni**.
2. Controllare la scheda e il titolo del gruppo Cronologia versioni. Entrambi mostrano **Opzioni cronologia versioni**; la chiave `JGLOBAL_HISTORY_OPTIONS` non compare.
3. Cambiare la lingua amministrativa dell’account in inglese e riaprire la stessa pagina. Atteso: titolo **JD Learn: Options** e label **Version History Options** in entrambi i punti; nessuna chiave grezza.
4. Verificare che le altre schede/campi delle Opzioni continuino a essere tradotti e che i valori salvati (inclusi versioni e limite cronologia) restino invariati durante la sola navigazione.

**Stato:** issue #115 / AC-134 integrata dalla PR [#124](https://github.com/jeckodevelopment/jd_learn/pull/124) in 0.41.29-dev; tutti i quattro job CI standard sono superati nel [run 42533814214](https://github.com/jeckodevelopment/jd_learn/actions/runs/42533814214). Collaudo manuale Joomla pendente.

### PR #94 — ricerca roster (deviazione di perimetro, non gate di accettazione)

Riferimenti: [PR #94](https://github.com/jeckodevelopment/jd_learn/pull/94), [AC-128](RELEASE_SCOPE_MATRIX.md#scenario-specifico-del-roster-core). La ricerca è stata aggiunta senza precedente identificazione nel backlog del primo rilascio. È inclusa nel pacchetto main per effetto del merge, ma questo testbook **non la promuove a requisito**: registrare il suo controllo come regressione informativa finché il proprietario del prodotto non decide se mantenerla.

1. Con un operatore autorizzato, aprire il roster di un corso e cercare un nome visualizzato e uno username presenti. Atteso: solo iscritti di quel corso.
2. Combinare ricerca e filtri già presenti; provare anche una stringa senza corrispondenze. Atteso: filtri combinati e stato vuoto coerenti.
3. Provare `%`, `_`, `!`, input lungo e caratteri di controllo. Atteso: caratteri jolly interpretati come testo, limite/validazione applicati, nessun errore tecnico.
4. Ripetere con operatore non autorizzato e con un corso fuori ambito. Atteso: accesso negato; nessun dato di altri corsi.

**Esito sito Joomla:** Non eseguito. **Decisione di perimetro:** Da riesaminare; non è requisito completato.

### PR #93 — percorsi formativi core (catalogo e progresso)

Riferimenti: [PR #93](https://github.com/jeckodevelopment/jd_learn/pull/93), CAT-03, SCR-04, EDGE-04. Il corpo PR definisce le prove 1–7; i test automatici non sostituiscono queste prove.

1. Creare una categoria da **JD Learn → Categorie percorsi**, creare un percorso con più corsi e verificare ordine/dati dopo la riapertura.
2. Pubblicare il percorso e controllare catalogo e scheda pubblica.
3. Come studente, verificare stato dei singoli corsi e aggregazione uniforme; corsi senza progresso contribuiscono zero.
4. Come ospite, controllare che non sia mostrata percentuale personale. Un corso promosso ma con livello contenuto riservato deve restare ristretto e pesare zero.
5. Aggiungere un corso in bozza/non visibile. Atteso: nessun titolo o ID rivelato; il percorso non iscrive né concede accesso.
6. Controllare un percorso vuoto: nessuna percentuale o completamento.
7. In modifica, verificare che la categoria resti bloccata.

**Esito sito Joomla:** Non eseguito. **CI/HTTP/upgrade-retention:** superati nel run standard associato a main 0.41.18.

### PR #92 — lifecycle Privacy Joomla

Riferimenti: [PR #92](https://github.com/jeckodevelopment/jd_learn/pull/92), PRI-02, AC-15/63.

1. Verificare installazione/attivazione di `plg_privacy_jdlearn` e capacità elencate in **Componenti → Privacy**.
2. Richiedere export per un utente di prova con iscrizione, progresso e tentativo: controllare domini e dati esportati; non devono apparire sessioni o token playback.
3. Con nessun documento identificativo, job o notifica bloccante, eseguire la rimozione: controllare unlink account, eliminazione delle risposte/motivi testuali, conservazione dello storico minimizzato e impossibilità di accesso.
4. Ripetere con ciascun impedimento previsto (documento identificativo, job proprietario, notifica): Joomla deve spiegare il motivo e i dati devono restare invariati.
5. Dopo upgrade, verificare conservazione dei dati e stato degli altri plugin.

**Esito sito Joomla:** Non eseguito. La policy retention e il purge dei backup restano responsabilità del sito, come indicato dalla PR.

### PR #90 — registry provider video

Riferimenti: [PR #90](https://github.com/jeckodevelopment/jd_learn/pull/90), PVD-01/06 e VID-01. Per la prima stabile verificare i **quattro plugin distribuiti**: YouTube, Vimeo, Cloudflare Stream e HTML5. Il test di un quinto plugin è esplicitamente fase 2 e non è un gate di rilascio.

1. Installare/aggiornare il pacchetto su Joomla di prova; controllare i quattro plugin nel gestore plugin e abilitarli/configurarli secondo le guide del provider.
2. Per ciascun provider, salvare un materiale/lezione e controllare che il provider registrato sia disponibile in authoring e riepilogo del programma.
3. Pubblicare e fruire il video con consenso e diritto di accesso validi; verificare player corretto e progressi secondo la configurazione.
4. Ripetere senza consenso e senza autorizzazione di fruizione: il player/media non deve caricarsi né concedere accesso.

**Esito sito Joomla:** Non eseguito. Il caso #5/plugin ulteriore è fase 2, fuori dal gate.

### PR #88 — migrazione esplicita di una singola iscrizione

Riferimenti: [PR #88](https://github.com/jeckodevelopment/jd_learn/pull/88), ciclo versioni/iscrizioni incluso nella matrice di rilascio. 

1. Pubblicare v1, avviare un’iscrizione studente e completare una lezione.
2. Pubblicare v2 copiando il programma; nel roster dell’iscrizione v1 selezionare **Migrare alla versione corrente**, associare la lezione e controllare anteprima/corrispondenza.
3. Confermare con motivo: la sorgente diventa **Migrata** e conserva lo storico; la nuova iscrizione contiene solo i completamenti confermati.
4. Ripetere scegliendo “Non trasferire”; controllare che un tentativo quiz aperto, destinazione esistente, edizione indisponibile, ACL negata e anteprima obsoleta blocchino senza scritture parziali.
5. Doppio invio identico: nessuna seconda iscrizione destinazione.

**Esito sito Joomla:** Non eseguito.

### PR #86 — finestre temporali dei materiali

Riferimenti: [PR #86](https://github.com/jeckodevelopment/jd_learn/pull/86), MAT-01–08. 

1. Impostare il fuso orario del corso (es. Europe/Rome), aggiungere un materiale con data inizio/fine e verificare che l’editor mostri gli orari locali dopo il salvataggio.
2. Impostare una fine precedente all’inizio: il salvataggio deve essere rifiutato senza creare allegato/file privato.
3. Pubblicare una bozza con materiale pronto, scansione pulita e finestra futura: il preflight mostra un avviso, non un blocco.
4. Nell’aula, verificare file assente prima dell’inizio, disponibile nella finestra e non più disponibile dopo la fine.
5. Modificare/sostituire materiale in bozza e verificare aggiornamento/conservazione della finestra e immutabilità della versione pubblicata.

**Esito sito Joomla:** Non eseguito.

### PR #80 — preflight disponibilità corso

Riferimenti: [PR #80](https://github.com/jeckodevelopment/jd_learn/pull/80), criteri di pubblicazione del corso. 

1. In una bozza, allegare materiale in quarantena/non pronto: Programma mostra un blocco di pubblicazione.
2. Con scansione ClamAV obbligatoria, il blocco resta finché il file non è pronto e la scansione `clamd` non è pulita.
3. Materiali solo anteprima o programmati mostrano un blocco.
4. Materiale pronto, non di anteprima e senza finestra rimuove il blocco (con scansione obbligatoria, solo dopo esito pulito).
5. La pagina preflight non apre/scansiona file; la pubblicazione valida di nuovo lato server e blocca materiale non valido.

**Esito sito Joomla:** Non eseguito.

### PR #74 — controlli di pubblicazione

Riferimenti: [PR #74](https://github.com/jeckodevelopment/jd_learn/pull/74), percorso editoriale/pref-light. 

1. Con amministratore autore/editore, creare bozza con un modulo senza lezioni: Programma segnala modulo vuoto e assenza di lezione obbligatoria.
2. Aggiungere lezione facoltativa: scompare il blocco modulo vuoto, resta quello sulla lezione obbligatoria.
3. Aggiungere lezione obbligatoria: scompaiono entrambi i blocchi strutturali; riepilogo/coperina assenti restano solo avvisi facoltativi.
4. Completare dettagli di contenuto e tentare la pubblicazione: controlli definitivi server rifiutano provider/contenuti non validi e conservano la bozza.
5. Ripetere la sola consultazione con ruolo che può leggere Programma ma non pubblicare. Liste separate e tradotte; il caricamento non cambia lo stato della bozza.

**Esito sito Joomla:** Non eseguito.

### PR #78 — preflight durata video mancante

Riferimenti: [PR #78](https://github.com/jeckodevelopment/jd_learn/pull/78), durata video/preflight.

1. Aprire bozza con lezione video senza durata rilevata/confermata: preflight mostra il blocco.
2. Registrare durata valida (1 secondo–8 ore) con flusso normale editor: blocco scompare dopo ricaricamento.
3. Tentare pubblicazione senza conferma: il server rifiuta e non modifica versione.
4. Verificare che l’apertura del preflight non contatti provider.

**Esito sito Joomla:** Non eseguito.

### PR #64 — accesso all’aula dopo il ritiro dal catalogo

Riferimenti: [PR #64](https://github.com/jeckodevelopment/jd_learn/pull/64), R-03.

1. Iscrivere uno studente, avviare una lezione testo e una video.
2. Ritirare la scheda e impostare la fine pubblicazione nel passato: scheda nascosta, nuovo studente non può avviare il corso né ricevere ammissione manuale.
3. Con lo studente già iscritto, usare Riprendi: aula, progressi e player continuano a funzionare anche dopo ricaricamento.
4. Revocare il livello Joomla o rendere indisponibile la categoria: l’aula viene bloccata.
5. Ripubblicare: corso torna visibile e progressi/versione dello studente restano invariati.

**Esito sito Joomla:** Non eseguito.

### PR #62 — Riprendi alla prima lezione obbligatoria incompleta

Riferimenti: [PR #62](https://github.com/jeckodevelopment/jd_learn/pull/62), R-05.

Precondizioni: corso pubblicato con almeno tre lezioni testo obbligatorie e una quarta facoltativa; iscrizione studente di prova.

1. Con iscrizione nuova, selezionare **Riprendi**: apre la prima lezione obbligatoria.
2. Completare le prime due; con ultima attività ancora valida, **Riprendi** riapre quella lezione.
3. Solo sull’istanza eliminabile, azzerare `last_lesson_id` nell’iscrizione di test: **Riprendi** apre la terza obbligatoria, non le già completate né la facoltativa.
4. Completare tutte le obbligatorie, azzerare nuovamente il riferimento e usare **Riprendi**: apre la prima lezione per revisione.
5. Controllare che le aperture non cambino stato, percentuale o progressi.

**Esito sito Joomla:** Non eseguito.

## PR senza comportamento Joomla

PR esclusivamente documentali o di infrastruttura senza cambiamento di comportamento utente: registrare “Non applicabile” con link alla PR e motivazione; non aggiungere casi manuali fittizi.

## Prove storiche ancora da recuperare

Le PR runtime senza procedura dettagliata nelle rispettive descrizioni non sono implicitamente collaudate. Per ciascuna, trascrivere i passi solo da requisiti/scenari già approvati e guide correnti; se la fonte non basta, segnalare la lacuna e chiedere una decisione di prodotto invece di inventare un test. Fino alla trascrizione ed esecuzione, mantenere lo stato “Non eseguito”.

## Manutenzione dopo ogni PR

Prima di unire una PR con comportamento runtime, riportarne qui le istruzioni manuali già presenti nella PR, collegare gli ID requisito/scenario esistenti e indicare precondizioni e risultati attesi. Se manca una procedura, indicare esplicitamente la fonte da cui ricavarla e tenere il test aperto. La descrizione di ogni PR deve includere `## Collaudo manuale Joomla`; per una PR solo documentale deve dichiarare che non cambia comportamento Joomla e che il test sito non è applicabile.
