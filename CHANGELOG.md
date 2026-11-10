## 0.41.54-dev — pulizia programmata dei materiali orfani (IMP-06)

- Aggiunta attività Joomla Scheduler `jlms.materials.cleanup`: elimina in batch limitato asset locali senza riferimenti, più vecchi di 24 ore, e i relativi blob privati. I riferimenti a lezioni, allegati, attestati e job sono esclusi; le sostituzioni immutabili restano conservate.
- L’attività è disponibile nel plugin `plg_task_jlms`, ma va pianificata dall’amministratore. Non viene creata automaticamente all’installazione.
- Collaudo automatico di storage e riferimenti previsto nel CI; test manuale Joomla descritto nel testbook.

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

- PR #192 corregge issue #172: il nuovo corso mostra l’opzione “Usa globali” nel campo Timezone nativo; al salvataggio usa il fuso globale IANA del sito. Fusi espliciti, date UTC e corsi esistenti invariati. CI standard 4/4 superata nel [run ${run}](https://github.com/jeckodevelopment/jd_learn/actions/runs/${run}); pacchetto installabile [JD-Learn-0.41.52-dev](${link}), SHA-256 interno `${inner}`, wrapper `${outer}`. Collaudo Joomla manuale pendente in [testbook](docs/QA_TESTBOOK.md#pr-192--fuso-globale-per-nuovi-corsi).


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
- Upgrade del workflow idempotente; prove Joomla d’integrazione per transizioni, catalogo, accesso assegnato e installazioni ripetute.
- CI standard 4/4 superata nel run 42704783478; merge `1c42b07f3fb7ec7d605f455dcf431510282ef5f2`; pacchetto [JD-Learn-0.41.42-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42704783478/artifacts/13711989978), SHA-256 interno `0e8ab0086a7305829b24f76e5859369213320d8529eda56a9556edaf7f0ad6fb`; collaudo manuale Joomla pendente.

## 0.41.41-dev — workflow di revisione della versione didattica (PR #153 integrata)

- Estende il workflow Joomla installato da JD Learn con invio In revisione, restituzione e approvazione; l’upgrade è idempotente e non sovrascrive workflow amministrativi personalizzati.
- Blocca al livello applicativo le modifiche alla bozza durante la revisione. La versione live e gli iscritti correnti restano invariati sino all’approvazione.
- L’approvazione usa i controlli di pubblicazione esistenti e richiede il riepilogo d’impatto per una nuova versione.
- CI standard 4/4 superata nel run 42701584797 (PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri); runtime integrato nel commit `a754805387b41bd0509f898e15bd380f76a45645`.
- **Collaudo manuale Joomla:** resta aperto; testare autore/revisore con ACL distinte, invio, blocco dei salvataggi, restituzione, prima pubblicazione, approvazione di aggiornamento, accesso studenti e upgrade ripetuto.

## 0.41.40-dev — navigazione dashboard e stato provider (issue #149, integrata PR #151)

- Riordinati i collegamenti rapidi: Corsi, Percorsi, Categorie corsi, Categorie percorsi, Banca domande, Docenti, Campi aggiuntivi, Plugin video. I controlli ACL esistenti restano applicati.
- Il riepilogo provider mostra sempre le quattro estensioni incluse e distingue installazione, attivazione, disponibilità e configurazione senza esporre segreti.
- Aggiornati test HTTP/unitari e guida dashboard; collaudo manuale Joomla aggiunto al testbook.
- CI standard 4/4 superata nel run 42679096692; commit integrato `2dd9d41c2e7496143a009da763fa9738871321c0`.
- **Collaudo manuale Joomla:** verificare l’ordine, i ruoli, le traduzioni IT/EN e gli stati provider abilitando/disabilitando e configurando le estensioni nel sito di prova; resta pendente.

# Changelog

## 0.41.39-dev — accesso amministrativo ai percorsi (integrata, PR #147)

