Warning: truncated output (original token count: 44862)
Total output lines: 1425

## IMP-05 — capability contract provider video

1. Installare il pacchetto candidato su Joomla di prova e verificare che i quattro plugin video risultino disponibili e che soltanto quelli intenzionalmente usati siano abilitati.
2. Preparare una lezione video tracciabile per ciascun provider (YouTube, Vimeo, Cloudflare Stream e HTML5) e provarla come studente autorizzato dopo il consenso richiesto.
3. Verificare posizione, durata, seek e ripresa nei quattro player; nessuna variazione della copertura o del completamento deve essere attribuita al solo seek.
4. Con video/account che lo supportano, verificare il selettore velocità su YouTube, Vimeo e HTML5; con un contenuto/account senza velocità disponibile il selettore non deve apparire. Cloudflare Stream non deve mostrare il controllo velocità.
5. Con video dotati di caption, verificare le tracce e la selezione sui player Vimeo e HTML5; su YouTube e Cloudflare Stream verificare la preferenza lingua all’avvio e il comportamento normale se la lingua non è disponibile. Nessuna preferenza va salvata sul profilo o inviata al server.
6. Verificare che la trascrizione locale sia disponibile separatamente per tutti i provider e che l’assenza di caption non generi una traccia artificiale.
7. Con Cloudflare Stream, verificare scadenza/rinnovo del token e che il download resti disabilitato; con provider disabilitato o non configurato, la riproduzione deve rimanere indisponibile.
8. Provare una sorgente configurata per un provider con l’adapter sbagliato: non deve risultare supportata né produrre una sessione valida.

**Esito sito Joomla:** Da eseguire su account, SDK e browser reali. I test automatici della capability matrix verificano il contratto dichiarato; non provano la disponibilità delle singole tracce o velocità nei contenuti reali.

---

## Issue #446 e #448 — toolbar comunicazioni e disponibilità corso

1. Aggiornare JD Learn su Joomla 6/Atum e aprire **Comunica agli iscritti** dal roster. Verificare che **Iscritti al corso** sia il primo comando della toolbar, con icona indietro; in fondo al corpo non deve apparire un link duplicato.
2. Dal roster aprire **Disponibilità del corso**. Verificare che **Iscritti al corso** sia il primo comando della toolbar e che i quattro comandi di azione siano nella toolbar, senza duplicati nel corpo pagina.
3. Aprire ciascuna operazione: Chiudi nuove ammissioni, Apri nuove ammissioni, Sospendi erogazione didattica, Riprendi erogazione didattica. Verificare corso e operazione selezionata, impatto, conferma, CSRF e risultato dopo il submit; il ritorno deve mantenere il corso.
4. Provare entrambi gli stati delle ammissioni e dell’erogazione, inclusi comandi già applicati e combinazioni tra loro. Verificare che audit e messaggi di conferma restino corretti.
5. Ripetere in IT/EN, desktop/mobile e con ruoli autorizzati e non autorizzati. L’accesso diretto agli URL non deve superare le ACL.

**Esito utente, 15 novembre 2026:** confermato che il ritorno al roster e le azioni toolbar delle issue #446/#448 funzionano. Collaudata la funzionalità. La richiesta cosmetica successiva accorcia le etichette in una PR distinta.

**Esito PR #452, 15 novembre 2026:** l'utente conferma che i pulsanti con etichette ridotte si vedono correttamente. La conferma riguarda la sola resa riferita; gli altri scenari della checklist restano aperti.

## Issue #455 — ritorno tra Corsi, iscritti e iscrizione manuale

1. Aggiornare JD Learn su Joomla 6/Atum e aprire il roster di un corso: il primo pulsante toolbar deve tornare a **Corsi** con icona indietro. **Iscrizione manuale** deve mostrare l'icona plus.
2. Aprire **Iscrizione manuale**: il primo pulsante toolbar deve riportare a **Iscritti** dello stesso corso, con icona indietro.
3. Verificare che nel corpo di entrambe le viste non compaiano i vecchi link di ritorno duplicati.
4. Verificare il corso/ID mantenuto nei percorsi, l'accesso da ruoli autorizzati e il diniego ACL agli altri; ripetere in italiano/inglese e su desktop/mobile.
5. Verificare anteprima, applicazione iscrizione manuale, ricevuta e filtri/paginazione del roster non modificati. Provare apertura diretta e uso della navigazione nativa Atum.

**Esito utente, 15 novembre 2026:** collaudo completo della issue #455 e della PR #457 riuscito. Confermati i ritorni dalla toolbar, l'icona Iscrizione manuale, la rimozione dei link duplicati, le destinazioni e il corso mantenuto, ACL e accesso diretto, IT/EN, desktop/mobile e il normale flusso di iscrizione manuale. La conferma vale per gli scenari della issue; non chiude l'intero requisito UI-02 o ISC-01.

## Issue #434 — azioni della vista iscritti nella toolbar

1. Aggiornare JD Learn su Joomla 6/Atum e aprire il roster di un corso con un account autorizzato. Verificare che **Iscrizione manuale**, **Comunica agli iscritti** e **Disponibilità del corso** compaiano una sola volta nella toolbar Joomla e non nel corpo della pagina.
2. Aprire ciascuna azione e verificare che mantenga il corso corretto. In particolare, **Disponibilità del corso** deve aprire l'operazione prevista di chiusura ammissioni. Tornare al roster con la navigazione nativa.
3. Verificare filtri, richieste pending, tabella, paginazione e azioni per singola iscrizione. Ripetere in italiano e inglese, su desktop e mobile.
4. Con un ruolo privo dei permessi richiesti, verificare che l'accesso alle viste di destinazione continui a essere negato anche inserendo direttamente l'URL.

La modifica riordina la navigazione del roster. Non cambia le operazioni né i relativi controlli ACL.

**Esito utente, 15 novembre 2026:** confermata la corretta visualizzazione dei pulsanti nella toolbar. La conferma copre la presenza visiva dei pulsanti; gli altri scenari sopra restano da verificare separatamente.

## MAIL-H — rendering template personalizzati, escaping (PR #410)

