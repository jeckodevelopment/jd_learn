# Componente e-learning per Joomla — Specifica funzionale

Versione 1.2 · 30 ottobre 2026 · Specifica funzionale consolidata del rilascio 1

**Stato:** specifica funzionale consolidata della prima versione. Comportamenti e default derivano dalle proposte accolte dall'utente. Il documento descrive cosa realizzare e come accettarlo; non attesta che il software sia già implementato o che le prestazioni siano state misurate. La verifica Joomla è documentale e su sorgenti ufficiali, con riferimenti riproducibili in sezione 32. Mockup e matrice requisito-test sono allegati alla stessa versione.

Le sezioni 1–30 definiscono prodotto, campi, viste e processi; le sezioni 31–36 completano precedenze, integrazioni, selettori, collaudo e configurazione del sito. Le regole di dettaglio sono prescrittive. Le funzioni differite sono elencate soltanto come evoluzioni, senza costituire impegno del rilascio 1.

## 1. Obiettivo, perimetro e ipotesi

Realizzare un sistema e-learning originale e nativo Joomla che permetta di progettare, pubblicare ed erogare corsi online, gestire iscrizioni, verificare l'apprendimento e documentare i risultati. Il prodotto è progettato per essere riutilizzabile in contesti formativi diversi, con funzionalità, modello dei dati e interfacce definiti dalla presente specifica.

Destinazione: Joomla 6 come piattaforma primaria; formazione asincrona come nucleo; sito singolo; utenti Joomla; interfaccia italiana traducibile; utilizzo su PC, tablet e smartphone. Compatibilità Joomla 5, formazione sincrona e standard di interoperabilità sono decisioni separate. La vendita e la gestione degli abbonamenti sono fuori perimetro: un'estensione dedicata determina le appartenenze ai gruppi Joomla e quindi i livelli di accesso disponibili all'utente. Ogni corso è associato a un livello di accesso Joomla. Provider video previsti nel primo rilascio: YouTube, Vimeo e Cloudflare Stream, oltre al video locale già previsto. Nessuna destinazione specifica del componente è presunta già approvata.

La completezza dell'analisi comprende anche le funzioni differite: non implica che l'intero perimetro debba essere sviluppato nella prima versione. Budget e calendario di sviluppo sono separati dal perimetro funzionale. Volumi e obiettivi di collaudo sono definiti in 17.4; formazione regolamentata non inclusa.

**Vincolo espresso dall'utente:** integrare tutte le funzionalità core Joomla applicabili al componente, inclusi categorie, utenti, override, lingua, privacy e livelli di accesso. È un requisito architetturale e funzionale del primo rilascio, non un'estensione futura. La matrice della sezione 17 ne definisce il perimetro verificabile; eventuali funzionalità core non pertinenti devono essere motivate nella specifica tecnica. Il componente conserva una propria gestione dei soli oggetti e processi didattici, senza duplicare i servizi del CMS.

**Riutilizzabilità e scala confermate dall'utente:** prodotto e-learning generico per Joomla, indipendente da organizzazioni, marchi, domini e template specifici. Specifiche, esempi, dati dimostrativi, nomi tecnici e documentazione devono essere neutrali. Le viste pubbliche devono esporre ID CSS documentati per la personalizzazione, oltre a classi, variabili CSS e override. L'architettura deve sostenere centinaia di studenti contemporaneamente; il profilo numerico e le soglie di collaudo in sezione 17.4 sono obiettivi confermati da verificare, non capacità già misurate.

## 2. Principi del prodotto

Il sistema è un componente e-learning autonomo per Joomla. Le scelte funzionali sono definite dai requisiti del progetto e dai comportamenti approvati.

| Principio | Requisito di progetto |
|---|---|
| Integrazione nativa | Usare i servizi core Joomla applicabili, con contesti, permessi e punti di estensione implementati e verificabili |
| Riutilizzabilità | Nessun vincolo a un'organizzazione, dominio, marchio, template o specifica attività formativa |
| Accesso coerente | Ogni corso è associato a un livello di accesso Joomla; vendita e abbonamenti sono gestiti esternamente |
| Struttura didattica | Categorie, percorsi, corsi, moduli, lezioni, materiali, quiz e attestati con responsabilità distinte |
| Risultati persistenti | Progressi, tentativi, completamenti e attestati legati all'identità Joomla, con storico e rettifiche controllate |
| Media estendibili | Provider video separati e regole di fruizione uniformi, nel rispetto delle capacità dei player |
| Personalizzazione | Viste pubbliche con ID, classi e variabili CSS documentati, sottolayout e override Joomla |
| Efficienza | Progettazione e collaudo per centinaia di studenti simultanei, con obiettivi e infrastruttura dichiarati |

Le fonti tecniche al termine del documento documentano esclusivamente i servizi Joomla, gli standard e le API dei provider integrati. Il perimetro del prodotto è definito dalla presente specifica.

## 3. Ruoli e autorizzazioni

| Ruolo funzionale | Capacità | Limiti |
|---|---|---|
| Visitatore | Catalogo pubblico, schede, eventuali lezioni di anteprima | Nessuno storico personale senza account |
| Studente | Iscrizione, fruizione, quiz, risultati, attestati | Solo dati e iscrizioni propri |
| Autore | Creare e aggiornare contenuti assegnati | Nessuna pubblicazione o accesso indiscriminato ai risultati |
| Docente/tutor | Seguire iscritti dei corsi assegnati; correggere attività quando abilitate | Nessuna visibilità su corsi o gruppi estranei |
| Responsabile didattico | Pubblicazione, iscrizioni, report e rettifiche autorizzate | Configurazione tecnica separata |
| Amministratore | Configurazione, integrazioni, permessi, manutenzione | Operazioni sensibili tracciate |
| Responsabile di gruppo, fase successiva | Consultare risultati del proprio gruppo | Nessun accesso trasversale |

I ruoli si traducono in autorizzazioni granulari; una persona può avere più ruoli. Il profilo pubblico di un docente può esistere senza account amministrativo. Essere autore non autorizza automaticamente a leggere i risultati degli studenti.

Separare tre controlli: visibilità del contenuto, diritto a fruirne e autorizzazione amministrativa. Sono verificati anche su URL diretti, download ed esportazioni. Un pulsante nascosto non costituisce un controllo di accesso.

## 4. Modello formativo e oggetti

| Oggetto | Dati principali e relazioni |
|---|---|
| Categoria | Categoria nativa Joomla associata al contesto del componente; titolo, descrizione, gerarchia, lingua, accesso e permessi; classifica i corsi |
| Percorso | Insieme ordinato di corsi, obiettivi e regola di completamento |
| Corso | Titolo, alias, immagine, descrizioni, obiettivi, destinatari, prerequisiti, difficoltà, lingua, durata stimata, docenti, accessi, calendario, regole |
| Versione del corso | Struttura e requisiti pubblicati cui riferire iscrizioni e risultati |
| Modulo didattico | Sezione ordinata di un corso; non è un modulo di visualizzazione Joomla |
| Lezione | Tipo, contenuto, ordine, obbligatorietà, durata, materiali, completamento, eventuali prerequisiti |
| Risorsa | File o riferimento esterno, disponibilità, autorizzazioni, versione e metadati |
| Quiz e domanda | Configurazione, punteggi, banca domande, versioni, feedback |
| Iscrizione | Studente, versione del corso, origine, stato, data di attivazione, eventuale scadenza |
| Progresso | Stato per lezione, posizione video, intervalli fruiti se disponibili, date |
| Tentativo | Quiz, domande estratte, risposte, scadenza, punteggio, esito e revisione |
| Attestato | Identificativo, titolare, corso/versione, data, modello, stato, documento |
| Evento e notifica | Azione, soggetto, data, riferimento, consegna e tentativi di invio |
| Gruppo, fase successiva | Partecipanti, responsabili, assegnazioni e scadenze comuni |

Una lezione appartiene a una versione del corso; la duplicazione è disponibile nella prima release. Una libreria di lezioni condivise è successiva, perché richiede regole sull'effetto delle modifiche. Un corso può comparire in più percorsi. La categoria classifica, il percorso definisce una proposta formativa.

## 5. Catalogo e orientamento

**CAT-01 — Catalogo.** Viste a schede ed elenco, paginazione, ricerca per titolo e descrizione; filtri per categoria, percorso, docente, difficoltà, lingua e durata. Filtro sui tag e sui corsi disponibili. Non esporre titoli o descrizioni di contenuti riservati salvo scheda promozionale esplicitamente pubblica.

**CAT-02 — Scheda corso.** Mostrare programma, obiettivi, prerequisiti dichiarativi, docenti, durata stimata, regole di completamento, eventuale test, disponibilità dell'attestato e modalità di accesso. Pulsante coerente: Accedi, Iscriviti, Richiedi accesso, Inizia o Riprendi. Durata stimata e tempo registrato restano distinti.

**CAT-03 — Percorsi.** Elenco ordinato dei corsi, progresso personale e requisiti. In prima release il percorso aggrega corsi; sblocco sequenziale fra corsi e attestato di percorso nella fase successiva.

**CAT-04 — Ricerca Joomla e SEO.** Voci di menu, URL leggibili, metadati, immagini social per schede pubbliche; integrazione Smart Search. Area personale, risultati e contenuti protetti esclusi dall'indicizzazione. Il motore di ricerca applica gli stessi vincoli di accesso della navigazione.

## 6. Progettazione e pubblicazione dei corsi

**AUT-01 — Editor corso.** Creazione guidata per dati generali, programma, accessi, completamento e attestato. Ordinamento di moduli e lezioni con trascinamento e alternativa da tastiera. Duplicazione corso, modulo e lezione.

**AUT-02 — Ciclo editoriale.** Bozza → in revisione → pubblicato → archiviato. Pubblicazione riservata ai ruoli autorizzati; date di inizio/fine opzionali. Anteprima come studente senza alterare statistiche reali.

**AUT-03 — Controlli prima della pubblicazione.** Impedire corsi senza lezioni obbligatorie, quiz senza domande valide, prerequisiti circolari, risorse mancanti note e requisiti incompatibili. Avvisare per sottotitoli mancanti, durata assente e provider non tracciabile. Un corso solo consultabile e privo di completamento è una modalità esplicita distinta.

**AUT-04 — Versioni.** Le modifiche di struttura, obbligatorietà o valutazione generano una nuova versione pubblicata. Gli iscritti esistenti restano sulla versione assegnata. Correzioni editoriali minori possono aggiornare la stessa versione con registro delle modifiche; il sistema chiede di classificare la modifica. Migrazione degli iscritti solo esplicita, con anteprima dell'impatto e mappatura delle equivalenze. Un semplice aggiornamento non revoca attestati né azzera risultati.

**AUT-05 — Lingue.** Interfaccia traducibile e contenuti con lingua assegnata. Nella prima versione le traduzioni dei corsi hanno iscrizioni distinte; la condivisione dei progressi tra traduzioni è un'opzione futura da progettare.

## 7. Tipi di lezione e materiali

| Tipo | Funzioni | Regola di completamento |
|---|---|---|
| Testo/HTML | Editor Joomla, immagini, collegamenti, tabelle accessibili | Conferma esplicita dello studente |
| Video locale | Player responsive, ripresa, sottotitoli e trascrizione | Soglia di fruizione configurabile |
| Video esterno | Provider abilitati, consenso quando necessario, ripresa se supportata | Soglia solo con integrazione di tracciamento; altrimenti conferma esplicita |
| Audio | Player, ripresa, trascrizione | Conferma; soglia solo se implementata e validata |
| PDF/documento | Visualizzazione e download autorizzato, alternativa al viewer | Conferma esplicita |
| Quiz | Domande, feedback e risultato | Superamento quando obbligatorio |
| Compito, fase successiva | Consegna file/testo, rubrica di valutazione, feedback | Approvazione o voto richiesto |
| Sessione live, fase successiva | Calendario, link, presenze validate | Regola dichiarata prima dell'iscrizione |
| Pacchetto interoperabile, fase successiva | Adapter SCORM/xAPI/LTI secondo standard scelto | Regola del pacchetto e mappatura nel LMS |

**LES-01.** Ogni lezione ha titolo, contenuto, disponibilità, obbligatorietà e regola di completamento. Ogni versione del corso e ciascuna lezione possono avere materiali extra PDF e altri file, con ambiti separati e gestione specificata nella sezione 38. Gli allegati aggiuntivi non diventano automaticamente attività obbligatorie.

**LES-02.** Consentire solo provider e incorporamenti autorizzati; niente script o iframe arbitrari per autori non autorizzati. I collegamenti esterni non devono ricevere credenziali del sito.

**LES-03.** I file riservati sono consegnati tramite controllo autorizzativo o URL temporanei. Un MP4 collocato in una cartella pubblica resta accessibile a chi ne conosce l'URL. La protezione dall'accesso diretto non impedisce registrazione dello schermo o copia da parte di un utente autorizzato.

**LES-04.** Il servizio LMS non include transcodifica o distribuzione video illimitata. Hosting video esterno o storage/CDN sono integrazioni da scegliere secondo volumi e riservatezza.

### 7.1 Provider video del primo rilascio

I tre provider richiesti sono YouTube, Vimeo e Cloudflare Stream. L'integrazione comprende il player e il tracciamento didattico supportato, non il solo inserimento di un iframe. Un corso può contenere lezioni con provider diversi.

| Provider | Identificazione del contenuto | Player e tracking | Controlli di distribuzione |
|---|---|---|---|
| YouTube | URL/ID del video con convalida dei formati supportati | IFrame Player API: stato, posizione, durata e ripresa; campionamento della posizione durante la riproduzione | Nessuna autenticazione Joomla sul servizio YouTube; un link non in elenco è condivisibile |
| Vimeo | URL/ID; conservare l'eventuale privacy hash necessario ai video non in elenco | Player SDK: eventi, posizione, durata e ripresa | Impostazioni di privacy e restrizione dell'embed ai domini autorizzati, secondo configurazione dell'account |
| Cloudflare Stream | Video UID o URL riconosciuto, associato alla configurazione dell'account | Stream Player API: eventi e posizione; ripresa e tracking uniformati dal LMS | Modalità riservata con requireSignedURLs, token temporanei generati dal server e origini consentite |

**VID-01 — Astrazione comune.** Implementare adapter come plugin Joomla del gruppo dedicato jlmsvideo, secondo il contratto della sezione 37, con elenco esplicito delle capacità disponibili: durata, seek/ripresa, cambio velocità, sottotitoli, eventi e riproduzione protetta. SDK e differenze di provider non devono entrare nelle regole di calcolo del completamento. Ogni adapter applica le condizioni del proprio servizio; non altera o oscura arbitrariamente controlli, attribuzioni o elementi del player.

**VID-02 — Editor.** Campi: provider, URL/ID video, titolo, poster locale, trascrizione, eventuali sottotitoli, regola e soglia di completamento. Anteprima e verifica della configurazione; durata letta dal player o dalle API quando disponibile, altrimenti inserita come valore dichiarato. Niente credenziali per singola lezione o codice embed arbitrario come modalità normale. L'importazione automatica di metadati è limitata agli endpoint ufficiali e non deve trasformarsi in un recupero server di URL arbitrari.

**VID-03 — Tracking.** Gestire play, pausa, fine, ricerca di posizione, durata, errori e intervalli riprodotti. Salvare posizione e progresso periodicamente e nei punti di pausa/uscita per quanto consentito dal browser; un ultimo evento di chiusura può andare perso. La ripresa usa l'ultimo salvataggio confermato. Nessuna lezione viene completata solo perché il player segnala “fine”. Rientrano nei test iOS Safari, Android Chrome e browser desktop della matrice concordata.

**VID-04 — Token Cloudflare.** Per i video riservati verificare sul server identità, livello di accesso corrente, disponibilità del corso e ulteriori vincoli didattici prima di rilasciare un token. Richiedere signed URLs sul contenuto, impostare durata limitata e rinnovo autorizzato, non abilitare il download salvo scelta esplicita. Nessuna chiave API o chiave di firma nel browser, nei contenuti o nei log. La restrizione di dominio è complementare, non sostituisce il controllo utente. Anche poster e altre risorse esterne devono rispettare la configurazione di riservatezza e consenso.

**VID-05 — Revoca durante la visione.** La perdita del livello di accesso impedisce nuove autorizzazioni e rinnovi. Un token Cloudflare già emesso può restare valido fino a scadenza e il buffer già ricevuto può proseguire: documentare il tempo massimo residuo, senza promettere revoca istantanea. La durata dei token è un parametro tecnico da fissare e collaudare. La restrizione embed Vimeo non costituisce un controllo per singolo abbonato. Nessun provider elimina il rischio di copia dello schermo.

**VID-06 — Privacy e disponibilità.** Non caricare iframe, SDK, poster remoti o richieste al provider prima delle condizioni di caricamento configurate dal sito. Gestire consenso non disponibile/rifiutato, video eliminato, embed disabilitato, errore rete e restrizione di dominio con messaggi distinti. Nessun errore attribuisce un completamento automatico. La compatibilità con il gestore consensi adottato dal sito e l'eventuale alternativa didattica sono da definire.

**VID-07 — Gestione della libreria.** Nel primo rilascio caricare e amministrare i video nei portali dei provider, poi collegarli dal LMS. Upload diretto da Joomla, selettore remoto dell'intera libreria e sincronizzazione editoriale sono evoluzioni fuori dal rilascio 1, non implicite nel supporto del player. Gli accessi API necessari alla firma Cloudflare restano comunque nel primo rilascio.

**VID-08 — Sostituzione del video.** Posizione e intervalli fruiti sono associati all'identità/versione della risorsa. La sostituzione sostanziale del video segue AUT-04, con nuova versione o equivalenza esplicita; un semplice cambio di URL non deve applicare percentuali del vecchio video a un contenuto diverso.

## 8. Iscrizioni, disponibilità e diritto di accesso

**ISC-01 — Accesso e iscrizione didattica.** Ogni corso ha un campo obbligatorio Livello di accesso, selezionato dai viewing access levels nativi Joomla. Il LMS verifica i livelli autorizzati dell'utente, senza conoscere piano commerciale o stato dei pagamenti. L'iscrizione didattica registra frequenza e risultati e non può concedere accesso a chi non soddisfa il livello del corso. Default confermato: creazione automatica al click Inizia. Modalità esplicita o approvazione sono opzioni alternative del corso. Iscrizione manuale e import CSV restano disponibili con anteprima, righe scartate e prevenzione duplicati; nessuna assegnazione automatica di gruppi commerciali. L'import non invia comunicazioni finché l'operatore non seleziona l'invio. Nessuna password in chiaro nei file.

**ISC-02 — Stati.** Richiesta, attiva, sospesa, scaduta, annullata. La conclusione didattica è registrata separatamente: uno studente può aver completato un corso e poi avere accesso scaduto.

**ISC-03 — Calendario didattico.** Eventuali finestre di erogazione o scadenze formative sono vincoli del corso, aggiuntivi al livello di accesso, e non scadenze di abbonamento. Scadenze mostrate con fuso orario riconoscibile. Sospensione didattica e chiusura della finestra bloccano nuove attività senza cancellare lo storico. Una proroga è tracciata. Il LMS non calcola durate commerciali né rinnovi.

**ISC-04 — Precedenza.** Per fruire di una lezione servono account abilitato, corso/versione disponibile, visibilità autorizzata, iscrizione valida quando richiesta e prerequisiti soddisfatti. Un'anteprima pubblica costituisce un'eccezione esplicita per la sola consultazione; non produce attestati anonimi.

**ISC-05 — Rientro e rinnovo.** Riattivazione del diritto di accesso e nuova edizione formativa sono operazioni distinte. Il rinnovo dell'accesso conserva i progressi; una nuova edizione può richiedere nuova frequenza.

