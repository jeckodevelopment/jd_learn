- **CORE-11 — contenuti dei percorsi formativi (candidata):** le descrizioni dei percorsi autorizzati sono preparate con `onContentPrepare` nel contesto `com_jlms.pathway`; il filtro HTML JD Learn resta applicato prima e dopo il dispatch. CORE-11 resta parziale; la CI e l'accettazione Joomla sono pendenti.
- **CORE-13 — associazioni lingua dei profili docente (PR #1108 integrata, 28/11/2026, merge `4c62e390bbae51e2268f67754effc2f0d2003f4b`):** registrato il tipo nativo `com_jlms.teacher` nel gestore Joomla Associazioni multilingua; il selettore pubblico include solo traduzioni visibili per lingua, pubblicazione, ACL e categoria. CI [#5046](https://github.com/jeckodevelopment/jd_learn/actions/runs/46160943466) 4/4 e gate requisiti [#2362](https://github.com/jeckodevelopment/jd_learn/actions/runs/46160924886) verdi; [pacchetto installabile JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/46160943466/artifacts/15270206234). CORE-13 resta Parziale; collaudo manuale Joomla in attesa e avanzamento invariato a 65,94%.
- **CORE-04/teacher_edit — modifica profilo docente delegato (PR #1101):** CI Joomla disposable verifica `core.edit.own` senza `core.edit`, campi amministrativi invariati e diniego sul profilo altrui; il criterio passa a implemented. Avanzamento documentato 65,94% (515 implementati, 9 mancanti, 233 non verificabili). Collaudo manuale Joomla ancora in attesa.
- **CORE-04/record_acl — transizioni docente e permesso edit-own (PR #1095 integrata, merge `ab4b9ad`):** uniformata l’autorizzazione dei batch docente a quella del singolo record, con `core.edit.state`, checkout e rollback atomico; regressione Joomla disposable su edit-own e batch misto. CI #4925 4/4 e gate CSV #2293 superati; pacchetto installabile verificato (ZIP interno SHA-256 `5bdd680bc3b2cfee32dc1feaa643936fbccece17b41011d5c0414dc7daa09500`). CORE-04 resta Parziale per `teacher_edit` e il collaudo manuale.
- **CORE-20 — ricerca e filtri dell’elenco Percorsi (PR #1088 integrata):** Search Tools Joomla per titolo/alias/ID, categoria, accesso, lingua, stato e ordinamento. CI #4852 4/4 e matrice #2256 verdi; accettazione manuale ancora in attesa. Batch avanzati differiti.
- **CORE-20 — ordinamento del registro iscritti (PR #1089 candidata):** aggiunto ordinamento server-side per studente, versione, stato, progresso e attività, con whitelist e direzione validata; i filtri restano preservati nei collegamenti. Test HTTP di regressione aggiunti; CI #4857 4/4 e matrice #2260 verdi; pacchetto dev [JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/46015551625/artifacts/15206585001). Collaudo Joomla in attesa.
- **CORE-16 — accesso allo storico dei percorsi (PR #1084, integrata):** l’editor amministrativo mostra il comando Joomla Versioni per i percorsi salvati. Merge `d66bc394c9000b7746ee5f86abcd182bb518b673`; CI completa #4797 verde. Collaudo manuale pendente.
- **STU-01 — matrice diagnostica completa (PR #1093 integrata):** prove servizio per ciascuna causa e azione; test HTTP della resa per sospensione, annullamento, attesa, scadenza, finestra futura, revoca ACL, corso ritirato e lingua assente. L’azione Riprendi/Rivedi compare solo con accesso disponibile. Criterio implementativo `blocked_reason` promosso. CI #4903 4/4 e gate requisiti #2285 superati; accettazione manuale Joomla ancora pendente.
## 1.0.0 — candidata non pubblicata

Prima versione stabile di JD Learn

- **CORE-14/index_acl — eventi Finder e revoca dei risultati privati ([PR #1099 integrata](https://github.com/jeckodevelopment/jd_learn/pull/1099), merge `9f1a88dc59ab3ec0b3f7329cb08458e2e5345c2a`):** regressioni Joomla verificano i link Smart Search persistiti dopo salvataggio/cambio stato/rimozione di corsi e categorie, incluse revoche di accesso e lingua e il successivo ripristino. Il criterio è implementato nel perimetro software del plugin Finder core; accettazione manuale Smart Search sul sito effettivo pendente. CI [#4948](https://github.com/jeckodevelopment/jd_learn/actions/runs/46076074058) 4/4 e gate requisiti [#2299](https://github.com/jeckodevelopment/jd_learn/actions/runs/46076054341) superati; pacchetto installabile [JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/46076074058/artifacts/15235566174), ZIP interno da 882919 byte con SHA-256 `5bdd680bc3b2cfee32dc1feaa643936fbccece17b41011d5c0414dc7daa09500`. Avanzamento minimo 65,78% (514 implementati, 9 mancanti, 234 non verificabili); accettazione manuale pendente.

- **CORE-03/cross_context — revoca viewing level tra i contesti ([PR #1097 integrata](https://github.com/jeckodevelopment/jd_learn/pull/1097), merge `93b241a98e400a4bbe7fd03ff8946eafd8623de2`):** prove di servizio e HTTP revocano indipendentemente il viewing access level mantenendo il gruppo Joomla e coprono aula, completamento/replay iscrizione, storico, ripresa, scheda e catalogo, con ripristino nella stessa sessione. CI [#4939](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672529) 4/4 e gate CSV [#2297](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672532) verdi. Avanzamento minimo documentato 65,55%; accettazione manuale ancora pendente. Pacchetto installabile [JD-Learn-0.64.22-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/46072672529/artifacts/15234490645), SHA-256 interno `5bdd680bc3b2cfee32dc1feaa643936fbccece17b41011d5c0414dc7daa09500` (estrarre il contenitore Actions una volta).

- **CORE-09 (PR #1081, CI #4754 verde):** tag nativi Joomla completati per corsi, docenti e percorsi, inclusi mapping UCM, persistenza, rimozione e reidratazione. `CORE-09/tags_context` è implementato nel perimetro software. AC-19/AC-64 sul pacchetto installato, ricerca e ACL restano collaudo manuale in attesa.
- **CORE-16 — storico dei percorsi (PR #1083, candidata):** aggiunti snapshot Joomla Content History con tag e ordine dei corsi; il ripristino passa dal modello transazionale e crea una nuova revisione. CI e collaudo Joomla restano pendenti; nessuna promozione dell'intero criterio.
- **DOC-03/criterion_03 (PR #1082, candidata):** aggiunte quattro illustrazioni SVG schematiche e descritte in modo accessibile per catalogo, area personale, aula e profilo docente. I dati sono fittizi; il confronto con il pacchetto installato resta collaudo manuale.

- DOC-10 implementativo completato (PR #1074, merge `37d3cd17f7afabb192ce429c36d9f1517eacb227`): le note della candidata sono legate alla sezione versionata del changelog e il digest è verificato prima della pubblicazione. Stato candidato e accettazione manuale restano separati.

- **LCM-05 — chiusura gate lifecycle implementativo (22/11/2026, PR #965 e #966):** 7/7 criteri documentati e testati nel perimetro software. Le query del plugin Finder rispettano stati, lingua, accesso e calendario; transizioni notificano dopo commit; metadati SEO HTTP e snapshot di percorso/associazioni dopo upgrade sono verificati. La CI #45076048707 è 4/4 verde, compreso il backup/ripristino disposable eseguito nella pipeline. Collaudo Smart Search e recovery dell'hosting reale non eseguiti.

- **LCM-05 — gate Finder e conservazione lifecycle (22/11/2026):** ampliate le prove dell'adapter Finder installato su database Joomla per archiviazione, cestinazione, ripristino non pubblicato, pubblicazione esplicita, finestre di pubblicazione e filtro lingua. Aggiunto snapshot isolato dei dati stabili di percorso e associazioni da confrontare prima e dopo un aggiornamento reale del pacchetto in CI; versioni, iscrizioni e attestati sono coperti dalle rispettive fixture retained della stessa pipeline. Restano distinti il collaudo SEO su installazione reale e le verifiche operative di backup/ripristino dell'hosting.

- **LCM-05 — ACL granulari dei percorsi, modello e HTTP (22/11/2026):** nuova fixture Joomla con gestore delegato che riceve permessi di modifica e stato tramite la categoria ma subisce un diniego esplicito sul singolo percorso. Test di rollback atomico delle selezioni miste e di archiviazione/ripristino/pubblicazione consentiti solo sul record autorizzato; le stesse transizioni vengono esercitate via controller HTTP con token Joomla del delegato. Collaudo manuale e verifiche Finder/SEO e backup restano separati.

- **LCM-05 — atomicità operazioni massive HTTP (22/11/2026):** una selezione mista contenente un percorso valido e uno inesistente deve far respingere con HTTP 403 sia archiviazione sia cestinazione, senza mutare il percorso valido; l'eliminazione definitiva respinge selezioni multiple con HTTP 422. Aggiunte regressioni su richieste autenticate con token Joomla reale. Le ACL per record in gruppi misti rimangono da verificare separatamente.

- **LCM-05 — prove HTTP con token CSRF alterato (22/11/2026):** aggiunti test di regressione sull'amministrazione Joomla per le sei operazioni di lifecycle dei percorsi (`trash`, `restore`, `archive`, `publish`, `unpublish`, `purge`): una richiesta POST autenticata con token alterato deve ricevere 403 e lasciare invariata la disponibilità del percorso. L'accettazione manuale e i gate ACL per record, Finder/SEO e upgrade rimangono separati.

- **LCM-04 — chiusura dell'implementazione core (22/11/2026, PR #960):** riconciliati i quattro criteri del ciclo di vita dei docenti. Le chiavi esterne SQL dichiarate dalle integrazioni verso `#__jlms_teachers.id` sono verificate prima della purga, incluse quelle con `ON DELETE CASCADE` (protezione e regressione Joomla già integrate con PR #958). Restano separati l'inventario delle eventuali integrazioni prive di FK e l'accettazione manuale su installazione reale; nessuna attestazione universale di protezione dei riferimenti opachi.

- **LCM-04 — protezione delle dipendenze esterne (22/11/2026, PR #958):** la purga di un docente cestinato ora verifica anche le chiavi esterne SQL dichiarate da integrazioni installate, impedendo la cancellazione e i relativi effetti `ON DELETE CASCADE` in presenza di righe storiche. Aggiunta prova Joomla su FK esterna; riferimenti opachi senza FK e collaudo manuale esclusi dal contratto. Nessuna chiusura prematura del requisito.

- **AUT-01 — chiusura implementativa dell'editor corsi (22/11/2026):** completata la riconciliazione dei sei criteri software dopo i test di recupero di input nei form corso, modulo e lezione e la correzione dei campi Joomla nativi su nuovo/esistente. AUT-01–AUT-05 risultano 5/5 requisiti completati per implementazione, senza anticipare i collaudi manuali Joomla. La dashboard di rilascio distingue gli esiti CI dall'accettazione umana.

- **AUT-01 — ripresa dei campi Joomla dopo errore nell’editor lezione (22/11/2026, PR #935):** per lezioni nuove ed esistenti, l’editor contestuale ripresenta i valori dei campi nativi Joomla autorizzati dopo un salvataggio respinto, senza scritture parziali. I valori di sessione sono limitati ai nomi riconosciuti e a dimensioni/strutture controllate; una fixture HTTP verifica il rifiuto, la conservazione degli input e il successivo salvataggio. Collaudo manuale Joomla separato.

- **AUT-04 — conservazione attestati nelle migrazioni di versione (22/11/2026, PR in preparazione):** regressione Joomla su una migrazione iscrizione esplicita: certificato storico, documento e snapshot della versione originale devono restare invariati e appartenere all'iscrizione sorgente; nessun attestato assegnato implicitamente alla nuova iscrizione. Il test retention ripete la verifica dopo l'upgrade del pacchetto. È una prova specifica di immutabilità, non un collaudo completo di backup e restore esterni.

- **AUT-03 — trasparenza sottotitoli provider (22/11/2026, PR in preparazione):** la checklist di pubblicazione avverte esplicitamente quando le tracce caption effettive di YouTube, Vimeo e Cloudflare Stream non possono essere verificate automaticamente, senza confondere la loro esistenza con la trascrizione locale e senza introdurre falsi blocchi alla pubblicazione. Test unitari su provider e versione MP4 locale, guida video e traduzioni IT/EN aggiornate; accettazione Joomla manuale distinta.

- **ISC-07 — chiusura delle verifiche automatiche inter-risorsa (22/11/2026, PR #917):** i replay idempotenti di telemetria video ora rispettano la scadenza del token provider prima di restituire risultati; le fixture verificano revoca del livello su quiz aperti (anche consegna e storico risposte), download protetti GET/HEAD/Range/PDF nella stessa sessione HTTP e ripristino dei permessi, oltre alle regressioni pregresse su revoca delle sessioni video. Le autorizzazioni vengono rivalidate lato server per ciascuna richiesta; nessuna revoca istantanea dei token già emessi da terze parti è implicata. I collaudi manuali Joomla restano separati.

- **ISC-07 — invalidazione server delle sessioni video alla revoca (22/11/2026, PR #915):** sospensione e cancellazione dell'iscrizione eliminano le sessioni player persistenti nella stessa transazione auditata che aggiorna lo stato, senza toccare progressi, attestati e storico. Nuovi test Joomla verificano la rimozione delle sessioni in entrambi i flussi; il controllo autorizzazioni sulle singole richieste resta obbligatorio. Token eventualmente già rilasciati dai provider remoti non sono revocati attraverso questa tabella; nessuna accettazione manuale dichiarata.

- **ISC-07 — revoca iscrizione durante sessione video (22/11/2026, PR #913):** aggiunta regressione Joomla realistica sulle sessioni già aperte: sospensione e cancellazione devono bloccare nuovo playback, replay idempotente e nuova telemetria; progressi e stato del player conservati e recuperabili dopo il ripristino. La prova non implica collaudo Joomla manuale né chiusura generale di ISC-07.

- **DOC-10 — evidenza candidata vincolata (22/11/2026, PR #911):** il workflow di release genera un record JSON verificabile che lega commit, versione, ZIP Joomla interno, checksum SHA-256, feed XML e bozza delle note; verifica nuovamente i byte prima della pubblicazione autorizzata. Test negativi su checksum e record alterati, guida aggiornata. Nessuna release pubblicata, nessun criterio DOC-10 promosso, accettazione Joomla ancora da eseguire.

- **CAT-04 — Finder accesso pubblico (22/11/2026, PR #908):** Smart Search ora richiede contemporaneamente accesso pubblico a catalogo e contenuto; test Joomla di transizione ACL e rimozione dall'indice, con fix del passaggio per riferimento a `updateObject()` su PHP 8.4. Le schede senza immagine valida usano `twitter:card=summary`. Preservati canonical non doppiamente escapati, header robots e breadcrumb della PR #907. Requisito già implementato 5/5: nessun aumento percentuale o collaudo Joomla attribuito.

- **0.64.22-dev — CAT-04, private indexing e breadcrumb (22/11/2026, PR #907):** esteso `X-Robots-Tag: noindex, nofollow` a tutte le rotte personali, didattiche e di modifica, con header coerente alle viste corso/categoria/percorso quando non pubblicamente indicizzabili. Il controllo automatico censisce viste private e contratti SEO pubblici; le schede corso/percorso completano inoltre il breadcrumb nativo senza duplicare una voce di menu diretta. Corretto anche il routing canonical/Open Graph affinché non riutilizzi URL già codificati per XHTML, evitando il doppio escaping di `&amp;`. Aggiunti test PHP per canonical, metadati social/immagini e breadcrumb e test HTTP per le varianti SEF/IT/EN, senza modificare permessi o contenuti. Con le regressioni sorgente, PHP e HTTP, l'audit riclassifica `robots_security` e `seo_combinations` come implementati: CAT-04 5/5 sul piano software, 61,92% sviluppo globale minimo (485 implemented, 15 missing, 257 unverifiable). I collaudi manuali Joomla, le configurazioni reali di Finder e le estensioni terze restano separati; nessuna accettazione è attribuita.

- **CAT-04 — protezione indicizzazione editor docente (22/11/2026, PR #905):** la vista privata `teacheredit` aggiunge `X-Robots-Tag: noindex, nofollow` e meta robots coerenti, mantenendo `Cache-Control: private, no-store`. La scheda pubblica `teacher` resta invariata. Test di regressione nel preflight; accettazione Joomla e gate SEO/Finder complessivo ancora aperti, nessun criterio funzionale promosso.

- **DOC-10 — note preparatorie del rilascio (22/11/2026, PR #904):** redatta una bozza controllata per la candidata 1.0.0 che separa funzioni in perimetro, compatibilità ancora da convalidare, limitazioni note e riferimenti verificabili all'artefatto con SHA-256 interno. Aggiunto controllo di struttura/versioni nella CI; nessuna release stabile pubblicata né accettazione manuale dichiarata. Lo stato DOC-10 e la percentuale globale restano invariati fino all'audit finale.

- **LCM-02 — toolbar editor corso (#793, PR #903, 22/11/2026):** il pulsante di applicazione usa l'azione Joomla `apply` per salvare senza uscire, distinta da `Salva & Chiudi` e `Salva e apri programma`. Aggiunta verifica statica, accettazione manuale pendente; nessun criterio funzionale incrementato.

- **DOC-04 — audit evidenze tecniche (22/11/2026):** riclassificati due criteri implementati dopo [PR #901](https://github.com/jeckodevelopment/jd_learn/pull/901): contratti plugin/database e procedure tecniche riproducibili, con manifest, API, DDL e link verificati in preflight. Il registro ora contiene **483 implemented, 15 missing e 259 unverifiable**; sviluppo globale minimo **61,73%** (+0,23 punti), macroarea delivery **45,00%**. DOC-04 resta parziale (3/4) e il collaudo manuale non eseguito.

- **DOC-04 — riferimento tecnico 0.64.21-dev (22/11/2026):** aggiunta la guida versionata su package e child ZIP, contratti provider/video, database, upgrade, task, troubleshooting e procedure riproducibili; nuovo test di drift eseguito dal preflight. La classificazione del registro funzionale resta in attesa di audit della nuova evidenza; nessuna modifica a runtime, schema o manual acceptance.

- **CAT-01 — chiusura implementativa (22/11/2026):** riallineati matrice e conteggi a cinque criteri software già `implemented`, con fonti e test esistenti. Stato sviluppo `Completata`; accettazione Joomla `Parziale` soltanto per conferma precedente circoscritta, collaudo integrale catalogo ancora aperto. Aggiunta regressione di tracciabilità; 33 requisiti completi nello sviluppo, 61,50% globale invariato. Nessuna modifica runtime.

- **LES-01 / STU-04 — chiusura implementativa (22/11/2026):** riconciliati rispettivamente 4/4 e 3/3 criteri software già `implemented` nel registro funzionale; chiusura della sola implementazione con la verifica manuale Joomla conservata a `Non eseguita`. Test di tracciabilità aggiunto e conteggio requisiti completati nello sviluppo aggiornato a 32. Nessun nuovo codice runtime né incremento percentuale globale, che rimane al 61,50%.

- **CAT-02 — CHIUSURA IMPLEMENTATIVA (21/11/2026):** riconciliati i cinque criteri software già `implemented` (presentazione, docenti, durata, azioni di accesso e riepilogo regole/attestati), comprese le correzioni testate #893 e #895. La matrice distingue `Completata` nello sviluppo da accettazione manuale Joomla `Non eseguita`; progressi globali e conteggio criteri invariati al 61,50%. Nessuna nuova funzione runtime né ampliamento del perimetro 1.0.0.

- **CAT-02 (21/11/2026):** anteprime pubbliche con dati storici di calendario malformati rifiutate con HTTP 404, in modo coerente con l'outline già protetto. La lettura non divulga corpo della lezione né produce errori applicativi; aggiunta regressione nella suite integrativa Joomla. Nessun criterio promosso né variazione della percentuale di sviluppo.

- **CAT-02 (21/11/2026):** la scheda corso mostra il collegamento di anteprima pubblica solo per lezioni testuali esplicitamente abilitate e disponibili nel programma. La vista ora rispecchia l'endpoint, che già rifiuta preview video e altri tipi di lezione. Regressione statica del contratto frontend/server; nessuna modifica a ACL, contenuti riservati, percentuali o accettazione manuale.

- **LES-02 (21/11/2026):** allineata la sanificazione HTML dei campi editoriali pubblici dei percorsi e dei docenti alla allowlist già usata per la presentazione dei corsi. Anche con filtro Joomla permissivo, script, iframe e attributi attivi non devono persistere; restano consentiti paragrafi, formattazione e link sicuri. Test di integrazione su salvataggio dei due modelli.

- **LCM-04 (21/11/2026):** la cancellazione definitiva di un profilo docente è negata se il profilo è ancora collegato a un account Joomla (`user_id`), anche bloccato e senza assegnazioni ai corsi. Il collegamento va rimosso esplicitamente prima dell'eventuale purga, che rimane subordinata alle altre dipendenze. Regressione Joomla su account attivo/bloccato e conservazione del riferimento; nessun account viene alterato dal modello.

- **LCM-03 (21/11/2026):** i salvataggi editoriali diretti dei percorsi ora rispettano il checkout nativo Joomla, oltre ad ACL e revisione. Modifica negata se il percorso è già aperto da altro operatore, senza alterare dati o relazioni con i corsi; fixture di integrazione su rifiuto, dati invariati e salvataggio del titolare.

- **LCM-03/04 (21/11/2026):** eliminazione definitiva protetta anche dalle associazioni multilingua native Joomla per percorsi e docenti. In presenza di riferimenti `#__associations` la purga viene negata, senza eliminare la scheda o toccare le relazioni; regressioni Joomla per divieto e riuscita dopo la rimozione del solo riferimento di test.

- **LCM-03 (21/11/2026):** le transizioni massive e la cancellazione definitiva dei percorsi ora rispettano il blocco di modifica nativo Joomla (`checked_out`). Una riga bloccata da un altro operatore arresta l'operazione e annulla l'intero batch, senza cambiare stati, revisioni o collegamenti. Test di integrazione su rollback e cancellazione negata; accettazione manuale separata.

- **LCM-03 — salvataggi parziali percorsi (21/11/2026):** i salvataggi editoriali che omettono `courses` conservano le associazioni e l'ordinamento esistenti; solo una lista `courses=[]` esplicitamente fornita azzera il programma. Regressione di integrazione Joomla con conservazione dopo aggiornamento. Collaudo manuale separato.

# Changelog

- **SCR-01 — navigazione catalogo con filtri persistenti (21/11/2026):** le pagine catalogo corsi e categoria, incluse le sottocategorie paginabili, propagano esplicitamente nei link Joomla solo i filtri e l'ordinamento già normalizzati dal server. Gli invii GET di nuovi filtri ricominciano dalla prima pagina; nessuna propagazione di parametri arbitrari o filtri disabilitati. Fixture PHP e guardie strutturali.

- **SCR-16 — chiusura implementativa configurazione e diagnostica (21/11/2026):** rimosso ogni dispatch/registrazione di plugin video dalla dashboard amministrativa; letture soltanto della configurazione Joomla locale con stati non verificati per Cloudflare Stream e plugin esterni. Configurazioni abilitate/disabilitate dei provider core valutate senza chiamate remote, scritture, pubblicazioni, modifiche gruppi o invii. Test negativi di segreti in output e gate strutturali sui side effect; criteri tecnici e accettazione Joomla tracciati separatamente.

- **SCR-16 — diagnostica provider e ACL (21/11/2026):** ripristinata l'etichetta localizzata nella proiezione di diagnostica video, supportati i soli identificativi IT/EN e plugin localizzati validi; registrazione/status dei provider e relativa sezione dashboard limitati a utenti con `core.manage` del componente. Nuovi test di regressione, nessuna azione di connessione o invio implicito.

- **SCR-16 — verifica e protezioni diagnostiche (21/11/2026):** parametri vuoti dei menu trattati come ereditarietà valida; controllo di tutte le cinque opzioni globali di presentazione catalogo; filtri sui nomi e sulle etichette forniti dai plugin video per non visualizzare valori arbitrari. Test PHP negativi e strutturali, sola lettura, nessun invio o mutazione.

- **SCR-16 — scansioni indipendenti (21/11/2026):** la diagnostica amministrativa distingue l'incompletezza dei corsi da quella dei menu; verifica soltanto i link delle viste catalogo/categoria esatte, esclusi prefissi simili. Fixture PHP e gate strutturali. Nessuna chiamata esterna, mutazione o divulgazione di valori. I criteri complessivi 02–05 non sono promossi senza prova integrale.

- **SCR-16 — diagnostica robusta dei menu (21/11/2026):** gli errori di override già rilevati prevalgono sullo stato di scansione incompleta; la lettura limitata a 500 voci include esclusivamente i menu catalogo/categoria pertinenti. Regressioni PHP e strutturali, nessuna scrittura o connessione esterna. I criteri trasversali SCR-16/02–05 restano da dimostrare integralmente.

- **SCR-16 — diagnostica override effettivi (21/11/2026):** controllo read-only e limitato a 500 record delle preferenze email per corso e dei parametri catalogo nei menu frontend. Individua valori locali validi, mancanti, malformati e scansioni incomplete; segnala gli stati in italiano/inglese senza mostrare identificativi, contenuti, indirizzi o credenziali. La dashboard resta protetta da ACL e non invia messaggi o modifica dati.

- **SCR-16 — video locali, prerequisiti effettivi (21/11/2026):** la dashboard non interpreta più la sola consegna PHP abilitata come pronta; verifica separatamente la radice video privata e l'eseguibile ffprobe senza eseguire comandi, sonde remote o scritture. Stati e rimedi IT/EN senza divulgare percorsi; test per percorsi pubblici, assenti e privati, ffprobe mancante e modalità disabilitata.

- **SCR-16 — diagnostica configurazione consolidata (21/11/2026):** dashboard con verifiche read-only per antivirus, video locali, attestati, notifiche globali e opzioni catalogo ereditate. Corretta la diagnosi di directory esposte attraverso la `DOCUMENT_ROOT` reale; esiti e rimedi IT/EN non includono segreti o percorsi. Test PHP e gate strutturale senza mutazioni. Le prove hosting, SMTP e provider esterni rimangono separate.

- **SCR-16 — diagnostica modelli email Joomla (21/11/2026):** aggiunto controllo read-only della presenza di tutti i template canonici nella lingua predefinita `*`, con stati e rimedi IT/EN e visibilità `core.manage`. Non vengono letti testi, destinatari, credenziali o parametri email. Regressioni per default presenti, assenti e sole traduzioni; nessuna modifica allo stato di implementazione complessivo.

- **SCR-16 — diagnostica storage privato (21/11/2026):** dashboard amministrativa con preflight locale in sola lettura di directory private esterne, percorso pubblico rifiutato, directory mancante e storage gestito da verificare all'uso; esiti e rimedi IT/EN senza divulgazione dei percorsi. Test automatici su quattro stati; nessun side effect o incremento di copertura anticipato.

- **LES-03 — storage privato Windows (21/11/2026):** corretta la validazione di path assoluti con separatore backslash, mantenendo il rifiuto di path relativi e drive-relative. Cinque test regressione Unix/Windows. Nessuna accettazione hosting dichiarata e nessun nuovo requisito completato automaticamente.

- **0.64.21-dev — AUT-04:** controllo fail-closed della completezza delle equivalenze salvate durante la migrazione di iscrizioni, con prova preflight 0–1001; integrazione Joomla ampliata per tentativi quiz aperti, edizioni esaurite, recupero e mapping solo espliciti. PATCH SemVer; nessuna migrazione schema e nessuna modifica agli stati delle iscrizioni senza conferma.

- **0.64.20-dev — AUT-04/LES-01:** corretto il rischio di troncamento silenzioso dei campi Joomla di una lezione durante la duplicazione: oltre 1.000 valori la copia fallisce prima della loro scrittura; fixture automatiche 0/1/999/1000/1001 e gate CI. Patch SemVer 0.64.20-dev per correzione integrità dati.

- **0.64.19-dev — SECDEV-07/02 SAST/stile/composizione dipendenze (candidato 21/11/2026):** CI con Semgrep CE 1.186.0 e regole offline per PHP, JavaScript e Python, fixture positive/negative, Ruff E4/E7/E9 e gate first-party sulle 12 parti Joomla; nessuna dipendenza runtime o modifica SemVer. Chiusura criterio subordinata alle prove CI e alla revisione dei limiti.

- **0.64.19-dev — SECDEV-07 syntax coverage (21/11/2026):** preflight sintattico su tutti i sorgenti Python/JS tracciati, parser Python e Node con fixture negative, mantenendo il lint PHP esistente; nessuna modifica runtime o SemVer, nessuna promozione di SECDEV-07/criterion_02 senza SAST/SCA completi.

- **0.64.18-dev — SEC-P2 findings gate (21/11/2026):** registro versionato delle mitigazioni e regressioni per finding critical/high del codice, fixture negative per rischi esterni non verificati e advisory Joomla/PHP; nessuna modifica runtime, versione invariata. SEC-P2/criterion_02 chiuso nel ledger di sviluppo del software, con gate hosting/release ancora aperti. Sviluppo minimo 60,12% (469 implemented / 21 missing / 267 unverifiable). Verifiche hosting separate.

- **0.64.18-dev — SEC-P2 dependency composition gate (21/11/2026):** controllo fail-closed delle 12 estensioni ZIP figlie e dei dependency manager/lockfile/vendor/binary runtime non revisionati, invocato dal builder e preflight; fixture negative. Nessun cambiamento alle funzioni Joomla né bump SemVer. SEC-P2/criterion_01 riclassificato come implementato con evidenze software; criterio 02 finding alti/critici e verifica hosting restano separati. Nuovo sviluppo comprovato 59,89% (468/22/267).
- SECDEV-07: security assurance ledger versionato con dieci controlli tracciati a codice/test/CI, quattro rischi aperti e negative fixtures obbligatorie nel preflight; nessuna nuova dipendenza e nessuna modifica runtime.

- PRIV-C2 / SEC-P3: pannello amministrativo read-only, con ACL di audit globale e filtro/paginazione, per i consensi MAIL-11 correnti; nessun dato di contatto, token o nuovo archivio dei consensi.
- SEC-P3: inventario versionato di tutte le 47 tabelle della baseline installabile, gate anti-deriva nel preflight, prove Joomla Privacy a due account e su pending/confirmed/revoked, procedure amministrative IT/EN per conservazione, backup/restore e diritti. Nessuna nuova scadenza di conservazione presunta e nessuna modifica di schema.

- SEC-P3: esportazione Joomla Privacy dei metadati di consegna email limitati al destinatario; rimozione dei diagnostici `last_error` dalle righe terminali anonimizzate, con regressioni e traduzioni IT/EN. Gate complessivo SEC-P3 ancora aperto.

- **0.64.18-dev — SEC-P2 storico segreti (21/11/2026):** audit CI della storia Git per sorgenti distribuiti e workflow, comprese versioni cancellate, con fixture negative e output senza dati sensibili. Controllo supply-chain senza modifiche al runtime Joomla o nuovo bump SemVer; CSP/hosting e revisione finale delle dipendenze restano separati.

- **0.64.18-dev — SEC-P2 hardening build/package (21/11/2026):** lo scanner ricorsivo del package rifiuta link simbolici ZIP, collisioni case-insensitive, drive Windows, traversal e decompressione aggregata per tutti gli archivi annidati; il builder applica il controllo prima di produrre ogni ZIP dev o stabile. Aggiunte fixture negative e documentato il gate live residuo; SEC-P2 non chiuso senza SCA, storico segreti e verifica hosting. Bump PATCH per difesa del percorso di distribuzione.

- **0.64.17-dev — DOC-04 contratti tecnici e procedure (21/11/2026):** aggiunta mappa versionata del package, dei dodici costituenti, delle superfici applicative e dei gate di verifica, con procedura riproducibile per build, integrity check, ricognizione schema read-only e collaudo. Nessuna modifica runtime, versione o percentuali; ricognizione database reale e accettazione manuale pendenti.

- **0.64.17-dev — UI-07, inventario DOM completo (21/11/2026):** mappa versionata delle 17 viste e dei 4 partial condivisi con dipendenze di inclusione, gate su nuove sorgenti e collisioni ID con moduli esterni, due personalizzazioni CSS indipendenti. Criterio di mappatura implementato, collaudo su Joomla/override reali ancora pendente. Nessuna modifica runtime, SemVer invariato.

- **0.64.17-dev — DOC-07, completamento implementativo (20/11/2026):** dashboard con verifiche locali di configurazione e rimedi IT/EN per Scheduler e provider video, visibili con ACL corrente; nessun invio, probe esterno o modifica. Test positivi/negativi incluse priorità duplicati, stati ignoti, etichette non attendibili e controllo del markup. Tre criteri implementativi DOC-07 chiusi; sviluppo comprovato **59,42%** (466 implementati / 24 mancanti / 267 non verificabili). Collaudo manuale distinto, SemVer invariato.

- **0.64.17-dev — PBASE-02, requisito implementativo completato (20/11/2026):** verifica di completezza delle guide distribuite dei quattro provider video, correlazione con i manifest e fixture negativa per capitoli mancanti. Riconciliati i tre criteri tecnici come implementati: 59,11% sviluppo comprovato, 464 implementati / 26 mancanti / 267 non verificabili. Nessun cambiamento runtime, SemVer invariato; riproduzione e collaudo manuale dei provider non acquisiti.

- HTTP-06 (test-only, 0.64.17-dev): regressioni Joomla sui confini di richiesta `enrollment.resume` POST (405), `lesson.begin` con body eccessivo (413 JSON) e `video.save` eccessivo (413 JSON senza riferimenti provider); nessun cambiamento runtime, criteri o percentuali promossi prima della CI e del collaudo.

- HTTP-06: ampliate le regressioni HTTP su Joomla reale per metodo, CSRF, task sconosciuti e contratto JSON dei flussi iscrizione/lezione/video; registro casi limite core separato dal catalogo progettuale.

- UI-07: controllo DOM sul markup reale Joomla per ID duplicati e scope di due personalizzazioni CSS indipendenti nelle viste catalogo/categorie/dashboard; fixture negative integrate nel preflight.

- SEC-P2: gate CI sul livello di patch PHP per le correzioni di sicurezza del 19/11/2026 (8.3.37 e 8.4.28); verifiche sull'hosting reale non incluse.

- SEC-P2 (pre-release): controllo CI su credenziali note nei sorgenti distribuiti e GitHub Actions pinned by SHA; workflow requisiti bloccato a commit; inventario supply-chain, CSP e logging documentato. Gate release live e advisory aperti.

- SEC-P2/SEC-P3 (pre-release): scansione ricorsiva di segreti e file sensibili nel ZIP distribuito; blocco privacy su mail queue attiva e redazione della storia di invio terminale, inclusi token di revoca. Gate finali ancora aperti.

- SEC-P2/SEC-P3 (prerelease): privacy removal fail-closed anche per evento diretto; audit read-only dei file video legacy in webroot, con gate di regressione. I controlli completi e l'accettazione finale restano aperti.

- **0.64.17-dev — DOC-02:** manuali amministrativi IT/EN distribuiti con quattro capitoli operativi aggiuntivi, link offline testati e test automatico di coerenza nel package builder. Contestuale riconciliazione dei criteri tecnici già realizzati, senza affermare accettazione manuale o modificare runtime e SemVer.


- **0.64.17-dev — DOC-08 (20/11/2026):** inventario verificabile dei 21 flussi core con riferimenti alle guide, test anti-drift e gate separato `--release` che vieta la pubblicazione documentale senza walkthrough ed evidenze; nessun cambiamento runtime o bump SemVer. Collaudi manuali ancora pendenti.


- **0.64.17-dev — DOC-07 (20/11/2026):** dashboard amministrativa ampliata con procedure read-only ad alto rischio per storage, coda email, attestati, gestione corsi e Scheduler, con ACL correnti e traduzioni IT/EN. Test strutturale anti-regressione e matrice operativa riproducibile; nessuna modifica dei servizi mutativi. Accettazione manuale e prove degradate ancora pendenti; versione invariata.


- **0.64.17-dev — PBASE-02 (20/11/2026):** consolidate in un solo intervento le guide installabili dei quattro plugin video, con tabelle manifest di tipi/default/vincoli, prerequisiti, privacy, ciclo di vita, failure mode e matrice di collaudo. Il gate di build ora confronta i parametri XML e le guide. Nessuna modifica runtime, nessun bump SemVer né collaudo manuale dichiarato.


- **0.64.17-dev — UI-07, contratti frontend (20/11/2026):** aggiunto inventario DOM pubblico versionato per 17 viste (radici, ID, classi, override), controllo di deriva in CI e due esempi di personalizzazione CSS indipendenti. Nessuna alterazione del runtime o bump SemVer; collaudi Joomla di override, DOM dinamico e accessibilità ancora pendenti.

- **0.64.17-dev — SEC-P1, threat review consolidata e hardening video (20/11/2026):** il video locale MP4 in chiaro richiede ora una `video_storage_root` esterna al webroot; configurazione assente rifiutata 503 invece di un fallback gestito. Aggiunti test Joomla di confine, gate sorgenti PHP ad alto rischio sull'intero pacchetto e modello delle minacce con finding classificati. Requisiti SEC-P2/SEC-P5 mantengono le verifiche su asset legacy, deployment e test E2E. Versione di sviluppo invariata fino al successivo rilascio.

- **0.64.17-dev — SEC-P1, FIX comunicazioni (20/11/2026):** chiuso il replay ambiguo dei job `course_communication`: un UUID ripetuto può restituire il job originale soltanto se oggetto, corpo, template e destinatari coincidono esattamente; altrimenti 409, senza effetti. Aggiunta regressione Joomla e allineate evidenze. Correzione PATCH nel ramo di sviluppo 0.64.17-dev, senza nuovo bump finché la versione non è rilasciata.

- **0.64.17-dev — SEC-P1, revoca HTTP reale (20/11/2026):** estese le regressioni di integrazione con sessione browser amministrativa persistente e blocco del record Joomla dopo la form; 403 su GET/POST stato e POST ammissione manuale, dati e audit immutati, ripristino fixture. Nessuna modifica runtime né bump SemVer; SEC-P1 ancora parziale.

- **0.64.17-dev — SEC-P1, review trasversale (20/11/2026):** gate anti-regressione dei 10 plugin distribuiti, 3 task Scheduler, limiti dei worker e interfaccia media privata, con matrice minaccia/controllo/test/residui per 13 macro-superfici. Nessuna modifica runtime, nessuna dichiarazione di audit completo, SEC-P1 ancora parziale; SemVer invariato.

- **0.64.17-dev — SEC-P1, test-only consolidato (20/11/2026):** regressioni ACL per tre servizi amministrativi delle iscrizioni (stato, approvazione, ammissione manuale) con account bloccato dopo login, verifiche negative, assenza side effect e ripristino. Confermati controlli runtime preesistenti; nessun bump SemVer né promozione SEC-P1.

- **0.64.17-dev — SEC-P1, test-only (20/11/2026):** prova negativa su identità Joomla bloccata dopo login per anteprima/applicazione della migrazione iscrizioni, con verifica di assenza di modifiche DB e ripristino account. Runtime invariato; nessun bump SemVer o avanzamento attribuito.

- **0.64.17-dev — SEC-P1, test-only (20/11/2026):** regressione Joomla sulla revoca immediata dell'accesso al roster e ai filtri dopo blocco account con identità di sessione obsoleta; ripristino DB garantito. Controlli runtime preesistenti, nessun bump SemVer; SEC-P1 ancora aperto e collaudo HTTP pendente.

- **0.64.17-dev — SEC-P1, PATCH (20/11/2026):** autorizzazione fresca per tutte le sette operazioni amministrative degli attestati: ricarica utente Joomla, controllo blocco account, permessi core.manage e jlms.certificates.manage su ogni richiesta. Corretto anche il controllo ACL della guida amministrativa variabili email; test Joomla negativo su account bloccato dopo il login per elenco, dettaglio, diagnosi, download, rigenerazione e recuperi, con ripristino DB. Gate strutturale contro nuovi metodi admin non controllati. Accettazione HTTP/manuale e audit SEC-P1 generale pendenti, minimo 57,48% invariato.

- **0.64.16-dev — SEC-P1, PATCH (20/11/2026):** `recipient_user_id` obbligatorio per tutti i nuovi eventi MailQueue; account Joomla e indirizzo ricontrollati prima di SMTP su tutti i template. Corretti i flussi di iscrizione, completamento quiz/video, approvazione, sospensione, attestati e promemoria; legacy senza identità scartati e minimizzati. Inventario dei 13 produttori SMTP, gate automatico in preflight e documentazione dei subscriber Joomla. SEC-P1/SEC-P5 aperti, collaudo manuale SMTP pendente, minimo 57,48% invariato.

- **0.64.15-dev — PATCH SEC-P1 (20/11/2026):** i nuovi messaggi corso della coda email conservano l'ID del destinatario Joomla e il dispatcher verifica account non bloccato e corrispondenza dell'indirizzo immediatamente prima di SMTP. Messaggi corso legacy senza ID vengono scartati, minimizzando indirizzo/nome/payload, anziché spedire senza controllo; gli altri messaggi con ID sono verificati allo stesso modo. Regressioni Joomla DB per indirizzo stale, utente inesistente e blocco account. Invio SMTP reale, matrice di sicurezza SEC-P1 e collaudo manuale pendenti; nessuna promozione di criterio, 57,48% invariato.

- **0.64.14-dev — SEC-P1, PATCH (20/11/2026):** restringe a `reassign` il dispatch amministrativo delle categorie (prima privo di override `execute`); controlli statici fail-closed su metodi pubblici e task consentiti dei 37 controller Joomla, prova negativa con classe distribuita nel test categorie e inventario di sicurezza per endpoint. ACL/HTTP/SMTP completi non dimostrati; SEC-P1/SEC-P5 aperti, minimo 57,48% invariato.

- **0.64.13-dev — PATCH SEC-P1 (20/11/2026):** il consenso opzionale confronta il destinatario con il record Joomla aggiornato; rifiuta email di sessione obsoleta e account bloccati per dashboard/iscrizione, usando nome/email canonici. Registrazione con attivazione pendente preservata. Regressioni Joomla, collaudo SMTP pendente; SEC-P1 ancora aperto, 57,48% invariato.

- **0.64.12-dev — PATCH DOC-07 (20/11/2026):** rilevate come `unknown` date `next_execution` impossibili o non canoniche, senza normalizzazione silenziosa PHP; test dei confini dei 10 minuti, nessuna mutazione o esecuzione dei task. DOC-07 e collaudo Joomla restano aperti, minimo 57,48% invariato.

- **0.64.11-dev — PATCH DOC-07 (20/11/2026):** la dashboard evidenzia le attività Scheduler duplicate anche quando il plugin o la singola attività sono disabilitati, per non nascondere il rischio alla successiva riattivazione. Test delle combinazioni di stato; nessuna azione automatica sui task. DOC-07 rimane parziale e il collaudo Joomla è pendente. Copertura minima 57,48% invariata.

- **0.64.10-dev — SEC-P1 (20/11/2026, test-only):** regressione parallela su due processi Joomla e due connessioni InnoDB che tentano la riconciliazione `sending→sent` dello stesso record; attesa una sola transizione e un conflitto 409, senza perdita dei dati di audit. Nessun bump SemVer né mutazione runtime. Test web/SMTP reale ancora pendente.

- **0.64.10-dev — PATCH SemVer (SEC-P1, 20/11/2026):** ridotta la persistenza ridondante del testo libero in coda email: la nota resta nel campo audit `reconciliation_note` e `last_error` conserva solo il marker generico. Regressioni Joomla su riconciliazione `sent/retry`, secondo tentativo rifiutato e redazione dei destinatari nel caso `sent`. Nessuna modifica a schema, SMTP, idempotenza o autorizzazioni; collaudo simultaneo e SMTP ancora pendenti.

- **0.64.9-dev — PATCH SemVer (SEC-P1, 20/11/2026):** separato l'accesso alla coda email globale dal semplice `core.manage`: sono ora richiesti anche i diritti Joomla preesistenti `jlms.audit.view` sia per i diagnostici sia per la riconciliazione; shortcut dashboard soggetto al medesimo permesso. Fixture Joomla prova delega/negazione/concessione/revoca senza effetti sui messaggi. Gli operatori non Super User che usavano la coda devono ricevere esplicitamente `jlms.audit.view` in ACL, secondo principio di minimo privilegio. Nessuna modifica schema; accettazione HTTP/SMTP pendente.

- **0.64.8-dev — SEC-P1 (20/11/2026, regressione senza modifiche runtime):** test Joomla di isolamento cross-course per comunicazioni ai corsisti: destinatari di altro corso, URL e POST manipolati, guest/studenti, nessun nuovo job o outbox dopo il rifiuto. Gate di sicurezza complessivo ancora aperto e collaudo manuale da effettuare; SemVer e percentuale funzionale invariati.

- **0.64.8-dev — PATCH SemVer (SEC-P1, 20/11/2026):** il controllo centrale `TeacherProfiles::canEdit()` ricarica l'account Joomla anziché fidarsi della sessione precedente, impedendo di considerare ancora editabile un profilo docente dopo il blocco account. Test negativo e positivo su fixture DB Joomla; nessuna migrazione o variazione dei permessi. SEC-P1 rimane aperto e il collaudo manuale è pendente.

- **0.64.7-dev — PATCH SemVer (SEC-P1, 20/11/2026):** la riassegnazione amministrativa delle categorie ricarica l'identità Joomla corrente prima dei controlli di blocco account e ACL, impedendo che un oggetto utente di sessione obsoleto autorizzi un'operazione dopo il blocco. Nuova regressione DB Joomla su inspect/reassign, rifiuto 403 e assenza di modifica dati; senza migrazione. SEC-P1 non chiuso, collaudo manuale pendente.

- **0.64.6-dev — SEC-P1 (20/11/2026, CI/test-only):** introdotto test di integrità del censimento dei controller frontend/amministrativi e dei controlli strutturali metodo/CSRF, richiamato dal preflight. Un nuovo controller non classificato non deve passare inosservato; test manuali ACL/IDOR ancora obbligatori. Nessun cambio runtime, pacchetto, SemVer o avanzamento funzionale.

- **0.64.6-dev — PATCH SemVer (SEC-P1, 20/11/2026):** prevenuta la possibile trasmissione dell'URL con token monouso di conferma consenso come Referer sul redirect, mediante `Referrer-Policy: no-referrer` e `X-Robots-Tag: noindex, nofollow`; `Cache-Control: no-store` confermato. Aggiunta regressione strutturale; inventario trasversale delle superfici da sottoporre a SEC-P1. Nessuna modifica schema, token, invio email, consenso, cookie o criteri funzionali. Collaudo HTTP Joomla pendente.

- **0.64.5-dev — SEC-P1 (20/11/2026, test senza modifica runtime):** estesa la fixture Joomla degli attestati con tre tentativi negativi di download amministrativo (proprietario, altro studente, ospite) e una prova positiva con operatore dotato dei permessi specifici. SEC-P1 resta aperto; CI e accettazione manuale da confermare; SemVer e avanzamento funzionale invariati.

- **0.64.5-dev — PATCH SemVer (SECDEV-03, 20/11/2026):** rifiutati i marcatori Unicode bidirezionali nei nomi dei file di materiali e video, per impedire la presentazione ingannevole delle estensioni anche nei download autorizzati. I controlli precedenti (nome, contenuto/MIME, dimensioni, ACL) restano invariati. Test negativi per override, isolate e marcatori direzionali e positivo per accenti Unicode. Nessuna promozione automatica di SECDEV-03, che richiede una verifica di sicurezza trasversale.

- **0.64.4-dev — PBASE-03 (20/11/2026, guide/test):** guide per amministratore, autore e sviluppatore dei provider video; esempio Joomla documentale del contratto di registrazione con manifest, service provider, subscriber e adapter deliberatamente non riproducibile. Test CLI del registry e dei metadati del manifest. Nessuna nuova estensione distribuita, nessuna modifica runtime e nessun aumento SemVer. Registro PBASE-03/criterion_04 riconciliato con test: 454 implementati, 35 mancanti, 268 non verificabili e minimo 57,48%.

- **0.64.4-dev — PBASE-03, riconciliazione audit dopo PR #762 (20/11/2026, solo evidenze):** il criterio di blocco della build in mancanza delle guide plugin video è ora attestato da test positivi e negativi e classificato implementato nel registro canonico, con report e dashboard sincronizzati. Nessun incremento SemVer e nessuna accettazione Joomla presunta.

- **0.64.4-dev — PATCH SemVer (PBASE-03, 20/11/2026):** il builder del pacchetto Joomla ora rifiuta il rilascio se manca la guida README di un provider video base, mancano sezioni contrattuali o parametri presenti nel suo manifest. Gate indipendente dalla CI, con test negativi su build isolata; nessuna modifica a riproduzione, iscrizioni o dati. Non dichiara completata la documentazione degli altri ruoli né l'accettazione reale Joomla.

- **0.64.3-dev — PATCH SemVer (DOC-07, 20/11/2026):** la diagnostica task JD Learn segnala le regole di esecuzione JSON mancanti, malformate o prive di tipo come `invalid_rules` invece di dichiarare il task operativo sulla sola base della prossima esecuzione. Messaggi di rimedio IT/EN e prove PHP dei casi validi e invalidi. Non cambia configurazioni/task, non avvia job e non attribuisce nuovi criteri funzionali.

- **0.64.2-dev — PATCH SemVer (DOC-07, 20/11/2026):** la diagnostica già disponibile dei quattro provider nella dashboard amministrativa ora mostra istruzioni operative localizzate per plugin mancante, disabilitato, non registrato, configurazione locale incompleta o configurazione rilevata. Nessuna attivazione automatica, invio di credenziali o prova di disponibilità remota implicita. Test di mapping degli stati e controllo IT/EN; DOC-07 resta parziale e il collaudo Joomla pendente.

- **0.64.1-dev — PBASE-02 (20/11/2026, solo documentazione/test):** aggiornate le quattro guide README locali ai plugin video per descrivere campi manifest, capacità effettive, limiti e prove richieste; gate statico in CI contro deriva documentale. Nessuna modifica runtime, nessuna promozione di criteri dell'audit, nessun aumento SemVer.

- **0.64.1-dev — PATCH SemVer (CAT-01, 20/11/2026):** la ricerca testuale nel catalogo confronta letteralmente i caratteri speciali `%`, `_` e `!` invece di interpretarli come jolly SQL, evitando corrispondenze estranee alla richiesta. Parametri SQL ancora vincolati, escape esplicito compatibile con MySQL/MariaDB; nuove regressioni PHP e DB Joomla. Nessuna migrazione o alterazione dell'indicizzazione; collaudo manuale pendente.


- **0.64.0-dev — MINOR SemVer (DOC-07, 20/11/2026):** diagnostica operativa dei task schedulati nella dashboard Joomla: suggerimenti IT/EN distinti per task assente, disabilitato, duplicato, fallito, in ritardo, in attesa e operativo. Il collegamento a Gestione attività pianificate compare solo con `core.manage` su `com_scheduler`; nessun task viene modificato o avviato automaticamente. Regressione per i messaggi e ACL; collaudo IT/EN sul sito di prova pendente.


- **0.63.4-dev — PATCH SemVer (CAT-02/STU-01, 20/11/2026):** il catalogo sceglie l'ultima iscrizione **per ogni corso visibile** prima di limitare a 50 i risultati. Storici con molte edizioni dello stesso corso non nascondono più l'iscrizione a corsi diversi né mostrano un'azione d'iscrizione ingannevole. Nuova regressione Joomla DB con 51 edizioni, due corsi e isolamento degli utenti; nessuna migrazione, cambiano solo la proiezione di stato delle schede catalogo.


- **0.63.3-dev — PATCH SemVer (STU-01, 20/11/2026):** Riprendi nel riepilogo personale cerca a pagine di 50 iscrizioni, anziché fermarsi alle prime 50 attività anche se tutte inaccessibili; conserva i controlli ACL, lingua, edizione e disponibilità a ogni blocco. Nessuna modifica dati/schema/API, test strutturale anti-regressione; prova Joomla con oltre 50 iscrizioni ancora da eseguire.


- **0.63.2-dev — PATCH SemVer (JOO-01/REL-03, 19/11/2026):** eliminata la seconda copia erronea del plugin Action Log dallo ZIP di installazione; builder e gate metadati ora rifiutano nomi ZIP duplicati sia nel pacchetto che negli archivi figli e verificano l'esatta corrispondenza con il manifest Joomla. Nessuna nuova estensione, schema o modifica ai dati; il collaudo di installazione/upgrade sul sito reale resta pendente.

- **0.63.1-dev — PATCH SemVer (QUI-02, 19/11/2026):** la cancellazione definitiva delle domande è ora rifiutata dal servizio se lo stato non è cestinato (`-2`), anche tramite richiesta POST diretta con permessi di eliminazione. Invariati ACL, CSRF, protezione tentativi e cancellazione atomica; gate statico e collaudo Joomla per bozza, ripristino e purga da cestino. Collaudo manuale pendente.

- **0.63.0-dev — MINOR SemVer (STA-02, 19/11/2026):** pre-avvio quiz con chiusura effettiva delle finestre di iscrizione, edizione, lezione e calendario corso; rifiuto esplicito per tempo residuo insufficiente, senza troncamento silenzioso e senza consumo tentativi. Mostrati durata richiesta, secondi disponibili e limite UTC prima dell'avvio. Estesi i test Joomla ai limiti esatti, calendari, durata individuale autorizzata e rollback; collaudo manuale pendente.

- **0.62.0-dev — MINOR SemVer (CORE-17, 19/11/2026):** plugin nativo Joomla Action Log installato e abilitato al primo impianto; logging delle transizioni workflow corso soltanto dopo commit, senza duplicare gli audit delle iscrizioni né divulgare dati didattici. Pacchetto ampliato da 11 a 12 estensioni, manifest e test di packaging aggiornati. Collaudo manuale Joomla pendente.

- **0.61.0-dev — MINOR SemVer (LIFE-01, 19/11/2026):** flusso di anteprima dell'impatto per cancellazione definitiva di corso cestinato, con conteggio delle dipendenze e revisioni editoriali, conferma POST e controllo di revisione sotto transazione. Cancellazione bloccata per storico o referenze, ACL/CSRF preservati; collaudo Joomla pendente.

- **0.60.9-dev (DOC-06, 19/11/2026):** 13 descrizioni di campo IT/EN aggiunte con gli attributi nativi dei form Joomla nel corso: immagine, destinatari, obiettivi, difficoltà, ACL promozionale/didattico, SEO e date. Gate automatico sulle chiavi, nessuna modifica a dati, default o validazioni. DOC-06 ancora parziale, collaudo Joomla pendente.

- **0.60.9-dev (DOC-08, 19/11/2026):** prima matrice di tracciabilità di dieci flussi core verso guide prodotto e aiuti contestuali IT/EN; controllo automatico dei link integrato nel gate esistente. DOC-08 parziale, collaudo reale pendente, nessun incremento SemVer o avanzamento arbitrario.

- **0.60.9-dev — PATCH SemVer (MAIL-08, 19/11/2026):** corretto il possibile doppio accodamento delle email di prova dopo un refresh della schermata Comunicazioni. La nuova anteprima assegna un UUID v4 persistito in sessione; le successive aperture e gli invii ripetuti della stessa anteprima riusano la chiave dell'evento, già protetta dall'indice univoco dell'outbox. Nuova anteprima esplicita = nuova operazione autorizzata. Nessuna modifica a schema, contratti pubblici o template globali. Regressioni nella CI, accettazione Joomla/SMTP ancora da effettuare.

- **0.60.8-dev (JOO-04, 19/11/2026):** diagnostica in sola lettura delle tre attività Joomla Task del componente (quiz, pulizia materiali, invio email) nella dashboard amministrativa; stato assente/disabilitato/in attesa/operativo/fallito/in ritardo/duplicato, ultima e prossima esecuzione, soglia di tolleranza di 10 minuti sul next_execution core Joomla. Nessuna modifica allo Scheduler o invio automatico. Test di classificazione deterministico IT/EN.

- **0.60.8-dev (DOC-05, 19/11/2026):** collegati tutti i 58 aiuti contestuali backend a 29 capitoli IT e 29 capitoli EN di due manuali amministratore offline versionati nel pacchetto. Contratto CI verifica automaticamente URL relativi, lingua e ancore, anche per future nuove viste. Verifica statica completata; prova Joomla manuale di navigazione, ACL e accessibilità ancora pendente.

- **0.60.8-dev (DOC-05, 19/11/2026) — censimento e copertura totale delle viste amministrative:** 19 viste residue dotate di pulsante Joomla Guida e 38 pagine offline IT/EN; copertura sorgente complessiva 29/29 viste, 58/58 capitoli locali. Test di regressione dinamico per nuove viste, file mancanti e interruzione del rendering; inventario in `docs/ADMIN_HELP_COVERAGE.md`. Restano da collaudare la risoluzione reale dei link e l'accessibilità su Joomla, e i rimandi ai capitoli del manuale completo.

- **0.60.8-dev (DOC-05, 19/11/2026) — guida contestuale backend, seconda tranche:** sette nuovi pulsanti Guida nativi nelle schermate Programma, dashboard amministrativa, Coda email, Variabili email, Comunicazioni corso, Attestati e Dettaglio attestato; 14 nuove pagine offline IT/EN. Esteso il gate DOC-05 a 10 viste e 20 pagine. DOC-05 rimane parziale fino alla copertura completa, ai collegamenti ai manuali e all'accettazione Joomla.

- **0.60.8-dev (DOC-05, 19/11/2026):** prima tranche di guida contestuale Joomla offline nelle viste Corsi, Corso e Iscritti, con pagine IT/EN nel pacchetto. DOC-05 resta parziale, collaudo manuale pendente.

- **0.60.8-dev (UI-01/FE-10, 19/11/2026):** aggiunti inventario e gate statico per le viste pubbliche anteprima lezione e revoca consenso email, inclusi contratti DOM e token POST/CSRF. Solo contratti/documentazione, senza modifica runtime o accettazione manuale.

- **0.60.8-dev (SCR-01, 19/11/2026):** suite HTTP su Joomla isolato per stato dei filtri condivisi, navigazione URL ripetuta, reset alla prima pagina dopo filtro, opzioni ordinamento Categoria e reset contestuale. Nessuna variazione runtime né accettazione manuale implicita.

- **0.60.8-dev (SCR-01, 19/11/2026):** filtri e ordinamenti coerenti nella vista Categoria, compresa disponibilità, ordine editoriale/data/durata; azzera filtri contestuale e paginazione del catalogo guidata da `limitstart` dell'URL, con reset alla pagina iniziale dopo invio di nuovi filtri. Test strutturali e collaudo Joomla pendente.

- **0.60.8-dev (SCR-01/sorts, 19/11/2026):** posizione editoriale 0–9999 nella scheda corso; nuova opzione Ordine editoriale predefinita, posizioni crescenti, non assegnati in coda, tie-break ID stabile. Opzioni menu Corsi/Categoria e impostazioni globali IT/EN. Nessuna migrazione SQL; test strutturali aggiunti e collaudo Joomla pendente.

- **0.60.8-dev (SCR-01/empty_error, 19/11/2026) — errore catalogo recuperabile:** in caso di errore backend durante il caricamento, il catalogo mostra avviso localizzato IT/EN e Riprova all'URL corrente, conservando filtri e contesto. Non mostra risultati parziali né dettagli tecnici; 401/403/404 rimangono errori di accesso. Nessun cambio schema; collaudo manuale Joomla pendente.

- **0.60.8-dev (SCR-01, 19/11/2026) — ordinamento per pubblicazione:** catalogo ordinabile anche per data di pubblicazione decrescente della versione corrente (`v.published_at`), con ordinamento deterministico per ID a parità di data. Opzione globale e menu corsi/categoria, traduzioni IT/EN e gate strutturale. Invariati ACL, filtri, paginazione e valore predefinito; accettazione Joomla reale da eseguire.

- **0.60.8-dev (SCR-01/card, 19/11/2026) — richieste d'iscrizione pendenti nelle card catalogo:** le schede dei corsi con iscrizione ad approvazione mostrano la richiesta personale pendente invece di proporre una nuova richiesta. La proiezione controlla proprietario, versione corrente e soli corsi visibili nella pagina con una singola query limitata, senza interrogazioni per ogni scheda e senza divulgare dati di altri iscritti. Stato di iscrizione già attiva prioritario; ACL, disponibilità e pagina di dettaglio invariati. Test strutturali inclusi, collaudo Joomla manuale pendente.

- **0.60.8-dev (EDGE-01, 19/11/2026) — attestati protetti dopo blocco account Joomla:** elenco e download degli attestati verificano lo stato corrente dell'utente nella tabella core Joomla, indipendentemente dall'oggetto di sessione potenzialmente obsoleto. Un account bloccato o rimosso non accede agli attestati tramite vecchi URL; risposta HTTP 403 localizzata. Riattivazione ripristina accesso ai propri documenti senza modificare seriali, snapshot o PDF. Test integrazione Joomla con blocco successivo al login; QA manuale ancora da eseguire.

- **0.60.8-dev (SCR-07, 19/11/2026) — avviso per risposte quiz non confermate:** aggiunta guardia `beforeunload` condizionata a risposte modificate rispetto all'ultimo salvataggio server o a mutazioni ancora in corso/incerte. Nessuna persistenza locale né salvataggio automatico in uscita. La consegna con esito confermato consente il redirect interno senza prompt; test JS verificano stato pulito, modifica e conferma di salvataggio. Il browser mostra un testo generico non personalizzabile. Collaudo Joomla/browser manuale pendente.

- **0.60.8-dev (SCR-04, 19/11/2026) — percentuale dei percorsi solo con base completa:** la percentuale aggregata appare soltanto se il discente autenticato possiede un'iscrizione e regole di completamento valide per ciascun corso necessario. I corsi non visibili, senza iscrizione o senza regole non vengono silenziosamente conteggiati come zero nella percentuale. I corsi di sola consultazione con regole valide possono essere esclusi dal denominatore. L'elenco del percorso e i relativi controlli di accesso rimangono invariati. Nessuna nuova iscrizione, modifica di storico o migrazione schema; test Joomla reale pendente.

- **0.60.8-dev (CAT-02/SCR-03, 19/11/2026) — azioni contestuali della scheda corso:** iscrizioni personali già esistenti visualizzano Inizia/Riprendi/Rivedi solo quando il controllo di accesso corrente consente la fruizione. Se l'iscrizione non è disponibile, la scheda indica il vincolo senza proporre una seconda iscrizione. Le richieste di approvazione pendenti della versione pubblicata mostrano uno stato informativo senza duplicare il POST; nuovi studenti con accesso possono scegliere Iscriviti e inizia (automatico) o Richiedi accesso (approvazione). Ospiti rimandati al login. Test strutturale e casi Joomla; accettazione manuale pendente, nessun cambio schema.

- **0.60.8-dev (CAT-01, 19/11/2026) — filtro corsi disponibili:** il catalogo offre la scelta facoltativa di mostrare soltanto corsi pubblicati e visibili con ammissioni aperte ed erogazione abilitata. Il filtro è applicato nella query ACL già esistente; disattivato per impostazione predefinita e ignorato se i filtri del catalogo sono disabilitati. Traduzioni IT/EN; collaudo Joomla da eseguire. Nessuna migrazione di schema.

- **0.60.8-dev (SCR-01/card, 18/11/2026) — stato personale nelle schede catalogo:** per utenti autenticati, la card riusa i controlli della dashboard personale per mostrare progresso e disponibilità dell'aula; propone Riprendi/Rivedi solo con accesso consentito e didattica erogabile, altrimenti conserva il link ai dettagli. Guest senza dati personali. Stato di richiesta di approvazione non convertita in iscrizione resta escluso e da trattare separatamente. CI e collaudo Joomla distinti.

- **0.60.8-dev (SCR-01/card, 18/11/2026) — docenti nel catalogo:** le card mostrano i docenti associati alla versione pubblicata solo se il profilo congelato supera le verifiche di visibilità correnti (ACL, stato e lingua). Nomi e link a scheda docente, nessun dato account privato; fallback vuoto per snapshot storici malformati. Collaudo Joomla ancora pendente.

- **0.60.8-dev (SCR-01, 18/11/2026) — azione primaria sulle schede catalogo:** ogni scheda mostra un link localizzato ai dettagli, senza avviare iscrizioni o aggirare i controlli della pagina corso. La modalità di iscrizione viene indicata solo se le ammissioni e l'erogazione sono attive; il catalogo non deduce lo stato individuale di iscrizione. Tests frontend/lingue; collaudo Joomla pendente.

- **0.60.8-dev (SCR-01, 18/11/2026) — ordinamento per durata:** disponibile nel catalogo e nelle opzioni predefinite globali/menu l'ordinamento crescente per durata stimata. Valore ricavato dalla versione pubblicata del corso, con durate non definite in fondo e paginazione deterministica; i filtri di accesso/lingua non cambiano. Test di regressione strutturale aggiunti; collaudo Joomla pendente.

- **0.60.8-dev (SCR-01, 18/11/2026) — azzeramento filtri catalogo:** in assenza di risultati con filtri attivi viene mostrata un'azione localizzata «Azzera filtri», che ripristina la ricerca senza cancellare l'eventuale categoria di partenza (catid). Controllo strutturale aggiunto; accettazione Joomla pendente e nessun incremento percentuale arbitrario.

- **0.60.8-dev (SCR-05, 18/11/2026) — navigazione dallo stato vuoto:** nella vista personale «I miei corsi» il messaggio senza risultati include un collegamento localizzato al catalogo, senza duplicare il link standard quando sono presenti card. Nessuna variazione a iscrizioni, ACL, schema o SemVer; collaudo Joomla pendente.

- **0.60.8-dev (CAT-02, PR #657, 18/11/2026) — regole di completamento nella scheda pubblica:** proiezione sicura della policy della versione pubblicata (lezioni obbligatorie, sequenza, soglia video, eventuale attestato previsto) con testi IT/EN e nota sulle condizioni di emissione reale. La consultazione non promette attestati. Nessuna esposizione dei contenuti riservati o modifica alla didattica; test Joomla esteso, collaudo manuale pendente.

- **0.60.8-dev (QUI-02, PR #653, 18/11/2026) — cestino banca domande:** nuove azioni Cestina/Ripristina con CSRF e ACL `core.edit.state`, filtro Cestinate e preservazione delle revisioni immutabili, anche se già usate nei quiz. Ripristino conservativo come bozza; eliminazione definitiva solo dal cestino e bloccata per riferimenti. Test Joomla estesi; collaudo manuale pendente.


- **0.60.8-dev (STA-02, PR #648, 18/11/2026) — guardia sull'avvio dei quiz a tempo:** prima di creare un tentativo, il server verifica che la durata intera rientri nella prima scadenza applicabile tra lezione, corso, edizione e iscrizione. Se insufficiente, restituisce la condizione di finestra didattica chiusa senza creare tentativi e il DTO non propone l'avvio. Nessun troncamento implicito né modifica ai tentativi già salvati. Test di regressione Joomla, collaudo manuale pendente.

- **0.60.8-dev (MAIL-08, PR #646, 18/11/2026) — invio test idempotente:** l'invio di prova di una comunicazione usa un identificativo UUID v4 per la richiesta, passato dal form al controller e utilizzato per l'evento univoco in outbox; un doppio POST con lo stesso identificativo non genera una nuova riga. Una nuova anteprima consente una prova volontaria distinta. Nessun cambiamento allo Scheduler, ai destinatari o allo schema; collaudo manuale Joomla/SMTP pendente.

- **0.60.8-dev (18/11/2026, PR #643; NOT-01 / MAIL-02) — notifica completamento da quiz:** l'ultimo quiz obbligatorio che completa il corso accoda `com_jlms.course.completed` con la stessa chiave idempotente dei percorsi lezione/video e con tutti i token canonici. La notifica entra nella transazione di progresso; i reinvii non duplicano l'evento. Test su Joomla disposable, accettazione SMTP reale pendente. Nessuna migrazione schema.

- **0.60.8 (18/11/2026) — refresh Smart Search dopo archiviazione/ripristino workflow:** `PublicationService::transition()` notifica nuovamente il Finder dopo il commit per le azioni Archive/Restore, con stato del corso già salvato. La notifica integra gli eventi nativi del workflow, che possono avvenire dentro la transazione. Errori nell'indice non annullano una transizione confermata. Test Joomla controlla stato e flag operativi osservati dagli eventi, PATCH senza migrazioni; collaudo E2E Finder pendente.

- **0.60.7 (18/11/2026) — Smart Search dopo cestinazione e ripristino corsi:** le transizioni di stato massive di `CourseModel::changeStates()` emettono la notifica Finder `onFinderAfterSave` solo dopo il commit, richiedendo l'aggiornamento dell'indice in base allo stato realmente salvato. L'eventuale indisponibilità di Smart Search è registrata nei log e non annulla il salvataggio del ciclo di vita. Test Joomla con listener e verifica di stato/flag osservati post-commit. PATCH senza schema; accettazione manuale e prova E2E Finder ancora pendenti.

- **0.60.6 (18/11/2026) — Messaggio di sospensione didattica nei percorsi:** le schede dei percorsi formativi mostrano il messaggio localizzato di erogazione sospesa anziché il messaggio generico di contenuto riservato, solo per i corsi a cui l'utente ha diritto di visibilità. Gli utenti con accesso riservato continuano a ricevere il messaggio generico, senza esposizione dello stato operativo. Regressione Joomla e verifica statica del template; nessuna migrazione schema e collaudo manuale pendente.

- **0.60.5 (18/11/2026) — Disponibilità dei corsi nei percorsi:** quando un corso resta pubblicato nel catalogo ma la didattica è sospesa (`delivery_enabled=0`), la scheda del percorso formativo non lo presenta più come fruibile. L'accesso è indicato come non disponibile, senza cancellare iscrizioni o alterare gli stati di avanzamento registrati. Al ripristino esplicito dell'erogazione torna l'indicatore normale. Test Joomla sulla scheda percorso e ripristino, PATCH senza schema; accettazione manuale pendente.

- **0.60.4 (18/11/2026) — Cestino corsi, chiusura conservativa degli accessi:** la cestinazione di un corso imposta `admissions_open=0` e `delivery_enabled=0` nella stessa transazione del passaggio a `state=-2`. Il ripristino allo stato Non pubblicato preserva entrambi i blocchi e la versione didattica. Il test Joomla verifica entrambi i flag prima e dopo ripristino e l'integrità dello storico, senza modifiche allo schema; accettazione manuale pendente. PATCH LCM-02/LCM-01.

- **0.60.3 (18/11/2026) — Gate LCM-01 sulle singole iscrizioni:** la riattivazione di un'iscrizione sospesa è consentita solo se il corso è Pubblicato e l'erogazione didattica è abilitata. L'anteprima e la conferma sotto lock del corso bloccano archiviati, non pubblicati, cestinati e corsi con didattica sospesa; sospensione e annullamento delle iscrizioni rimangono disponibili secondo le regole preesistenti. Test Joomla senza nuove scritture né notifiche per richieste respinte. PATCH senza schema; collaudo manuale pendente.

- **0.60.2 (18/11/2026) — Gate LCM-01 sulle operazioni amministrative:** la riapertura delle iscrizioni e la ripresa delle lezioni non sono consentite finché il corso non è nello stato Pubblicato. Questo impedisce di riarmare in anticipo un corso archiviato, non pubblicato o cestinato e di riaprire implicitamente gli accessi al successivo ripristino. La sospensione resta consentita. Regressione Joomla su anteprima, applicazione e audit, senza modifiche allo schema; accettazione manuale pendente.

- **0.60.1 (18/11/2026) — Ripristino archivio senza riapertura automatica:** archiviare un corso chiude ammissioni e fruizione, conservando le versioni e lo storico; il ripristino editoriale allo stato Pubblicato mantiene entrambi i blocchi fino a un'azione amministrativa esplicita. Il gate di fruizione di LearningService mantiene il rifiuto HTTP 403 quando delivery_enabled è 0. Test Joomla delle transizioni e della protezione dei link diretti. Correzione PATCH senza modifiche allo schema.

- **0.60.0 (18/11/2026) — Purga condizionata dei corsi:** comando irreversibile disponibile solo nel cestino, su un singolo corso per volta, con controllo POST/CSRF, `core.delete`, check-out e verifica transazionale di versioni didattiche, iscritti, percorsi, docenti, email, operazioni e cronologia. Nessuna cancellazione a cascata: si rifiuta ogni record con dipendenze protette. Test Joomla negativi e richieste HTTP dirette; accettazione su installazione reale pendente.

- **0.59.0 (18/11/2026) — Cestino corsi:** azioni native di cestinazione e ripristino, singole/massive (100 max), POST/CSRF, ACL Joomla per corso e lock transazionale con rollback; filtri Cestinati e badge coerente. Il ripristino torna a Non pubblicato senza riaprire automaticamente iscrizioni o accessi; versioni, progressi e attestati non vengono cancellati. Cancellazione fisica ancora vietata. Fixture Joomla e HTTP ampliate, accettazione manuale pendente.

- **0.58.0 (18/11/2026) — Stati dei corsi in amministrazione:** aggiunti filtro Archiviati e badge coerente nella lista Corsi; verifica su fixture Joomla del filtro stato 2 e rifiuto di input stato non autorizzato. Non vengono introdotti cestino o cancellazione definitiva. Versione MINOR perché la capability amministrativa è nuova e retrocompatibile.
- **Audit funzionale 1.0.0 — settima tranche (18/11/2026):** censiti 22 ulteriori requisiti relativi a UI, accessibilità e performance (49 criteri, 20 evidenze iniziali e 29 punti ancora da valutare). Audit progressivo 129/214 requisiti; nessuna modifica runtime, SemVer invariato e nessuna percentuale globale inventata.

- **Audit funzionale 1.0.0 — sesta tranche (17/11/2026):** aggiunti 34 requisiti SIC/PRI/CFG/CORE, 75 criteri (36 prime evidenze e 39 da approfondire). Registro a 107/214 requisiti. Nessun runtime modificato o avanzamento globale arbitrario, SemVer invariato.

- **Audit funzionale 1.0.0 — quinta tranche (17/11/2026):** aggiunti i criteri per NOT-01/02, MAIL-01–11, CERT-01–08 e JOO-01–05 (26 requisiti, 62 criteri: 30 con evidenze iniziali, 32 da approfondire). Audit progressivo 73/214 requisiti; nessun runtime modificato, nessuna modifica SemVer o percentuale di completamento globale non dimostrata.

- **Audit funzionale 1.0.0 — quarta tranche (17/11/2026):** scomposti LES-01–04, VID-01–08 e MAT-01–08 in 68 criteri (44 evidenze iniziali, 24 da approfondire). Totale audit 47/214 requisiti; nessuna percentuale globale anticipata, nessuna variazione runtime o SemVer.

- **Audit funzionale 1.0.0 — terza tranche (17/11/2026):** censiti criteri di accesso/iscrizione, area studente e avanzamento didattico (15 requisiti/47 criteri, 16 punti ancora da approfondire); copertura dell'audit ora 27/214 requisiti. Nessun cambiamento runtime o SemVer, nessuna nuova percentuale globale non dimostrata.

- **Audit funzionale 1.0.0 — seconda tranche (17/11/2026):** aggiunta la scomposizione con evidenze di CAT-01–04 e AUT-01–05 (42 criteri, di cui 11 ancora da approfondire), oltre agli LCM già in audit. Nessuna modifica runtime o SemVer; nessuna nuova percentuale globale finché tutta la baseline in perimetro non è riconciliata.

- **LCM-05 — transizioni HTTP autorizzate (17/11/2026):** il test HTTP Joomla usa il token CSRF nativo per archiviare, ripristinare senza pubblicazione automatica e ripubblicare esplicitamente un percorso, verificandone la visibilità pubblica. Nessun runtime modificato; restano ACL granulari e accettazione reale.

- **LCM-05 — gate HTTP/CSRF percorsi (17/11/2026):** introdotti test negativi via vero HTTP Joomla disposable sui sei task di transizione/cancellazione percorsi, distinguendo GET (405) e POST senza CSRF (403), e verifica della conservazione della lista. Nessuna modifica runtime né accettazione manuale; verifica dei permessi granulari e delle transizioni positive ancora aperta.

- **LCM-05 — PR #581 e #582 (17/11/2026, CI 4/4 entrambe):** ampliati i test Joomla disposable per URL diretti dei percorsi archiviati/cestinati anche come ospiti, conservazione degli stati dei corsi associati, rifiuto del ripristino e della cancellazione da attore non autorizzato, e verifica dell'immutabilità dei dati dopo il rifiuto. Solo test e documentazione, runtime invariato. Gate LCM-05 parziale; restano HTTP/CSRF, ACL per record, Finder/SEO, upgrade e collaudo manuale. Merge #581 `14ef72b95b07bdfc38295b7ea9a3c741ac5d16cd` e #582 `08a7a9bf72e46996d4aad3eb538291330dbd6275`.

- **LCM-04 — eliminazione protetta dei docenti:** aggiunta rimozione definitiva singola, esplicitamente confermata, soltanto per profili cestinati privi di corsi collegati e riferimenti nelle versioni didattiche congelate. Controlli ACL, CSRF e checkout, test Joomla di protezione storico e account nativi. Nessuna cancellazione a cascata; accettazione manuale differita.

- **LCM-04 — ciclo di vita docenti (prima tranche):** introdotti cestino e ripristino conservativo con filtro e toolbar Joomla, azioni multiple atomiche autorizzate e blocco di ripubblicazione diretta dal cestino. Account Joomla, docenze assegnate e snapshot formativi sono preservati; eliminazione definitiva ancora non disponibile.
- **LCM-03 — azioni massive e rimozione controllata dei percorsi:** selezioni multiple per pubblicazione, sospensione, archiviazione, cestinazione e ripristino con transazioni atomiche e ACL per record; cancellazione definitiva esplicita soltanto per un percorso già cestinato e senza corsi associati. Test di regressione Joomla per rollback e integrità relazioni, traduzioni e manuali aggiornati. Restano collaudo manuale e audit delle dipendenze esterne.

- **LCM-03 — cestino reversibile dei percorsi:** supportato lo stato `-2` con filtro amministrativo Cestinato, ripristino controllato a Non pubblicato e guardia contro la pubblicazione diretta dal cestino. Conservati corsi, iscrizioni e storico, nessuna eliminazione fisica. Test Joomla per transizioni e accesso pubblico; operazioni massive e cancellazione condizionata da completare.

- **LCM-03 — archiviazione percorsi formativi:** aggiunto stato Archiviato nell'editor e nell'elenco, mantenute relazioni e progressi, URL pubblico non disponibile per percorsi archiviati, ripristino tramite stato non pubblicato e pubblicazione esplicita. Test Joomla per le transizioni e la conservazione dati. Cestinazione ed eliminazione condizionata restano da implementare.

- **Collaudo video P0 #573 confermato (17/11/2026):** la riapertura di una lezione già completata riproduce regolarmente dall'inizio su Joomla reale. Aggiunto test di regressione frontend sul mancato seek per lezioni completate e sul resume invariato per quelle parziali; runtime non modificato.

- **P0 — replay di una lezione video già completata:** eliminata la ripresa automatica dalla posizione finale per le lezioni completate, mantenendo la ripresa delle lezioni parziali. Il grant indica esplicitamente `lesson_completed`, e il frontend salta il seek in questo caso. Conservati storico, copertura, stato e attestati; aggiunto test di regressione backend. Da confermare su Joomla reale.

- **P0 — rollback della PR #571:** ripristinato il ciclo di vita del player YouTube già collaudato nella PR #570, dopo segnalazione che i video non partivano su Joomla. Temporaneamente torna il riquadro di completamento sotto al player (non sostituzione in loco). La futura UI nello stesso spazio richiederà una PR separata con test di avvio e replay in browser reale.

- **PR #571 — YouTube e-learning:** dopo fine video **confermata dal backend**, l'iframe viene **rimosso** e sostituito nello stesso spazio 16:9 da «Lezione completata», link contestuale alla lezione successiva e «Rivedi il video». Il replay chiede una nuova sessione autorizzata e inizia da 0, senza recuperare il resume salvato in coda. Non copre il player e non altera le soglie/telemetria; test di regressione JS e collaudo Joomla descritti in `docs/QA_TESTBOOK.md`.

- Migliorata la concentrazione nelle lezioni YouTube: `rel=0` per raccomandazioni dello stesso canale (non eliminabili del tutto), riquadro nativo JD Learn **esterno** all'iframe quando una riproduzione conclusa è confermata come completata dal server e collegamento alla lezione successiva solo se autorizzata. Nessun overlay, nessuna alterazione del tracciamento o dei controlli del provider; gestione anche dopo rinnovo della sessione video. Test e collaudo Joomla documentati.

- **Issue #379 (P1)**: la build timbra automaticamente nei manifest dello ZIP del package e delle 11 parti la stessa versione, una `creationDate` esplicita e nomi `JD Learn - ...` uniformi; il workflow release riceve la data UTC e la CI verifica ogni ZIP interno. Nessun identificativo tecnico delle estensioni viene rinominato. Collaudo di visualizzazione in Gestione estensioni Joomla pendente.

- Aggiunto il recupero amministrativo puntuale degli attestati **mai emessi** per iscrizioni completate eleggibili (`eligible_missing`), con conferma, motivazione, verifica delle regole pubblicate, transazione, audit, protezione ACL/CSRF e divieto di duplicati. Separato dalla procedura eccezionale per versioni storiche disabilitate e dal rendering PDF `pending_render`; test di integrazione Joomla e smoke aggiunti.

- **PR #566 integrata e collaudo utente positivo (17/11/2026):** download dell'attestato precedentemente in errore HTTP 503 con logo PNG riuscito su Joomla 6.2.0. Restano da verificare formalmente stato `ready`, confronto snapshot/seriale, formati diversi dal PNG, ACL/HEAD e recupero di emissioni assenti. [CI 4/4](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701); [pacchetto](https://github.com/jeckodevelopment/jd_learn/actions/runs/44106325701/artifacts/14338442764).

- Corretto il 503 del primo download di attestati con logo PNG: il renderer PDF accetta JPEG, PNG e, quando supportati da GD, GIF/WebP/AVIF e compone i canali alpha su bianco; i documenti `pending_render` già emessi restano recuperabili senza nuova emissione. Test `tests/certificate-raster-logos.php` e collaudo Joomla richiesti.

- Eliminata la dipendenza dai file temporanei PHP (`sys_get_temp_dir()`/`tempnam`) nella generazione e rigenerazione degli attestati: il PDF generato viene salvato direttamente nello storage privato cifrato, con diagnostica per fase nei log Joomla (issue #564, PR #565).

- Storage gestito automaticamente per attestati e materiali su hosting condiviso: quando non è configurato un percorso esterno, JD Learn crea `media/com_jlms/private`, protegge i file con guardia PHP, access rules e cifratura AES-256-GCM, e permette il rendering `pending_render` senza configurazione manuale (issue #562, PR #563).

- Corretto il primo download dei PDF degli attestati storici: il rendering accetta timestamp di completamento SQL `DATETIME(6)` con microsecondi; copertura Joomla di transizione `pending_render` → `ready` e download ripetuti, senza fallback verso storage pubblico (issue #560).

- Corretta l'emissione amministrativa di attestati storici: i parametri tipizzati delle query Joomla sono ora associati tramite variabili, con prova integrata del percorso positivo, audit e blocco duplicati (issue #558; PR #559).

- Aggiunta emissione amministrativa esplicita e auditabile di attestati per completamenti storici privi del flag nella versione didattica, senza modificare snapshot né emettere automaticamente dopo l'upgrade (issue #553).

- Correzione pre-release: alla pubblicazione di nuove versioni didattiche, la regola di emissione attestati è derivata dalle preferenze globali/per corso e congelata nello snapshot; nessuna modifica retroattiva alle versioni pubblicate (issue #553).

- **PR #551 integrata — issue #547:** merge `2e9a65ab8d9044f9502950514c0a24419d04d03b`, CI completa 4/4 verde [run 44049557136](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136), gate CSV verde [run 44049557129](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557129); [pacchetto installabile JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44049557136/artifacts/14312389981), digest archivio Actions `sha256:01ebeb70fae56e88caa1cbe54ae5d4f6eb7d408e824b8a0a94d7167dfa32da25` (non checksum interno dello ZIP Joomla). Il ritentativo esplicito è possibile solo dopo riparazione di template per iscrizione e completamento con errore validato pre-SMTP, nota e conferma, mai per trasporto incerto; collaudo Joomla/SMTP e correzione reale del modello Rigoletto rimangono pendenti. Avanzamento globale 79,56% invariato.

- **MAIL-10 / issue #547 — retry manuale controllato (PR #551, CI e collaudo Joomla pendenti):** le sole notifiche `com_jlms.enrollment.confirmed` e `com_jlms.course.completed` in stato `failed` con categoria errore di validazione modello **prima dell'SMTP** possono essere riportate a `retry` dall'amministratore autorizzato, con conferma esplicita e nota audit (10–1000 caratteri), dopo la riparazione del template Joomla. Prima del cambio stato il server controlla template corrente, lingua, dati, nomi dei token e recapito; aggiornamento SQL atomicamente condizionato a stato/errore/tentativi. Non ritentare errori di trasporto, invii incerti, `sending` non riconciliati, record esauriti o altre categorie. Il task di invio Joomla resta l'unico mittente, nessun invio immediato dal pulsante. Avanzamento 79,56% invariato.

- **PR #545 (mail completamento video)** integrata al commit `f0415c45dd512797c4f5882d97d430c58bdc29b0`; CI 4/4 verde [run 44045664274](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274), gate CSV [run 44045664187](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664187), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44045664274/artifacts/14311024216). Token `COMPLETION_DATE` e `DASHBOARD_URL` ora disponibili nell'evento di completamento generato da `VideoProgressService`. Collaudo Joomla/SMTP ancora da eseguire.
- **PR #548 (diagnostica template mail, issue #547)** integrata al commit `12edaa91c2ba95e0ea159d897b6f9cabcb9d304e`; CI 4/4 verde [run 44046787217](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217), gate CSV [run 44046787244](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787244), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44046787217/artifacts/14311582918). Diagnostica admin dei token non risolti/assenza modello senza esporre contenuto delle email. Il modello `com_jlms.enrollment.confirmed` fallito in installazione Joomla di prova richiede controllo e correzione nell'installazione; nessun invio storico riaccodato automaticamente.
- **PR #549 (diagnostica attestati, issue #546)** integrata al commit `013267b788e2033fa5f11ef89bdf3b284c8d47d1`; CI 4/4 verde [run 44048059581](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581), gate CSV [run 44048060095](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048060095), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581/artifacts/14311889571). Lista protetta e limitata delle iscrizioni completate senza attestato, con diagnosi dei gate; nessuna emissione retroattiva forzata. Collaudo Joomla e causa precisa del corso Rigoletto ancora pendenti.

- **CERT-06 / P0 #546 — diagnosi attestati mancanti (PR #549, CI e collaudo Joomla pendenti):** nuova sezione in JD Learn → Attestati, accessibile solo con `core.manage` e `jlms.certificates.manage`, che mostra in sola lettura le ultime 25 iscrizioni realmente completate senza emissione completa. Espone la causa: regole della versione pubblicata senza rilascio attestati, preferenze globali/corso disabilitate, modello attestato assente, configurazione invalida oppure emissione attesa ma assente/documento non collegato. Nessuna emissione automatica retroattiva, modifica delle regole storiche, invio email o variazione della percentuale di completamento. Test nativi Joomla su ACL, limiti e categorie di esito. Avanzamento globale 79,56% invariato.

- **MAIL-10 — issue #547 (PR #548, test sito Joomla pendente):** nella Coda email amministrativa, per messaggi `failed`/`retry` classificati come errore modello, JD Learn ora rilegge in sola lettura il template Joomla effettivo della lingua della riga e mostra se è assente oppure i **nomi** dei tag canonici non disponibili (massimo 12). Non espone indirizzi, valori delle variabili, dati SMTP, token o eccezioni. Non riscrive i modelli personalizzati, non introduce alias legacy, non modifica lo stato della coda né riavvia gli invii. Verificare anche le varianti lingua e correggere esplicitamente i template obsoleti.

- **MAIL P0 — completamento video (PR in corso, 17/11/2026):** la notifica `com_jlms.course.completed` generata da `VideoProgressService` non forniva `COMPLETION_DATE` e `DASHBOARD_URL`, nonostante il modello standard Joomla li richieda. Corretto il produttore affinché fornisca entrambi i campi (data reale del completamento persistita e URL autenticato della dashboard), con test regressione. Il messaggio di iscrizione `com_jlms.enrollment.confirmed` presente nello screenshot è un evento distinto: il relativo produttore fornisce già i tag canonici, e la causa va verificata sul modello effettivo `#__mail_templates` personalizzato dell'installazione, evitando di sovrascrivere template amministrativi o reintrodurre alias. L'emissione attestati dipende dalle regole immutabili della versione pubblicata, dal consenso configurazione `certificate_enabled` globale/per corso e dalla presenza di un modello attestato attivo; verificare le precondizioni prima di parlare di regressione. Avanzamento 79,56% invariato.

- **PR #543 integrata (P0 YouTube #532):** merge `a695e4c24179b1933c3856c65f802baab1ab7deb`; CI completa 4/4 verde [run 43984520221](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221), gate CSV verde [run 43984520239](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520239). [Pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43984520221/artifacts/14285480650); digest dell'archivio Actions `sha256:21074ebe6c878571a47e907e05926edd7598d168b6a6b97f742daa1def8c78ad` (non checksum dello ZIP Joomla interno). Aggiunto il permesso `autoplay` all'iframe YouTube ma confermato `autoplay=0`: serve un click utente, consenso preservato. **La riproduzione e il completamento su installazione Joomla di prova sono da collaudare, issue #532 aperta**. Avanzamento 79,56% invariato.

- **P0 #532 — permesso riproduzione IFrame YouTube (PR #543, collaudo installazione Joomla di prova pendente):** il player JD Learn dichiarava `allow="encrypted-media; fullscreen; picture-in-picture"`, omettendo `autoplay` richiesto dagli esempi ufficiali Google per gli iframe YouTube. La configurazione ora delega il permesso `autoplay` all'iframe, senza attivare autoplay automatico (il parametro resta `autoplay=0`); SDK, consenso, `Referer`, ACL e calcolo dei progressi invariati. Test di regressione sull'attributo `allow` e sul parametro autoplay. L'eventuale blocco residuo va diagnosticato con evidenza dell'evento YouTube, non con ulteriori modifiche speculative. Percentuale globale 79,56% invariata.

- **PR #541 integrata — P0 #532:** merge `2ab0ffa2589a22013a26b2ad9ee7bcefe11c3fd6`; CI 4/4 verde [run 43983276857](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857), integrità CSV [run 43983276867](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276867). [Pacchetto installabile JD Learn 0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43983276857/artifacts/14283797967), digest archivio Actions `sha256:f9feaf56a8d380d2ee0c101304b243613c10402bd57554cfe2ef2e1878fcc24c` (non checksum interno del pacchetto ZIP Joomla). **Riproduzione video e completamento da verificare manualmente su installazione Joomla di prova prima di chiudere issue #532**. Avanzamento globale 79,56%, invariato.

- **P0 #532 — race YouTube IFrame API (PR #541, CI e test live pendenti):** l'adapter collegava `YT.Player` solo dopo `iframe.onload`, evento di navigazione della pagina YouTube e non garanzia di handshake dell'IFrame API. Ora la API viene collegata subito dopo l'inserimento dell'iframe nel DOM, prima del completamento della navigazione; referrer policy, dominio `youtube-nocookie.com`, ID video, consenso e telemetria restano invariati. Evidenza: l'utente vede il poster, Play resta su spinner in Safari e Chrome; YouTube diretto funziona. L'esito reale della modifica deve essere confermato sul sito di prova. Avanzamento globale 79,56% invariato.

- **PR #539 integrata (P0 #532; merge `bebffec3eb01b3eead93d6a8e96833b5d49eec6e`):** CI verifica completa 4/4 verde [run 43980525575](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575), gate CSV [run 43980525067](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525067). [Artefatto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43980525575/artifacts/14282058958), digest dell'archivio GitHub Actions `sha256:3f87d34fd71960a20bfc3adbf75d3a523c04c6c0f62eff795be57a95811c1713` (NON checksum dello ZIP Joomla interno). Il frontend serializza `lesson.begin` e `video.playback` per la stessa lezione/iscrizione; test automatici verificati. **Il collaudo reale su installazione Joomla di prova dell'avvio e del completamento video è pendente; issue #532 rimane aperta.** Avanzamento globale 79,56% invariato.

- P0 YouTube #532 (PR #539, CI e collaudo Joomla pendenti): ordinato l'avvio concorrente di `lesson.begin` e `video.playback` per stessa iscrizione/lezione per mitigare possibili conflitti DB all'apertura dell'aula. Test regressione di sequencing e isolamento fra lezioni; soglia di copertura, sessioni YouTube, consenso e ACL invariati. Persistenza dell'errore 500 e `video.save` da verificare sul sito; avanzamento 79,56% invariato.

- P0 YouTube #532 (PR #537 integrata, collaudo Safari live pendente): corretto il trasporto metadata dell'editor che su Safari poteva chiamare `fetch` con il receiver `VideoMetadataEditor` anziché con contesto nativo valido; aggiunto test regressione e allineati QA e VID-02. La chiamata manuale a `lesson.metadata` rispondeva con JSON valido nonostante MIME `text/html` (verifica separata ancora aperta). Nessun bypass ACL, CSRF, consenso o policy video. Merge `eb4cafa7b0df6aeffc726522eaea50b61c0bade8`; CI 43970838888 (4/4 verde). Il player YouTube reale non è ancora accettato.

- P0 YouTube #532 (PR #535 integrata, collaudo live pendente): sincronizzata la versione del registro asset WAM alla versione componente (in precedenza `0.45.0` anziché `0.57.7`), automatizzata una chiave di cache composta da versione e impronta degli asset (anche fra pacchetti con la stessa versione) e introdotte verifiche sullo ZIP installabile. L'editor distingue errori server HTTP 5xx, rete, risposta, modulo JS e player, senza esporre dettagli server. L'eventuale recupero dell'embed va verificato su Joomla reale.

- P0 YouTube (#532, PR #533 integrata, collaudo reale pendente): il preview editoriale mostra errori specifici per CSP, caricamento SDK, identificazione del sito/referrer e video non incorporabile, oltre ai messaggi 4xx locali autorizzati; i dettagli 5xx restano nascosti. Adeguata la Referrer-Policy dell’iframe a quella documentata da YouTube. Nessun bypass di consenso, ACL o requisito durata; collaudo reale ancora pendente.

- AUT-03 (PR #530 integrata): il controllo preliminare segnala trascrizioni editoriali assenti in lezioni audio/video come avvisi non bloccanti; aggiunti messaggi IT/EN e test, senza confondere trascrizioni con caption del provider.

- FE-12 (PR #528 integrata, CI 4/4): corretto il 404 della directory Docenti includendo `teachers` nella allowlist del controller frontend, aggiunto test regressione; estesi i controlli di rendering Joomla per i cataloghi pubblici categorie, percorsi e docenti; nessuna modifica di prodotto, gate manuale ancora aperto.

- FE-12 (PR #526 integrata, gate strutturale, collaudo pendente): verifica CI contratti frontend (menu, viste, router, WAM, CSS, private SEO) e matrice di accettazione manuale; nessuna dichiarazione di conformità WCAG o collaudo Joomla. Gate finale ancora aperto.

- FE-11 (PR #524 integrata): allineate opzioni globali e override per-menu su categorie e catalogo, inclusi filtri/ordinamento/paginazione e sezioni categoria. Contratti ACL e URL non ampliati; collaudo Joomla pendente.

- FE-09 (PR #522 integrata): nuova directory pubblica docenti con menu Joomla IT/EN, profili paginati e schede responsive riusate, selezione server-side ACL/lingua/categorie senza dati degli account. CI e collaudo Joomla pendenti.

- FE-08 (PR #520 integrata): opzioni indipendenti per voce menu Joomla per mostrare sottocategorie e corsi nella stessa pagina categoria; default Sì, paginazioni distinte, schede CSS riusate, ACL e filtri mantenuti. Collaudo Joomla pendente.

- FE-07 (PR #518 integrata): catalogo percorsi con copertina dedicata facoltativa e intro, editor Joomla Media con alt/decorativa e salvataggio in params_json; dettaglio con timeline verticale, progressi autorizzati senza iscrizione aggregata, nuovo menu dettaglio. Test e collaudo Joomla pendenti.

- FE-06 (PR #516 integrata): navigazione interna facoltativa nelle quattro viste studente; opzione per singola voce menu Joomla, default No, link SEF, stato corrente accessibile e CSS scoped. Collaudo Joomla pendente.

- FE-05 (PR #514 integrata): dashboard studente “La mia formazione” con contatori owner-scoped dello storico, riepilogo attestati, ultima attività riprendibile se autorizzata e link alle tre viste personali autonome; dettagli/filtri/paginazione mantenuti in I miei corsi. Collaudo Joomla pendente.

- FE-05 (PR #512 integrata): vista privata Preferenze JD Learn, menu IT/EN, riuso del consenso facoltativo con double opt-in/revoca e del layout condiviso con dashboard. Collaudo Joomla/SMTP pendente.

- FE-05 (seconda tranche, PR #510 integrata): vista personale autonoma “I miei attestati” con menu Joomla e traduzioni IT/EN, servizio attestati già vincolato al titolare, download protetto e partial condivisa con dashboard. Collaudo Joomla pendente.

- FE-05 (prima tranche): vista privata autonoma **I miei corsi** selezionabile dal menu Joomla, con modello e schede condivisi con la dashboard esistente, routing/ACL/filtri/paginazione invariati; traduzioni, test e documentazione aggiornati. Attestati, preferenze e riepilogo autonomo rimangono da implementare; collaudo Joomla pendente.

- FE-04 (seconda tranche): feedback salvataggio lezione e ripresa audio con conferme temporanee e errori persistenti/annunciati; preservati retry, idempotenza e backend. Test statico CI, documentazione e scenari QA aggiornati; collaudo Joomla pendente.

- FE-04 (prima tranche): navigazione manuale fra lezioni adiacenti nell'aula, con disponibilità e prerequisiti rispettati, link GET non mutativi, etichette IT/EN e CSS responsive; test di contratto. Nessun avanzamento automatico o completamento per cambio lezione. Collaudo Joomla pendente.

- FE-03 (prima tranche): aula con moduli del programma espandibili tramite dettagli nativi, toggle per larghezza del contenitore con fallback, indice sticky/statico secondo spazio disponibile e nomi dei soli docenti pubblici nell'intestazione. Conservati player, ACL e progressi; contratto CSS e test strutturale. Collaudo Joomla pendente.

- FE-02 (prima tranche): scheda corso con pannello di iscrizione e fatti laterale sticky/statico responsive, programma pubblico in moduli nativi espandibili senza JavaScript e filtro server per lezioni con disponibilità temporale non attiva. Form originali, ACL e token mantenuti; documentazione e verifiche statiche aggiunte. Collaudo Joomla pendente.

- FE-01 (seconda tranche): filtro per categoria su corsi pubblicati e accessibili, configurabile a livello globale e per voce menu Joomla. Nessuna esposizione di tassonomia riservata tramite il selettore o GET forgiati. Livello/difficoltà mostrato assieme alla durata dalle informazioni della versione pubblicata, con documentazione e test statici. Collaudo Joomla manuale pendente.

- FE-01 (prima tranche): catalogo corsi a griglia responsive su larghezza del contenitore, copertine e descrizioni facoltative, ricerca e filtri configurabili da opzioni globali e voce menu Joomla, paginazione e ordinamento iniziale configurabili. Test menu/XML/lingue e documentazione aggiornati; accettazione Joomla pendente.

- FE-10 (seconda tranche): inventariati ID e classi CSS, dipendenze JS frontend e registrazioni Web Asset Manager; nuovo gate CI per verificare la corrispondenza fra hook e layout PHP. Nessuna modifica al comportamento applicativo; collaudo Joomla pendente.

- FE-10 (prima tranche): aggiunto hook CSS pubblico `jlms-card` sulle schede frontend esistenti (corsi, percorsi, categorie, area personale), con regole scoped e controllo strutturale. ID, classi preesistenti, ACL e comportamento JS invariati; collaudo Joomla da eseguire.

## 0.57.7-dev

- LCM-01 / PR #490 integrata (`622a4ae67cae10d5dec35688d1d550fa064994a1`; CI 4/4) — fix parametrizzazione SQL nella generazione differita degli attestati: il lock `FOR UPDATE` conserva i binding della query per il rendering del PDF. Regressione Joomla degli attestati storici: download privato e consultazione per corsi non pubblicati/archiviati/cestinati, utente estraneo e anonimo negati, contenuto e seriale invariati anche dopo upgrade. Nessuna nuova emissione per la sola archiviazione; collaudo manuale ancora pendente.

- LCM-01 / PR #488 integrata (`9993414aed64c1126c8894f5b23af1a542dea10a`; CI 4/4) — dashboard storica: calcolo della modalità didattica dalla versione iscritta indipendentemente dalla pubblicazione del corso; per i corsi di consultazione archiviati/cestinati la percentuale resta correttamente non applicabile. Test Joomla per stati 0/2/-2, storico, completamento e blocco ripresa; collaudo manuale e attestati pendenti.

- LCM-01 / PR #486 integrata (`f17f16b05f3b0d7a8d8cdb6dc46aa4aa333461e6`; CI 4/4): completato il gate per le migrazioni amministrative su corsi non pubblicati/archiviati/cestinati e per le notifiche didattiche di nuove lezioni e aggiornamenti delle versioni (produzione e dispatch). Inattività del corso non cancella iscrizioni, progressi, email storiche o attestati. Aggiunti test di migrazione per stati `0/2/-2`; accettazione Joomla/SMTP pendente.

- LCM-01 / PR #484 integrata (`fef7a87a399e9bfafdbeca0c178f0e8629cf4ebf`; CI 4/4): incremento sul lifecycle corsi: il recupero dell'aula per iscritti richiede stato del corso `1`, bloccando anche URL diretti e mutazioni di progresso su corsi non pubblicati, archiviati o cestinati; catalogo e nuove iscrizioni mantengono i blocchi già presenti. Il solo termine della finestra promozionale non revoca gli accessi didattici preesistenti. Test Joomla di integrazione aggiornati; storico e progressi non cancellati. LCM-01 non ancora chiuso integralmente; accettazione reale pendente.

- Issue #472: aggiunta la voce selezionabile **Percorsi formativi** nel gestore menu nativo Joomla, grazie al layout metadata `site/tmpl/pathways/default.xml` con traduzioni IT/EN. Riutilizza il catalogo percorsi frontend e i controlli di visibilità esistenti; nessuna modifica a query, ACL o dati. Collaudo menu/SEF/lingua pendente.

- Vista **Attestati**: ordinamento Search Tools semplificato a una voce per campo (data, studente, corso, codice, stato, ID); direzione invertibile tramite intestazioni cliccabili della tabella. Filtri Stato e ricerca invariati. Collaudo Joomla pendente.

- Fix post-PR #475: corretto HTTP 500 nell'apertura backend **JD Learn → Attestati**. `CertificatesModel::populateState()` usava `getState()` e reinnescava l'inizializzazione Joomla ricorsivamente; ora legge `state->get()` direttamente. Aggiunti controllo anti-regressione e test HTTP autenticato di apertura/filtri della vista. Accettazione sul sito Joomla ancora da verificare.

- CERT-08 / PR #477: rigenerazione amministrativa protetta del PDF dagli snapshot originali, con conferma, motivo obbligatorio, confronto revisione, sostituzione atomica dello storage privato e audit transazionale; invariati codice, identità, data e documenti. Casi QA di sicurezza, concorrenza e backup/ripristino da accettare su Joomla.


- Issue #308 / PR #475: elenco attestati aggiornato alle convenzioni Joomla (Search Tools, filtri, ordinamento e paginazione), mantenendo servizio ACL/audit; nuova scheda Attestati nell'editor corso per configurare eredita/abilita/disabilita, con blocco in checkout. Test strutturali, tracciabilità e scenari QA aggiornati; accettazione Joomla pendente. Percentuale globale invariata in attesa di riconciliazione.

- MAIL-10/CORE-18 / PR #474: il contesto email non trasforma date calendario relative/impossibili in avvii corso validi. Il producer accetta date SQL/ISO assolute, normalizza a UTC e lascia vuoto il campo quando non valido; regressioni PHP aggiunte. Nessuna modifica a template o scheduler; accettazione SMTP/Joomla pendente e stima invariata.

- CERT-03/CERT-08 / PR #473: il rendering dell'attestato PDF rifiuta date di completamento vuote, relative, malformate o impossibili anziché convertirle nella data corrente. Aggiunta regressione su input invalidi e data ISO valida. Nessun cambiamento ai PDF già emessi o all'avanzamento stimato; collaudo Joomla pendente.

- Issue #430 / PR #468: corretta la proiezione del corso pubblico, ora include `catalog_access` letto dalla vista frontend per i metadati SEO e la regola di indicizzazione. Test service e HTTP aggiunti per proprietà presente e assenza di warning PHP. Merge `2d70bb15d897f0d9f0ed3cbc29e72bf6bb61c28b`; CI 4/4 e CSV superati. Pacchetto `pkg_jlms-0.57.7-dev.zip`, SHA-256 `20e3ed07b1d4200d098c6b76c11d9f5bc97dd9e232bcf8eda6d9a7499b98f02b`, [artefatto Actions 14183930070](https://github.com/jeckodevelopment/jd_learn/actions/runs/43768526287/artifacts/14183930070); checksum contenitore `a837d45552a60876effe535ca516a3e9393c1cc9e9bf55511d7b87afbf5c57db`. Collaudo manuale Joomla pendente; stima globale invariata 79,33%.

- IMP-06 / PR #465: anteprima pubblica di allegati selezionati nelle lezioni testuali, subordinata alla visibilità pubblica di corso e lezione e alla selezione esplicita del singolo allegato. Accesso rivalidato per versione corrente, catalogo, disponibilità e stato del file. Matrice MAT-02/MAT-04/MAT-08 aggiornata. Merge `075af6c0426fde914a9c818e73cd18637d9fec71`; CI 4/4 superata (run 43766727095), integrità CSV superata (run 43766727134). Pacchetto installabile `pkg_jlms-0.57.7-dev.zip`, SHA-256 `29a48cbfb4b17991c93cbccccbea001d4bd58d5aa2d9495ddd1db84a70844d7d`, nell'[artefatto Actions 14181769192](https://github.com/jeckodevelopment/jd_learn/actions/runs/43766727095/artifacts/14181769192) (checksum archivio: `743a81cda8a619f1ad2ff86ff3ef37931d35abb108e1e67a458e840f04b875a4`). Collaudo manuale Joomla ancora pendente; stima globale invariata 79,33% (79% arrotondato).

- Email / PR #464 integrata (merge 9cdb81527dcd520191bf8608868e900d519581fe): completati i produttori sicuri dei placeholder corso/lezione/docente e aggiornati test, catalogo e documentazione. CI 43766296559 4/4 e matrice 43766296685 superate; pacchetto installabile `pkg_jlms-0.57.7-dev.zip` nell'[artefatto Actions 14182925340](https://github.com/jeckodevelopment/jd_learn/actions/runs/43766296559/artifacts/14182925340), SHA-256 `5bfb0e57e720f9c807b6367e3a5f1dda80cf49af87c469a71e022793a5ee5850`. Collaudo Joomla/SMTP manuale pendente.

- IMP-05 / PR #460: contratto capability allineato agli adapter YouTube, Vimeo, Cloudflare Stream e HTML5 e alla sorgente richiesta; test di contratto, specifica e testbook aggiornati. CI 4/4 (run 43763377315; CSV 43763377317). Pacchetto installabile `pkg_jlms-0.57.7-dev.zip`, SHA-256 `5b7f184b4da34d46de5f7378925a07006a67ab73bdca8f551243f21d26516440`, incluso nell'artefatto Actions 14181605561 (checksum del contenitore: `55548b257b6e3b21bc2108c82fe136e74ac57a8b48ce1c1230b0f090cfa450dd`). Collaudo Joomla reale pendente.

- Issue #455 / PR #457: toolbar e ritorni tra Corsi, Iscritti e Iscrizione manuale completati; link duplicati rimossi. Collaudo utente completo riuscito il 15/11/2026. CI superata; pacchetto installabile `pkg_jlms-0.57.7-dev.zip` nell'artefatto Actions 14180606835.

- Toolbar: etichette brevi in italiano e inglese per tornare agli iscritti e gestire la disponibilità del corso.

- Issue #446/#448: azioni e ritorno al roster delle viste Comunicazione e Disponibilità corso trasferiti alla toolbar Joomla.

- Lezioni audio e video: trascrizioni facoltative versionate, filtrate e mostrate nell’aula solo agli iscritti autorizzati.

- Issue #434: le azioni di iscrizione manuale, comunicazione agli iscritti e disponibilità del corso sono disponibili nella toolbar nativa del roster.

- Comunicazioni agli iscritti: normalizzazione dei segnaposto storici dei modelli Joomla nella copia locale. Il 422 indica i segnaposto da correggere.

- MAIL-H / SEC-P1: la coda email passa a Joomla `addUnsafeTags()` soltanto i valori grezzi di tipo testo, data o URL. I token `*_HTML` già escapati e i frammenti HTML sanitizzati vengono preservati senza doppio escaping. Ciò migliora la resa dei template HTML personalizzati senza rinunciare alla protezione del testo non affidabile; invariati ID, modelli, placeholder, ACL e flusso SMTP.
- Verifica diretta del rendering `MailTemplate::replaceTags` in Joomla, test di classificazione token e guardia strutturale contro il precedente escaping indiscriminato. Accettazione manuale Joomla/SMTP ancora da eseguire.

## 0.57.6-dev

- Navigazione amministrativa compatta: sidebar JD Learn a cinque voci; **Coda email** resta nella dashboard.
- Le viste secondarie interne a JD Learn selezionano solo il nodo principale nativo del menu (scelta UX A), senza evidenziare voci operative; le viste principali mantengono il rispettivo focus.
- Issue #394: icona **Dipendenze categorie** corretta con `icon-folder`, classe usata da Joomla; accettazione visiva Atum ancora pendente.
- Nessuna personalizzazione del menu dei componenti nativi Joomla e nessun uso dell'API Pathway nel backend.


## 0.57.5-dev

- MAIL-H / issue #363: spostato il pulsante **Anteprima email di annuncio** dal corpo del form del corso alla toolbar amministrativa nativa di Joomla. Navigazione soltanto per corsi pubblicati modificabili e non in checkout da altro utente; nessuna email viene accodata alla semplice apertura.
- Regressioni browser/strutturali su toolbar, unico link e assenza nel form, percorso di autorizzazione e assenza pulsante per bozze. Nessuna modifica a modello Mail Templates, SMTP, consenso o percentuale di implementazione.

## 0.57.4-dev

- MAIL-G (PR #401): notifica operativa facoltativa `com_jlms.enrollment.manager_request` ai responsabili realmente autorizzati a gestire le iscrizioni del corso quando è presente una nuova richiesta ancora in attesa. Destinatari derivati da gruppi ACL Joomla e docenti assegnati, con rivalidazione individuale tramite `EnrollmentRosterService::course()` e ricontrollo prima dell'invio.
- Configurazione globale disabilitata per default e override per corso; coda/Scheduler nativi Joomla con deduplica per richiesta e destinatario, finestra di due giorni, link autenticato alla gestione iscrizioni, nessun dato del richiedente esposto nell'email. HTML escapato e template IT/EN. QA Joomla/SMTP manuale pendente.

## 0.57.3-dev

- MAIL-G (PR #399): notifica operativa facoltativa `com_jlms.course.updated` esclusivamente dopo la migrazione esplicita di un'iscrizione a una versione didattica pubblicata con cambiamenti sostanziali. Nessuna email al semplice salvataggio o alla pubblicazione di una versione non assegnata.
- Rilevazione cambi su contenuti di lezioni, quiz, materiali, regole e moduli (escludendo la sola presentazione editoriale); default OFF, override per corso, coda idempotente/Scheduler Joomla, controllo stato iscrizione, account, categoria e ACL prima di SMTP. Modello nativo IT/EN; QA manuale SMTP pendente.

## 0.57.2-dev

- MAIL-G (PR #397): introdotta notifica opzionale `com_jlms.lesson.available` quando una lezione pianificata diventa accessibile a un'iscrizione attiva della stessa versione didattica. Default globale OFF, override per corso, Scheduler e Mail Templates Joomla nativi, deduplica per iscrizione+lezione e ricontrollo ACL/stato/finestra/preferenze al dispatch. Messaggio con titolo e link autenticato, HTML con escaping.
- Limitato il recupero di rilasci programmati alle ultime 48 ore, evitando annunci retroattivi su lezioni già disponibili. Escluse bozza e semplice salvataggio; modifiche didattiche significative e avvisi ai responsabili ancora da sviluppare. Collaudo reale Joomla/SMTP pendente.

## 0.57.1-dev

- MAIL-H: titoli e descrizioni IT/EN dei 14 modelli email JD Learn nelle viste native Joomla, issue #312 / PR #395. Nessuna modifica ai template personalizzati; accettazione Joomla pendente.

## 0.57.0-dev

- Sidebar amministrativa JD Learn ridotta a sei voci operative, dashboard aggiornata con **Configurazione & Strumenti** e **Dipendenze categorie**.
- Pulsante **Variabili modelli email** (#359); viste interne associate alle rispettive voci principali tramite menu amministrativo nativo di Joomla (#390).
- Accettazione visiva su Atum/Joomla e verifica di aggiornamento ancora da registrare nel testbook.

## 0.56.29-dev

- Corretta la localizzazione della voce **Salva & Chiudi** nell’editor corso, con stringhe dedicate per italiano e inglese (#391).


## 0.56.28-dev

- Ripristinata la toolbar dell’editor corso: **Salva** resta autonomo; **Salva & Chiudi** è l’azione principale del menu split e **Salva e apri programma** è la voce aggiuntiva (#384).


## 0.56.27-dev

- Ripristinato il routing amministrativo dell’anteprima email di annuncio corso, includendo la vista esistente nella whitelist del controller display (#364).


Registro delle modifiche delle build di sviluppo JD Learn, in ordine di versione decrescente. Le versioni `-dev` **non sono rilasci stabili**. Sono riportate modifiche applicative verificabili, non le attività pianificate, la cronologia CI né i risultati del collaudo. Per l'archivio integrale precedente alla normalizzazione vedere [CHANGELOG_ARCHIVE_PRE_1_0.md](docs/CHANGELOG_ARCHIVE_PRE_1_0.md); per le evidenze di test vedere [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).

## 0.56.26-dev

- Raggruppata l’azione **Salva e apri programma** nel menu di **Salva & Chiudi**, mantenendo **Salva** e il comportamento di salvataggio esistente (#365).

## 0.56.25-dev

- Aggiunta la traduzione IT/EN della voce Ricollocazione categorie nel menu amministrativo Joomla (#368).

## 0.56.24-dev

- Resa la colonna Stato tramite l'icona nativa Joomla e uniformato il contatore Corsi ai pulsanti di conteggio delle categorie (#377).

## 0.56.23-dev

- Spostato il collegamento ai Modelli email Joomla dalla guida alle variabili alla toolbar, visibile solo con i permessi di gestione e modifica di `com_mails` (#375).

## 0.56.22-dev

- Corretto il contratto dati del form lezione con FieldsHelper di Joomla 6.2.0 e resa dei gruppi nativi nel form (#374).
- Il gestore nativo apre l’URL del contesto selezionato per Corsi, Docenti e Lezioni (#306).

## 0.56.21-dev

- Inserita notifica operativa al docente effettivamente assegnato a un corso, via Joomla Mail Templates e outbox, con opt-out globale/per corso, idempotenza e controllo dell'associazione/account all'invio. Nessun invio per salvataggi senza nuova relazione, nessun privilegio aggiuntivo, nessuna informazione di roster.
- Confermato dall'utente il successo dell'installazione 0.56.20-dev sul sito Joomla 6.2.0. La prova del nuovo evento email è ancora pendente e i restanti blocchi MAIL-G/H non risultano completati.

## 0.56.20-dev

- **Causa verificata:** il `postflight` di `com_jlms` invocava `MailTemplateTagUpdater::synchronize()`, che passava direttamente `(object) [...]` come secondo argomento di `DatabaseDriver::updateObject()`; l'API Joomla richiede un oggetto variabile per riferimento. Il difetto era preesistente alla PR #366, ma si manifesta quando i Mail Templates installati devono aggiornare i tag canonici.
- Corretto l'aggiornamento dei tag senza alterare oggetto, mittente, HTML o preferenze degli amministratori. Eliminati quattro analoghi argomenti temporanei in `LearningService`.
- Nuova prova di upgrade con template preesistente e tag obsoleti; validazione statica contro la ricomparsa del bug.

## 0.56.19-dev

- MAIL-04: promemoria operativo facoltativo prima della scadenza, con soglia configurabile, override per corso, outbox/Scheduler Joomla e rivalidazione dell’iscrizione prima dell’invio. Default disattivato.
- Il codice stabile e i dati attestati restano invariati. Collaudo Joomla/SMTP e CI della PR pendenti.

## 0.56.18-dev

- Non persiste più il testo delle eccezioni SMTP/provider, che può contenere indirizzi o dettagli infrastrutturali; il retry mantiene gli stati e i limiti esistenti.
- La vista di audit non seleziona `last_error`; la migrazione redige i testi già presenti senza modificare stato, tentativi o pianificazione.

## 0.56.16-dev

- Aggiunta conferma amministrativa esplicita, protetta da CSRF, e una campagna unica per corso con una riga di coda per destinatario.
- Lo Scheduler invia in batch tramite la coda persistente già esistente. Prima di ciascun tentativo verifica di nuovo la revisione del consenso, lo stato account/email, la preferenza di invio e l'accessibilità pubblica del corso; le righe non più idonee vengono saltate e i dati del destinatario rimossi.
- Idempotenza per corso/utente, link individuali di revoca, retry limitati e record campagna. Migrazione 0.56.16; installazione aggiornata a 47 tabelle / 66 FK.

## 0.56.15-dev

- Aggiunta anteprima amministrativa in sola lettura del modello Joomla `com_jlms.course.published`, con oggetto, HTML, testo semplice e conteggio aggregato degli utenti con consenso confermato ed email valida.
- Il contenuto è limitato al corso e alle lezioni pubbliche; la schermata non espone indirizzi email e non invia né accoda messaggi. Corsi non pubblicati/non visibili non sono eleggibili. Invio confermato, outbox, guardie al dispatch, deduplicazione, retry e audit restano da implementare.

## 0.56.14-dev

- Aggiunto il plugin utente JD Learn che inserisce nella registrazione Joomla un checkbox facoltativo e non preselezionato per gli annunci di nuovi corsi.
- La scelta richiede conferma email double opt-in; l’account viene creato anche se l’invio fallisce. Il plugin viene attivato alla prima installazione e il suo stato manuale è preservato negli aggiornamenti.

## 0.56.13-dev

- La visita GET non revoca; la conferma POST richiede CSRF. La revoca invalida i token paralleli; cancellazione consenso elimina i token via FK.
- Aggiunge view Joomla IT/EN, migrazione schema e verifiche automatizzate del ciclo di vita. Il collegamento al flusso di invio annunci resta nella tranche admin successiva; nessun annuncio viene inviato da questa PR.

## 0.56.12-dev

- Aggiunta una scelta facoltativa e non preselezionata per la richiesta di adesione agli annunci di nuovi corsi nel modulo di avvio automatico.
- Il double opt-in è richiesto solo dopo l'avvio riuscito; le richieste duplicate non reinviano una conferma già pending. Un errore SMTP non annulla l'iscrizione.

## 0.56.11-dev

- Registra data, origine e versione dell’informativa; rende i dati esportabili/rimovibili dal plugin Privacy Joomla e aggiunge il template nativo di conferma.
- Nessuna campagna o annuncio corso viene accodato; manca ancora la revoca tramite link nell’email di annuncio e la selezione/preview amministrativa.

## 0.56.10-dev

- Registra il template nativo Joomla `com_jlms.course.published` per MAIL-11.1, con variabili per contenuti pubblici, immagine e revoca.

## 0.56.9-dev

- Aggiunta comunicazione operativa agli iscritti selezionati per corso con anteprima, conferma e test all'operatore.
- Aggiunto job asincrono idempotente, rivalidazione ACL/stato, preferenze globale/per corso e template Joomla com_jlms.course.message.

## 0.56.8-dev

- Aggiunge il template Joomla `com_jlms.certificate.available` e l’accodamento idempotente alla prima emissione.
- Preferenze globali/per corso; errore outbox non annulla l’emissione; consegna differita al task esistente.

## 0.56.7-dev

- Elenco ricercabile/filtrabile, dettaglio su snapshot e download amministrativo protetto; ACL `jlms.certificates.manage` e audit minimo per elenco, consultazione e download.
- Migrazione additiva per il registro audit; schema ora a 44 tabelle e 65 FK.

## 0.56.6-dev

- Generazione PDF A4 IT/EN con dati certificati congelati, intestazioni personalizzabili, colore e logo JPEG locale.
- Elenco attestati nella dashboard studente e download GET/HEAD protetto; PDF conservato come asset nello storage privato esistente e rendering alla prima richiesta autorizzata.

## 0.56.5-dev

- Avvia CERT-A: preferenze globali e per corso (predefinito disabilitato) e registrazione idempotente dell'attestato sui completamenti persistiti in modalità Apprendimento, anche tramite quiz, revisione quiz e video.
- Il record conserva snapshot di studente, corso/versione, data e modello; non è ancora generato o scaricabile un PDF.
- Integra la conferma email dell'iscrizione amministrativa della PR #299 (merge 561e3a9).

## 0.56.4-dev

- Collega l’iscrizione amministrativa singola al template Joomla di conferma e all’outbox già esistente.
- Applica le preferenze globali/per corso e deduplica per enrollment; i preassegnamenti senza accesso corrente non inviano una falsa conferma.
- Requisiti: IMP-09-MAIL-A, MAIL-02/05. Collaudo Joomla/SMTP pendente.

## 0.56.3-dev

- Registra template Joomla per sospensione e riattivazione dell’accesso al corso.
- Accoda i messaggi nella transazione della modifica di stato, applicando preferenze globali/per corso; la chiave evento deduplica i replay.
- Accoda i messaggi nella stessa transazione della modifica di stato e applica le preferenze globali/per corso.
- La chiave evento deduplica i replay della transizione; il mancato invio non annulla lo stato didattico.

## 0.56.2-dev

- Aggiunge opzioni globali per abilitare o disabilitare conferme di iscrizione automatica e email di completamento.
- Aggiunge override per corso: eredita dall'impostazione globale, abilita o disabilita l'evento. Le preferenze sono salvate in `params_json` senza migrazioni DB e valutate alla transizione, prima dell'accodamento.
- Avanzamento aggiornato a 66,06% (66% arrotondato), contando anche gli attestati essenziali PDF approvati e ancora non implementati (0%).

## 0.56.1-dev

- Collega la conferma dell'iscrizione automatica e il completamento corso ai template Mail Templates introdotti in 0.56.0-dev.
- Aggiunge una coda persistente con chiave univoca evento, invio tramite attività pianificata Joomla, retry progressivo limitato e pulizia dei dati personali a consegna confermata.
- Se la consegna SMTP ha esito incerto, il messaggio resta in stato `sending` e non viene reinviato automaticamente; le richieste in approvazione, gli inserimenti manuali/import, gli attestati e i promemoria restano fuori da questo incremento.
- Avanzamento aggiornato: 73,88% (74% arrotondato), includendo il macroambito email approvato al 22%.

## 0.56.0-dev

- Collaudo Joomla manuale ancora pendente. L’invio automatico con outbox/retry/deduplicazione AC-13/AC-57 resta un residuo 1.0.0; attestati e promemoria avanzati restano esclusi.

## 0.55.0-dev

- Il player mostra gli stati localizzati di salvataggio e conferma; completamento, retry e gestione degli errori restano invariati.

## 0.54.0-dev

- Il riepilogo pre-pubblicazione mostra un avviso quando un video consultabile non ha durata confermata. La modalità Apprendimento mantiene il blocco esistente.
- Collaudo Joomla manuale ancora pendente: vedere `docs/QA_TESTBOOK.md`.
- Il riepilogo pre-pubblicazione mostra un avviso quando un video consultabile non ha una durata confermata. La modalità Apprendimento mantiene il blocco esistente.
- Regressione aggiunta in `tests/run.php`; accettazione Joomla manuale documentata in `docs/QA_TESTBOOK.md` e ancora da eseguire.

## 0.53.0-dev

- Un replay idempotente di un batch video già salvato ricalcola il conflitto di ripresa dalla sessione corrente: restituisce `resume_conflict=true` e lascia invariati resume e copertura.
- Collaudo Joomla manuale ancora pendente: vedere [QA_TESTBOOK.md](docs/QA_TESTBOOK.md).

## 0.52.0-dev

- Una sessione video che rileva un conflitto di ripresa mantiene la revisione obsoleta per tutti i batch successivi. Continua ad aggiungere copertura senza spostare il resume più recente né duplicare il completamento.
- Collaudo multi-browser Joomla ancora da registrare nel testbook.

## 0.51.0-dev

- Per pubblicare e avviare una lezione video tracciata, provider e descrittore devono supportare posizione, durata e seek. Velocità, caption e trascrizione restano opzionali. La consultazione non richiede capabilities di tracciamento.
- Collaudo Joomla dei quattro provider e adapter con capabilities incomplete ancora pendente; vedere il [testbook](docs/QA_TESTBOOK.md).

## 0.50.0-dev

- La lingua caption valida salvata nel browser viene passata a YouTube (`cc_lang_pref` e `cc_load_policy`) e Cloudflare Stream (`defaultTextTrack`) all’inizializzazione. Input non validi o localStorage non accessibile non bloccano la riproduzione. Il selettore JD Learn rimane limitato alle tracce interrogabili tramite Vimeo/HTML5; nessun dato lingua o telemetria aggiuntiva viene inviato.
- Accettazione Joomla manuale con account/provider/browser reali ancora pendente secondo il [testbook](docs/QA_TESTBOOK.md).

## 0.49.0-dev

- Aggiunge un’impostazione di corso e la selezione esplicita per le lezioni Testo; la pubblicazione impedisce preview attive quando il corso non le abilita.
- La vista ospite rivalida visibilità del corso, versione didattica corrente, selezione e finestra della lezione; non crea iscrizioni, progressi o tentativi. Video, quiz e materiali di lezione non vengono esposti.
- Collaudo Joomla manuale ancora da eseguire secondo [testbook](docs/QA_TESTBOOK.md#imp-02--anteprime-pubbliche-esplicite-delle-lezioni-di-testo-edge-02--isc-04).

## 0.48.0-dev

- La modalità di sola consultazione pubblica lezioni video anche senza durata misurata e senza capacità di telemetria/progresso. Il preflight e il controllo server restano coerenti; configurazione, normalizzazione della sorgente e validità del contenuto sono ancora verificate.

## 0.47.0-dev

- Aggiunta una modalità esplicita per pubblicare corsi con sole lezioni facoltative. La modalità standard continua a richiedere attività obbligatorie.
- I corsi di consultazione non accettano quiz, prerequisiti o sequenziamento e non attribuiscono completamenti o percentuali; dashboard e percorsi indicano che il progresso non si applica.
- Aggiornati il preflight, la validazione autorevole, le guide e le regressioni automatiche. Accettazione manuale Joomla ancora da eseguire.

## 0.46.0-dev

- Alla pubblicazione, i valori e le definizioni dei campi Joomla `com_fields` del corso sono congelati in `fields_snapshot_json` della versione didattica e inclusi nell'hash strutturale. I cambi editoriali successivi non alterano la versione assegnata; una nuova pubblicazione registra i valori aggiornati.
- Il limite artificiale che rifiutava versioni con snapshot corso non vuoto è rimosso. Restano attivi i controlli dello snapshot lezione, dei livelli di accesso Joomla e del limite massimo di campi.

## 0.45.0-dev

- I filtri Versione e Iscritto nel roster sono selettori ricercabili e mostrano solo voci del corso autorizzato. Ricerca libera per nome/username, stato, avanzamento e permessi per corso restano integrati.
- Test automatici HTTP e JavaScript aggiunti. Collaudo Joomla manuale richiesto nel testbook; nessuna modifica allo storico o ai diritti di accesso.

## 0.44.0-dev

- La vista amministrativa dei materiali consente di filtrare l’elenco per corso, singola lezione o corso e tutte le lezioni; il filtro conserva la paginazione e le azioni operano solo sugli allegati della versione autorizzata.
- Aggiunti messaggi IT/EN per il filtro, il conteggio e lo stato senza risultati. Il collaudo automatico copre separazione degli ambiti e validazione di lezioni estranee alla versione.
- Collaudo Joomla manuale richiesto nel testbook; nessun file diventa pubblico né cambia il comportamento di accesso/download.

## 0.43.0-dev

- Aggiunta la preferenza lingua locale per le tracce caption/sottotitoli esposte dal player; selezione esatta o fallback al codice lingua principale e comando per disattivare le tracce.
- La selezione è disponibile per Vimeo e per tracce native HTML5. YouTube e Cloudflare Stream non espongono all’adapter un elenco interrogabile e non mostrano il selettore. La preferenza resta nel browser e non modifica progresso, soglia o dati Joomla.
- Collaudo Joomla da registrare secondo la sezione STU-05 in `docs/QA_TESTBOOK.md`; selezione e persistenza verificate anche con test automatici su adapter simulati.

## 0.42.0-dev

- Aggiunta una preferenza locale per la velocità video, applicata solo se il player e il singolo video la supportano; disponibile sui player YouTube, Vimeo quando consentito dall’account/video, e HTML5.
- Il player Cloudflare Stream non espone questa impostazione e non mostra il selettore. La velocità effettiva resta parte dei campioni di telemetria già usati dal calcolo della copertura; nessuna soglia o regola di completamento cambia.
- STU-05 resta parziale: preferenza lingua da completare e collaudo provider da registrare nel [testbook](docs/QA_TESTBOOK.md#stu-05--preferenza-velocita-player-0420-dev).

## 0.41.81-dev

- Rimossi i titoli interni ripetuti dalla Dashboard e dalla lista Percorsi formativi; resta il titolo Joomla nella toolbar.
- Conservate l’introduzione Dashboard e la caption accessibile della tabella Percorsi. Aggiunti controlli HTTP e strutturali contro la regressione.
- Collaudo Joomla pendente: vedere [STAB-05 nel testbook](docs/QA_TESTBOOK.md#stab-05--titoli-non-duplicati-in-dashboard-e-percorsi-formativi-04181-dev-issue-199).

## 0.41.80-dev

- Rimosso il collegamento “Programma didattico” duplicato dal contenuto dell’editor; restano l’azione toolbar e il riepilogo nella scheda Programma.
- Aggiornato il test HTTP perché verifichi l’assenza del link duplicato e conservi l’accesso al Programma.

## 0.41.79-dev

- Accorcia la label del collegamento ai campi aggiuntivi delle lezioni: “Campi aggiuntivi” in italiano e “Lesson fields” in inglese, sia nella toolbar Programma sia nei cataloghi di sistema.
- La destinazione resta la gestione nativa `com_fields` con contesto `com_jlms.lesson`; permessi e visibilità del comando non cambiano.
- Il controllo strutturale verifica le due traduzioni in entrambi i file lingua.
- Collaudo Joomla: vedere [STAB-04 nel testbook](docs/QA_TESTBOOK.md#stab-04--label-compatta-per-i-campi-lezione-04179-dev).

## 0.41.78-dev

- Aggiunta `COM_JLMS_PATHWAYS` ai cataloghi di sistema IT/EN; il menu amministrativo mostra la voce tradotta invece della chiave grezza. Controllo strutturale dedicato.

## 0.41.77-dev

- L’anteprima del corso mostra gli allegati dell’ambito corso anche nelle lezioni di testo e video, come nel contesto di fruizione, mantenendo privati i campioni pubblici e i file non disponibili.
- Il download passa da un endpoint amministrativo protetto dall’autorizzazione editoriale e dalla relazione corso/versione/lezione; materiali associati a una lezione continuano a comparire solo per lezioni Documento/Audio. Nessun progresso o tentativo viene creato.
- Collaudo Joomla richiesto sul sito di prova: vedere [IMP-03 nel testbook](docs/QA_TESTBOOK.md#imp-03--preview-video-in-sola-lettura-04146-dev).

## 0.41.76-dev

- Corretto il controllo di pubblicazione che trattava l'anteprima esplicita degli allegati corso come un materiale non pronto. Ora una bozza può pubblicare allegati corso selezionati come anteprima se pronti, validi e scansionati quando richiesto; l'anteprima degli allegati lezione resta rifiutata.
- Estesa la prova HTTP Joomla con pubblicazione, pagina ospite, consegna pubblica, metodi/ID negati, finestra futura e visibilità del corso revocata.

## 0.41.75-dev

- L'editor può marcare un singolo allegato dell'ambito corso come anteprima pubblica; il default resta privato e il server rifiuta il flag sugli allegati di lezione.
- La scheda corso mostra soltanto anteprime della versione corrente pubblicata, pronte, attive e nel periodo configurato. La consegna revalida corso/catalogo/allegato, usa cache privata/no-store e non indicizza il file.
- Gli asset riutilizzati generano collegamenti privati. Aggiunta la procedura di collaudo Joomla in `docs/QA_TESTBOOK.md`; accettazione manuale ancora pendente.

## 0.41.74-dev

- File puliti passano a `ready`; EICAR viene rifiutato; socket indisponibile mantiene la quarantena. Il codice non dichiara una certificazione del server o delle definizioni di produzione.
- Accettazione manuale Joomla e configurazione dello scanner sul sito restano pendenti.

## 0.41.73-dev

- Aggiunto controllo Joomla del manifest e prova di compatibilità dei parametri legacy.
- Accettazione Joomla con account Cloudflare e video reali ancora da eseguire.

## 0.41.72-dev

- Aggiunge nelle opzioni Materiali una soglia di conservazione configurabile fra 24 e 8760 ore; il default resta 24 ore.
- Il cleanup Scheduler continua a elaborare al massimo 100 asset per esecuzione, verifica tutti i riferimenti e rimuove soltanto file locali non referenziati più vecchi della soglia. Valori fuori intervallo vengono limitati lato server.
- Aggiunge una prova d’integrazione Joomla che configura 48 ore e dimostra che un asset di 96 ore viene rimosso mentre uno di 30 ore e gli allegati referenziati vengono conservati.

## 0.41.71-dev

- I siti aggiornati con il valore legacy `x_accel` usano il percorso PHP protetto. Le prestazioni concorrenti richiedono benchmark separato.

## 0.41.70-dev

- Aggiunto nel backend un riepilogo dei riferimenti a corsi, percorsi, profili docente e domande, con ricollocazione esplicita verso categorie autorizzate della stessa sezione.
- Aggiornamento atomico del solo riferimento categoria; versioni didattiche, iscrizioni, progressi e risultati restano invariati. Se Finder è attivo, i corsi spostati vengono reindicizzati.

## 0.41.69-dev

- Aggiunge un filtro accessibile per titolo o ID ai selettori delle associazioni multilingua dei corsi, utile nei gruppi editoriali numerosi.
- Mantiene l’elenco autorizzato, il limite esplicito di 2.000 corsi e la convalida server di lingua, ACL, checkout e appartenenza al gruppo.
- Corregge la guida e le stringhe editoriali: le associazioni dei corsi pubblicati sono modificabili dalla PR #223; programmi, iscrizioni e progressi restano invariati.
- Mantiene l'elenco autorizzato, il limite esplicito di 2.000 corsi e la convalida server di lingua, ACL, checkout e appartenenza al gruppo.

## 0.41.68-dev

- Abilita i selettori nativi delle associazioni per corsi in bozza valida e pubblicati con versione live.
- Consente di collegare, staccare e ripristinare traduzioni senza cambiare versioni didattiche, iscrizioni o progressi; ACL, checkout e coerenza delle lingue restano verificati.
- Aggiorna test automatici, documentazione multilingua e collaudi manuali AC-20/AC-48. Accettazione Joomla manuale pendente.

## 0.41.67-dev

- Registra i corsi come contenuto taggabile Joomla con type alias `com_jlms.course`, content type UCM e mapping dei campi standard.
- Aggiunge il campo Tag nativo nell’editor corso; salva le associazioni, mantiene i tag nella duplicazione e collega le viste `com_tags` ai filtri già disponibili nel catalogo.
- Aggiorna la documentazione e il testbook con i comportamenti manuali per AC-19 e AC-64. Integrata nella PR #222 in 0.41.67-dev. Accettazione Joomla manuale ancora da eseguire.

## 0.41.66-dev

- Rimuove dalla consegna degli allegati la dipendenza opzionale da Nginx introdotta nella PR #219. I materiali privati vengono trasmessi dal componente PHP dopo i controlli Joomla e di integrità; non occorre configurare moduli o location specifiche del web server.
- Aggiorna la documentazione e le prove manuali per verificare download, anteprima, richieste Range/HEAD e revoca dell’accesso in un ambiente Joomla standard.

## 0.41.64-dev

- La gestione Materiali consente di collegare un asset privato esistente ai materiali del corso o a un’altra lezione della stessa versione in bozza, senza duplicare il file.
- Le associazioni ereditano titolo, descrizione e finestra di disponibilità del sorgente; il riuso ripetuto nello stesso ambito viene rifiutato. Le sostituzioni continuano a creare asset immutabili e le versioni pubblicate restano inalterate.

## 0.41.63-dev

- Le FK verso categorie Joomla impediscono la cancellazione permanente di categorie ancora usate da corsi, percorsi, docenti o domande.
- Il cestinamento e il ripristino restano reversibili e non cancellano corsi o storico; la visibilità segue lo stato della categoria.

## 0.41.62-dev

- Aggiunge filtri docente e percorso nel catalogo e nelle viste categoria; opzioni e risultati sono limitati a schede e profili accessibili all’utente.
- Filtri componibili con tag, difficoltà, durata, ricerca, ordine, vista griglia/elenco e paginazione. Gli ID non validi non allargano i risultati.

## 0.41.61-dev

- Consente di passare dalla griglia all’elenco nel catalogo corsi e nella pagina di una categoria; selezione tramite filtro allowlist e mantenuta nell’URL insieme agli altri filtri.
- La vista elenco si adatta agli schermi stretti e usa le stesse schede e gli stessi risultati autorizzati della griglia.

## 0.41.60-dev

- Aggiunge il filtro per tag Joomla nel catalogo e nelle pagine categoria, combinabile con difficoltà, durata, ricerca e ordinamento.
- Le opzioni mostrano soltanto tag pubblicati/accessibili associati a corsi visibili; il filtro mantiene il ramo categoria e non rivela corsi riservati.

## 0.41.59-dev

- Aggiunge filtri pubblici per difficoltà e fasce di durata stimata al catalogo e alla lista corsi delle categorie; ricerca, ordine, categoria e paginazione si combinano.
- I valori inviati sono limitati a opzioni previste; i filtri leggono solo i metadati promozionali del corso.
- Collaudo Joomla: vedere [testbook](docs/QA_TESTBOOK.md#imp-07--filtri-di-difficolta-e-durata-del-catalogo).

## 0.41.58-dev

- Genera segmenti Joomla ID-alias per corsi, categorie e percorsi; conserva il parsing degli URL numerici storici e usa query parametrizzate.
- Il canonical delle viste pubbliche indica l’URL SEF con alias o il menu nativo dedicato; gli ID distinguono alias ripetuti tra lingue e contenitori.
- Genera segmenti Joomla nel formato ID-alias per corsi, categorie e percorsi; mantiene il parsing degli URL numerici esistenti e usa il database Joomla con parametri associati.
- Il canonical delle viste pubbliche risolve al percorso SEF con alias. Restano gli ID per disambiguare alias ripetuti tra lingue e contenitori.

## 0.41.57-dev

- Aggiunge canonical URL e metadati Open Graph/Twitter per schede corso, categorie e percorsi visibili agli ospiti; per le schede corso usa titolo/descrizione SEO e immagine promozionale locale già configurati.
- Le pagine con accesso catalogo/contenuto non disponibile agli ospiti e le viste percorso autenticate ricevono `noindex, nofollow`; le immagini social puntano a media locali.

## 0.41.56-dev

- Aggiunge ricerca case-insensitive su titolo e sommario promozionale, ordinamento per più recenti o titolo, anche nella vista categoria.
- La ricerca usa parametri SQL associati, mantiene il perimetro di accesso e non interroga programma, lezioni o dati personali; i controlli sono tradotti IT/EN e accessibili da tastiera.

## 0.41.55-dev

- Integrato il plugin Finder `plg_finder_jlms`: indice limitato a titolo e sommario delle schede pubbliche idonee, con lingua/categorie e aggiornamento al cambio di corso o categoria.
- Esclusi contenuti non pubblicati, programmati, riservati, senza versione attiva o collocati in categorie non pubbliche. Nessun programma, lezione, media, dato personale o campo privato entra nell’indice.

## 0.41.54-dev

- Aggiunta attività Joomla Scheduler `jlms.materials.cleanup`: elimina in batch limitato asset locali senza riferimenti, più vecchi di 24 ore, e i relativi blob privati. I riferimenti a lezioni, allegati, attestati e job sono esclusi; le sostituzioni immutabili restano conservate.
- L’attività è disponibile nel plugin `plg_task_jlms`, ma va pianificata dall’amministratore. Non viene creata automaticamente all’installazione.

## 0.41.53-dev

- L’avvio autorizzato di una nuova sessione elimina fino a 250 sessioni video scadute usando l’indice di scadenza; le sessioni attive non vengono toccate. La pulizia è opportunistica e non sostituisce un’attività periodica sul sito inattivo.
- Aggiunta prova d’integrazione Joomla per la rimozione di una riga scaduta. Nessuna modifica a progresso, accessi o provider.

## 0.41.52-dev

- Collaudo Joomla pendente: ripresa, pausa/seek, errore transitorio e permanente, nessun completamento automatico. [Testbook](docs/QA_TESTBOOK.md#pr-196-integrata--retry-del-progresso-audio).
- La vista Banca domande mostra i filtri Ricerca, Categoria, Stato e Lingua; consente combinazioni e mostra l’intestazione Azioni/Actions.
- Aggiunge in aula un comando tradotto e accessibile per mostrare/nascondere il programma didattico sotto 48rem; desktop mantiene l’indice visibile. Senza JavaScript il programma resta disponibile.

## 0.41.51-dev

- Aggiunge stato “Salvataggio attività…” e un singolo retry dopo errore di rete/timeout o risposta HTTP 408, 429 o 5xx; timeout per richiesta 8 secondi e pausa 500 ms.
- Riutilizza la stessa chiave di idempotenza nelle due richieste; gli errori di autorizzazione e gli altri 4xx non vengono ritentati. Nessuna modalità offline o retry continuo.

## 0.41.50-dev

- L’apertura esplicita di una lezione accessibile registra lo stato “in corso” e la destinazione Riprendi tramite POST con token Joomla. Il GET dell’aula resta senza scritture.

## 0.41.49-dev

- Aggiunta una soglia facoltativa per ogni lezione video: campo vuoto eredita quella della versione, valore impostato prevale per la singola lezione.
- La soglia è salvata nei parametri della lezione, inclusa nel confronto del programma, validata al salvataggio/pubblicazione e usata nel calcolo di completamento. Le versioni assegnate restano immutabili.

## 0.41.48-dev

- Nell’aula aggiunge **Apri nel browser** per PDF e immagini JPEG/PNG/WebP, mantenendo il download separato. L’endpoint applica nuovamente accesso all’iscrizione, disponibilità e relazioni del materiale e ammette solo i MIME passivi consentiti.
- La visualizzazione resta read-only: non cambia iscrizione, progresso o completamento. Gli altri formati continuano a essere scaricabili ma non sono serviti inline.

## 0.41.47-dev

- Estende IMP-03 alle lezioni documento/audio: mostra allegati di corso e lezione pronti, rispetta stato e finestre di pubblicazione e rivalida ACL prima della consegna. PDF, immagini e testo consentiti possono aprirsi inline; altri documenti restano download. MP3/OGG sono riprodotti con il player HTML5 nativo.
- La preview non crea iscrizioni, sessioni, tentativi o progressi. Le interazioni quiz restano fuori da questa preview.

## 0.41.46-dev

- Aggiunge descrittori video in sola lettura nella preview amministrativa, con autorizzazione editoriale rivalidata e consegna HTML5 locale protetta. Il player non crea sessioni, non invia telemetria e non salva progressi o tentativi.
- Aggiorna i messaggi IT/EN per distinguere la riproduzione video dalle attività non simulate (documenti, audio e quiz).

## 0.41.45-dev

- Le azioni principali del programma sono disponibili nella toolbar nativa, con URL contestuali al corso e visibilità condizionata ai permessi già verificati.
- Il pannello laterale separa visivamente moduli, moduli di form e comandi, migliorando spaziatura e leggibilità sui viewport stretti.

## 0.41.44-dev

- Risolve issue #154: un nuovo corso eredita il fuso impostato nella configurazione globale del sito Joomla. L’autore può scegliere e conservare un fuso diverso; i corsi già esistenti non vengono reinterpretati.

## 0.41.43-dev

- La preview editoriale consente di simulare viewing access level Joomla e distingue visibilità di catalogo, accesso ai contenuti e requisito combinato. La simulazione è read-only e non modifica identità, iscrizioni, corso o progressi.
- Aggiunte traduzioni IT/EN e verifiche HTTP/integrazione per accessi misti, autorizzazione editor e assenza di mutazioni.

## 0.41.42-dev

- Estende il workflow Joomla JD Learn con archiviazione e ripristino del corso pubblicato. L’archivio sparisce dal catalogo e non ammette nuove iscrizioni.
- Versione didattica, iscrizioni esistenti e accesso degli studenti già ammessi sono preservati; il ripristino riporta online la stessa versione.
- Upgrade del workflow idempotente; prove Joomla d’integrazione per tra…13233 tokens truncated…e.admin sul relativo asset. Le modifiche vengono salvate dal controller com_config di Joomla.
- I salvataggi editoriali conservano le regole; grant, deny, ereditarietà, CSRF e scope verificati sul percorso HTTP nativo.

## 0.29.0-dev

- Applica il requisito della descrizione estesa alla prima pubblicazione. Rifiuta testo assente o composto da soli spazi e markup vuoto, indicando il campo da completare.
- Le bozze esistenti richiedono il completamento della descrizione. Le versioni già pubblicate e i ritiri/ripubblicazioni conservano i dati congelati.

## 0.28.0-dev

- Il Programma apre l’anteprima della versione selezionata. L’editor può consultare moduli e testo delle lezioni senza iscrizioni, riproduzione video, tentativi o progressi.

## 0.27.2-dev

- Crea l’iframe con URL e origine espliciti e attende la navigazione prima di collegare l’SDK. Il referrer dell’iframe contiene solo l’origine del sito.

## 0.27.1-dev

- Ripulisce il caricamento SDK fallito, consente un nuovo tentativo e distingue blocchi CSP, script indisponibile, video non incorporabile e identificazione del sito mancante.

## 0.27.0-dev

- Il player appare all’apertura della lezione secondo la politica configurata dal gestore. Rimangono i controlli di riproduzione del player.
- La precedente modalità esplicita richiede una scelta nelle opzioni. Sessione, modulo e provider hanno errori distinti.

## 0.26.3-dev

- Attende i metadati di YouTube prima di valutare la durata e registrare progressi. Mostra un messaggio distinto se il server rifiuta l’accesso al player.

## 0.26.2-dev

- Stato di configurazione dei quattro provider nel form lezione e accesso alle impostazioni native per operatori autorizzati.
- Messaggi specifici per sorgenti non valide, provider non registrati e rifiuti del servizio Cloudflare Stream.

## 0.26.1-dev

- Il workflow verifica il permesso di cambio stato sul singolo corso, con ereditarietà dalla categoria. Prova di prima pubblicazione HTTP con un operatore senza permesso globale.

## 0.26.0-dev

- Limite di 500 caratteri per descrizioni brevi nuove o modificate, senza troncamento dei testi preesistenti.
- Alias duplicati rilevati dopo normalizzazione, con messaggio specifico per categoria e lingua.
- Prove native e HTTP per i limiti, la conservazione dei dati e i messaggi del modulo.

## 0.25.0-dev

- Elenco corsi con scope ACL nativo per-record prima di paginazione/totali, cache coerente dopo revoca e nessun filtro basato sul solo autore.
- Nuovo corso consentito anche ai delegati categoriali; selettore Category esteso con scope e conservazione della categoria reale, cestino rifiutato.
- Permessi effettivi, opzioni native autorizzate, checkout altrui in sola lettura ed errori copertina specifici; modalità avvio documentate.
- Nuove prove native/HTTP/reinstall, guide e matrice aggiornate; esiti nella PR. Schema41tabelle/58FK invariato; M01 e integrazioni core ancora parziali.

## 0.24.0-dev

- Campi calendario/fuso nativi: pubblicazione e fruizione, UTC, intervalli e DST senza normalizzazione silenziosa.
- Snapshot delle ultime date alla pubblicazione e assegnazione della finestra alla prima edizione; date pubblicate immutabili, vecchi client/snapshot conservati.
- Scheda con fuso/offset e disponibilità corrente del comando di avvio; politica dei quiz già aperti invariata. Test dominio/Joomla/HTTP/reinstall e guide aggiornati, esiti nella PR del build. Schema41tabelle/58FK invariato.

## 0.23.0-dev

- Profili docente con MVC/Form/Table Joomla, categorie/asset, ACL dedicata e revisioni; bio testuale, immagine/alt, sito e account facoltativo senza gruppi aggiunti.
- Selezione multipla nei corsi in bozza; snapshot ordinato, pubblicazione validata e visibilità corrente/storica su profili e categorie.
- Prove native/HTTP/upgrade e guide/IT-EN/override; esiti nella PR del build. Schema41tabelle/58FK invariato. Calendario, directory pubblica standalone, associazioni multilingua dei docenti e selettore ricercabile avanzato ancora pendenti.

## 0.22.0-dev

- Media field nativo per copertina pubblica locale, alt obbligatorio o decorazione esplicita; validazione path/MIME/dimensioni e confini filesystem.
- Tab metadati con titolo/descrizione SEO facoltativi, congelati nella versione e applicati alla head nativa soltanto dopo autorizzazione.
- Compatibilità degli snapshot0.21 e test di upgrade reale; schema41tabelle/58FK invariato. Docenti, calendario, provider media remoti e asset versionati ancora pendenti.
- Corretto avvio automatico concorrente: il lock dell’edizione è la prima lettura transazionale, prima dello snapshot MySQL/MariaDB; identità e disponibilità aggiornate dopo l’attesa. Test deterministico con un’iscrizione concorrente inserita durante il blocco.
- Prove servizio/HTTP e regressioni: esiti nella PR del build. Nessuna accettazione completa della fase dedotta dai test.

## 0.21.0-dev

- Schede native editor e nuovi campi di presentazione, filtrati/tipizzati e conservati senza accettare JSON grezzo.
- Snapshot degli ultimi dati alla pubblicazione; scheda autorizzata con nuove regioni CSS. Versioni storiche non modificate e liste leggere.
- Piano M01–M12 più differitoM13, con assegnazione verificata di tutti i210ID; non dichiarazione di completamento.
- Nuove prove di persistenza, autorizzazione, XSS, input invalidi, snapshot e upgrade; risultati da registrare. Schema41tabelle/58FK invariato.

## 0.20.1-dev

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

## 0.3.0-dev

- Pubblicazione validata dei corsi di testo tramite workflow e plugin Publishing Joomla; transazione unica, versione immutabile, hash ed edizione iniziale.
- Catalogo paginato e scheda pubblica con programma, filtro categoria/discendenti, autorizzazioni correnti e nuovi ID CSS.
- Test integrativi workflow/veto/immutabilità/visibilità e rendering HTTP; risultati effettivi nella PR.

## 0.2.0-dev

- Aggiunti servizio applicativo DI, prima bozza didattica, editor del programma e comandi MVC nativi.
- Implementati moduli e lezioni testo/video, obbligatorietà, modifica, riordino e rimozione con controlli delle dipendenze.
- Controlli centralizzati di ACL, parent, revisione aggregata/elemento e immutabilità delle versioni pubblicate; query e transazioni brevi.
- Integrazione editor/filtro HTML core, provider dal registro estensibile e normalizzazione delle sorgenti.
- Nuovi test integrativi e HTTP del flusso completo. Nessuna pubblicazione o fruizione studenti ancora attiva.

## 0.1.0-dev

- Importate le specifiche funzionali, tecniche, sicurezza/provider, servizi/endpoint e matrici di collaudo concordate.
- Aggiunti package Joomla, libreria contratti, componente MVC, schema37tabelle/53FK e installer conservativo con verifiche dello schema trattenuto.
- Implementata gestione dei corsi in bozza con categorie, livelli, lingua, asset ACL, check-in/out e controllo delle revisioni.
- Inclusi i quattro plugin video con registrazione lazy e validazione locale delle sorgenti; riproduzione esplicitamente non disponibile.
- Aggiunte regole dominio iniziali, traduzioni, WAM, prime regioni CSS pubbliche e guida agli override.
- Predisposte build riproducibile e CI PHP8.3/8.4, MySQL8.4/MariaDB10.6, installazione/aggiornamento Joomla, test MVC e rendering HTTP.
- Pubblicazione, fruizione, quiz persistenti, attestati e integrazioni rimanenti restano nello stato documentato in IMPLEMENTATION_STATUS.md.

