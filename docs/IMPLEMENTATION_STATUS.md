# Avanzamento di JD Learn

Aggiornamento: 4 novembre 2026. Versione di sviluppo **0.34.0-dev**, con revisione del flusso editoriale e profili docente; ACL0.30 verificate nella CI41813160693 e integrate con PR37; vincolo di descrizione 0.29 verificato nella CI41804790705 e integrato con PR36; anteprima editoriale 0.28 verificata nella CI41734705599 e integrata con PR35; correzione iframe 0.27.2 verificata nella CI41731792184 e integrata con PR34; diagnostica 0.27.1 verificata nella CI41728328142 e integrata con PR33; player integrato 0.27.0 verificato nella CI41726857577 e integrato con PR32; correzione dell’avvio YouTube 0.26.3 verificata nella CI41725330930 e integrata con PR31; diagnostica video 0.26.2 verificata nella CI41722244743 e integrata con PR30 nell’editor e correzione ACL di pubblicazione nella 0.26.1; validazione editoriale 0.26 verificata nella CI41718537724 e integrata con PR28; permessi editor0.25 verificati nella CI41437667314 e integrati con PR27; calendario0.24 verificato nella CI41432090964 e integrato con PR26; docenti0.23 verificati nella CI41428719456 e integrati con PR25; copertina/SEO e concorrenza iscrizioni0.22 verificati nella CI41421171965 e integrati con PR24; editor/presentazione0.21 verificato dalla CI41417124499 e integrato con PR23; prima pubblicazione0.20.1 già verificata nella CI; il prodotto non è ancora pronto per la produzione.

Schema attuale: **41 tabelle / 58 FK**. Il pacchetto contiene7estensioni: componente, libreria, quattro provider video e plugin Scheduler. Il database presente non equivale a una funzionalità operativa.

La specifica funzionale1.5 e il progetto tecnico sono il perimetro; il suffisso1.0 nei loro nomi è storico. La [matrice corrente](REQUIREMENTS_STATUS.csv) riporta tutti i210requisiti della [matrice baseline](specifications/Matrice_requisiti_test_LMS_1.0.csv), con descrizioni e scenari aggiornati esplicitamente dalla richiesta del4novembre, registrata in [REQUIREMENTS_CHANGES.md](REQUIREMENTS_CHANGES.md). Gli ID e i perimetri restano conservati; lo stato di implementazione non riscrive i requisiti.

## Incremento0.34 — M01/i programma contestuale

Scaletta a moduli espandibili e lezioni cliccabili, aggiunta contestuale e un solo editor aperto su richiesta. Azioni secondarie per duplicare, spostare tra moduli della bozza e confermare l’eliminazione; materiali della lezione e nuovi quiz mantengono il contesto. Trascinamento con salvataggio atomico della permutazione e revisione, rollback visivo se fallisce, comandi Su/Giù con fallback nativo senza JS. Il salvataggio ricarica le revisioni server di tutti gli elementi. Le versioni pubblicate restano immutabili. Specifica1.5/AC-127; prove native/HTTP/JS e upgrade nella CI della PR, collaudo browser/mobile/accessibilità e residui M01 ancora aperti.

## Incremento0.33 — M01/h amministrazione nativa

Elenco ed editor secondo la preferenza articoli Joomla: Search Tools/filtri/sort/selezione/toolbar, metadati, layout contenuto/sidebar e opzioni raccolte. Salva e apri programma usa il FormController e conserva il form prima della navigazione. Le nuove prove sono nella CI della PR del build; riordino e accettazione completa M01/AC-126 restano aperti.

## Incremento0.32 — M01/g

Duplicazioni autonome dal Programma: nuova identità corso in bozza, copia modulo con sottoalbero e copia lezione con destinazione esplicita nella stessa versione di lavoro. Quiz, file e campi nativi sono conservati secondo ACL; dati di apprendimento esclusi. Nuove verifiche native/HTTP e conservazione dati nella CI del build da riscontrare nella PR. La0.31 resta identificata dal proprio build già eseguito. M01, AC-96 e gli altri requisiti non sono promossi automaticamente a completi.

