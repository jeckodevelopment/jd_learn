## IMP-06 — consegna Nginx interna dei materiali (0.41.65-dev)

**Stato:** PR [#219](https://github.com/jeckodevelopment/jd_learn/pull/219) integrata con squash nel commit `93e7f8a0de84e759b46e6118d9f2ab3a28b5cb61`, milestone **1.0.0**; CI standard 4/4 nel [run 43006202795](https://github.com/jeckodevelopment/jd_learn/actions/runs/43006202795). Pacchetto [JD-Learn-0.41.65-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43006202795/artifacts/13847744520), ZIP Joomla SHA-256 `f0f8fa201c2b79d041cbe4a3d52fbb3f2a444a2ba37a2dc0e6fd4c1fb1c26017`. La suite `materials-http.py` include Nginx reale, accesso interno, Range, HEAD e rifiuto della conferma non valida. L'accettazione sul sito di prova resta da eseguire.

**Comportamenti da testare in Joomla**

1. In Opzioni componente, imposta la directory privata già usata dai materiali, disabilita la consegna PHP, abilita la consegna Nginx e configura `material_internal_prefix` con una location Nginx `internal` che usa `alias` verso la stessa directory.
2. Verifica prima la location diretta: `/jlms-private/<chiave>.blob` deve restituire 404 a ospiti e utenti autenticati. Conferma l'opzione solo dopo aver verificato la configurazione effettiva del server.
3. Da un corso autorizzato, scarica un allegato e apri un PDF/immagine in anteprima. Verifica byte esatti, MIME e `Content-Disposition`; prova anche una richiesta Range e una HEAD. La risposta deve non esporre l'header `X-Accel-Redirect`.
4. Revoca l'accesso all'utente e ripeti la richiesta: deve essere negata anche con lo stesso cookie/sessione. Disabilita la conferma o configura un prefisso non valido e verifica errore chiuso, senza fallback PHP o URL pubblico.
5. Ripeti i controlli con consegna PHP esplicita per assicurare che il percorso precedente resti disponibile.

Warning: truncated output (original token count: 20326)
Total output lines: 696

## IMP-07 — lifecycle sicuro delle categorie (0.41.63-dev, PR #215 integrata)

**Stato:** CI PR #215 4/4 nel [run 42978021654](https://github.com/jeckodevelopment/jd_learn/actions/runs/42978021654); eseguire la prova manuale categorie sul pacchetto aggiornato [JD-Learn-0.41.65-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43006202795/artifacts/13847744520). La CI verifica FK su corsi, percorsi, docenti e domande, rifiuto della cancellazione permanente con riferimenti e conservazione del corso durante cestinamento/ripristino.

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


## IMP-06 — lifecycle materiali, riuso e offload (0.41.65-dev)

Riferimenti: MAT-01–08, DAT-04, SCR-16, SIC-02, SECDEV-05; AC-27–31, AC-93, AC-97–103. Su Joomla 6 di prova predisporre un corso con una bozza, almeno due lezioni e un editor con permesso di gestione materiali.

1. Caricare un allegato nella prima lezione e selezionare **Collega materiale esistente** verso materiali del corso. Atteso: il nuovo collegamento compare in entrambi gli ambiti e usa lo stesso file; nessuna seconda copia viene caricata.
2. Collegare lo stesso file a una seconda lezione, poi provare a collegarlo di nuovo allo stesso ambito. Atteso: i due ambiti validi lo rendono disponibile senza duplicati locali; la ripetizione viene rifiutata con un messaggio comprensibile.
3. Rimuovere il collegamento da un ambito. Atteso: il materiale resta utilizzabile nell'altro. Sostituire il file nel collegamento originale: gli altri collegamenti continuano a riferirsi al vecchio asset immutabile.
4. Ripetere con una versione pubblicata e con un editor privo del permesso materiali; nessuna modifica/riuso deve essere consentita. Verificare che il corso pubblicato e lo storico assegnato restino invariati.
5. Eseguire l'attività Scheduler di pulizia: il blob condiviso deve restare finché un collegamento lo referenzia e diventare eleggibile solo quando tutte le associazioni sono state rimosse e sono trascorse 24 ore.

PR [#217](https://github.com/jeckodevelopment/jd_learn/pull/217) integrata nel commit `abf82a4053918f823726c84a156d295652c7b72c`; CI standard 4/4 nel [run 42983654823](https://github.com/jeckodevelopment/jd_learn/actions/runs/42983654823). La fixture Joomla eliminabile verifica il riuso tramite form amministrativo, il riferimento condiviso allo stesso asset, il rifiuto del secondo collegamento duplicato e il blocco sulle versioni pubblicate. Pacchetto aggiornato [JD-Learn-0.41.65-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43006202795/artifacts/13847744520): wrapper SHA-256 `b0aac9e71f221b5ef601579c3fe8e8789135157e748f0236f74c25bbaa1a95f2`, ZIP Joomla `f0f8fa201c2b79d041cbe4a3d52fbb3f2a444a2ba37a2dc0e6fd4c1fb1c26017`. Il wrapper va estratto una volta. Collaudo manuale Joomla ancora da eseguire.

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
