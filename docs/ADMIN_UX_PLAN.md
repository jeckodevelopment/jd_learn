# Backend: accesso e navigazione amministrativa

Richiesta del7novembre2026, pianificata e **non ancora implementata**. Chiarisce CORE-20, CORE-13, UI-02 e SCR-13; non introduce un pannello parallelo al backend Joomla. Collegamento M01/M10. L’editor corsi è già stato semplificato; questo lavoro riguarda anche l’ingresso nel componente e la navigazione persistente.

## Risultato richiesto

- Rivedere la schermata iniziale: l’attuale gruppo di pulsanti Corsi/Categorie/Banca domande/Docenti non deve essere l’unico accesso alle funzioni. Rendere evidenti il percorso principale di gestione corsi e le azioni disponibili, con etichette comprensibili e spazi contenuti.
- Predisporre un menu amministrativo JD Learn nella sidebar nativa Joomla, visibile nelle viste del componente e coerente con le convenzioni del core. Accessi diretti almeno a Corsi, Categorie corsi, Banca domande e Docenti, secondo i permessi; configurazione tramite strumenti nativi e altri collegamenti solo quando le funzioni sono operative.
- Mantenere il contesto del corso per programma, materiali e iscritti: evitare voci globali che aprano pagine prive del corso necessario. Le categorie riguardano i corsi, non la gestione dei docenti.
- Uniformare titoli, toolbar, ritorno all’elenco, stato vuoto/errori e azioni principali, senza nuovi passaggi obbligatori per salvare o aggiornare un corso.

## Sequenza e accettazione

Intervento da svolgere nel completamento M01/M10, dopo i blocchi essenziali di creazione/modifica/pubblicazione e dei tre provider esterni. Non attendere il rilascio finale per rendere navigabile il backend.

Verificare il menu dopo installazione e upgrade, in italiano/inglese, con Super User e ruoli limitati: voci autorizzate e selezione attiva coerenti in tutte le viste; nessun accesso ottenuto alterando l’URL. Provare tastiera, desktop/mobile, menu Joomla compresso e principali percorsi corso→programma→lezione/materiali→ritorno. Non aggiungere collegamenti a funzionalità mancanti o richiedere categorie docente. Guide e ID/classi per override aggiornati nello stesso incremento.