## Revisione funzionale M01 — richiesta del4novembre

La0.31 semplifica il normale aggiornamento dei corsi pubblicati con storico editoriale Joomla, la modifica del programma con copia completa della versione corrente e i profili docente con campi personalizzati nativi e voci di menu. La richiesta aggiorna la presentazione della gestione docente eliminando il passaggio obbligatorio di categoria. I dati preesistenti conservano categorie e ACL tecniche.

Il programma pubblicato e le assegnazioni didattiche già in uso mantengono la loro identità. Le nuove versioni vengono attivate con il workflow. La PR38 è integrata; la CI41819674307 ha superato tutti e4gli ambienti, con34controlli editoriali nativi e6HTTP per ambiente e conservazione dopo upgrade. Questo non chiude M01: i criteri effettivamente residui, incluse duplicazioni autonome, ordinamento completo e parti dell’editor, sono in [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md). Anteprima interattiva, revisione completa e migrazioni restano nel piano M02. La specifica1.3 aggiunge AC-119–125, ancora non dichiarati integralmente accettati.

## Anteprima editoriale M02

Il Programma apre una vista amministrativa della versione selezionata con moduli, lezioni e testo. L’accesso segue i permessi di modifica del singolo corso, anche su richiesta diretta. La vista è in sola lettura e non carica materiali, player o tentativi.

## Priorità dal collaudo utente

Il 3 novembre l’utente ha confermato di avere pubblicato il corso e ha segnalato un blocco nell’uso dei provider video. Il percorso di gestione dei corsi e la verifica dei video sul sito di collaudo hanno precedenza sugli incrementi non essenziali. La 0.26.2 mostra i prerequisiti locali nel Programma e messaggi video specifici. Il collaudo utente ha poi confermato che YouTube funziona con la 0.27.2.

## Blocco di pubblicazione segnalato in precedenza

**Priorità0: gestione corsi / prima pubblicazione, prima dell'import CSV.** L'utente segnala corsi che restano in bozza. L’utente precisa che manca proprio il comando di pubblicazione nell’interfaccia, anziché una transizione eseguita che fallisce. La CI0.20 verificava la pubblicazione via servizio e il ritiro/ripubblicazione HTTP, senza creare e pubblicare inizialmente un nuovo corso interamente dalle form native. Questo era il limite delle prove precedenti; il successivo riscontro utente sul percorso di prima pubblicazione è registrato sopra.

La0.20.1 aggiunge navigazione editor/programma, istruzioni e diagnosi visibili per versione, permessi, plugin Publishing e transizioni, oltre al test HTTP completo di creazione/prima pubblicazione e ai rifiuti per programma vuoto/plugin disabilitato. La prima CI0.20.1 (41349160121) ha superato13verifiche HTTP per ambiente e tutte le regressioni, inclusa la prima pubblicazione. Il registro evidenze conserva quel build preciso; i controlli del successivo chiarimento nell’elenco sono consultabili nella PR22. Il riscontro successivo sul sito segnalante è stato acquisito. I messaggi e la guida di pubblicazione, rimasti erroneamente alle limitazioni della0.3, vengono riallineati.

## Incremento precedente0.30 — M01/e

Regole per-record modificabili con il campo Rules e il controller Joomla, autorizzazione core.admin sul corso e avviso di salvataggio immediato. Prove HTTP native e conservazione dopo Salva. M01 richiede ancora il collaudo complessivo.

## Incremento precedente0.29 — M01/d

La prima pubblicazione richiede la descrizione estesa, con testo significativo, come previsto dalla tabella dei campi corso. Le bozze incomplete restano salvabili. Il vincolo si applica anche a bozze preesistenti; le versioni già pubblicate mantengono i dati congelati. Nuove prove native e HTTP nella PR del build. M01 rimane parziale.

