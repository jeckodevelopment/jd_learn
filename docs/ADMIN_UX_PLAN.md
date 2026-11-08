# Backend: accesso e navigazione amministrativa

Richiesta del 7 novembre 2026, **prima parte implementata nella 0.40, verificati nella CI standard42395767098**. Chiarisce CORE-20, CORE-13, UI-02 e SCR-13; non introduce un pannello parallelo al backend Joomla. Collegamento M01/M10. L’editor corsi è già stato semplificato; questo lavoro riguarda anche l’ingresso nel componente e la navigazione persistente.

## Incremento0.41.4-dev — integrato

La PR #60 (ora integrata in main) riunisce immagine/docenti nei dati corso, regole didattiche nel programma e accesso/calendario nella scheda Accesso e pubblicazione. Le opzioni avanzate restano separate. La modifica è solo di navigazione: valori, permessi e salvataggi restano invariati. CI standard e Chromium superati (run 42410200676); restano i controlli manuali su Joomla reale, menu, ruoli, mobile, tastiera e override.

## Incremento0.41.8-dev — dashboard amministrativa più chiara

La PR #68 è integrata in main nella 0.41.8-dev. La schermata iniziale mette in evidenza la gestione corsi, raccoglie i collegamenti secondari e rende espandibile lo stato dei provider. Il foglio WAM è limitato alla dashboard e mantiene lo stile del template Joomla. La CI standard è superata (run [42423237440](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440)); restano da verificare manualmente ruoli, tastiera e viewport mobile nel backend Joomla reale.

## Risultato richiesto

## Issue #105 — dashboard di navigazione amministrativa

La dashboard esistente diventa l'ingresso predefinito del componente. Presenta riquadri con icone per Corsi, Categorie corsi, Docenti, Banca domande, Campi aggiuntivi e Plugin video, usando le destinazioni Joomla già disponibili. Ogni riquadro è mostrato in base ai permessi nativi della relativa gestione. Non si inseriscono indicatori o collegamenti a statistiche, attestati e altre funzioni di fase 2. La PR [#108](https://github.com/jeckodevelopment/jd_learn/pull/108) integra il requisito e supera la CI standard; resta il collaudo Joomla manuale. La verifica di ruoli, tastiera, responsive e URL è nello [scenario AC-132 e nel testbook](QA_TESTBOOK.md#issue-105--dashboard-amministrativa).

### Regola di uniformità delle viste Joomla

Per ogni vista amministrativa, prima si individua la vista standard Joomla più vicina (ad esempio elenco articoli per i corsi, categorie native per le categorie) e se ne riusano struttura, navigazione, toolbar, filtri e convenzioni quando rappresentano correttamente i dati. Si introduce un layout specifico solo dove la vista core non copre il flusso richiesto; anche in quel caso si mantengono menu, titoli, ritorno all’elenco, stato vuoto/errori, ACL e comportamento coerenti con Joomla. Questa regola guida l’implementazione dei requisiti già nel perimetro; non aggiunge funzionalità.

### Incremento richiesto l’8 novembre — campi, docenti e banca domande

- **Campi aggiuntivi di corsi e lezioni:** dalla gestione del corso e della lezione fornire un accesso visibile alla relativa vista nativa Joomla per configurare i campi (`com_fields`), con il contesto corretto e senza aggirare i permessi Joomla.
- **Docenti:** rifare l’elenco backend seguendo una vista Joomla standard adeguata, ispirata a `com_contacts` o alla gestione articoli. Elenco e form devono essere distinti; riusare toolbar, Search Tools/filtri, ordinamento, paginazione, stati e navigazione core. La gestione docente resta senza categorie docente.
- **Banca domande:** vista elenco dedicata, separata dal form di creazione/modifica. Usare le convenzioni elenco articoli Joomla e azioni standard per creare, duplicare ed eliminare domande, con conferme e controlli d’accesso. Nessun ampliamento dei tipi di quiz o della valutazione.

**AC-129 prima implementazione in main 0.41.19-dev:** l’elenco Corsi apre `com_fields` per `com_jlms.course` e Programma apre `com_jlms.lesson`. L'issue #99 chiede di rimuovere il link fuori toolbar da Corsi e il link da Docenti, raccogliendo la gestione in una voce sidebar **Campi aggiuntivi**. La 0.41.22 integrata usa la lista nativa `com_fields`, il filtro Joomla per passare fra i contesti corso/docente/lezione, e mantiene il link contestuale alle lezioni dal Programma. Resta il collaudo manuale di navigazione, filtro e permessi.