**ISC-06 — Confine con gli abbonamenti.** L'estensione esterna assegna/rimuove gruppi Joomla e gestisce piani, ordini, incassi, rinnovi, scadenze, insoluti e rimborsi. Il LMS legge soltanto l'autorizzazione risultante in Joomla. Nessun webhook di pagamento, gateway, adapter commerce o stato commerciale è richiesto nel perimetro del LMS. Configurazione e corretto aggiornamento dei gruppi sono responsabilità dell'estensione esterna e dell'amministratore del sito; il relativo scenario di integrazione va comunque collaudato.

**ISC-07 — Perdita e recupero dell'accesso.** Il controllo del livello si applica a ogni richiesta protetta, inclusi salvataggi, consegna quiz, download e rilascio/rinnovo token video. Non basta verificarlo al login: eliminare le autorizzazioni obsolete nelle sessioni persistenti e nelle cache secondo i meccanismi supportati dalla release Joomla target. La perdita del livello blocca nuove operazioni LMS e non cancella progressi, tentativi o attestati già emessi. Al ripristino del livello lo studente ritrova il proprio storico, salvo nuova edizione formativa o cancellazione prevista dalla politica adottata. Un quiz già avviato non attribuisce più risultati da nuove risposte non autorizzate: il server può chiuderlo sulle risposte già salvate secondo la sua scadenza. Questa regola prevale sulla tolleranza per la sola scadenza didattica descritta in QUI-05.

**ISC-08 — Livello unico e vincoli di lezione.** Un corso seleziona un livello Joomla, che può includere più gruppi: non servono livelli commerciali o gerarchie “premium” proprietarie. Le lezioni seguono di default l'accesso del corso; eventuali restrizioni ulteriori non possono ampliare l'accesso del corso. Anteprime pubbliche sono eccezioni esplicite separate dalla fruizione protetta. Un percorso che include corsi con accessi diversi non concede automaticamente accesso a tutti i corsi.

## 9. Esperienza dello studente

**STU-01 — Area personale.** I miei corsi, stato e avanzamento; corsi conclusi separati da quelli in corso; attestati; attività da correggere o ripetere. Un'azione Riprendi porta all'ultima attività valida. Mostrare la ragione di un blocco e l'azione disponibile.

**STU-02 — Aula.** Contenuto centrale, indice di moduli e lezioni, stato delle attività, navigazione precedente/successiva e materiali contestuali. Su smartphone l'indice è apribile senza sottrarre spazio costante al contenuto; su PC si evita di costringere a scorrere l'intera pagina per cambiare lezione.

**STU-03 — Persistenza.** Progressi associati all'account, ripresa su altro dispositivo e messaggi chiari su salvataggio riuscito, in corso o fallito. Nessuna modalità offline promessa nella prima release.

**STU-04 — Sequenza.** Corso libero oppure sequenziale. Lezioni facoltative escluse dai blocchi salvo prerequisito esplicito, evidenziato all'autore. Contenuti non ancora disponibili mostrano la data. Nessun requisito deve dipendere da un'attività nascosta o irraggiungibile.

**STU-05 — Preferenze.** Lingua e velocità del player quando disponibili. Segnalibri e appunti privati nella seconda fase; esportazione degli appunti nell'ambito dei dati personali.

## 10. Regole di avanzamento e completamento

**PRO-01.** Stati lezione: non iniziata, in corso, completata. Per il quiz aggiungere tentativo in corso ed esito; per il compito consegnato, in valutazione, approvato o da ripetere. Il fallimento di un quiz non completa una lezione con requisito di superamento.

**PRO-02.** Avanzamento del corso = lezioni obbligatorie completate / lezioni obbligatorie previste dalla versione assegnata × 100. Percentuale mostrata arrotondata per difetto fino al completamento effettivo; nessun 100% anticipato. I corsi senza attività obbligatorie non possono rilasciare attestati di completamento.

**PRO-03.** Corso completato quando tutte le attività obbligatorie soddisfano la rispettiva regola e le eventuali approvazioni sono concluse. Uno o più quiz obbligatori sono già attività richieste; ulteriori soglie aggregate vanno introdotte solo con una configurazione esplicita. Stato e data di completamento sono registrati dal server.

**PRO-04.** Per video tracciabili, soglia predefinita: 90% degli intervalli unici del contenuto riprodotti. La soglia è configurabile, non una misura di apprendimento. Saltare alla fine non deve contare gli intervalli saltati. Non si sommano due volte le stesse porzioni viste. Cambio velocità, ripresa e sovrapposizione di sessioni hanno regole uniformi. L'evento ricevuto dal player non prova presenza o identità.

**PRO-05.** Se un provider non permette il tracciamento, l'autore deve scegliere una regola supportata prima della pubblicazione. Un errore momentaneo del player non converte automaticamente il requisito in completamento manuale.

**PRO-06.** Eventi duplicati e richieste ritentate non duplicano completamenti, attestati o conteggi. Modifiche da più dispositivi conservano i progressi già acquisiti; una posizione video più vecchia non deve sovrascrivere automaticamente una più recente. Consentire comunque allo studente di rivedere parti già fruite.

**PRO-07.** Rettifica manuale e reset richiedono permesso specifico, ambito, motivazione e conferma. Registrare prima/dopo e operatore. Nessuna distruzione silenziosa dello storico; emissione e revoca di attestati restano operazioni distinte.

## 11. Quiz e valutazione

**QUI-01 — Formati.** Prima release: scelta singola, vero/falso e scelta multipla. Per la multipla applicare corrispondenza esatta dell'insieme scelto, senza punteggio parziale. Risposte aperte e rubriche nella seconda fase; abbinamenti e ordinamento opzionali.

**QUI-02 — Banca domande.** Domande categorizzate e versionate, punti, spiegazione e risposte. Selezione fissa oppure casuale con estrazione riproducibile per tentativo. Non ammettere un'estrazione con più domande di quelle disponibili; domande estratte e ordine restano gli stessi alla ripresa.

**QUI-03 — Configurazione.** Soglia di superamento, durata, numero tentativi, intervallo tra tentativi, disponibilità temporale, ordine casuale e modalità di feedback. Test di esercitazione distinto dal test valutativo. Per la prima release punteggi non negativi; nessuna penalità implicita.

**QUI-04 — Calcolo.** Percentuale = punti ottenuti / punti massimi delle domande del tentativo × 100. Le omissioni valgono zero. Il confronto con la soglia usa il valore non arrotondato; il display può mostrare decimali. Ai fini del superamento vale il miglior tentativo concluso; la regola non cambia retroattivamente.

**QUI-05 — Timer e disconnessione.** Scadenza autorevole lato server. Salvataggio periodico delle risposte e indicatore dell'ultimo salvataggio. Alla scadenza, chiudere e valutare le risposte salvate. Chiudere la scheda non ferma il timer. La scadenza del diritto di accesso impedisce nuovi tentativi; per quello già iniziato si propone conclusione entro la scadenza del tentativo, salvo revoca esplicita di sicurezza. La regola è comunicata all'avvio.

**QUI-06 — Integrità.** Un solo tentativo attivo dello stesso quiz per iscrizione; più schede condividono lo stesso tentativo. Invio finale idempotente. Correzioni e risposte esatte restano sul server fino al momento consentito. Modificare la banca domande non altera tentativi iniziati o conclusi.

**QUI-07 — Esito.** Mostrare punteggio, superamento, tentativi residui, prossimo tentativo e feedback previsto. Non mostrare risposte corrette prima della chiusura quando il test è riutilizzabile. Una correzione errata del test si gestisce con rivalutazione tracciata, non alterando silenziosamente il passato.

**QUI-08 — Accessibilità.** Tempo aggiuntivo individuale autorizzato e registrato; avvisi prima della scadenza; navigazione da tastiera. La piattaforma non include proctoring né garantisce l'identità di chi risponde.

## 12. Attestati

**ATT-01.** Emissione automatica dopo il completamento verificato dal server. Attestato PDF con nome, titolo corso, eventuale durata dichiarata, data, organizzazione emittente, identificativo univoco e modello grafico. La durata nominale è distinta dal tempo misurato.

**ATT-02.** Modello con sfondo, logo e campi configurabili; anteprima con nomi e titoli lunghi. Prima release con posizionamento e dimensioni configurabili; designer visuale completo nella seconda fase.

**ATT-03.** Un'emissione per iscrizione/versione; download ripetuto dello stesso documento. Conservare i dati al momento dell'emissione e la versione del modello. Un cambio di nome sul profilo non riscrive il PDF già emesso; la correzione produce un documento sostitutivo collegato al precedente.

**ATT-04.** Revoca motivata, sostituzione e registro. Eventuale pagina di verifica con codice non enumerabile e QR, informazioni minime e stato valido/revocato; nessun elenco pubblico degli studenti. Verifica pubblica opzionale, da configurare secondo la finalità.

**ATT-05.** Attestato interno di completamento come impostazione iniziale. Crediti, qualifiche riconosciute, firma digitale e certificazione regolamentata non sono impliciti e richiedono un perimetro dedicato.

## 13. Gestione didattica e report

**REP-01 — Pannello.** Corsi pubblicati, iscrizioni, iscritti attivi, completamenti, esiti quiz, attestati emessi e attività recente. Filtri per corso/versione, periodo, docente e, quando disponibili, gruppi.

**REP-02 — Dettaglio corso.** Elenco iscritti con stato accesso, avanzamento, primo e ultimo evento, risultato dei quiz e attestato. Accesso al dettaglio studente limitato ai ruoli e corsi assegnati.

**REP-03 — Esportazioni.** CSV UTF-8 con colonne documentate, date e fuso orario; autorizzazioni, filtri e tracciamento dell'export. Neutralizzare formule nei valori esportati. Nessuna risposta libera o dato extra incluso per default.

**REP-04 — Dizionario KPI.** Iscritto: iscrizione attivata nel perimetro; avviato: prima attività didattica; completato: requisiti soddisfatti; attivo: evento didattico nel periodo selezionato. Tasso di completamento per coorte = completati nella coorte alla data di osservazione / iscrizioni attivate nella coorte non annullate. Mostrare anche numero di sospesi/scaduti. Non confrontare senza spiegazione coorti appena avviate e coorti mature.

**REP-05 — Tempi.** Eventuale tempo attivo è una stima con regole dichiarate di inattività, non il tempo tra apertura e chiusura pagina. La prima release può limitarsi a date, eventi e completamenti, senza fornire ore di frequenza apparentemente certificate.

## 14. Notifiche e comunicazioni

**NOT-01.** Messaggi di iscrizione, approvazione/rifiuto, scadenza imminente, completamento e attestato disponibile. Template traducibili e configurabili; nessuna comunicazione promozionale implicita.

**NOT-02.** Coda con ritentativi limitati, prevenzione duplicati e log dell'esito. L'indisponibilità della posta non annulla un quiz o un completamento. Un attestato resta disponibile nell'area personale anche se l'email fallisce.

**NOT-03.** Promemoria inattività e comunicazioni di gruppo nella fase successiva, con frequenza e disattivazione configurabili. Separare avvisi di servizio da iscrizioni marketing.

## 15. Moduli evolutivi

| Area | Requisiti del modulo | Perimetro |
|---|---|---|
| Gruppi/classi | Import, assegnazioni, tutor, scadenze, report circoscritti | Fase 2; un gruppo non costituisce isolamento multi-tenant |
| Compiti | Upload protetti, limiti, versioni consegna, correzione, rubriche, nuova consegna | Fase 2 |
| Interazione | Domande per lezione, moderazione, notifiche, segnalazioni | Fase 2; forum generalista come integrazione |
| Live | Eventi, fusi orari, link, presenze confermate o importate | Fase 3; click sul link non equivale a presenza |
| Vendita e abbonamenti | Gestiti da estensione dedicata che aggiorna i gruppi Joomla | Fuori perimetro del LMS; nessun modulo commerciale previsto |
| Interoperabilità | Standard/versione, import, player o servizio esterno, tracking, test di conformità | Fase 3; nessun generico “supporto SCORM” |
| SSO/API | Provider identità, provisioning, revoca, scope API e rate limit | Fase 3 |
| Gamification | Badge e obiettivi; classifiche solo se necessarie e configurabili | Opzionale |

Il contratto funzionale con l'estensione commerciale è il sistema gruppi/livelli di accesso Joomla, come definito in ISC-06–08. Il LMS non interpreta notifiche dei gateway e non duplica lo stato dell'abbonamento. Il collaudo verifica che la variazione di un gruppo aggiorni effettivamente l'accesso anche di un utente già collegato. Il comportamento di tentativi e risorse già aperti è documentato in ISC-07 e VID-05.

## 16. Privacy, sicurezza e accessibilità

Requisiti di prodotto, non attestazione di conformità giuridica del futuro sito. Finalità, basi giuridiche, informative, conservazione e gestione di eventuali minori dipenderanno dal titolare e dal contesto d'uso.

**SIC-01.** Account e autenticazione Joomla, privilegi minimi, protezione CSRF, validazione e sanificazione contenuti, query parametrizzate e controllo accessi per ogni oggetto. Verificare espressamente richieste con identificativi di altri utenti.

**SIC-02.** Upload con limiti di formato, dimensione, nome e MIME, conservazione non eseguibile e accesso controllato; scansione se prevista dall'ambiente. Import di pacchetti futuri in ambiente confinato, con protezione da percorsi malevoli e script non attendibili.

**PRI-01.** Raccogliere solo dati necessari; impostazioni di conservazione distinte per progressi, tentativi, attestati, audit e notifiche. Fornire anteprima delle cancellazioni, eccezioni motivate e operazioni di anonimizzazione coerenti con la finalità. Non fissare arbitrariamente periodi “a norma”.

**PRI-02.** Integrazione con gli strumenti privacy Joomla per esportazione e gestione delle richieste relative ai dati del componente. Definire il comportamento alla rimozione dell'account e l'effetto sulla verifica degli attestati. Il processo deve gestire dati storici e backup secondo la politica adottata, senza promettere cancellazione immediata da ogni copia.

**PRI-03.** Provider video esterni caricati solo secondo la configurazione del sito e le scelte di consenso applicabili; prima del caricamento usare una schermata locale e indicare il provider. Il consenso per un embed non va confuso con la base del trattamento dei progressi. Nessuna telemetria esterna del componente attiva per default.

**ACC-01.** Obiettivo progettuale WCAG 2.2 AA per le schermate realizzate: tastiera, focus visibile, etichette, errori comprensibili, contrasto, zoom, ridisposizione, alternative testuali, sottotitoli e trascrizioni dove pertinenti. Verifica automatica e manuale; il componente non può rendere accessibile da solo un PDF o un video caricato dall'autore.

## 17. Integrazione e operatività Joomla

**JOO-01.** Pacchetto installabile contenente componente principale, estensioni necessarie e moduli di visualizzazione opzionali. Usare utenti, ACL, editor, lingue, menu, routing e aggiornamenti Joomla; nessuna modifica al core. Nome tecnico e marchio da definire.

**JOO-02.** Frontend con layout sovrascrivibili, ID pubblici stabili e CSS circoscritto al componente, come specificato in 17.3. Funzionamento verificato con template standard e template personalizzato; palette, tipografia, spaziature e presentazione configurabili senza alterare le regole didattiche. Nessuna dipendenza obbligatoria da un template o marchio.

**JOO-03.** Installazione pulita, aggiornamenti del database preservando i dati, preflight dei requisiti, backup e procedura di ripristino verificata. La disinstallazione deve dichiarare chiaramente se conserva i dati; cancellazione definitiva separata e intenzionale.

**JOO-04.** Attività pianificate per notifiche, scadenze e pulizie, con diagnostica dell'ultima esecuzione. Le autorizzazioni e le scadenze sono comunque controllate al momento dell'accesso: un task in ritardo non deve lasciare aperto un corso scaduto.

**JOO-05.** Prestazioni: dimensionamento per centinaia di studenti simultanei, elenchi paginati, report indicizzati, elaborazioni lunghe in background e video separati dal traffico applicativo. I dati personali non entrano in cache pubbliche. Applicare requisiti e profilo di collaudo della sezione 17.4; nessuna capacità garantita senza prova sull'ambiente di riferimento dichiarato.

### 17.1 Matrice obbligatoria di integrazione con il core

Tutte le integrazioni seguenti rientrano nel rilascio 1 per le entità e schermate disponibili in quel rilascio. Richiedono implementazione esplicita dei servizi, contesti o plugin necessari: non sono acquisite automaticamente installando il componente.