## Incremento precedente0.26 — M01/c2

Descrizione breve limitata a 500 caratteri per nuovi valori, conservazione dei testi precedenti e messaggio per alias duplicati per categoria e lingua. Prove native e HTTP nella PR. La 0.29 applica il vincolo di descrizione estesa alla prima pubblicazione.

## Incremento precedente0.25 — M01/c1

Elenco/totali/cache secondo ACL per-record, deleghe di creazione categoriali, selettore nativo esteso, riepilogo dei diritti e checkout altrui in sola lettura. Nessuna regola o gruppo aggiunto dal prodotto e nessun writer ACL parallelo. Opzioni/permessi ereditati rimangono nativi Joomla. Guida EDITOR_PERMISSIONS e prove nuove nella PR; Fields/History/per-record editor, collaudo completo e M01 rimangono parziali.

## Incremento precedente0.24 — M01/b3

Calendario pubblicazione/fruizione con form native, fuso esplicito, UTC rigoroso, DST e intervalli. Snapshot delle ultime date e prima edizione coerenti; disponibilità corrente del pulsante con controlli server esistenti. Policy dei quiz già aperti e dipendenza dalla visibilità editoriale restano documentate, senza cambi di timer. Guida CALENDAR e test dominio/Joomla/HTTP/reinstall predisposti; esiti del build nella PR. Non completa M01 o R-03.

## Incremento precedente0.23 — M01/b2

Profili docente e associazioni ordinate ai corsi tramite form/asset/ACL/categorie nativi; snapshot storico e doppio controllo livelli/lingue congelati e disponibilità corrente. Nessun account/email nella proiezione docente e nessun gruppo concesso. Guida TEACHERS e prove servizio/HTTP/upgrade predisposte; esiti nella PR del build. Calendario, directory docente standalone e altri criteri rimangono pendenti.

## Incremento precedente0.22 — M01/b1 e stabilizzazione iscrizioni

Copertina pubblica locale tramite Media Manager, alt/decorazione e metadati SEO tipizzati. La regressione concorrente ha rilevato uno snapshot transazionale obsoleto nell’avvio automatico: risoluzione edizione spostata prima della transazione, lock come prima lettura e identità nuovamente verificata; test deterministico della lettura dopo l’attesa, oltre alla gara manuale/automatica. Snapshot, autorizzazioni e compatibilità storica come nella guida COURSE_EDITOR; non completa docenti/calendario o M01. Prove nuove nella CI del build, da consultare nella PR prima di attribuire risultati. CORE-12 ora ha il primo percorso implementato, non la copertura completa: il collaudo interattivo del selettore e gli adapter remoti sono ancora pendenti.

## Incremento precedente0.21 — M01/a

Schede native editor (dati generali, presentazione, accessi e pubblicazione; associazioni se abilitate), campi promozionali tipizzati e HTML filtrato. La pubblicazione congela gli ultimi dati salvati e la scheda pubblica li legge dalla versione autorizzata. Nessun dato di bozza o corpo delle lezioni viene esposto. Compatibilità dei vecchi client e versioni precedenti, schema invariato. La CI41417124499 ha superato44verifiche presentazione,19asserzioni di prima pubblicazione HTTP e3controlli di conservazione per ambiente, oltre alle regressioni. PR23 integrata; l’esito non completa M01. L'evidenza JSON storica continua a identificare soltanto i build già eseguiti.

La [roadmap verso il rilascio stabile](ROADMAP_TO_STABLE.md) articola le fasi M01–M12 e il differito M13. Tutti i210ID sono assegnati in RELEASE_PLAN.json e controllati automaticamente; nessuna fase è dichiarata accettata. Immagini, docenti, calendario, ACL e aggiornamenti hanno sottoinsiemi operativi verificati. Restano i criteri puntuali di M01, l’anteprima completa, workflow revisione/archivio e migrazioni; nessuna fase viene promossa sulla base dei soli sottoinsiemi.

## Come leggere lo stato

