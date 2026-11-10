## PR #183 integrata — soglia video per lezione (0.41.49-dev)

CI standard 4/4 superata nel [run 42830117573](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573). Il pacchetto [0.41.49-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42830117573/artifacts/13770693036) contiene il runtime verificato; SHA-256 interno `46db03c8214873bbd2d266f067585458ebec059e3abbaf2fe29d9e38be1635bb`. Accettazione manuale sul Joomla di prova ancora pendente.

1. In Joomla di prova, creare una bozza con soglia di versione al 75% e due lezioni video. Lasciare vuota la soglia della prima e impostare 50% sulla seconda. Salvare entrambe, riaprire il Programma e verificare che il valore esplicito sia mostrato e la prima resti vuota (eredita).
2. Impostare valori limite 0,01% e 100% e salvare; verificare che valori 0, 100,01 e più di due decimali siano rifiutati mantenendo i dati già salvati.
3. Pubblicare e iscrivere uno studente di prova. Verificare che la prima lezione applichi il 75% e la seconda il 50% del video visto, anche se entrambe usano lo stesso provider.
4. Modificare la soglia in una nuova versione e pubblicarla: lo studente già iscritto mantiene la versione e la soglia originali; una nuova iscrizione riceve i nuovi valori.
5. Verificare che una lezione non video non esponga la soglia e che una richiesta alterata che provi a salvarla su testo/audio/documento venga respinta senza modifiche.

Warning: truncated output (original token count: 14491)
Total output lines: 515

## PR #162 integrata — anteprima dei livelli di accesso (0.41.43-dev)

CI standard 4/4 superata nel [run 42712132178](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178). Il [pacchetto 0.41.43-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42712132178/artifacts/13715794795) contiene il runtime verificato (SHA-256 interno `39797f26dfee8bcfec34c9aa8365c218653e56857c8133808f1892996e2de738`); il collaudo Joomla resta aperto.

1. Aggiornare il sito Joomla 6 di prova. Predisporre un corso pubblicato con catalogo **Public**, contenuto **Registered**, categoria pubblica e un editor con permesso di aprire la preview del programma.
2. In **Programma → Anteprima editoriale → Verifica dei livelli di accesso**, selezionare solo **Public** e inviare. Atteso: catalogo visibile, contenuto non accessibile, requisito combinato non soddisfatto.
3. Selezionare **Public** e **Registered**. Atteso: entrambe le condizioni vere e requisito combinato soddisfatto. Provare solo Registered: il catalogo resta nascosto se il livello della categoria/corso è Public.
4. Verificare che gruppi dell’editor, stato/versione corso, iscrizioni e progressi restino invariati; nessun tentativo, iscrizione o attività viene creato.
5. Richiedere la preview per un corso fuori dall’ACL editor. Atteso: Joomla nega la richiesta senza mostrare livelli o esiti.

## PR #159 integrata — archiviazione e ripristino (0.41.42-dev)

