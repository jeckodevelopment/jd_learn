## IMP-09 / CORE-18 — template email Joomla

**Comportamenti da testare in Joomla**

1. Installare o aggiornare il pacchetto e aprire **Sistema → Modelli email**. Filtrare per `com_jlms` e verificare i modelli richiesta iscrizione, iscrizione confermata e completamento corso, con testo, HTML e tag dichiarati.
2. Aprire un modello, modificarne oggetto e corpo e salvare. Creare inoltre una traduzione per la lingua secondaria configurata nel sito.
3. Aggiornare nuovamente il pacchetto: oggetto, corpo e traduzione personalizzati devono rimanere invariati; non devono comparire modelli duplicati.
4. Verificare che l'impostazione del mailer/SMTP resti quella globale di Joomla. Questo incremento registra i modelli; la spedizione automatica e il retry persistente sono residui CORE-18 e non vanno considerati collaudati da queste prove.

**Verifiche aggiunte dalla PR #291 (0.56.1-dev)**

5. Abilitare il plugin Attività - JD Learn e creare un'attività pianificata Joomla `JD Learn: invia email in coda` con frequenza breve per il collaudo. Configurare il mailer globale del sito su una casella di prova.
6. Con un utente di prova, iscriversi a un corso pubblicato in modalità automatica. Eseguire l'attività e verificare la conferma d'iscrizione con corso, nome, lingua e link aula corretti. Ripetere l'attività: la casella deve ricevere un solo messaggio.
7. Completare tutte le attività obbligatorie del corso, con una lezione manuale e una video in due prove separate. Verificare un solo messaggio di completamento per ciascuna iscrizione e nessun invio su salvataggi/ripetizioni dello stesso evento.
8. Simulare un errore SMTP temporaneo prima dell'invio, eseguire l'attività e verificare che l'elemento venga ritentato dopo l'attesa progressiva e raggiunga `sent` al ripristino. Verificare che il log dell'attività riporti l'esito senza contenuti o destinatari sensibili.
9. Verificare nel database che i messaggi inviati abbiano stato `sent` e i dati personali siano rimossi; i messaggi in stato `sending` dopo un'interruzione non devono essere reinviati automaticamente.

PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291) integrata nel commit `a6488b3b646d607dbc20317168f1c69dae055527`. CI standard 4/4 nel [run 43300371393](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393), inclusi controlli automatici su entrambi gli stack DB e Chromium. Pacchetto [JD-Learn-0.56.1-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43300371393/artifacts/13981827024), SHA-256 ZIP `beacb58745732bf803d61c6c10c72e483ad6eba2ee2a9e4337eb80d326b90154` (scade il 26 novembre 2026). La CI copre schema, integrazioni e suite automatizzata, ma non prova SMTP end-to-end né sostituisce l'accettazione manuale Joomla. Per i test manuali descritti sopra registrare qui esito, Joomla/PHP, mailer e configurazione usata; non marcare il requisito superato senza tali evidenze.

## IMP-09-MAIL-A — preferenze globali e override corso (PR #295, 0.56.2-dev)

**Comportamenti da testare in Joomla**

1. Aprire le opzioni di JD Learn e verificare le impostazioni per conferma iscrizione automatica e completamento. Impostare entrambe su abilitate; su un corso lasciare entrambe su **Usa impostazione del sito**. Salvare e riaprire: le scelte devono persistere.
2. Con un account di prova, attivare una nuova iscrizione automatica e completare un altro corso. Eseguire il task email: entrambe le notifiche devono essere accodate e consegnate secondo le prove della sezione precedente.
3. Disabilitare a livello globale la conferma di iscrizione. Per un corso lasciare eredita, per un secondo corso impostare esplicitamente abilita. Creare nuove iscrizioni automatiche: il primo corso non deve notificare; il secondo deve notificare.
4. Impostare l'override disabilita sul completamento di un corso, lasciando l'opzione globale abilitata; completare il corso e verificare che non si accodi la mail. Per un altro corso con eredita, il completamento deve continuare a produrre la notifica.
5. Riportare un override da abilita/disabilita a **Usa impostazione del sito** e verificare la nuova eredità. Confermare che modificare queste opzioni non alteri iscrizioni, completamenti, progressi o personalizzazioni dei modelli Joomla.

Le prove automatizzate pure coprono ereditarietà, override, validazione e conservazione dei parametri estranei. L'invio SMTP end-to-end e il collaudo manuale Joomla restano da registrare.

PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295) integrata nel commit `8152e337455b1ec7b9d307dc9248c8070b964709`; CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698). Pacchetto [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`, digest artefatto `4e541658e5cf45c2b1238c41bb1501a768ec850cbd3b2958c37d3e735959450e` (scade il 26 novembre 2026). Acceptance manuale Joomla/SMTP ancora pendente.

## IMP-09-MAIL-B — email su sospensione e riattivazione (0.56.3-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare 0.56.3-dev e aprire **Sistema → Modelli email**. Verificare i template `com_jlms.enrollment.suspended` e `com_jlms.enrollment.reactivated`, i tag dichiarati e la conservazione di personalizzazioni dopo un secondo upgrade.
2. Configurare il mailer globale Joomla su casella di prova; impostare globalmente entrambe le notifiche. Sospendere l'iscrizione attiva di uno studente: l'attività pianificata deve inviare un solo messaggio con nome e corso corretti; ripetere il task senza duplicati.
3. Riattivare la stessa iscrizione: inviare il solo template di riattivazione. Verificare che progressi, stato di completamento e tentativi quiz conservati restino invariati.
4. Disabilitare globalmente la sospensione; per un corso lasciare **Usa impostazione del sito** e per un altro impostare **Abilita**. Sospendere due iscrizioni e verificare che la prima non accodi messaggi e la seconda sì. Verificare anche l'override **Disabilita** con default globale attivo e la preferenza di riattivazione separata.
5. Configurare un errore nel database/outbox durante l'applicazione: la transazione non deve lasciare uno stato aggiornato senza il corrispondente evento; ripetere una richiesta idempotente e verificare un solo record di coda.

Il test automatico copre i valori consentiti e la risoluzione delle preferenze. La CI di integrazione verifica il workflow e il registro template; invio SMTP/Joomla manuale ancora da eseguire.

## IMP-04 / STU-03 — feedback salvataggio progresso video (PR #287, 0.55.0-dev)

PR [#287](https://github.com/jeckodevelopment/jd_learn/pull/287) integrata nel commit `2af8994271d95f3c41b3e5f813ba2222794265c5`; CI standard 4/4 nel [run 43289138110](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110). Pacchetto installabile [JD-Learn-0.55.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43289138110/artifacts/13975579105); SHA-256 package `fb30f231780fc290aeecb64ff3ab7b79f3bb2c00ea19085ea67c0ff239794282`.

**Comportamenti da testare in Joomla**

1. Su un corso in modalità Apprendimento con lezione video tracciabile, accedi come studente e avvia il video. Durante un invio periodico o dopo pausa/seek, verifica che l’area di stato mostri “Salvataggio del progresso…” e poi “Progresso salvato” dopo la risposta positiva del server.
2. Verifica che un completamento ottenuto sulla soglia prevista mostri il messaggio di completamento, senza essere sostituito dallo stato salvato.
3. Simula una perdita di rete durante il salvataggio: il player deve mostrare lo stato offline e riprendere il salvataggio idempotente al successivo invio; dopo il recupero deve mostrare lo stato salvato. Un errore 4xx definitivo deve continuare a mostrare il messaggio di accesso/conflitto e fermare la sessione secondo il comportamento esistente.
4. Ripeti in inglese e verifica le etichette localizzate. Nella preview editoriale non deve apparire uno stato di salvataggio né essere creata telemetria.

Il test automatico `tests/video-player.mjs` e la CI standard sono superati. Il collaudo Joomla manuale resta da eseguire.

## IMP-04 / AUT-03 — avviso durata video in sola consultazione (PR #284, 0.54.0-dev)

PR [#284](https://github.com/jeckodevelopment/jd_learn/pull/284) integrata nel commit `04704621f64a7ee712743f98037dfe31d7a4606f`; CI standard 4/4 nel [run 43256423443](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443). Pacchetto installabile [JD-Learn-0.54.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43256423443/artifacts/13961114324), SHA-256 ZIP `bb30f239755b24b7c439d03ba5a70b7e11294360d648ce2a0a291cdbb0c46f12`; wrapper `ba7f9c7568500bfd82da3d9ff054ea907272942c9dfc9ff9f5203bfc8331494d`. Accettazione manuale da eseguire sul sito Joomla di prova.

1. Crea una bozza in modalità **Sola consultazione** con una lezione video la cui durata non è disponibile o confermata. Apri il riepilogo di pubblicazione: deve apparire l’avviso sulla durata e la pubblicazione non deve essere bloccata solo per questo motivo.
2. Imposta/conferma una durata valida e riapri il riepilogo: l’avviso deve scomparire.
3. Passa alla modalità **Apprendimento** e rimuovi la durata o la relativa conferma: deve restare il blocco esistente `COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED`, senza un secondo avviso duplicato.
4. Verifica che gli errori già esistenti relativi a provider o sorgente video continuino a essere segnalati correttamente.

La copertura automatizzata è in `tests/run.php`; registrare separatamente il collaudo Joomla manuale.

## IMP-01 — snapshot dei campi corso `com_fields` (0.46.0-dev, PR #266)

PR [#266](https://github.com/jeckodevelopment/jd_learn/pull/266) integrata nel commit `d41111e229ced8460ff9e30a0baa24f2094c4e60`; CI standard 4/4 nel [run 43196626438](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438). Pacchetto [JD-Learn-0.46.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43196626438/artifacts/13932614114), SHA-256 interno `044782978b03fa740889c6f215439c7658a84a95d7bb9fe721f64dfc19ca5183`, wrapper `4993336bd9f382f8e6df7191b4842c7f3699525a12ab2c46dfa7abfff7384013` (estrarre una volta; scade il 26 novembre 2026). Accettazione manuale Joomla ancora da registrare.

Riferimenti: CORE-10, EDGE-11, AUT-04; criteri AC-48, AC-71, AC-119/120/125. Nel sito Joomla di prova crea un corso con un campo nativo `com_fields` valorizzato e pubblica una versione didattica.

1. Verificare che la pubblicazione riesca e che aula/lezioni continuino a mostrare i campi lezione previsti.
2. Cambiare il valore del campo corso dopo la pubblicazione. La scheda editoriale può mostrare il valore corrente, ma lo snapshot della versione già pubblicata e le iscrizioni assegnate devono conservare quello originario.
3. Creare una nuova versione di lavoro e pubblicarla: lo snapshot della nuova versione deve contenere il valore corrente del campo; la versione precedente deve restare invariata.
4. Ripetere con un campo non pubblicato o non visibile al livello Joomla dell’operatore: non deve essere incluso nello snapshot. Verificare inoltre che accessi e stato dei campi nativi restino gestiti da Joomla.

La CI automatizzata copre congelamento, hash e conservazione dei campi corso/lezione durante la pubblicazione. Registrare separatamente l’accettazione Joomla manuale.

## IMP-01 — ripristino dei campi corso tramite Content History (CORE-10/CORE-16)

Con un account che può modificare il corso, modificare i valori `com_fields` e usare Content History, salvare una revisione del corso con il campo impostato su “Valore A”, quindi salvare “Valore B”. Aprire **Versioni**, confrontare la revisione con “Valore A” e ripristinarla.

Atteso: i dati editoriali e il campo tornano a “Valore A”; lo stato del corso, la versione didattica corrente, le versioni assegnate, le iscrizioni e i progressi restano invariati. Se il corso ha una bozza aperta, il ripristino aggiorna i dati editoriali del corso e non sovrascrive la struttura delle versioni. Verificare anche che un account privo di `core.edit.value` sul campo non possa alterarlo tramite il ripristino.

La regressione automatica è integrata dalla PR [#268](https://github.com/jeckodevelopment/jd_learn/pull/268); CI standard 4/4 nel [run 43201132206](https://github.com/jeckodevelopment/jd_learn/actions/runs/43201132206). La prova Joomla della schermata Content History resta da eseguire.

## IMP-02 — modalità corso di sola consultazione (AUT-03 / EDGE-02)

**Comportamenti da testare in Joomla**

1. Installare il package 0.47.0-dev su Joomla 6 di prova e creare una bozza con descrizione, un modulo e una lezione Testo. In **Regole didattiche** selezionare **Sola consultazione** e salvare.
2. Verificare che la modalità imposti regole libere senza completamento; marcare tutte le lezioni come facoltative. Il preflight non deve segnalare l’assenza di attività obbligatorie, ma deve continuare a segnalare moduli vuoti, descrizione mancante e contenuti/materiali non validi.
3. Provare a pubblicare con una lezione obbligatoria: la pubblicazione deve essere bloccata con un messaggio comprensibile. Rendere la lezione facoltativa e riprovare: la pubblicazione deve riuscire.
4. In una copia della bozza, aggiungere un quiz o un prerequisito e verificare che la pubblicazione sia rifiutata. Verificare che il form non consenta il sequenziamento in modalità consultazione.
5. In un'altra lezione video facoltativa della bozza, rimuovere la durata misurata/confermata se la configurazione consente il salvataggio della bozza e pubblicare. La pubblicazione deve riuscire se la sorgente e il provider sono validi. Aprire la lezione come studente: il video deve riprodursi e non devono essere create sessioni di telemetria o righe di avanzamento; nessun completamento o percentuale deve comparire. Ripetere con provider/sorgente non validi e verificare che la pubblicazione resti bloccata.
5. Accedere come studente autorizzato, iscriversi e aprire la lezione. Aula e dashboard devono indicare “Non applicabile” per il progresso, senza percentuale, completamento o attestato. Provare l’azione manuale di completamento: deve essere rifiutata. Ripetere con un video e verificare che il tracciamento non conceda completamento.
6. Tornare in modalità **Apprendimento**: la pubblicazione senza almeno una lezione obbligatoria deve essere nuovamente bloccata. Verificare che cambiare regole in una bozza non modifichi una versione già pubblicata o le iscrizioni esistenti.

Implementato dalla PR [#270](https://github.com/jeckodevelopment/jd_learn/pull/270), merge `0a1294e491e0aa531aeca776911674fc701ddff4`; CI standard 4/4 nel [run 43207411958](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958). Package [JD-Learn-0.47.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43207411958/artifacts/13939861423), SHA-256 interno `e65ceefa13de8ec577a84d8284ec3a09012cb6e2950351674dec9af6ed4c009f`; wrapper SHA-256 `7721896ba521c4ba8cfbb836023af16d752df3b2c40890000fe1cb29e22cc6a0`, da estrarre una volta; scadenza 26 novembre 2026. CI copre regole, readiness, pubblicazione, iscrizione e telemetria video senza completamento; il collaudo sito Joomla resta pendente. La modalità non abilita accesso anonimo o anteprima pubblica.

## IMP-02 — anteprime pubbliche esplicite delle lezioni di testo (EDGE-02 / ISC-04)

Il perimetro di questo incremento consente anteprime anonime soltanto per lezioni **Testo** marcate esplicitamente e dopo aver abilitato l’opzione nelle regole didattiche del corso. Non pubblica quiz, video o materiali associati. La scheda deve essere accessibile al catalogo pubblico; l’anteprima è in sola lettura e non crea iscrizioni, progressi o tentativi.

1. Su Joomla 6 di prova crea un corso con catalogo **Public** e contenuti **Registered**, una bozza con due lezioni Testo e un materiale privato. In **Regole didattiche** abilita le anteprime pubbliche; marca una sola lezione come disponibile in anteprima e pubblica.
2. Apri la scheda del corso in una sessione anonima. Deve apparire il collegamento solo accanto alla lezione selezionata. Aprilo: deve mostrare il titolo e il testo, senza richiedere login e senza esporre il materiale privato.
3. Confronta iscrizioni, progressi e tentativi prima/dopo: i conteggi devono restare invariati. La pagina deve indicare chiaramente la consultazione in sola lettura e avere `noindex`.
4. Richiedi direttamente l’URL per la lezione non selezionata, una lezione Video, una lezione fuori dalla finestra o un ID appartenente a un altro corso/versione: nessun contenuto deve essere restituito.
5. In una nuova bozza disabilita le anteprime lasciando una lezione marcata: la pubblicazione deve essere bloccata. Prova a marcare Video o Documento: il form non deve offrire l’opzione e una richiesta manipolata deve essere rifiutata.
6. Rendi il corso non visibile agli ospiti o ritira la versione pubblicata: scheda e URL diretta di preview devono diventare inaccessibili.

Le prove automatizzate di authoring/pubblicazione sono in `tests/joomla-curriculum.php` e `tests/joomla-publication.php`. Il collaudo Joomla manuale resta da eseguire e registrare.

## IMP-08 — filtri ricercabili del roster (0.45.0-dev)

Riferimenti: ISC-01/02/05/07, REP-02, SCR-14; AC-11/12/25/26/40/53/54/60/63/128. Nel backend Joomla aprire il roster di un corso autorizzato con più versioni e iscritti.

1. Verificare che i selettori Versione e Iscritto mostrino soltanto versioni e account associati al corso; digitare nel campo di ricerca di ciascun selettore e verificare il filtro delle opzioni senza invio del form.
2. Scegliere un valore e applicare il filtro: il totale, le righe e la paginazione devono rispettare la selezione. Combinare con ricerca nome/username, stato e avanzamento.
3. Modificare l’URL con una versione di altro corso, ID negativo o non numerico: la richiesta deve essere respinta senza esporre righe esterne. Un iscritto di altro corso non deve comparire nelle opzioni.
4. Ripetere con account amministrativo limitato al corso, anonimo e permesso revocato nella sessione: la protezione deve restare applicata.
5. Confermare che la pagina resta in sola lettura e che dati, progressi e stato iscrizione non cambiano.

Il test automatico `tests/roster-http.py` controlla opzioni native e isolamento corso; `tests/roster-filters.mjs` verifica ricerca delle opzioni e ripristino. Accettazione manuale Joomla da registrare. Implementato dalla PR [#264](https://github.com/jeckodevelopment/jd_learn/pull/264), merge `aed93820f0a948b11d3427ce9ba4d312621b0d74`; CI 4/4 nel [run 43188504970](https://github.com/jeckodevelopment/jd_learn/actions/runs/43188504970). L’esito della CI non sostituisce i cinque scenari manuali sopra.

## MAT-01 — filtro degli allegati per ambito (0.44.0-dev)

Riferimenti: MAT-01, AC-93, AC-97–103. Nel sito Joomla di prova, aprire la gestione materiali di una bozza con almeno un allegato del corso e allegati in due lezioni.

1. Verificare che l’apertura iniziale mostri tutti gli ambiti e il conteggio totale.
2. Selezionare i materiali del corso: devono apparire solo collegamenti con `lesson_id` nullo. Selezionare una lezione: devono apparire soltanto i materiali di quella lezione, senza i collegamenti riutilizzati in altri ambiti.
3. Provare paginazione e ritorno all’elenco: il filtro deve restare selezionato e conteggio/risultati devono rimanere coerenti.
4. Provare modifica, download, sostituzione, riuso e rimozione dal filtro; le azioni devono riguardare solo il collegamento scelto e rispettare ACL/versione. Alterare `material_scope` con un ID lezione di un’altra versione: deve restituire not found e non mostrare materiali.
5. Verificare un ambito vuoto: messaggio esplicito e nessun errore PHP. Ripetere con account senza permesso `jlms.materials.manage`: accesso negato.

**Automatizzato:** fixture `tests/materials-http.py` verifica selettore, separazione corso/lezione e isolamento da collegamenti riutilizzati. L’accettazione manuale Joomla resta da registrare.

## STU-05 — preferenza velocità player (0.42.0-dev)

**Riferimenti:** STU-05, VID-01, PRO-04. La preferenza è locale al browser; il selettore compare solo dopo la verifica di supporto del player e non altera il completamento.

**Comportamenti da testare in Joomla**

1. Installare il package 0.42.0-dev su Joomla 6 di prova e preparare video YouTube, Vimeo, HTML5 e Cloudflare Stream secondo le rispettive guide; usare un profilo Vimeo che consenta le velocità variabili e un video che le supporti.
2. Aprire la lezione YouTube dopo il consenso. Verificare che il selettore mostri solo le velocità restituite dall’API del video; scegliere 1,5× e controllare che il player applichi la velocità e che il valore resti selezionato ricaricando la lezione sullo stesso browser.
3. Ripetere con Vimeo: se SDK/account/video rifiuta la velocità, il selettore non deve comparire; quando supportata, verificare 0,75× e 1,5×.
4. Ripetere con HTML5 e verificare 0,5× e 2×; provare un browser che non espone playbackRate e controllare che il selettore resti nascosto.
5. Aprire Cloudflare Stream e verificare che non compaia il selettore di JD Learn. La riproduzione e i controlli nativi devono restare disponibili.
6. Con video tracciabile, campionare per un intervallo noto a velocità normale e a 1,5×; verificare che il server usi rate_milli effettivamente osservato e che l’avanzamento derivi sempre dagli intervalli unici, senza credito per il solo seek.

### Preferenza lingua caption/sottotitoli (0.50.0-dev)

1. Installare il pacchetto su Joomla 6 di prova. Preparare un video Vimeo con almeno due tracce linguistiche abilitate e un file HTML5 con tracce native `captions`/`subtitles` in due lingue. Aprire la lezione da un account iscritto e autorizzato.
2. Su Vimeo, scegliere una lingua dal controllo **Lingua dei sottotitoli**. Verificare che venga attivata la traccia scelta; ricaricare la lezione nello stesso browser e verificare la persistenza locale. Selezionare **Disattivati** e verificare che la traccia si spenga e che la preferenza venga rimossa.
3. Ripetere con HTML5; verificare che la traccia scelta sia `showing` e le altre siano `disabled`. Provare una preferenza `fr-CA` con traccia `fr`: il selettore deve scegliere la lingua principale corrispondente.
4. Ripetere su un altro browser o profilo: la preferenza non deve trasferirsi. Negare il consenso a un provider esterno: nessuna richiesta SDK o selezione deve avvenire prima del normale consenso/autorizzazione.
5. Dopo aver scelto una lingua su Vimeo o HTML5 nello stesso browser, aprire un video YouTube che contiene quella traccia. Verificare che il player chieda il caption nella lingua salvata; il selettore personalizzato non compare e i controlli YouTube restano disponibili per cambiare lingua. Ripetere con Cloudflare Stream (con codice BCP-47 corrispondente) e verificare il default all’avvio tramite il player provider; non deve comparire il selettore JD Learn. Se il video non ha quella traccia, il player deve continuare secondo il comportamento normale del provider.
6. Rimuovere la preferenza scegliendo **Disattivati** su Vimeo/HTML5 e riaprire YouTube e Cloudflare: JD Learn non deve forzare sottotitoli. Provare un valore locale non valido e localStorage bloccato; i player devono continuare a funzionare senza parametri lingua aggiunti.
7. Nell’anteprima editoriale ripetere la selezione e verificare che non siano creati progressi, iscrizioni o eventi di telemetria. Durante una lezione tracciabile, cambiare lingua e verificare che la selezione non alteri stato, percentuale, soglia o progressi del video.
8. Verificare che la preferenza resti soltanto nel browser corrente, non sia inviata a Joomla e non cambi fra dispositivi; bloccare localStorage e verificare che i player continuino a funzionare a 1×.

**Implementata dalla PR [#276](https://github.com/jeckodevelopment/jd_learn/pull/276), merge `d29cdc9326bcc0d1f9ac3dd454fa40189ca20c82`, versione 0.50.0-dev. CI standard 4/4 [run 43240360856](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856). Pacchetto [JD-Learn-0.50.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43240360856/artifacts/13954487496), SHA-256 interno `fce6b1f09ab3d7d1fe1821aff366a11718e420e2331754ea18cf4b741b0b877c`, digest wrapper `094f514f005324c76af7284e4e0d43a2e09a25563976c94982504e23bf78d342` (estrarre una volta; scade il 26 novembre 2026). I collaudi sul sito Joomla con account/provider reali sono ancora da registrare.

**Esito sito Joomla:** Non eseguito.


---

## IMP-06 — scansione materiali con ClamAV reale (0.41.74-dev)

**Stato:** PR [#229](https://github.com/jeckodevelopment/jd_learn/pull/229) integrata in `0.41.74-dev` (merge `d02aeceb2402b8b609fbe97a4ad31c38aa7b4b8c`). CI standard 4/4 nel [run 43094357303](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303); artifact [JD-Learn-0.41.74-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43094357303/artifacts/13890232415), wrapper SHA-256 `902cc7909297f38936feddade12020bffec6335a7fd430ffe173b71e172e29c8`. Accettazione manuale Joomla sul sito di prova ancora pendente.

**Comportamenti da testare in Joomla**

1. In un sito di prova, configura ClamAV con socket Unix locale e attiva **Componenti → JD Learn → Opzioni → Materiali → Scansione antivirus obbligatoria**.
2. Carica un file innocuo in una bozza e verifica che il risultato ClamAV sia `clean`, lo stato diventi pronto e l'asset possa essere usato dal preflight.
3. Solo in un ambiente usa-e-getta, carica il file di test EICAR come allegato. Verifica che sia rifiutato e che download e pubblicazione restino bloccati; elimina il record rifiutato.
4. Ferma ClamAV e carica un altro file: deve restare in quarantena. Riavvia il servizio, avvia la scansione dalla gestione materiali e verifica che il file innocuo diventi pronto.
5. Ripeti con interfaccia italiana e inglese, controllando che i messaggi distinguano esito infetto e scanner indisponibile.

La CI standard usa ClamAV reale, firme aggiornate e EICAR; la suite resta distinta dal collaudo sul server Joomla.

**Esito sito Joomla:** Non eseguito.

---

## IMP-05 — configurazione Cloudflare Stream solo API (0.41.73-dev)

**Stato:** PR [#228](https://github.com/jeckodevelopment/jd_learn/pull/228) integrata in `0.41.73-dev` (merge `d677924aca265f3397be156f7298d614bad330f3`). CI 4/4 nel [run 43091365354](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354); pacchetto [JD-Learn-0.41.73-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43091365354/artifacts/13888777492), SHA ZIP `b3f577a3760a5884290a9dcb52ad09a10aaf3fd2d04d521408f1574f31c83172`. Accettazione manuale Joomla con account Cloudflare reale ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Installa JD Learn 0.41.73-dev sopra una versione precedente con il plugin Cloudflare Stream configurato. Apri **Sistema → Plugin → Cloudflare Stream**: il form non deve mostrare modalità di firma, chiave locale o ID della chiave.
2. Verifica che playback, account, dominio, hostname, riferimento al token e durata/rinnovo restino disponibili. I parametri legacy di firma salvati non devono impedire il funzionamento API.
3. Con un account di prova e un video protetto già configurati, verifica pubblicazione/riproduzione/rinnovo dopo consenso. Revoca il consenso e verifica che non venga creato il player.
4. Ripeti in italiano e inglese. Verifica che il video e il token di riferimento non siano modificati dall'upgrade.

**Esito sito Joomla:** Non eseguito.

---

## IMP-06 — periodo configurabile per la pulizia degli asset locali orfani (0.41.72-dev)

**Stato:** candidato di sviluppo; la verifica automatica di Joomla Scheduler è aggiornata per distinguere il periodo configurato da una soglia fissa. Accettazione manuale in Joomla ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Apri **Componenti → JD Learn → Opzioni → Materiali**. Verifica che la nuova impostazione abbia default **24 ore**, accetti valori da **24 a 8760** ore e mostri la descrizione in italiano e inglese. Salva 48 ore e riapri le opzioni: il valore deve restare.
2. Configura storage privato fuori dalla document root e abilita **Task - JD Learn**. Crea manualmente l'attività “JD Learn: pulisci i file materiali orfani” con limite 100.
3. Avvia l'attività prima che un asset orfano raggiunga 48 ore: il suo blob e record devono restare. Gli allegati ancora referenziati devono restare anche quando sono più vecchi.
4. Dopo 48 ore, avvia di nuovo l'attività: gli asset locali non referenziati eleggibili possono essere rimossi; file sconosciuti nella cartella, asset referenziati e materiali di versioni pubblicate devono restare intatti.
5. Imposta il periodo massimo di 8760 ore e verifica che un asset orfano più giovane non venga rimosso da un'esecuzione giornaliera.

## IMP-07 — Associazioni multilingua dei corsi pubblicati (CORE-07, 0.41.68-dev; PR #223 integrata)

**Stato:** PR [#223](https://github.com/jeckodevelopment/jd_learn/pull/223) integrata nel main per AC-20 e AC-48. Il form nativo può modificare il grafo delle associazioni anche quando i corsi sono pubblicati; le versioni didattiche devono restare immutate. Pacchetto installabile `dist/pkg_jlms-0.41.68-dev.zip` (SHA-256 `ed115844d2de2925f8a087a3a8cdbed646e870bfd529ca31eb3b2dfd1f97a7c0`). Accettazione Joomla manuale da eseguire.

**Comportamenti da testare in Joomla**

1. Abilita il plugin Language Filter e crea/pubblica due corsi, uno `en-GB` e uno `it-IT`, ciascuno con una versione didattica, materiali e progresso di un iscritto.
2. Nel form del corso inglese collega la traduzione italiana; salva e riapri entrambi i corsi per verificare il collegamento reciproco e i selettori lingua.
3. Rimuovi e poi ripristina il collegamento da un corso pubblicato. Verifica nel selettore di un altro corso che i corsi pubblicati siano selezionabili.
4. Controlla che dopo ogni modifica gli ID delle versioni live, il programma, gli iscritti e i progressi siano invariati.
5. Prova lingua duplicata, lingua `*`, target archiviato/non valido e target collegato a un altro gruppo: il salvataggio deve rifiutare o lasciare il grafo esistente coerente.
6. Con un utente senza permesso di modifica o con un corso in checkout da un altro operatore, verifica che una richiesta diretta non possa modificare o staccare il gruppo.
7. Apri la pagina pubblica e verifica che la Language Switcher mostri soltanto traduzioni pubblicate e accessibili; una traduzione non accessibile non deve essere rivelata.

## IMP-07 — Ricerca nei selettori delle traduzioni (CORE-07, 0.41.69-dev; [PR #224](https://github.com/jeckodevelopment/jd_learn/pull/224) integrata)

**Stato:** PR #224 integrata in main 0.41.69-dev. Il campo filtra localmente i corsi autorizzati per titolo o ID; limite: 2.000 candidati per lingua. La convalida server è indipendente. Pacchetto [JD-Learn-0.41.69-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43057819152/artifacts/13872631084) (SHA-256 `54b8667258a833fcd907b3a9767abbd2d7cadbbd8e01fceecd0dda1c86034450`). CI [#43057819152](https://github.com/jeckodevelopment/jd_learn/actions/runs/43057819152): controlli documentali e suite PHP 8.3/MySQL e PHP 8.4/MariaDB superati; job Chromium annullato durante l’installazione del runtime; suite non eseguita. Accettazione Joomla manuale ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Apri un corso con almeno 20 corsi eleggibili in una lingua di destinazione. Nel campo di ricerca digita una parte del titolo e verifica che restino selezionabili solo le corrispondenze.
2. Cerca un ID completo e un frammento dell'ID nel titolo visualizzato (#ID); verifica che l'esito corretto sia selezionabile.
3. Inserisci un testo senza corrispondenze: l'opzione di scollegamento resta disponibile e nessun altro corso deve poter essere scelto.
4. Cancella il testo, scegli una traduzione e salva; riapri la scheda e verifica la selezione persistente.
5. Ripeti su due lingue di destinazione nella stessa scheda: il filtro di un campo non deve alterare l'elenco dell'altro.
6. Verifica che un corso senza permesso di modifica e un corso in checkout da un altro operatore non compaiano fra i candidati, anche cercandone titolo e ID.
7. Con il filtro attivo salva una modifica alle associazioni; verifica che la validazione server mantenga i controlli su lingua, ACL, checkout e appartenenza a un altro gruppo.

## IMP-07 — Tag Joomla nativi sui corsi (CORE-09, 0.41.67-dev; PR #222 integrata)

**Stato:** PR [#222](https://github.com/jeckodevelopment/jd_learn/pull/222) integrata nel main; associazioni registrate con il tipo contenuto `com_jlms.course`. Criteri collegati: CORE-09, AC-19 e AC-64. Pacchetto installabile `dist/pkg_jlms-0.41.67-dev.zip` (SHA-256 `637e8284dde13f5b3c89398bac21a1aa862236a07619f978f33130dc8ec07ef6`). Accettazione manuale in Joomla ancora da eseguire.

**Comportamenti da testare in Joomla**

1. Crea due tag pubblicati in Joomla → Tag. Crea un corso in bozza, assegna entrambi i tag e salva; riapri il corso e verifica che le selezioni restino.
2. Pubblica il corso e verifica che i tag compaiano nelle viste/filtri nativi `com_tags` e nel filtro tag del catalogo JD Learn, rispettando stato, accesso, lingua e ACL del corso.
3. Rimuovi un tag dal corso, salva e verifica che non risulti più associato né tra i risultati filtrati per quel tag.
4. Duplica il corso: verifica che i tag siano copiati nel nuovo corso; cambia la selezione nel duplicato e controlla che l’originale non cambi.
5. Rinomina un tag dal gestore Joomla e verifica che il nuovo nome sia visibile nell’editor, nei filtri e nelle viste tag senza ricreare l’associazione.
6. Verifica separatamente un corso italiano e uno inglese, e accedi con un utente privo dei permessi di modifica: non deve poter alterare le associazioni con una richiesta diretta.

## IMP-06 — consegna protetta dei materiali su Apache/LiteSpeed (0.41.66-dev)

**Stato:** PR [#221](https://github.com/jeckodevelopment/jd_learn/pull/221) integrata nel main 0.41.66-dev. Il flusso usa PHP protetto e non richiede Nginx o moduli aggiuntivi. Pacchetto installabile: `dist/pkg_jlms-0.41.66-dev.zip` (SHA-256 `214e311d5cee72963b3e9d01ba958dfa52088b781c52052761e4f1d74ffdc4af`). Accettazione Joomla da eseguire.

**Comportamenti da testare in Joomla**

1. In Opzioni componente configura la directory privata dei materiali e abilita la consegna PHP. Non aggiungere location o moduli specifici del web server.
2. Come studente autorizzato scarica un allegato e apri in anteprima un PDF e un’immagine. Verifica contenuto, MIME e Content-Disposition; il download non deve assegnare progresso alla lezione.
3. Prova una richiesta Range e HEAD: Range deve restituire solo i byte richiesti; HEAD deve restituire gli header senza corpo.
4. Revoca il livello di accesso o sospendi l’iscrizione e ripeti la richiesta: il file deve essere negato anche con la sessione già autenticata.
5. Verifica che un file alterato o di dimensione incoerente non venga servito e che la directory di storage resti fuori dalla document root.

## IMP-07 — ricollocazione esplicita delle dipendenze categoria (0.41.70-dev)

Riferimenti: CAT-01, CORE-01, LIFE-02. Il nuovo strumento backend esamina i riferimenti alle categorie e permette di ricollocare i record in categorie destinatarie della stessa sezione prima della rimozione. Verificare in Joomla 6 di prova con categorie dedicate a corsi, percorsi, docenti e domande. Per la prova sui corsi usare un corso pubblicato con versione attiva, iscrizione/progresso e associazione multilingua.

**Comportamenti da testare in Joomla**

1. Apri JD Learn → Gestione dipendenze categorie, seleziona una categoria con record collegati e verifica conteggi e destinazioni consentite.
2. Verifica che le destinazioni appartengano alla stessa sezione, non includano la sorgente o sue discendenti e rispettino i permessi dell’operatore.
3. Ricolloca record in ciascuna delle quattro sezioni. Verifica che i riferimenti cambino categoria e che record, storici, versioni, iscrizioni, progressi, tentativi e risultati restino invariati.
4. Per i corsi, verifica in Smart Search che gli elementi idonei riflettano la nuova categoria; ripeti anche con Finder disattivato.
5. Modifica i dati mentre la pagina di ricollocazione è aperta e riprova l’operazione: deve essere annullata integralmente se i conteggi non coincidono più.
6. Dopo la ricollocazione elimina la sorgente dal gestore Categorie nativo. Ripeti con categorie in checkout, destinazioni non autorizzate, destinazioni di altro tipo, nessun target scelto e una categoria con discendenti; nessuna operazione parziale o eliminazione implicita deve verificarsi.

## IMP-07 — lifecycle sicuro delle categorie (0.41.63-dev, PR #215 integrata)

**Stato:** CI PR #215 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654); eseguire la prova manuale lifecycle/ricollocazione categorie sul pacchetto [JD-Learn-0.41.70-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43075293879/artifacts/13881162374). La CI verifica FK su corsi, percorsi, docenti e domande, rifiuto della cancellazione permanente con riferimenti e conservazione del corso durante cestinamento/ripristino.

**Comportamenti da testare in Joomla**

1. Crea una categoria corsi e collegale un corso pubblicato con iscrizione e progresso già registrati.
2. Dal gestore categorie cestina la categoria. Verifica che la categoria e il corso spariscano dal catalogo pubblico, mentre il record corso, l’iscrizione e il progresso restano presenti.
3. Ripristina la categoria dal cestino e verifica che la visibilità del corso torni senza alterare versione, iscrizione o progresso.
4. Prova la cancellazione permanente della categoria ancora collegata al corso: Joomla/database deve rifiutarla e i record devono rimanere integri.
5. Sposta il corso in un’altra categoria valida; ripeti la cancellazione permanente e verifica che sia consentita.
6. Ripeti i passaggi 1, 4 e 5 per una categoria usata da un percorso, un docente e una domanda.

## IMP-07 — filtri docente e percorso nel catalogo (0.41.62-dev, PR #213 integrata)

PR [#213](https://github.com/jeckodevelopment/jd_learn/pull/213), milestone **1.0.0**. CI standard 4/4 nel [run 42923290761](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761); pacchetto [JD-Learn-0.41.62-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42923290761/artifacts/13811584820), checksum wrapper `003c56dd360cd8a7c375c220a9b1a0ce0c57e6669913a693c63ea6781f032cf2`, checksum ZIP Joomla `e26a39a0c63c3ba07a9851b49b701582d3df8a656ac55c634cd1414ff9412728`. Riferimenti: CAT-01, AC-41 e AC-85. Su Joomla 6 di prova predisporre corsi pubblici con docenti pubblici e percorsi pubblicati; aggiungere profili o percorsi con accesso limitato collegati a corsi accessibili.

1. Da ospite, verificare nel catalogo e nella pagina categoria i filtri Docente e Percorso. Le opzioni devono includere solo profili/percorsi pubblici associati a corsi visibili nel contesto.
2. Selezionare un docente: devono restare i corsi pubblici a lui assegnati. Ripetere selezionando un percorso: mostrare solo i corsi visibili associati al percorso.
3. Combinare i due filtri con tag, difficoltà, durata, ricerca, ordinamento, vista elenco e paginazione. In una categoria i risultati restano nel ramo della categoria.
4. Accedere con account a livelli differenti e verificare opzioni e risultati coerenti con le autorizzazioni correnti; non devono trapelare profili, percorsi o corsi privati.
5. Inviare ID inesistenti o non accessibili per entrambi i filtri: risultati vuoti senza allargare il catalogo. Ripetere in IT/EN, SEF attivo/disattivo e viewport mobile.

**Esito sito Joomla:** Non eseguito.

## IMP-07 — filtro tag nel catalogo pubblico (0.41.60-dev, PR #209 integrata)

Riferimenti: CAT-01, AC-41 e AC-85. Installare il pacchetto candidato su Joomla 6; predisporre corsi pubblicati con tag pubblici, tag con accesso differenziato e corsi privati o in categorie non visibili.

1. Da ospite, verificare catalogo e pagine categoria: il selettore mostra solo tag pubblicati/accessibili collegati a corsi visibili.
2. Filtrare per tag nel catalogo e in una categoria con corsi diretti e discendenti: mostrare solo corsi associati, mantenendo il ramo categoria.
3. Accedere con un utente autorizzato e verificare che compaiano i tag aggiuntivi consentiti e solo i corsi per cui ha accesso.
4. Combinare tag, difficoltà, durata, ricerca, ordinamento e paginazione; un ID tag inesistente non deve allargare i risultati.
5. Ripetere IT/EN e SEF attivo/disattivo; non devono comparire titoli, riassunti o conteggi di corsi nascosti.

## IMP-07 — filtri di difficoltà e durata del catalogo (0.41.59-dev, PR #210 integrata)

PR [#210](https://github.com/jeckodevelopment/jd_learn/pull/210), milestone **1.0.0**, integrata con squash nel commit `e5d0bae954c099c8522db57c30b1e32882b468ee`. CI standard 4/4 nel [run 42913999069](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069). Pacchetto [JD-Learn-0.41.59-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42913999069/artifacts/13807843959), wrapper SHA-256 `c1e9e8e7c3e64daebc4c5162f64695c89b0d3d79a9c41019f5eeee087227644c`, ZIP Joomla SHA-256 `f22a8a3fb1c87e17f10f0342e6ba6980e5c8882e83dcfec41bab34713b3c41dc`, disponibile fino al 24 novembre 2026.

Riferimenti: CAT-01/CAT-04, AC-41/AC-85. Verificare su Joomla 6 di prova con almeno quattro corsi pubblicati, nelle categorie accessibili: uno principiante da45minuti, uno avanzato da90minuti, uno intermedio oltre120minuti e uno senza durata stimata. Predisporre anche un account con accesso limitato e una categoria con corsi discendenti.

1. Aprire il catalogo da ospite e come account autorizzato. Atteso: i filtri difficoltà e durata sono disponibili con label tradotte in italiano/inglese.
2. Selezionare principiante e fascia30–60minuti: deve restare solo il corso principiante da45minuti. Selezionare avanzato o oltre120minuti e verificare i rispettivi corsi; le schede senza durata non devono comparire quando una fascia è attiva.
3. Aggiungere ricerca e ordinamento, poi impostare un filtro dalla pagina di una categoria: risultati e paginazione devono rispettare tutti i filtri e restare nel ramo categoria selezionato.
4. Ripetere con un corso o categoria non visibile all’account limitato: il filtro non deve rivelare titoli, riassunti o conteggi dei corsi esclusi.
5. Inviare manualmente valori non previsti per difficoltà e durata: il catalogo deve restare integro e trattarli come filtri assenti.

**Esito sito Joomla:** Non eseguito.

## IMP-07 — metadati SEO e social pubblici (0.41.57-dev, PR #207 integrata)

PR [#207](https://github.com/jeckodevelopment/jd_learn/pull/207), milestone **1.0.0**, integrata con squash nel commit `32215c493fe1d7a2bf99871b5f93087a842563bd`. CI standard 4/4 nel [run 42905972598](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598). Pacchetto [JD-Learn-0.41.57-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42905972598/artifacts/13804558462), wrapper SHA-256 `f0729b8cfe769f5d7f2065522651bc4b56278c6a4fdb2e29adae4e54be2199de`, ZIP Joomla SHA-256 `7e078f93ff421c143e7a7c198911e4d38c5c6208c788c5920080fd5d4d642921`, disponibile fino al 24 novembre 2026. Collaudo manuale sul sito Joomla di prova richiesto; l’esito va registrato qui dopo la prova.

1. Installare il pacchetto candidato su Joomla di prova e creare una scheda corso pubblica con titolo SEO, descrizione SEO e immagine promozionale locale; creare anche un corso con catalogo pubblico ma contenuto riservato, una categoria pubblica e una categoria accessibile solo agli utenti registrati, e un percorso pubblico.
2. Aprire la scheda pubblica e verificare nel sorgente HTML titolo/descrizione, `og:title`, `og:description`, `og:type`, `og:url`, `og:image`, `twitter:card`, `twitter:title`, `twitter:description` e `twitter:image`. L’immagine deve essere un URL assoluto del sito.
3. Aprire categoria e percorso pubblici: verificare canonical assoluto, titolo e descrizione social coerenti con la pagina, senza duplicati di meta tag.
4. Aprire scheda corso con catalogo o contenuto non accessibile agli ospiti e la categoria privata: verificare `robots=noindex, nofollow` e assenza di canonical/preview social che li promuovano come contenuti pubblici. Accedere poi da utente autorizzato: i contenuti restano consultabili, ma non devono diventare indicizzabili.
5. Aprire un percorso da utente autenticato: verificare `robots=noindex, nofollow` e assenza di metadati social/canonical.
6. Ripetere con SEF attivo e disattivo e in IT/EN; verificare che canonical sia assoluto, stabile e privo di parametri di ricerca, filtro o paginazione.

## IMP-07 — Ricerca e ordinamento del catalogo (0.41.56-dev, PR #206 integrata)

La PR aggiunge ricerca su titolo/sommario e ordinamento recenti/titolo, nel catalogo completo e dentro una categoria. Verificare sul sito Joomla 6 di prova con corsi pubblici e contenuti riservati di fixture.

1. Installare il pacchetto candidato. Aprire Catalogo corsi e verificare i controlli “Cerca nei corsi” e “Ordina per” in italiano; ripetere con lingua inglese.
2. Cercare una parola univoca nel titolo e poi una nel sommario promozionale; verificare che compaiano solo le schede pubbliche accessibili.
3. Usare un termine presente solo in una lezione o in un campo privato: nessun corso deve comparire per quel termine.
4. Ordinare per “Titolo” e per “Più recenti”; verificare ordine stabile e che paginazione e selezione restino coerenti.
5. Aprire una categoria con discendenti, cercare un corso appartenente a un discendente, cambiare ordinamento e paginare; verificare che la ricerca resti limitata alla categoria e ai discendenti.
6. Provare caratteri speciali e stringhe con virgolette; nessun errore SQL o allargamento ai contenuti protetti.

La PR #206 è stata integrata nel commit `cc5af168e5b42aff0b30db2e051acecd86806b9c`; CI standard 4/4 superata nel [run 42901417240](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240). [Pacchetto installabile JD-Learn-0.41.56-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42901417240/artifacts/13803875500), wrapper SHA-256 `3c3d7d078ae6b3d03e530d81d833bd89babd8c12903580f84f8d8f4b3b526cd2`, disponibile fino al 24 novembre 2026. Estrarre il wrapper per ottenere lo ZIP Joomla. La CI verifica il rendering HTTP e l’isolamento dei termini di ricerca in fixture; l’accettazione sul sito reale resta da registrare separatamente.

## IMP-07 — Smart Search del catalogo JD Learn (0.41.55-dev, PR #204 integrata)

La PR #204 è stata integrata nel commit `5018d895e52727be29cedb31f4f93b5245ef346a`. CI standard 4/4 superata nel [run 42898585659](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659). Il collaudo manuale Joomla sul sito di prova resta richiesto prima di chiudere IMP-07; la CI non sostituisce queste prove. Pacchetto installabile [JD-Learn-0.41.55-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42898585659/artifacts/13802930146), wrapper ZIP SHA-256 `48af954a9fa682519e5a6c8121ef97d22b17bd37004f9969e1e8c59c694e66e1`, disponibile fino al 24 novembre 2026. Estrarre il wrapper per ottenere il pacchetto Joomla.

1. Installare il pacchetto candidato su Joomla 6 di prova con `com_finder` attivo. Verificare **Estensioni → Plugin**: `Smart Search - JD Learn` è abilitato alla prima installazione e resta nello stato scelto durante un aggiornamento.
2. Predisporre corsi con titolo/sommario univoci: pubblicato in categoria pubblica, non pubblicato, catalogo con livello registrato, categoria privata, antenato non pubblicato, lingua differente, senza versione pubblicata, e pubblicato con `publish_up`/`publish_down` configurato. Predisporre anche un corso pubblico con titolo e sommario promozionale.
3. In **Componenti → Smart Search → Indice**, avviare l’indicizzazione e verificare che risultino soltanto i corsi pubblici idonei. Cercare dal sito il titolo univoco: il link apre la scheda corso e mostra solo titolo/sommario; nessuna lezione o informazione privata compare nel risultato.
4. Modificare titolo e sommario del corso pubblico, reindicizzare, quindi verificare i nuovi termini e l’assenza dei precedenti. Ritirare/pubblicare un corso e verificare rispettivamente rimozione/ricomparsa del risultato.
5. Cambiare accesso, stato o lingua di una categoria/antenato; spostare una categoria con corsi dentro/fuori. Dopo aggiornamento indice, verificare le nuove regole sulle schede discendenti.
6. Ripetere in IT e EN con filtro lingua attivo. Verificare che corsi incompatibili con la lingua categoria non compaiano e che i corsi con finestra programmata restino esclusi.

## PR #196 integrata — retry del progresso audio

PR [#196](https://github.com/jeckodevelopment/jd_learn/pull/196), milestone **1.0.0**, introduce un retry singolo dello stesso salvataggio per errori di rete, timeout, 408, 429 e 5xx. Gli errori permanenti non vengono riprovati. Il progresso audio non completa la lezione né aumenta la percentuale; la conferma esplicita resta necessaria. CI 4/4 nel [run 42876421523](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523). Collaudo manuale pendente.

1. Avviare una lezione audio, attendere il salvataggio e ricaricare: la ripresa deve partire dal punto registrato.
2. Mettere in pausa e spostare il cursore; verificare salvataggio e ripresa della posizione aggiornata.
3. Simulare rete/5xx: il client deve ritentare una volta lo stesso punto e mostrare il salvataggio se riuscito.
4. Simulare 403: nessun retry ripetuto, errore visibile, nessun cambiamento di completamento o percentuale.
5. Arrivare alla fine dell’audio: la lezione deve richiedere ancora la conferma esplicita.

[Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42876421523/artifacts/13793090250), disponibile fino al 24 novembre 2026.

## PR #194 integrata — filtri della banca domande

PR [#194](https://github.com/jeckodevelopment/jd_learn/pull/194), milestone **1.0.0**, aggiunge i controlli Ricerca, Categoria, Stato e Lingua e corregge l’intestazione Azioni. CI standard 4/4 superata nel [run 42873607501](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501). Collaudo manuale pendente.

1. Aprire JD Learn → Banca domande come amministratore autorizzato.
2. Verificare i quattro controlli; filtrare singolarmente, poi combinarli. Usare Pulisci.
3. Verificare risultati, paginazione e ordinamento.
4. Verificare **Azioni** in italiano e **Actions** in inglese, senza placeholder.
5. Con un ruolo senza `jlms.quiz.manage`, verificare che l’accesso sia negato.

[Pacchetto installabile](https://github.com/jeckodevelopment/jd_learn/actions/runs/42873607501/artifacts/13791570779).

## PR #192 integrata — default “Usa globali” per il fuso del corso

Issue [#172](https://github.com/jeckodevelopment/jd_learn/issues/172) corretta: il campo Timezone nativo Joomla offre e seleziona “Usa globali” per un nuovo corso. Il salvataggio risolve l’opzione nel fuso IANA di Configurazione globale; corsi esistenti e date UTC restano invariati. PR [#192](https://github.com/jeckodevelopment/jd_learn/pull/192), merge commit `64ae66454c7d2a64a3237f1f7f12b2d9174f95ae`.

CI standard 4/4 superata nel [run 42865678167](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167). Pacchetto [JD-Learn-0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42865678167/artifacts/13787890952); wrapper SHA-256 `9b29ce5e6d2e5cc54f857d64080b3dfb45be6ef46dd967248cb04ba20113d3c4` (543923 byte), pacchetto Joomla interno `2a42ae0a50139cf727995b2ab50764d33fe6734ab738a09e6f7cde62aab19ac3` (544064 byte). Scadenza artifact: 24 novembre 2026, 17:34 UTC. Collaudo manuale sul sito Joomla ancora pendente:

1. Impostare **Europe/Rome** in Configurazione globale → Server → Fuso orario.
2. Aprire JD Learn → Corsi → Nuovo e verificare che il campo fuso mostri **Usa globali**, non Abidjan.
3. Inserire una data/ora locale, salvare e riaprire: il selettore deve mostrare Europe/Rome e l’orario deve restare invariato.
4. Cambiare il fuso globale, creare un altro corso e verificare che resti selezionato **Usa globali**; dopo il salvataggio deve risultare il nuovo fuso globale.
5. Aprire un corso con fuso esplicito e verificare che fuso e date non cambino.

## PR #190 integrata — indice aula su smartphone (0.41.52-dev)

CI standard 4/4 superata nel [run 42860617351](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351). Il [pacchetto 0.41.52-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42860617351/artifacts/13784608215) contiene il runtime verificato (SHA-256 interno `278d260dddbdcdda305fff7456943d5ee43a15d042c94e1c3d901fe9f166c332`, wrapper `6214644bc36d91e60cb7da5b9d31f9216e3e53c8492eba1a5ea3ac5fa4962ddd`; scade il 24 novembre 2026 alle 17:06 UTC). La procedura manuale seguente resta pendente.

1. Aprire da smartphone (viewport 375 px) un corso pubblicato con più moduli e lezioni; aprire una lezione accessibile. Atteso: il programma inizia chiuso e il contenuto della lezione appare senza scorrere l’intero indice.
2. Attivare “Mostra programma”. Atteso: si espande l’indice, il controllo cambia in “Nascondi programma” e aria-expanded passa a true; attivarlo di nuovo lo chiude. Verificare navigazione da tastiera e focus visibile.
3. Selezionare una lezione dal programma aperto. Atteso: viene caricata la lezione selezionata e al nuovo caricamento l’indice mobile torna chiuso.
4. Ridimensionare il viewport oltre 48rem. Atteso: il controllo sparisce e il programma resta visibile nella colonna desktop. Verificare anche la navigazione su corso con lezione bloccata e senza JavaScript: contenuto non autorizzato non viene mostrato e i link del programma restano raggiungibili.

## PR #187 integrata — retry del salvataggio di avvio (0.41.51-dev)

CI standard 4/4 superata nel [run 42845258898](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898). Il [pacchetto 0.41.51-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42845258898/artifacts/13779340061) contiene il runtime verificato (SHA-256 interno `1f7563bd4957f66027dbc1d9b925784a087e196a75ad8909d4b78ce9b2455cab`); questa procedura Joomla resta pendente.

1. Installare il pacchetto verificato 0.41.51-dev su Joomla 6 di prova. Predisporre un corso pubblicato e uno studente iscritto; aprire una lezione testo/documento accessibile e verificare lo stato visibile “Salvataggio dell’attività…” seguito da “Attività avviata e salvata”, quindi “In corso” nell’indice.
2. Con gli strumenti di rete del browser, provocare un errore transitorio (timeout/connessione interrotta oppure risposta 503) alla prima chiamata `lesson.begin`; ripristinare la rete durante la finestra di retry. Verificare una sola seconda richiesta, con la stessa chiave `idempotency_key`, esito salvato e una sola riga di progresso `started`.
3. Lasciare la rete offline per entrambe le richieste. Atteso: al massimo due tentativi e messaggio d’errore; nessun completamento/credito e ricaricando la pagina il flusso può riprovare.
4. Provare un rifiuto 403 (sessione/token non valido o accesso revocato) e verificare che non sia ripetuto; nessun contenuto riservato viene mostrato o mutato.
5. Verificare che il completamento manuale successivo conti una sola volta e che l’avvio di una lezione audio/video non azzeri posizione o copertura già acquisita.

## PR #185 integrata — avvio e ripresa della lezione (0.41.50-dev)

CI standard 4/4 superata nel [run 42839639601](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601). Il pacchetto [0.41.50-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42839639601/artifacts/13776106013) verificato contiene SHA-256 interno `9534346da253c8f953da826d203b3f3509fab0682b09e7d09736e60180e28e83` (542668 byte) e wrapper `461d8dd9c4d8287c15d6dc667821920e25c4c879108f0a3d9a2ac8cea5ec70f6` (542489 byte), con scadenza 24 novembre 2026 15:03 UTC. L’accettazione manuale resta aperta.

1. Installare il pacchetto su Joomla 6 di prova. Creare un corso pubblicato con due lezioni obbligatorie testo/documento, iscrivere uno studente e aprire la seconda lezione senza premere “Segna come completata”.
2. Verificare che lo stato della seconda lezione diventi “In corso”, l’avanzamento del corso resti 0% e la lezione non risulti completata. Ricaricare l’aula e verificare la persistenza dello stato.
3. Tornare alla Dashboard, usare “Riprendi” e verificare che riapra l’ultima lezione aperta ancora disponibile. Ripetere con altro browser/dispositivo sullo stesso account.
4. Premere “Segna come completata”: la stessa attività passa a completata, avanza il corso una sola volta e non genera conflitti o righe duplicate. Verificare anche una lezione audio/video, per cui il player continua a salvare posizione/copertura senza cambiare la regola di completamento.
5. Tentare avvio su iscrizione non propria, lezione futura, chiusa, bloccata da prerequisiti, token mancante o metodo GET: atteso diniego e nessuna mutazione. Confermare che visualizzare una pagina bloccata non crea progresso.

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
2. Come revisore…8326 tokens truncated…sponibile nella finestra e non più disponibile dopo la fine.
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

**Aggiornamento 0.41.77-dev (IMP-03):** l'anteprima include ora i materiali dell'ambito corso anche mentre si visualizzano lezioni di testo o video. I file devono essere pronti, attivi, nella finestra di disponibilità e non marcati come soli campioni pubblici. I materiali associati a una lezione restano limitati alle lezioni Documento/Audio.

6. Preparare un allegato corso privato pronto, un campione marcato come anteprima pubblica, un file futuro e una lezione di testo e una video. Aprire entrambe le lezioni nell'anteprima editoriale: il solo allegato corso disponibile per gli iscritti deve apparire in ciascuna vista; il campione pubblico e il file futuro non devono comparire. Usare il collegamento del materiale e verificare che il download protetto riesca; alterare corso/versione/lezione/allegato e verificare che non venga restituito un file.
7. Confrontare iscrizioni, tentativi e progressi prima e dopo l'apertura e il download: devono restare invariati.

**Stato build:** PR [#235](https://github.com/jeckodevelopment/jd_learn/pull/235) integrata nel commit `dbd277605a7eebd18d5d16515853a5f61a40d3f0`; CI standard 4/4 nel [run 43110571231](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231). Pacchetto [JD-Learn-0.41.77-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43110571231/artifacts/13896755810), ZIP interno SHA-256 `22f41d76870fb2c5b39fa621d8d9d0e596ccf1a9c19886fe712aeac8d4159cd1`, wrapper SHA-256 `fcbdd65eac2f49d17a987d5510e39581dd445335e4014393f4a2db370d82f61f`, scadenza 25 novembre 2026.

**Esito sito Joomla per 0.41.77-dev:** Non eseguito.

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

Run preliminare CI della PR #187: il job leggero è riuscito, ma i job PHP/database e Chromium sono risultati `skipped` sul primo evento ready. Il candidato non è considerato verificato finché un nuovo run non esegue la matrice runtime standard.


## IMP-06 — anteprima pubblica esplicita dei materiali corso (0.41.76-dev)

**Aggiornamento PR #233, integrata in 0.41.76-dev:** la pubblicazione della versione deve accettare gli allegati corso esplicitamente selezionati come anteprima, se pronti e validi; allegati lezione con flag preview devono restare rifiutati. La suite HTTP automatica ora copre pubblicazione e accesso pubblico/negato.

**Stato build:** PR [#233](https://github.com/jeckodevelopment/jd_learn/pull/233) integrata nel commit `5b7e7460dfa637b2847d6231f337b830950887df`; CI standard 4/4 nel [run 43106823340](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340). Pacchetto [JD-Learn-0.41.76-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43106823340/artifacts/13894937008), ZIP interno 569117 byte, wrapper SHA-256 `2a50af30a6bfa349806c38325c0388771e983d9f56495186736375519e077665`; scadenza 2026-11-25T21:56:46Z. Accettazione manuale ancora non eseguita.

Riferimenti: MAT-02/04/08, AC-100/101/103. Usare Joomla 6 di prova eliminabile, un corso pubblicato visibile agli ospiti, una bozza aggiornabile e un account con permesso di gestione materiali. Preparare due allegati corso (PDF innocuo e TXT), un allegato lezione, un file in quarantena e un file con data futura.

1. Nella bozza caricare PDF e TXT come materiali del corso. Lasciare il PDF privato e selezionare **Disponibile come anteprima pubblica** solo per il TXT; salvare, riaprire e verificare che il valore resti selezionato. Provare l'opzione su un allegato lezione: il salvataggio deve rifiutare la combinazione e non deve renderlo pubblico.
2. Pubblicare la nuova versione. Da ospite aprire la scheda del corso: la regione **Materiali del corso** deve mostrare solo il TXT, con titolo, descrizione, nome file, MIME e dimensione. Il PDF privato, l'allegato lezione, il file in quarantena e quello fuori finestra non devono apparire.
3. Aprire il link del TXT e verificare download riuscito. Per PDF/immagine esplicitamente pubblicati verificare apertura inline; per gli altri formati il browser deve ricevere il download. La risposta deve includere cache privata/no-store e `X-Robots-Tag: noindex, nofollow`.
4. Provare URL dirette per un attachment privato, ID associato a un altro corso/versione, corso non visibile, materiale non pronto, disabilitato o fuori finestra; atteso 404/negato senza contenuto. Ripetere con POST e aspettarsi rifiuto del metodo.
5. Disabilitare il corso, ritirare la versione corrente oppure disabilitare/scadere il file; riprovare il medesimo link. L'accesso deve cessare senza dipendere dalla pagina già aperta. Collegare un asset esistente alla bozza: il nuovo collegamento deve restare privato finché l'editor non seleziona esplicitamente l'anteprima per l'ambito corso.

**Esito sito Joomla:** Non eseguito.

## IMP-06 — lifecycle e riuso dei materiali (0.41.64-dev)

Riferimenti: MAT-01–08, DAT-04, SCR-16, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. Su Joomla 6 di prova predisporre un corso con una bozza, almeno due lezioni e un editor con permesso di gestione materiali.

1. Caricare un allegato nella prima lezione e selezionare **Collega materiale esistente** verso materiali del corso. Atteso: il nuovo collegamento compare in entrambi gli ambiti e usa lo stesso file; nessuna seconda copia viene caricata.
2. Collegare lo stesso file a una seconda lezione, poi provare a collegarlo di nuovo allo stesso ambito. Atteso: i due ambiti validi lo rendono disponibile senza duplicati locali; la ripetizione viene rifiutata con un messaggio comprensibile.
3. Rimuovere il collegamento da un ambito. Atteso: il materiale resta utilizzabile nell'altro. Sostituire il file nel collegamento originale: gli altri collegamenti continuano a riferirsi al vecchio asset immutabile.
4. Ripetere con una versione pubblicata e con un editor privo del permesso materiali; nessuna modifica/riuso deve essere consentita. Verificare che il corso pubblicato e lo storico assegnato restino invariati.
5. Eseguire l'attività Scheduler di pulizia: il blob condiviso deve restare finché un collegamento lo referenzia e diventare eleggibile solo quando tutte le associazioni sono state rimosse e sono trascorse 24 ore.

PR [#217](https://github.com/jeckodevelopment/jd_learn/pull/217) integrata nel commit `abf82a4053918f823726c84a156d295652c7b72c`; CI standard 4/4 nel [run 42983654823](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823). La fixture Joomla eliminabile verifica il riuso tramite form amministrativo, il riferimento condiviso allo stesso asset, il rifiuto del secondo collegamento duplicato e il blocco sulle versioni pubblicate. Pacchetto della PR #217: [JD-Learn-0.41.64-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823/artifacts/13838677604). Il wrapper va estratto una volta. Collaudo manuale Joomla ancora da eseguire.

## IMP-06 — pulizia programmata dei materiali orfani (0.41.54-dev)

Riferimenti: MAT-02/03/05/06, DAT-04, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. La guida è in [MATERIALS.md](MATERIALS.md). Sul sito di prova verificare che il plugin Task - JD Learn sia abilitato.

1. Caricare un allegato valido nella bozza e sostituirlo con un nuovo file. Verificare che il contenuto precedente non venga sovrascritto; entrambi i file devono essere privati e serviti solo tramite i controlli Joomla.
2. Creare da Sistema → Attività pianificate un’attività “JD Learn: pulisci i file materiali orfani”, limite `100`, e avviarla manualmente. Atteso: record e file ancora usati da corsi/lezioni restano disponibili.
3. Verificare nel log dell’attività conteggio rimozioni, errori ed eventuale lavoro residuo; nessuna attività viene creata automaticamente dall’upgrade.

La CI standard 4/4 della PR [#202](https://github.com/jeckodevelopment/jd_learn/pull/202) nel [run 42886192087](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087) genera un asset fittizio non referenziato più vecchio di 24 ore e verifica la rimozione del record e del blob tramite la routine nativa Scheduler, mantenendo intatti gli allegati collegati. Pacchetto: [JD-Learn-0.41.54-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42886192087/artifacts/13797460306).

**Esito sito Joomla:** Non eseguito.

## IMP-05 — pulizia opportunistica delle sessioni video (0.41.53-dev)

Riferimenti: VID-03/04, PBASE-01/04; AC-25, AC-27–31, AC-91, AC-95, AC-97–103. Su Joomla 6 di prova usare una lezione video disponibile, un’iscrizione autorizzata e uno dei quattro provider già configurati.

1. Aprire il video e avviare la riproduzione; verificare che si crei la sessione e che il salvataggio posizione funzioni.
2. Con una sessione precedente scaduta, aprire di nuovo il video: il player deve avviarsi e la riga scaduta deve essere rimossa; le sessioni attive e i progressi esistenti devono restare invariati.
3. Ripetere con provider esterno e HTML5 locale, con accesso autorizzato e poi revocato: il purge avviene solo dopo l’autorizzazione e la revoca continua a impedire l’avvio.
4. Verificare che nessuna sessione attiva venga eliminata e che una nuova sessione non attribuisca credito finché non arrivano intervalli validi.

La suite automatica copre una riga scaduta e la cancellazione; il limite massimo 250 è verificato nel codice. La pulizia è opportunistica, quindi l’eliminazione completa in assenza di nuovi avvii rimane un’attività periodica futura.

**CI:** PR [#198] integrata; standard 4/4 superata nel [run 42883180012](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012). [Pacchetto installabile JD-Learn-0.41.53-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42883180012/artifacts/13794578948).

**Esito sito Joomla:** Non eseguito.
## IMP-07 — URL SEF con alias per corsi, categorie e percorsi (0.41.58-dev, PR #208 integrata)

PR [#208](https://github.com/jeckodevelopment/jd_learn/pull/208), milestone **1.0.0**, integrata con squash nel commit `6b92612254c060c1ef296c0a03661442871ad1cc`. CI standard 4/4 nel [run 42910392500](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500). Pacchetto [JD-Learn-0.41.58-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/42910392500/artifacts/13806778546), wrapper SHA-256 `773fb7cd4e200df599ffd1b858cc5ef60d013c8823780b625024afbb4acec8cb`, ZIP Joomla SHA-256 `7a4d52cdbbd708b523d6d05f29f0b937fcf5094019a6c9e70ab443bd7a28ade3`, disponibile fino al 24 novembre 2026. Eseguire sul sito Joomla di prova e registrare qui l’esito; i percorsi numerici precedenti devono restare funzionanti.

1. Installare il pacchetto candidato su Joomla di prova e predisporre corsi, categorie e percorsi pubblicati con alias parlanti; includere due record con alias uguale in lingue/categorie diverse.
2. Attivare SEF e URL rewriting. Aprire catalogo, categoria, scheda corso e percorso; verificare che i collegamenti usino segmenti ID-alias (per esempio `42-alias-corso`), che ogni pagina corrisponda al record corretto e che non appaiano ID di un altro tipo di contenuto.
3. Aprire gli stessi record con i vecchi URL numerici; verificare che continuino a rispondere e che le pagine pubbliche espongano come canonical l’URL SEF con alias.
4. Cambiare lingua con associazioni valide e verificare che il link porti al record tradotto e al suo alias. Con un’associazione assente, verificare il fallback lingua già previsto.
5. Disattivare SEF e verificare URL query tradizionali, navigazione e menu nativi. Riattivare SEF e verificare breadcrumb, paginazione/filtri e link di ritorno.
6. Modificare un alias nel gestore Joomla; verificare che il nuovo alias generi il link successivo e che il vecchio segmento numerico continui a identificare il record.
## IMP-07 — vista elenco del catalogo (0.41.61-dev, PR #211 integrata)

Riferimenti: CAT-01, AC-41 e AC-85. Installare il candidato su Joomla 6 di prova; usare corsi pubblici e una categoria visibile.

1. Aprire il catalogo e verificare che la selezione Visualizzazione proponga Griglia ed Elenco, con etichette corrette in italiano e inglese.
2. Selezionare Elenco e applicare. Atteso: le schede sono disposte in righe, la scelta resta selezionata e l’URL conserva gli altri filtri attivi.
3. Combinare Elenco con ricerca, tag, difficoltà, durata e ordinamento; i risultati e la paginazione devono rimanere coerenti con i filtri.
4. Ripetere nella pagina di una categoria con corsi diretti e discendenti. La vista scelta non deve rimuovere il contesto categoria.
5. Restringere la finestra sotto 640 px: titolo, link e sommario devono rimanere leggibili senza overflow orizzontale. Verificare la navigazione da tastiera dei controlli.
6. Inviare `filter[layout]=unexpected`: deve essere usata la griglia predefinita. Verificare che nessun corso non autorizzato appaia in entrambe le viste.

**Esito sito Joomla:** Non eseguito.


## IMP-05 — consegna HTML5 locale compatibile con Apache/LiteSpeed (0.41.71-dev, PR #226)

**Stato:** PR [#226](https://github.com/jeckodevelopment/jd_learn/pull/226) integrata nel merge `1b889ee550a23700071488930b50fbcb0081ad74`. CI 4/4 nel [run 43079973855](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855); pacchetto [JD-Learn-0.41.71-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43079973855/artifacts/13885130510), ZIP SHA-256 `3d9dcca6c943da364e6c6f83d6ac45b44f51a8a7a5d34f532da40150b9e21c8f`, wrapper `4ecf79e8fa6d64cc0171412dbc56b4ea26e7ee0f4a48544348fb4a6817e34d33`. Lo streaming protetto usa PHP a blocchi e non richiede Nginx, moduli aggiuntivi o regole server. I vecchi valori `x_accel` sono serviti via PHP. La capacità concorrente non è certificata senza benchmark.

**Comportamenti da testare in Joomla**

1. Aggiorna il componente dal pacchetto precedente su un sito Apache/LiteSpeed standard, abilita il plugin HTML5 e configura directory privata, ffprobe e streaming PHP. Non aggiungere direttive o moduli web server.
2. Carica un MP4 H.264 valido più grande di 64 MiB (entro il limite configurato e i limiti PHP); pubblica la lezione e avviala da un account iscritto. Verifica riproduzione, richieste Range e assenza di URL/percorso pubblico.
3. Prova guest, utente senza iscrizione, revoca del livello e sospensione dell’iscrizione: nessuno deve ricevere byte dopo il diniego. Verifica GET/HEAD, byte-range, Range non valido e intestazioni private/no-store.
4. Su un sito aggiornato con vecchio valore `x_accel`, verifica che l’anteprima e la fruizione continuino tramite PHP protetto e che non venga emesso `X-Accel-Redirect`.
5. Disabilita la consegna e verifica che pubblicazione/streaming siano bloccati. Registra versione Joomla/PHP, web server e dimensione file.
## STAB-04 — label compatta per i campi lezione (0.41.79-dev, PR #241 integrata)

**Stato:** PR [#241](https://github.com/jeckodevelopment/jd_learn/pull/241) integrata; CI 4/4 nel [run 43116473760](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760). Pacchetto [JD-Learn-0.41.79-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43116473760/artifacts/13899451043), SHA-256 interno `4c54233651a85f33594fcde84d32f7f31d51bcc82d03f63fead3e189fa270843`, wrapper `a33d99e3742305adfe0964f8e878b8804092d4bf3caa7e125f91d5bfeef02eaa` (estrarre una volta; scade il 25 novembre 2026). Accettazione Joomla pendente.

**Issue:** [#173](https://github.com/jeckodevelopment/jd_learn/issues/173). La label lunga è sostituita con “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, sia nel catalogo componente sia in quello di sistema. Il link resta associato al contesto Joomla `com_jlms.lesson` e mantiene i controlli ACL.

**Comportamenti da testare in Joomla**

1. Installa il pacchetto 0.41.79-dev su un sito Joomla di prova. Apri JD Learn → Programma e verifica che il comando toolbar mostri “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, senza taglio o sovrapposizione a desktop e mobile.
2. Attiva il comando e verifica che apra **Campi aggiuntivi** filtrati per il contesto `com_jlms.lesson`.
3. Accedi con un utente senza `core.manage` su `com_fields`: il collegamento non deve apparire; un URL diretto non deve concedere accesso.
4. Verifica tastiera: il comando ha nome accessibile coerente con la label, riceve focus visibile e si attiva con Invio/Spazio.

**Esito sito Joomla:** Non eseguito.

---

## STAB-01 — traduzione del menu Percorsi formativi (0.41.78-dev), PR #238 integrata

**Stato:** PR [#238](https://github.com/jeckodevelopment/jd_learn/pull/238) integrata; CI 4/4 nel [run 43113528500](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500). Pacchetto [JD-Learn-0.41.78-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43113528500/artifacts/13897377687), SHA-256 interno `19931c7606ae274ca12291e713bddc09a8c946bed3f83f7d3cad1b48db7b53f1`, wrapper `6a029d5283ef897d62869bc8fb88eaf193eb966bab3d61424a04951cae8c9ec5` (estrarre una volta; scadenza 25 novembre 2026). Accettazione manuale Joomla pendente.

**Issue:** [#150](https://github.com/jeckodevelopment/jd_learn/issues/150). La voce amministrativa usava una stringa di sistema non tradotta, anche se il titolo della vista Percorsi era già localizzato. La patch aggiunge `COM_JLMS_PATHWAYS` ai cataloghi di sistema italiano e inglese. L’ordine della dashboard e il riepilogo provider dell’issue #149 sono già implementati; verificarli nello stesso passaggio di accettazione.

**Comportamenti da testare in Joomla**

1. Installa il pacchetto 0.41.78-dev su un sito Joomla usa-e-getta; accedi come Super User con lingua amministrativa italiana e apri il menu JD Learn. La voce deve apparire come “Percorsi formativi”, senza chiavi `JD_COM_JLMS_*` visibili.
2. Apri la voce: deve portare alla gestione Percorsi e il titolo pagina deve essere tradotto.
3. Imposta inglese come lingua amministrativa e ripeti: la voce deve essere “Learning pathways”.
4. Verifica l’ordine dashboard: Corsi, Percorsi formativi, Categorie corsi, Categorie percorsi, Banca domande, Docenti, Campi aggiuntivi, Plugin video (solo le voci consentite dai permessi dell’utente).
5. Espandi lo stato provider: ciascuno dei quattro plugin deve essere indicato come configurato, non configurato, disattivato o mancante secondo il suo stato reale; non devono comparire segreti o impostazioni sensibili.

**Esito sito Joomla:** Non eseguito.

---


## STAB-05 — un solo accesso al Programma nell’editor corso (0.41.80-dev, issue #200)

**Stato:** PR [#244](https://github.com/jeckodevelopment/jd_learn/pull/244) integrata nel commit `ce3ee6f9d62c2483a3a826a34c0a0ba9b0d3bf62`. CI standard 4/4 nel [run 43121346817](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817); pacchetto [JD-Learn-0.41.80-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43121346817/artifacts/13900597900), SHA-256 interno `5faefa65f5dce76823cb8dd7aa378ebe1801ad720bc740dc7ebe228bdb19132a`, wrapper `c82f72b0c1728bf7bda492c368e5581a3768133ad1ce06e6c9c3f9e50c65b2c7` (estrarre una volta; scade il 25 novembre 2026). Accettazione manuale Joomla non eseguita.

**Comportamenti da testare in Joomla**

1. Installare il pacchetto 0.41.80-dev su un sito Joomla di prova e accedere con un utente autorizzato a modificare corsi.
2. Aprire un corso esistente in **Componenti → JD Learn → Corsi → Modifica**. Verificare che il contenuto non mostri più il secondo pulsante “Programma didattico” sotto la toolbar e che la toolbar mostri una sola azione **Salva e apri programma**.
3. Aprire la scheda **Programma**: il riepilogo moduli e il comando devono restare disponibili. Usare la toolbar e verificare che salvataggio e apertura portino al programma dello stesso corso.
4. Aprire un nuovo corso: l’avviso che richiede il primo salvataggio deve restare visibile; dopo il salvataggio, la toolbar deve aprire il programma senza link duplicati.
5. Ripetere in italiano e inglese e con un utente in sola lettura; nessun comando di modifica non autorizzato deve comparire.

**Esito sito Joomla:** Non eseguito.


## STAB-05 — titoli non duplicati in Dashboard e Percorsi formativi (0.41.81-dev, issue #199)

**Stato:** PR [#247](https://github.com/jeckodevelopment/jd_learn/pull/247) integrata nel commit `a8734dd606cc579077312dfecd9cd66773934804`. CI standard 4/4 nel [run 43129077496](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496); pacchetto [JD-Learn-0.41.81-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43129077496/artifacts/13904467823), SHA-256 interno `3ef211e6ac7713d381fb9fabb106ee40881eca72a9b3bbff5465fd300bdcc573`, wrapper `1924e7a641dbb539e3b5833d6b57a737c2389edda2645b8c6d6688e4699df3ee` (estrarre una volta; scade il 26 novembre 2026). Accettazione manuale Joomla non eseguita.

**Comportamenti da testare in Joomla**

1. Installare JD Learn 0.41.81-dev su un sito Joomla di prova e accedere con un utente autorizzato a `core.manage` su JD Learn.
2. Aprire **Componenti → JD Learn**. Verificare che “JD Learn” compaia una sola volta come titolo, nella barra blu, e che introduzione, scorciatoie operative e stato dei quattro provider restino visibili.
3. Aprire **Percorsi formativi** dal menu laterale o dalla dashboard. Verificare che il titolo compaia una sola volta nella barra blu, senza intestazione ripetuta sopra la tabella; tabella, intestazioni colonne e paginazione restano visibili.
4. Usare uno screen reader o l’albero accessibilità del browser: il titolo Joomla deve rimanere un heading principale; la tabella Percorsi deve conservare una caption accessibile.
5. Ripetere in italiano e inglese e con un utente senza `jlms.pathways.manage`: la vista percorsi e il relativo collegamento devono continuare a essere negati/nascosti.

**Esito sito Joomla:** Non eseguito.


## IMP-09 — Verifica FTPS reale (12 novembre 2026)

**Evidenza automatica ambiente remoto:** [GitHub Actions #43131327572](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131327572), branch `main`, commit `dae0751b6828f3dcd6bc3b9a20fdd72e2a94c621`, **SUCCESS**. Il log contiene `FTPS LIVE PROBE OK`: handshake FTPS esplicito con verifica certificato/pin configurato, login, `PROT P`, upload, lettura di ritorno, rename-overwrite di due file usa-e-getta e cancellazione. `jdlearn.xml` **non modificato**. I test offline risultano 5/5 + 3/3 + 7/7.

**Limite evidenza:** il workflow non esegue pubblicazione di una versione reale, non verifica la raggiungibilità HTTP del feed e non collauda l'aggiornamento Joomla end-to-end (IMP-10). Conferma indipendente dell'impronta del certificato privato con Host.it ancora raccomandata. **Esito collaudo Joomla:** Non eseguito.


## REL-01–03 — Dry-run del rilascio 1.0.0 (12 novembre 2026)

**Esito automatico:** [GitHub Actions 43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497), commit `67505908b2a7729429f345a420b534f13387070a`, **SUCCESS**. Generati `pkg_jlms-1.0.0.zip` (569587 byte; SHA-256 `8fd033a56ca4a424d6d754d1f9e13dfc3e78f3b40d64e49f7ad8b01b178b41df`), `jdlearn.xml` e `RELEASE_SHA256SUMS.txt`; artefatto [13906260145](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497/artifacts/13906260145). Job produzione **SKIPPED**. Feed XML generato soltanto localmente: il suo URL di download della v1.0.0 non è ancora disponibile pubblicamente.

**Comportamenti ancora da testare in Joomla (IMP-10):** installazione pulita del candidato su un sito di prova; upgrade da versione precedente mantenendo iscrizioni e progressi; compatibilità con Apache/LiteSpeed standard senza configurazioni Nginx; verifiche finali del catalogo, corsi, lezioni, player ed enrollment; updater e feed pubblico solo dopo autorizzazione esplicita al rilascio. **Esito collaudo reale Joomla:** Non eseguito. Non pubblicare una release stabile sulla base del solo dry-run.


### IMP-10 — Conferma manuale installazione candidato 1.0.0 (12 novembre 2026)

Il richiedente ha confermato in chat di aver **scaricato e installato il componente** dal candidato 1.0.0 generato dal workflow [43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497) e che il componente **funziona** sul sito Joomla di prova. Evidenza **dichiarata dall'utente**, non accompagnata da screenshot o verbale di casi dettagliati. Considerare **superato il solo smoke test manuale di installazione e avvio generale**.

**Non ancora confermati dall'utente:** aggiornamento da precedente versione con dati esistenti; preservazione corsi, iscrizioni e progressi; verifica approfondita dei provider/player, autorizzazioni e delle funzionalità operative; feed pubblico e aggiornamento automatico Joomla. **IMP-10 resta parziale**, nessun rilascio pubblico autorizzato. Il valore globale documentato resta 78% finché il criterio di misurazione non viene aggiornato sulla base di evidenze di tutti i requisiti.


## IMP-05 — capacità minime per il tracciamento video (0.51.0-dev, PR #278)

Riferimenti: PRO-05, VID-03; AC-05, AC-27, AC-30, AC-44, AC-91. PR [#278](https://github.com/jeckodevelopment/jd_learn/pull/278), integrata nel commit `62af5dbcbc6bd2300c67bc149c04bccaa0203aec`; CI standard 4/4 nel [run 43246533928](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928). Pacchetto [0.51.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43246533928/artifacts/13957416321), SHA-256 `b6b01e432ddd660c32f25dfa4f611bfab069edcfa92977349f2ef16dbff0fd2f`; digest wrapper `2da0bd7ce7d03b58227ab24b4e7c84060143d41d5841aa3b54449aaa06a707bc`, scadenza 26 novembre 2026. Il controllo richiede posizione, durata e seek per il corso in apprendimento tracciato; velocità e sottotitoli/caption restano opzionali. Il server ripete la verifica sulle capabilities del descrittore prima di avviare o rinnovare il tracciamento.

**Comportamenti da testare in Joomla**

1. Installare il pacchetto candidato 0.51.0-dev in Joomla 6 di prova e configurare, uno alla volta, YouTube, Vimeo, Cloudflare Stream e HTML5 con sorgenti valide.
2. Per ciascun provider, creare una lezione video richiesta in un corso in modalità apprendimento, verificare la durata, pubblicare il corso e avviare la lezione. Verificare che il player si apra e che posizione, durata e seek siano disponibili; completare una parte del video e controllare che la copertura venga registrata.
3. Provare un corso in modalità consultazione con una lezione video: la riproduzione deve restare disponibile senza sessione né scritture di progresso.
4. In un ambiente di test con adapter che dichiara o restituisce capabilities incomplete, verificare che la pubblicazione oppure l’avvio del tracciamento venga rifiutato e che nessun progresso sia attribuito. L’assenza di velocità o caption, da sola, non deve bloccare la pubblicazione.

**Esito automatico locale:** build pacchetto e check strutturali superati; test player JavaScript superati. CI standard 4/4, inclusi test PHP e integrazione Joomla; il collaudo manuale live resta pendente. **Esito sito Joomla:** Non eseguito.


## IMP-04 — ripresa video tra sessioni concorrenti (0.52.0-dev, PR #280)

Requisiti: PRO-06, STU-03; AC-07, AC-37, AC-81, AC-91. PR [#280](https://github.com/jeckodevelopment/jd_learn/pull/280), integrata nel commit `dcc49eb0e09b0ba6f2424e6eaaa973b837b3e4f4`; CI standard 4/4 nel [run 43250267681](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681). Pacchetto [0.52.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43250267681/artifacts/13958934116), SHA-256 `f5c0986ae59361de77c67ebae9ed9d91583b12f38e835a4a12a9cedfebfb9216`; wrapper `3f528639d38a2c61d39fc5713f6750f7dfadb5bf5c6220d810174cddaf8b89e9`, scadenza 26 novembre 2026. Una sessione video avviata da una posizione obsoleta può continuare ad aggiungere copertura, ma non deve sovrascrivere la posizione più recente né riacquisire il diritto di farlo nei batch successivi.

**Comportamenti da testare in Joomla**

1. Accedere con lo stesso account studente da due browser o dispositivi e avviare la stessa lezione video in entrambi.
2. Nel browser A avanzare fino a un punto riconoscibile e attendere il salvataggio; annotare la posizione confermata.
3. Lasciare aperto il browser B dalla posizione precedente e inviare almeno due salvataggi successivi. La copertura può unirsi, ma la posizione di ripresa non deve retrocedere o sostituire quella più recente.
4. Ricaricare la lezione nel browser B o aprirla in una nuova sessione: il player deve riprendere dalla posizione più recente confermata. Il completamento e la percentuale devono rimanere idempotenti.
5. Ripetere con due sessioni sulla stessa lezione e verificare che chiudere una sessione non chiuda né invalidi l’altra.

La regressione automatica d’integrazione in `tests/joomla-video.php` copre due batch consecutivi da una sessione obsoleta ed è passata nella CI standard 4/4. Accettazione live Joomla: Non eseguita.

## IMP-04 — replay del batch video obsoleto (PR #282, 0.53.0-dev)

**Stato automatico:** superato nella CI standard 4/4 [run 43252689828](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828), incluso test Joomla/PHP di replay idempotente. Merge `66a59d0a5e4e60943b0d1487ddce4c95c64f97b0`. **Accettazione manuale Joomla: da eseguire.** Pacchetto [JD-Learn-0.53.0-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43252689828/artifacts/13959653251), SHA-256 ZIP `7898ba9c5dc340aaec1b9c260ef729bc4314cde4cd31bc44c2411efacca0616e`; wrapper `a2dc70a32891b12c378f0920c75c5fa4b9da3c47e46fc0efd442b898051235fa`, scade il 26 novembre 2026.

1. Sul sito Joomla di prova, aprire la stessa iscrizione video in due sessioni/browser: A e B.
2. Avanzare e salvare da A un punto di ripresa più recente. Da B inviare un batch di progresso basato sulla revisione precedente; verificare che la risposta segnali il conflitto e il resume salvato resti quello più recente.
3. Ripetere da B la stessa identica richiesta. Atteso: risposta idempotente con `replay=true` e `resume_conflict=true`; nessuna seconda applicazione di copertura, completamento o posizione di ripresa.
4. Inviare da B un ulteriore batch con la revisione obsoleta. Atteso: conflitto ancora segnalato e nessuna regressione del resume.
5. Verificare che il progresso valido già registrato da entrambe le sessioni venga mantenuto e che il corso non risulti completato due volte.

## IMP-04 / AUT-03 — avviso durata video in sola consultazione (0.54.0-dev)

Verificare su Joomla di prova il riepilogo **Verifica prima della pubblicazione** in una versione bozza con modalità **Sola consultazione** e una lezione video senza durata rilevata/confermata. Atteso: avviso `COM_JLMS_READINESS_VIDEO_DURATION_UNCONFIRMED`, nessun blocco per la sola durata e possibilità di pubblicare se gli altri controlli passano. Aggiungere poi una durata confermata valida e verificare che l’avviso scompaia. Ripetere in modalità **Apprendimento**: l’assenza di durata resta un blocco `COM_JLMS_VIDEO_EDITOR_DURATION_REQUIRED` e non viene duplicata come avviso.


## IMP-09-CERT — attestati PDF essenziali (CERT-A/B integrate)

**Comportamenti da testare in Joomla (da eseguire dopo le PR runtime):**

1. Disabilitare e abilitare il rilascio attestati a livello globale e per corso; completare un corso abilitato e uno disabilitato, verificando l'emissione solo dove autorizzata.
2. Simulare corso incompleto, anteprima editoriale e sola consultazione: nessun attestato deve essere emesso.
3. Ripetere evento e richieste concorrenti di completamento: il codice identificativo e il numero attestati non devono duplicarsi.
4. Personalizzare logo/intestazione e generare PDF con nomi e titoli lunghi, caratteri accentati, lingua italiana e inglese. Verificare studente, corso, data, codice e leggibilità.
5. Studente A scarica il proprio attestato; studente B e visitatore anonimo non possono scaricarlo nemmeno conoscendo l'URL. Verificare risposta e assenza indicizzazione di PDF privati.
6. Verificare elenco e download dal backend per amministratore autorizzato; negare accessi non consentiti da ACL a dettagli e dati personali.
7. Eseguire upgrade e ripristino dati: attestati e codici restano invariati, senza rigenerazioni accidentali.
8. Verificare mail di disponibilità tramite modello Joomla con invio, errore SMTP, retry e deduplica; l'attestato persiste quando la posta non funziona.

**Stato:** prove pianificate, nessuna accettazione manuale registrata. Vedere [CERTIFICATES_1.0.0.md](CERTIFICATES_1.0.0.md).


## IMP-09-MAIL-B — sospensione e riattivazione (PR #297, 0.56.3-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto; in **Sistema → Modelli email** verificare i template `com_jlms.enrollment.suspended` e `com_jlms.enrollment.reactivated`. Personalizzare testo e traduzione, aggiornare e verificare che restino invariati.
2. Configurare SMTP di prova e attività pianificata JD Learn. Sospendere un’iscrizione attiva: deve arrivare un solo messaggio di sospensione; rieseguire l’attività senza nuove transizioni e verificare che non partano duplicati.
3. Riattivare l’iscrizione: deve arrivare soltanto il template di riattivazione. Verificare che progressi e storico quiz restino invariati.
4. Verificare preferenze globali e override corso separatamente per sospensione e riattivazione: globale disabilitato con corso ereditato (nessun invio), override abilita (invio), override disabilita con globale abilitato (nessun invio).
5. Verificare la gestione di errore di accodamento: transazione di stato/audit/coda coerente e rollback; ripetere la stessa transizione idempotente senza duplicati.

PR [#297](https://github.com/jeckodevelopment/jd_learn/pull/297) integrata, CI standard 4/4 nel [run 43307181190](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190). Pacchetto [JD-Learn-0.56.3-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43307181190/artifacts/13983759363), SHA-256 ZIP `8c3d5e0de6935f67cf93e1b7caf3a27e7d10d61d26c20450f5d9ace1cc6cdee0`. La suite automatizzata non sostituisce le prove manuali Joomla/SMTP sopra: registrare qui ambiente ed esito.


## IMP-09-MAIL-A — conferma di iscrizione amministrativa (candidata 0.56.4-dev)

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto candidato su Joomla di prova con SMTP di test e attività pianificata JD Learn attiva; verificare che il template `com_jlms.enrollment.confirmed` esista in Sistema → Modelli email e che le personalizzazioni esistenti siano preservate dall’upgrade.
2. Come amministratore autorizzato, iscrivere manualmente uno studente valido a un corso pubblicato in cui abbia accesso ai contenuti. Atteso: l’iscrizione viene creata e, se le preferenze globali/corso sono abilitate, entra in coda una sola email di conferma con nome, corso e URL aula corretti.
3. Ripetere la stessa richiesta idempotente o ricaricare la schermata dopo il salvataggio. Atteso: un’unica iscrizione e nessun messaggio duplicato.
4. Disabilitare l’evento globalmente; verificare nessun messaggio. Riabilitarlo e impostare l’override del corso su disabilitato; verificare ancora nessun messaggio. Con globale disabilitato e override esplicitamente abilitato, verificare un solo messaggio.
5. Eseguire un preassegnamento senza livello di accesso al contenuto. Atteso: l’assegnazione resta distinta e lo studente non riceve una falsa conferma di accesso. Se l’utente possiede già accesso corrente e l’evento è abilitato, la conferma può essere accodata.
6. Simulare errore SMTP: l’iscrizione deve restare valida e la coda deve applicare retry secondo le regole esistenti; controllare lingua e dati email nel log, senza esporre dati personali eccedenti.

**Esito sito Joomla:** Non eseguito.
# CERT-A — Emissione persistente degli attestati (0.56.5-dev)

La CI deve verificare installazione/upgrade dello schema, preferenza disabilitata per default, override per corso e un solo record attestato dopo il completamento reale nelle modalità testo/documento/audio, quiz (inclusa revisione), video e completamento idempotente. Verificare che consultazione, corso non completato e corso senza attestati abilitati non creino record; che il template e lo snapshot persistano i valori al momento del completamento.

**Collaudo manuale Joomla da eseguire:** abilitare gli attestati globalmente e poi sovrascrivere la preferenza in due corsi; completare un corso Apprendimento e verificare nel database di prova una sola riga per iscrizione/versione con nome, titolo/versione e data corretti; ripetere l'ultima richiesta e verificare che il record non cambi; completare un corso in sola consultazione e verificare che non venga emesso nulla. Il PDF, l'area studente e la vista amministrativa sono fuori da CERT-A e verranno collaudati nelle PR successive.


# CERT-B — PDF personalizzabile e download protetto (PR #302 integrata; 0.56.6-dev)

**Prerequisiti:** installare la candidata su Joomla di prova con storage materiali privato configurato; predisporre un JPEG locale nella cartella media consentita; attivare attestati globali e per il corso di test; usare uno studente A completabile, uno studente B e un account anonimo.

**Collaudo manuale Joomla da eseguire**

1. In Configurazione componente → Attestati impostare intestazione, sottotitolo, piè pagina, colore e logo JPEG locale. Completare il corso come studente A. Atteso: nel pannello studente compare un attestato e il PDF scaricato riporta nome, titolo del corso, data, codice, testi e logo configurati.
2. Aprire il PDF in italiano e con account inglese; verificare testo e date/localizzazione, nomi accentati, nomi lunghi e titolo corso lungo. Atteso: una pagina A4 orizzontale leggibile, contenuti non interpretati come markup e file PDF valido.
3. Rimuovere o impostare un logo PNG/URL remoto e avviare una nuova emissione di prova. Atteso: il renderer rifiuta il formato/percorso non supportato senza esporre file e senza rompere l’aula; ripristinare poi un JPEG valido.
4. Accedere all’URL di download come A, come B, anonimo e con un enrollment_id inesistente. Atteso: A scarica il proprio PDF; B, anonimo e ID inesistente non ricevono il documento di A. Il percorso non è enumerabile tramite seriale o URL asset.
5. Verificare intestazioni `Content-Type: application/pdf`, `Cache-Control: private, no-store`, `X-Robots-Tag: noindex, nofollow`; ripetere con HEAD. Atteso: HEAD non invia il corpo.
6. Ripetere il download e confrontare seriale/contenuti: lo stesso attestato resta identico. Controllare che il blob risieda nello storage privato esterno alla web root, con permessi restrittivi e che l’upgrade non lo elimini.

**Esito sito Joomla:** Non eseguito. Questa procedura non copre ancora vista amministrativa, audit né backup/ripristino, previsti nelle PR successive/IMP-10.


**Verifica automatica PR #302:** CI standard 4/4 superata nel [run 1023](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842); il test PDF e la lint PHP sono passati. L’accettazione manuale descritta sopra resta pendente. Pacchetto [JD-Learn-0.56.6-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43410407842/artifacts/14029108411), ZIP interno SHA-256 `73a8cb6de3e4c2f636b2a2b0702799864e3644dcc3c463913e628a4c4c4056d1`, wrapper SHA-256 `313e4a2b18adc2d1f431114eed9b19ff593ec5b154dc0784481e094546a80a92`.


# CERT-C — gestione amministrativa attestati (PR #304 integrata; 0.56.7-dev)

**Prerequisiti:** installare/aggiornare il pacchetto candidato su Joomla di prova; abilitare Attestati; completare un corso come studente; creare due account amministrativi, uno con `core.manage` e `jlms.certificates.manage`, uno senza il permesso certificati.

1. Da JD Learn > Dashboard, l’account autorizzato vede la scorciatoia **Attestati**; l’account senza il permesso non la vede. Aprire elenco e dettaglio del certificato e verificare studente, corso, codice, data e dati dello snapshot.
2. Cercare per nome studente, titolo corso e codice; applicare separatamente i filtri Disponibile, In attesa del PDF e Revocato; verificare risultati, paginazione e stato.
3. Con l’account senza `jlms.certificates.manage`, aprire direttamente elenco, dettaglio e URL `task=certificate.download`: ogni richiesta deve essere negata senza esporre dati o PDF. Provare anche da utente non autenticato.
4. Con l’account autorizzato scaricare il PDF dal dettaglio: il file deve corrispondere al codice e ai dati congelati del certificato. Se è presente un certificato già revocato, verificare il filtro/stato e il diniego download; la revoca non è introdotta da CERT-C.
5. Nel dettaglio verificare gli eventi di consultazione/download con ID amministratore e data UTC. Confermare che l’audit non includa email o altri dati personali non necessari.
6. Aggiornare da 0.56.6-dev e verificare che certificati e asset esistenti restino leggibili e non vengano rigenerati o modificati.

**Verifica automatica PR #304:** CI standard 4/4 superata nel [run 1031](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364); la suite ha superato database, installazione/upgrade e smoke test Joomla su MySQL 8.4 e MariaDB 10.6. Gli scenari manuali Joomla restano pendenti. Pacchetto [JD-Learn-0.56.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364/artifacts/14034621388), SHA-256 ZIP interno `28c462f2eae0b795cf02574408ac9bfa830e8e4a95bd5ad634bc011700e14bc4`, wrapper `f0467b1a4e96ab784283090e48377d400fbb69f944579ffd444934cdc41175e2`.


# CERT-D — notifica disponibilità attestato (PR #307 integrata; 0.56.8-dev)

1. Aggiornare il componente su Joomla di prova, abilitare attestati e notifiche email globali; verificare che il template `com_jlms.certificate.available` compaia in **Sistema → Modelli email** con oggetto, HTML/testo e tag `NAME`, `COURSE`, `SITENAME`, `CERTIFICATE_URL`. Personalizzare il template e verificare che l’upgrade successivo lo preservi.
2. Completare un corso idoneo con utenti che hanno lingua profilo IT ed EN; verificare una sola riga in outbox per ciascun documento e template lingua corretto. Eseguire il task `JD Learn: send queued emails` e verificare contenuti, link autenticato alla dashboard e accesso al download.
3. Ripetere il completamento/replay: non devono essere create ulteriori notifiche. Disabilitare la preferenza globale oppure l’override per corso e verificare che l’email non venga accodata.
4. Provare un errore temporaneo SMTP: il completamento e l’attestato devono restare validi, la coda deve pianificare il retry; dopo il ripristino eseguire il task e verificare la consegna.
5. Eseguire CI e smoke test Joomla automatici; l’accettazione manuale sul sito di prova resta a carico del collaudo di progetto.


## IMP-09-MAIL-C / PR #313 — comunicazione operativa per corso (0.56.9-dev)

**PR #313 integrata; CI 4/4 superata nel run [43445911912](https://github.com/jeckodevelopment/jd_learn/actions/runs/43445911912). Collaudo manuale Joomla/SMTP pendente; la CI non equivale all'accettazione.** Preparare Joomla 6, mailer di prova, Scheduler JD Learn attivo, corso con almeno tre iscritti attivi, uno sospeso e un docente assegnato.

1. Dal roster aprire “Comunica agli iscritti”, modificare oggetto/testo e selezionare due iscritti. Verificare anteprima, conteggio e destinatari; email e indirizzi non devono apparire nell'interfaccia.
2. Inviare il test: deve riceverlo solo l'operatore autenticato; gli iscritti non ricevono il test.
3. Confermare e lanciare il task Scheduler più volte. Solo i selezionati ancora attivi e non bloccati ricevono un messaggio ciascuno; verificare corpo HTML e testo, lingua Joomla e template com_jlms.course.message in Sistema → Modelli email.
4. Ripetere/replay della conferma e inviare POST alterati con course/enrollment ID o hash vecchi: nessun duplicato, l'alterazione è respinta e la preview diventa da aggiornare se è cambiato il roster.
5. Prima dell'elaborazione revocare l'ACL del docente assegnato e provare con un docente non assegnato. Job revocato interrotto; roster e pagina comunicazione negati al docente non assegnato.
6. Provare switch globale e override corso (eredita/abilita/disabilita), mailer non disponibile e ripresa del Scheduler. Verificare log azioni, conteggi, assenza del testo e degli indirizzi nei dati di audit/job e pulizia del payload a esito terminale.


## MAIL-11.1 / PR #315 — template Joomla annuncio nuovo corso (0.56.10-dev)

PR integrata; CI 4/4 superata nel [run 43450891869](https://github.com/jeckodevelopment/jd_learn/actions/runs/43450891869). **Nessun invio è previsto da questa PR.** Collaudo manuale Joomla pendente. Sul Joomla 6 di prova:

1. Installare o aggiornare JD Learn, aprire **Sistema → Modelli email** e cercare `com_jlms.course.published`.
2. Verificare tag disponibili: `NAME`, `COURSE_TITLE`, `COURSE_TITLE_HTML`, `COURSE_IMAGE_HTML`, `COURSE_INTRO_TEXT`, `COURSE_INTRO_HTML`, `COURSE_LESSONS_TEXT`, `COURSE_LESSONS_HTML`, `COURSE_URL`, `SITE_NAME`, `SITE_NAME_HTML`, `UNSUBSCRIBE_URL`.
3. Verificare testo e HTML: immagine opzionale, titolo, introduzione, lezioni pubbliche ordinate, CTA e revoca; plain text leggibile con URL e lezioni.
4. Tradurre IT/EN con la funzione Joomla, personalizzare i corpi e aggiornare il componente: le personalizzazioni devono restare intatte.
5. Installazione pulita deve creare un solo template; aggiornamento non invia email e non crea destinatari né campagne.

MAIL-11.2/11.3 sono parziali nella PR #317; MAIL-11.4/11.5 restano da implementare e bloccano qualsiasi invio di annunci.



### PR #319 integrata — 0.56.12-dev: opt-in durante iscrizione automatica

1. Configurare il mailer Joomla verso una casella controllata e pubblicare un corso con iscrizione automatica.
2. Aprire il corso da utente autenticato: la casella annunci deve essere facoltativa e non selezionata per default.
3. Avviare il corso senza selezionarla: l'iscrizione riesce e non viene inviata una richiesta di consenso.
4. Con un utente nuovo, selezionarla e avviare il corso: l'iscrizione riesce e arriva soltanto l'email double opt-in; nessun annuncio viene accodato.
5. Ripetere il POST mentre lo stato è pending: non deve partire una seconda email di conferma. Confermare il token e ripetere: non deve essere riaperta la richiesta.
6. Simulare un errore SMTP: l'iscrizione resta valida, compare un avviso e i log non contengono email, nome o token.
7. Verificare label IT/EN e uso da tastiera. Registrazione Joomla, revoca anonima e campagna amministrativa restano fuori da questa tranche. Questi scenari sono ancora da eseguire sul sito Joomla; CI run 43469236700 ha superato i controlli automatici standard 4/4.

## IMP-09-MAIL-D — MAIL-11.2/11.3 consenso e double opt-in (PR #317, 0.56.11-dev)

PR [#317](https://github.com/jeckodevelopment/jd_learn/pull/317) integrata nel merge `6c44bc4e852bf1d693b53646b5deee6c83153629`; CI standard 4/4 nel [run 43457091398](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398). Pacchetto installabile [JD-Learn-0.56.11-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43457091398/artifacts/14053993647), SHA-256 interno `3e6b918c157b320984064a7fecc0b392ef5bc049d164a3e088b5a835e6732420`; accettazione manuale Joomla/SMTP pendente.

**Comportamenti da testare in Joomla**

1. Installare/aggiornare il pacchetto su Joomla 6 di prova con mailer SMTP di test e un account studente valido. Aprire l’Area personale: la scelta novità corsi deve essere separata dalle mail operative e inizialmente non selezionata.
2. Senza selezionare la casella, inviare direttamente il POST di richiesta: non deve creare consenso né inviare email. Selezionare la casella e inviare: deve arrivare soltanto la mail di conferma, non un annuncio; l’Area personale mostra lo stato in attesa.
3. Verificare in database che la richiesta abbia stato pending, origine, versione informativa e data, e che non sia salvato il token in chiaro. Usare il collegamento entro 24 ore: lo stato passa a confirmed una sola volta. Riaprire il link o alterarlo: nessuna seconda transizione.
4. Confermare di nuovo con indirizzo email errato o con mailer in errore: lo stato non deve diventare confirmed; verificare messaggio generico e log senza token/email.
5. Dall’Area personale revocare l’adesione: lo stato passa a revoked e l’eventuale token pendente viene eliminato. Riaprire l’Area personale e verificare che la preferenza non risulti attiva; gli annunci restano non inviati.
6. Esportare i dati personali dal plugin Privacy Joomla: verificare origine, stato, versione informativa e timestamp senza hash/token. Eseguire la rimozione Privacy su un utente di prova: la riga di consenso deve essere rimossa.
7. Verificare IT/EN, navigazione tastiera/label checkbox e che l’upgrade conservi le personalizzazioni dei modelli `com_jlms.course.published` e `com_jlms.course.consent`.

Il collaudo manuale/SMTP resta da registrare. La registrazione durante enrollment/registrazione, la revoca via link anonimo, i trigger e l’invio degli annunci sono fuori da questa tranche e non devono essere simulati come implementati.


## IMP-09-MAIL-D — Revoca anonima tramite link email (MAIL-11.3)

La pagina di revoca non modifica preferenze in GET: i client email possono aprire o pre-caricare il link. La revoca avviene solo dopo conferma POST protetta da token CSRF. Il bearer token casuale è memorizzato solo come SHA-256, scade dopo 30 giorni ed è legato al timestamp esatto del consenso confermato; una revoca invalida tutti i link emessi per quel consenso. La pagina non mostra dati dell’utente e imposta no-store/no-referrer/noindex.

**Comportamenti da testare sul sito Joomla di prova**

1. Con utente di prova che ha confermato l’opt-in, generare un’email di annuncio di prova e aprire il link di revoca senza inviare il modulo: la preferenza deve restare attiva, anche dopo il prefetch automatico del link.
2. Confermare la revoca dal modulo: verificare messaggio generico, preferenza revocata, redirect senza token nell’URL e assenza di altre email annuncio per quell’utente.
3. Ripetere con link scaduto, già usato e link di un utente senza consenso attivo: l’esito deve essere generico, senza rivelare indirizzo o stato; nessun consenso nuovo deve essere modificato.
4. Verificare POST privo di token CSRF respinto; tentare riutilizzo del link e verificare che non produca ulteriori modifiche.
5. Verificare intestazioni `Cache-Control: private, no-store`, `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex, nofollow`, lingua italiana/inglese e layout mobile.
6. Rimuovere l’utente o il suo consenso tramite Privacy Joomla e verificare che anche i token di revoca siano eliminati.

La UI e il servizio sono coperti dalla CI; collaudo reale Joomla/email resta da registrare. Questa tranche non invia annunci: il generatore di token sarà collegato al flusso amministrativo in una PR successiva.