1. Installare/aggiornare build `0.57.7-dev` su sito di prova Joomla 6.2. In **Sistema → Modelli email** personalizzare una variante HTML di `com_jlms.enrollment.manager_request`: usare sia `{COURSE_TITLE}` sia `{COURSE_TITLE_HTML}` in paragrafi distinti; verificare che titoli contenenti `&`, `<`, `>` e virgolette vengano mostrati una volta sola senza doppio escaping e senza markup iniettato.
2. Verificare con `com_jlms.course.message` che `{MESSAGE_BODY_TEXT}` venga escapato in HTML e che `{MESSAGE_BODY_HTML}`, già sanitizzato dal producer, conservi il markup innocuo consentito; ripetere nel plain text e verificare che non appaiano `&amp;lt;` o tag HTML indesiderati.
3. Verificare modello `com_jlms.course.published` con anteprima immagine e lezioni pubbliche: `{COURSE_IMAGE_HTML}`, `{COURSE_INTRO_HTML}`, `{COURSE_LESSONS_HTML}`, `{UNSUBSCRIBE_URL_HTML}` non devono essere codificati due volte. Il modello personalizzato preesistente deve restare invariato dopo upgrade.
4. Ripetere in lingua amministrativa IT ed EN, HTML e plain text, controllando retry/outbox e assenza di regressioni su ACL e avvisi ai responsabili. Su template con placeholder inesistenti, il dispatch deve continuare a fallire senza invio o retry infinito.

**Test automatici non sostituiscono l'accettazione SMTP/Joomla su sito di prova.**

## PR #407 — navigazione amministrativa essenziale e issue #394 (accettazione Joomla pendente)

1. Aggiornare da 0.57.5-dev a 0.57.6-dev su Joomla 6 / Atum. Verificare la sidebar a **cinque** voci nell'ordine: Dashboard, Corsi, Percorsi formativi, Docenti, Banca domande. **Coda email** assente dalla sidebar, presente nella dashboard; nessuna voce obsoleta dopo l'aggiornamento.
2. Aprire Corsi → editor → programma → lezione; Percorsi → editor; Docenti → editor; Banca domande → editor. Verificare che la voce operativa corrispondente resti evidenziata e che JD Learn rimanga espanso.
3. Aprire dalla dashboard **Coda email**, **Variabili modelli email**, **Dipendenze categorie**, **Attestati** e un dettaglio secondario. Verificare che il nodo JD Learn rimanga espanso ma nessuna voce operativa sia evidenziata. Gli URL diretti devono comportarsi ugualmente. Nessun errore HTTP 500.
4. Aprire dalla dashboard **Categorie corsi**, **Categorie percorsi**, **Campi aggiuntivi**, **Plugin video** e **Template email**. Verificare che questi componenti nativi Joomla non ricevano un focus fittizio su una voce JD Learn e mantengano i propri ACL e filtri.
5. Issue **#394**: verificare visivamente la presenza dell'icona cartella su **Dipendenze categorie** in italiano/inglese, su desktop/mobile e tema chiaro/scuro. Non basta la verifica della classe HTML: verificare la resa effettiva in Atum.
6. Provare Super User e ruoli ACL limitati per ogni link, URL diretto e relativo accesso negato. Verificare installazione pulita e upgrade preservando dati, registrazione dei menu e pacchetto installabile.

La CI automatica non costituisce accettazione manuale. Perimetro UX, nessun aumento automatico della stima globale di implementazione.

## MAIL-H — issue #363: anteprima email nella toolbar editor corso (PR candidata, accettazione pendente)

1. Aggiornare Joomla con build `0.57.5-dev`. Aprire **JD Learn → Corsi** e un corso **pubblicato** con un utente autorizzato alla modifica. La toolbar amministrativa deve mostrare **Anteprima email di annuncio**, una sola volta; il vecchio pulsante nel corpo del form non deve esserci.
2. Fare clic sul pulsante: si apre la pagina di anteprima già esistente, con titolo corso e contenuto previsto. L'apertura è navigazione GET, senza salvataggio, email in coda o invio SMTP. Verificare il pulsante di ritorno e il comportamento di checkout.
3. Aprire una bozza/non pubblicato: il pulsante **non** compare. Ripetere su corso in checkout da altro utente: nessun pulsante. Un utente senza permesso di modifica non può accedere all'editor né eludere i permessi dell'anteprima passando direttamente l'URL.
4. Verificare che **Salva**, **Salva e chiudi**, **Salva e programma**, **Versioni** e **Chiudi** funzionino normalmente e che il rendering dell'editor rimanga corretto su schermi desktop e smartphone. Ripetere in amministrazione IT ed EN; verificare assenza di doppio pulsante e nessuna email al semplice caricamento.

**CI automatica e collaudo manuale Joomla sono distinti**. Il fix è una regressione UX, non aumenta la copertura funzionale stimata verso 1.0.0.

## MAIL-G — nuovi avvisi ai responsabili delle richieste (PR #401, QA pendente)

1. Aggiornare Joomla 6.2 alla build 0.57.4-dev. Verificare **Sistema → Modelli email**: `com_jlms.enrollment.manager_request`, titolo/descrizione IT/EN, token e conservazione delle modifiche a template esistenti.
2. Configurare un corso in modalità iscrizione con approvazione, con responsabile amministrativo che possiede `core.manage` su `com_jlms` e `jlms.enrollments.manage` sul corso, e un docente assegnato dotato del permesso sullo stesso corso. Con la preferenza globale OFF e override Eredita, creare richiesta pending: nessun nuovo messaggio. Con override Abilita, creare una nuova richiesta e lanciare lo Scheduler: un solo messaggio per ciascun responsabile autorizzato.
3. Aggiungere docente assegnato senza permesso di gestione, docente di un altro corso, utente non autorizzato e studente richiedente: **nessun avviso** a loro. Con globale ON e override Disabilita: nessun nuovo avviso.
4. Ripetere Scheduler e tentare richieste duplicate: deduplica per richiesta+manager, non oltre due giorni dalla creazione. Approvare/rifiutare prima del dispatch: `skipped`, destinatario/PII rimossi; nessun avviso per richieste già decise prima della rilevazione.
5. Accodare e poi revocare il permesso, rimuovere l'assegnazione docente, bloccare il responsabile, cambiarne email o disabilitare la preferenza: skip senza spedizione SMTP. Verificare che non vengano esposti nominativo, email o motivazione del richiedente nel messaggio.
6. Eseguire SMTP e retry su installazione Joomla di prova; aprire il link al registro iscrizioni da account con e senza permesso per verificare la protezione delle viste. Provare lingua IT/EN, caratteri speciali nel titolo, upgrade, più amministratori e throughput di molte richieste.

