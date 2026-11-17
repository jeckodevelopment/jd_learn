# Attestati essenziali — perimetro JD Learn 1.0.0

**Decisione approvata: 12 novembre 2026.** La prima versione stabile include l'emissione di attestati PDF di completamento del corso. Il perimetro è distinto da **JD Learn Credentials / Open Badges**, che rimane successivo alla 1.0.0. Requisiti approvati il 12 novembre 2026 per la prima stabile. CERT-A (PR #300), CERT-B (PR #302), CERT-C (PR #304) e CERT-D (PR #307) sono integrate; l'implementazione del macroambito è stimata al 75%. L'accettazione Joomla resta distinta dalla presenza del codice.

## Requisiti CERT-01–CERT-08

| ID | Priorità | Requisito e accettazione |
|---|---|---|
| CERT-01 | P0 | Opzione globale e per corso per abilitare gli attestati; per default nessuna emissione su corsi non abilitati. I permessi sono applicati server-side. |
| CERT-02 | P0 | Emissione automatica di un attestato solo dopo il completamento effettivo e persistito di un corso in modalità apprendimento, secondo le regole di completamento core. Mai su salvataggio editoriale, anteprima, corso non completato o sola consultazione. |
| CERT-03 | P0 | Generazione PDF con template standard personalizzabile da amministratore: logo, intestazione, nome studente, titolo corso, data di completamento e codice attestato. Escape dei contenuti e layout utilizzabile in IT/EN. |
| CERT-04 | P0 | Codice univoco, stabile, non riutilizzato; emissione idempotente anche per eventi/richieste concorrenti. Persistenza di identità corso, studente, versione didattica e data, senza rigenerazioni spurie. |
| CERT-05 | P0 | Download autenticato dall'area personale dello studente esclusivamente dei propri attestati; ACL e protezione accesso ai PDF, senza URL pubblici non autorizzati. |
| CERT-06 | P0 | Vista amministrativa per ricerca, consultazione e download degli attestati emessi, con ACL coerenti con Joomla e limitazione dei dati visibili. |
| CERT-07 | P1 | Notifica operativa di disponibilità attestato usando i Mail Templates nativi Joomla e l'outbox approvata (MAIL-01/02/09). Il fallimento della mail non annulla il completamento né l'emissione; nessun doppio invio su replay. |
| CERT-08 | P1 | Lifecycle affidabile: upgrade non elimina attestati; rigenerazione del PDF, se necessaria per problemi tecnici, non muta codice o dati certificati; audit minimo ed error handling con protezione dei dati. Definire e collaudare backup/ripristino. |

## Confini e decisioni

- Incluso: attestato **di completamento**, per singolo corso, generato come PDF; opzione di rilascio configurabile.
- Escluso: Open Badges, firme digitali qualificate, verifica pubblica della credenziale, badge di percorso, editor grafico avanzato, regole complesse, emissione manuale arbitraria, scadenza/revoca avanzata. Tali capacità restano nel piano JD Learn Credentials.
- Il PDF è un attestato prodotto dalla piattaforma, **non** una certificazione legale o un titolo con validità riconosciuta automaticamente.
- Per eventuali ri-iscrizioni/ripetizioni definire nella PR tecnica una chiave di emissione per singola istanza di completamento, senza duplicati da retry.

## Sequenza PR approvata prima di IMP-10

| ID | Priorità | Contenuto | Requisiti | Criteri uscita |
|---|---|---|---|---|
| IMP-09-CERT-A | P0 | Modello dati, configurazioni globale/per corso, permessi e servizio di emissione idempotente collegato al completamento core | CERT-01/02/04 | Test di transizione, concorrenza, controllo modalità corso e upgrade |
| IMP-09-CERT-B | P0 | Generatore PDF e template base configurabile, storage privato, download studente | CERT-03/05/08 | PDF IT/EN e ACL autenticazione/download; nessuna fuga di dati |
| IMP-09-CERT-C | P0 | Backend elenco/dettaglio/download attestati, ACL amministrative e audit minimo | CERT-06/08 | Roster, filtri, accessi non autorizzati negati |
| IMP-09-CERT-D | P1 | Mail Template Joomla, notifica disponibilità, integrazione outbox, documentazione e test end-to-end | CERT-07, MAIL-01/02/09 | Invio singolo, retry senza duplicati, mailer non disponibile non blocca attestato |

Ogni PR di implementazione: milestone `1.0.0`, elenco requisiti, sezione **Comportamenti da testare** nel sito Joomla, aggiornamento `docs/REQUIREMENTS_STATUS.csv` e testbook, changelog, guide e pacchetto verificato dopo merge. Le PR soltanto documentali non contano nella percentuale implementativa.

## Prove di accettazione minime

