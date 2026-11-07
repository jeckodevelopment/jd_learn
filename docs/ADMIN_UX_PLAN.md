# Backend: accesso e navigazione amministrativa

Richiesta del 7 novembre 2026, **prima parte implementata nella 0.40, verificati nella CI standard42395767098**. Chiarisce CORE-20, CORE-13, UI-02 e SCR-13; non introduce un pannello parallelo al backend Joomla. Collegamento M01/M10. L’editor corsi è già stato semplificato; questo lavoro riguarda anche l’ingresso nel componente e la navigazione persistente.

## Incremento0.41.4-dev — integrato

La PR #60 (ora integrata in main) riunisce immagine/docenti nei dati corso, regole didattiche nel programma e accesso/calendario nella scheda Accesso e pubblicazione. Le opzioni avanzate restano separate. La modifica è solo di navigazione: valori, permessi e salvataggi restano invariati. CI standard e Chromium superati (run 42410200676); restano i controlli manuali su Joomla reale, menu, ruoli, mobile, tastiera e override.

## Risultato richiesto

- Rivedere la schermata iniziale: l’attuale gruppo di pulsanti Corsi/Categorie/Banca domande/Docenti non deve essere l’unico accesso alle funzioni. Rendere evidenti il percorso principale di gestione corsi e le azioni disponibili, con etichette comprensibili e spazi contenuti.
- Predisporre un menu amministrativo JD Learn nella sidebar nativa Joomla, visibile nelle viste del componente e coerente con le convenzioni del core. Accessi diretti almeno a Corsi, Categorie corsi, Banca domande e Docenti, secondo i permessi; configurazione tramite strumenti nativi e altri collegamenti solo quando le funzioni sono operative.
- Mantenere il contesto del corso per programma, materiali e iscritti: evitare voci globali che aprano pagine prive del corso necessario. Le categorie riguardano i corsi, non la gestione dei docenti.
- Uniformare titoli, toolbar, ritorno all’elenco, stato vuoto/errori e azioni principali, senza nuovi passaggi obbligatori per salvare o aggiornare un corso.

## Sequenza e accettazione

Intervento da svolgere nel completamento M01/M10, dopo i blocchi essenziali di creazione/modifica/pubblicazione e dei tre provider esterni. Non attendere il rilascio finale per rendere navigabile il backend.

Verificare il menu dopo installazione e upgrade, in italiano/inglese, con Super User e ruoli limitati: voci autorizzate e selezione attiva coerenti in tutte le viste; nessun accesso ottenuto alterando l’URL. Provare tastiera, desktop/mobile, menu Joomla compresso e principali percorsi corso→programma→lezione/materiali→ritorno. Non aggiungere collegamenti a funzionalità mancanti o richiedere categorie docente. Guide e ID/classi per override aggiornati nello stesso incremento.

## Incremento 0.40

Ingresso predefinito sull’elenco corsi e submenu dichiarato nel manifest, gestito dall’installer e dal menu nativo Joomla. Cinque destinazioni: Corsi, Categorie corsi, Banca domande, Docenti e Plugin video. Nella0.40.1, Plugin video apre la gestione nativa com_plugins filtrata su jlmsvideo e azzera i filtri precedenti; sostituisce il precedente collegamento fuorviante alla vecchia dashboard. Visibilità e azioni rispettano i permessi com_plugins, distinti dalla gestione corsi. Etichette IT/EN nei file sys per il caricamento fuori dal componente. Il core filtra per core.manage; le viste mantengono i propri controlli e il proprio scope. Nessuna concessione di permessi e nessuna voce globale per materiali/programma/iscritti.

Restano il restyling della pagina diagnostica, il collaudo visivo/ruoli/menu compresso e la selezione attiva nel contesto completo. Le prove automatiche verificano creazione/upgrade senza duplicati e destinazioni HTTP, non equivalgono al collaudo completo della sidebar.

Correzione0.40.1 verificata nella CI standard42397626415: menu filtrato e navigazione nativa, nessuna diagnostica della riproduzione dichiarata disponibile.