**La CI non sostituisce l'accettazione SMTP/manuale.** Registrare le evidenze distinte per scenario.

## MAIL-G — aggiornamenti didattici significativi (PR #399, accettazione pendente)

1. Installare o aggiornare la build 0.57.3-dev su Joomla 6.2; verificare in **Sistema → Modelli email** il modello `com_jlms.course.updated` e relativi titoli/descrizioni IT/EN. Assicurarsi che i modelli già personalizzati non vengano sovrascritti.
2. Creare due versioni pubblicate con differenze materiali in lezioni, quiz, contenuti, materiali o regole. Pubblicare la nuova versione senza migrare iscritti: Scheduler non deve produrre email di aggiornamento. Ripetere solo salvataggi di bozza: nessun invio.
3. Migrare esplicitamente un'iscrizione attiva alla nuova versione con modifica sostanziale. Con globale OFF e override Eredita: nessun invio. Con override Abilita, il task deve accodare e inviare un solo messaggio; ripetere Scheduler e conferma idempotente: zero duplicati. Con globale ON e override Disabilita: niente email.
4. Ripetere con differenza **solo editoriale** (presentazione, titolo promozionale): nessuna notifica; poi provare cambi di programma/quiz e verificare che la modifica sia riconosciuta. Controllare che studenti non migrati non ricevano comunicazioni.
5. Dopo l'accodamento, sospendere o completare l'iscrizione, bloccare account, disabilitare preferenza, revocare ACL/tassonomia o chiudere edizione: `skipped` e nessun SMTP; dati personali rimossi dalla riga. Verificare versioni errate, utenti con lingua IT/EN e assenza di URL a risorse private.
6. Provare il task schedulato dopo migrazione recente, SMTP fallito/retry e finestra 48h; controllare sicurezza dei log, escaping HTML dei titoli e preservazione dei dati con upgrade della build precedente.

**Accettazione manuale non eseguita.** La CI non costituisce evidenza SMTP su sito di prova.

## MAIL-G — disponibilità nuove lezioni (PR #397, accettazione pendente)

1. Installare 0.57.2-dev su Joomla 6.2 e aprire **Sistema → Modelli email** in IT/EN. Verificare `com_jlms.lesson.available`, titolo, descrizione, tag canonici, subject/HTML/testo e conservazione dei template personalizzati in upgrade.
2. Preparare una versione pubblicata con un'iscrizione attiva e una lezione con `publish_up` futuro. Mantenere la preferenza globale OFF e per corso eredita: dopo la disponibilità, Scheduler non deve accodare nulla. Abilitare un secondo caso per corso: al rilascio deve essere presente una sola notifica per iscrizione e lezione. Verificare override corso disabilitato con globale ON.
3. Ripetere per `release_after_days`, anche con `publish_up` più tardo e studente che non ha ancora avviato il corso. La data effettiva più tarda deve determinare la disponibilità. Una lezione già disponibile prima dell'iscrizione, una bozza e un normale salvataggio non producono email.
4. Iscrivere uno studente a una versione precedente mentre ne viene pubblicata una nuova con lezioni differenti: nessuna notifica relativa alle lezioni di versione non assegnata. Verificare un'iscrizione completata, sospesa, edizione chiusa, corso con erogazione sospesa, utente bloccato e finestre scadute.
5. Dopo l'accodamento revocare un permesso, bloccare l'utente o l'accesso, sospendere l'iscrizione o disattivare la preferenza: prima di SMTP il record deve passare a `skipped` ed essere privato dei dati personali; niente comunicazioni improprie.
6. Eseguire più volte Scheduler e simulare SMTP temporaneamente assente: invio singolo, retry limitato, niente doppio invio; verificare che non vengano inviate email automaticamente al salvataggio o per eventi di oltre 48 ore prima del nuovo controllo.
7. Confermare resa HTML/text IT/EN, escaping di titoli con caratteri speciali e collegamento sicuro alla sola dashboard autenticata. Registrare esiti separati dalla CI, inclusa performance su molte iscrizioni.

**Stato:** da eseguire manualmente sul sito di prova Joomla/SMTP; la CI non sostituisce l'accettazione.

## UX navigazione amministrativa — issue #359 (collaudo richiesto)