- **In corso:** esiste un sottoinsieme riscontrato, con sorgenti/guide e verifiche parziali indicati. Non significa requisito completato, né esprime una percentuale.
- **Da implementare:** il percorso specifico non è disponibile; una tabella, una funzione vicina o una specifica non lo sostituiscono.
- **CI parziale:** i file elencati sono verifiche della suite implementata, sul build indicato nelle [evidenze](IMPLEMENTATION_EVIDENCE.json). Non attestano automaticamente tutti gli scenari AC/provider della baseline.
- **Solo riscontro documentale:** scelta di perimetro/guida trovata, senza attribuzione di collaudo runtime per quella riga.

Nessun requisito è stato promosso ad accettazione completa in questo riallineamento. La matrice non va usata per calcolare una percentuale di completamento: requisiti di complessità diversa si sovrappongono e un test può verificare solo parte di più requisiti. I requisiti differiti restano separati dal rilascio1.

## Funzioni disponibili e residui

| Area | Sottoinsiemi presenti (non accettazione completa) | Residui per il primo rilascio |
|---|---|---|
| Pacchetto e installazione | Manifest/DI/MVC, preflight, build riproducibile, installazione/upgrade e conservazione dati sui flussi CI | Update server, backup/ripristino, matrice completa sulla patch corrente e manutenzione di rilascio |
| Utenti e accessi | Identità/sessioni/CSRF, livelli e asset Joomla; ricaricamento dei diritti, blocchi/revoche e controllo diretto per oggetto | MFA/provider reali e copertura di tutte le nuove azioni; integrazione reale con estensione abbonamenti |
| Categorie e lingue | Categorie native, gerarchia/antenati, Pathway e associazioni; corsi associati in bozza, Language Filter e menu SEF | Lifecycle con dipendenze, associazioni dei gruppi pubblicati e selettori completi |
| Authoring e pubblicazione | Bozze, moduli/lezioni testo/video/quiz, ordinamento/parent, editor filtrato; prima pubblicazione e YouTube confermati dall’utente; salvataggio pubblicato, storico Joomla e copia completa per aggiornamento0.31 | Duplicazioni autonome corso/modulo/lezione, audio/documenti come lezioni, analisi impatti/migrazioni, prerequisiti e rilascio progressivo |
| Catalogo e aula | Schede/programma pubblici autorizzati, filtri categorie, aula protetta, materiali e completamenti persistenti | Ricerca/ordinamenti/SEO e metadati completi, percorsi aggregati, navigazione/UX/accessibilità complete |
| Area personale | Storico del proprietario con filtri/pagine, apprendimento distinto dall’accesso, Riprendi/Rivedi e lingua originale | Attestati e attività dettagliate; fallback di ripresa R-05 |
| Video | Quattro plugin base, consenso, copertura unica/ripresa, Stream API/token, MP4 privati PHP limitato/Nginx | Provider aggiuntivi senza patch R-02, sottotitoli/trascrizioni/velocità/configurazioni complete, firma locale/cache Stream, collaudo SDK/provider/browser reale |
| Materiali | Allegati distinti corso/lezione, upload con limiti/allowlist/quarantena, scanner opzionale e download autorizzato/Range | Riuso/preview/metadati e ciclo versioni completi, offload/pulizia/conservazione e scanner reale |
| Iscrizioni | Avvio automatico; elenco amministrativo per corso; sospensione/riattivazione; ammissione manuale singola auto/explicit con anteprima, motivo, conferma, capienza/audit e preassegnazione esplicita senza livelli | CSV, richieste/approvazioni/rifiuti, annullamenti, edizioni aggiuntive, finestre/proroghe e selettori ricercabili |
| Quiz | Banca/revisioni, composizione/pubblicazione, pool/snapshot, tentativi/autosave/CAS, grading/feedback, scadenza server; tempi individuali, annullamenti e proroghe con audit | Rivalutazioni, disponibilità specifiche, anomalie/attestati, UI/accessibilità e collaudo completo |
| Attestati | Solo schema e specifica | Modelli/PDF, idoneità/emissione, download, verifica pubblica opzionale, revoca/sostituzione e anomalie |
| Audit e task | Writer com_actionlogs su quiz/iscrizioni; plugin Scheduler per scadenze quiz | Plugin/coverage Action Logs generali, job/outbox/lease/retry, notifiche, Mail Templates e purghe |
| Report | Elenco iscritti con filtri/versione/utente/stato/avanzamento e date UTC | Aggregati/KPI, dettagli studente/quiz/attestati, filtri/CSV export e dati temporanei protetti |
| Core Joomla restante | WAM, override, form/menu/lingue; Fields sui docenti e Content History corso/docente0.31 | Plugin Privacy, Finder, Tags, estensione e collaudo completo Fields/History/Media e restanti azioni core |
| Qualità e scala | Test PHP/database/Joomla/HTTP/JS, negativi, richieste concorrenti e upgrade della suite implementata | WCAG/manuali browser/mobile, analisi statica/stile/scan e revisione sicurezza completa, provider live, recovery e carico300/500 |