- Aggiunti la voce Percorsi formativi nel sottomenu JD Learn e la scorciatoia dashboard, entrambi filtrati con `jlms.pathways.manage`; le categorie rispettano anche le ACL native Joomla.
- Aggiornati i test di installazione del menu e HTTP dashboard per verificare la nuova scorciatoia e le icone visibili senza un conteggio rigido.
- Aggiunta la guida amministrativa dei percorsi. CI standard 4/4 superata nel [run 42674520291](https://github.com/jeckodevelopment/jd_learn/actions/runs/42674520291). Pacchetto [installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42674520291/artifacts/13699268584); il collaudo manuale Joomla resta pendente.


## 0.41.38-dev — rilascio relativo delle lezioni (candidato)

- L’autore può impostare un’attesa di 0–3650 giorni dall’avvio effettivo dell’iscrizione. Il rilascio è calcolato in UTC a giorni di 24 ore; la disponibilità effettiva rispetta insieme `publish_up` assoluto e `release_after_days`, con inizio incluso e chiusura esclusiva.
- Prima dell’avvio, corpo, campi, allegati, player, completamento, ripresa e accessi diretti restano protetti. L’aula indica che la lezione sarà disponibile dopo l’avvio e poi mostra la data effettiva. Almeno una lezione obbligatoria deve essere disponibile dal giorno zero.
- Le iscrizioni automatiche e manuali sono rifiutate quando una lezione obbligatoria non può diventare accessibile prima della chiusura più vicina fra lezione, corso, edizione e iscrizione.
- **Comportamenti da testare in Joomla:** creare un corso con una lezione obbligatoria iniziale e una seconda lezione con rilascio a 2 giorni; pubblicare e iscrivere uno studente. Prima dell’avvio, verificare avviso senza contenuti e diniego di URL diretti, materiali/player e completamento; avviare il corso e controllare che la seconda lezione mostri la data prevista e resti bloccata; dopo 48 ore verificare accesso e completamento. Combinare il rilascio relativo con apertura/chiusura assoluta e controllare l’intersezione. Impostare tutte le lezioni obbligatorie oltre il giorno zero e verificare il rifiuto di pubblicazione; configurare una scadenza precedente alla data di rilascio e verificare il rifiuto dell’iscrizione. Ripetere l’ammissione automatica e quella manuale e controllare la conservazione dei progressi delle iscrizioni esistenti.
- PR [#143](https://github.com/jeckodevelopment/jd_learn/pull/143) integrata nel commit `7681473d34e23ec633fcf9fc59c3711e644b2d98`; CI standard 4/4 superata nel [run 42652681260](https://github.com/jeckodevelopment/jd_learn/actions/runs/42652681260). Pacchetto installabile [JD-Learn-0.41.38-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42652681260/artifacts/13690028782), ZIP wrapper da estrarre una volta: SHA-256 wrapper `b8a7b72014647d2a2cb7adad9c588318fed014403ba745d9cf59564ba6ca968d`, pacchetto Joomla interno `382cc48ce54045326041f0a84bcfaa7db76d32ee2b3bc73bbfa5bd2bc3b2de22` (526473 byte), scadenza 23 novembre 2026; collaudo manuale Joomla pendente.

## 0.41.37-dev — finestre di disponibilità delle lezioni

- L’autore può impostare «Disponibile dal» e «Disponibile fino al» nel fuso del corso; i valori vengono normalizzati in UTC, validati e inclusi nello snapshot strutturale della versione. Il preflight avvisa sulle finestre pianificate e la pubblicazione rifiuta intervalli invalidi o lezioni obbligatorie irraggiungibili entro le finestre del corso.
- Aula e outline mostrano la data di rilascio; prima/dopo la finestra corpo, campi, materiali, player, quiz e mutazioni restano protetti anche sugli endpoint diretti. Le lezioni obbligatorie future restano nel denominatore.
- `release_after_days` relativo all’avvio dell’iscrizione resta non supportato; la verifica di compatibilità con scadenze individuali resta residua.
- **Comportamenti da testare in Joomla:** impostare sul corso un fuso diverso da UTC, salvare una lezione con apertura futura e chiusura successiva, riaprire il form e verificare gli orari locali; pubblicare; da studente verificare la data visibile senza contenuti/materiali/player e i dinieghi di completamento e URL diretti; dopo l’apertura verificare la fruizione, dopo la chiusura verificare il blocco; tentare una finestra senza sovrapposizione con la disponibilità del corso e verificare che la pubblicazione sia rifiutata. Controllare che il conteggio delle attività obbligatorie non escluda quelle future.
- PR [#141](https://github.com/jeckodevelopment/jd_learn/pull/141) integrata; CI standard 4/4 superata nel [run 42646076977](https://github.com/jeckodevelopment/jd_learn/actions/runs/42646076977). Pacchetto [JD-Learn-0.41.37-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42646076977/artifacts/13688665407), SHA-256 `55c3c53886c0f668ee1113a24941a64ff8ad299c23a91afbec29c79d9d06b6a3` (artifact SHA-256 `e9eb13658a7cdf0f1f3e3b7db47657ddda7ec8714620b6a2eebe5ccc09c0e461`), scadenza 23 novembre 2026; collaudo manuale Joomla pendente.

## 0.41.36-dev — sequenziamento delle attività obbligatorie

- Le regole della bozza consentono di scegliere navigazione libera o sequenziale. In modalità sequenziale, la pubblicazione congela una catena che usa come prerequisito l’attività obbligatoria precedente nell’ordine dei moduli e delle lezioni; i prerequisiti espliciti restano attivi e il grafo combinato è validato contro i cicli.
- L’aula e gli endpoint di contenuti, materiali, player, quiz, completamento e ripresa impediscono l’accesso finché l’attività precedente richiesta non è completata. Dashboard, iscrizioni e percorsi riconoscono la nuova modalità.
- Il rilascio programmato per data non è incluso.
- **Comportamenti da testare in Joomla:** creare in bozza tre attività obbligatorie in ordine A/B/C e una facoltativa; scegliere Sequenziale e pubblicare. Da studente verificare che B e C siano bloccate con collegamento all’attività richiesta, che URL diretti/player e completamento siano negati, che completare A sblocchi B e completare B sblocchi C. Verificare che la facoltativa non blocchi l’attività obbligatoria seguente, che la modalità Libera consenta la navigazione salvo prerequisiti espliciti, che gli iscritti esistenti mantengano le regole congelate e che un prerequisito esplicito inverso incompatibile sia rifiutato in pubblicazione.
- PR #138 integrata in main (`24013410e41d1c51361762405c67a3a2237c6616`); CI standard 4/4 superata nel [run 42636746915](https://github.com/jeckodevelopment/jd_learn/actions/runs/42636746915). Pacchetto [JD-Learn-0.41.36-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42636746915/artifacts/13683107045), ZIP installabile interno `pkg_jlms-0.41.36-dev.zip`, SHA-256 `d8e274a9eed880c7750ec638fce268065814cf0c9eca7e0a41c2dd4ea3b4a5c2`; wrapper SHA-256 `e1b9d0424f590a07e23a7cb7ebac7225a61061cd0f2597b4ab669e3cd1d888cd`. Artefatto in scadenza il 23 novembre 2026. Collaudo manuale Joomla pendente.

## 0.41.35-dev — prerequisiti espliciti delle lezioni

- L’autore può collegare una lezione a una o più lezioni richieste nella stessa bozza. Il salvataggio controlla appartenenza alla versione, riferimenti duplicati e cicli; la pubblicazione valida e congela il grafo.
- Nell’aula, le lezioni bloccate mostrano la prima attività richiesta ancora incompleta e un collegamento per raggiungerla. Corpo, materiali e player restano protetti; gli endpoint diretti e i comandi di completamento/ripresa rifiutano la lezione finché tutti i prerequisiti non risultano completati.
- È supportato il prerequisito esplicito in modalità libera. Sequenziamento automatico e rilascio temporale restano da completare.
- **Collaudo manuale Joomla:** creare nella bozza due lezioni A e B; impostare A come prerequisito di B, salvare e pubblicare. Da studente verificare che B mostri il collegamento ad A senza corpo/materiali/player e che URL diretti di materiali e completamento siano negati. Completare A e verificare che B diventi fruibile. Tentare un ciclo A→B→A e l’autopropedeuticità: salvataggio rifiutato. Verificare che una nuova bozza conservi i prerequisiti e che la versione già assegnata non cambi.
- PR [#136](https://github.com/jeckodevelopment/jd_learn/pull/136) integrata nel commit `832d7d4089071002a11eb09cabdc86fae4e3f820`. CI standard 4/4 superata nel [run 42628727538](https://github.com/jeckodevelopment/jd_learn/actions/runs/42628727538): controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium controllato.
- Pacchetto installabile [JD-Learn-0.41.35-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42628727538/artifacts/13679892795), wrapper ZIP da estrarre una volta; SHA-256 wrapper `fe8fa8bc4aa560548bd92db27d74c979e4f3dee7eec361421bc4462dcc212bd2`, pacchetto interno `609719d24445eda91e0f35ab6aa4895cc60a564da54a0995e1499f5d6bdc617ab` (520630 byte), scadenza 23 novembre 2026. Il collaudo manuale Joomla resta pendente.

## 0.41.34-dev — ripresa lezioni audio

- Salva la posizione di ascolto su intervalli di 12 secondi, pausa, seek e uscita pagina; ripristina l’ultimo punto confermato. L’endpoint richiede sessione, CSRF, iscrizione e lezione audio della versione assegnata.
- La posizione è legata all’iscrizione, non assegna completamenti e non cambia la soglia video. Il completamento audio resta esplicito; il browser può perdere l’ultimo tratto se l’uscita avviene prima dell’invio.
- **Collaudo manuale Joomla:** riprodurre un audio per almeno 15 secondi, mettere in pausa e ricaricare; verificare il ripristino. Fare seek all’indietro, ricaricare e verificare il nuovo punto. Confermare che ascolto/fine player non completino la lezione senza pulsante. Provare due iscrizioni e negare richieste senza CSRF o con enrollment/lesson non corrispondenti.
- PR [#134](https://github.com/jeckodevelopment/jd_learn/pull/134) integrata nel commit `ba703f361a8530a0d8f4fb6805025492f78657d6`. CI standard 4/4 superata nel [run 42613814879](https://github.com/jeckodevelopment/jd_learn/actions/runs/42613814879): controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. Pacchetto verificato [JD-Learn-0.41.34-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42613814879/artifacts/13671314050), wrapper ZIP da estrarre una volta, scade il 23 novembre 2026; SHA-256 wrapper `2b49743f65f402b811b5de9f0b9ab85ecb2363ad52380566759adef23c519e50`, pacchetto interno `972b74d91132503634538d2eec68edb149cee7e89fa00b438255ccbd1981f9f1` (518591 byte), versione 0.41.34.


## 0.41.33-dev — lezioni audio autonome

- Aggiunge il tipo lezione Audio con player HTML5 nativo. MP3 e OGG sono validati per estensione, MIME e firma; l’autore carica il file come materiale della lezione. La pubblicazione richiede almeno un audio pronto.
- La riproduzione usa lo streaming privato esistente con supporto Range, `inline`, `no-store` e nuova verifica di sessione, iscrizione, versione, lezione, disponibilità e MIME ad ogni richiesta. Non vengono esposte URL pubbliche o asset ID senza autorizzazione.
- La posizione viene salvata ogni 12 secondi e su pausa, seek e uscita pagina, poi ripristinata al rientro. Il completamento resta manuale; ascolto/fine player non attribuiscono credito. Gli allegati audio restano immutabili dopo la pubblicazione. Trascrizioni non incluse.
- **Collaudo manuale Joomla:** su sito di prova, creare bozza/modulo/lezione Audio, caricare un MP3/OGG nella lezione, tentare pubblicazione senza file (bloccata), caricare e pubblicare (consentita), poi riprodurre/seek e segnare completata da studente iscritto. Verificare diniego anonimo, non iscritto, altra lezione, file revocato e URL diretto; provare anche un WAV o un MP3 rinominato (upload rifiutato).
- PR [#132](https://github.com/jeckodevelopment/jd_learn/pull/132) integrata nel commit `2d0984bc4adf0ee420eed3cdc7a0356f97813e39`. CI standard superata nel [run 42609983073](https://github.com/jeckodevelopment/jd_learn/actions/runs/42609983073): controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. Pacchetto verificato [JD-Learn-0.41.33-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42609983073/artifacts/13669353295), wrapper ZIP da estrarre una volta; 515566 byte, scade il 23 novembre 2026. SHA-256 wrapper `fff003d89ecac607a5a57b7b29972dcd56a3c46cecb2dbc00bcec550aa1073e9`; SHA-256 pacchetto Joomla contenuto `6e80193338036652f6fa1550da29ae9859bfc8a30bb3b47f354111429de09880`; versione interna 0.41.33. Il collaudo manuale Joomla resta pendente.


## 0.41.32-dev — lezioni-documento

- Aggiunge la tipologia di lezione Documento al programma; il docente associa il file tramite la gestione materiali già esistente, con consegna protetta e controlli di accesso/scansione già applicati agli allegati.
- La pubblicazione richiede almeno un allegato pronto, attivo e non di anteprima associato alla lezione. Il completamento resta manuale come per una lezione testuale.
- **Collaudo manuale Joomla:** creare una bozza con un modulo e una lezione Documento; provare a pubblicare senza allegati (deve essere bloccato), quindi caricare un documento nella gestione materiali della lezione e pubblicare. Con un account studente iscritto, aprire la lezione, scaricare il documento e segnare la lezione completata; verificare che URL/ID del file non siano accessibili da anonimo o da studente non iscritto.
- PR [#130](https://github.com/jeckodevelopment/jd_learn/pull/130) integrata nel commit `0c3c0109681cdaca6673dded3c10941c06a2ae20`; CI standard superata nei due stack PHP/database e in Chromium nel [run 42601315214](https://github.com/jeckodevelopment/jd_learn/actions/runs/42601315214). Pacchetto installabile [JD-Learn-0.41.32-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42601315214/artifacts/13666127495), wrapper ZIP da estrarre una volta (514512 byte, scade il 23 novembre 2026). SHA-256 wrapper `b1bd48d638286d90c4ce9e9102851012b3babfcb729af25255b4da1562a76e75`; SHA-256 del pacchetto Joomla contenuto `3cc98f6837810f72c366b98a4ec28d2c4645ccd78d03ec546ef336d44c351a71`; versione interna verificata `0.41.32`. Il collaudo manuale Joomla è ancora pendente. La lezione audio autonoma è integrata dalla PR #132 in 0.41.33-dev, con player HTML5 e streaming protetto; il collaudo manuale Joomla resta pendente.

## 0.41.31-dev — permessi specifici nella sidebar nativa

- Completa issue #113: `plg_system_jlmsmenu` filtra le voci di JD Learn per le autorizzazioni di Banca domande, Categorie, Campi e Plugin video. Le altre voci JD Learn mantengono il requisito `core.manage` del componente; gli altri menu Joomla non sono modificati.
- Il pacchetto attiva il plugin alla prima installazione e conserva lo stato scelto dall’amministratore agli aggiornamenti.
- **Collaudo manuale Joomla:** account con accesso JD Learn ma senza `jlms.quiz.manage`; verificare assenza di Banca domande in sidebar e dashboard e diniego dell’URL diretto. Rimuovere separatamente i permessi `com_jlms.course`, `com_fields`, `com_plugins` e verificare l’assenza rispettiva di Categorie, Campi, Plugin video. Verificare account completo, altri menu Joomla e installazione/upgrade con plugin attivo e disattivato.
- PR [#128](https://github.com/jeckodevelopment/jd_learn/pull/128) integrata nel commit `26c7ad5264dfbbdbdab9c446162220567df2d30e`; quattro job standard superati nel [run 42594246627](https://github.com/jeckodevelopment/jd_learn/actions/runs/42594246627). Pacchetto interno [JD-Learn-0.41.31-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42594246627/artifacts/13663320592), checksum SHA-256 `2212e763bcb10e8089b783586d3ef5a9e68b310fab5f0b2494a4427702619db0`, 514020 byte; artefatto wrapper da estrarre una volta, in scadenza il 23 novembre 2026. CI d’installazione/upgrade superata; collaudo manuale dei ruoli Joomla pendente.


## 0.41.30-dev — ACL nelle scorciatoie Dashboard

- Issue [#113](https://github.com/jeckodevelopment/jd_learn/issues/113): Banca domande ora richiede anche `jlms.quiz.manage`; Categorie corsi e Campi aggiuntivi richiedono i permessi di destinazione oltre all’accesso a JD Learn.
- La parte del sottomenu nativo resta da completare; il testbook distingue il comportamento implementato dal collaudo Joomla pendente.
- **Collaudo manuale Joomla:** gruppo con `core.manage` JD Learn ma senza `jlms.quiz.manage`; verificare assenza della scorciatoia banca domande, diniego dell’URL diretto, e visibilità dei link categorie/campi con permessi mancanti/presenti.
- PR [#126](https://github.com/jeckodevelopment/jd_learn/pull/126) integrata nel commit `92f05f82cc74671b07a5b0c8275f410ddf74756d`; quattro job CI superati ([run 42589362926](https://github.com/jeckodevelopment/jd_learn/actions/runs/42589362926)). Pacchetto [JD-Learn-0.41.30-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42589362926/artifacts/13660408807), checksum interno `5c43dff45c5d121e4f96aa897926e7db8fbf7fec6799406cb7b2a512aab5770c` (511075 byte), artefatto in scadenza 23 novembre 2026; wrapper ZIP da estrarre una volta.

## 0.41.29-dev — traduzioni delle opzioni componente

- Sostituisce la chiave Joomla mancante `JGLOBAL_HISTORY_OPTIONS` con l’etichetta JD Learn tradotta in italiano e inglese, sia nella tab sia nel gruppo della pagina Opzioni.
- Traduce il titolo generato da `com_config` in “JD Learn: Opzioni” / “JD Learn: Options”.
- Issue [#115](https://github.com/jeckodevelopment/jd_learn/issues/115), scenario amministrativo AC-134. PR [#124](https://github.com/jeckodevelopment/jd_learn/pull/124) integrata; CI standard superata ([run 42533814214](https://github.com/jeckodevelopment/jd_learn/actions/runs/42533814214)). Pacchetto [JD-Learn-0.41.29-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42533814214/artifacts/13638104439), SHA-256 interno `7f1810d1d6adb5f4c92ac9237e4bd6c9079932acb6cd9cfd8d3d72ff385bf62b` (510851 byte; scadenza 22 novembre 2026). Collaudo manuale Joomla AC-134 ancora pendente.


## 0.41.28-dev — collegamento tra docenti e corsi

- Mostra nella lista docenti il conteggio dei corsi assegnati nel perimetro gestibile dall’utente; il conteggio apre la lista corsi filtrata per docente.
- Aggiunge il filtro Docente alla lista Corsi e traduce correttamente titolo browser/intestazione della lista docenti.
- Issue [#117](https://github.com/jeckodevelopment/jd_learn/issues/117), scenario AC-133. PR [#122](https://github.com/jeckodevelopment/jd_learn/pull/122) integrata; CI standard superata ([run 42531222588](https://github.com/jeckodevelopment/jd_learn/actions/runs/42531222588)). Pacchetto [JD-Learn-0.41.28-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42531222588/artifacts/13637556798), SHA-256 interno `2e49152eb377dab09b9135bd95910d465b71f4293159a62e18c50a4178e5a628` (510805 byte; scadenza 22 novembre 2026). Collaudo manuale Joomla AC-133 ancora da eseguire.

## 0.41.27-dev — elenco e azioni standard per la banca domande

- Separa l’elenco domande dal form di creazione/modifica e adotta Search Tools Joomla con ricerca, filtri, ordinamento e paginazione.
- Aggiunge duplicazione come nuova bozza indipendente e cancellazione CSRF con conferma, ACL e blocco per revisioni usate da quiz/tentativi.
- Aggiorna il testbook AC-131; nessun nuovo comportamento didattico quiz.
- PR [#120](https://github.com/jeckodevelopment/jd_learn/pull/120) integrata; CI standard completa superata ([run 42527985085](https://github.com/jeckodevelopment/jd_learn/actions/runs/42527985085)). Pacchetto [JD-Learn-0.41.27-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42527985085/artifacts/13636515713), SHA-256 interno `2d5f53f22445eda2445025346fc42878d7652124befd7dc0bd17dd7224ff4506` (509376 byte), scadenza 22 novembre 2026. Collaudo Joomla pendente.

## 0.41.26-dev — elenco docenti secondo convenzioni Joomla

- Rifà l’elenco docenti con Search Tools, ricerca, filtri stato/lingua, ordinamento, paginazione e collegamento al form separato.
- Mostra metadati utili senza esporre categorie docente; non modifica dati, associazioni o regole ACL.
- Aggiunge/aggiorna i passi di collaudo manuale AC-130 nel testbook; la verifica sul sito Joomla è pendente.
- PR [#116](https://github.com/jeckodevelopment/jd_learn/pull/116) integrata; i quattro job standard sono superati ([run 42524671164](https://github.com/jeckodevelopment/jd_learn/actions/runs/42524671164)). Pacchetto [JD-Learn-0.41.26-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42524671164/artifacts/13634039771), SHA-256 interno `869c40a705602af9c1c7e2bd1562e407fce75d38038cefcf67085156c3230ab5` (503665 byte), scadenza 22 novembre 2026.

## 0.41.25-dev — accesso Dashboard nella sidebar

- Aggiunge **Dashboard** come prima voce figlia di JD Learn nel menu amministrativo, collegata alla vista dashboard esistente; Corsi e le altre voci seguono nell'ordine.
- Traduce l'etichetta in italiano e inglese e aggiunge una regressione Joomla sull'ordine delle voci dopo l'upgrade.
- Collaudo manuale di posizione, destinazione e lingue aggiunto al testbook AC-132.
- Nessuna nuova funzione didattica o capacità di fase 2.
- PR [#112](https://github.com/jeckodevelopment/jd_learn/pull/112) integrata; tutti i quattro job CI superati ([run 42520306552](https://github.com/jeckodevelopment/jd_learn/actions/runs/42520306552)). Pacchetto [JD-Learn-0.41.25-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42520306552/artifacts/13633183064), SHA-256 interno `bd0a7796d3f260af9ea2e2d8079265623d987db4fa485716bd1c43f966929b03` (501472 byte), scadenza 22 novembre 2026.

## 0.41.24-dev — correzioni dashboard amministrativa

- Aggiorna nel postflight l'elemento principale della sidebar Joomla rimasto collegato alla lista Corsi, senza toccare voci personalizzate o i collegamenti figli.
- Evita la doppia codifica HTML dei separatori negli URL dei sei riquadri; aggiunge regressioni sui percorsi reali.
- Rimuove le vecchie traduzioni duplicate della frase introduttiva, conservando la descrizione corrente degli strumenti amministrativi in italiano e inglese.
- Collaudo manuale Joomla di upgrade, link, traduzioni e ruoli aggiunto al testbook; AC-132 resta da accettare sul sito di prova.
- Nessuna modifica ai dati didattici o alle funzioni di fase 2.
- PR [#110](https://github.com/jeckodevelopment/jd_learn/pull/110) integrata; CI standard superata ([run 42518292971](https://github.com/jeckodevelopment/jd_learn/actions/runs/42518292971)). Pacchetto [JD-Learn-0.41.24-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42518292971/artifacts/13632870363), SHA-256 interno `5162d657369820d14b082b0e1f14037bf65923f9c8a1c682cde584821ab0c174`, scadenza 22 novembre 2026.

## 0.41.23-dev — dashboard con accessi rapidi

- Risolve [issue #105](https://github.com/jeckodevelopment/jd_learn/issues/105): la voce radice JD Learn apre la dashboard amministrativa.
- Aggiunge riquadri con icone per Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video; usa percorsi Joomla esistenti e li mostra in base ai relativi permessi.
- Il menu root generato su installazione nuova punta alla dashboard; la correzione di upgrade delle installazioni esistenti è in 0.41.24-dev.
- Mantiene la panoramica provider esistente. Statistiche, attestati e altre funzioni future restano fase 2.
- Procedura di collaudo manuale per ruoli, lingue, tastiera e viewport aggiunta al testbook AC-132; l'esecuzione Joomla reale resta da registrare.
- PR [#108](https://github.com/jeckodevelopment/jd_learn/pull/108) integrata; CI standard superata ([run 42513162483](https://github.com/jeckodevelopment/jd_learn/actions/runs/42513162483)). Pacchetto [JD-Learn-0.41.23-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42513162483/artifacts/13630646222), SHA-256 interno `0b3692497d996da91055121c6ca2ed82176d52f87e7a6f92af2ba7a204f25ae3`, scadenza 22 novembre 2026.

## 0.41.22-dev — accesso centralizzato ai campi aggiuntivi

- Risolve l'issue [#99](https://github.com/jeckodevelopment/jd_learn/issues/99), collegata alla presentazione dei campi dopo la PR #98.
- Rimuove il collegamento fuori toolbar dalla lista Corsi e quello dalla lista Docenti; aggiunge la voce sidebar **Campi aggiuntivi**, diretta alla lista nativa `com_fields`.
- La lista nativa apre il contesto JD Learn e consente di scegliere corsi, docenti o lezioni dal filtro contesto di Joomla. Nessuna nuova vista o gestione proprietaria dei campi.
- Le prove automatiche verificano submenu, contesti registrati e assenza dei link dalle liste. PR [#104](https://github.com/jeckodevelopment/jd_learn/pull/104) integrata; CI completa superata. Collaudo manuale Joomla ancora da eseguire.

## 0.41.21-dev — attivazione Privacy e dominio lingua esplicito

- Sposta l'attivazione automatica nel postflight del plugin `plg_privacy_jdlearn`, quando la riga plugin è già stata creata dal suo installer nativo.
- Carica il dominio `plg_privacy_jdlearn` dai percorsi lingua amministrativo e plugin prima degli eventi Privacy; conserva le scelte di attivazione sugli upgrade.
- Aggiunge regressioni CI che controllano l'installazione senza forzare il plugin attivo nel fixture e verificano le quattro stringhe tradotte.
- Il test ha rilevato che il tentativo di attivazione a livello package della 0.41.20 non veniva applicato; corregge quel difetto, senza aggiungere capacità Privacy.
- PR [#102](https://github.com/jeckodevelopment/jd_learn/pull/102) integrata nel merge `0a05f004823156abc750981a11db6598a8cf9972`; CI standard superata ([run 42503477910](https://github.com/jeckodevelopment/jd_learn/actions/runs/42503477910)).
- Pacchetto CI [JD-Learn-0.41.21-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42503477910/artifacts/13626378474): wrapper ZIP da estrarre una volta; SHA-256 del pacchetto interno `40c1743ba9556a6fd96eeba9da1c403edc2f67c91d9b93773daac539c9f380f7`, scadenza 22 novembre 2026. Collaudo Joomla manuale ancora aperto.

## 0.41.20-dev — integrazione Privacy e primo tentativo di correzione

- La prima installazione del pacchetto tentava di abilitare il plugin `plg_privacy_jdlearn`; il successivo test installazione ha mostrato che l'hook package non veniva applicato.
- Il plugin tentava di caricare le stringhe prima di esporre capacità, domini export e impedimenti; la regressione Joomla ha poi mostrato che il dominio restava non risolto.
- Collaudo manuale Joomla richiesto per installazione nuova, upgrade con plugin disabilitato e lingue amministrative italiana/inglese.
- PR [#100](https://github.com/jeckodevelopment/jd_learn/pull/100) integrata; i quattro job CI sono superati ([run 42498937182](https://github.com/jeckodevelopment/jd_learn/actions/runs/42498937182)). Il collaudo Joomla della correzione è ancora pendente.
- Pacchetto [JD-Learn-0.41.20-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42498937182/artifacts/13623954016): contenitore da estrarre una volta; ZIP interno SHA-256 `d640593bd6cb91d9d57b58ed98917ebb8efe698ec2c36ddfbccd17f05d8118f2`, scadenza 22 novembre 2026.
- Correzione in corso: PR #102 sposta l'attivazione nel manifest plugin e rende espliciti il dominio e i percorsi lingua, con regressioni automatiche per entrambi i difetti.

## 0.41.19-dev — accesso ai campi aggiuntivi corso e lezione

- Aggiunge dall’elenco Corsi il collegamento al gestore Joomla `com_fields` filtrato sul contesto `com_jlms.course`.
- Aggiunge dal Programma il collegamento al contesto `com_jlms.lesson`.
- I collegamenti sono mostrati solo agli operatori con `core.manage` su `com_fields`; le definizioni e i valori restano gestiti dal core Joomla.
- Collaudo manuale Joomla AC-129 richiesto; nessun requisito è dichiarato completo da questo incremento.

## 0.41.18-dev — ricerca iscritti nel roster

- Aggiunge al roster amministrativo la ricerca per nome visualizzato o username Joomla, combinabile con i filtri esistenti.
- Limita l'input a 100 caratteri, rifiuta valori non testuali/caratteri di controllo e tratta `%`, `_` e `!` come testo letterale; la query resta limitata al corso autorizzato e non modifica dati.
- Aggiunge regressioni native Joomla e HTTP; PR [#94](https://github.com/jeckodevelopment/jd_learn/pull/94) integrata nel merge `2b411fc`.
- CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium controllato e controlli leggeri ([run 42492865814](https://github.com/jeckodevelopment/jd_learn/actions/runs/42492865814)). Collaudo manuale Joomla ancora aperto.
- Pacchetto CI [JD-Learn-0.41.18-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42492865814/artifacts/13622073971): SHA-256 interno `e1d046a16e11f5b89c2487f450d6088d097d2281553b1205108348ccad5b03ff`, wrapper ZIP verificato, scadenza 22 novembre 2026.

## 0.41.17-dev — percorsi formativi aggregati

- Aggiunge la gestione amministrativa dei percorsi: categoria Joomla dedicata, accesso, lingua, pubblicazione e associazione ordinata fino a 100 corsi.
- Aggiunge catalogo e scheda percorso pubblici con stato di apprendimento per corso e progresso aggregato a peso uniforme. Gli ospiti non vedono progressi personali; corsi nascosti non rivelano titolo o ID e i percorsi non concedono iscrizioni o accesso.
- Non introduce sblocco sequenziale né attestati di percorso, rinviati alla fase 2. Nuovi test Joomla coprono salvataggio, ordinamento, accesso, progresso e conservazione dopo upgrade; la CI standard è superata, il collaudo manuale resta da completare.

## 0.41.16-dev — lifecycle Privacy Joomla

- Aggiunge al pacchetto `plg_privacy_jdlearn`, con dichiarazione delle capacità e domini export per iscrizioni/richieste, progressi, tentativi/risposte e attestati eventualmente emessi.
- Il controllo nativo impedisce la rimozione finché esistono documenti attestato identificativi, job dell'account o notifiche legate alle iscrizioni. Alla rimozione elimina sessioni temporanee, scollega l'account, cancella risposte e motivi testuali e anonimizza i riferimenti utente negli audit, conservando lo storico didattico minimizzato.
- La CI include verifiche su installazione del plugin, dichiarazione capabilities, export e impedimento motivato per job attivi. Da completare la prova su dati personali reali e la policy di conservazione del sito.
- I test end-to-end di un quinto provider video e lo sviluppo di ulteriori provider sono stati differiti alla fase 2; la prima stabile richiede la verifica dei quattro provider già distribuiti.

## 0.41.15-dev — provider video estendibili senza patch al componente

- Rimuove i blocchi a elenco fisso nel salvataggio durata, pubblicazione e fruizione dei video: i provider aggiuntivi passano dal registry comune e dalle capacità dichiarate.
- Il player carica il modulo Joomla convenzionale `media/plg_jlmsvideo_<provider>/js/player.js` dopo autorizzazione e grant server; la chiave è validata dal registry e dal client.
- Stato provider in Programma derivato dai plugin installati. Regressione JS aggiunta per `demo_provider`, oltre ai provider già inclusi.
- Comportamento aggiuntivo: installare un plugin compatibile non richiede patch al componente; restano obbligatori consenso, validazione sorgente, capability di posizione/durata e sicurezza server.
- PR #90 integrata con squash nel merge `0f55bd1`; matrice standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run [42476313500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42476313500)). Test end-to-end con quinto plugin Joomla ancora da eseguire; R-02 resta parziale. Artefatto CI [JD-Learn-0.41.15-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42476313500) (SHA-256 wrapper `e9814b1d68150ea9cb74e2254768b63051bb3872cfd007e793fedc9b1b607d04`, scadenza 22 novembre 2026).


## 0.41.14-dev — migrazione esplicita di un’iscrizione

- Aggiunge dal roster la migrazione singola di iscrizioni verso la versione pubblicata corrente, con selezione esplicita delle equivalenze fra lezioni e anteprima protetta da hash.
- Trasferisce soltanto stati di completamento non quiz approvati; progressi e iscrizione sorgente restano nello storico, mentre quiz, coverage video, risposte e attestati non vengono copiati.
- Conferma con motivo, CSRF, ACL e idempotenza; blocca tentativi aperti, iscrizioni duplicate, edizioni indisponibili/a capienza e corso con erogazione sospesa. Registrazione atomica in job, equivalenze, migrazione e Action Log.
- PR #88 integrata con squash (`f2f8c76`); CI standard superata nei due ambienti PHP/database, Chromium e controlli leggeri (run [42473935482](https://github.com/jeckodevelopment/jd_learn/actions/runs/42473935482)). Collaudo manuale Joomla ancora aperto. Pacchetto `pkg_jlms-0.41.14-dev.zip` verificato: SHA-256 `7a9f08f7ad01c88c04d19e9568d8aebb9d6e5b3258c066d6d8f9b6cd26f335a4`.

## 0.41.13-dev — finestre temporali dei materiali

- Aggiunge date iniziali/finali ai materiali extra, inserite nel fuso del corso e conservate in UTC; modifica delle date disponibile soltanto sulla bozza.
- La pubblicazione accetta finestre valide; il preflight le segnala come avviso e l’aula continua a nascondere gli allegati fuori finestra.
- Intervalli invertiti o date ambigue/non valide vengono rifiutati prima di creare o sostituire un asset.


## 0.41.12-dev — preflight materiali non pronti

- Il controllo preliminare del Programma segnala allegati in quarantena, con scansione ClamAV obbligatoria non pulita, marcati come sola anteprima o con finestra di pubblicazione configurata, in linea con la validazione server.
- Il preflight legge soltanto i metadati degli allegati della bozza; aggiunte verifiche di dominio e regressione HTTP con materiale fixture temporaneamente inserito e rimosso.
- Nessuna modifica ai file, ai permessi o ai contenuti pubblicati.


## 0.41.11-dev — preflight durata video

- Il controllo preliminare della bozza segnala come bloccante il video privo di durata tra 1 secondo e 8 ore o senza conferma registrata, coerentemente con il validatore server.
- Il controllo usa solo i metadati già salvati e non interroga provider o carica i corpi delle lezioni.
- Aggiunte verifiche di dominio per durata mancante e valida; documentazione Programma sincronizzata nel pacchetto. PR #78 integrata (merge 64dcf6c); CI standard superata nei due ambienti PHP/database e Chromium (run 42441526162). Collaudo manuale Joomla ancora aperto. Artefatto verificato: SHA-256 interno `3a67a2e92424cf426c593f0cedcee7efb4d470f674095baacc898878568edf1a`.


## 0.41.10-dev — riepilogo preliminare di pubblicazione

- Mostra nella bozza del programma gli errori strutturali bloccanti separati dagli avvisi facoltativi su riepilogo e copertina.
- Mantiene la verifica definitiva in `PublicationService`; il controllo preliminare non carica i corpi delle lezioni né contatta provider.
- Aggiunge test unitari e regressioni HTTP per il passaggio da modulo vuoto a lezione obbligatoria; PR #74 integrata nel merge [c023fc4](https://github.com/jeckodevelopment/jd_learn/commit/c023fc4ed29f3d643414cd5afc83b3ad42615dba); CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run [42439350519](https://github.com/jeckodevelopment/jd_learn/actions/runs/42439350519)). Collaudo manuale Joomla ancora aperto. Pacchetto verificato: SHA-256 `4d95e9627ddd6db338961da374e35721262e95356c817d46940e5b6ddc469ab3`.


## 0.41.9-dev — controlli separati di ammissione ed erogazione

- Aggiunge i comandi motivati per chiudere/aprire nuove ammissioni e sospendere/riprendere l’erogazione, con anteprima di iscritti e quiz aperti, conferma, conflitto su revisioni obsolete e storico transazionale.
- Blocca nuove iscrizioni automatiche/manuali quando le ammissioni sono chiuse; la sospensione nega le azioni didattiche protette senza modificare iscrizioni, risposte o progressi.
- Introduce migrazione 0.41.9.0, comandi dal roster, traduzioni e guida operativa. PR #70 integrata nel merge 70f6ed7; CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6, Chromium e controlli leggeri (run 42428496835); collaudo manuale Joomla ancora aperto.


## 0.41.8-dev — ingresso amministrativo più chiaro

- Mette in evidenza l’accesso alla gestione corsi, raggruppa gli strumenti secondari e raccoglie lo stato provider in un dettaglio espandibile.
- Aggiunge stile responsive confinato alla dashboard tramite Joomla Web Asset Manager; nessuna modifica ai permessi o al template amministrativo.
- Aggiunge stringhe italiane e inglesi e documenta gli ID/classi per gli override. PR #68 integrata; CI standard superata su PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium (run [42423237440](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440)). Collaudo visivo e dei ruoli nel backend Joomla reale ancora da svolgere.

## 0.41.7-dev — campi personalizzati delle lezioni

- Abilita il contesto nativo `com_jlms.lesson` in Joomla Fields e aggiunge i campi dinamici all’editor delle lezioni.
- Salva i valori tramite il modello Fields, controllando permesso `core.edit.value`, livello di accesso e contesto; copia i valori nelle nuove bozze.
- Congela valori e metadati dei campi alla pubblicazione e li mostra nell’aula dal relativo snapshot, filtrando per livello di accesso e facendo escaping dell’output.
- Regressione Joomla aggiunta per salvataggio, snapshot, impatto della pubblicazione e resa nell’aula. PR #65 integrata; CI standard superata nei due ambienti PHP/database e Chromium ([run 42420998171](https://github.com/jeckodevelopment/jd_learn/actions/runs/42420998171)). Il collaudo manuale Joomla resta da eseguire.

## 0.41.6-dev — continuità dell’aula dopo il ritiro dal catalogo

- L’accesso degli iscritti autorizzati non dipende più dallo stato editoriale, dal livello di catalogo o dalla scadenza della scheda del corso. Restano attivi i controlli correnti su account, livello contenuto, categoria, lingua, versione, edizione e finestre dell’iscrizione.
- Le nuove ammissioni restano soggette alla pubblicazione e disponibilità del corso.
- Aggiunte regressioni d’integrazione per aula testo/video. PR #64 integrata nel commit [303f781](https://github.com/jeckodevelopment/jd_learn/commit/303f78124ef94a6968a0f0997770bb80e3d28800); CI standard finale superata in PHP/database e Chromium ([run 42414492267](https://github.com/jeckodevelopment/jd_learn/actions/runs/42414492267)). Collaudo manuale Joomla reale ancora aperto.
- R-03 è parziale: mancano le azioni amministrative separate di chiusura iscrizioni e sospensione erogazione, con riepilogo, motivo e audit.

## 0.41.5-dev — ripresa alla prima attività obbligatoria incompleta

- Se non esiste un’ultima attività valida, Riprendi sceglie la prima lezione obbligatoria non completata nell’ordine del programma.
- Se tutte le attività obbligatorie sono completate, Riprendi apre la prima lezione per consentire la revisione.
- Mantiene controlli di accesso, versione e disponibilità; lettura senza modificare progressi o iscrizione.
- Test Joomla aggiornato per attività obbligatorie già completate, fallback alla successiva incompleta e ultima attività ancora valida. PR #62 integrata; quattro job standard superati ([run 42412135232](https://github.com/jeckodevelopment/jd_learn/actions/runs/42412135232)). Pacchetto verificato: [0.41.5-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42412135232/artifacts/13590285852), SHA-256 `baaf4447da1f9559472ea889fdc34c224259501d061a84e797d10b8709bbe70c`.

## 0.41.4-dev — editor corso più lineare

- Riunisce dati, copertina e docenti nella scheda Corso; colloca regole nel contesto Programma e consolida accesso e calendario.
- Lascia invariati modello dati, salvataggio, versioni e permessi.
- PR #60 integrata in main. Quattro job standard superati: controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium ([run 42410200676](https://github.com/jeckodevelopment/jd_learn/actions/runs/42410200676)). Pacchetto: [artifact 13589411586](https://github.com/jeckodevelopment/jd_learn/actions/runs/42410200676/artifacts/13589411586).

## 0.41.3-dev — valore corretto per il checkbox della risposta

- Rimuove `default="0"` dal campo checkbox Joomla: il core usa quel default anche come attributo `value`, così una casella selezionata inviava `0` invece di `1`.
- Aggiorna il test HTTP affinché legga nomi e valori dei checkbox dal markup nativo Joomla e simuli la selezione reale dell’autore.
- La causa è individuata; la correzione resta da verificare sul sito dell’utente dopo installazione.


## 0.41.2-dev — diagnosi degli errori della banca domande

- Mostra i messaggi di validazione nativi Joomla quando il form non supera la validazione, così da indicare il campo rifiutato.
- Distingue gli errori semantici di testo, opzioni, etichette e risposte corrette.
- La segnalazione reale persiste dopo0.41.1; questo incremento è diagnostico e non è dichiarato risolutivo finché non viene riprovato nell’installazione che ha segnalato il problema.


## 0.41.1-dev — salvataggio delle opzioni domanda

- Normalizza i checkbox non selezionati della banca domande sia quando il browser li omette sia quando Joomla li filtra come stringa vuota.
- Aggiunge regressioni di dominio e invio HTTP del form nativo che riproducono il checkbox non selezionato.
- Incremento mirato alla segnalazione di salvataggio 422; non dichiara l’intero authoring quiz accettato.


## 0.41.0-dev — campi nativi nella scheda pubblica del corso

- Resa dei campi editoriali correnti con com_fields, plugin di tipo e layout/override nativi; posizioni dopo titolo, prima e dopo la presentazione. Nascosti/manuali ed elementi vuoti non producono una sezione vuota.
- Accesso corrente a corso, campo e gruppo, pubblicazione, categoria e lingua; aggiornamenti dei valori senza ripubblicare il programma e senza mutare versioni assegnate o progressi.
- Prove native/HTTP per tipi testo/area/lista, impostazioni di visualizzazione, revoche nella stessa sessione e override, più conservazione dopo upgrade. Snapshot didattici, campi lezione e ripristino completo dei valori tramite Content History restano aperti.

## 0.40.1-dev — gestione nativa dei plugin video

- Sostituita la voce fuorviante Stato provider video con Plugin video: apre com_plugins filtrato su jlmsvideo, azzerando i filtri precedenti e la paginazione.
- Visibilità e gestione restano soggette ai permessi nativi Joomla per com_plugins. La vecchia dashboard non è più destinazione del menu e non rappresenta un controllo di riproduzione.
- Prove aggiornate di navigazione HTTP, filtri persistiti, quattro plugin e conservazione del menu dopo upgrade.

## 0.40.0-dev — accesso diretto ai corsi e menu amministrativo

- JD Learn apre direttamente l’elenco corsi. Menu nativo Joomla con Corsi, Categorie corsi, Banca domande, Docenti e Stato provider video, installato/aggiornato dal manifest.
- La pagina precedente resta accessibile esplicitamente per la diagnostica; nessun menu globale per pagine che richiedono il contesto corso.
- Controlli di installazione/upgrade del menu e navigazione HTTP autenticata. Restyling completo, collaudo ruoli/browser e campi/snapshot restano aperti.

## 0.39.0-dev — durata automatica dei video

- Metadati nell’editor con preview SDK YouTube/Vimeo, API server Stream e durata MP4 da file invariata. Automatico predefinito, ripiego manuale distinto, nessuna casella di conferma ordinaria.
- Ricevute di sessione per autore/sorgente/revisioni, scadenza e consumo; scope, ACL, checkout e CSRF. Richieste remote fuori dalle transazioni, bozze incomplete salvabili e durata preservata sulle modifiche della stessa sorgente.
- Tolleranza player/server max(2s,min(5s,1%)), durata osservata per intervalli e denominatore, copertura/idempotenza e dati congelati preservati. Grandi incongruenze restano bloccate. Nessun progresso generato dall’anteprima amministrativa.
- Vimeo0.6.1: un errore temporaneo di caricamento SDK non compromette i successivi tentativi di anteprima/player. Import locali dell’authoring con versione cache coerente al componente.
- Controlli su metadati/provenienza, sorgente cambiata/risposta tardiva, consenso e completamento con scarti; collaudo reale dei quattro provider ancora distinto dalle fixture automatiche.

## 0.38.0-dev — regole didattiche e soglia video della versione

- Scheda Regole didattiche nel corso con salvataggio prima dell’apertura della bozza; editor contestuale nel Programma, lettura delle versioni pubblicate e riuso dell’avviso modifiche non salvate.
- Soglia video configurabile da0,01% a100%, massimo due decimali, default90%; conversione/confronto esatti e validazione anche prima del congelamento. Navigazione libera e tutte le attività obbligatorie restano le regole operative.
- Salvataggio POST/token con ACL correnti, checkout e CAS. Riepilogo di pubblicazione con soglia precedente/nuova e invalidazione se le regole cambiano; storico e iscritti conservano la versione assegnata.
- Prove di completamento a soglie diverse per iscrizioni vecchie/nuove, form nativi e conservazione dopo upgrade. Regole avanzate, attestati e override per lezione restano aperti.

## 0.37.0-dev — recupero del programma storico come nuova bozza

- Comando nativo dal programma storico con conferma POST/CSRF, ACL fresche, checkout, revisioni e audit atomico. Rigenera gli ID mantenendo chiavi logiche, lezioni, riferimenti video, quiz e materiali.
- Nessuna sovrascrittura delle altre bozze, dei dati promozionali correnti o dei dati studente; la versione attiva cambia soltanto con la pubblicazione ordinaria e il riepilogo di impatto. Modifica programma riapre la bozza più recente.
- Prove di recupero e ripubblicazione con YouTube, Vimeo e Cloudflare Stream; trasporto Stream controllato, senza credenziali o rete reali. Il collaudo SDK/browser reale rimane distinto.

## 0.36.0-dev — riepilogo e conferma degli aggiornamenti didattici

- Riepilogo separato prima di pubblicare una bozza che aggiorna il programma attivo: conteggi e dettagli di moduli, lezioni, quiz e materiali aggiunti/modificati/rimossi, regole e assegnazioni esistenti per versione.
- Confronto per chiavi logiche e riferimenti immutabili, senza falsi cambi per gli ID rigenerati dalla copia di lavoro; corpi e descrizioni confrontati tramite hash senza esporli nel riepilogo.
- Conferma esplicita POST/CSRF con ticket di sessione, validità15minuti e consumo singolo; impronta ricontrollata nella transazione di pubblicazione, con ACL fresche e rifiuto delle modifiche concorrenti. Nessuna migrazione o modifica dei dati studente.
- Prima pubblicazione e ritiro/ripubblicazione della stessa versione conservano il percorso precedente. Tutti i controlli ordinari di pubblicazione restano applicati dopo la conferma.

## 0.35.0-dev — campi personalizzati nativi nell’editor corso

- Scheda Campi personalizzati presente quando Joomla aggiunge campi al form del corso; resa di campi e gruppi tramite il core, con disabilitazione sul corso bloccato da un altro operatore.
- Salvataggio di com_fields tramite gli eventi del plugin Fields nativo, conservando i valori per i client che omettono il gruppo e rifiutando revisioni obsolete prima delle scritture.
- Prove native/HTTP del percorso di creazione, modifica, errore e annullamento. Snapshot didattici e campi lezione rimangono residui M10.
- CI completa conservata sulle PR e avvio manuale disponibile; rimosso il secondo avvio automatico al merge in main per evitare esecuzioni duplicate.

## 0.34.1-dev — protezione delle modifiche nel programma

- Avviso nativo del browser quando si abbandona il modulo o la lezione con modifiche non salvate, incluse modifiche TinyMCE non sincronizzate nella textarea.
- Salvataggio valido consentito; validazione annullata e ritorno dalla cache del browser non disattivano la protezione. Nessun contenuto memorizzato nel browser.
- Test JS dedicati; collaudo completo M01 ancora aperto.

## 0.34.0-dev — programma didattico contestuale

- Scaletta leggibile di moduli espandibili e lezioni cliccabili; aggiunta nel modulo, editor singolo, materiali e quiz nel contesto selezionato.
- Duplicazione, eliminazione con conferma e spostamento della lezione raccolti nelle azioni secondarie. Input recuperato dopo errori di validazione.
- Riordino tramite trascinamento con permutazione atomica/CAS e ripristino visivo su errore; Su/Giù da tastiera e form nativo senza JS. Nessuna modifica dello storico o dei risultati.
- Specifica1.5/AC-127 e guide aggiornate; M01 resta parziale.

## 0.33.0-dev — gestione corsi nativa e più diretta

- Elenco corsi secondo le convenzioni della gestione articoli Joomla: Search Tools, filtri, sort, paginazione e selezione con toolbar; metadati leggibili e accesso al programma dalla riga o dalla selezione.
- Editor compatto con contenuto e impostazioni principali affiancati; schede Programma, Docenti, Immagine, Pubblicazione e Opzioni. Rimossi gli avvisi ripetuti dall’elenco e dall’editor; dati e ID di compatibilità conservati.
- Salva e apri programma riusa la validazione e il salvataggio Joomla, conservando modifiche e storico. Lo stato rimanda al workflow autorizzato, senza toggle che aggirino la pubblicazione.
- Specifica1.4 e AC-126 recepiscono la preferenza esplicita dell’utente per la gestione amministrativa simile agli articoli.

## 0.32.0-dev — duplicazioni autonome

- Comandi per duplicare un corso come nuova bozza e un modulo o una lezione nel programma di lavoro, con titolo e destinazione espliciti.
- Copia di quiz, revisioni domanda, materiali e campi del corso senza duplicare blob, iscrizioni, progressi, tentativi o attestati. Nuovi ID e chiavi logiche; ACL native ereditate dalla categoria.
- Prenotazione dell’asset Joomla prima della transazione; rollback delle scritture e rimozione dell’asset inutilizzato se la copia fallisce. Controlli su identità corrente, sorgente, destinazione, checkout e revisioni.

## 0.31.0-dev — revisione del flusso editoriale

- Corsi pubblicati aggiornabili dal normale editor, con Content History di Joomla e ripristino editoriale protetto.
- Modifica del programma tramite copia completa della versione corrente, incluse lezioni, quiz e materiali. Pubblicazione degli aggiornamenti con conservazione delle assegnazioni didattiche.
- Profili docente senza selettore di categoria, campi Joomla, pagina e lista corsi configurabili da menu, modifica del proprio profilo dal frontend.

## 0.30.0-dev — regole Joomla per corso

- Campo Rules nativo nei corsi salvati per utenti autorizzati con core.admin sul relativo asset. Le modifiche vengono salvate dal controller com_config di Joomla.
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