1. Installare/aggiornare JD Learn su Joomla 5/6 in italiano e inglese. Verificare che la sidebar presenti **solo** Dashboard, Corsi, Percorsi formativi, Docenti, Coda email, Banca domande, esattamente in questo ordine e senza voci residue dopo l'aggiornamento.
2. Aprire la dashboard. Verificare le sezioni Didattica, Persone e valutazione, Comunicazioni, Configurazione & Strumenti. Verificare il collegamento Dipendenze categorie in quest'ultima, la destinazione della vista di ricollocazione, e il pannello Stato provider video.
3. In Comunicazioni verificare la voce **Variabili modelli email** (#359), che deve continuare ad aprire la guida esistente; verificare anche Template email nativi Joomla e Coda email.
4. Con amministratore autorizzato e con ruoli ACL limitati confrontare sidebar, dashboard e accesso diretto agli URL: nessun collegamento deve eludere le ACL.
5. Aprire in successione elenco corsi, modifica corso, programma, modifica lezione; poi percorsi e modifica percorso, docenti e modifica docente, banca domande e modifica domanda, coda email e vista interna. Verificare la selezione della voce attiva fornita dal menu nativo Joomla nei percorsi profondi; l'accettazione manuale è ancora pendente.
6. Da una vista secondaria accessibile solo dalla dashboard (Dipendenze categorie, Plugin video, Campi aggiuntivi, Template email) verificare l'assenza di un'evidenziazione fuorviante e la disponibilità di percorsi di ritorno tramite strumenti nativi Joomla. Verificare il percorso di ritorno con la toolbar e la navigazione nativa Atum. Non utilizzare Pathway nel client administrator: provoca HTTP 500. L'accettazione manuale è ancora pendente.
7. Verificare eventuali menu di amministrazione personalizzati, caching e aggiornamento da una versione precedente: non deve essere necessario reinstallare il componente.

La mappatura nativa della voce attiva è implementata; Pathway amministrativo non è supportato. Le verifiche visive 5 e 6 restano **collaudi manuali aperti**. Non dichiarare accettazione su Atum senza riscontro reale. L'esito dei test automatici non costituisce accettazione manuale.

## Issue #391 — etichetta localizzata Salva & Chiudi

1. Installare il pacchetto `0.56.29-dev` della PR #391 integrata su Joomla 6.2.
2. Con lingua amministrativa inglese, aprire **JD Learn → Corsi → Nuovo** e verificare che la toolbar mostri **Save & Close**, senza chiavi tecniche.
3. Cambiare la lingua amministrativa in italiano, riaprire l’editor e verificare **Salva & Chiudi**.
4. Ripetere su un corso esistente e verificare che **Salva**, **Salva & Chiudi** e **Salva e apri programma** mantengano i rispettivi comportamenti.
5. Verificare che non appaiano le stringhe letterali `JTOOLBAR_SAVE_AND_CLOSE` o `COM_JLMS_COURSE_SAVE_AND_CLOSE`.

CI [43649969902](https://github.com/jeckodevelopment/jd_learn/actions/runs/43649969902) verde 4/4; la prova HTTP autenticata controlla form nuovo/esistente e traduzioni IT/EN. L’accettazione visuale sul sito di prova resta da registrare.

## Issue #384 — toolbar salvataggio corso

1. In Joomla 6.2 accedere come amministratore con permesso di creazione e modifica corsi.
2. Aprire **JD Learn → Corsi → Nuovo**. Verificare che **Salva** sia autonomo e che **Salva & Chiudi** sia il pulsante principale split.
3. Aprire il menu split: verificare la voce **Salva e apri programma**.
4. Verificare i tre esiti: **Salva** salva e resta nell’editor; **Salva & Chiudi** salva e torna all’elenco; **Salva e apri programma** salva e apre il programma del corso.
5. Ripetere le verifiche su un corso esistente e verificare che la validazione e la protezione read-only/check-out restino attive.

CI [43648461944](https://github.com/jeckodevelopment/jd_learn/actions/runs/43648461944) **4/4 verde**. La prova HTTP autenticata verifica le tre etichette e i task effettivamente renderizzati su form nuovo/esistente. Resta da registrare l’accettazione manuale sul sito Joomla di prova.

## Issue #364 — anteprima email annuncio corso

1. Accedere al backend Joomla come amministratore con autorizzazione a modificare il corso.
2. Aprire **JD Learn → Corsi**, modificare un corso esistente e individuare l’azione per l’anteprima dell’annuncio email.
3. Aprire l’anteprima. La pagina deve caricarsi senza 404 e mostrare il titolo del corso e lo stato dell’annuncio (anteprima disponibile, corso non idoneo oppure nessun destinatario).
4. Verificare che l’anteprima non invii email: l’accodamento deve avvenire solo dopo la conferma esplicita, con token Joomla valido.
5. Con un utente privo di permesso di modifica sul corso, tentare di aprire direttamente lo stesso URL: Joomla deve negare l’accesso senza mostrare i contenuti del corso.

CI copre il routing HTTP autenticato usando un corso fixture e verifica il contenuto della vista. L’invio reale SMTP e l’accodamento restano fuori da questo collaudo di sola anteprima.

## Issue #365 — azioni di salvataggio nella toolbar corso

1. In Joomla 6.2, accedere con un utente autorizzato a creare corsi e aprire **JD Learn → Corsi → Nuovo**. Verificare che **Salva** resti un'azione autonoma e che **Salva & Chiudi** sia il pulsante principale del gruppo a discesa.
2. Aprire il menu di **Salva & Chiudi** e verificare che **Salva e apri programma** compaia come opzione aggiuntiva. Selezionarla sul nuovo corso: il corso deve essere salvato una volta e si deve aprire il suo programma didattico.
3. Modificare un corso esistente e ripetere le tre azioni: **Salva** deve restare nella pagina, **Salva & Chiudi** deve tornare all'elenco, l'opzione del menu deve salvare e aprire il programma.
4. Verificare che la stessa opzione inline nella scheda **Programma** continui a funzionare, che la validazione del form sia rispettata e che la toolbar resti nascosta quando il corso è in sola lettura/check-out di un altro utente.

CI controlla il wiring Joomla della toolbar e il task associato; il flusso controller/programme è coperto dalla suite esistente. Accettazione visuale e di navigazione sul sito di prova da registrare.

## Issue #368 — traduzione voce sidebar ricollocazione categorie

1. Aprire il backend Joomla 6.2 in italiano e verificare che la voce **Gestione dipendenze categorie** compaia nel menu laterale JD Learn invece della chiave `COM_JLMS_CATEGORY_REASSIGN_TITLE`.
2. Passare all’inglese e verificare che la stessa voce mostri **Category dependency cleanup**.
3. Aprire la voce in entrambe le lingue e verificare che la pagina si carichi senza errori e mantenga le autorizzazioni esistenti.

CI verifica entrambe le traduzioni in `com_jlms.sys.ini` e che Joomla risolva la chiave in inglese nel menu amministrativo.

## Issue #377 — vista Docenti: stato e conteggio corsi

1. In Joomla 6.2 di prova, aprire **JD Learn → Docenti**. Verificare che la colonna Stato mostri l'icona nativa Joomla per pubblicato/non pubblicato, senza testo e senza consentire toggle inline.
2. Verificare il conteggio Corsi: con corsi assegnati il pulsante usa il colore di conteggio positivo delle categorie; con zero corsi usa il colore secondario. Aprire il conteggio e verificare che la lista Corsi sia filtrata per docente.
3. Controllare contrasto e leggibilità in tema chiaro/scuro, navigazione da tastiera e viewport desktop/mobile.

CI controlla uso di `jgrid.published`, assenza del toggle inline e classi di stato del conteggio. Accettazione visiva manuale da registrare.

## IMP-09 / issue #375 — toolbar Guida alle variabili email

1. Accedere con un amministratore autorizzato a `com_jlms`, `core.manage` e `core.edit` su `com_mails`. Aprire **JD Learn → Variabili modelli email** e verificare che il pulsante **Template Email** compaia nella toolbar e apra i modelli filtrati su `com_jlms`.
2. Verificare che il pulsante non sia duplicato nel corpo della guida.
3. Accedere con un utente autorizzato a JD Learn ma senza `core.edit` su `com_mails`: il pulsante non deve comparire. Ripetere senza `core.manage` su `com_mails`.
4. Verificare che catalogo, contenuto e lingua IT/EN della guida restino invariati.

CI automatica copre la presenza del link in toolbar, il filtro `com_jlms`, il controllo ACL e la rimozione del collegamento dal corpo pagina. Accettazione manuale Joomla da registrare.

## CORE-10 — Campi aggiuntivi su Joomla 6.2.0 (PR #378)

1. Aggiornare JD Learn su un sito di prova Joomla 6.2.0. Creare un campo testuale distinto per ciascuno dei tre contesti.
2. Da **JD Learn → Campi aggiuntivi**, cambiare il selettore avanti e indietro. Controllare URL, etichetta selezionata e righe: Corsi = `com_jlms.course`, Docenti = `com_jlms.teacher`, Lezioni = `com_jlms.lesson`.
3. Aprire il programma di un corso in bozza e creare o modificare una lezione. Il form deve mostrare il campo nativo lezione senza errore HTTP 500.
4. Verificare che un editor delegato privo del permesso di gestione Campi non veda il collegamento nel menu. Da una sessione anonima verificare il rinvio al login per tutti e tre gli URL diretti.

La suite HTTP copre selettore, isolamento degli elenchi, form lezione e accesso dopo l’upgrade. Collaudo manuale sul sito di prova da registrare.

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
6. Tornare in modalità **Apprendimento**: la pubblicazione senza almeno una lezione obbligatoria deve essere…24862 tokens truncated…chetto candidato su Joomla di prova con SMTP di test e attività pianificata JD Learn attiva; verificare che il template `com_jlms.enrollment.confirmed` esista in Sistema → Modelli email e che le personalizzazioni esistenti siano preservate dall’upgrade.
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

Il collaudo manuale/SMTP resta da registrare. La revoca anonima tramite link è coperta dal caso IMP-09-MAIL-D dedicato qui sotto. Registrazione Joomla, trigger e invio degli annunci restano fuori da questa tranche.


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

## MAIL-11.2 — opt-in durante la registrazione Joomla (0.56.14-dev)

Eseguire sul sito di prova con un indirizzo email controllato e il mailer Joomla collegato alla casella di test. Il plugin utente **JD Learn - Consenso email nuovi corsi** deve essere attivo dopo una nuova installazione.

1. Aprire la registrazione Joomla in italiano e inglese. Verificare la sezione facoltativa con checkbox non selezionata e testo che spiega conferma email e revoca; completare la registrazione senza selezionarla. L’account deve essere creato e nessun consenso deve risultare pending o confirmed.
2. Ripetere selezionando la checkbox. Verificare che l’account venga creato, il consenso risulti pending con origine `registration`, e venga inviata l’email double opt-in. Prima della conferma l’utente non deve essere eleggibile per annunci di nuovi corsi; confermare dal link e verificare lo stato confirmed.
3. Verificare che la preferenza selezionata non diventi attiva senza conferma; provare link scaduto/riutilizzato tramite i test già descritti nelle sezioni MAIL-11.3.
4. Simulare un errore di invio SMTP. La registrazione deve comunque riuscire e mostrare un avviso comprensibile; controllare log senza indirizzo email o altri dati personali. Verificare che la preferenza non risulti confirmed.
5. Disabilitare manualmente il plugin in **Sistema → Gestisci → Plugin**, aggiornare il pacchetto e verificare che resti disabilitato. Su nuova installazione deve invece essere attivo.
6. Compilare la registrazione senza consenso e con consenso selezionato, verificando che gli altri plugin e i campi standard Joomla continuino a funzionare.

La CI esercita il caricamento del plugin e la presenza del campo nel form nativo; il flusso SMTP e i casi sopra restano collaudo manuale sul sito Joomla.



## MAIL-11.4/11.5 — anteprima e invio controllato annuncio corso

**Comportamenti da testare sul sito Joomla di prova**

1. Accedere con un amministratore che può modificare un corso pubblicato e visibile nel catalogo pubblico. Nella modifica deve comparire **Anteprima email di annuncio**; verificare oggetto, HTML, testo semplice, immagine e lezioni pubblicamente visibili. L'anteprima non deve accodare email né mostrare indirizzi.
2. Verificare che il conteggio includa soltanto account attivi con email valida e consenso confermato. Bozze, corsi privati/non visibili e corsi in checkout da un altro editor non devono essere eleggibili.
3. In anteprima, inviare il modulo senza checkbox o con token CSRF alterato: la campagna non deve essere creata. Accedere con un utente privo di permesso di modifica e tentare l'URL diretto: accesso negato.
4. Selezionare la conferma esplicita e verificare una sola campagna e una riga di coda per destinatario con consenso. Ripetere la richiesta o ricaricare la pagina: non devono apparire campagne o righe duplicate; il corso non deve essere notificabile una seconda volta.
5. Configurare il mailer Joomla su una casella di prova ed eseguire il plugin Attività - JD Learn. Verificare invio in lotti, contenuto IT/EN, link del corso e link personale per revocare il consenso. Ripetere l’attività: i messaggi già inviati non devono essere rispediti.
6. Accodare un test e, prima dell'attività, revocare il consenso, bloccare l'account/cambiare email oppure rendere il corso privato o non pubblicato. Eseguire Scheduler e verificare che la riga venga marcata come saltata e nessuna email raggiunga il destinatario.
7. Simulare un errore SMTP temporaneo e verificare il retry limitato. Un errore di persistenza dopo l'accettazione SMTP non deve causare un reinvio cieco del messaggio.

La deduplicazione per corso/destinatario impedisce campagne ripetute; i controlli al dispatch usano anche la revisione temporale del consenso e i dati correnti dell'account/corso.


## MAIL-11 — riconciliazione della copertura e accettazione manuale (baseline 0.56.16-dev)

Implementazione integrata: PR #315, #317, #319, #321, #323, #325 e #327. **Tutti i test seguenti sono da eseguire e registrare sul sito di prova**; l'esito CI non li sostituisce.

1. Configurare in **Joomla → Configurazione globale → Server → Impostazioni email** un trasporto di test, senza configurare SMTP in JD Learn; verificare l'invio della conferma double opt-in e delle mail in coda tramite il medesimo trasporto.
2. Verificare opt-in inizialmente non selezionato, IT/EN, da registrazione Joomla, iscrizione al corso e Area personale; confermare il token entro 24 ore, tentare riuso e scadenza, poi revocare con link sicuro e ripetere la richiesta.
3. Personalizzare in **Sistema → Modelli email** soggetto, corpo HTML e plain text di `com_jlms.course.published`; aggiornare il componente e verificare che le modifiche non siano sovrascritte.
4. Provare anteprima con immagine assente/presente e immagini bloccate, introduzione e solo lezioni pubbliche, senza divulgare lezioni o materiali protetti; testare corso privato/non pubblicato e ACL amministrative.
5. Confermare esplicitamente l'annuncio di un nuovo corso: verificare platea eleggibile, una sola campagna per corso, deduplica, nessun invio automatico su semplice modifica/ripubblicazione e invio batch Scheduler.
6. Tra accodamento e invio revocare consenso, bloccare account, variare indirizzo email o rendere privato il corso: l'elemento deve essere saltato. Verificare minimizzazione dei dati in coda.
7. Simulare SMTP temporaneamente indisponibile; verificare retry e limite tentativi, assenza di invalidazione di registrazioni/iscrizioni o attività didattiche, e nessun doppio invio su esecuzioni ripetute. Trattare lo stato `sending` ambiguo solo con verifica degli esiti SMTP, senza presumere exactly-once.

Registrare versione Joomla/PHP, lingua, configurazione di invio, data, esito e prove. L'audit e la riconciliazione outbox sono integrati dalla PR #332; il relativo collaudo manuale resta pendente.


## IMP-09-MAIL-B / CORE-18 — audit e riconciliazione coda email (PR #332 integrata, 0.56.17-dev)

**Comportamenti da collaudare sul sito Joomla di prova**

1. Installare/aggiornare JD Learn 0.56.17-dev e aprire **JD Learn → Coda email** con un account `core.manage`. Un account senza il permesso non deve vedere la scorciatoia e deve ricevere diniego aprendo direttamente la vista.
2. Verificare filtri per stato e modello, paginazione e righe con corso e numero tentativi. Gli indirizzi devono essere mascherati; i contenuti del messaggio e l’indirizzo completo non devono essere esposti nella pagina.
3. In ambiente SMTP controllato, portare una riga in `sending` con `claimed_at` più vecchio di 15 minuti. La riga deve mostrare l’avviso; righe recenti o in altri stati devono restare in sola lettura.
4. Confrontare i log del mailer/SMTP. Se il messaggio è stato accettato/consegnato, usare **Segna come inviato**; se non è stato accettato e il retry è sicuro, usare **Accoda nuovo tentativo**. Senza nota (10–1000 caratteri) o conferma esplicita il server deve rifiutare l’operazione. Verificare che il testo avverta del rischio di duplicati.
5. Ripetere il POST senza token CSRF, con utente non autorizzato e con un ID alterato: ogni tentativo deve essere respinto. Inviare due richieste concorrenti per la stessa riga: una sola può completare; il refresh deve mostrare il nuovo stato.
6. Verificare che la scelta “inviato” imposti `sent`, data UTC e autore, cancelli email/nome/payload personali dalla coda; la scelta “retry” imposti `retry` e `available_at` immediato. Nota e azione devono essere visibili nello storico.
7. Verificare che a cinque tentativi il retry manuale non sia disponibile e che il limite venga applicato anche a richieste POST costruite a mano. Dopo la riconciliazione una riga non deve poter essere modificata una seconda volta.
8. Aggiornare da versione precedente e verificare la migrazione schema senza perdita delle righe e la compatibilità dei task Scheduler esistenti.

Le date mostrate nell’audit sono in UTC e identificano la transizione registrata, non il momento di accettazione SMTP.

**Accettazione manuale Joomla/SMTP:** da eseguire; la CI non equivale al collaudo reale. La riconciliazione “inviato” richiede evidenza nei log del mailer, mentre “retry” richiede evidenza che SMTP non abbia accettato il messaggio.


## SEC-P1 — riduzione dati nelle eccezioni SMTP (0.56.18-dev, PR #338 integrata)

**Comportamenti da collaudare sul sito Joomla di prova dopo installazione/upgrade**

1. Configurare il mailer globale Joomla su un trasporto SMTP di test e accodare un messaggio a un account fittizio con indirizzo riconoscibile, per esempio `sec-test@example.invalid`.
2. Forzare una risposta SMTP di errore che includa l'indirizzo di prova o una stringa diagnostica identificabile. Eseguire il task Scheduler e verificare che la riga resti retry/failed secondo il limite previsto.
3. Controllare la tabella `#__jlms_mail_queue`: `last_error` deve contenere solo il messaggio generico, senza testo SMTP, indirizzo, nome o dettagli del provider.
4. Aprire **JD Learn → Coda email**: la risposta della vista non deve includere il campo `last_error` né esporre payload/indirizzo completo.
5. Aggiornare da una versione precedente che contiene righe con errori storici: la migrazione deve sostituire tutti i `last_error` preesistenti con il testo generico senza modificare stato, conteggio tentativi o disponibilità.
6. Ripetere con errore temporaneo e permanente; verificare retry, limite di cinque tentativi, nessuna interruzione della registrazione/iscrizione e nessuna fuga di dati nella pagina.

**Accettazione manuale Joomla/SMTP:** pendente. CI standard 4/4 nel [run 43522457570](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570). Il pacchetto [JD-Learn-0.56.18-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43522457570/artifacts/14083597534) contiene `pkg_jlms-0.56.18-dev.zip`, SHA-256 `c59074d3bbe6a4ddb6b79714ca0d2948e04d6bff0d40316a1ac0e15f8087cbc6`. Non inserire dati reali o segreti nelle prove; usare solo account e trasporto usa-e-getta.


## IMP-09-MAIL-D / MAIL-04 — promemoria scadenza corso (candidata 0.56.19-dev)

**Comportamenti da testare sul sito Joomla di prova**

1. Aggiornare il pacchetto e aprire le opzioni di JD Learn → Notifiche email. Confermare che il promemoria sia disabilitato per impostazione predefinita; abilitarlo e impostare una soglia di 3 giorni.
2. In un corso di prova, impostare la scadenza dell'edizione a circa 48 ore e iscrivere uno studente attivo non completato con email usa-e-getta. Eseguire il task pianificato **JD Learn: send queued emails**. Atteso: un solo messaggio in outbox con corso, data/ora UTC e collegamento all'area personale.
3. Eseguire di nuovo il task: non deve essere accodata una seconda email per la stessa iscrizione e scadenza. Verificare in **JD Learn → Coda email** il template `com_jlms.course.expiring` e il corso associato.
4. Disabilitare l'override per il corso e controllare l'ereditarietà dall'impostazione globale; impostare poi l'override su disabilitato e verificare che non venga accodata alcuna email. Con l'impostazione globale disabilitata, il comportamento deve restare spento anche per i corsi in ereditarietà.
5. Accodare un promemoria, completare o sospendere l'iscrizione prima dell'invio SMTP e rilanciare il task: l'elemento non deve essere inviato e i dati personali della riga saltata devono essere rimossi. Ripristinare mailer SMTP di prova e controllare le traduzioni IT/EN del template.
6. Provare date senza scadenza, scadenza già trascorsa, corso completato, utente bloccato e soglie 1/30 giorni; nessuno deve ricevere un promemoria fuori dai criteri.

L'esecuzione del task usa solo il mailer globale Joomla; nessun SMTP autonomo è aggiunto. La verifica manuale SMTP resta da registrare.


## PR #346 — Dashboard raggruppata e template email Joomla (CORE-20 / UI-02)

- [ ] Super User: Dashboard con sezioni Didattica, Persone e valutazione, Comunicazioni, Configurazione e tutti i collegamenti permessi nell'ordine concordato; Attestati non è in prima posizione (issue #308 soltanto parzialmente risolta: resta la vista elenco).
- [ ] Utente con ACL limitate: soltanto link autorizzati e nessuna sezione priva di pulsanti; rifiuto core dei link visitati direttamente senza permessi.
- [ ] Comunicazioni → Template email: apertura di Joomla `com_mails` con `com_jlms` selezionato; nessun template estraneo visibile, filtro rimovibile; ricerca/lingua/paginazione non restano attaccate da sessioni precedenti; verifica in IT/EN.
- [ ] Verificare issue #312 separatamente: titoli e descrizioni mail template localizzati, senza considerare il problema risolto dalla sola aggiunta del collegamento.
- [ ] Desktop 13–15 pollici, smartphone, tema scuro, accessibilità tastiera e focus, stato provider espandibile, toolbar Opzioni nativa invariata.

Nessun dato di test personale nei log o screenshot. CI e test strutturali non sono accettazione manuale Joomla.

## MAIL-F — Richiesta pending (da collaudare in Joomla)

1. Creare un corso pubblicato in modalità **Approvazione**, con edizione aperta e iscrizioni consentite. Accedere come studente avente il livello di accesso previsto: deve apparire **Richiedi iscrizione**, non **Inizia**.
2. Inviare la richiesta: verificare il messaggio di ricevuta e una sola riga `#__jlms_enrollment_requests` con stato `pending`. Nessun record in `#__jlms_enrollments`, nessun accesso all'aula, nessun progresso e nessuna variazione dei gruppi Joomla.
3. Ripetere la stessa POST e un nuovo invio con chiave differente: deve restare una sola richiesta e una sola notifica in coda; nessun reinvio duplicate SMTP dopo lo Scheduler.
4. Verificare che modalità automatica/esplicita non accettino `enrollment.request`, e che la chiamata senza CSRF, con utente non autenticato/bloccato, accesso revocato, edizione chiusa, lingua errata o corso non pubblicato venga respinta senza mutazioni.
5. Impostare le preferenze globali e gli override corso su disabilitata/abilitata/eredita; verificare la coda per nuove richieste. Modificare il template in IT/EN e verificare i placeholder canonici, lingua destinatario e conservazione upgrade.
6. Forzare un fallimento dell'accodamento e verificare rollback dell'intera richiesta; riprovare dopo il ripristino. La consegna SMTP avviene in Scheduler e non deve attribuire accesso didattico.

**Non ancora implementati in questo incremento:** decisione amministrativa approva/rifiuta, promozione a iscrizione attiva, notifiche esiti e revoca distinta; non considerarli collaudati.

## MAIL-F — Decisioni amministrative pending (prove manuali pendenti)

1. Creare un corso pubblicato in modalità approvazione e due richieste pending. Come amministratore autorizzato aprire **Corsi → Iscritti**: le richieste devono essere visibili solo nel corso di appartenenza, senza duplicati; con docente non assegnato devono essere nascoste/negate.
2. Approvare la prima richiesta: una sola iscrizione `active`, versione corretta, progressi inizialmente vuoti, audit della decisione, stato richiesta `approved`, un solo evento `enrollment.approved` in coda; nessuna concessione automatica di gruppi Joomla. Verificare l'accesso all'aula con lo studente.
3. Rifiutare la seconda: stato `rejected`, nessuna iscrizione, nessun accesso didattico, evento `enrollment.rejected`; la motivazione interna non compare in oggetto, testo o HTML dell'email.
4. Inviare nuovamente lo stesso form, con la stessa revisione, poi tentare la decisione opposta. Verificare risposta di conflitto e assenza di doppi record/email.
5. Prima di approvare chiudere iscrizioni, scadere edizione, esaurire posti, cambiare versione o revocare ACL allo studente. Ogni azione deve fallire senza mutazioni né invii. Verificare capienza in due richieste concorrenti: non superare la capacità impostata.
6. Disabilitare globalmente e per corso le notifiche approval/rejection, poi verificare gli override eredita/abilita/disabilita, l'invio tramite Scheduler SMTP e il retry; confermare la conservazione di traduzioni e template personalizzati dopo upgrade.
7. Testare utenti con permessi diversi, URL di altro corso, CSRF mancante, metodo GET, motivazioni contenenti caratteri non ammessi e richieste obsolette. Verificare audit e risposte senza dati riservati.

La CI automatizzata non sostituisce questi collaudi di accettazione.

## Collaudo upgrade 0.56.20-dev (PR #373, 14 novembre 2026)

**Ambiente:** Joomla 6.2.0 di prova con una versione JD Learn precedente alla #366 (o 0.56.19-dev) e Mail Templates Joomla preesistenti, almeno uno personalizzato. Eseguire un backup ripristinabile prima dell'upgrade. Scaricare l'artefatto Actions `JD-Learn-0.56.20-dev`, **estrarlo una volta** e caricare in Joomla solo il file interno `pkg_jlms-0.56.20-dev.zip` (manifest `pkg_jlms.xml` in radice).

- [ ] Eseguire l'upgrade in **Estensioni → Installa → Installa da URL** (fornendo un URL diretto allo ZIP installabile se disponibile) oppure **Carica file pacchetto** con lo ZIP interno; verificare esito positivo e nessuna eccezione `DatabaseDriver::updateObject() ... passed by reference`.
- [ ] Aprire **Sistema → Mail Templates**, filtrare `com_jlms`: i template presenti, inclusi quelli IT/EN, devono conservare oggetto, corpo HTML/testo, mittente e preferenze personalizzate; le variabili canoniche devono essere disponibili.
- [ ] Ripetere lo stesso upgrade, verificando idempotenza e conservazione contenuti/dati iscrizioni.
- [ ] Accedere ai corsi, alle iscrizioni e a una lezione con progresso preesistente; verificare che la ripresa e il completamento funzionino.
- [ ] Verificare separatamente il manager **Campi personalizzati** su Joomla 6.2.0 per l'issue [#374](https://github.com/jeckodevelopment/jd_learn/issues/374); questa anomalia HTTP/FieldsHelper è distinta dal blocco d'installazione.

**Stato:** prove automatizzate PR #373 4/4 CI superate (Joomla 6.0.0 in CI standard); accettazione **manuale** Joomla 6.2.0 ancora da eseguire. Non chiamare il vecchio snapshot GitHub `jdlearn-dev-latest` una release aggiornata: contiene la versione 0.56.19-dev, ora superata.

## MAIL-G — Primo incremento: avviso docente assegnato (0.56.21-dev)

- [ ] Su Joomla 6.2.0 assegnare un nuovo profilo docente **collegato a un account Joomla attivo** a un corso salvato: verificare una sola voce `com_jlms.teacher.assigned` nella Coda email e il destinatario esatto.
- [ ] Assegnare un docente senza account, ripetere il salvataggio del corso o riordinare soltanto profili già assegnati: nessuna nuova mail.
- [ ] Rimuovere il docente prima del dispatch o bloccare l'account/alterare la sua email: il dispatch deve marcare la notifica `skipped` senza consegnare o rivelare dati ad altri.
- [ ] Provare disabilitazione globale e override per corso; inviare tramite SMTP a docente di prova, controllando lingua, oggetto, HTML/testo e conservazione di template personalizzati dopo upgrade.
- [ ] Verificare che l'assegnazione non attribuisca ACL di amministrazione e non includa dati di iscritti o altre persone nell'email.

Il solo successo CI non prova l'accettazione SMTP sul sito dell'utente.


## MAIL-H / #312 — Titoli e descrizioni dei Mail Templates (PR #395)

**Comportamenti da testare sul sito Joomla 6.2 (IT/EN)**

1. Con amministrazione in italiano, aprire **Sistema → Modelli email** e filtrare `com_jlms`; verificare che tutti i 14 modelli registrati abbiano titolo e descrizione localizzati, senza chiavi `com_jlms_MAIL_..._TITLE/DESC`.
2. Aprire il dettaglio dei modelli `com_jlms.enrollment.confirmed`, `com_jlms.course.completed` e `com_jlms.course.published`: titolo e descrizione localizzati anche nella modifica.
3. Ripetere elenco e dettaglio cambiando la lingua amministrativa in inglese.
4. Modificare soggetto, HTML e testo semplice di un template e creare una versione localizzata. Eseguire upgrade e verificare che le personalizzazioni rimangano inalterate e non si creino record duplicati.
5. Verificare che accessi al gestore non accodino né inviino email; controllare che la procedura di installazione/upgrade non fallisca.

**Stato:** implementazione candidata, accettazione manuale non eseguita. La CI non sostituisce questa verifica.

## MAIL-SCHED — PR #424 integrata (collaudo pendente)

Seguire gli scenari dettagliati in [MAIL_SCHEDULER_SETUP.md](MAIL_SCHEDULER_SETUP.md): installazione pulita, task disabilitato a 5 minuti, attivazione, SMTP di prova e invio singolo, fallimento e ripristino, cron Joomla, plugin disabilitato, upgrade con impostazioni personalizzate, task duplicati, disinstallazione, ACL, IT/EN e responsive. La sola CI non costituisce accettazione.

## MAIL-RECONCILE — PR #427 integrata (collaudo manuale pendente)

Riprodurre l'errore su messaggio `sending` con `claimed_at` anteriore di almeno 15 minuti. Confermare dal log SMTP o dal destinatario che non risulta consegna. Dalla Coda email scegliere la riconciliazione `retry`, inserire nota di almeno 10 caratteri e confermare: la chiamata POST non deve più dare «Impossibile riconciliare il messaggio»; verificare `retry`, `claimed_at=NULL`, autore e nota audit, quindi task manuale e una sola consegna. Verificare anche `sent` manuale per recapito accertato, soglia 15 minuti, limite 5 tentativi, CSRF, ACL e comportamento dopo upgrade Joomla. Non marcare come accettato prima del riscontro sul sito di prova.

## LES-01 / SCR-06 — trascrizione audio

1. In una bozza, creare o modificare una lezione di tipo Audio e inserire testo con paragrafi e caratteri HTML nel campo Trascrizione audio; salvare e riaprire l’editor. Il contenuto deve essere conservato.
2. Pubblicare la nuova versione e accedere come iscritto autorizzato. La trascrizione deve apparire sotto al player audio in un controllo espandibile con nome localizzato e navigabile da tastiera.
3. Verificare che testo non affidabile venga filtrato, che HTML ammesso mantenga la struttura e che una trascrizione vuota non mostri un controllo vuoto.
4. Cambiare il tipo di lezione da Audio ad altro e salvare: la trascrizione audio non deve essere resa né riutilizzata; una lezione audio nuova non deve ereditare il testo di un’altra lezione o versione.
5. Aprire la lezione con un account non iscritto o non autorizzato: non devono essere esposti né il contenuto né la trascrizione. Verificare l’assenza di modifiche a progresso, ripresa audio e completamento.


## VID-01 / VID-02 — trascrizione facoltativa delle lezioni video

1. In una bozza creare o modificare una lezione Video e compilare Trascrizione della lezione video con paragrafi e markup ammesso. Salvare, riaprire e verificare che il testo sia conservato nella versione didattica.
2. Pubblicare la versione e accedere come iscritto autorizzato: il controllo espandibile deve comparire sotto al player, con nome accessibile e traduzioni IT/EN.
3. Verificare il filtro HTML, la trascrizione vuota senza riquadro, il cambio Video → altro tipo e l’assenza di riuso tra lezioni o versioni.
4. Verificare l’accesso negato per account non iscritto/non autorizzato: nessuna trascrizione nel markup, nel payload aula o in endpoint separati.
5. Accertare che la trascrizione non cambi soglia, durata, progresso, ripresa, consenso, richieste al provider o completamento del video.