| ID | Funzione Joomla | Requisito per il LMS | Verifica funzionale |
|---|---|---|---|
| CORE-01 | Categorie / com_categories | Gestione categorie nativa nel contesto del componente, gerarchia, ordinamento, stato, lingua, accesso e permessi; nessuna tabella di tassonomia parallela | Creare e modificare categorie dal gestore Joomla e ritrovarle nei corsi e nei filtri; verificare restrizioni e spostamenti |
| CORE-02 | Utenti / com_users | Account, registrazione, attivazione, autenticazione, recupero credenziali, blocco e profilo tramite Joomla; rispettare MFA e provider configurati | Uno stesso account accede al sito e al LMS; account bloccato perde accesso senza cancellare implicitamente lo storico |
| CORE-03 | Gruppi utenti e livelli di accesso | Usare gruppi e viewing access levels Joomla; nessun elenco parallelo di identità o livelli; gruppi didattici restano oggetti distinti | Cambiare il livello autorizzato e verificare gli effetti su catalogo, aula, download e ricerca |
| CORE-04 | ACL e permessi | Azioni native create/edit/edit-own/edit-state/delete e permessi LMS aggiuntivi per iscrizioni, valutazioni, report e rettifiche; ereditarietà componente/categoria/record dove pertinente | Utente con edit-own modifica solo contenuti propri; negazioni e limitazioni applicate anche agli endpoint diretti |
| CORE-05 | Template override e layout | Viste frontend, moduli distribuiti e sottolayout personalizzabili con i meccanismi standard; logica didattica e autorizzativa fuori dai template | Personalizzare scheda e aula tramite template override; aggiornare il componente senza sovrascrivere i file di override |
| CORE-06 | Lingue e language override | Stringhe in file lingua Joomla, frontend/backend, messaggi, errori, email e testo generato; nessun testo di interfaccia incorporato nel codice; pacchetti it-IT ed en-GB | Cambiare lingua e sostituire una stringa con Language Overrides senza modificare il pacchetto |
| CORE-07 | Multilingua e associazioni | Lingua dei contenuti, filtro lingua e associazioni fra traduzioni di corsi/categorie secondo i contesti supportati; rispetto delle voci menu multilingua | Cambio lingua conduce alla scheda associata; in assenza di traduzione fallback dichiarato; nessun trasferimento implicito dei progressi |
| CORE-08 | Privacy / com_privacy | Plugin privacy del componente: dichiarazione delle capacità, export e gestione delle richieste di rimozione; dati su iscrizioni, progressi, tentativi, attestati e appunti quando presenti | Una richiesta Joomla include i dati LMS; cancellazione o impedimenti motivati sono gestiti dal processo nativo |
| CORE-09 | Tag / com_tags | Tag nativi per corsi e per le altre entità editoriali previste; contesti e associazioni correttamente registrati | Un tag Joomla compare nell'editor, nei filtri e nelle visualizzazioni previste senza esporre contenuti riservati |
| CORE-10 | Campi personalizzati / com_fields | Campi e gruppi di campi nativi per corsi e lezioni, con categorie, lingua, accesso e resa nei layout; nessun designer proprietario per gli stessi metadati | Aggiungere un campo dall'amministrazione Joomla e usarlo senza modificare codice o schema LMS |
| CORE-11 | Editor e content plugin | Editor selezionato in Joomla; applicare gli eventi di preparazione contenuto nei contesti documentati, rispettando filtri e autorizzazioni | Cambiare editor e usare un content plugin ammesso nelle descrizioni/lezioni; nessuna esecuzione arbitraria di script |
| CORE-12 | Media Manager | Selezione immagini e risorse pubbliche tramite gestore media e provider configurati; materiali didattici riservati attraverso consegna protetta | Immagine corso selezionabile dal gestore nativo; allegato riservato non reso pubblico per effetto della selezione |
| CORE-13 | Menu, moduli e routing | Voci menu native per catalogo, categoria, percorso, corso e area personale; parametri, breadcrumbs, URL SEF e associazioni lingua | Creare le voci dal gestore menu, assegnare moduli per pagina e navigare con SEF attivo/disattivo |
| CORE-14 | Smart Search / com_finder | Plugin Finder, contesti e aggiornamento dell'indice su modifica/stato/accesso/lingua; indicizzare solo schede o parti di contenuto autorizzate | Ricerca aggiornata dopo modifica o ritiro; nessuna fuga di testo riservato in titolo, anteprima o filtri |
| CORE-15 | Workflow e stati editoriali | Integrare i workflow nativi per i corsi; lezioni e altre entità editoriali usano gli stati nativi e, quando previsto, lo stesso servizio workflow. Le transizioni sono autorizzate | Redattore propone e responsabile pubblica; gli stati non si confondono con completamento o iscrizione |
| CORE-16 | Content History e blocco modifica | Storico editoriale Joomla sulle entità supportate e check-in/check-out per evitare sovrascritture concorrenti | Confrontare/ripristinare una revisione; due autori non sovrascrivono silenziosamente lo stesso contenuto |
| CORE-17 | User Actions Log | Plugin per il registro azioni Joomla sulle operazioni amministrative rilevanti; audit didattico complementare e senza duplicazione indiscriminata di dati | Pubblicazione, modifica iscrizione, rettifica e revoca identificano operatore, oggetto e momento |
| CORE-18 | Posta e Mail Templates | Mailer e configurazione SMTP Joomla; template LMS registrati nel sistema Mail Templates, traducibili e con variabili documentate | Modificare un template dal gestore nativo; un guasto SMTP lascia l'evento didattico valido e la notifica ritentabile |
| CORE-19 | Scheduled Tasks | Plugin task per scadenze, notifiche e conservazione nel pianificatore Joomla; diagnostica ed esecuzioni sicure da ripetere | Configurare ed eseguire i task dall'amministrazione; una doppia esecuzione non duplica notifiche o emissioni |
| CORE-20 | Configurazione e interfaccia amministrativa | Opzioni del componente, form, toolbar, filtri, ordinamento, paginazione, azioni batch e cestino secondo le convenzioni Joomla | Gestione coerente con il backend e permessi applicati a tutte le operazioni batch |
| CORE-21 | Asset, cache e sessione | Web Asset Manager, sessione/token e caching Joomla; cache privata o disabilitata sulle viste personali | Nessun risultato di uno studente servito a un altro; asset caricati solo quando richiesti |
| CORE-22 | Installazione e aggiornamenti | Manifest, update server, gestione dipendenze e migrazioni supportate; messaggi post-installazione dove necessari | Installare e aggiornare dagli strumenti Joomla senza modificare il core o perdere dati |
| CORE-23 | Eventi e plugin | Pubblicare eventi LMS documentati tramite il sistema eventi Joomla, con contesto e dati minimi; usare i servizi core nei punti applicabili | Un plugin estende il comportamento senza patch del componente e senza aggirare autorizzazioni |

Il prefisso tecnico definitivo dell'estensione sarà scelto con il nome del prodotto. Categorie, campi e tag sono gestiti nei contesti registrati per l'estensione: l'uso dei servizi core non significa mescolare automaticamente categorie di articoli e corsi.

### 17.2 Distinzioni da preservare

- **Gruppo Joomla:** assegna permessi e livelli di visibilità. **Classe/gruppo didattico:** assegna corsi, tutor e scadenze. Possono essere collegati con regole esplicite senza diventare lo stesso oggetto.
- **Livello di accesso:** determina la visibilità. **Iscrizione:** determina il diritto didattico secondo stato e scadenza. Servono entrambi quando configurati.
- **Revisione Joomla:** registra le modifiche editoriali. **Versione didattica:** fissa struttura e requisiti applicabili agli iscritti. Il ripristino di una revisione non deve modificare retroattivamente attestati e risultati.
- **Workflow editoriale:** governa revisione e pubblicazione. **Stato didattico:** governa fruizione, superamento e completamento.
- **Media pubblici:** gestiti dal servizio nativo. **File riservati:** richiedono autorizzazione alla consegna; non basta il controllo dell'editor media.
- **Override:** personalizza la presentazione. Nessuna decisione su esiti, accessi o punteggi deve dipendere da un file di override.

Le API web Joomla del LMS restano nel rilascio 3 perché sono una superficie funzionale aggiuntiva: saranno implementate come estensione delle Web Services native, senza un sistema parallelo. L'integrazione con le API interne del core è invece obbligatoria già nel rilascio 1. Le funzionalità core prive di relazione con il LMS non richiedono schermate o servizi fittizi; le esclusioni vanno documentate e non possono comprendere le integrazioni obbligatorie sopra elencate.

### 17.3 Contratto pubblico di personalizzazione delle viste

Il prefisso neutro `jlms` è proposto per gli esempi; il prefisso definitivo sarà congelato con il nome tecnico prima dello sviluppo. Gli identificatori documentati costituiscono un'interfaccia pubblica del componente: non cambiano a ogni aggiornamento.

| Vista/area | ID di riferimento proposti | Parti interne da rendere indirizzabili |
|---|---|---|
| Radice del componente | `#jlms` con `data-jlms-view` | Tipo di vista e contesto del record, senza dati personali |
| Elenco categorie | `#jlms-categories` | Intestazione, descrizione, elenco, paginazione |
| Categoria | `#jlms-category` | `#jlms-category-header`, `#jlms-category-description`, `#jlms-subcategories`, `#jlms-category-courses` |
| Catalogo corsi | `#jlms-courses` | `#jlms-courses-header`, `#jlms-courses-filters`, `#jlms-courses-results`, `#jlms-courses-pagination` |
| Scheda corso | `#jlms-course` | `#jlms-course-header`, `#jlms-course-cover`, `#jlms-course-summary`, `#jlms-course-metadata`, `#jlms-course-teachers`, `#jlms-course-syllabus`, `#jlms-course-actions` |
| Elenco percorsi e percorso | `#jlms-pathways`, `#jlms-pathway` | Intestazione, descrizione, corsi, avanzamento e azioni |
| Docenti e scheda docente | `#jlms-teachers`, `#jlms-teacher` | Foto, biografia, metadati e corsi associati |
| Aula/lezione | `#jlms-classroom` | `#jlms-lesson-header`, `#jlms-lesson-content`, `#jlms-video-player`, `#jlms-lesson-outline`, `#jlms-lesson-resources`, `#jlms-lesson-navigation`, `#jlms-lesson-progress` |
| Quiz e risultato | `#jlms-quiz`, `#jlms-quiz-result` | Istruzioni, timer, domande, azioni, messaggi e riepilogo |
| Area personale | `#jlms-dashboard`, `#jlms-my-courses` | Filtri, elenco, avanzamento, azioni e stato vuoto |
| Attestati e verifica | `#jlms-certificates`, `#jlms-certificate-verification` | Elenco, azioni, stato e riepilogo dei dati pubblicabili |
| Iscrizione/richiesta accesso | `#jlms-enrollment` | Istruzioni, campi, errori e azioni |

**UI-01 — Copertura.** Ogni regione significativa ha un ID documentato; contenitori e componenti ripetuti espongono classi semantiche stabili, ad esempio `.jlms-course-card`, `.jlms-course-card__title`, `.jlms-category-card`, `.jlms-button`, `.jlms-progress`, `.jlms-notice`. La specifica delle schermate completerà la tabella con tutti i selettori, gli stati vuoti, gli errori, le anteprime e le viste riservate accessibili dal frontend.

**UI-02 — Unicità e riuso.** Gli ID sono unici nell'intero documento HTML; niente ID duplicati per le schede di un elenco. Per indirizzare singoli record usare ID composti da contesto, entità e identificativo stabile, ad esempio `jlms-catalog-course-42`; le istanze di moduli Joomla includono il proprio module ID e non riutilizzano gli ID della vista principale. Per una stessa tipologia usare classi comuni. Nessun ID derivato da titolo, traduzione o posizione nell'elenco.

**UI-03 — Personalizzazione visuale.** Fornire variabili CSS per colori, superfici, testo, font, dimensioni, spaziature, bordi, raggi, ombre, larghezza contenuti e spazi delle griglie. Esempi: `--jlms-color-primary`, `--jlms-color-text`, `--jlms-color-surface`, `--jlms-font-family`, `--jlms-radius`, `--jlms-content-max-width`, `--jlms-grid-gap`. Definire scale coerenti per le varianti. Le variabili sono applicabili alla radice o alla singola vista tramite gli ID.

**UI-04 — Cascata e layout.** CSS base con bassa specificità, selettori circoscritti e uso minimo giustificato di `!important`; ID forniti come punti di personalizzazione, senza usarli sistematicamente per rendere troppo specifico il foglio base. Documentare come caricare il foglio del template/personalizzazione dopo gli asset base tramite i meccanismi Joomla. Consentire una modalità di stile essenziale mantenendo funzionalità e accessibilità. Non inserire grandi fogli inline o imporre un framework UI globale al template.

**UI-05 — Override e confine CSS.** ID, classi e variabili permettono la personalizzazione visuale; gli override nativi e i sottolayout consentono cambiamenti della struttura HTML e dell'ordine semantico. La personalizzazione non deve richiedere modifiche ai file distribuiti. Il CSS della pagina può modificare il contenitore di un player esterno, mentre i controlli interni all'iframe restano personalizzabili solo con le opzioni consentite dal provider. Non promettere che CSS del componente possa alterare liberamente un player cross-origin.

**UI-06 — Separazione dei comportamenti.** JavaScript usa hook dedicati `data-jlms-*` e servizi applicativi, senza dipendere da classi decorative, colori o ordine visuale dei blocchi. Mantenere etichette, relazioni ARIA, focus, contrasto e funzionamento da tastiera nei layout base; documentare le responsabilità degli override personalizzati. Lo stato visuale non determina l'autorizzazione.

**UI-07 — Documentazione e stabilità.** Distribuire una guida per vista con mappa DOM, ID/classi/variabili, esempi di CSS e percorsi degli override. Aggiornarla insieme a ogni aggiunta o modifica. Cambiamenti incompatibili richiedono versione dichiarata, changelog e guida di migrazione; ove possibile introdurre prima selettori compatibili. Testare almeno due personalizzazioni indipendenti e l'assenza di ID duplicati quando componente e moduli sono presenti nella stessa pagina.

### 17.4 Efficienza e studenti contemporanei

**PERF-01 — Definizione del carico.** Utenti registrati, sessioni aperte, studenti attivi e richieste simultanee sono grandezze distinte. La richiesta confermata è il supporto a centinaia di studenti attivi in contemporanea. Profilo di collaudo confermato: 300 studenti attivi per 60 minuti e picco di 500 per 10 minuti, con carico progressivo e prova oltre soglia per individuare il punto di saturazione. Il profilo è un obiettivo di accettazione, non una capacità già ottenuta.

**PERF-02 — Modello realistico.** Distribuzione di riferimento: 60% video, 20% quiz, 15% navigazione/consultazione materiali, 5% avvio/completamento corso e attestati. Gli utenti virtuali hanno account distinti e tempi di interazione realistici; non basta aprire sessioni inattive. Aggiungere prove concentrate di accesso ai corsi e invio quiz nello stesso momento. Dataset di riferimento: 10.000 utenti, 500 corsi e 100.000 iscrizioni con progressi/tentativi preesistenti. Volumi e distribuzione definitivi saranno riportati nel verbale di prova.

**PERF-03 — Budget di risposta.** Sull'ambiente di riferimento, misurare tempi HTTP end-to-end dalla sonda nella stessa regione: p95 entro 1,5 s per le pagine LMS e 800 ms per salvataggi di progresso/risposte; p95 entro 2 s per la consegna del quiz, con richiesta di attestato accodata se necessaria. Errori tecnici inattesi e timeout sotto l'1% delle richieste valide; zero perdite o duplicazioni di dati confermati e zero esposizioni tra utenti. Queste soglie sono obiettivi di collaudo confermati, non SLA commerciali. Misurare separatamente rendering client, caricamento dei provider e riproduzione video, esclusi dai tempi del backend Joomla.

**PERF-04 — Database.** Query paginated e filtri indicizzati; evitare query ripetute per ogni scheda o lezione (N+1). Indici coerenti con utente/iscrizione/versione/lezione/quiz, vincoli univoci e transazioni brevi per le operazioni sensibili. Aggiornamenti del progresso atomici e idempotenti, senza riscrivere l'intero storico del corso a ogni evento. Le viste di sintesi non devono ricostruire tutti gli eventi per ciascuno studente a ogni apertura pagina. Verificare piani di esecuzione e query lente sul dataset di riferimento.

**PERF-05 — Frequenza dei salvataggi.** Normalizzare gli eventi dei player nel browser e aggregare il progresso; non inviare una richiesta al server per ogni `timeupdate`. Intervallo di 20 s, configurabile fra 15 e 30 s durante riproduzione, con salvataggi aggiuntivi limitati su pausa, cambio lezione e completamento. Quiz con salvataggio raggruppato su modifica e periodico secondo il budget concordato. Distribuire temporalmente i salvataggi per evitare picchi sincronizzati, limitare payload e batch e gestire ritentativi con backoff senza duplicare eventi. Con 500 video attivi e un intervallo di 20 s si attendono circa 25 richieste/s di solo progresso: navigazione, quiz e altri eventi sono carico aggiuntivo.

**PERF-06 — Cache e autorizzazioni.** Cache dei metadati non personali con invalidazione su modifica/pubblicazione. Risultati, risposte, token e stato personale non entrano in cache pubbliche né nelle pagine HTML condivise. Quando una vista dipende da lingua o livelli di accesso, le chiavi o l'esclusione dalla cache devono rispecchiarlo. Nessuna ottimizzazione può prolungare silenziosamente un'autorizzazione revocata. Cache condivisa opzionale secondo infrastruttura; il prodotto non deve richiedere un servizio esterno proprietario per funzionare.

**PERF-07 — Video e file.** YouTube, Vimeo e Cloudflare erogano il flusso direttamente al browser: PHP Joomla non fa da proxy ai segmenti video. Token Stream generati sul server senza interrogare l'API del provider a ogni evento di progresso; chiavi e configurazioni protette. I video locali necessitano di consegna efficiente da web server o storage autorizzato e relativo collaudo: non si assume che un normale hosting PHP possa distribuire centinaia di flussi. Per file grandi prevedere consegna autorizzata senza mantenere inutilmente occupato un processo PHP.

**PERF-08 — Operazioni lunghe.** Email, esportazioni estese, report pesanti e produzione massiva di PDF in coda o a batch, con task Joomla e avanzamento; il scheduler attiva il lavoro ma non costituisce da solo una garanzia di capacità. Limitare batch, concorrenza e retry; per esigenze maggiori prevedere un worker/CLI amministrato senza renderlo obbligatorio per l'installazione base. Evitare che la sessione di un utente serializzi tutte le sue richieste tramite blocchi mantenuti durante operazioni lente; rispettare i servizi Joomla e le necessità di sicurezza.

**PERF-09 — Frontend.** Asset versionati e caricati per vista tramite Web Asset Manager; player inizializzato solo nell'aula o nell'anteprima attivata, non un iframe per ogni scheda del catalogo. Immagini responsive e lazy loading ove opportuno; paginazione obbligatoria per elenchi grandi. Nessun polling continuo di tutte le lezioni o ricaricamento completo dell'aula per salvare il progresso.

**PERF-10 — Ambiente e prova.** Dichiarare per ogni test versione Joomla/PHP/database, CPU, RAM, storage, limiti PHP-FPM/hosting, configurazione cache/sessione, cron/worker, provider e località della sonda. Raccogliere throughput, p50/p95/p99, errori, consumo CPU/RAM, connessioni/lock database, query lente e arretrato code. Eseguire prove con cache fredda e calda, controlli di integrità dopo il carico e ripetizione delle prove dopo cambiamenti rilevanti. Separare test backend con provider simulato da verifiche browser reali dei tre provider, senza caricare artificialmente servizi esterni senza autorizzazione. Il report finale deve dire quale carico è stato sostenuto e quale risorsa ha limitato la prova.

**PERF-11 — Operabilità.** Diagnostica di task, code, storage e configurazione; errori tecnici correlabili senza registrare risposte, credenziali o token in chiaro. L'hosting va dimensionato e verificato prima della messa in produzione: il supporto a centinaia di studenti non è una promessa valida su qualsiasi piano condiviso. L'architettura rimane compatibile con crescita successiva su risorse maggiori; un'eventuale distribuzione su più nodi richiede sessioni, cache, file e lock condivisi e un collaudo dedicato.

## 18. Flussi essenziali

1. **Pubblicazione:** autore prepara corso e attività → controlli di validità → revisione → pubblicazione versione → apertura iscrizioni.
2. **Studente:** scoperta → scheda → autenticazione → iscrizione/approvazione → fruizione → quiz → completamento → attestato.
3. **Tutor:** accesso ai corsi assegnati → verifica degli avanzamenti → intervento o correzione → feedback tracciato.
4. **Aggiornamento corso:** duplicazione/versionamento → analisi impatti → nuovi iscritti sulla nuova versione → eventuale migrazione esplicita degli iscritti precedenti.
5. **Eccezione:** risorsa non disponibile, salvataggio fallito o invio email fallito → stato comprensibile → tentativo di recupero → diagnosi amministrativa senza attribuire completamenti inesistenti.

## 19. Schermate previste

| Frontend | Backend |
|---|---|
| Catalogo e risultati di ricerca | Dashboard didattica |
| Scheda percorso | Gestione categorie e percorsi |
| Scheda corso | Elenco corsi e versioni |
| Iscrizione/richiesta accesso | Editor corso, moduli e lezioni |
| I miei corsi | Banca domande ed editor quiz |
| Aula e materiali | Iscrizioni e import |
| Quiz e risultato | Studenti e tentativi |
| I miei attestati | Modelli, emissioni e revoche |
| Verifica attestato se abilitata | Report, notifiche e registro operazioni |
| Area tutor nella fase 2 | Configurazione, permessi e diagnostica |

## 20. Rilasci e priorità

| Rilascio | Perimetro | Condizione di uscita |
|---|---|---|
| 1 — LMS essenziale completo | Catalogo, docenti, categorie e percorsi aggregativi, moduli, testo/video/audio/PDF, iscrizioni, ACL, area studente, tracking, quiz oggettivi, attestati base, report/CSV, notifiche, privacy e pacchetto Joomla | Uno studente conclude un corso reale con dati persistenti e permessi verificati |
| 2 — Gestione didattica avanzata | Gruppi, tutor frontend, compiti, banca domande evoluta, appunti, prerequisiti tra corsi, attestati di percorso, designer visuale, interazione moderata | Un tutor gestisce una classe senza accesso a dati estranei |
| 3 — Integrazioni | Live, SSO, API e interoperabilità selezionata | Ogni adapter supera scenari positivi, errori e riconciliazione; vendita e abbonamenti esclusi |

