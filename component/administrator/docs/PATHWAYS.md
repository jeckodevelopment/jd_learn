# Percorsi formativi

I percorsi raggruppano corsi in un ordine editoriale e mostrano la scheda pubblica con l'avanzamento personale dello studente. Non iscrivono ai corsi e non concedono accesso: ogni corso continua a usare categoria di accesso, stato e iscrizione propri.

## Dove si gestiscono

Apri **Componenti → JD Learn → Percorsi formativi** oppure la scorciatoia **Percorsi formativi** nella dashboard JD Learn. Entrambe richiedono `core.manage` e `jlms.pathways.manage` su JD Learn. Le categorie dei percorsi richiedono inoltre i permessi nativi di Joomla per `com_categories` e `com_jlms.pathway`.

## Creare un percorso

1. Seleziona **Nuovo** e inserisci titolo, categoria, lingua, livello di accesso e stato.
2. Inserisci una descrizione introduttiva facoltativa.
3. Nella sezione **Corsi necessari del percorso**, aggiungi una riga per ogni corso e usa il riordino del campo ripetibile per definire la sequenza mostrata.
4. Salva. Per modificare un percorso esistente, aprilo dall'elenco; la categoria è bloccata dopo la creazione per mantenere coerenti gli asset ACL Joomla.
5. Pubblica il percorso quando la sua scheda pubblica è pronta. Il corso deve essere a sua volta pubblicato e accessibile perché lo studente possa aprirlo.

Per verificare il risultato, apri la vista pubblica dei percorsi e la scheda del percorso con un account studente. Controlla ordine, descrizione, lingua/accesso e progressi. Un corso non visibile resta presentato senza rivelare i suoi identificativi. Prova inoltre che aggiungere un corso al percorso non crea un'iscrizione e non cambia i diritti d'accesso.

## Collaudo amministrativo

Su Joomla di prova, con un account autorizzato, apri l'elenco, crea un percorso con almeno due corsi e riordina le righe; salva, modifica e pubblica. Con un account senza `jlms.pathways.manage`, verifica che la voce laterale e la scorciatoia non appaiano e che l'URL diretto sia negato. Ripeti con un account che ha il permesso dedicato ma senza `core.create`: l'elenco è accessibile ma il comando **Nuovo** non deve apparire. Verifica le etichette in italiano e inglese.

## Archiviazione e ripristino

Nell'editor del percorso un amministratore con `core.edit.state` può scegliere **Archiviato** e salvare. Il percorso archiviato non è visibile nel catalogo o nella pagina pubblica diretta (404), ma **non viene eliminato**: categorie, associazioni ai corsi, progressi e iscrizioni ai singoli corsi restano inalterati. L'archiviazione del percorso non archivia automaticamente i corsi associati.

Per riattivarlo impostare prima **Non pubblicato**, verificare contenuti e ordine, quindi selezionare **Pubblicato** con una seconda azione esplicita. Lo stato Archiviato è mostrato nell'elenco amministrativo. La cestinazione e la cancellazione definitiva condizionata sono requisiti separati non ancora disponibili; non simulare l'eliminazione con l'archiviazione.

## Cestino dei percorsi

Nell'editor il campo Stato può essere impostato su **Cestinato** da chi dispone del permesso Joomla `core.edit.state`. Il percorso scompare dall'elenco ordinario e dal frontend ma conserva le associazioni ai corsi, iscrizioni e progressi indipendenti. Per visualizzare i percorsi cestinati usare il filtro **Cestinato** nell'elenco amministrativo. Aprire il percorso, impostare **Non pubblicato** e salvare per ripristinarlo; **Pubblicato** richiede successiva scelta esplicita. Un percorso cestinato non può essere ripubblicato direttamente con una singola richiesta, anche se inviata all'endpoint.

Questa tranche non abilita ancora eliminazione fisica né operazioni massive; nessuna cancellazione a cascata viene eseguita. Anche la configurazione degli altri corsi rimane indipendente.

## Azioni massive e cancellazione definitiva

La toolbar dell'elenco percorsi consente, con il permesso `core.edit.state`, di selezionare più percorsi e applicare Pubblica, Non pubblicare, Archivia, Cestina o Ripristina. Il server esige POST e token CSRF, controlla `core.edit` e `core.edit.state` su **ciascun** percorso e applica la transizione in una transazione unica: se un record non è autorizzato, manca o non ammette la transizione, non viene modificato nessun elemento della selezione. Un percorso cestinato deve essere prima ripristinato a Non pubblicato; non può essere pubblicato direttamente.

Nella vista Cestinati, un amministratore con `core.delete` può usare Elimina definitivamente su **un solo percorso** per volta, dopo conferma Joomla. Il server elimina soltanto un percorso effettivamente cestinato e senza corsi associati; diversamente blocca l'operazione e preserva i dati. Nessun corso, frequenza o attestato è eliminato a cascata. Controlli applicativi su altre possibili dipendenze di estensioni terze sono rimandati alla verifica di rilascio: non usare la cancellazione fisica in presenza di integrazioni esterne senza verificare i relativi riferimenti.
