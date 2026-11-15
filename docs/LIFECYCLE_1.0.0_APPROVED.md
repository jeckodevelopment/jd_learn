# Ciclo di vita di corsi, percorsi e docenti — requisiti approvati 1.0.0

Decisione funzionale del 15 novembre 2026. Stato: **requisiti approvati, implementazione e collaudo pendenti**. La presenza di un workflow editoriale di archiviazione in JD Learn **non** equivale al blocco della fruizione già acquisita; verificare entrambi separatamente.

## Regole generali

- Utilizzare ove possibile toolbar, filtri di stato, selezione multipla, ACL e transizioni native Joomla; distinguere Pubblicato, Non pubblicato, Archiviato (ove pertinente), Cestinato. Per i docenti non introdurre uno stato Archiviato autonomo.
- La cestinazione è reversibile; ripristinare non equivale a pubblicare o riaprire iscrizioni. Le transizioni devono essere autorizzate server-side, protette da CSRF e registrate nell'audit disponibile.
- Vietare la cancellazione fisica in presenza di utenti collegati, iscrizioni o dipendenze storiche da conservare, anche se l'account Joomla è inattivo. Un vincolo FK non sostituisce il controllo applicativo: mostrare un messaggio comprensibile e non eseguire cancellazioni a cascata.
- Ogni controllo dev'essere applicato anche agli endpoint diretti, non soltanto alla UI. Preservare ACL, accessi riservati, versioni e dati personali secondo retention approvata.

## Corsi

- **Pubblicato:** accessi e iscrizioni governati dalle regole esistenti.
- **Non pubblicato:** non visibile pubblicamente; senza nuove iscrizioni o accesso alla didattica.
- **Archiviato:** niente nuove iscrizioni, avvio/ripresa, lezioni, player o materiali, inclusi URL diretti; escluso dal catalogo attivo. Restano nella storia personale titolo/partecipazione/completamento/dati di frequenza e attestati precedentemente emessi, consultabili e scaricabili secondo autorizzazione e retention. L'archiviazione non completa né annulla iscrizioni o progressi.
- **Cestinato:** escluso dagli elenchi ordinari; nessuna nuova iscrizione o fruizione; record di frequenza, progressi e attestati preservati e protetti. Verificare compatibilità con la vista storico personale.
- **Riattivazione:** possibile da archiviato con permesso appropriato; nessuna riapertura automatica delle iscrizioni né modifica dei progressi.
- **Eliminazione definitiva:** consentita soltanto per record privi di dipendenze significative e utenti collegati; includere versioni/lezioni/iscrizioni/progressi/attestati e riferimenti esterni nel controllo, con messaggio esplicativo.

## Percorsi

Stessi stati e azioni del corso, inclusa archiviazione, storico e protezione della fruizione aggregata. Archiviare o cestinare un percorso **non** modifica né elimina i corsi che lo compongono, anche se sono condivisi con altri percorsi. Non perdere storico/attestati eventualmente collegati.

## Docenti

Consentire pubblicazione/non pubblicazione/cestinazione/ripristino, senza stato Archiviato aggiuntivo. La cessazione dell'attività non elimina l'utente Joomla né i riferimenti storici a corsi/lezioni; l'eliminazione definitiva è vietata se esistono associazioni o registrazioni da preservare.

## Piano di implementazione separabile

1. **LCM-01 (P0):** audit stato attuale, semantica unificata e tutela iscrizioni, storico, attestati; introdurre check lato server per corsi archiviati/non pubblicati/cestinati in tutti i flussi di accesso e iscrizione.
2. **LCM-02 (P1):** toolbar, filtri, cestinazione e ripristino corsi; controllo dipendenze su eliminazione fisica.
3. **LCM-03 (P1):** stessi controlli e archiviazione percorsi, senza effetti sui corsi inclusi.
4. **LCM-04 (P1):** gestione docenti e associazioni storiche; mai eliminare l'account Joomla associato.
5. **LCM-05 (gate):** test automatici/UI Joomla, ACL/CSRF, retrocompatibilità e upgrade dati, catalogo/Finder/SEO, link diretti, storico personale/attestati, documentazione e collaudi.

## Comportamenti da testare sul sito Joomla

1. Pubblicare e archiviare un corso con un iscritto incompleto e uno completato; entrambi mantengono stato/progressi e attestati, ma non aprono lezioni/materiali/player e non possono iscriversi nuovamente.
2. Verificare endpoint diretti, download protetti, catalogo, Smart Search e cache dopo l'archiviazione.
3. Ripristinare corso archiviato: storico invariato e iscrizioni non riaperte automaticamente.
4. Cestinare/ripristinare corso, percorso e docente via selezione e azioni massive, con ruoli autorizzati e non autorizzati.
5. Negare eliminazione definitiva quando esiste una dipendenza storica o utente collegato anche non più attivo; mostrare il motivo senza alterare database.
6. Archiviare percorso con corsi riutilizzati altrove: nessun cambiamento dello stato dei corsi; preservare gli eventuali attestati di percorso.
7. Cestinare docente collegato a corso o utente Joomla: mantenere identità e riferimenti storici.
8. Aggiornare un'installazione con dati preesistenti e verificare ACL, multilingua e nessuna regressione.

Non indicare accettazione finché i test su un sito Joomla reale non sono registrati in QA_TESTBOOK.md.