Le funzioni di sicurezza, versionamento dei risultati, protezione delle risorse e recupero dagli errori appartengono al primo rilascio. Se il perimetro iniziale deve ridursi, ridurre i tipi di contenuto o le personalizzazioni, preservando queste garanzie.

## 21. Criteri di accettazione

| ID | Scenario | Risultato atteso |
|---|---|---|
| AC-01 | Pubblicazione di corso con un modulo e tre lezioni valide | Scheda e aula disponibili ai soli destinatari previsti |
| AC-02 | Accesso diretto a file/quiz di altro corso o altro studente | Accesso negato senza esposizione di dati |
| AC-03 | Studente riprende su secondo dispositivo | Stato persistente e posizione disponibile secondo il player |
| AC-04 | Salto diretto al finale di video con soglia | Le porzioni saltate non contano come fruite |
| AC-05 | Provider video senza eventi affidabili | Pubblicazione richiede regola supportata, nessuna falsa percentuale |
| AC-06 | Quiz scade durante disconnessione | Valutate solo risposte già salvate, timer non azzerato |
| AC-07 | Invio quiz duplicato o due schede aperte | Un tentativo e un solo esito finale |
| AC-08 | Quiz fallito obbligatorio, tutte le altre attività completate | Corso non completato e nessun attestato |
| AC-09 | Completamento totale con richiesta ripetuta | Un attestato, scaricabile più volte |
| AC-10 | Aggiunta di lezione obbligatoria a corso già frequentato | Nuova versione senza retrocessione silenziosa dei vecchi iscritti |
| AC-11 | Accesso scaduto o sospeso | Fruizione bloccata, storico conservato secondo politica |
| AC-12 | Tutor tenta report di corso non assegnato | Accesso negato anche tramite export |
| AC-13 | Invio email indisponibile | Risultato persistente, notifica ritentabile, nessun duplicato |
| AC-14 | Aggiornamento del componente su dati esistenti | Iscrizioni, tentativi, progressi e PDF preservati |
| AC-15 | Export o richiesta privacy autorizzata | Dati del componente inclusi e perimetro verificato |
| AC-16 | Navigazione mobile e sola tastiera | Funzioni essenziali utilizzabili, focus e messaggi comprensibili |
| AC-17 | Corso o domanda senza requisiti validi | Pubblicazione impedita con errore risolvibile |
| AC-18 | Task pianificato fermo | Avviso amministrativo; scadenze applicate comunque all'accesso |
| AC-19 | Gestione categorie, tag e campi dal backend Joomla | Modifiche disponibili nel LMS, con accessi, lingua e permessi coerenti |
| AC-20 | Cambio lingua, associazione di traduzioni e override di stringhe | Navigazione multilingua coerente e testi personalizzati preservati all'aggiornamento |
| AC-21 | Template override di scheda corso e aula seguito da aggiornamento | Personalizzazioni preservate; autorizzazioni e calcolo dei risultati invariati |
| AC-22 | Blocco account e modifica gruppi/livelli dal gestore utenti Joomla | Effetti verificati su tutte le superfici LMS senza identità o ACL parallele |
| AC-23 | Revisione, pubblicazione, blocco modifica e ripristino contenuto | Integrazione con workflow/history/check-out senza riscrivere risultati pregressi |
| AC-24 | Modifica template email e consultazione registro azioni | Configurazione dagli strumenti nativi; eventi amministrativi rintracciabili |
| AC-25 | Estensione esterna rimuove il gruppo mentre l'utente è collegato | Nuove richieste protette e rinnovi token negati, nessun obbligo di nuovo login e storico preservato |
| AC-26 | Gruppo ripristinato senza nuova edizione formativa | Fruizione ripristinata e progressi precedenti disponibili |
| AC-27 | Lezioni YouTube, Vimeo e Cloudflare nello stesso corso | Player, ripresa ed eventi uniformati; nessun completamento sui soli eventi di fine |
| AC-28 | Video Cloudflare riservato richiesto da utente non autorizzato | Nessun token emesso e nessun link pubblico di riproduzione utilizzabile per quel video |
| AC-29 | Token Stream in scadenza o diritto Joomla revocato | Rinnovo solo dopo nuova autorizzazione; validità residua dichiarata per token già emessi |
| AC-30 | Video rimosso, embed bloccato o caricamento non consentito | Messaggio utile e nessun progresso attribuito per errore |
| AC-31 | URL/ID sostituito con un video differente | Nessun riuso automatico degli intervalli fruiti del video precedente |
| AC-32 | Personalizzazione di categorie, catalogo, corso e aula tramite ID/classi/variabili | Aspetto modificabile senza patch dei file distribuiti; struttura modificabile tramite override |
| AC-33 | Stessa pagina con componente, moduli e schede ripetute | Nessun ID HTML duplicato e nessuna interferenza fra istanze |
| AC-34 | Aggiornamento del componente con tema e override personalizzati | Contratto CSS e personalizzazioni preservati o migrazione esplicita documentata |
| AC-35 | Installazione su due siti/template indipendenti | Nessun marchio, dominio, percorso o stile obbligatorio legato a un sito specifico |
| AC-36 | Carico misto e picco secondo il profilo approvato | Obiettivi di latenza/errori raggiunti sull'ambiente dichiarato; integrità verificata dopo la prova |
| AC-37 | Molti player inviano eventi ad alta frequenza | Eventi aggregati, numero richieste entro il budget e nessun completamento duplicato |
| AC-38 | Due utenti con dati e livelli differenti navigano con cache attiva | Nessuna esposizione di contenuti, stato personale o token dell'altro utente |
| AC-39 | Molti studenti completano il corso e chiedono attestati contemporaneamente | Richieste persistenti e idempotenti, generazione gestita senza bloccare quiz e aula |

Per il collaudo preparare almeno due corsi, uno con test e uno senza; due versioni di un corso; ruoli distinti; file riservati; almeno un provider senza tracking; iscrizioni valide e scadute. Ambiente di prova separato dalla produzione. Le verifiche di carico, accessibilità e ripristino richiedono prove dedicate.

## 22. Configurazione funzionale confermata

| Decisione | Comportamento confermato | Confine |
|---|---|---|
| Uso del prodotto | Confermato: componente generico riutilizzabile su installazioni Joomla indipendenti | Multi-organizzazione nella stessa installazione richiede modello di isolamento dedicato |
| Joomla target | Joomla 6 | Joomla 5 amplia matrice di compatibilità e collaudo |
| Accesso | Confermato: corso associato a un livello di accesso nativo Joomla | Estensione esterna governa l'accesso tramite gruppi; LMS verifica il livello |
| Vendita e abbonamenti | Confermato: fuori perimetro, estensione dedicata | Nessun pagamento, rinnovo o adapter commerce nel LMS |
| Iscrizione didattica | Automatica al click Inizia | Iscrizione esplicita e approvazione sono modalità configurabili |
| Formazione | Asincrona | Live richiede calendario e modello presenze |
| Video | Provider previsti: YouTube, Vimeo, Cloudflare Stream; video locali mantenuti | Upload e gestione libreria nei portali provider come default iniziale |
| Completamento video | Soglia dove supportata; regola esplicita altrove | Tracciamento regolamentato richiede analisi ulteriore |
| Valutazione | Quiz oggettivi | Compiti e domande aperte richiedono workflow tutor |
| Attestato | Interno di completamento | Titoli riconosciuti richiedono requisiti specifici |
| Volumi | Confermato: centinaia di studenti simultanei; profilo confermato 300 continuativi/500 di picco | Ambiente da documentare e risultati da misurare |
| Personalizzazione | Confermato: ID CSS pubblici, classi/variabili e override documentati | Prefisso tecnico da congelare con il nome del prodotto; nessun template obbligatorio |
| Minori | Nessuna assunzione sull'utenza | Se presenti, analisi specifica di account, ruoli e trattamento |
| Contenuti già esistenti | Creazione ex novo | La migrazione da altre piattaforme richiede campione, mapping e riconciliazione |

Sono confermati: separazione fra scheda e contenuti, anteprime esplicite, soglia video configurabile con default 90%, test e navigazione configurabili per corso e disponibilità dello storico e degli attestati dopo la perdita di accesso. Il registro 23 documenta le scelte accolte. Restano parametri del sito i valori D-17 e D-18, non il disegno del prodotto.

## 23. Registro delle decisioni funzionali

**Stato aggiornato:** le proposte del riepilogo sono state accolte dall'utente. D-01–D-15 sono confermate per il perimetro iniziale; D-16 è confermata come obiettivo di collaudo, non capacità acquisita; D-17–D-18 sono confermate come meccanismi configurabili e richiedono parametri del singolo sito prima della messa in produzione. Le alternative nella tabella restano documentazione delle opzioni considerate e non sono selezionate. I comportamenti configurabili conservano i default approvati senza imporli a ogni corso o installazione.

| ID | Decisione | Soluzione confermata | Alternative non selezionate |
|---|---|---|---|
| D-01 | Come inizia la frequenza? | Pulsante Inizia, visibile agli utenti autorizzati; al click si crea automaticamente l'iscrizione didattica | Iscriviti prima di iniziare, oppure richiesta con approvazione; richiede stati e comunicazioni aggiuntivi |
| D-02 | Cosa vede chi non ha accesso? | Scheda pubblicabile separatamente dal livello delle lezioni; tre modalità: nascosto, scheda pubblica, scheda con anteprime | Catalogo interamente riservato; riduce i flussi di presentazione pubblica |
| D-03 | Come indirizzare all'accesso esterno? | URL Joomla configurabile per corso, con fallback globale; per anonimi prima login con ritorno alla scheda | Messaggio informativo senza collegamento; il LMS non deve interpretare prezzi o piani |
| D-04 | Lezioni libere o sequenziali? | Opzione per corso; default libero. In sequenziale il requisito precedente è esplicito, non derivato da un semplice numero d'ordine | Sequenza obbligatoria per tutti; più vincoli di navigazione e gestione eccezioni |
| D-05 | Quando è completato un contenuto? | Video al 90% degli intervalli unici; testo, audio e PDF con conferma esplicita; quiz al superamento. Soglia video configurabile per lezione | Solo conferma manuale per tutto oppure soglie aggiuntive; cambiano affidabilità e complessità |
| D-06 | Quali quiz nella prima versione? | Scelta singola, vero/falso e multipla; soglia predefinita 70%, tre tentativi, senza timer salvo configurazione, miglior risultato valido | Tentativi illimitati o test solo esercitativi; domande aperte richiedono correzione del docente |
| D-07 | Quando mostrare le risposte corrette? | Dopo superamento o esaurimento dei tentativi; prima mostrare soltanto punteggio e feedback generale | Feedback immediato, adatto all'esercitazione; oppure mai per test riservati |
| D-08 | Quali attestati? | Abilitazione per corso; rilascio automatico al completamento; modello PDF e codice univoco; verifica pubblica disattivata di default | Nessun attestato, rilascio manuale oppure pagina pubblica con QR: va scelto il flusso |
| D-09 | Cosa resta dopo la perdita di accesso? | Storico e attestati già emessi consultabili dall'account attivo; lezioni, risorse e nuovi tentativi bloccati; al ritorno si riprende | Bloccare anche i download di attestati oppure avviare nuova edizione; non cancellare implicitamente i dati |
| D-10 | Come aggiornare corsi già frequentati? | Modifiche sostanziali in nuova versione; vecchi iscritti mantengono la precedente; migrazione manuale con equivalenze | Tutti migrano obbligatoriamente: richiede avvisi, riconoscimento crediti e regole sugli attestati |
| D-11 | Come gestire traduzioni? | Corsi tradotti associati, con frequenza e risultati separati nella prima versione | Un'unica iscrizione condivisa fra lingue; richiede equivalenze di ogni lezione e quiz |
| D-12 | Chi modifica e pubblica? | Contenuti gestiti nel backend; autore prepara, responsabile didattico pubblica; docente vede solo risultati assegnati | Editor docente anche frontend; amplia schermate, permessi e collaudo |
| D-13 | Come si caricano i video? | Upload nei portali dei tre provider e collegamento nel LMS; niente upload remoto dal componente nella prima versione | Upload e libreria remota in Joomla; richiede trasferimenti, errori e sincronizzazione dedicati |
| D-14 | Quali strumenti didattici avanzati al lancio? | Compiti, gruppi/classi didattiche, domande/commenti, appunti e live differiti; nessun chat/forum implicito | Anticipare uno o più moduli richiede dettaglio funzionale prima dello sviluppo |
| D-15 | Contenuti preesistenti? | Nessun migratore da piattaforme esterne nel primo rilascio; duplicazione interna e import iscrizioni CSV | Migrazione di corsi, quiz e progressi: servono campioni e riconciliazione, non basta importare pagine |
| D-16 | Quanto carico deve essere collaudato? | Profilo 300 studenti continuativi e 500 di picco, con i budget PERF-01–03 | Altro profilo e volumi; cambiano ambiente e prova, non solo una soglia nel codice |
| D-17 | Come convivono LMS e gestore consensi? | Un adapter per il gestore effettivamente scelto; player esterno non caricato fino allo stato di consenso previsto | Nessun adapter dedicato: richiede comunque un contratto tecnico di integrazione e test sul sito |
| D-18 | Quale politica dati adottare? | Tempi distinti e configurabili per tentativi, progressi, attestati e audit; decisione del titolare in configurazione sito | Nessuna durata può essere dedotta dal componente o fissata come obbligo legale generico |

Non restano decisioni funzionali bloccanti nel registro per proseguire con il dettaglio della prima versione. Restano da completare: nome del componente e relativo prefisso; ambiente di esecuzione e strumenti di collaudo per D-16; scelta del gestore consensi per D-17; tempi effettivi e contatti del titolare per D-18. I parametri del sito non devono diventare valori fissi o riferimenti a un'organizzazione nel prodotto. Ulteriori requisiti relativi a minori o formazione regolamentata richiedono un'analisi dedicata se il caso d'uso li prevede: non sono assunti nel prodotto base.

Nome del prodotto, prefisso tecnico, hosting, versioni PHP/database e infrastruttura di consegna file sono decisioni tecniche distinte. Non è necessario chiedere al committente di scegliere singoli indici, librerie o nomi di classi; vanno motivati nella specifica tecnica.

## 24. Dizionario funzionale dei dati del rilascio 1

Legenda: **O** obbligatorio al salvataggio; **P** obbligatorio alla pubblicazione o attivazione pertinente; **F** facoltativo; **S** generato dal sistema e non modificabile liberamente. I limiti applicativi sono definiti dal presente dizionario; eventuali limiti tecnici più restrittivi richiedono segnalazione, mai troncamento silenzioso. Campi comuni: identificativo S; creatore/data creazione S; autore ultima modifica/data S; storico editoriale S; permessi secondo entità; lingua e stato per contenuti editoriali.

### 24.1 Corso e versione

| Campo | Tipo, obbligo e valore iniziale | Regole |
|---|---|---|
| Titolo | Testo, O, massimo 255 caratteri | Non vuoto dopo rimozione spazi; usato nelle viste e nel PDF |
| Alias | Testo, S modificabile da autorizzati | Generato da Joomla; unicità nel contesto del router; cambio di alias con gestione del vecchio collegamento |
| Categoria principale | Categoria Joomla, P | Una principale; tag per classificazioni trasversali. Categoria valida e non nel cestino |
| Descrizione breve | Testo, F, limite applicativo di 500 caratteri | Nessun contenuto riservato nei testi promozionali |
| Descrizione estesa | HTML filtrato, P | Destinatari, obiettivi e prerequisiti possono essere campi distinti e renderizzati separatamente |
| Immagine e testo alternativo | Media/URL consentito e testo, F | Richiedere alt descrittivo se immagine informativa; immagine decorativa marcata esplicitamente |
| Docenti | Selezione multipla, F | Profili assegnati senza attribuzione automatica di permessi amministrativi |
| Livello/difficoltà | Elenco, F | Principiante/intermedio/avanzato come etichette traducibili; nessun effetto sull'accesso |
| Durata stimata | Intero in minuti, F | Positivo; distinto dal tempo misurato e non calcolato come frequenza certificata |
| Lingua e associazioni | Servizi Joomla, P | Tutte oppure lingua installata/configurata; associazioni coerenti con il contesto |
| Livello di accesso ai contenuti | Livello Joomla, P | Un valore per corso, senza livelli proprietari o liste utenti parallele |
| Presentazione nel catalogo | Nascosto/scheda/scheda con anteprime, P | D-02 confermata; livello di visibilità della scheda separato dal diritto alle lezioni |
| Livello di accesso alla scheda | Livello Joomla, P se scheda separata | Controlla solo testo e metadati dichiarati promozionali; default Public |
| Destinazione richiesta accesso | Voce menu/URL consentito, F | Fallback globale; solo navigazione, niente apertura dei contenuti per effetto del ritorno |
| Percorsi e tag | Associazioni, F | Nessuna iscrizione o abilitazione implicita derivante dalla sola associazione |
| Modalità di avvio | Eredita/automatico/esplicito/approvazione | Il default finale dipende da D-01; l'approvazione non sostituisce il livello Joomla |
| Modalità di navigazione | Libera/sequenziale, P | Default libero; transizione documentata se cambia su corso attivo |
| Calendario | Date pubblicazione e fruizione, F | Date finali successive alle iniziali; inizio incluso, fine esclusa; fuso mostrato |
| Attestato e modello | Sì/no + modello, P se abilitato | Modello valido, dati emittente configurati e regola di completamento definita |
| Stato editoriale | Workflow Joomla, S/azioni autorizzate | Non rappresenta iscrizione o completamento |
| Numero versione didattica | S | Assegnato alla pubblicazione; distinta da revisione Content History |

La separazione fra accesso della scheda e accesso dei contenuti precisa CAT-01 e ISC-01 senza cambiare il vincolo di un livello Joomla per il corso. Una scheda pubblica non rende indicizzabili lezioni, URL media riservati o risultati. Ritiro della scheda non cancella l'iscrizione; disponibilità della fruizione deve seguire il calendario e stato specificati.

### 24.2 Moduli, lezioni e materiali

| Oggetto/campo | Obbligo e comportamento |
|---|---|
| Modulo: titolo, corso/versione, ordine | O; titolo massimo 255, appartenenza a una sola versione. Modulo vuoto ammesso in bozza ma non esposto nell'aula |
| Lezione: titolo, modulo, tipo | O; tipi supportati del rilascio 1; ordine assegnato con operazioni autorizzate |
| Lezione: obbligatoria | P; default sì; il cambio su versione frequentata è modifica sostanziale |
| Lezione: anteprima | F, default no; abilitabile solo se il corso consente anteprime; quiz valutativi e risorse riservate non diventano pubblici automaticamente |
| Lezione: regola completamento | P; scelta tra regole supportate dal tipo/provider; nessun fallback automatico dopo errore |
| Lezione: prerequisito | F; riferimento a lezioni obbligatorie della stessa versione nella prima release; niente cicli o prerequisito verso se stessa |
| Lezione: contenuto testuale | P per testo; HTML filtrato ed eventi content plugin nei contesti dichiarati |
| Lezione video: provider, identificativo | P per video; parser per formati documentati, nessun iframe arbitrario |
| Lezione video: soglia | P se completamento per fruizione; numero maggiore di zero e fino a 100; default 90 |
| Lezione audio: file/URL | P per audio; posizione riprendibile se player supportato; conferma esplicita |
| Documento e risorse | Titolo, riferimento, formato, dimensione, disponibilità e politica download; pubblici/riservati espliciti |
| Risorse scaricabili | Download per singola risorsa; default vietato per video provider riservati, consentito per allegati didattici autorizzati come default |
| Trascrizione e sottotitoli | F con avviso di accessibilità; formato e lingua documentati; caricamento compatibile con capacità del provider |
| Date di disponibilità | F; incompatibilità con requisiti di completamento segnalate prima della pubblicazione |

