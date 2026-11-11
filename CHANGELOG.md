## 0.41.66-dev — consegna materiali compatibile con Apache/LiteSpeed (IMP-06)

- Rimuove dalla consegna degli allegati la dipendenza opzionale da Nginx introdotta nella PR #219. I materiali privati vengono trasmessi dal componente PHP dopo i controlli Joomla e di integrità; non occorre configurare moduli o location specifiche del web server.
- Aggiorna la documentazione e le prove manuali per verificare download, anteprima, richieste Range/HEAD e revoca dell’accesso in un ambiente Joomla standard.
- Correzione di compatibilità della 0.41.65-dev; i dettagli della PR #219 restano nella cronologia Git.

## 0.41.64-dev — riuso dei materiali nella bozza (IMP-06)

- La gestione Materiali consente di collegare un asset privato esistente ai materiali del corso o a un’altra lezione della stessa versione in bozza, senza duplicare il file.
- Le associazioni ereditano titolo, descrizione e finestra di disponibilità del sorgente; il riuso ripetuto nello stesso ambito viene rifiutato. Le sostituzioni continuano a creare asset immutabili e le versioni pubblicate restano inalterate.
- PR [#217](https://github.com/jeckodevelopment/jd_learn/pull/217) integrata con squash nel commit `abf82a4053918f823726c84a156d295652c7b72c`, milestone **1.0.0**; CI standard 4/4 nel [run 42983654823](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823).
- Pacchetto [JD-Learn-0.41.64-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823/artifacts/13838677604), wrapper SHA-256 `1045619ab6ba9a47d050afc233ae9e8f124b8ede994f5677a9d921f1e1920986`, ZIP Joomla SHA-256 `f8d318f1dea8b68801bda56ac057bfd3df7dc6d0af93b972131e979196b72484`, disponibile fino al 25 novembre 2026. Estrarre il wrapper una volta per ottenere lo ZIP installabile. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-06--lifecycle-materiali-e-riuso-in-bozza-04164-dev).

Warning: truncated output (original token count: 25233)
Total output lines: 839

## 0.41.63-dev — protezione lifecycle categorie native (IMP-07)