1. Corsi con attestati disabilitati e abilitati; completamento reale/non completamento; anteprima e sola consultazione.
2. Ripetizione della stessa transizione e concorrenza: un solo attestato per completamento valido, codice invariato.
3. PDF con contenuti lunghi, diacritici, IT/EN, logo e dati reali corretti.
4. Download studente A consentito, studente B negato, visitatore anonimo negato; ACL backend differenziate.
5. Upgrade e backup/ripristino non perdono attestati o metadati.
6. SMTP indisponibile, retry, deduplica e preferenze comunicazioni Joomla.
7. Verificare che gli attestati non espongano URL o informazioni private tramite indicizzazione o API.

Questi scenari vanno aggiunti a `docs/QA_TESTBOOK.md`; l'esecuzione manuale non è dichiarata superata dall'esito CI.


**Aggiornamento CERT-C (13 novembre 2026, PR #304 integrata):** elenco, dettaglio e download amministrativo protetto con ACL Joomla; audit minimo per consultazione, ricerca e download. CI standard 4/4 nel [run 1031](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364); collaudo Joomla pendente. Pacchetto [JD-Learn-0.56.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364/artifacts/14034621388), ZIP interno SHA-256 `28c462f2eae0b795cf02574408ac9bfa830e8e4a95bd5ad634bc011700e14bc4`, wrapper `f0467b1a4e96ab784283090e48377d400fbb69f944579ffd444934cdc41175e2` (scadenza 27 novembre 2026 alle 12:00 UTC). CERT-A/B/C coprono il 62,5% stimato dei criteri attestati; prossima PR CERT-D per notifica tramite Mail Templates/outbox.


## CERT-D — notifica disponibilità attestato

La prima emissione accoda `com_jlms.certificate.available` nell’outbox esistente, con lingua del profilo Joomla, link autenticato alla dashboard e chiave univoca per documento. Preferenza globale/per corso; la consegna è differita al task Scheduler e usa il mailer Joomla. Replay non crea una seconda notifica; errori di invio non invalidano completamento o attestato.

**Collaudo manuale Joomla:** abilitare gli attestati e le notifiche globali, configurare SMTP di test; completare il corso con uno studente IT e uno EN e verificare in Joomla → Sistema → Modelli email le variabili e il template. Eseguire il job `JD Learn: send queued emails`, verificare una email corretta con link alla dashboard e download disponibile dopo login. Ripetere il completamento e verificare nessuna seconda riga/invio. Disabilitare l’override per corso e verificare che non venga accodata. Rendere temporaneamente indisponibile il mailer prima del job: il certificato deve restare presente, la coda deve ritentare e, ripristinato SMTP, la notifica deve essere consegnata una volta.


## CERT-08 — Rigenerazione tecnica amministrativa (PR #477)

Un amministratore autorizzato può rigenerare il **solo file PDF** di un attestato valido e non revocato dal dettaglio nel backend, motivando esplicitamente l'intervento e confermando l'operazione. La richiesta usa POST e CSRF Joomla. Il server applica i permessi di gestione degli attestati e verifica la revisione bloccando le modifiche concorrenti.

La rigenerazione parte esclusivamente dallo snapshot dell'identità e del modello già persistito, non dai dati attuali del corso o dell'utente. Identificativo attestato, documento, codice, data e campi certificati restano invariati; cambia il puntatore alla risorsa privata del PDF con incremento di revisione. Il vecchio asset non viene eliminato durante l'operazione e l'audit registra motivo, vecchio/nuovo asset, checksum e revisione. In caso di errore l'operazione torna allo stato precedente; non vengono emesse nuove notifiche.

**Residui di accettazione:** prova su Joomla dell'operazione e delle condizioni concorrenti/negative; test del ciclo backup/ripristino che comprenda sia database sia storage privato, inclusa verifica di integrità dei PDF.

## LCM-01 — conservazione attestati dopo archiviazione del corso (PR #490 integrata, CI 4/4)

La consultazione e il download degli attestati già emessi sono separati dalla disponibilità didattica del corso. I dati degli attestati derivano da snapshot associati all'iscrizione completata e il servizio di download valida titolarità, presenza e stato del documento; non richiede che il corso sia ancora pubblicato. Il test ha individuato e richiesto la correzione di un problema preesistente del rendering differito: il lock SQL con `FOR UPDATE` deve mantenere i binding `:document`, `:enrollment`, `:user` della query parametrizzata Joomla. Test automatici di integrazione Joomla controllano stati `0`, `2`, `-2`, identità e seriale invariati, negazione all'anonimo e a un altro utente, PDF disponibile e preservato nell'upgrade. La verifica manuale Joomla del PDF su sito reale e della coppia backup database/storage rimane un gate di accettazione, non dichiarato concluso.


## Diagnostica delle iscrizioni completate senza attestato — issue #546

**CERT-06 / P0 #546 — diagnosi attestati mancanti (PR #549, CI e collaudo Joomla pendenti):** nuova sezione in JD Learn → Attestati, accessibile solo con `core.manage` e `jlms.certificates.manage`, che mostra in sola lettura le ultime 25 iscrizioni realmente completate senza emissione completa. Espone la causa: regole della versione pubblicata senza rilascio attestati, preferenze globali/corso disabilitate, modello attestato assente, configurazione invalida oppure emissione attesa ma assente/documento non collegato. Nessuna emissione automatica retroattiva, modifica delle regole storiche, invio email o variazione della percentuale di completamento. Test nativi Joomla su ACL, limiti e categorie di esito. Avanzamento globale 79,56% invariato.

La vista Attestati amministrativa distingue la mancata emissione dalla mancata generazione del PDF: un documento `pending_render` esiste già e non deve comparire tra le emissioni assenti. La vista non è uno strumento per creare attestati fuori dalle condizioni approvate della versione di corso completata.


## Post-merge PR #549 — diagnosi certificati mancanti

**PR #549 (diagnostica attestati, issue #546)** integrata al commit `013267b788e2033fa5f11ef89bdf3b284c8d47d1`; CI 4/4 verde [run 44048059581](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581), gate CSV [run 44048060095](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048060095), [pacchetto JD-Learn-0.57.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/44048059581/artifacts/14311889571). Lista protetta e limitata delle iscrizioni completate senza attestato, con diagnosi dei gate; nessuna emissione retroattiva forzata. Collaudo Joomla e causa precisa del corso Rigoletto ancora pendenti.


## Download attestati storici e configurazione privata (issue #560)

Il record di emissione può essere `pending_render` finché il primo download autorizzato non genera il PDF e lo salva nello storage privato. Il renderer accetta date SQL `DATETIME(6)`, incluse frazioni di secondo, oltre a date SQL senza frazioni; il completamento rimane immutato. Per lo storage, configurare in JD Learn una directory **assoluta**, esistente, scrivibile da PHP e **esterna** alla radice pubblica del sito (`material_storage_root`). Un percorso vuoto, non scrivibile o pubblico provoca `COM_JLMS_STORAGE_UNAVAILABLE` (503): non si effettua alcun salvataggio in una cartella web per aggirare il controllo. Dopo aver corretto la configurazione, riprovare il download; gli attestati ancora `pending_render` non vanno emessi una seconda volta.


## Storage privato automatico (issue #562, PR #563)

Un hosting condiviso non deve richiedere scrittura fuori da `public_html`. Se `material_storage_root` è vuoto (comportamento predefinito), JD Learn crea automaticamente `media/com_jlms/private` alla prima necessità e conserva file randomici `.php` con un'intestazione PHP che non eroga contenuti; il payload è cifrato e autenticato con AES-256-GCM derivando una chiave dal secret di Joomla. La directory contiene `.htaccess` e `index.php` per ulteriore protezione su Apache/LiteSpeed. Su Nginx+PHP, la cifratura protegge dalla divulgazione statica accidentale. Lo scaricamento passa soltanto dal controller autorizzato, che restituisce i byte originali dopo verifica del checksum. Nella tab **Materiali extra**, il percorso esterno è facoltativo ed è destinato a configurazioni avanzate: se già impostato, mantiene i blob legacy e richiede una directory privata esterna alla webroot. Non cambiare percorso senza migrare gli asset. Il backup deve conservare **database, directory gestita e secret Joomla**; la perdita del secret impedisce la decifratura dei documenti. Se la directory non è scrivibile da PHP, l'errore 503 rimane intenzionale (fail closed) e va risolta la configurazione dei permessi.


## Generazione senza cartella temporanea PHP (issue #564, PR #565)

Per il PDF generato internamente, JD Learn non usa più `tempnam(sys_get_temp_dir())`: la funzione `stageGeneratedPdf` valida il PDF e scrive direttamente in storage privato gestito, con cifratura e checksum, oppure nello storage privato esterno preconfigurato. Questo evita i blocchi `open_basedir` su hosting condivisi e la temporanea esposizione di un PDF in chiaro. Per errori di emissione PDF i log operativi Joomla in categoria `com_jlms` registrano solo la fase di fallimento (`logo`, `pdf`, `storage`, `database`) e il tipo di eccezione, senza dati personali o path. Il 503 rimane fail-closed; lo stato `pending_render` può essere recuperato ripetendo il download dopo aver risolto il problema specifico.

## Correzione logo raster e recupero documenti in attesa (PR in verifica)

- Logo attestati: JPEG, PNG, GIF, WebP e AVIF (questi ultimi quando supportati dalla libreria GD di PHP). I raster non-JPEG sono decodificati e ricomposti su fondo bianco, poi incorporati come JPEG nel PDF; SVG e sorgenti remote non sono accettati.
- La correzione riguarda anche documenti già emessi con stato `pending_render`: al nuovo download autenticato il renderer usa lo snapshot originale con il logo PNG senza creare una nuova emissione, senza cambiare codice, identità, data o audit storico.
- **Non riemettere** un attestato se il record esiste: riprovare il rendering. Se manca completamente l'emissione, utilizzare la diagnostica delle iscrizioni completate senza attestato e l'eventuale recupero storico amministrativo previsto dalle regole; nessun backfill indiscriminato.
- Test aggiunto: `php tests/certificate-raster-logos.php`; richiede estensione GD per raster non JPEG. Collaudo Joomla reale ancora richiesto.