Non è possibile cancellare definitivamente una risorsa ancora utilizzata da una versione attiva o storica soggetta a conservazione; il sistema presenta i riferimenti e le alternative. Una risorsa esterna può scomparire senza controllo del LMS: messaggio allo studente e segnalazione al gestore, nessun completamento fittizio.

### 24.3 Quiz, domanda e tentativo

| Campo | Regola |
|---|---|
| Titolo, corso/versione e collocazione | O; il quiz è un'attività ordinata del corso |
| Tipo valutazione | Esercitazione oppure valutativo; definisce feedback e inclusione nei requisiti |
| Soglia | 0–100%; per test valutativo obbligatorio valore maggiore di zero; default 70% |
| Numero tentativi | Intero positivo oppure illimitati esplicito; default 3 |
| Durata | Minuti interi positivi oppure senza limite; default senza limite |
| Pausa fra tentativi | Minuti >= 0; default 0; il tentativo già iniziato segue la propria scadenza |
| Domande | Tutte oppure numero di estrazione > 0 e non superiore alla banca disponibile |
| Ordine | Fisso o casuale per domande e risposte; congelato all'avvio del tentativo |
| Punteggio domanda | Numero > 0; nessun totale massimo uguale a zero |
| Tipo e risposte | Scelta singola/vero-falso/multipla; domanda non vuota; almeno due opzioni e configurazione corretta coerente col tipo |
| Feedback | Regola selezionata da D-07; testi HTML filtrati e non inviati prematuramente al client |
| Tentativo: stato, date, versione e domande | S; snapshot alla partenza, senza ricalcolo retroattivo da banca modificata |
| Risposte e conferme salvataggio | S dal punto di vista editoriale; lo studente può modificarle finché il tentativo è aperto e autorizzato |
| Esito e revisione | S dal server; una rivalutazione autorizzata crea una revisione dell'esito con motivazione |

Un'esercitazione non influisce sul completamento a meno che l'autore la trasformi esplicitamente in attività obbligatoria con regola coerente. Il test non consente di calcolare punteggi o validare scadenze nel solo browser.

### 24.4 Iscrizione, docente, percorso e attestato

Iscrizione: utente Joomla esistente e corso/versione O; origine S (avvio, manuale, import); stato S/azioni autorizzate; inizio e scadenza formativa F; progressi, completamento e ultimo evento S; nessuna appartenenza commerciale memorizzata. Unicità per studente/corso/versione/edizione; un nuovo click non crea una seconda iscrizione.

Docente: nome pubblico O; biografia, immagine e link F; account Joomla collegato F; nessun indirizzo email pubblico automatico. Percorso: titolo O, descrizione/immagine/lingua/accesso della pagina e elenco ordinato dei corsi; nessuna autorizzazione implicita ai corsi e nessun attestato di percorso nel perimetro iniziale.

Attestato: codice, iscrizione, identità al momento dell'emissione, titolo/versione, emittente, data completamento, modello/versione e stato S; documento protetto. L'identità di default usa il nome del profilo Joomla. Nome anagrafico distinto o altri dati per il PDF richiedono campi e regole espliciti, non una raccolta automatica aggiuntiva. Modello: nome O, sfondo e campi posizionabili F, orientamento e formato P; anteprima prima dell'assegnazione.

## 25. Matrice delle autorizzazioni di riferimento

La tabella definisce profili di riferimento configurabili tramite ACL Joomla; non sono gruppi obbligatori creati in modo invasivo. **Propri** = record personali dello studente o contenuti di cui l'autore è proprietario; **assegnati** = corsi/docenti autorizzati esplicitamente. “No” indica assenza della capacità nel profilo di riferimento; negazioni effettive, ereditarietà e super user seguono Joomla. Un profilo amministrativo generico non equivale automaticamente al Super User.

| Operazione | Visitatore | Studente | Autore | Docente/tutor | Resp. didattico | Amm. LMS |
|---|---|---|---|---|---|---|
| Consultare schede | Pubbliche | Autorizzate | Autorizzate | Autorizzate | Autorizzate | Autorizzate |
| Frequentare attività | Solo anteprime | Proprie e autorizzate | Solo con diritto studente | Solo con diritto studente | Solo con diritto studente | Anteprima amministrativa separata |
| Vedere risultati personali | No | Propri | Se studente | Se studente | Se studente | Solo con permesso dati |
| Creare corsi/lezioni | No | No | Sì, in bozza | Non implicito | Sì | Con permesso editoriale |
| Modificare contenuti | No | No | Propri/assegnati | Se autorizzato | Sì nel perimetro | Con permesso editoriale |
| Pubblicare o ritirare | No | No | No | No | Sì nel perimetro | Con permesso editoriale |
| Gestire banca domande | No | No | Propria/assegnata | Se autorizzato | Sì nel perimetro | Con permesso dedicato |
| Leggere risultati altrui | No | No | No | Assegnati | Nel perimetro | Con permesso dati |
| Esportare risultati | No | Solo export personale privacy | No | Non incluso per default | Nel perimetro con permesso | Con permesso export |
| Gestire iscrizioni | No | Solo avvio/richiesta propria | No | No | Nel perimetro | Con permesso dedicato |
| Rettificare/reset progressi | No | No | No | No | Con permesso specifico | Con permesso specifico |
| Emettere/revocare attestati manualmente | No | No | No | No | Con permesso specifico | Con permesso specifico |
| Configurare provider/credenziali | No | No | No | No | No | Con permesso configurazione |
| Modificare utenti/gruppi Joomla | No | Solo profilo tramite core | No | No | No | Solo se autorizzato nel core |
| Gestire privacy | No | Richiesta propria tramite core | No | No | No | Ruolo privacy Joomla dedicato |

**ACL-DET-01.** Pubblicare, leggere risultati, esportare, rettificare ed eliminare sono capacità distinte. Essere insegnante esposto nella scheda non conferisce alcuna di queste capacità.

**ACL-DET-02.** L'accesso al backend LMS richiede autorizzazione amministrativa, ma l'accesso alle credenziali dei provider non deve essere necessario a un autore. Campi segreti mascherati e non restituiti nelle risposte di consultazione.

**ACL-DET-03.** L'anteprima amministrativa è contrassegnata e non genera progressi reali, tentativi valutativi, attestati o statistiche dello studente. Un amministratore che vuole frequentare realmente segue lo stesso flusso autorizzato degli altri studenti.

**ACL-DET-04.** La specifica tecnica mapperà ogni azione ai permessi Joomla e agli asset pertinenti. I test comprendono URL diretti, richieste modificate, query, export e risorse; non soltanto la presenza dei pulsanti.

## 26. Transizioni di stato e regole temporali

### 26.1 Editoriale del corso/versione

| Da → a | Attore/condizione | Effetto |
|---|---|---|
| Nuovo → bozza | Autore autorizzato | Nessuna visibilità pubblica né iscrizione reale |
| Bozza → in revisione | Autore, validazioni superate | Versione proposta; la pubblicazione vigente non cambia |
| In revisione → bozza | Revisore con osservazioni | Modifica nuovamente consentita secondo permessi |
| In revisione → pubblicato | Pubblicatore, validazioni e calendario | Nuova versione disponibile; iscritti precedenti non migrati automaticamente |
| Pubblicato → ritirato | Pubblicatore autorizzato | Nessuna nuova iscrizione; politica di fruizione esistenti esplicitata nell'azione |
| Ritirato → pubblicato | Pubblicatore e controlli | Ripristino della disponibilità secondo finestre e livelli |
| Non pubblicato → cestino | Permesso edit-state | Esclusione dalla navigazione; storico ancora presente |
| Cestino → ripristinato | Permesso dedicato e validazione relazioni | Ritorno in bozza/non pubblicato; mai pubblicazione involontaria |

Le etichette sono stati funzionali da mappare ai workflow nativi, non un secondo motore editoriale. “Ritirato” distingue la scelta di non accettare nuove frequenze dal blocco completo per indisponibilità; il form deve chiedere quale effetto applicare agli iscritti. Archiviare non implica cancellare o rendere pubblico il contenuto storico.

### 26.2 Iscrizione e percorso didattico individuale

| Evento | Precondizioni | Transizione/risultato |
|---|---|---|
| Inizia | Account attivo, accesso e corso validi, D-01 automatico | Nessuna iscrizione → attiva; stato apprendimento non iniziato |
| Richiesta | Modalità approvazione selezionata | Nessuna iscrizione → richiesta; niente fruizione protetta |
| Approvazione | Gestore autorizzato; diritto Joomla ancora richiesto | Richiesta → attiva; nessuna assegnazione di gruppo commerciale |
| Rifiuto | Gestore autorizzato | Richiesta → rifiutata, motivazione interna e messaggio appropriato |
| Prima attività valida | Iscrizione attiva e accesso corrente | Non iniziato → in corso |
| Tutti i requisiti soddisfatti | Validazione server | In corso → completato; data fissata e attestato eventualmente richiesto |
| Sospensione | Operatore autorizzato e motivazione | Attiva → sospesa; blocco didattico, storico conservato |
| Scadenza formativa | Finestra terminata | Attiva → scaduta; nessun cambiamento dei gruppi Joomla |
| Revoca di gruppo/livello | Estensione esterna/core | Accesso effettivo negato; nessun falso stato commerciale nel LMS |
| Riattivazione | Operatore o fine del blocco applicabile | Ripresa consentita solo se tutti i vincoli correnti sono soddisfatti |
| Annullamento | Gestore autorizzato e motivazione | Iscrizione → annullata; dati conservati secondo politica |
| Reset didattico | Permesso specifico, conferma e impatto | Nuova edizione/reset tracciato; attestato esistente non revocato implicitamente |

Lo stato **rifiutata** precisa ISC-02 per i siti che scelgono l'approvazione. L'accesso effettivo viene calcolato, non copiato dallo stato dell'iscrizione; una sospensione didattica può coesistere con un livello valido e una revoca di livello con un corso già completato.

### 26.3 Tentativo e attestato

| Oggetto/transizione | Regola |
|---|---|
| Tentativo assente → in corso | Una sola operazione atomica crea lo snapshot e, se previsto, la scadenza |
| In corso → consegnato | Conferma studente autorizzato; risposte assenti richiedono avviso, non impediscono necessariamente la consegna |
| In corso → scaduto | Chiusura server alla scadenza con ultime risposte salvate; non dipende dal browser aperto |
| Consegnato/scaduto → valutato | Calcolo server una sola volta per revisione; omissioni zero, esito coerente con la soglia |
| In corso → annullato | Solo amministratore autorizzato per problema documentato; la restituzione del tentativo disponibile è un'opzione esplicita |
| Valutato → rivalutato | Nuova revisione motivata, conservazione del prima/dopo e aggiornamento controllato del completamento |
| Nessun attestato → in preparazione | Idoneità verificata e registrazione emissione idempotente |
| In preparazione → disponibile | PDF prodotto e conservato correttamente; l'esito didattico non dipende dalla mail |
| In preparazione → errore tecnico | Stato visibile, retry senza nuova emissione; nessun PDF vuoto presentato come valido |
| Disponibile → revocato/sostituito | Operatore autorizzato, motivo, data e collegamento al sostitutivo |

**STA-01.** Conservare istanti in un formato temporale uniforme e mostrare il fuso nelle scadenze. L'orologio del server decide. Un cambio di fuso o ora legale non riapre un quiz scaduto.

**STA-02.** Le finestre già applicate al tentativo sono congelate; proroghe individuali devono essere autorizzate e tracciate. Se un quiz ha una data finale di disponibilità, non consentire un avvio che dia meno tempo del limite previsto; modalità alternativa di troncamento a scadenza solo esplicita e mostrata prima di iniziare.

**STA-03.** Revoca dell'accesso, indisponibilità del corso, scadenza formativa e timer quiz hanno cause e messaggi diversi. La revoca di autorizzazione prevale sull'invio di nuove risposte; una chiusura automatica sulle risposte già autorizzate segue ISC-07.

## 27. Specifica delle schermate e delle azioni

Ogni schermata frontend applica i selettori UI-01–07, layout responsive e testi traducibili. Gli stati caricamento, vuoto, errore, accesso negato e risultato parziale sono parte della schermata, non errori generici lasciati al browser.

### 27.1 Catalogo, categorie, scheda corso e percorsi

**SCR-01 — Catalogo/categoria.** Ricerca e filtri persistono nell'URL per condivisione e navigazione avanti/indietro. Cambiare filtro ritorna alla prima pagina. Default: 12 elementi, ordinamento editoriale, alternative titolo/data/durata; limiti applicati dal server. Scheda ripetuta: immagine, titolo, descrizione breve, durata, difficoltà, docenti e azione disponibile. Nessun dato promozionale riservato compare in un risultato pubblico. Zero risultati mostra filtri attivi e azione Azzera filtri; errore backend mostra Riprova senza perdere i filtri.

**SCR-02 — Categoria.** Titolo, descrizione, eventuali sottocategorie e corsi; categoria con figli nascosti non ne rivela numero o titoli. Eliminazione/spostamento di categoria gestiti in amministrazione con indicazione dei corsi coinvolti. La scheda categoria non concede l'accesso ai corsi elencati.

**SCR-03 — Scheda corso.** Header, immagine, descrizioni, obiettivi, destinatari, prerequisiti, docenti, durata, programma e regole. Nel programma pubblico si mostrano solo titoli e metadati che l'autore ha dichiarato pubblicabili; nessun URL di media protetto. Azione primaria calcolata come segue:

| Condizione | Azione/messaggio |
|---|---|
| Anonimo e corso protetto | Accedi; ritorno sicuro alla scheda dopo login |
| Utente autenticato senza livello | Richiedi accesso/Scopri come accedere, se destinazione configurata; altrimenti istruzione informativa |
| Autorizzato, corso non ancora disponibile | Disponibile dal…; nessuna iscrizione prematura salvo scelta esplicita |
| Autorizzato senza iscrizione, modalità automatica | Inizia; crea iscrizione una volta e apre la prima attività |
| Modalità esplicita/approvazione | Iscriviti/Richiedi iscrizione; dopo richiesta mostra lo stato |
| Iscritto in corso | Riprendi con destinazione autorizzata |
| Corso completato | Rivedi corso se ancora autorizzato; attestato se disponibile |
| Sospeso o finestra terminata | Motivo leggibile e contatto configurato, senza dettagli amministrativi riservati |

Una registrazione/login non costituisce iscrizione, acquisto o prova di accesso. Le destinazioni di ritorno ammesse evitano reindirizzamenti arbitrari.

**SCR-04 — Percorso.** Elenco ordinato di corsi; ogni elemento mostra il proprio accesso e stato personale. Percentuale aggregata solo se lo studente ha iscrizioni e requisiti definiti per i corsi necessari; evitare un 100% del percorso calcolato ignorando corsi bloccati. Nella prima release nessun rilascio di attestato di percorso né migrazione automatica fra percorsi.

### 27.2 Area personale e aula

**SCR-05 — I miei corsi.** Separare apprendimento (non iniziato/in corso/completato) da disponibilità (accessibile/bloccato/scaduto). Filtri e paginazione; azioni Riprendi, Rivedi, Attestato. Un corso non più autorizzato resta nello storico con indicazione Accesso non disponibile secondo D-09; non mostra risorse o risposte riservate. Vista vuota con collegamento al catalogo autorizzato.

**SCR-06 — Aula.** Indice moduli/lezioni, titolo, contenuto, materiali, avanzamento, precedente/successiva e indicatore salvataggio. Aprire una lezione non la completa; per testi/documenti/audio, pulsante Segna come completata. Per video richiesti il pulsante non sostituisce la soglia, salvo scelta editoriale esplicita di regola manuale. In sequenziale, clic su attività bloccata mostra prerequisito e link all'attività da completare. La ripresa preferisce l'ultima attività iniziata ancora disponibile, altrimenti la prima obbligatoria non completata; nessun reindirizzamento a contenuto revocato.

**SCR-07 — Errori di salvataggio.** Mostrare Salvataggio…, Salvato alle…, Salvataggio non riuscito. Conservare temporaneamente in memoria le modifiche non confermate, riprovare con limiti e segnalare il rischio di perdita uscendo. Non mostrare come persistenti dati che il server non ha accettato; non conservare risposte o credenziali in archivi pubblici del browser. Gli intervalli di salvataggio sono quelli PERF-05: l'ultimo segmento non confermato può dover essere rivisto dopo un arresto improvviso.

**SCR-08 — Contenuto esterno bloccato.** Placeholder locale con provider, motivo e azione disponibile (preferenze di consenso, riprova o assistenza). Nessun poster/SDK remoto prima delle condizioni configurate. Un link esterno alternativo non deve aggirare una scelta di consenso o un controllo di accesso; l'alternativa formativa va configurata esplicitamente.

### 27.3 Quiz e attestati

**SCR-09 — Prima del quiz.** Mostrare numero domande, punti/soglia, tentativi usati/disponibili, limite tempo o assenza di limite, possibilità di ripresa, momento del feedback e regola di scadenza. Il tentativo si consuma all'avvio confermato, non alla semplice visita. Se il tentativo è già aperto mostrare Riprendi, senza generarne un altro.

**SCR-10 — Quiz in corso.** Navigazione tra domande, stato risposta, timer quando previsto e ultimo salvataggio. Consentire revisione delle risposte prima della consegna. Conferma prima dell'invio definitivo con numero di omissioni; non fare affidamento solo su un timer client. La risposta di consegna ritorna un unico identificativo di esito. Un click ripetuto riporta allo stesso risultato.

**SCR-11 — Esito quiz.** Punteggio, soglia, esito, tempo e tentativi residui; feedback conforme a D-07. Nuovo tentativo soltanto quando consentito. Se un errore di rete impedisce di conoscere l'esito, verificare lo stato del tentativo invece di crearne uno nuovo. Tentativi annullati dall'operatore sono indicati nello storico nei limiti dei dati mostrabili allo studente.

**SCR-12 — Attestati.** Elenco personale con titolo, data, codice e stato; download solo dopo autorizzazione sul documento. Stato In preparazione/Errore tecnico distinguibile da Non idoneo. Attestato revocato non scaricabile come valido; eventuale sostitutivo collegato. La verifica pubblica, se abilitata, richiede il codice, ha limitazione richieste e mostra solo i campi pubblicabili scelti; nessuna ricerca per nome o elenco aperto.

### 27.4 Amministrazione

**SCR-13 — Editor corso.** Schede Dati generali, Programma, Accesso e catalogo, Regole didattiche, Attestato, Pubblicazione, Permessi e campi Joomla. Salva bozza, Anteprima, Invia in revisione, Pubblica solo con permessi. L'anteprima dei livelli/accessi non modifica l'appartenenza dell'utente. Prima di pubblicare mostrare errori bloccanti separati dagli avvisi.

**SCR-14 — Iscrizioni/report.** Filtri per corso/versione, utente, origine, apprendimento e stato accesso; colonne e azioni coerenti con i permessi. Operazioni massive mostrano selezionati, ambito e conteggio degli esclusi; nessuna promozione a un gruppo Joomla. Dettaglio studente/corso con attività obbligatorie, date, tentativi e rettifiche.

**SCR-15 — Operazioni sensibili.** Reset, migrazione, cancellazione, rivalutazione e revoca aprono un riepilogo con oggetti interessati, impatto, dati conservati, eventuali notifiche e motivazione richiesta. Conferma esplicita; lavori lunghi identificati e consultabili. I batch gestiscono errori per elemento e retry senza ripetere le azioni già riuscite.

