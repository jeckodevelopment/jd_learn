# Testbook manuale Joomla — JD Learn

Registro cumulativo delle verifiche da eseguire su un **sito Joomla di prova usa e getta**. Si aggiorna a ogni PR con comportamenti runtime, aggiungendo le prove descritte nella PR e mantenendo il collegamento al requisito/scenario esistente. Non crea criteri funzionali nuovi. Un test è superato solo dopo esecuzione manuale sul sito e registrazione dell’esito qui; CI e test HTTP sono evidenze separate.

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

## Registro PR

### PR #100 — attivazione e lingue del plugin Privacy Joomla

Riferimenti: PRI-02, CORE-08; correzione del comportamento già incluso, senza introdurre nuove capacità privacy. Usare due installazioni Joomla usa e getta: una pulita e una con JD Learn già installato e `JD Learn - Privacy` intenzionalmente disabilitato.

1. Installazione pulita: installare il pacchetto completo 0.41.21-dev e aprire **Sistema → Gestisci → Plugin**. Atteso: **JD Learn - Privacy** risulta abilitato; i quattro plugin video conservano la loro procedura di configurazione e stato previsto.
2. Aprire **Utenti → Privacy → Funzionalità** con lingua amministrativa italiana. Atteso: titolo **JD Learn** e descrizioni leggibili per iscrizioni/richieste, progressi, tentativi/risposte e attestati; nessuna chiave `PLG_PRIVACY_...` visibile.
3. Cambiare la lingua amministrativa in inglese e riaprire la pagina. Atteso: gli stessi quattro elementi sono tradotti in inglese, senza chiavi grezze.
4. Sul sito già installato, disabilitare intenzionalmente il plugin e aggiornare il pacchetto. Atteso: il plugin resta disabilitato; gli aggiornamenti non sovrascrivono la scelta dell'amministratore. Riabilitarlo per i controlli successivi.
5. Con un account di prova soggetto a un impedimento di rimozione (documento attestato, job o notifica), richiedere la rimozione privacy. Atteso: il motivo è localizzato nella lingua backend e nessun dato cambia prima della risoluzione dell'impedimento.
6. Con dati privacy di prova, verificare che export e rimozione continuino a mostrare i domini tradotti e a rispettare i comportamenti di conservazione descritti nella sezione PR #92.

**Stato:** PR #100 integrata in main 0.41.20-dev; CI originale superata ma non copriva lo stato reale di attivazione né traduzione. I test PR #102 hanno trovato che il suo hook package non attivava il plugin. Il run che poi ha confermato l'attivazione è stato interrotto perché il fixture CLI non aveva inizializzato una lingua Joomla, quindi non ha verificato le stringhe. La candidata .21 usa il postflight del manifest plugin, carica `plg_privacy_jdlearn` dai percorsi espliciti e inizializza en-GB nel fixture. **Esito sito Joomla:** ricollaudo ancora da eseguire, incluse entrambe le lingue e l'upgrade con plugin intenzionalmente disabilitato.

### PR #102 — regressione installazione e traduzioni Privacy

PR di correzione e test per PRI-02/CORE-08. La suite prima di questa PR preattivava il plugin nel fixture e lo forzava nuovamente attivo subito dopo l'installazione, nascondendo il difetto. La build candidata **0.41.21-dev** sposta l'attivazione nel postflight del plugin e carica il dominio lingua esplicito; gli upgrade mantengono lo stato esistente.

1. Su un Joomla 6 usa e getta pulito installare 0.41.21-dev. Atteso: **JD Learn - Privacy** è attivo; YouTube, Vimeo, Cloudflare Stream e HTML5 non vengono attivati automaticamente.
2. Aprire **Utenti → Privacy → Funzionalità** in italiano e inglese. Atteso: titolo e quattro descrizioni tradotti, nessuna chiave tecnica.
3. Su un'installazione esistente disattivare manualmente JD Learn - Privacy e aggiornare a 0.41.21-dev. Atteso: rimane disattivato; l'upgrade rispetta la scelta dell'amministratore.
4. Con utente di prova bloccato da documento attestato/job/notifica, verificare motivo localizzato e nessuna modifica ai dati finché l'impedimento resta.
5. Ripetere export e rimozione con dati di prova e verificare domini, minimizzazione e conservazione descritti in PR #92.

**Stato:** la CI della revisione precedente ha confermato l'attivazione ma si è fermata prima dell'assert traduzioni perché il fixture non aveva una lingua inizializzata. Il fixture ora carica en-GB; candidata aggiornata per esplicitare dominio/percorso lingua. CI completa e collaudo manuale Joomla ancora da registrare.

### AC-129 — accesso ai campi aggiuntivi di corsi e lezioni (in attesa di implementazione)

Riferimento: CORE-10; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native).

1. Con un ruolo autorizzato, aprire la gestione corsi e usare il collegamento ai campi aggiuntivi: deve aprire la vista Joomla per il contesto corsi.
2. Ripetere dall’area di gestione lezioni: deve aprire il contesto campi delle lezioni, distinto da quello corsi.
3. Verificare che l’URL/destinazione non consenta di cambiare contesto per accedere a campi non autorizzati; usare un ruolo privo del permesso necessario e verificare il diniego.
4. Tornare al componente tramite la navigazione standard Joomla e verificare che non siano state create voci/campi paralleli nel componente.

**Stato:** implementato in main 0.41.19-dev; collaudo manuale ancora da eseguire.

### AC-130 — vista standard per i docenti (in attesa di implementazione)

Riferimenti: AUT-01, SCR-13; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native).

1. Aprire Docenti: deve apparire un elenco, con form di modifica/creazione distinto e accesso a quest’ultimo mediante toolbar/azione standard.
2. Provare ricerca, filtri, ordinamento, paginazione, cambio stato e ritorno all’elenco; controllare che i risultati e i totali corrispondano ai filtri.
3. Aprire e salvare un profilo esistente, quindi annullare una modifica: verificare persistenza e annullamento senza cambiare associazioni al corso.
4. Ripetere con ruoli autorizzati e non autorizzati. Verificare navigazione da tastiera e viewport mobile.
5. Controllare che nessuna categoria docente sia richiesta o esposta.

**Stato:** da implementare e collaudare; il riferimento visivo è una lista backend Joomla standard (com_contacts/articoli).

### AC-131 — elenco separato per la banca domande (in attesa di implementazione)

Riferimenti: QUI-02, SCR-13; [RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native). Questa prova riguarda l’amministrazione delle domande esistenti, non nuovi comportamenti didattici quiz.

1. Aprire Banca domande: deve mostrare l’elenco senza il form di creazione incorporato; usare il comando standard per aprire separatamente il form di nuova domanda.
2. Tornare all’elenco e aprire una domanda esistente in modifica; annullare e salvare una modifica controllando stato e feedback.
3. Duplicare una domanda: deve essere creata una nuova definizione indipendente, lasciando invariata la sorgente e le revisioni/tentativi già esistenti.
4. Eliminare una domanda senza dipendenze: richiedere conferma e verificare l’esito. Ripetere con domanda usata da quiz/revisioni o tentativi: l’azione deve essere bloccata o gestita secondo i vincoli esistenti, senza cancellazioni parziali.
5. Verificare ricerca/filtri, ordinamento, paginazione, toolbar, CSRF e diniego con ruolo non autorizzato.

**Stato:** da implementare e collaudare. Nessun nuovo tipo di domanda, regola di valutazione o flusso studente è incluso.

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