Questi punti sono tracciati in AC-129–131 e nella [decisione requisiti RF-2026-11-08-B](REQUIREMENTS_CHANGES.md#rf-2026-11-08-b--accesso-e-viste-amministrative-native). La banca domande è inclusa come eccezione limitata di usabilità: le altre funzionalità quiz restano fase 2.

- Rivedere la schermata iniziale: l’attuale gruppo di pulsanti Corsi/Categorie/Banca domande/Docenti non deve essere l’unico accesso alle funzioni. Rendere evidenti il percorso principale di gestione corsi e le azioni disponibili, con etichette comprensibili e spazi contenuti.
- Predisporre un menu amministrativo JD Learn nella sidebar nativa Joomla, visibile nelle viste del componente e coerente con le convenzioni del core. Accessi diretti almeno a Corsi, Categorie corsi, Banca domande e Docenti, secondo i permessi; configurazione tramite strumenti nativi e altri collegamenti solo quando le funzioni sono operative.
- Mantenere il contesto del corso per programma, materiali e iscritti: evitare voci globali che aprano pagine prive del corso necessario. Le categorie riguardano i corsi, non la gestione dei docenti.
- Uniformare titoli, toolbar, ritorno all’elenco, stato vuoto/errori e azioni principali, senza nuovi passaggi obbligatori per salvare o aggiornare un corso.

## Sequenza e accettazione

Intervento da svolgere nel completamento M01/M10, dopo i blocchi essenziali di creazione/modifica/pubblicazione e dei tre provider esterni. Non attendere il rilascio finale per rendere navigabile il backend.

Verificare il menu dopo installazione e upgrade, in italiano/inglese, con Super User e ruoli limitati: voci autorizzate e selezione attiva coerenti in tutte le viste; nessun accesso ottenuto alterando l’URL. Provare tastiera, desktop/mobile, menu Joomla compresso e principali percorsi corso→programma→lezione/materiali→ritorno. Non aggiungere collegamenti a funzionalità mancanti o richiedere categorie docente. Guide e ID/classi per override aggiornati nello stesso incremento.

## Incremento 0.40

Ingresso predefinito sull’elenco corsi e submenu dichiarato nel manifest, gestito dall’installer e dal menu nativo Joomla. Cinque destinazioni: Corsi, Categorie corsi, Banca domande, Docenti e Plugin video. Nella0.40.1, Plugin video apre la gestione nativa com_plugins filtrata su jlmsvideo e azzera i filtri precedenti; sostituisce il precedente collegamento fuorviante alla vecchia dashboard. Visibilità e azioni rispettano i permessi com_plugins, distinti dalla gestione corsi. Etichette IT/EN nei file sys per il caricamento fuori dal componente. Il core filtra per core.manage; le viste mantengono i propri controlli e il proprio scope. Nessuna concessione di permessi e nessuna voce globale per materiali/programma/iscritti.

Il restyling della dashboard è stato integrato nella 0.41.8-dev (PR #68); la CI standard è passata nel run [42423237440](https://github.com/jeckodevelopment/jd_learn/actions/runs/42423237440). Restano il collaudo visivo/ruoli/menu compresso e la selezione attiva nel contesto completo. Le prove automatiche verificano creazione/upgrade senza duplicati e destinazioni HTTP, non equivalgono al collaudo completo della sidebar.

Correzione0.40.1 verificata nella CI standard42397626415: menu filtrato e navigazione nativa, nessuna diagnostica della riproduzione dichiarata disponibile.


## Incremento 0.41.9-dev integrato — disponibilità operativa R-03

Dal roster si apre una schermata operativa del corso con quattro comandi indipendenti: chiusura/apertura ammissioni e sospensione/ripresa erogazione. L’anteprima mostra iscritti e tentativi quiz aperti; motivo, conferma e storico sono richiesti. La guida installabile è [COURSE_OPERATIONS.md](../component/administrator/docs/COURSE_OPERATIONS.md). La PR #70 è integrata (merge 70f6ed7) e la CI standard è superata (run 42428496835); il collaudo manuale Joomla resta aperto. Il menu principale non cambia.