**SCR-16 — Configurazione/diagnostica.** Parametri globali, valori ereditati, provider, template email, task, storage e controlli iniziali. Mostrare funzionalità non configurate con ragione e istruzioni; nessuna credenziale leggibile. Il controllo connessione non deve pubblicare video, alterare gruppi o inviare messaggi reali senza azione esplicita dell'operatore.

## 28. Regole trasversali di modifica, rimozione, import e notifica

**LIFE-01 — Dipendenze.** Un corso con iscrizioni, tentativi o attestati non è eliminabile definitivamente con un normale comando editoriale. Cestinamento/archiviazione e cancellazione dei dati sono operazioni diverse. La purga è soggetta a permessi, politica di conservazione e riepilogo dell'impatto. Nessuna eliminazione a cascata non dichiarata.

**LIFE-02 — Categoria e docenti.** Non consentire la cancellazione definitiva di una categoria con corsi senza scelta esplicita di ricollocazione, né lasciare riferimenti orfani. Rimuovere un docente dalla visualizzazione pubblica non cancella lezioni e risultati; le assegnazioni operative e il nome già presente in attestati storici sono gestiti separatamente.

**LIFE-03 — Profilo/utente.** Cambio nome/email non riscrive attestati emessi né riallaccia iscrizioni usando la sola email. Le iscrizioni fanno riferimento all'identificativo Joomla; account eliminati o anonimizzati seguono la procedura privacy configurata. Nuovo account con stessa email non eredita automaticamente risultati o attestati dell'account precedente.

**LIFE-04 — Import iscrizioni.** CSV UTF-8 con intestazioni documentate: identificativo utente Joomla, identificativo corso/versione, eventuale edizione, date formative e origine. Non importare password, gruppi commerciali, risultati o attestati tramite lo stesso flusso. In prima versione usare soltanto utenti già esistenti. Anteprima segnala utenti/corsi mancanti, duplicati, date invalide e righe fuori perimetro; le righe senza livello Joomla possono essere assegnate didatticamente solo con avviso esplicito, restando non fruibili.

**LIFE-05 — Esecuzione import.** Distinguere simulazione ed esecuzione, con numero righe accettate/scartate e conferma di eventuali email. Duplicati = nessuna nuova iscrizione; non aggiornare implicitamente un'iscrizione già attiva. File report scaricabile solo dall'operatore autorizzato; dimensioni/righe massime configurate, file temporanei rimossi secondo politica. Ripetere lo stesso import non deve moltiplicare assegnazioni o comunicazioni.

**LIFE-06 — Export report.** Il perimetro esportato coincide con filtro e autorizzazioni; mostrare colonne prima dell'esecuzione. Campi predefiniti: identificativi, nome utente quando autorizzato, corso/versione, stato iscrizione, stato apprendimento, percentuale, prime/ultime attività, data completamento e conteggio attestati. Risposte dei quiz, email e dati di profilo extra esclusi per default. Distinguere questo export operativo dall'export personale del processo privacy.

**LIFE-07 — Notifiche.** Una notifica appartiene a evento, destinatario, template e lingua. Gli eventi iniziali sono avvio/iscrizione, approvazione/rifiuto ove previsto, completamento e attestato disponibile. Scadenza e inattività sono opzionali. Non inviare una mail a ogni salvataggio, visualizzazione o risposta quiz. Lingua da profilo, con fallback configurato; collegamenti sempre protetti dalle normali autorizzazioni.

**LIFE-08 — Rettifiche.** Correggere un punteggio può rendere necessario un completamento o una revisione dell'idoneità. Prima dell'applicazione mostrare attestati interessati; non revocarli silenziosamente. Una rivalutazione in massa è un lavoro separato con report. Gli attestati interessati sono sottoposti alla regola deterministica EDGE-10, senza revoca automatica.

**LIFE-09 — Cancellazione privacy.** Il plugin espone dati e dipendenze al processo Joomla; non usa cestino del corso o reset come sostituti di una richiesta di rimozione. Eventuali impedimenti e conservazioni residue sono dichiarati all'operatore. Le azioni devono aggiornare coerentemente anche le verifiche degli attestati, gli export temporanei e le code non ancora inviate secondo la politica configurata.

## 29. Collaudi di dettaglio aggiuntivi

Gli AC-01–39 restano validi. La tabella seguente collega le nuove regole a scenari verificabili; è una base di copertura, non l'affermazione che ogni test sia già stato eseguito.

| ID | Riferimenti | Scenario e risultato atteso |
|---|---|---|
| AC-40 | D-01, ISC-01, SCR-03 | Due click Inizia per lo stesso utente/versione producono una sola iscrizione e una destinazione coerente |
| AC-41 | D-02, 24.1, SCR-01–03 | Scheda pubblica di corso riservato visibile senza URL media, risposte o lezioni protette nell'HTML e nell'indice ricerca |
| AC-42 | D-03, SCR-03 | Login e ritorno alla scheda non concedono accesso; un ritorno a dominio non ammesso è rifiutato |
| AC-43 | D-04, 24.2, SCR-06 | Configurazione con prerequisito ciclico o verso lezione non disponibile impedisce pubblicazione |
| AC-44 | D-05, PRO-04, SCR-06 | Ripetere lo stesso intervallo video non aumenta la copertura; salto finale non completa; soglia raggiunta completa una volta |
| AC-45 | D-06, 24.3, SCR-09–11 | Quiz con punteggi pesati, omissioni e risposta multipla valutato secondo regole; arrotondamento non trasforma un fallimento in superamento |
| AC-46 | D-07, QUI-07 | Risposte corrette non presenti nei payload prima dell'evento di feedback consentito |
| AC-47 | D-08–09, 26.3, SCR-12 | Attestato in errore generazione ritentabile senza doppia emissione; disponibilità dopo revoca accesso conforme alla scelta confermata |
| AC-48 | D-10–11, AUT-04–05 | Nuova versione e traduzione associata non modificano progressi o attestati della versione/lingua precedente |
| AC-49 | 25, ACL-DET-01–04 | Ogni azione della matrice è provata con almeno un ruolo ammesso e uno escluso, anche su endpoint diretto |
| AC-50 | 26.1, SCR-15 | Ritiro, cestino e ripristino rispettano la politica verso iscritti e non pubblicano involontariamente |
| AC-51 | 26.2–3, STA-01–03 | Timer, chiusura finestra e revoca livello producono motivi distinti; nessun nuovo invio dopo revoca |
| AC-52 | SCR-07, PERF-05 | Perdita rete mostra salvataggio fallito; retry registra una sola volta e non dichiara salvato ciò che non è persistito |
| AC-53 | SCR-13–16 | Utente senza permesso non può pubblicare, esportare o visualizzare configurazione segreta modificando le richieste |
| AC-54 | LIFE-01–03 | Rimozione categoria, corso o account non crea riferimenti orfani né trasferisce dati a nuovo utente con stessa email |
| AC-55 | LIFE-04–05 | Import ripetuto non duplica iscrizioni/mail; ogni riga scartata ha causa e nessun gruppo commerciale viene modificato |
| AC-56 | LIFE-06 | Export limita righe/colonne al perimetro e neutralizza contenuti interpretati come formule |
| AC-57 | LIFE-07 | Completamento e PDF producono solo le notifiche abilitate, nella lingua corretta, senza duplicati su retry |
| AC-58 | LIFE-08–09 | Rivalutazione e richiesta privacy presentano l'impatto sugli attestati e non producono revoche o cancellazioni silenziose |

## 30. Regole di configurazione e completezza della specifica

**CFG-01 — Precedenza.** Vincoli di sicurezza e permessi prevalgono sempre. Fra opzioni compatibili: override individuale autorizzato (ad esempio tempo aggiuntivo quiz), regola dell'attività, regola del corso/versione, default globale. L'ereditarietà deve essere visibile nel form con il valore effettivo; nessuna stringa vuota ambigua. I parametri del menu cambiano presentazione e filtri, non soglie, autorizzazioni o criteri didattici.

**CFG-02 — Congelamento.** Accessi Joomla sono correnti e non congelati nell'iscrizione. Requisiti del corso, contenuti e valutazione seguono la versione didattica; domande/ordine/limiti seguono lo snapshot del tentativo; dati e modello attestato seguono l'emissione. Cambiare un default globale influisce sui nuovi oggetti che lo ereditano, non riscrive risultati storici. Il form deve segnalare quando un cambiamento richiede nuova versione o una migrazione esplicita.

**CFG-03 — Classificazione degli errori.** Validazione dei campi, contenuto non disponibile, prerequisito non soddisfatto, accesso assente, sessione scaduta, salvataggio in conflitto, guasto provider ed errore interno hanno messaggi distinti. I dettagli tecnici sono nel registro con codice di correlazione; segreti e risposte corrette non appaiono nei messaggi. Nessun testo di errore deve rivelare l'esistenza di un record riservato non pubblicizzabile.

**CFG-04 — Documentazione da distribuire.** Manuale amministratore e autore; guida studente; matrice ACL; guida ID/classi/variabili/override; configurazione dei tre provider; prerequisiti hosting/task; politica di aggiornamento e procedura backup/ripristino. La documentazione segue la versione del componente ed è neutrale rispetto al sito di utilizzo.

**CFG-05 — Completezza del rilascio 1.** Il documento comprende perimetro, default, campi, autorizzazioni, stati, schermate, casi limite, contratto CSS, integrazioni Joomla e tracciabilità dei requisiti identificati. Gli allegati contengono mockup funzionali e catalogo dei test. I mockup rappresentano la struttura e i comportamenti, non un tema grafico definitivo. I parametri del sito in sezione 36 sono prerequisiti per produzione e collaudi, non nuove decisioni funzionali.

**CFG-06 — Limite della verifica.** La completezza funzionale non equivale a implementazione o certificazione tecnica. Restano da produrre lo schema fisico del database, i contratti degli endpoint e gli automatismi di build; restano da eseguire test installazione, sicurezza, accessibilità, provider e prestazioni sul componente. Il mapping Joomla è verificato documentalmente con le limitazioni esplicitate in sezione 32.

## 31. Regole consolidate per i casi limite

Le regole EDGE precisano i requisiti precedenti. In caso di ambiguità, prevale la regola specifica qui definita. I cambiamenti di configurazione non alterano retroattivamente tentativi chiusi o documenti emessi.

**EDGE-01 — Catena di accesso.** Verificare disponibilità di corso/versione, stato e accesso delle categorie applicabili, account, livello del corso, iscrizione didattica e prerequisiti. Il livello della scheda riguarda soltanto la presentazione promozionale. Le categorie limitano la visibilità secondo le proprie regole e non concedono mai accesso alle lezioni. L'implementazione deve verificare esplicitamente le categorie ascendenti: il viewing access level non va confuso con l'ereditarietà dei permessi di modifica. Una voce di menu nascosta non protegge una risorsa; un URL diretto applica gli stessi controlli del servizio. Un account Joomla bloccato non scarica attestati tramite vecchi collegamenti.

**EDGE-02 — Anteprima e consultazione.** Anteprime pubbliche solo per lezioni marcate e corso configurato; consentono la visualizzazione ma non iscrizioni anonime, tentativi valutativi o attestati. I materiali hanno una scelta autonoma di pubblicabilità: non diventano pubblici per associazione alla lezione. Guardare un'anteprima senza avviare il corso non riconosce automaticamente frequenza personale. Corso consultabile senza attività obbligatorie: progresso non applicabile e nessun attestato; la modalità va dichiarata, altrimenti la pubblicazione senza obblighi è rifiutata.

**EDGE-03 — Sequenza e rilascio delle lezioni.** In modalità sequenziale usare dipendenze dichiarate; alla creazione proporre la precedente attività obbligatoria come prerequisito. L'autore conferma la catena e il sistema impedisce cicli. In modalità libera sono comunque possibili prerequisiti espliciti, segnalati nella scheda. Una lezione futura obbligatoria resta nel denominatore del progresso e non permette completamenti anticipati. Scadenze di corso e lezioni non possono rendere irraggiungibile un requisito: validazione bloccante prima della pubblicazione.

**EDGE-04 — Percorso aggregato.** Ogni corso necessario pesa allo stesso modo. Percentuale = somma delle percentuali dei corsi richiesti / numero dei corsi richiesti; corso non iniziato o senza iscrizione vale zero. Corsi non autorizzati contano zero senza rivelare contenuti non pubblicabili; etichetta “percorso con corsi non disponibili” dove consentita. Un percorso senza corsi necessari non espone percentuale né completamento. Nessuna certificazione di percorso nel rilascio 1. L'associazione non cambia gruppi, livelli o iscrizioni.

**EDGE-05 — Revoca durante quiz.** Alla perdita del livello o al blocco account, interrompere salvataggi/consegne e mostrare accesso non disponibile. Le risposte già confermate restano. Se il tentativo ha timer, la scadenza originale chiude e valuta le risposte salvate; senza timer il tentativo rimane aperto ma non utilizzabile fino al ripristino dell'accesso o annullamento autorizzato. Il browser non accumula risposte da consegnare dopo la revoca. La sola scadenza didattica può consentire di concludere un tentativo già iniziato entro il limite originario, come annunciato prima dell'avvio. Si distinguono quindi scadenza didattica e perdita dell'autorizzazione Joomla.

**EDGE-06 — Tentativi e domande.** Domande a risposta multipla: esatta corrispondenza dell'insieme scelto; nessun punto parziale. Vero/falso: due opzioni e una corretta. Scelta singola: almeno due opzioni e una corretta. Multipla: almeno due opzioni, almeno una corretta e almeno una errata. Ogni domanda ha punti positivi. Un tentativo si consuma all'avvio; uno scaduto conta, uno annullato torna disponibile soltanto se l'operatore seleziona Restituisci tentativo. Un nuovo tentativo dopo superamento è consentito entro i limiti ma non annulla un superamento precedente. Dopo superamento o esaurimento tentativi si mostra il feedback previsto per il test; l'autore può impostare un criterio più restrittivo prima della pubblicazione.

**EDGE-07 — Quiz esercitativo.** L'esercitazione è facoltativa per default, non dà idoneità attestato e può consentire feedback immediato. Per richiedere un superamento nel corso l'autore deve usare un quiz valutativo obbligatorio con soglia maggiore di zero. È vietata una configurazione che dichiari obbligatoria un'esercitazione priva di una regola di completamento supportata. Non inserire automaticamente quiz in ogni corso: un corso può concludersi con le sole lezioni obbligatorie.

**EDGE-08 — Concorrenza e disconnessione.** Una sola attività di quiz attiva per la stessa iscrizione/quiz, anche da più dispositivi. Aggiornamenti portano una revisione; una revisione obsoleta riceve conflitto e non sovrascrive una risposta più recente. Per il progresso video unire gli intervalli già fruiti; la ripresa usa l'ultima posizione confermata della sessione valida, non il massimo tempo mai raggiunto, per permettere ripassi. Identificativi di evento unici e validazione del tempo plausibile impediscono conteggi duplicati o salti attribuiti come visione. Gli eventi client restano una stima, non una prova di identità o presenza.

**EDGE-09 — Corsi ritirati.** Due azioni distinte: Chiudi nuove iscrizioni mantiene l'aula agli iscritti aventi diritto; Sospendi erogazione blocca l'aula anche agli iscritti. Entrambe richiedono motivo e riepilogo. Per una sospensione di erogazione il quiz segue EDGE-05 come blocco di fruizione; uno già concluso resta storico. Il ritiro dal catalogo e il termine della pubblicazione della scheda non sostituiscono queste scelte. Ripristino dal cestino in bozza e controllo di tutte le dipendenze. Versioni storiche non vengono eliminate automaticamente quando si pubblica la successiva.

**EDGE-10 — Rivalutazione e attestati.** Una correzione può riconoscere un completamento prima assente. Se il nuovo risultato toglierebbe l'idoneità a un attestato emesso, creare anomalia da riesaminare, senza revoca automatica. Il documento rimane nello stato precedente fino alla decisione di un responsabile autorizzato; la dashboard amministrativa segnala la pendenza. La revoca esplicita comprende motivazione ed eventuale notifica di servizio; il PDF revocato non viene riproposto come valido. Sostituzione genera un nuovo codice con collegamento allo storico. Una nuova edizione/reset non revoca l'attestato dell'edizione precedente.

**EDGE-11 — Versioni e revisioni native.** Esiste una versione didattica assegnata a ogni iscrizione; una correzione ortografica è una revisione editoriale. Il ripristino Content History di una struttura con requisiti diversi propone una nuova versione in bozza, non altera gli iscritti in silenzio. Valori dei campi custom pertinenti alla fruizione sono acquisiti nello snapshot di pubblicazione; le definizioni dei campi rimangono nel core. Cambio di categoria o livello è applicato alla disponibilità corrente e non viene congelato come diritto storico. URL delle versioni storiche richiedono autorizzazione e non devono diventare indicizzabili autonomamente.

**EDGE-12 — Protezione provider.** Default Stream: token temporaneo 15 minuti, rinnovo anticipato con nuova verifica autorizzativa; durata configurabile e dichiarata in diagnostica. Non superare una scadenza didattica nota quando il modello di fruizione la rende vincolante. Token già emesso e buffer mantengono la validità residua; non promettere invalidazione immediata. Poster riservati non caricati da endpoint pubblici. Download disabilitato per default. Configurazione errata di dominio/privacy produce errore distinto, non ripiego a link pubblico o provider alternativo.

**EDGE-13 — Iscrizione e identità.** Per il primo avvio selezionare la versione pubblicata corrente. Duplicazioni concorrenti restituiscono la stessa iscrizione. Un'iscrizione manuale/import può preassegnare il corso a un utente senza livello, ma non abilitarlo alla fruizione: mostrare l'avviso. Il LMS non aggiunge gruppi commerciali. Account ricreato con stessa email non eredita lo storico; trasferimenti fra identità sono fuori perimetro e non avvengono tramite import.

**EDGE-14 — Rimozione delle configurazioni.** Livello Joomla eliminato o categoria mancante: nessun fallback a Public; corso non fruibile con avviso amministrativo. Provider disabilitato non cancella i video né consente completamenti manuali impliciti. Modello attestato usato non eliminabile senza conservare gli snapshot già emessi. Un aggiornamento del pacchetto conserva template email personalizzati, permessi, file di override e identificatori dei documenti.

### 31.1 Validazioni dei campi e dei parametri

**DAT-01 — Dati editoriali.** Verificare titolo vuoto, spazi, limite 255; descrizione breve 500; durate positive; alias duplicato; lingua/categoria/accesso inesistenti; date invertite. I limiti non troncano silenziosamente. Le descrizioni sono sanificate secondo configurazione Joomla; i campi custom conservano i vincoli definiti nel core.

**DAT-02 — Struttura.** Validare appartenenza univoca modulo/versione e lezione/modulo, ordine senza perdita di elementi, dipendenze disponibili e assenza di cicli. La duplicazione crea contenuti in bozza e nuovi ID, non iscrizioni, tentativi, attestati o diritti.

**DAT-03 — Quiz.** Testare limiti soglia, punti, numero opzioni, risposte corrette, campione estratto, durata e tentativi. La somma dei punti del campione definisce il massimo del tentativo, non la banca intera. Arrotondamento visuale a due decimali; decisione sull'esito a precisione non arrotondata.

**DAT-04 — Risorse.** Validare provider/ID e host consentiti, MIME, estensione, dimensione, permessi upload e download. Default allegati fino a 20 MiB e CSV fino a 10 MiB/10.000 righe, modificabili entro i limiti dell'hosting; mostrare il limite effettivo. Nessun caricamento di file eseguibili. MP4 locali seguono limiti separati e preflight del servizio di consegna; non transitano nelle code email.

**DAT-05 — Attestati.** Modello in formato A4 orizzontale predefinito, varianti configurabili; nomi e titoli lunghi con regole di adattamento e anteprima. Un campo fuori pagina blocca l'attivazione del modello. Data e identità riferite all'emissione; nessun campo extra obbligatorio richiesto allo studente senza configurazione del sito.