- Le FK verso categorie Joomla impediscono la cancellazione permanente di categorie ancora usate da corsi, percorsi, docenti o domande.
- Il cestinamento e il ripristino restano reversibili e non cancellano corsi o storico; la visibilità segue lo stato della categoria.
- PR [#215](https://github.com/jeckodevelopment/jd_learn/pull/215) integrata con squash nel commit `148a4e4c7fe1a814f8a1bd561cecbb0380c7dd39`, milestone **1.0.0**; CI standard 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654).
- Pacchetto [JD-Learn-0.41.63-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654/artifacts/13835639241), wrapper SHA-256 `dfcfab154b2425a1c862a3680315aecb9c633abdab249c6f7c66af93e0a9feb0`, ZIP Joomla SHA-256 `938a623037eeaba7cdb0c5f70e6aa159f9926e6b84bfbc88e856e7c285b8bdd5`, disponibile fino al 25 novembre 2026. Collaudo Joomla manuale nel [testbook](docs/QA_TESTBOOK.md#imp-07--lifecycle-sicuro-delle-categorie-04163-dev).

## 0.41.62-dev — filtri docente e percorso nel catalogo (IMP-07, PR #213 integrata)

- Aggiunge filtri docente e percorso nel catalogo e nelle viste categoria; opzioni e risultati sono limitati a schede e profili accessibili all’utente.
- Filtri componibili con tag, difficoltà, durata, ricerca, ordine, vista griglia/elenco e paginazione. Gli ID non validi non allargano i risultati.
- PR [#213](https://github.com/jeckodevelopment/jd_learn/pull/213) integrata con squash nel commit `a08e67eb09c0b11279f6a68ee6abf0a0d077be1a`, milestone **1.0.0**; CI 4/4 nel [run 42923290761](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761). Pacchetto [JD-Learn-0.41.62-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761/artifacts/13811584820), wrapper SHA-256 `003c56dd360cd8a7c375c220a9b1a0ce0c57e6669913a693c63ea6781f032cf2`, ZIP Joomla SHA-256 `e26a39a0c63c3ba07a9851b49b701582d3df8a656ac55c634cd1414ff9412728`, disponibile fino al 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-docente-e-percorso-nel-catalogo-04162-dev-pr-213-integrata).

## 0.41.60-dev — filtro tag nel catalogo pubblico (IMP-07, PR #209 integrata)

- Aggiunge il filtro per tag Joomla nel catalogo e nelle pagine categoria, combinabile con difficoltà, durata, ricerca e ordinamento.
- Le opzioni mostrano soltanto tag pubblicati/accessibili associati a corsi visibili; il filtro mantiene il ramo categoria e non rivela corsi riservati.
- PR [#209](https://github.com/jeckodevelopment/jd_learn/pull/209) integrata con squash nel commit `66b9488009378ba7b7e7d77204bf33b904028e0e`, milestone **1.0.0**; CI standard 4/4 nel [run 42918303751](https://github.com/jeckodevelopment/jd_learn/actions/runs/42918303751). Pacchetto [JD-Learn-0.41.60-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42918303751/artifacts/13810143951), wrapper SHA-256 `fda316f972196e9990d57e27a0d8be632eeda51afdfaa99bf846aca803d5d994`, disponibile fino al 24 novembre 2026. Il checksum ZIP installabile verificato in CI è registrato nel log del job di build; accettazione Joomla manuale pendente. Testbook aggiornato.

## 0.41.59-dev — filtri core del catalogo (CAT-01, PR #210 integrata)

- Aggiunge filtri pubblici per difficoltà e fasce di durata stimata al catalogo e alla lista corsi delle categorie; ricerca, ordine, categoria e paginazione si combinano.
- I valori inviati sono limitati a opzioni previste; i filtri leggono solo i metadati promozionali del corso.
- PR [#210](https://github.com/jeckodevelopment/jd_learn/pull/210) integrata con squash nel commit `e5d0bae954c099c8522db57c30b1e32882b468ee`; milestone **1.0.0**; CI standard 4/4 nel [run 42913999069](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069). Pacchetto [JD-Learn-0.41.59-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069/artifacts/13807843959), wrapper SHA-256 `c1e9e8e7c3e64daebc4c5162f64695c89b0d3d79a9c41019f5eeee087227644c`, ZIP Joomla SHA-256 `f22a8a3fb1c87e17f10f0342e6ba6980e5c8882e83dcfec41bab34713b3c41dc`; scade il 24 novembre 2026. Collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-di-difficolta-e-durata-del-catalogo).
- Collaudo Joomla: vedere [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-di-difficolta-e-durata-del-catalogo).

## 0.41.58-dev — URL SEF con alias per catalogo e percorsi (IMP-07, PR #208 integrata)

- Genera segmenti Joomla ID-alias per corsi, categorie e percorsi; conserva il parsing degli URL numerici storici e usa query parametrizzate.
- Il canonical delle viste pubbliche indica l’URL SEF con alias o il menu nativo dedicato; gli ID distinguono alias ripetuti tra lingue e contenitori.
- PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208) integrata con squash nel commit `6b92612254c060c1ef296c0a03661442871ad1cc`; CI standard 4/4 nel [run 42910392500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500).
- Pacchetto installabile [JD-Learn-0.41.58-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500/artifacts/13806778546), wrapper SHA-256 `773fb7cd4e200df599ffd1b858cc5ef60d013c8823780b625024afbb4acec8cb`, ZIP Joomla SHA-256 `7a4d52cdbbd708b523d6d05f29f0b937fcf5094019a6c9e70ab443bd7a28ade3`; scade il 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--url-sef-con-alias-per-corsi-categorie-e-percorsi).

## 0.41.57-dev — metadati SEO/social pubblici (IMP-07, PR #207 integrata)

- Aggiunge canonical URL e metadati Open Graph/Twitter per schede corso, categorie e percorsi visibili agli ospiti; per le schede corso usa titolo/descrizione SEO e immagine promozionale locale già configurati.
- Le pagine con accesso catalogo/contenuto non disponibile agli ospiti e le viste percorso autenticate ricevono `noindex, nofollow`; le immagini social puntano a media locali.
- PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207) integrata con squash nel commit `32215c493fe1d7a2bf99871b5f93087a842563bd`; CI standard 4/4 superata nel [run 42905972598](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598).
- Pacchetto installabile [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462), checksum wrapper SHA-256 `f0729b8cfe769f5d7f2065522651bc4b56278c6a4fdb2e29adae4e54be2199de`; checksum ZIP Joomla `7e078f93ff421c143e7a7c198911e4d38c5c6208c788c5920080fd5d4d642921`. Il wrapper scade il 24 novembre 2026: estrarlo per ottenere lo ZIP Joomla. Collaudo manuale ancora pendente; procedura nel [testbook](docs/QA_TESTBOOK.md#imp-07--metadati-seo-e-social-pubblici).

## 0.41.56-dev — ricerca e ordinamento del catalogo (IMP-07, PR #206 integrata)

- Aggiunge ricerca case-insensitive su titolo e sommario promozionale, ordinamento per più recenti o titolo, anche nella vista categoria.
- La ricerca usa parametri SQL associati, mantiene il perimetro di accesso e non interroga programma, lezioni o dati personali; i controlli sono tradotti IT/EN e accessibili da tastiera.
- PR [#206](https://github.com/jeckodevelopment/jd_learn/pull/206) integrata nel commit `cc5af168e5b42aff0b30db2e051acecd86806b9c`; CI standard 4/4 superata nel [run 42901417240](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240). [Pacchetto installabile JD-Learn-0.41.56-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240/artifacts/13803875500), wrapper SHA-256 `3c3d7d078ae6b3d03e530d81d833bd89babd8c12903580f84f8d8f4b3b526cd2`, scade il 24 novembre 2026. Collaudo Joomla pendente: ricerca titolo/sommario, termine riservato assente, filtro categoria e ordinamenti. Procedura in [testbook](docs/QA_TESTBOOK.md#imp-07--ricerca-e-ordinamento-del-catalogo-04156-dev-pr-206-integrata).

## 0.41.55-dev — Smart Search catalogo (IMP-07, PR #204)

- Integrato il plugin Finder `plg_finder_jlms`: indice limitato a titolo e sommario delle schede pubbliche idonee, con lingua/categorie e aggiornamento al cambio di corso o categoria.
- Esclusi contenuti non pubblicati, programmati, riservati, senza versione attiva o collocati in categorie non pubbliche. Nessun programma, lezione, media, dato personale o campo privato entra nell’indice.
- PR [#204](https://github.com/jeckodevelopment/jd_learn/pull/204) integrata nel commit `5018d895e52727be29cedb31f4f93b5245ef346a`; CI standard 4/4 superata nel [run 42898585659](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659).
- Pacchetto installabile [JD-Learn-0.41.55-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659/artifacts/13802930146), wrapper ZIP SHA-256 `48af954a9fa682519e5a6c8121ef97d22b17bd37004f9969e1e8c59c694e66e1`, scade il 24 novembre 2026. Estrarre il wrapper ZIP per ottenere il pacchetto da installare in Joomla.
- Il collaudo manuale Joomla resta pendente; procedura in [testbook](docs/QA_TESTBOOK.md#imp-07--smart-search-del-catalogo-jd-learn-04155-dev-pr-204-integrata).

## Riconciliazione requisiti — 10 novembre 2026 (runtime 0.41.56-dev, merge IMP-07)

- Riconciliati tutti i 210 ID senza alterare descrizioni/scenari/perimetro baseline: implementazione, esecuzione automatica con copertura, accettazione Joomla, perimetro 1.0.0/fase 2, PR/commit e residui separati.
- 14 implementazioni completate nel sottoinsieme core esplicito, 166 parziali, 30 non iniziate; nessuna accettazione integrale. 133 righe core, 40 miste, 37 fase 2. Il 73% deriva dalla media aggiornata delle sette aree (513/7), non dal conteggio delle righe.
- Verificatore e sei regressioni impediscono confusione tra CI, codice e accettazione; riepilogo JSON controllato contro il CSV. Ricostruzione del pacchetto: SHA-256 invariato `2a3b87ab7669e2d41e830b2c1bddb7d41668260278916f85510aac9cf12024a6`.

## 0.41.54-dev — pulizia programmata dei materiali orfani (IMP-06)

- Aggiunta attività Joomla Scheduler `jlms.materials.cleanup`: elimina in batch limitato asset locali senza riferimenti, più vecchi di 24 ore, e i relativi blob privati. I riferimenti a lezioni, allegati, attestati e job sono esclusi; le sostituzioni immutabili restano conservate.
- L’attività è disponibile nel plugin `plg_task_jlms`, ma va pianificata dall’amministratore. Non viene creata automaticamente all’installazione.
- PR [#202] integrata nel commit `fc94b715d265306777471a40e1043c9cec204d1b`; CI standard 4/4 superata nel [run 42886192087](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087). Pacchetto [JD-Learn-0.41.54-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087/artifacts/13797460306), SHA-256 artefatto `622501af14e1a9b50a47203c7c641ec0beac1ad7b939589343e68d83274e23b6`, scade il 24 novembre 2026. Test manuale Joomla pendente.

## 0.41.53-dev — pulizia limitata delle sessioni video scadute (IMP-05)

- L’avvio autorizzato di una nuova sessione elimina fino a 250 sessioni video scadute usando l’indice di scadenza; le sessioni attive non vengono toccate. La pulizia è opportunistica e non sostituisce un’attività periodica sul sito inattivo.
- Aggiunta prova d’integrazione Joomla per la rimozione di una riga scaduta. Nessuna modifica a progresso, accessi o provider.
- PR [#198] integrata nel commit `df98bb5cfd8d7056643e786eedc9bab0804d9d53`; CI standard 4/4 superata nel [run 42883180012](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012). Pacchetto installabile [JD-Learn-0.41.53-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012/artifacts/13794578948), SHA-256 ZIP `e8ac53685a7b2189f23373b6048a3342987112c0f15d15637a329e67e51a69c7`, artefatto `f30f0475571a2240fabb03eaee7b0280b78d0b8154b6a7a46ce4c976984b5815`; dimensioni rispettive 545.191 e 545.042 byte, scadenza 24 novembre 2026.
- Collaudo manuale Joomla richiesto: avvio di un video dopo la scadenza di una sessione precedente; verificare che il player parta normalmente e che la nuova sessione salvi la posizione.

## 0.41.52-dev — retry del progresso audio (PR #196 integrata)

- Gli errori temporanei di rete, timeout, HTTP 408/429 e 5xx ritentano una sola volta il salvataggio della stessa posizione audio. Gli errori permanenti non generano retry; il punto fallito resta disponibile per il successivo evento del player. Completamento e percentuale non cambiano.
- CI standard 4/4 superata nel [run 42876421523](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523). [Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523/artifacts/13793090250).
- Collaudo Joomla pendente: ripresa, pausa/seek, errore transitorio e permanente, nessun completamento automatico. [Testbook](docs/QA_TESTBOOK.md#pr-196-integrata--retry-del-progresso-audio).

## 0.41.52-dev — filtri della banca domande (PR #194 integrata)

- La vista Banca domande mostra i filtri Ricerca, Categoria, Stato e Lingua; consente combinazioni e mostra l’intestazione Azioni/Actions.
- CI standard 4/4 superata nel [run 42873607501](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501). [Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501/artifacts/13791570779).
- Collaudo manuale Joomla pendente: filtri singoli e combinati, pulizia, paginazione/ordinamento, intestazione IT/EN e accesso negato ai ruoli non autorizzati. [Testbook](docs/QA_TESTBOOK.md#pr-194-integrata--filtri-della-banca-domande).

## 0.41.52-dev — indice aula comprimibile su smartphone e default fuso globale (PR #190/#192 integrate)

- PR #192 corregge issue #172: il nuovo corso mostra l’opzione “Usa globali” nel campo Timezone nativo; al salvataggio usa il fuso globale IANA del sito. Fusi espliciti, date UTC e corsi esistenti invariati. CI standard 4/4 superata nel [run 42865678167](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167); pacchetto installabile [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167/artifacts/13787890952), checksum e procedura manuale nel testbook. Collaudo Joomla manuale pendente in [testbook](docs/QA_TESTBOOK.md#pr-192--fuso-globale-per-nuovi-corsi).


- Aggiunge in aula un comando tradotto e accessibile per mostrare/nascondere il programma didattico sotto 48rem; desktop mantiene l’indice visibile. Senza JavaScript il programma resta disponibile.
- CI standard 4/4 superata nel [run 42860617351](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351); pacchetto [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351/artifacts/13784608215), SHA-256 interno `278d260dddbdcdda305fff7456943d5ee43a15d042c94e1c3d901fe9f166c332`, wrapper `6214644bc36d91e60cb7da5b9d31f9216e3e53c8492eba1a5ea3ac5fa4962ddd`; collaudo Joomla manuale pendente. Procedura in [testbook](docs/QA_TESTBOOK.md#pr-190--indice-aula-su-smartphone).

## 0.41.51-dev — retry limitato del salvataggio di avvio (PR #187 integrata)

- Aggiunge stato “Salvataggio attività…” e un singolo retry dopo errore di rete/timeout o risposta HTTP 408, 429 o 5xx; timeout per richiesta 8 secondi e pausa 500 ms.
- Riutilizza la stessa chiave di idempotenza nelle due richieste; gli errori di autorizzazione e gli altri 4xx non vengono ritentati. Nessuna modalità offline o retry continuo.
- CI standard 4/4 superata nel [run 42845258898](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898); pacchetto [JD-Learn-0.41.51-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898/artifacts/13779340061), SHA-256 interno `1f7563bd4957f66027dbc1d9b925784a087e196a75ad8909d4b78ce9b2455cab`, collaudo Joomla manuale pendente.
- Comportamenti Joomla in [testbook](docs/QA_TESTBOOK.md#pr-187-integrata-retry-del-salvataggio-di-avvio-04151-dev); CI standard 4/4 superata.

## 0.41.50-dev — registrazione dell’avvio lezione (PR #185 integrata)

- L’apertura esplicita di una lezione accessibile registra lo stato “in corso” e la destinazione Riprendi tramite POST con token Joomla. Il GET dell’aula resta senza scritture.
- Nessun completamento, credito o aumento di percentuale viene attribuito; il completamento manuale aggiorna la riga esistente senza duplicarla.
- PR #185 integrata nel commit `de93c1290e89a5048a5cf6e16c0b15f719580322`; CI standard 4/4 superata nel [run 42839639601](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601). Pacchetto [JD-Learn-0.41.50-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601/artifacts/13776106013), SHA-256 interno `9534346da253c8f953da826d203b3f3509fab0682b09e7d09736e60180e28e83`, wrapper `461d8dd9c4d8287c15d6dc667821920e25c4c879108f0a3d9a2ac8cea5ec70f6`; collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#pr-185-integrata--avvio-e-ripresa-della-lezione-04150-dev).

## 0.41.49-dev — soglia video configurabile per lezione (PR #183 integrata)

- Aggiunta una soglia facoltativa per ogni lezione video: campo vuoto eredita quella della versione, valore impostato prevale per la singola lezione.
- La soglia è salvata nei parametri della lezione, inclusa nel confronto del programma, validata al salvataggio/pubblicazione e usata nel calcolo di completamento. Le versioni assegnate restano immutabili.
- CI standard 4/4 superata nel [run 42830117573](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573); merge `4ef19dd8b5ebe4d6eddb1f127a5864929b457297`. Pacchetto [JD-Learn-0.41.49-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573/artifacts/13770693036), SHA-256 interno `46db03c8214873bbd2d266f067585458ebec059e3abbaf2fe29d9e38be1635bb`; collaudo manuale Joomla pendente nel [testbook](docs/QA_TESTBOOK.md#pr-183-integrata--soglia-video-per-lezione-04149-dev).

## 0.41.48-dev — apertura inline protetta di PDF e immagini (PR #181 integrata)

- Nell’aula aggiunge **Apri nel browser** per PDF e immagini JPEG/PNG/WebP, mantenendo il download separato. L’endpoint applica nuovamente accesso all’iscrizione, disponibilità e relazioni del materiale e ammette solo i MIME passivi consentiti.
- La visualizzazione resta read-only: non cambia iscrizione, progresso o completamento. Gli altri formati continuano a essere scaricabili ma non sono serviti inline.
- PR #181 integrata nel commit `06ac78d277c8b7656d1cd635c88441ef74eef6be`; CI standard 4/4 superata sul commit `eacca4b25f5489a75481989d51cb242d6cc5f460` nel [run 42807134904](https://github.com/jeckodevelopment/jd_learn/actions/runs/42807134904).
- Pacchetto Actions [JD-Learn-0.41.48-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42807134904/artifacts/13758379615): wrapper ZIP da estrarre una volta; SHA-256 interno `c1cae9dc3f5fe084a61880f8e4ead4df7f84564d7343631a2dc4147f6d0897c5` (539797 byte), wrapper `e879592a674a5f75e8693f9411ff6412683897675a94aa112d8c76baa62a9c0c` (539608 byte), scadenza 24 novembre 2026.
- Collaudo manuale Joomla dettagliato nel [testbook](docs/QA_TESTBOOK.md#imp-04--apertura-protetta-inline-di-pdf-e-immagini-nell-aula-04148-dev); resta pendente.

## 0.41.47-dev — preview editoriale di documenti e audio (PR #179 integrata)

- Estende IMP-03 alle lezioni documento/audio: mostra allegati di corso e lezione pronti, rispetta stato e finestre di pubblicazione e rivalida ACL prima della consegna. PDF, immagini e testo consentiti possono aprirsi inline; altri documenti restano download. MP3/OGG sono riprodotti con il player HTML5 nativo.
- La preview non crea iscrizioni, sessioni, tentativi o progressi. Le interazioni quiz restano fuori da questa preview.
- PR #179 integrata nel commit `1b957f22e5ceeccb7aa5b387d5018011a8ec20db`; CI standard 4/4 superata sul commit `2c7c6b03a33bca5a4c6b65aa214b5da7613c04ca` nel [run 42804753192](https://github.com/jeckodevelopment/jd_learn/actions/runs/42804753192).
- Pacchetto Actions [JD-Learn-0.41.47-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42804753192/artifacts/13758745162): wrapper ZIP da estrarre una volta; SHA-256 interno `cf204d5ac6366e7a509892ad47f5d30841e87d49737f5323f56b0a5a0c90c6d6` (539512 byte), wrapper `e1cb2a2a8b3940d1b53d1974e2b2cc45a25975d5889b7b46aed4018307c7fc19` (539324 byte), scadenza 24 novembre 2026.
- Collaudo manuale Joomla dettagliato nel [testbook](docs/QA_TESTBOOK.md#imp-03--preview-editoriale-di-documenti-e-audio-04147-dev); resta pendente.

# Changelog

## 0.41.46-dev — riproduzione video nella preview editoriale (PR #177 integrata)

- Aggiunge descrittori video in sola lettura nella preview amministrativa, con autorizzazione editoriale rivalidata e consegna HTML5 locale protetta. Il player non crea sessioni, non invia telemetria e non salva progressi o tentativi.
- Aggiorna i messaggi IT/EN per distinguere la riproduzione video dalle attività non simulate (documenti, audio e quiz).
- PR #177 integrata nel commit `b80aead8792b549823538883c3c88080d614ae4f`; CI standard 4/4 superata sul commit `1874616ab2d192b92875071212099481f9fc21c7` nel [run 42800028533](https://github.com/jeckodevelopment/jd_learn/actions/runs/42800028533).\n- Pacchetto Actions [JD-Learn-0.41.46-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42800028533/artifacts/13755751590): wrapper ZIP da estrarre una volta; SHA-256 interno `ccbaff840cf94a5829de32b9efea90b7c83fe0565e77c50f16bf124bbe69f94b` (538172 byte), wrapper `8647a6a99551b19d793d83eedcddb14401c137888df046566f0c980601e70353` (537983 byte), scadenza 24 novembre 2026.\n- Collaudo manuale Joomla richiesto per provider, consenso, revoca ACL e assenza di effetti sullo stato studente; vedi [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).


## 0.41.45-dev — vista del programma didattico allineata alla toolbar Joomla (PR #169 integrata)

- Le azioni principali del programma sono disponibili nella toolbar nativa, con URL contestuali al corso e visibilità condizionata ai permessi già verificati.
- Il pannello laterale separa visivamente moduli, moduli di form e comandi, migliorando spaziatura e leggibilità sui viewport stretti.
- Aggiunta una verifica HTTP per l’azione contestuale di aggiunta modulo. Collaudo manuale Joomla ancora da eseguire; la checklist è in `docs/QA_TESTBOOK.md`.

## 0.41.44-dev — fuso predefinito dei corsi dalla configurazione globale (PR #167 integrata)

- Risolve issue #154: un nuovo corso eredita il fuso impostato nella configurazione globale del sito Joomla. L’autore può scegliere e conservare un fuso diverso; i corsi già esistenti non vengono reinterpretati.
- Aggiunta la prova Joomla d’integrazione sul valore globale e sulla selezione del form. Il collaudo manuale reale resta pendente.
- PR [#167](https://github.com/jeckodevelopment/jd_learn/pull/167) integrata in `43d87a52320dc7e6bbd0d6f3f55371bdacd4c8c7` nella milestone `1.0.0`; CI standard 4/4 superata nel [run 42769089189](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189).
- Pacchetto [JD-Learn-0.41.44-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189/artifacts/13740848347), wrapper ZIP da estrarre una volta, checksum interno `e68934ff0b4257d6defd8ed9e28762eb526c9f3a2d0999c315536534658a4458` (534662 byte), checksum wrapper `f4d0ca0bc889bdbe03cd60b8e8b91984ef060d65ab2c66ccd2a901b6165d4796`; scade il 24 novembre 2026.

## 0.41.43-dev — anteprima dei livelli di accesso (PR #162 integrata)

- La preview editoriale consente di simulare viewing access level Joomla e distingue visibilità di catalogo, accesso ai contenuti e requisito combinato. La simulazione è read-only e non modifica identità, iscrizioni, corso o progressi.
- Aggiunte traduzioni IT/EN e verifiche HTTP/integrazione per accessi misti, autorizzazione editor e assenza di mutazioni.
- CI standard 4/4 superata nel run 42712132178; merge `4c87851d427592885ef3a5348da3e1a182de6042`. Pacchetto [JD-Learn-0.41.43-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178/artifacts/13715794795), SHA-256 interno `39797f26dfee8bcfec34c9aa8365c218653e56857c8133808f1892996e2de738`; collaudo manuale Joomla pendente.

## 0.41.42-dev — archiviazione e ripristino corsi (PR #159 integrata)

- Estende il workflow Joomla JD Learn con archiviazione e ripristino del corso pubblicato. L’archivio sparisce dal catalogo e non ammette nuove iscrizioni.
- Versione didattica, iscrizioni esistenti e accesso degli studenti già ammessi sono preservati; il ripristino riporta online la stessa versione.
- Upgrade del workflow idempotente; prove Joomla d’integrazione per tra…13233 tokens truncated…e.admin sul relativo asset. Le modifiche vengono salvate dal controller com_config di Joomla.
- I salvataggi editoriali conservano le regole; grant, deny, ereditarietà, CSRF e scope verificati sul percorso HTTP nativo.

## 0.29.0-dev — descrizione richiesta alla pubblicazione

- Applica il requisito della descrizione estesa alla prima pubblicazione. Rifiuta testo assente o composto da soli spazi e markup vuoto, indicando il campo da completare.
- Le bozze esistenti richiedono il completamento della descrizione. Le versioni già pubblicate e i ritiri/ripubblicazioni conservano i dati congelati.

## 0.28.0-dev — anteprima editoriale

- Il Programma apre l’anteprima della versione selezionata. L’editor può consultare moduli e testo delle lezioni senza iscrizioni, riproduzione video, tentativi o progressi.

## 0.27.2-dev — inizializzazione iframe YouTube

- Crea l’iframe con URL e origine espliciti e attende la navigazione prima di collegare l’SDK. Il referrer dell’iframe contiene solo l’origine del sito.

## 0.27.1-dev — caricamento SDK YouTube

- Ripulisce il caricamento SDK fallito, consente un nuovo tentativo e distingue blocchi CSP, script indisponibile, video non incorporabile e identificazione del sito mancante.

## 0.27.0-dev — player integrato nell’aula

- Il player appare all’apertura della lezione secondo la politica configurata dal gestore. Rimangono i controlli di riproduzione del player.
- La precedente modalità esplicita richiede una scelta nelle opzioni. Sessione, modulo e provider hanno errori distinti.

## 0.26.3-dev — avvio player YouTube

- Attende i metadati di YouTube prima di valutare la durata e registrare progressi. Mostra un messaggio distinto se il server rifiuta l’accesso al player.

## 0.26.2-dev — diagnostica video nel programma

- Stato di configurazione dei quattro provider nel form lezione e accesso alle impostazioni native per operatori autorizzati.
- Messaggi specifici per sorgenti non valide, provider non registrati e rifiuti del servizio Cloudflare Stream.

## 0.26.1-dev — pubblicazione delegata

- Il workflow verifica il permesso di cambio stato sul singolo corso, con ereditarietà dalla categoria. Prova di prima pubblicazione HTTP con un operatore senza permesso globale.

## 0.26.0-dev — validazione editoriale M01/c2

- Limite di 500 caratteri per descrizioni brevi nuove o modificate, senza troncamento dei testi preesistenti.
- Alias duplicati rilevati dopo normalizzazione, con messaggio specifico per categoria e lingua.
- Prove native e HTTP per i limiti, la conservazione dei dati e i messaggi del modulo.

## 0.25.0-dev — permessi editor M01/c1

- Elenco corsi con scope ACL nativo per-record prima di paginazione/totali, cache coerente dopo revoca e nessun filtro basato sul solo autore.
- Nuovo corso consentito anche ai delegati categoriali; selettore Category esteso con scope e conservazione della categoria reale, cestino rifiutato.
- Permessi effettivi, opzioni native autorizzate, checkout altrui in sola lettura ed errori copertina specifici; modalità avvio documentate.
- Nuove prove native/HTTP/reinstall, guide e matrice aggiornate; esiti nella PR. Schema41tabelle/58FK invariato; M01 e integrazioni core ancora parziali.

## 0.24.0-dev — calendario M01/b3

- Campi calendario/fuso nativi: pubblicazione e fruizione, UTC, intervalli e DST senza normalizzazione silenziosa.
- Snapshot delle ultime date alla pubblicazione e assegnazione della finestra alla prima edizione; date pubblicate immutabili, vecchi client/snapshot conservati.
- Scheda con fuso/offset e disponibilità corrente del comando di avvio; politica dei quiz già aperti invariata. Test dominio/Joomla/HTTP/reinstall e guide aggiornati, esiti nella PR del build. Schema41tabelle/58FK invariato.

## 0.23.0-dev — docenti M01/b2

- Profili docente con MVC/Form/Table Joomla, categorie/asset, ACL dedicata e revisioni; bio testuale, immagine/alt, sito e account facoltativo senza gruppi aggiunti.
- Selezione multipla nei corsi in bozza; snapshot ordinato, pubblicazione validata e visibilità corrente/storica su profili e categorie.
- Prove native/HTTP/upgrade e guide/IT-EN/override; esiti nella PR del build. Schema41tabelle/58FK invariato. Calendario, directory pubblica standalone, associazioni multilingua dei docenti e selettore ricercabile avanzato ancora pendenti.

## 0.22.0-dev — copertina e SEO M01/b1

- Media field nativo per copertina pubblica locale, alt obbligatorio o decorazione esplicita; validazione path/MIME/dimensioni e confini filesystem.
- Tab metadati con titolo/descrizione SEO facoltativi, congelati nella versione e applicati alla head nativa soltanto dopo autorizzazione.
- Compatibilità degli snapshot0.21 e test di upgrade reale; schema41tabelle/58FK invariato. Docenti, calendario, provider media remoti e asset versionati ancora pendenti.
- Corretto avvio automatico concorrente: il lock dell’edizione è la prima lettura transazionale, prima dello snapshot MySQL/MariaDB; identità e disponibilità aggiornate dopo l’attesa. Test deterministico con un’iscrizione concorrente inserita durante il blocco.
- Prove servizio/HTTP e regressioni: esiti nella PR del build. Nessuna accettazione completa della fase dedotta dai test.

## 0.21.0-dev — editor/presentazione M01/a

- Schede native editor e nuovi campi di presentazione, filtrati/tipizzati e conservati senza accettare JSON grezzo.
- Snapshot degli ultimi dati alla pubblicazione; scheda autorizzata con nuove regioni CSS. Versioni storiche non modificate e liste leggere.
- Piano M01–M12 più differitoM13, con assegnazione verificata di tutti i210ID; non dichiarazione di completamento.
- Nuove prove di persistenza, autorizzazione, XSS, input invalidi, snapshot e upgrade; risultati da registrare. Schema41tabelle/58FK invariato.

## 0.20.1-dev — stabilizzazione pubblicazione

- Priorità0 per il blocco segnalato nel collaudo utente; import CSV rinviato fino alla verifica del flusso essenziale. Nessuna diagnosi del sito dedotta dalla CI.
- Collegamento programma/pubblicazione dall’editor, istruzioni corrette e diagnostica di versione, permessi, plugin Publishing e transizioni mancanti.
- Nuova prova HTTP di creazione e prima pubblicazione, rifiuto programma vuoto/plugin disabilitato e replay senza duplicare l’edizione. Prima CI41349160121:13asserzioni HTTP per ambiente e suite completa riuscite. Chiarimento successivo della voce nell’elenco corsi nella PR22; riscontro sul sito pendente.
- Schema invariato, quattro provider base conservati, nessun bypass del workflow o dell’immutabilità.

## 0.20.0-dev

- Preassegnazione manuale esplicita senza livelli correnti, con avviso e conferma; default di ammissione invariato.
- Assegnazione distinta dalla fruizione: nessun gruppo aggiunto, stessi controlli runtime, disponibilità editoriale obbligatoria.
- Opt-in vincolato all’anteprima/hash e registrato nel log Joomla; compatibilità degli hash standard0.19 preservata.
- Nessuna modifica allo schema41tabelle/58FK; prove negative, concorrenza, grant/revoca e conservazione superate nella CI41319974680 (37servizio/18HTTP/4conservazione per ambiente). CSV e accettazione completa ancora pendenti.

## 0.19.0-dev

- Iscrizione manuale singola alla versione pubblicata corrente e all’edizione predefinita, con controlli freschi su operatore e destinatario.
- Anteprima/conferma/motivo, CSRF, receipt idempotente e audit core atomico; nessuna assegnazione di gruppi o duplicazione/riattivazione implicita.
- Lock dell’edizione condiviso con Inizia, capienza e subject risolti in transazione; nessuna email automatica.
- Schema additivo41tabelle/58FK; prove runtime/HTTP/concorrenza/audit/upgrade predisposte, esiti effettivi nelle Actions. Import CSV, approvazioni e gestione completa restano pendenti.

## 0.18.0-dev

- Sospensione e riattivazione delle iscrizioni con anteprima aggiornata, motivo/conferma e permessi correnti per corso.
- Eventi append-only e Action Logs atomici; replay idempotenti e lock/CAS condivisi con i writer didattici.
- Progressi, completamenti, risposte e timer conservati; nessuna assegnazione di gruppi o proroga delle finestre.
- Schema additivo40tabelle/57FK; test runtime/HTTP/concorrenza/audit/upgrade predisposti, esiti effettivi nelle Actions. Creazione manuale, annullamenti e gestione completa ancora pendenti.

## 0.17.0-dev

- Elenco amministrativo degli iscritti per corso con permessi nativi dedicati e aggiornati su ogni lettura.
- Filtri versione/utente/stato/avanzamento, paginazione nativa e date UTC; nessuna mutazione o assegnazione di gruppi.
- Stato iscrizione distinto dal completamento; dati minimizzati e nomi escapati, viste native e override documentati.
- Nessuna modifica allo schema; prove ACL/runtime/HTTP/conservazione predisposte, esiti effettivi nelle Actions. Gestione con audit, report completi ed export restano da implementare.

## 0.16.0-dev

- Area personale del solo utente autenticato con storico paginato, filtri nativi e gruppi didattici separati dalla disponibilità corrente.
- Storico e titolo congelato conservati dopo revoca o scadenza; nessuna lezione/risorsa/risposta nel riepilogo.
- Riprendi/Rivedi in lettura con nuova autorizzazione, ultima attività valida e lingua originale, senza iscrizioni o tentativi automatici.
- MVC/menu/form/override e ID CSS nativi; intestazioni private e noindex. Nessuna nuova tabella; test runtime/HTTP/upgrade predisposti, esiti effettivi nelle Actions.
- Attestati, attività dettagliate e gestione amministrativa delle iscrizioni restano da implementare.

## 0.15.0-dev

- Viste pubbliche categorie e categoria con figli/corsi paginati e ID CSS stabili per override nativi.
- Filtro corrente di stato/lingua/accesso su ogni antenato e sezione; nessun conteggio di rami nascosti.
- Tipo nativo course.category nel gestore Associazioni e collegamenti lingua pubblici, menu/Pathway e SEF.
- Nessuna nuova tabella; test lifecycle/HTTP/upgrade predisposti, esiti effettivi nelle Actions. Rimozione con dipendenze e SEO completo restano da implementare.

## 0.14.0-dev

- Associazioni dei corsi in bozza tramite AdminModel, tabella e contratto nativi Joomla; campi per lingua con ACL e checkout dei target.
- Integrazione con gestore Associazioni, Language Filter e mod_languages; schede tradotte filtrate per visibilità corrente, senza trasferire progressi.
- Gruppi pubblicati protetti, input manomessi e appropriazione di collegamenti rifiutati; nessuna modifica allo schema39tabelle/56FK.
- Test runtime, HTTP con pacchetto lingua italiano, routing SEF e conservazione predisposti. Categorie e gestione dei collegamenti pubblicati restano da completare; esiti effettivi nelle Actions.

## 0.13.0-dev

- Proroghe dei tentativi aperti con timer, anteprima/conferma/motivo, autorizzazioni native per corso e Action Logs atomici.
- Deadline effettiva distinta dallo snapshot originario: storia append-only, nessuna modifica alle risposte o alla loro revisione; limite48ore e nessuna riapertura dopo scadenza/consegna.
- Stato studente compatto e sincronizzazione del timer anche senza risposte cambiate; selezioni locali conservate e avvisi riallineati alla proroga.
- Schema additivo39tabelle/56FK. Verifiche runtime/HTTP/client/concorrenza e aggiornamento dalla0.12 predisposti; esiti effettivi nelle Actions.
- Rivalutazioni, attestati/anomalie, integrazioni complete e certificazioni di produzione restano da completare.

## 0.12.0-dev

- Annullamento dei tentativi con anteprima d’impatto, conferma e motivo; restituzione esplicita del consumo e numeri storici mai riutilizzati.
- Evidenze originarie conservate, risultati/completamento ricalcolati sui tentativi validi, annullamenti distinti dallo storico ancora valido.
- Audit Action Logs e ledger nella transazione; anteprime obsolete e replay incompatibili rifiutati. Mutazioni dello studente bloccate sul tentativo annullato.
- Guardie per attestati, revisioni d’esito e storici oltre10.000tentativi; questi casi richiedono flussi dedicati successivi.
- Test runtime/HTTP/concorrenza/upgrade predisposti; esiti effettivi nelle Actions. Nessuna modifica allo schema38tabelle/55FK.

## 0.11.0-dev

- Autorizzazione e ritiro del tempo aggiuntivo per iscrizione/quiz, con backend MVC/form nativi, ACL per corso, motivazione e revisioni append-only.
- Durata individuale congelata all’avvio; tentativi già aperti/conclusi immutabili, anche durante revoche o aumenti successivi. Compatibilità con snapshot0.10.
- Action Logs Joomla con dati minimi e verifica atomica della registrazione; nessun invio email dentro la transazione.
- Avvisi a cinque minuti e a un minuto in regione accessibile separata dal salvataggio. Riepilogo distinto fra tempo attuale e prossimo tentativo.
- Schema additivo:38tabelle/55FK. Collaudi predisposti per aggiornamento0.10→0.11 con tentativi preesistenti, ACL/audit/deadline, HTTP e conservazione. Risultati effettivi nelle Actions della PR.
- Rivalutazioni, proroghe durante un tentativo, privacy completa, carico e certificazione accessibilità restano da completare.

## 0.10.0-dev

- Pubblicazione di quiz con pool congelato e snapshot privati/pubblici per ogni tentativo; estrazione e ordine stabili alla ripresa.
- Avvio condiviso fra schede, salvataggi CAS, UUID idempotenti, consegna e scoring esatto lato server, migliore risultato e completamento una sola volta.
- Timer server, chiusura sulle sole risposte salvate e plugin base Joomla Scheduler `task/jlms`, routine `jlms.quiz.expire` a lotti limitati.
- Viste studenti con override/ID CSS, task JSON con CSRF/accesso corrente e feedback protetto; autosave JavaScript ogni20secondi con retry immutabili.
- Test runtime/HTTP/concorrenza/upgrade predisposti; vedere Actions della PR per l’esito effettivo. Tempi aggiuntivi, rivalutazione, collaudo browser/carico e integrazioni complete restano da implementare.

## 0.9.0-dev

- Banca domande con categorie e asset Joomla, lingua, stato, editor/subform e checkout/checkin nativi.
- Scelta singola, vero/falso e multipla: validazione, chiavi di correzione separate e nuove revisioni immutabili a ogni salvataggio.
- Quiz nelle bozze: composizione per revisioni esplicite, punti, selezione/estrazione configurata, soglia esatta, timer/tentativi/intervallo/feedback e cancellazione con dipendenze.
- ACL su corso e banca, revisione aggregata, confini delle risposte corrette e guide/override aggiornati.
- Tentativi, estrazione runtime, autosave, valutazione, feedback studente e pubblicazione dei quiz restano chiusi fino all'incremento successivo.

## 0.8.0-dev

- HTML5 con upload MP4/H.264/AAC per lezione, durata/codec rilevati da ffprobe e asset privati immutabili.
- Quarantena e rescan con policy antivirus condivisa; nessuna assegnazione arbitraria di asset o sostituzione di video pubblicati.
- Consegna GET/HEAD/Range con accesso Joomla corrente, PHP limitato a64MiB oppure offload Nginx interno, senza fallback pubblico.
- Player nativo con progressi comuni e nessuna dipendenza dal consenso ai provider esterni; nuove regioni admin documentate.
- Verifica HTTP con ffprobe e Nginx reali; browser decoder, carico, altri codec/storage e cleanup ancora da completare.

## 0.7.0-dev

- Cloudflare Stream opt-in in modalità API: segreti server via riferimenti ambiente, verifica remota di privacy/account/dominio/origini e token temporanei senza download.
- Scadenza limitata dalle finestre didattiche note; rinnovo con nuovo controllo Joomla, generazione per sessione e conservazione progressi/posizione.
- Chiamate provider fuori dai lock di scrittura e nuova autorizzazione prima della consegna; nessuna chiamata API durante i salvataggi del progresso.
- Adapter SDK Stream, messaggi di rinnovo e test API/SDK simulati, accesso revocato durante risposta e upgrade dalla0.6.
- Firma locale, HTML5, diagnostica autonoma, cache e collaudo reale provider/carico restano da completare.

## 0.6.0-dev

- Player YouTube/Vimeo opt-in tramite plugin Joomla, SDK ufficiali caricati dopo consenso e grant server.
- Pubblicazione video con durata verificata e snapshot immutabile; aula con nuove regioni CSS e materiali corso/lezione.
- Sessioni, sequenze e replay, copertura unica, budget temporale server, resume concorrente e controllo ACL corrente.
- Test dominio, integrazione Joomla/database, endpoint HTTP e mock del trasporto browser; upgrade da 0.5 con storico conservato.
- Cloudflare Stream/HTML5, metadati remoti, prove SDK reali e test di carico restano da completare.

## 0.5.0-dev

- Materiali di corso/versione e lezione: upload, metadati, ordine, sostituzione immutabile e rimozione del collegamento in bozza.
- Storage fuori webroot, allowlist e controlli MIME/firme/archivi bounded, quarantena e scansione ClamAV opzionale via socket locale.
- Download con accesso corrente, ownership/parent, SHA256, attachment/nosniff/no-store e Range/HEAD; materiali facoltativi senza credito didattico.
- Pubblicazione include hash dei materiali e richiede asset pronti; nuove regioni CSS e override documentati.
- Offload, cleanup, preview e riuso asset ancora da completare.

## 0.4.0-dev

- Iscrizione automatica/ripresa e aula per lezioni di testo, con login nativo Joomla.
- Completamento idempotente e transazioni per iscrizione, contatori obbligatori e data di conclusione separata dallo stato di accesso.
- Identità e livelli aggiornati dal core a ogni richiesta, anche dopo revoca con sessione aperta.
- Test con processi concorrenti, revoca/ripristino, ownership, CSRF e flusso HTTP studente.
- Modalità approvazione, player/materiali, quiz, attestati e integrazioni restanti ancora in sviluppo.

## 0.3.0-dev

- Pubblicazione validata dei corsi di testo tramite workflow e plugin Publishing Joomla; transazione unica, versione immutabile, hash ed edizione iniziale.
- Catalogo paginato e scheda pubblica con programma, filtro categoria/discendenti, autorizzazioni correnti e nuovi ID CSS.
- Test integrativi workflow/veto/immutabilità/visibilità e rendering HTTP; risultati effettivi nella PR.
- Video, materiali, iscrizioni e fruizione studenti ancora in sviluppo.

## 0.2.0-dev — 31 ottobre 2026

- Aggiunti servizio applicativo DI, prima bozza didattica, editor del programma e comandi MVC nativi.
- Implementati moduli e lezioni testo/video, obbligatorietà, modifica, riordino e rimozione con controlli delle dipendenze.
- Controlli centralizzati di ACL, parent, revisione aggregata/elemento e immutabilità delle versioni pubblicate; query e transazioni brevi.
- Integrazione editor/filtro HTML core, provider dal registro estensibile e normalizzazione delle sorgenti.
- Nuovi test integrativi e HTTP del flusso completo. Nessuna pubblicazione o fruizione studenti ancora attiva.

## 0.1.0-dev — 31 ottobre 2026

Primo incremento di sviluppo, non release del sistema e-learning completo.

- Importate le specifiche funzionali, tecniche, sicurezza/provider, servizi/endpoint e matrici di collaudo concordate.
- Aggiunti package Joomla, libreria contratti, componente MVC, schema37tabelle/53FK e installer conservativo con verifiche dello schema trattenuto.
- Implementata gestione dei corsi in bozza con categorie, livelli, lingua, asset ACL, check-in/out e controllo delle revisioni.
- Inclusi i quattro plugin video con registrazione lazy e validazione locale delle sorgenti; riproduzione esplicitamente non disponibile.
- Aggiunte regole dominio iniziali, traduzioni, WAM, prime regioni CSS pubbliche e guida agli override.
- Predisposte build riproducibile e CI PHP8.3/8.4, MySQL8.4/MariaDB10.6, installazione/aggiornamento Joomla, test MVC e rendering HTTP.
- Pubblicazione, fruizione, quiz persistenti, attestati e integrazioni rimanenti restano nello stato documentato in IMPLEMENTATION_STATUS.md.

## Requisiti — 7 novembre 2026: durata video

- Registrata come prossima priorità R-06 l’acquisizione automatica della durata dai provider/file, con inserimento manuale di ripiego e gestione coerente degli scarti player/server.
- Aggiornati piano, limiti attuali, checklist e tracciabilità; runtime/pacchetto0.38 invariati. La funzione esterna completa è ancora da implementare.

## Processo e roadmap — 7 novembre 2026

- PR in bozza con controlli leggeri; integrazioni per il candidato pronto, due coppie PHP/database e Chromium. Matrice completa disponibile manualmente prima di milestone/rilascio o interventi di compatibilità; nessuna rimozione di scenari dalla suite.
- Restyling del backend/ingresso e menu amministrativo JD Learn nella sidebar nativa pianificati in M01/M10, con verifica ACL, installazione/upgrade e navigazione accessibile. Non ancora implementati.
- Versione applicativa e pacchetto0.37 invariati.

## Documentazione — 5 novembre 2026

- Consolidato lo stato attuale per funzionalità; separati piano residuo, perimetro delle fasi e cronologia.
- Allineati matrice, problemi aperti, prove del build e guide alla 0.34.1; distinte funzioni assenti e collaudi pendenti.
- Nessuna modifica del runtime, della versione o dei criteri di accettazione.

## Requisiti funzionali1.3 —4novembre2026

- Recepita esplicitamente la richiesta di aggiornamento dei corsi pubblicati con storico Joomla e gestione semplificata dei docenti, campi nativi e viste di menu.
- Aggiornate specifica funzionale/tecnica, matrice e catalogo con AC-119–125, registro della modifica e criteri residui M01; nessun requisito dichiarato completo per effetto dell’aggiornamento documentale.
- Pacchetto runtime invariato0.31.0-dev; verifiche della PR38 registrate separatamente dai nuovi criteri di accettazione.

## Riallineamento documentale della baseline0.19

- Riepilogo/README aggiornati ai flussi realmente presenti, schema41tabelle/58FK e integrazioni core ancora mancanti.
- Matrice dei210requisiti con descrizione/perimetro/scenari baseline preservati, sorgenti e prove parziali distinti dai criteri residui; nessuna accettazione completa dedotta dalla CI.
- Registro delle differenze aperte (preassegnazione senza livello, provider aggiuntivi, ritiro/erogazione, ID pagina e fallback di ripresa), evidenze del build0.19 e piano esecutivo residuo.
- Note storiche conservate separatamente e controllo di coerenza documentale nella verifica strutturale. Nessuna modifica al codice distribuito o al pacchetto0.19.
## 0.41.58-dev — URL SEF con alias per catalogo e percorsi (IMP-07)

- Genera segmenti Joomla nel formato ID-alias per corsi, categorie e percorsi; mantiene il parsing degli URL numerici esistenti e usa il database Joomla con parametri associati.
- Il canonical delle viste pubbliche risolve al percorso SEF con alias. Restano gli ID per disambiguare alias ripetuti tra lingue e contenitori.
- PR IMP-07 in preparazione per la milestone **1.0.0**. Collaudo Joomla manuale in [testbook](docs/QA_TESTBOOK.md#imp-07--url-sef-con-alias-per-corsi-categorie-e-percorsi).
## 0.41.61-dev — vista elenco del catalogo (IMP-07, PR #211 integrata)

- Consente di passare dalla griglia all’elenco nel catalogo corsi e nella pagina di una categoria; selezione tramite filtro allowlist e mantenuta nell’URL insieme agli altri filtri.
- La vista elenco si adatta agli schermi stretti e usa le stesse schede e gli stessi risultati autorizzati della griglia.
- PR [#211](https://github.com/jeckodevelopment/jd_learn/pull/211) integrata con squash nel commit `ca6fecc685902c60527ea4d74f436f87ce9f4d12`, milestone **1.0.0**; CI standard 4/4 nel [run 42920368304](https://github.com/jeckodevelopment/jd_learn/actions/runs/42920368304). Pacchetto [JD-Learn-0.41.61-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42920368304/artifacts/13811701431), wrapper SHA-256 `e99f8024ae32bc221234262697dad8a196673994cafbcc7a32493c2dce500d12`, ZIP Joomla SHA-256 `cc7df8c79e4e3dc8717337df4d427eb43f1b15b61618ee9233aefd92c8201174`, disponibile fino al 24 novembre 2026. Collaudo Joomla manuale pendente nel [testbook](docs/QA_TESTBOOK.md#imp-07--vista-elenco-del-catalogo-04161-dev).