I limiti specifici e le differenze rispetto alla specifica sono nel [registro aperto](IMPLEMENTATION_GAPS.md). Il ritiro dal catalogo non implementa ancora le due operazioni distinte previste da EDGE-09; i controlli sugli ID del componente non certificano l’unicità di tutta la pagina Joomla.

## Verifiche effettive

La [CI del build0.20](https://github.com/jeckodevelopment/jd_learn/actions/runs/41319974680) è risultata verde su PHP8.3/8.4 × MySQL8.4/MariaDB10.6, con Joomla6.0.0 e pacchetto italiano nativo. Comprende la suite esistente, l’upgrade dalla0.19 e il reinstall con dati conservati. Il minimo Joomla6.0.0 usato nella CI non è una raccomandazione di patch per la produzione.

Per la preassegnazione sono verificati37controlli del servizio,18asserzioni HTTP oltre alle richieste negative e4controlli di conservazione per ambiente, insieme ai controlli manuali standard già esistenti; non sono37requisiti di prodotto accettati. Il [registro delle evidenze](IMPLEMENTATION_EVIDENCE.json) fissa commit/albero, job e hash del pacchetto, distinguendo la prova storica dalla documentazione aggiornata.

Non sono eseguiti i benchmark300studenti/60min e500/10min, né una verifica completa di ASVS/WCAG, provider reali, patch corrente e recovery. I test concorrenti su pochi processi provano specifici invarianti, non la capacità su centinaia di studenti.

## Prossimi passaggi

Il [piano esecutivo residuo](NEXT_STEPS.md) specifica dipendenze e condizioni di accettazione. Priorità successiva: chiudere i residui puntuali di [M01_ACCEPTANCE.md](M01_ACCEPTANCE.md), senza trattare la prima pubblicazione già confermata come un nuovo blocco aperto. Import CSV e job amministrati seguono dopo la verifica del flusso essenziale. Privacy e sicurezza accompagnano ogni incremento e non sono rinviate alla sola verifica finale.

## Cronologia e guide

La [cronologia originale](IMPLEMENTATION_HISTORY.md) conserva le note dei singoli incrementi; i vecchi «da completare» vanno letti nel loro contesto temporale. Per configurare e usare i percorsi operativi consultare le guide elencate nel [README](../README.md). La vendita/gestione abbonamenti rimane fuori perimetro; compiti, live, gruppi/classi e interoperabilità mantengono le fasi differite della baseline.

## Incremento0.20 verificato nella CI

[PREASSIGNMENT.md](PREASSIGNMENT.md): opzione esplicita disattivata per default, avviso di accesso mancante, assegnazione senza gruppi e successiva fruizione soltanto dopo diritti correnti. Prove di incremento eseguite nei quattro ambienti CI; l’evidenza storica0.19 è conservata separatamente nel registro. Nessuna nuova tabella.