**DAT-06 — Import/export.** CSV UTF-8, virgola o punto e virgola dichiarati nel form; intestazioni canoniche `user_id,course_id,course_version_id,edition,starts_at,ends_at`. `user_id` e `course_id` obbligatori; versione vuota = corrente pubblicata; date ISO 8601 con fuso, celle vuote = nessun limite formativo. Edition vuota = edizione corrente predefinita. Righe duplicate non aggiornano dati esistenti; errori per riga. Export con stesso formato temporale, delimitatore selezionabile e protezione formule su tutte le celle testuali.

**DAT-07 — Email.** Notifiche di avvio, completamento e attestato abilitate per default; approvazione/rifiuto solo per i corsi che usano quella modalità. Promemoria scadenza opzionale, inattività e comunicazioni di gruppo differite. Template espongono solo variabili documentate (nome, corso, link protetto, scadenza pertinente); nessuna risposta corretta o segreto. Completamento e attestato sono due eventi separati. Coda con esito e retry, distinta dalle preferenze marketing.

**DAT-08 — Configurazione sicura.** Alla prima installazione componenti/provider non configurati rimangono disabilitati, senza credenziali dimostrative né contenuti pubblicati automaticamente. Impostare lingua fallback, contatto supporto, storage riservato e task; i corsi senza test o attestato non richiedono parametri di funzioni disattivate.

## 32. Mapping delle integrazioni Joomla e verifica delle fonti

### 32.1 Metodo e limite

Verifica effettuata il 30 ottobre 2026 tramite manuale ufficiale Joomla 6.1, indice API Joomla 6.0 e campioni del codice ufficiale CMS al tag **6.0.0**. Il tag è un riferimento riproducibile, non una raccomandazione a installare una patch vecchia. Lo sviluppo userà una patch supportata della serie 6, da fissare nell'ambiente di prova. Il manuale workflow consultato è una pagina segnaposto: la disponibilità dei servizi è verificata nel codice di `ContentComponent.php`, non dedotta dal titolo del manuale. Le API online hanno alcuni collegamenti non risolti: si riportano i riferimenti effettivamente letti e il codice come riscontro.

**VD** = percorso documentato o servizio riscontrato nei sorgenti ufficiali. **VI** = applicabilità al nostro oggetto da verificare con un test d'integrazione dopo l'implementazione. Tutte le righe hanno VI pendente: nessun componente LMS è stato installato o eseguito in questa fase.

| Requisito | Mapping da realizzare | Riscontro VD | Prova VI prevista |
|---|---|---|---|
| CORE-01 | Contesti com_categories, category field, catid, CategoryServiceInterface e servizio categoria | Manuale categorie 6.1 e ContentComponent | AC-19, AC-60 |
| CORE-02 | Utente Joomla, autenticazione/sessione, blocco e profilo core | Servizi utenti/ACL Joomla già documentati | AC-22, AC-25 |
| CORE-03 | Livelli correnti e gruppi core | Guida Viewing Access Levels | AC-22, AC-25, AC-38 |
| CORE-04 | access.xml, asset e controlli per azione e record | Manuale categorie e ACL | AC-49, AC-61 |
| CORE-05 | View tmpl e sottolayout JLayout sovrascrivibili | Manuale Layouts | AC-21, AC-34 |
| CORE-06 | File lingua, Text e Language Overrides | Manuale Multilingual | AC-20, AC-62 |
| CORE-07 | AssociationServiceInterface e helper per corsi/categorie | ContentComponent e AssociationsHelper | AC-20, AC-48 |
| CORE-08 | Plugin privacy con export/removal/capabilities dei domini LMS | PrivacyPlugin e plugin core privacy/content | AC-15, AC-58, AC-63 |
| CORE-09 | TagServiceInterface/Trait, tipi e mapping contenuti | ContentComponent | AC-19, AC-64 |
| CORE-10 | Servizi Fields e contesti corsi/lezioni/categorie | ContentComponent, indice API Fields | AC-19, AC-65 |
| CORE-11 | Form/editor core, content prepare per contesto | Form e plugin content core | AC-66 |
| CORE-12 | Campo media e selezione con ACL; consegna protetta distinta | Integrazione editoriale core | AC-02, AC-67 |
| CORE-13 | RouterServiceInterface, RouterView, menu e breadcrumbs | Router ufficiale com_content | AC-68 |
| CORE-14 | Plugin Finder basato sull'Indexer Adapter | Plugin finder/content | AC-41, AC-69 |
| CORE-15 | WorkflowServiceInterface/Trait e modello workflow del corso | ContentComponent ufficiale | AC-23, AC-70 |
| CORE-16 | Modello/tabella versionabili, storico core e checkout | Indice API Versionable e modello ArticleModel | AC-23, AC-71 |
| CORE-17 | Plugin actionlog e log didattico separato | Base ActionLogPlugin | AC-24, AC-72 |
| CORE-18 | MailTemplate registrati, Mailer configurato e lingua | API Joomla 6 MailTemplate | AC-13, AC-24, AC-57 |
| CORE-19 | TaskPluginTrait, routine e Scheduler | Plugin task/requests | AC-18, AC-73 |
| CORE-20 | Form XML, toolbar, list model e azioni batch core | Modello ArticleModel e convenzioni backend | AC-53, AC-74 |
| CORE-21 | Web Asset Manager, sessioni e cache separate per contesto | Manuali WAM e cache | AC-38, AC-75 |
| CORE-22 | Manifest, script installazione, migrazioni, Update System | Manuale installazione estensioni | AC-14, AC-76 |
| CORE-23 | Dispatcher/eventi Joomla, contratti LMS minimi | Servizi e SubscriberInterface nei plugin core | AC-77 |

Il nome esatto dell'interfaccia Fields impiegata verrà scelto sulla patch target: il codice 6.0.0 usa FieldsFormServiceInterface e FieldsServiceTrait, mentre l'indice API elenca anche FieldsServiceInterface. Non prescrivere un simbolo deprecato solo per coincidere con un esempio. Lo stesso criterio si applica alle classi di eventi e alla registrazione dei servizi.

L'uso del core richiede di rendere realmente raggiungibili questi gestori dai menu del componente. Un link a com_fields o com_categories privo di contesti e supporto nel modello non soddisfa il requisito. Tag, associazioni e versionamento richiedono registrazione/coerenza dei tipi di contenuto; il test include anche ripristino e rimozione, non il solo salvataggio.

### 32.2 Riferimenti riproducibili

Il file allegato `Evidenze_Joomla_6.json` riporta percorso ufficiale, URL e SHA del contenuto consultato per nove campioni. Percorsi principali: `administrator/components/com_content/src/Extension/ContentComponent.php`, `src/Model/ArticleModel.php`, `src/Table/ArticleTable.php`, helper associazioni, router frontend e plugin core privacy/content, finder/content, task/requests e base ActionLogPlugin. Sono evidenze documentali, non codice del futuro componente. Non sono incluse copie dell'intero sorgente Joomla nel pacchetto.