CI standard 4/4 superata nel [run 42704783478](https://github.com/jeckodevelopment/jd_learn/actions/runs/42704783478). Il [pacchetto 0.41.42-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42704783478/artifacts/13711989978) contiene il runtime verificato (SHA-256 interno `0e8ab0086a7305829b24f76e5859369213320d8529eda56a9556edaf7f0ad6fb`); resta aperto il collaudo sul Joomla di prova.

1. Predisporre un corso pubblicato e un’iscrizione attiva con progressi, più autore e revisore con ACL distinte.
2. Come revisore, archiviare il corso dal Programma. Atteso: fase **Archiviato**, corso non visibile nel catalogo e nuove iscrizioni negate.
3. Come studente già iscritto, aprire l’aula e una lezione già assegnata. Atteso: accesso, versione, materiali e progressi invariati. Verificare che roster e cronologia iscrizione siano conservati.
4. Ripristinare il corso. Atteso: la stessa versione torna nel catalogo; storico e progressi restano identici.
5. Ripetere upgrade del pacchetto e verificare idempotenza delle fasi/transizioni; provare un utente privo del permesso di transizione e verificare il diniego senza mutazioni.

## PR #153 integrata — workflow nativo “In revisione” (0.41.41-dev)

CI standard 4/4 superata nel [run 42701584797](https://github.com/jeckodevelopment/jd_learn/actions/runs/42701584797). Il [pacchetto 0.41.41-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42701584797/artifacts/13711269099) contiene il runtime verificato (SHA-256 interno `1f13ea58b21496521111bc75fb054f06aa807805a99847233912faf0136264b0`); il collaudo manuale seguente resta aperto. Su un Joomla 6 di prova, predisporre un ruolo autore e un ruolo revisore con permessi Joomla di transizione distinti.

1. Creare una bozza valida e inviarla in revisione. Atteso: stato In revisione; corso ancora non pubblico e nessuna versione didattica congelata.
2. Provare a salvare o riordinare la bozza durante la revisione, sia dalla UI sia via richiesta diretta autenticata. Atteso: rifiuto; nessuna modifica a versione, contenuti o revisione.
3. Restituire la bozza al redattore. Atteso: stato Bozza e modifiche nuovamente consentite.
4. Inviare di nuovo e approvare con il revisore. Atteso: validatori autorevoli superati, nuova versione pubblicata una sola volta e iscritti esistenti immutati.
5. Ripetere su un corso già pubblicato: durante la revisione verificare catalogo e aula con un account studente e confermare che il corso live resta disponibile sulla versione assegnata. All’approvazione verificare il riepilogo impatto, il diniego senza conferma e che la versione precedente resti immutata.
6. Rimuovere il permesso di transizione dai due ruoli a turno e provare URL diretto/token non valido: atteso diniego senza cambio di fase. Aggiornare il pacchetto due volte e verificare che non vengano duplicati fasi o transizioni.

La CI deve coprire installazione/upgrade, idempotenza, transizioni native, ACL, blocco authoring e conservazione della versione corrente.

## Issue #149 — dashboard e stato dei provider video

Nel sito Joomla 6 di prova, accedere come amministratore e aprire Componenti → JD Learn.

1. Verificare l’ordine: Corsi, Percorsi formativi, Categorie corsi, Categorie dei percorsi, Banca domande, Docenti, Campi aggiuntivi, Plugin video.
2. Verificare che ogni link apra la destinazione corretta e che un account senza i permessi specifici non veda le voci protette.
3. Aprire lo stato provider: devono apparire YouTube, Vimeo, Cloudflare Stream e Video locali.
4. Disattivare un plugin video e ricaricare: compare “Disattivato”; riattivarlo senza configurazione: compare “Riproduzione non ancora disponibile”; completare la configurazione: compare “Configurato”. I provider mancanti o non disponibili devono avere il rispettivo stato.
5. Ripetere in italiano e inglese e verificare layout da tastiera/finestra stretta.

CI automatica prevista: test PHP del riepilogo e smoke HTTP dell’ordine/visibilità. Il collaudo Joomla reale resta da registrare separatamente.

# Testbook manuale Joomla — JD Learn

## PR #147 integrata — accesso amministrativo ai Percorsi formativi (0.41.39-dev)

La CI standard 4/4 è superata nel [run 42674520291](https://github.com/jeckodevelopment/jd_learn/actions/runs/42674520291): controlli leggeri, PHP 8.3/MySQL 8.4, PHP 8.4/MariaDB 10.6 e Chromium. La verifica automatica controlla il link nativo d’installazione e la scorciatoia dashboard; non sostituisce il collaudo Joomla reale.

Su un Joomla di prova usa e getta:

1. Con un account autorizzato, aprire dashboard e sottomenu JD Learn; verificare la scorciatoia Percorsi e la posizione dopo Corsi.
2. Creare un percorso con almeno due corsi, riordinarli, salvare, riaprire e pubblicare. Verificare descrizione, lingua, livello di accesso e categorie.
3. Aprire catalogo/scheda da studente e controllare ordine e avanzamento; aggiungere corsi al percorso non deve iscrivere né concedere accesso.
4. Con un account senza `jlms.pathways.manage`, verificare che scorciatoia e voce laterale non compaiano e che l’URL diretto sia negato.
5. Con un account con permesso dedicato ma senza `core.create`, verificare che **Nuovo** non sia disponibile; verificare anche le ACL native delle categorie.
6. Ripetere in italiano e inglese.

**Stato:** collaudo manuale non ancora registrato.


Registro cumulativo delle verifiche da eseguire su un **sito Joomla di prova usa e getta**. Si aggiorna a ogni PR con comportamenti runtime, aggiungendo le prove descritte nella PR e mantenendo il collegamento al requisito/scenario esistente. Non crea criteri funzionali nuovi. Un test è superato solo dopo esecuzione manuale sul sito e registrazione dell’esito qui; CI e test HTTP sono evidenze separate.

## PR #143 integrata — rilascio relativo all’avvio iscrizione

Su un Joomla di prova usa e getta, predisporre un corso pubblicabile con una lezione obbligatoria immediata e una seconda lezione obbligatoria con attesa di 2 giorni.

1. Salvare e riaprire la lezione differita; verificare che il valore `2` sia persistito. Provare valori negativi e oltre 3650: rifiutati.
2. Pubblicare il corso e iscrivere un account studente. Nell’aula verificare l’indicazione “disponibile dopo l’avvio, tra 2 giorni”, senza corpo, campi, allegati o player.
3. Provare URL diretti di materiale/player, completamento e ripresa prima della data; atteso diniego senza mutazioni o progresso.
4. Avviare l’iscrizione completando la lezione iniziale. Verificare che la data di disponibilità sia `started_at + 48 ore` (fuso visualizzato coerente) e che la lezione resti bloccata fino all’istante; poi verificare accesso e completamento.
5. Combinare il rilascio con `publish_up` e `publish_down`: deve valere l’apertura più tarda e la chiusura è esclusiva.
6. Tentare di pubblicare un corso dove tutte le lezioni obbligatorie hanno attesa positiva: atteso blocco per assenza di attività iniziale disponibile.
7. Provare ammissione automatica e manuale con una scadenza di iscrizione/edizione precedente al rilascio di una lezione obbligatoria: atteso rifiuto esplicito. Verificare che un’iscrizione già attiva e i relativi progressi restino invariati.

**Stato:** PR [#143](https://github.com/jeckodevelopment/jd_learn/pull/143) integrata nel commit `7681473d34e23ec633fcf9fc59c3711e644b2d98`; CI standard 4/4 superata nel [run 42652681260](https://github.com/jeckodevelopment/jd_learn/actions/runs/42652681260). Pacchetto [JD-Learn-0.41.38-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42652681260/artifacts/13690028782), wrapper SHA-256 `b8a7b72014647d2a2cb7adad9c588318fed014403ba745d9cf59564ba6ca968d`, pacchetto interno SHA-256 `382cc48ce54045326041f0a84bcfaa7db76d32ee2b3bc73bbfa5bd2bc3b2de22` (526473 byte), scade il 23 novembre 2026. Collaudo Joomla pendente.

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

## PR prerequisiti espliciti delle lezioni (0.41.35-dev)

Usare un corso, un autore e uno studente fittizi su Joomla di prova.

1. In una bozza con due lezioni testo A e B, aprire B, impostare A come prerequisito e salvare. Verificare che la selezione resti dopo la riapertura.
2. Tentare di impostare B come prerequisito di A (ciclo) e di impostare una lezione come prerequisito di sé stessa. Atteso: salvataggio rifiutato, nessuna modifica parziale. Provare anche un ID appartenente a un’altra versione con una richiesta modificata: deve essere rifiutata.
3. Pubblicare la versione. Con lo studente iscritto aprire B prima di A: l’aula mostra il nome e il collegamento ad A, senza corpo, campi aggiuntivi, materiali, player o comando di completamento per B. Provare URL diretto di allegato, player audio/video e POST di completamento per B: nessun contenuto né progresso.
4. Completare A e riaprire B: il contenuto diventa fruibile; materiali e regola di completamento funzionano come prima.
5. Creare una nuova bozza e verificare che il collegamento sia copiato; modificare il prerequisito nella bozza senza alterare la versione già assegnata.

**Stato:** PR [#136](https://github.com/jeckodevelopment/jd_learn/pull/136) integrata nel commit `832d7d4089071002a11eb09cabdc86fae4e3f820`. CI standard 4/4 superata nel [run 42628727538](https://github.com/jeckodevelopment/jd_learn/actions/runs/42628727538). Pacchetto [JD-Learn-0.41.35-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42628727538/artifacts/13679892795), wrapper SHA-256 `fe8fa8bc4aa560548bd92db27d74c979e4f3dee7eec361421bc4462dcc212bd2`, interno `609719d24445eda91e0f35ab6aa4895cc60a564da54a0995e1499f5d6bdc617ab` (520630 byte), scade il 23 novembre 2026. Collaudo manuale Joomla pendente. Requisiti collegati: STU-04, EDGE-03, MAT-04, PRO-01. Sequenziamento automatico e rilascio temporale non sono inclusi in questo incremento.

## PR sequenziamento delle attività obbligatorie (0.41.36-dev)

Usare un corso, un autore e uno studente fittizi su Joomla di prova.

1. Nella bozza crea tre lezioni obbligatorie A, B, C in ordine e una facoltativa tra A e B. Apri Regole didattiche, seleziona Sequenziale e salva. Riapri la schermata e verifica che la modalità sia conservata.
2. Pubblica il programma. Da studente iscritto prova ad aprire B e C: devono mostrare la prima attività obbligatoria ancora incompleta e un collegamento, senza esporre corpo, campi, materiali o player.
3. Prova URL diretti di allegati/player e richieste di completamento/ripresa per una lezione bloccata: nessun contenuto né progresso deve essere restituito. Completa A: B si sblocca. Completa B: C si sblocca. La lezione facoltativa non deve interrompere la catena.
4. Crea una nuova bozza, passa a Libera e pubblica l’aggiornamento: la navigazione torna libera salvo prerequisiti espliciti. Verifica che gli iscritti alla versione precedente mantengano la regola assegnata.
5. In una bozza sequenziale aggiungi un prerequisito esplicito da una lezione successiva verso una precedente: la pubblicazione deve rifiutare il ciclo combinato e lasciare intatta la versione attiva.

**Stato:** PR #138 integrata; CI standard 4/4 superata; collaudo manuale Joomla pendente.

## PR finestre temporali delle lezioni (0.41.37-dev)

Usare un corso fittizio con fuso **Europe/Rome**, una lezione obbligatoria con data di apertura futura e chiusura successiva e uno studente iscritto.

1. Salvare le date dal Programma e riaprire il form: gli orari devono corrispondere al fuso del corso. Intervallo invertito o data locale inesistente nel passaggio DST deve essere rifiutato.
2. Pubblicare il programma e aprire la lezione prima della data: aula e outline mostrano quando sarà disponibile; contenuto/campi, materiali, player e quiz non sono esposti. Provare URL diretti, completamento e ripresa: devono essere negati e non devono registrare progresso.
3. Dopo l’apertura verificare accesso e completamento secondo la regola della lezione. Dopo la chiusura, aula e endpoint devono bloccare di nuovo l’accesso.
4. Verificare che un’attività obbligatoria futura resti nel denominatore del progresso. Una lezione obbligatoria la cui finestra non interseca la disponibilità corso deve bloccare la pubblicazione; una finestra raggiungibile deve essere pubblicabile.
5. In una nuova versione controllare che le date restino congelate per chi è già assegnato alla versione precedente.

**Stato:** PR #141 integrata in 0.41.37-dev; CI standard 4/4 superata nel [run 42646076977](https://github.com/jeckodevelopment/jd_learn/actions/runs/42646076977); pacchetto verificato [JD-Learn-0.41.37-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42646076977/artifacts/13688665407); collaudo manuale Joomla pendente. Il drip relativo a giorni dall’avvio dell’iscrizione non è incluso.

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

Riferimenti: [issue #113](https://github.com/jeckodevelopment/jd_learn/issues/113), CORE-20 / AC-132. Il plugin `plg_system_jlmsmenu` deve filtrare le voci della sidebar secondo i permessi già applicati alle destinazioni senza cambiare gli altri menu Jo…2491 tokens truncated…Verificare ACL e ricerca sul sito; il cambio stato del profilo resta disponibile nel form secondo i permessi, senza introdurre azioni massive.

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
## Issue #154 — fuso iniziale del corso dalla configurazione globale Joomla

**Stato:** prova automatizzata superata in CI nel [run 42769089189](https://github.com/jeckodevelopment/jd_learn/actions/runs/42769089189); il collaudo manuale Joomla è ancora da eseguire. PR [#167](https://github.com/jeckodevelopment/jd_learn/pull/167), main 0.41.44-dev.

1. In Joomla 6, impostare in **Configurazione globale → Server → Fuso orario** un valore riconoscibile, ad esempio `Europe/Rome`. Creare un nuovo corso come autore.
2. Aprire la scheda **Calendario**: il campo Fuso orario deve partire da `Europe/Rome`, anche se il fuso del profilo utente è diverso o non impostato.
3. Selezionare un fuso diverso e salvare il corso; riaprirlo e verificare che il valore scelto sia conservato.
4. Creare un altro corso e impostare date locali dopo aver selezionato un fuso diverso. Verificare che l’ora salvata/mostrata segua il fuso scelto e che le date esistenti dei corsi non vengano reinterpretate.

## Issue #157 — usabilità del programma didattico

**Stato:** PR [#169](https://github.com/jeckodevelopment/jd_learn/pull/169) integrata in 0.41.45-dev; CI standard 4/4 superata nel [run 42771935666](https://github.com/jeckodevelopment/jd_learn/actions/runs/42771935666); il collaudo manuale Joomla deve ancora essere eseguito.

1. Aprire il programma di un corso in bozza con un utente autorizzato alla modifica. Verificare che **Aggiungi modulo**, **Campi lezione**, **Regole didattiche** e **Anteprima corso** compaiano nella toolbar nativa Joomla, senza duplicati sotto il titolo, e che mantengano il corso/versione selezionati.
2. Aggiungere un modulo e aprire ciascuna delle altre destinazioni. Verificare che la navigazione arrivi alla funzione corretta, mantenendo il contesto del corso quando previsto.
3. Ripetere con un utente senza permesso di modifica e con un utente senza gestione campi: le azioni non autorizzate non devono comparire; provare anche l’accesso diretto alle relative URL.
4. Usare la toolbar e i form laterali solo da tastiera; verificare focus visibile e spaziatura leggibile a larghezza desktop e mobile, compreso il pannello Joomla compresso.

**Esito sito Joomla:** Non eseguito.


## AC-121 — docenti, associazioni e profili preesistenti

Riferimenti: AUT-01, CAT-02, CORE-01/04, LIFE-02; [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md).

Precondizioni: Joomla 6 di prova aggiornabile, un corso in bozza e uno pubblicato, un profilo docente nuovo e uno preesistente; account segreteria con permessi di gestione corsi/docenti.

1. Aprire JD Learn → Docenti e creare un profilo. Verificare che non sia richiesta né mostrata una categoria docente; il salvataggio deve usare il profilo senza assegnare gruppi Joomla o permessi all’account collegato.
2. Modificare un profilo preesistente e associarlo al corso in bozza. Salvare, riaprire il corso e verificare che l’associazione e l’ordine restino invariati.
3. Modificare l’associazione di un corso pubblicato. La scheda editoriale deve mostrare il dato corrente, mentre la versione didattica e lo snapshot già pubblicati restano invariati.
4. Ripetere l’aggiornamento dei dati di un profilo tecnico preesistente: categorie tecniche e riferimenti restano conservati e non si trasformano in un flusso di gestione per l’utente.

**Esito sito Joomla:** Non eseguito.

## AC-122 — campi e presentazione pubblica del docente

Riferimenti: CORE-10/04; [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md).

1. Da JD Learn → Campi aggiuntivi, selezionare il contesto Docenti (`com_jlms.teacher`). Creare un campo testo, email e un campo di tipo/gruppo disponibile nell’installazione; impostare lingua, accesso e permessi tramite i controlli Joomla.
2. Compilare presentazione, riferimenti pubblici e campi. Collegare al profilo un account Joomla con email privata distinta.
3. Aprire la pagina docente e la scheda pubblica di un corso associato come ospite e come utente autorizzato. Atteso: campi e gruppi appaiono solo secondo lingua, pubblicazione, livelli e ACL; l’email privata dell’account non viene esposta automaticamente.
4. Provare un valore vuoto e un campo non pubblicato: non devono apparire label o contenitori vuoti.

**Esito sito Joomla:** Non eseguito.

## AC-123 — modifica del profilo e revoca dei permessi

Riferimenti: CORE-04/10 e [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md). Preparare un docente collegato, un account segreteria e un secondo docente/account senza permessi.

1. Come docente collegato, aprire la pagina profilo e modificare i soli campi autorizzati. Verificare che account collegato, stato, lingua e livello di accesso non cambino.
2. Come segreteria, modificare gli stessi dati e un campo protetto. Atteso: l’operazione riesce solo con l’ACL corrispondente.
3. Come altro account, tentare l’URL di modifica del profilo e l’invio POST diretto. Atteso: accesso negato e dati invariati.
4. Revocare `core.edit.own` o `core.edit.value` e riprovare; il controllo deve applicare la revoca anche dopo una nuova richiesta. Ripetere con dati invalidi e una revisione obsoleta: il form conserva i dati inseriti per correggerli e non sovrascrive la revisione corrente.

**Esito sito Joomla:** Non eseguito.

## AC-124 — menu, SEF, lingue e corsi del docente

Riferimenti: CAT-02, CORE-13 e [guida Profili e associazioni docente](../component/administrator/docs/TEACHERS.md). Preparare due profili e corsi con stato, lingua, categoria e livelli di accesso differenti.

1. Nel gestore menu Joomla creare voci **Pagina docente** e **Corsi di un docente**, selezionando il profilo previsto. Aprire le voci e verificare breadcrumbs, URL SEF, titolo e paginazione.
2. Ripetere per italiano e inglese, includendo un profilo/corso con lingua specifica e uno `*`. Atteso: profilo e corsi coerenti con il filtro lingua.
3. Cambiare manualmente l’ID nell’URL e aprire URL SEF dirette per profili non pubblicati o non accessibili. Atteso: nessun dettaglio o corso riservato viene esposto.
4. Verificare che un corso non visibile non compaia neppure nell’elenco del docente e che il profilo non riveli l’email privata dell’account collegato.

**Esito sito Joomla:** Non eseguito.

## AC-125 — upgrade e conservazione dei dati docente

Riferimenti: AUT-04/05, CORE-01/04/10/16, LIFE-02 ed EDGE-11. Usare un’istanza Joomla usa-e-getta con il pacchetto precedente e dati di prova preesistenti: corso pubblicato, iscrizione/progressi, profilo docente, categoria tecnica, associazioni, campi/valori e revisioni.

1. Registrare prima dell’upgrade gli ID e i valori di corso, profilo, categoria, associazioni, campo e versione didattica. Aggiornare il pacchetto seguendo la normale procedura Joomla.
2. Dopo l’upgrade confrontare ID e valori registrati; verificare che stato, livello, lingua, campi e associazioni restino validi e che lo storico didattico conservi il docente congelato.
3. Accedere come studente già iscritto e come nuovo utente: l’upgrade non deve migrare iscrizioni, azzerare progressi o assegnare gruppi/permessi. Le nuove modifiche editoriali non alterano retroattivamente le versioni assegnate.
4. Verificare che le categorie tecniche restino utilizzabili per compatibilità ma non diventino obbligatorie nell’authoring.

**Esito sito Joomla:** Non eseguito. Eseguire solo su installazione eliminabile; non usare dati di produzione.


## IMP-03 — preview video in sola lettura (0.41.46-dev)

Requisiti: AUT-02, AUT-03, STU-02, SCR-06–08; AC-20, AC-80, AC-86, AC-119–127. La procedura copre la riproduzione video e i confini read-only di questo incremento; non chiude la preview interattiva completa (documenti, audio e interazioni quiz restano non simulati).

1. Su Joomla 6 di prova, accedere come editor autorizzato e aprire **Programma → Anteprima editoriale** per una versione bozza e una pubblicata contenenti testo e video. Se disponibili, predisporre un video locale HTML5 e video YouTube/Vimeo/Cloudflare Stream.
2. Aprire una lezione testuale e poi ciascuna lezione video. Atteso: testo e navigazione sono visibili; il player video carica solo dopo l’apertura della lezione. Avviare, mettere in pausa e cercare in avanti/indietro. Per provider esterni, provare anche la policy bloccata, embed e consenso del sito: nessuna richiesta esterna deve partire prima che la policy consenta la riproduzione.
3. Prima e dopo la riproduzione, confrontare roster, iscrizioni, tentativi, sessioni e progresso della lezione/corso: nessun valore deve essere creato o cambiato. Nel player deve apparire un messaggio che dichiara che il progresso non viene salvato.
4. Provare un account senza permesso di modifica corso/materiali e poi revocare il permesso durante la sessione. Anteprima e richieste dirette POST del descrittore/GET del video devono essere negate; URL alterate con corso, versione, lezione o revisione non correlati non devono restituire media.
5. Aprire una lezione quiz. Atteso: l’attività è indicata come anteprima read-only non simulata; non viene creato alcun tentativo.

**Esito sito Joomla:** Non eseguito.

## IMP-03 — preview editoriale di documenti e audio (0.41.47-dev)

Riferimenti: AUT-02/03, STU-02, MAT-01/02; AC-20, AC-27–31, AC-80, AC-86, AC-93, AC-97–103, AC-119–127. Eseguire su Joomla 6 eliminabile, con un editor autorizzato alla gestione del corso e dei materiali; predisporre una lezione Documento e una Audio nella bozza, PDF, DOCX/TXT, immagine e MP3/OGG in stato pronto, oltre a un allegato non disponibile (quarantena, non pubblicato o con data futura/scaduta). Conservare conteggi iniziali di iscrizioni, sessioni, tentativi e progressi.

1. Aprire **Programma → Anteprima editoriale** sulla lezione Documento. Atteso: elenco separato di materiali del corso e della lezione, con titolo, descrizione, tipo e dimensione; PDF, immagini e testo ammessi si aprono inline in una nuova scheda, gli altri file vengono scaricati. File in quarantena/non pronto, disabilitati, contrassegnati preview o fuori finestra non compaiono e non sono serviti.
2. Aprire la lezione Audio. Atteso: player HTML5 per MP3/OGG della lezione, con seek/ripresa HTTP Range; gli allegati rimanenti sono consultabili secondo il tipo file. Un file audio di formato diverso non deve essere riprodotto dall’anteprima.
3. Provare URL dirette con GET e HEAD per un allegato valido. Cambiare a turno course, version, lesson e attachment ID, includendo una lezione di un altro corso/versione e un attachment collegato a un’altra lezione. Atteso: nessun file viene restituito per relazioni non corrispondenti.
4. Ripetere con un account privo di gestione materiali e dopo la revoca del permesso nella sessione. Atteso: accesso negato anche sull’endpoint diretto. Verificare che l’endpoint non accetti POST o altri metodi.
5. Prima e dopo preview/download/riproduzione, confrontare iscrizioni, sessioni, tentativi e progresso: devono restare invariati. La lezione quiz deve continuare a mostrare solo il placeholder read-only, senza tentativi.

**Esito sito Joomla:** Non eseguito.


## IMP-04 — apertura protetta inline di PDF e immagini nell’aula (0.41.48-dev)

Riferimenti: LES-01, MAT-04/05, STU-02; AC-27–31, AC-43–44, AC-89, AC-95. Su Joomla 6 di prova predisporre un’iscrizione attiva e lezioni disponibili con PDF, JPEG/PNG/WebP, DOCX, CSV, ZIP e un file non pronto; conservare lo stato iniziale di progressi e iscrizioni.

1. In aula verificare che PDF e immagini mostrino **Apri nel browser** e **Scarica file**; DOCX, CSV e ZIP devono mostrare solo il download.
2. Aprire PDF e immagini: risposta inline in nuova scheda con `nosniff`, cache privata e senza contenuti eseguibili; provare anche Range su PDF. Il download alternativo deve continuare a funzionare.
3. Alterare attachment ID/enrollment ID e ripetere dopo revoca accesso, sospensione, chiusura finestra o blocco prerequisito della lezione. L’endpoint inline deve negare il file senza rivelare contenuto.
4. Richiedere l’endpoint inline per DOCX/CSV/ZIP o asset in quarantena: deve essere negato; il normale endpoint di download resta soggetto ai controlli esistenti. Metodi diversi da GET/HEAD devono essere rifiutati.
5. Confrontare progressi e iscrizione prima/dopo vista inline e download: nessuna apertura o scaricamento attribuisce completamento.

**Esito sito Joomla:** Non eseguito.