Fonti aggiuntive: [categorie 6.1](https://manual.joomla.org/docs/general-concepts/categories/implementing-categories-in-components/), [multilingua](https://manual.joomla.org/docs/general-concepts/multilingual/), [layouts](https://manual.joomla.org/docs/general-concepts/layouts/), [API MailTemplate](https://api.joomla.org/cms-6/classes/Joomla-CMS-Mail-MailTemplate.html), [indice API 6](https://api.joomla.org/cms-6/packages/JoomlaCMS.html), [codice ContentComponent al tag consultato](https://github.com/joomla/joomla-cms/blob/6.0.0/administrator/components/com_content/src/Extension/ContentComponent.php).

## 33. Contratto delle viste e mockup allegati

I mockup funzionali `Mockup_LMS_Joomla_1.0.pdf` sono parte della specifica e usano un linguaggio grafico neutro. Non sono schermate di software già funzionante. Il layout non impone font, colori o un template; struttura, stati, contenuti e azioni sono gli aspetti normativi. Tutte le azioni hanno descrizione in SCR-01–16; il mockup non introduce nuove funzioni.

| Mockup | Vista e schermata | Contratto visuale |
|---|---|---|
| M-01 | Elenco categorie, SCR-01–02 | #jlms-categories; intestazione; schede categoria e conteggi autorizzati |
| M-02 | Categoria/catalogo, SCR-01–02 | #jlms-category; header, filtri, risultati, paginazione; classi delle schede |
| M-03 | Scheda corso, SCR-03 | #jlms-course; header, cover, summary, metadata, teachers, syllabus e actions |
| M-04 | Aula, SCR-06–08 | #jlms-classroom; contenuto/player, outline, materiali, navigazione e salvataggio |
| M-05 | Quiz, SCR-09–11 | #jlms-quiz; istruzioni, tentativi, timer opzionale, domande e conferma |
| M-06 | Area personale/attestati, SCR-05/12 | #jlms-dashboard, #jlms-my-courses, #jlms-certificates; separazione progresso/accesso |
| M-07 | Editor amministrativo, SCR-13–16 | Tab funzionali, errori bloccanti, controllo pubblicazione e pannelli core |
| M-08 | Adattamento smartphone | Corso, aula e quiz con ordine di lettura, indice richiudibile e azioni accessibili |

Il catalogo standalone usa #jlms-courses. Nella vista categoria, usare #jlms-category-courses come area corsi e suffissi di contesto per filtri/risultati; non montare una seconda radice #jlms dentro la prima. Gli ID delle regioni non sono riutilizzati nelle istanze di moduli Joomla. I mockup sono tavole separate, quindi la ripetizione di una didascalia ID tra tavole non rappresenta un ID HTML duplicato.

Esempio CSS di personalizzazione, da caricare nel template dopo il foglio base:

```css
#jlms {
  --jlms-color-primary: #175e67;
  --jlms-color-text: #192e38;
  --jlms-color-surface: #ffffff;
  --jlms-font-family: system-ui, sans-serif;
  --jlms-radius: 0.75rem;
  --jlms-content-max-width: 76rem;
  --jlms-grid-gap: 1.25rem;
}
#jlms-course-header { padding-block: 2rem; }
#jlms-category-courses .jlms-course-card { border-radius: 1rem; }
```

La guida dei selettori finale sarà generata dalla stessa fonte dei layout: aggiungere una regione senza documentarne l'ID e il comportamento responsive non soddisfa UI-07. Nel rilascio 1 le schermate non rappresentate nelle otto tavole (docenti, percorsi, form di iscrizione alternativo, verifica attestato) seguono i pattern documentati nelle sezioni 17.3 e 27 e sono soggette agli stessi test.

## 34. Criteri di collaudo integrativi e copertura

| ID | Scenario | Risultato atteso |
|---|---|---|
| AC-59 | Perdita livello durante tentativo con e senza timer | Applicazione di EDGE-05; nessun nuovo salvataggio autorizzato dalla sola iscrizione |
| AC-60 | Categoria/antenato con accesso diverso e scheda promozionale pubblica | Controlli di catena coerenti, nessuna esposizione via URL o ricerca |
| AC-61 | ACL ereditata con negazione e permesso edit-own | Operazione negata anche se il pulsante è riabilitato nel browser |
| AC-62 | Pacchetti it-IT/en-GB e override frontend/backend/email | Tutti i testi selezionati tradotti; override conservato all'update |
| AC-63 | Export e rimozione privacy con attestati, audit e file temporanei | Domini LMS inclusi, impedimenti motivati, coerenza della verifica e delle code |
| AC-64 | Tag creato, rinominato ed eliminato su corso pubblicato | Gestori, filtri e resa coerenti, nessun riferimento orfano |
| AC-65 | Campi custom per corso/lezione, ACL, lingua e revisione | Valori visibili solo nel contesto autorizzato; snapshot non riscrive attestati |
| AC-66 | Cambio editor, content plugin consentito e HTML non ammesso | Rendering corretto del contenuto ammesso e rimozione del contenuto vietato |
| AC-67 | Selezione media con utente privo di upload e download privato | Nessuna escalation; file riservato non esposto tramite URL pubblico |
| AC-68 | Menu multilingua, SEF on/off, alias modificato e breadcrumbs | Destinazione stabile o redirect valido; controllo accesso invariato |
| AC-69 | Ritiro/modifica lingua e accesso dopo indicizzazione Finder | Nessun contenuto o snippet riservato residuo; indice aggiornato |
| AC-70 | Workflow nativo personalizzato e transizione non autorizzata | Stato e pubblicazione coerenti; nessun secondo motore di workflow |
| AC-71 | Checkout concorrente e ripristino revisione con requisiti diversi | Conflitto gestito; nuova versione proposta senza alterare risultati acquisiti |
| AC-72 | Rettifica/revoca loggata e consultazione senza permesso log | Operatore e motivo rintracciabili, log non esposto a ruoli esclusi |
| AC-73 | Task sovrapposti, arresto e ripresa del batch | Lavoro riprendibile, deduplicato e visibile in diagnostica |
| AC-74 | Batch misto con alcuni record fuori perimetro | Risultato per record, nessuna modifica a record non autorizzati |
| AC-75 | Asset per vista e cache con lingua/accessi diversi | Nessun player nel catalogo, nessuna contaminazione fra utenti e lingue |
| AC-76 | Installazione pulita, update, rollback da backup e disinstallazione | Dati preservati secondo politica e nessuna modifica core/override |
| AC-77 | Plugin LMS ascolta evento documentato e tenta di forzare accesso | Evento corretto; autorizzazione server non aggirabile |
| AC-78 | Scheda pubblica/anteprima senza avvio, poi Inizia autenticato | Nessuna frequenza anonima riconosciuta; una sola iscrizione personale |
| AC-79 | Lezione futura obbligatoria e percorso con corso non iniziato | Denominatori invariati e nessun 100% prematuro |
| AC-80 | Valori limite e invalidi dei dizionari 24 e DAT-01–08 | Errori per campo, nessun troncamento, pubblicazione invalida bloccata |
| AC-81 | Risposte simultanee di due schede con revisione obsoleta | Conflitto esplicito, nessuna sovrascrittura silenziosa |
| AC-82 | Chiusura iscrizioni distinta da sospensione erogazione | Iscritti aventi diritto continuano solo nel primo caso |
| AC-83 | Rivalutazione toglie idoneità di attestato già emesso | Anomalia in revisione, nessuna revoca silenziosa; decisione tracciata |
| AC-84 | Eliminazione livello, provider o modello ancora usato | Nessun fallback a Public, nessun dato storico perso |
| AC-85 | Ricerca, filtri, ordinamento, paginazione e stato vuoto | Risultati autorizzati, filtri in URL e ritorno coerente alla prima pagina |
| AC-86 | Pubblicazione con struttura/date/risorse invalide e prerequisiti incompatibili | Pubblicazione impedita con elenco errori correggibili; bozza conservata |
| AC-87 | Modello attestato con nome/titolo lungo e campi fuori pagina | Anteprima leggibile; modello invalido non attivabile; attestato interno senza crediti impliciti |
| AC-88 | Report con coorti note, versioni e attività facoltative | Conteggi riproducibili secondo REP-04, nessun denominatore alterato dai filtri personali |
| AC-89 | Testo/audio/PDF completati e allegato opzionale non scaricato | Conferma esplicita registrata; allegato opzionale non blocca il completamento |
| AC-90 | Tastiera, focus, zoom, screen reader e timer con tempo aggiuntivo | Azioni e messaggi accessibili, tempo individuale autorizzato applicato e tracciato |
| AC-91 | Ripresa del player su Safari iOS, Chrome Android e browser desktop supportati | Stato coerente entro l'ultimo salvataggio confermato e nessuna fruizione offline promessa |
| AC-92 | Templates email, fallback lingua, retry e variabili non fidate | Messaggi corretti, escaping dei valori, nessun invio a ogni evento player |
| AC-93 | Guide e contratto CSS confrontati con il pacchetto e i mockup | Selettori, permessi, task e procedure documentati e coerenti con la build |
| AC-94 | Report delle date/attività consultato come prova di frequenza | Durata stimata e tempi tecnici distinti; nessuna certificazione implicita delle ore |
| AC-95 | Upload eseguibile, HTML malevolo, URL provider arbitrario e CSRF | Input rifiutato/sanificato e nessuna esecuzione, fetch arbitrario o modifica non autorizzata |
| AC-96 | Duplicazione corso, modulo e lezione con iscrizioni esistenti | Copia in bozza con nuovi ID, nessuna copia di iscrizioni, attestati, tentativi o gruppi |

La matrice CSV allegata include una riga per ogni requisito identificato e una o più prove, con stato **da eseguire**. La copertura è documentale: non equivale a test passati. Il catalogo CSV dei test riporta scenario, risultato atteso e categoria della prova. Per la matrice ruolo×azione e i dizionari campi, i test sono parametrizzati su tutti i ruoli/campi indicati, inclusi valori limite e richieste dirette.

## 35. Criteri di uscita e responsabilità

**REL-01 — Accettazione funzionale.** Per ogni requisito del rilascio 1, almeno una prova prevista nella matrice, eseguita con risultato e riferimento al build. Nessun caso obbligatorio lasciato senza esito; anomalie registrate con gravità, riproduzione e decisione. Le funzioni differite non possono comparire come completate nel report. Le opzioni disattivate hanno comportamento testato.

**REL-02 — Accettazione non funzionale.** Prove accessibilità automatiche e manuali, controllo ID unici, test sicurezza degli oggetti e dei ruoli, carico misto e picco, aggiornamento con dati esistenti e ripristino. Il report prestazionale contiene l'ambiente e i risultati reali, non solo il numero di utenti virtuali impostato. Testare i provider reali su browser supportati con contenuti autorizzati.

**REL-03 — Documentazione del rilascio.** Consegnare ZIP installabile, checksum, changelog, guida amministrazione/autore/studente, guida personalizzazione, mappa permessi e istruzioni task/storage/provider. La documentazione è parte della verifica, non un'attività facoltativa successiva.

**REL-04 — Separazione delle responsabilità.** Prodotto: identità Joomla, livelli, fruizione, progressi, valutazione e attestati. Estensione abbonamenti: assegnazione/revoca gruppi e flussi commerciali. Gestore sito: configurazione ambientale, informative/consensi e conservazione. Provider video: disponibilità e distribuzione secondo contratto. Nessun errore esterno deve generare dati didattici falsi o aprire contenuti protetti.

## 36. Parametri da definire per ogni installazione

Questa tabella non riapre le scelte funzionali approvate. Distingue parametri necessari al sito da attività a carico dello sviluppo.

| Parametro | Responsabile | Quando è necessario | Comportamento fino alla configurazione |
|---|---|---|---|
| Nome definitivo, namespace e prefisso | Progetto | Prima del primo pacchetto pubblicabile | jlms è prefisso di lavoro neutro; non è una verifica di disponibilità del nome |
| Patch Joomla/PHP/database supportati | Sviluppo + gestore | Prima del collaudo | Versioni riportate nella matrice tecnica, nessuna promessa su tutte le combinazioni |
| Provider e credenziali Stream | Gestore | Prima dei corsi che li usano | Provider non configurato non pubblicabile per video riservati |
| Storage protetto e consegna file | Gestore + sviluppo | Prima degli upload riservati | File riservati non caricati in cartelle pubbliche come ripiego |
| Gestore consensi e adapter | Gestore + sviluppo | Prima della messa in produzione con provider esterni | Caricamento esterno bloccato fino al collegamento corretto |
| Conservazione dei domini dati | Titolare/gestore | Prima della messa in produzione | Nessuna cancellazione automatica irreversibile fino alla politica configurata |
| Emittente e modello attestato | Gestore | Prima di abilitare attestati | Corsi senza attestato utilizzabili; emissione non configurata impedita |
| Mailer, contatto supporto, task | Gestore | Prima della produzione | Diagnostica e avvisi; risultati didattici salvati anche senza invio email |
| CPU/RAM, processi, cache e carico | Gestore + sviluppo | Prima del test prestazionale | Nessuna capacità di 300/500 dichiarata come misurata |

Il prodotto rimane neutro: nessun dominio, marchio, emittente, periodo di conservazione o estensione commerciale specifica è incorporato nel codice o nelle viste.


## 37. Plugin video dedicati al componente

**PVD-01 — Gruppo e pacchetti.** Tutti i quattro plugin video fanno parte obbligatoria dello ZIP base distribuito, con documentazione e collaudo propri; installazione distinta da attivazione/configurazione. Usare il gruppo plugin Joomla dedicato `jlmsvideo`, con plugin YouTube, Vimeo e Cloudflare Stream e adapter/plugin HTML5 per i video locali. Manifest, parametri, lingue, asset e aggiornamenti indipendenti. Il prefisso rimane di lavoro fino al nome definitivo del prodotto. Installare un nuovo provider compatibile non deve richiedere patch del componente.

**PVD-02 — Registro e contratto.** Il componente importa il gruppo nei contesti necessari ed emette il proprio evento `onJlmsRegisterVideoProviders`; i plugin registrano factory in un registry. L'evento è del progetto, non un evento core preesistente. Provider key univoca e contratto con versione; errori su collisione o incompatibilita. Operazioni comuni: descrizione, capacita, normalizzazione/validazione sorgente, metadati, descrittore playback e asset frontend. Contratto dettagliato nell'allegato Servizi_endpoint_LMS_Joomla_1.0.md.

**PVD-03 — Player uniforme.** Eventi player normalizzati e distruzione ordinata al cambio lezione/consenso. Il nucleo mantiene le regole di copertura, salvataggio e completamento. L'adapter dichiara le capacita effettive della sorgente; una regola non supportata non puo essere pubblicata. Nessuna chiamata remota a ogni evento di progresso.

**PVD-04 — Autorizzazione e segreti.** AccessPolicyService verifica l'accesso prima del plugin. Il provider non assegna livelli, voti o attestati. Parametri API server separati dai token playback temporanei restituibili al browser; cache personale/no-store e nessuna chiave privata nei payload.

**PVD-05 — Errori e compatibilita.** Disattivazione/rimozione del provider lascia i riferimenti alle lezioni e produce un errore controllato solo sui contenuti interessati; nessun fallback pubblico. Versionamento del contratto e guida per sviluppatori con plugin di esempio. I plugin sono codice Joomla attendibile installato, non processi isolati in sandbox.

**PVD-06 — Estensione futura.** L'interfaccia permette altri provider senza modificare il modello di corso e progresso. Upload remoto, sincronizzazione libreria, live e webhook provider non sono impliciti e rimangono evoluzioni. API e gruppi core Joomla si usano secondo la versione target, senza duplicare il gestore plugin.

## 38. Materiali extra di corso e lezione

**MAT-01 — Due ambiti.** Ogni versione di corso puo avere materiali generali e ogni lezione materiali propri, senza limite funzionale di un solo allegato. Nell'aula mostrare gruppi separati Materiali del corso e Materiali della lezione; nella scheda corso regione #jlms-course-materials. Liste paginate e limiti di upload reali configurati.

**MAT-02 — Campi.** Materiale con ID, ambito/proprietario, titolo obbligatorio, descrizione, asset/revisione, nome file sanificato, formato/MIME, dimensione, ordine, stato, date e pubblicabilita esplicita. Il proprietario e il corso sono ricavati e validati dal server. File in staging non visibili nel frontend.

**MAT-03 — Formati.** PDF, documenti, fogli di calcolo, presentazioni, immagini, TXT/CSV e ZIP ammessi da allowlist. Default pdf/docx/xlsx/pptx/odt/ods/odp/txt/csv/jpg/jpeg/png/webp/zip, massimo 20 MiB per allegato entro il limite dell'hosting. Niente eseguibili, HTML/SVG attivi o estrazione automatica di archivi. Controllare contenuto/MIME, estensione, dimensione e permessi, con scansione se configurata.

**MAT-04 — Controlli di accesso.** Materiali generali subordinati al corso/versione, account, livello Joomla e iscrizione attiva; materiali della lezione anche alla sua disponibilita e ai prerequisiti. Pubblicabilita in anteprima soltanto esplicita, senza apertura implicita della libreria. Download rivalida ogni volta l'oggetto proprietario; nessun accesso mediante ID manipolato o path dal client.

**MAT-05 — Consegna.** Storage protetto e download controllato, MIME/Content-Disposition corretti, nosniff e cache non pubblica. Consegna di file grandi delegabile al web server/storage dopo autorizzazione. Anteprima PDF/immagini opzionale, non lettore universale per tutti i formati. Copie gia scaricate dall'utente non revocabili dal LMS.

**MAT-06 — Versioni e rimozione.** Sostituzione con nuova revisione immutabile, senza sovrascrivere blob storici; cancellazione fisica solo senza riferimenti e secondo politica. Materiali di una lezione e del corso seguono la versione assegnata allo studente. Nessuna cancellazione dei contenuti per reset o rimozione del singolo studente.

**MAT-07 — Completamento.** I materiali extra sono supporti facoltativi: apertura, download o aggiunta di un file non cambiano la percentuale del corso. Un documento obbligatorio deve essere una lezione con una regola propria. Non raccogliere un registro indefinito dei download senza una finalita e conservazione configurate.

**MAT-08 — Editor e personalizzazione.** Gestione materiali nelle schede del corso e della lezione: upload, metadati, ordine, stato, sostituzione e rimozione con i permessi pertinenti. CSS pubblico #jlms-course-materials, #jlms-lesson-resources e classi .jlms-material, __title, __meta, __action; override Joomla. Nessuna cartella pubblica come ripiego per file riservati.

## 39. Servizi e contratti degli endpoint

**HTTP-01 — Servizi applicativi.** Definire servizi interni per policy accessi, catalogo, authoring/versioni, iscrizioni, aula/progresso/completamento, registry/playback, materiali/storage, quiz, attestati, report, notifiche/job, audit/privacy. Sono classi nello stesso componente, non microservizi. I controller e i template non duplicano la logica di autorizzazione o valutazione.

**HTTP-02 — Trasporto Joomla.** Nel rilascio 1 le azioni browser usano controller MVC site/backend del componente, con task dichiarati e formato JSON o raw. API REST per applicazioni esterne restano differite e si integreranno nei Web Services core. Il catalogo delle azioni non introduce gateway pagamento, servizi account paralleli o un cron pubblico.

**HTTP-03 — Metodi e policy.** GET per letture, POST per azioni/mutazioni nel trasporto MVC, sessione Joomla, permessi backend e CSRF su POST. Tutti gli endpoint controllano oggetto e accesso correnti; GET non modifica voti, iscrizioni o completamenti. Errori con stato HTTP, codice applicativo e correlazione senza informazioni riservate. Dati personali e token non in cache condivisa.

**HTTP-04 — Contratti e concorrenza.** DTO di input/output per avvio iscrizione, playback, progresso, quiz, upload/download, attestati, report e job; revisioni attese e idempotenza sulle azioni ritentabili. Stessa chiave con contenuto differente restituisce conflitto. Prima di consegnare un quiz attendere conferma del salvataggio; consegna riferita all'ultima revisione salvata. Revoca di accesso prevale anche sul replay di una vecchia richiesta.

**HTTP-05 — Gestori core e job.** Utenti, gruppi/livelli, categorie, tag, campi, lingue, plugin, privacy e Mail Templates rimangono nei gestori Joomla. Job lunghi tramite Scheduler/CLI amministrati e output protetti; niente duplicazione di endpoint core. Le azioni di authoring, materiali e correzione richiedono capacita distinte nel perimetro del corso.

**HTTP-06 — Catalogo tecnico.** L'allegato Servizi_endpoint_LMS_Joomla_1.0.md descrive responsabilita, invarianti ed esempi; Catalogo_endpoint_LMS_Joomla_1.0.csv elenca per azione contesto, metodo, task, percorso MVC proposto, servizio, autorizzazione, input/output, CSRF, retry, cache e stato da implementare. Il catalogo non e un'API gia esistente. Le firme finali PHP e i limiti di ciascun DTO sono parte della specifica tecnica di implementazione.

### 39.1 Scenari integrativi

| ID | Scenario | Risultato atteso |
|---|---|---|
| AC-97 | Installare un nuovo plugin video compatibile senza patch del componente | Provider selezionabile e player/progresso integrati mediante contratto comune |
| AC-98 | Chiave provider duplicata, versione incompatibile o plugin disabilitato | Errore esplicito, riferimenti conservati e nessun blocco globale o fallback pubblico |
| AC-99 | Capacita player insufficiente o errore plugin | Pubblicazione invalida bloccata; nessun completamento o autorizzazione attribuito dal plugin |
| AC-100 | Allegare file generali al corso e specifici a due lezioni | Due gruppi distinti e ordinati; materiali disponibili soltanto nei contesti pertinenti |
| AC-101 | Richiedere un materiale di altro corso o lezione bloccata con ID manipolato | Accesso negato, nessun percorso interno o contenuto riservato nella risposta |
| AC-102 | Sostituire un allegato e pubblicare una nuova versione corso | Revisione storica preservata; progresso e attestati invariati |
| AC-103 | Upload non ammesso, staging scaduto o asset di autore non autorizzato | Rifiuto controllato e nessuna pubblicazione o appropriazione del file |
| AC-104 | Provare ogni azione del catalogo con metodo/CSRF/sessione/ruolo errati | Rifiuto coerente per ciascuna rotta; nessuna scrittura da GET o utente non autorizzato |
| AC-105 | Ripetere mutazioni con stessa chiave e payload identico o diverso | Un solo risultato sul retry identico, conflitto sul payload diverso e accesso rivalidato |
| AC-106 | Consegnare quiz mentre un salvataggio e pendente o da una revisione obsoleta | Nessuna perdita silenziosa; ultima revisione confermata oppure conflitto esplicito |
| AC-107 | Ispezionare risposte playback e cache fra utenti | Token temporanei solo al destinatario autorizzato, nessuna credenziale provider o cache condivisa |
| AC-108 | Scaricare output di un job altrui o dopo perdita del permesso | Output protetto e accesso negato; gli strumenti core non aggirati |


## 40. Vincoli di sviluppo software sicuro e architettura

**SECDEV-01 — MVC Joomla.** Implementare controller, model, view e tmpl con le classi e convenzioni MVC Joomla; servizi applicativi condivisi tramite dependency injection. Controller sottili, modelli per dati/form e layout privi di query e logica autorizzativa/didattica. Nessun motore MVC o bootstrap parallelo.

**SECDEV-02 — Core e PHP.** Applicare la matrice CORE-01–23 e le best practices Joomla/PHP, PSR-12, namespace e contratti tipizzati/documentati. Usare runtime supportati e verificare API della patch target; nessuna modifica del core o dipendenza intenzionale da API rimosse. Motivare ogni servizio core non pertinente senza duplicarlo automaticamente.

**SECDEV-03 — Input, query e output.** Validazione server, campi allowlist, query con binding tramite servizi Joomla, output contestualmente codificato e HTML filtrato. Test contro SQL injection, XSS, mass assignment e formule CSV. Nessuna concatenazione di input come query o esecuzione dinamica di codice non attendibile.

**SECDEV-04 — Identita, autorizzazioni e richieste.** Utenti/sessioni e livelli Joomla; controllo oggetto/parent a ogni operazione, CSRF su mutazioni browser, metodo HTTP, concorrenza e idempotenza. La presenza di un token CSRF non equivale a un permesso. I job e i plugin non acquisiscono privilegi generici da super user.

**SECDEV-05 — File e chiamate esterne.** Upload/storage protetti, MIME/limiti, percorsi generati, nessun eseguibile o estrazione ZIP automatica. API provider su HTTPS verificato e host ammessi, timeout/retry e protezione SSRF anche sui redirect; nessun fetch di URL server arbitrari. Nessun ripiego pubblico dopo errore di sicurezza.

**SECDEV-06 — Segreti e dati.** SecretProvider/configurazione server protetta, chiavi separate dal database ove necessarie, nessun segreto nei form lezione, DTO, export, log o pacchetti. Primitive crittografiche sicure mantenute, cache personale corretta, risposte quiz non anticipate e nessuna crittografia proprietaria improvvisata. Un normale campo password del form non viene considerato automaticamente cifratura a riposo.

**SECDEV-07 — Processo verificabile.** Modello delle minacce, revisione codice sensibile, lockfile/inventario dipendenze, lint/stile/analisi statica/scansione segreti e dipendenze, test positivi e negativi. OWASP ASVS come riferimento con versione fissata, senza certificazioni implicite. Vulnerabilita critiche/alte confermate e pertinenti non risolte bloccano la distribuzione; non applicabilita e falsi positivi motivati.

**SECDEV-08 — Rilascio e manutenzione.** Build riproducibile, pacchetto senza segreti/file dev, checksum, changelog e aggiornamenti Joomla su HTTPS. Collaudare installazione, aggiornamenti con dati/configurazioni esistenti e recupero; ripetere le verifiche pertinenti per modifiche sensibili. Nessuna checklist viene dichiarata superata prima dell'esecuzione.

## 41. Plugin video nel pacchetto base e documentazione obbligatoria

**PBASE-01 — Inclusione nella distribuzione.** Lo ZIP base installabile deve contenere il componente e i quattro plugin dedicati: YouTube, Vimeo, Cloudflare Stream e HTML5 locale. Sono modulari e gestiti dal core Joomla, ma non componenti da acquistare/scaricare separatamente per ottenere le funzioni descritte. Tutti installati; attivazione e segreti sono configurazione del sito, senza credenziali o contenuti pubblicati automaticamente.

**PBASE-02 — Specifiche per singolo plugin.** Documentare per ciascuno identita/versione, prerequisiti, manifest/dipendenze, parametri con tipi/default/obblighi, formati sorgente, dati lezione, capacita, player/eventi, ripresa/completamento, privacy/consenso, errori, prestazioni, limitazioni, aggiornamento/disattivazione/rimozione e collaudi. Il solo contratto astratto non e documentazione sufficiente dei quattro plugin.

**PBASE-03 — Guide e contratti.** Il documento Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md contiene le schede e i dizionari dei parametri; Servizi_endpoint_LMS_Joomla_1.0.md il contratto comune. Consegnare guide amministratore/autore/sviluppatore ed esempio di estensione compatibile. Contratto, manuali, changelog e test seguono ogni modifica; assenza di documentazione di un plugin base blocca l'accettazione del pacchetto.

**PBASE-04 — Collaudo completo.** La matrice dedicata Matrice_collaudo_plugin_video_1.0.csv copre contratto comune e quattro provider. Verificare incluso/attivo/configurato come stati diversi, dipendenze compatibili e update che preserva stato, segreti esterni, riferimenti, dati e override. Nessuna cancellazione remota di video all'installazione/disinstallazione.

### 41.1 Scenari di sicurezza e distribuzione

| ID | Scenario | Risultato atteso |
|---|---|---|
| AC-109 | Installare il solo ZIP base su Joomla pulito | Componente e quattro plugin video presenti, nessun download aggiuntivo dei plugin e nessuna attivazione insicura |
| AC-110 | Review di controller/model/view/tmpl e servizi | MVC Joomla, separazione delle responsabilita, niente query/permessi o voti nei layout |
| AC-111 | Controlli stile/runtime/core su tutte le estensioni incluse | PSR-12, nessuna API rimossa o patch core, matrice di compatibilita documentata |
| AC-112 | Payload SQL injection, XSS e mass assignment in form/API/CSV | Query parametrizzate, campi sistema non modificabili e output codificato secondo contesto |
| AC-113 | CSRF valido ma ACL assente, ID altrui e richiesta da job/plugin | Nessun accesso concesso dal solo token o contesto privilegiato implicito |
| AC-114 | Upload/percorso malevolo e API con redirect verso destinazione privata | Nessuna esecuzione, lettura arbitraria o SSRF; richiesta rifiutata e log senza segreti |
| AC-115 | Ispezionare config/export/log/browser/ZIP e ruotare il segreto Stream | Segreti server assenti dalle superfici non autorizzate e rotazione gestita senza divulgazione |
| AC-116 | Analisi del report di release e delle dipendenze | Threat model, review, lockfile, controlli e risultati disponibili; vulnerabilita bloccanti non ignorate |
| AC-117 | Aggiornare componente e plugin con configurazioni/dati reali di prova | Stato e riferimenti preservati, errore parziale diagnosticato, nessuna apertura privacy o perdita segreti |
| AC-118 | Confrontare documentazione e collaudi dei quattro plugin col build | Ogni plugin base ha scheda completa, parametri e test pertinenti con esiti tracciabili |


## Fonti

Fonti ufficiali consultate il 30 ottobre 2026. Le sezioni progettuali sono requisiti originali; le fonti documentano i servizi core Joomla, gli standard e i punti di integrazione con i provider.

- **S1 — Joomla, Privacy:** https://guide.joomla.org/component/content/category/documentation/user/joomla-extra/privacy
- **S2 — Joomla, requisiti tecnici:** https://manual.joomla.org/docs/next/get-started/technical-requirements/ (pagina del manuale in evoluzione; requisiti da riconfermare sulla release target).
- **S3 — Joomla, categorie nei componenti:** https://manual.joomla.org/docs/next/general-concepts/categories/implementing-categories-in-components/
- **S4 — Joomla, lingue, multilingua e override:** https://manual.joomla.org/docs/next/general-concepts/multilingual/
- **S5 — Joomla, struttura delle estensioni:** https://manual.joomla.org/docs/next/building-extensions/
- **S6 — Joomla, livelli di accesso e gruppi:** https://guide.joomla.org/help-pages-references/users/users-viewing-access-levels
- **S7 — YouTube IFrame Player API:** https://developers.google.com/youtube/iframe_api_reference
- **S8 — YouTube, visibilità dei video:** https://support.google.com/youtube/answer/157177?hl=en
- **S9 — Vimeo Player SDK ufficiale:** https://github.com/vimeo/player.js
- **S10 — Vimeo, privacy dell'embed per dominio:** https://help.vimeo.com/hc/en-us/articles/30030693052305-How-do-I-set-up-domain-level-privacy
- **S11 — Cloudflare Stream Player API:** https://developers.cloudflare.com/stream/viewing-videos/using-the-stream-player/using-the-player-api/
- **S12 — Cloudflare Stream, URL firmati e origini consentite:** https://developers.cloudflare.com/stream/viewing-videos/securing-your-stream/
- **S13 — Joomla, cache di componenti e moduli:** https://manual.joomla.org/docs/next/general-concepts/cache/caching-views-modules/
- **S14 — W3C, unicità dell'attributo id:** https://www.w3.org/TR/REC-html40/struct/global.html

Le pagine “next” descrivono un manuale in evoluzione: firme API e modalità di integrazione di tutte le funzioni core saranno ricontrollate sulla specifica versione Joomla 6 selezionata, senza ridurre i requisiti funzionali concordati.

Le verifiche documentali e l'ispezione dei sorgenti ufficiali non sostituiscono il collaudo operativo delle integrazioni nel componente.

Riferimenti aggiuntivi della verifica 1.0: codice ufficiale Joomla CMS, tag 6.0.0, percorsi e SHA in `Evidenze_Joomla_6.json`; manuale categorie della serie 6.1 e API Joomla 6; riferimenti dettagliati nella sezione 32. La verifica non usa il manuale “next” come prova di compatibilità di una release ancora futura.
