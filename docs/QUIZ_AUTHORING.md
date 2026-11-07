# Banca domande e quiz in bozza

Questa guida descrive la banca domande e la composizione amministrativa. Pubblicazione, tentativi, autosalvataggio, timer e valutazione sono disponibili nel [flusso studente](QUIZ_RUNTIME.md). Per i limiti correnti del prodotto vedere [IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md).

## Banca domande

Aprire «Banca domande» dalla dashboard del componente. Creare le categorie con il gestore nativo Joomla, contesto `com_jlms.question`. Sono distinti dalle categorie dei corsi. La domanda ha categoria, lingua contenuto Joomla, stato attivo/inattivo, tipo, testo, opzioni, punti predefiniti e spiegazione. Il testo e la spiegazione usano l'editor e il filtro HTML Joomla; etichette opzione sono testo e vengono codificate nel layout.

Tipi disponibili: scelta singola, vero/falso, multipla. Da2a20 opzioni distinte; vero/falso richiede esattamente2. Singola e vero/falso richiedono una risposta corretta; la multipla almeno una. Omissioni e scoring del tentativo seguono la corrispondenza esatta dell'insieme, senza punti parziali. Punti positivi interi da1a10000. Il servizio genera gli identificatori mancanti e valida quelli conservati; ID duplicati, opzioni indistinguibili, configurazioni incoerenti, tipi non supportati e documenti troppo grandi sono rifiutati.

Ogni salvataggio crea una riga immutabile in `question_revisions` e aggiorna il puntatore della domanda nella stessa transazione. Revisioni concorrenti obsolete restituiscono409; non si riscrive la revisione precedente, anche se cambia soltanto lo stato o una proprietà del contenitore. Le opzioni pubbliche, la chiave di correzione e la spiegazione sono documenti distinti con schema1 e hash canonico del contenuto. Il limite complessivo della definizione è256KiB; testo/spiegazione sono limitati a64KiB ciascuno, etichetta a4096byte.

L'asset nativo `com_jlms.question.{id}` eredita dalla categoria. Creazione richiede `core.create` e `jlms.quiz.manage` sulla categoria; modifica richiede il permesso dedicato e `core.edit` oppure `core.edit.own` sulla domanda. Lo stato richiede anche `core.edit.state`. Lettura della definizione, chiave e spiegazione richiede sempre `core.manage` sul componente e `jlms.quiz.manage` sull'oggetto. L'identità e i permessi vengono ricaricati dal core. Non basta conoscere l'ID.

Sono disponibili riserva e rilascio mediante checkout/checkin nativi: la riserva è esplicita, non implicita alla semplice lettura. Un altro autore non può salvare una domanda riservata. Lo sblocco altrui richiede l'autorizzazione nativa di `com_checkin`. Un salvataggio valido libera la riserva. Non sono implementate cancellazione fisica, bulk edit, duplicazione, version comparison o Content History nativo: l'inattivazione conserva tutte le revisioni.

L'elenco usa paginazione a cursore e controlli ACL per record. Legge al massimo100 candidati e restituisce fino a20 domande autorizzate, senza totale globale delle domande altrui. Il menu categorie è nativo; ricerche/filtri avanzati e ordinamenti multipli restano da completare.

### Correttezza delle opzioni non selezionate

Le opzioni checkbox non selezionate possono essere omesse dal POST HTML oppure arrivare come valore vuoto dopo il filtro del form Joomla: entrambe le forme equivalgono a “non corretta”. Le domande richiedono comunque almeno una risposta corretta e i tipi a risposta singola/vero-falso ne ammettono esattamente una. Questo caso è coperto dalle verifiche dell’incremento0.41.1.

### Diagnostica degli errori di salvataggio

La validazione del form mostra i messaggi Joomla relativi ai campi rifiutati. Le verifiche successive distinguono testo domanda, numero/forma delle opzioni, etichette duplicate o mancanti e numero di risposte corrette. Un errore422 generico va trattato come difetto aperto finché la causa non è visibile e riproducibile.

## Composizione di un quiz

Nel programma di una versione in bozza, creare almeno un modulo e usare «Aggiungi quiz». Il quiz è una lezione ordinata, modificabile dal suo editor dedicato. Il titolo e l'obbligatorietà appartengono alla lezione; soglia, tentativi, timer e selezione appartengono alla configurazione del quiz.

| Campo | Comportamento |
|---|---|
| Tipo | Valutativo oppure esercitazione facoltativa |
| Soglia | 0–100%, massimo2decimali, memorizzata in basis points interi; se obbligatorio deve essere >0 |
| Tentativi | Default3, da1a100 oppure0 esplicito per illimitati |
| Tempo | Default0 senza limite, altrimenti minuti interi fino a1440 |
| Pausa | Minuti interi da0a10080 |
| Selezione | Tutte le domande scelte oppure estrazione positiva non superiore al pool |
| Ordine | Flag separati per mescolare domande e opzioni, acquisiti nello snapshot del tentativo |
| Feedback | Dopo superamento/esaurimento tentativi oppure mai; dopo consegna solo per esercitazioni |
| Domande | Da1a200 revisioni distinte, non due revisioni della stessa domanda; punti positivi interi per voce |

Il selettore è un campo Joomla e mostra soltanto domande autorizzate. Il selettore propone le revisioni correnti di al massimo200 candidati recenti; mantiene anche una revisione già selezionata quando è più vecchia. Ricerca remota e navigazione di banche più grandi nel selettore restano da completare. La revisione scelta è salvata esplicitamente, non sostituita automaticamente quando cambia la banca. Le opzioni private e la chiave di correzione non fanno parte del DTO dell'editor quiz.

Al salvataggio si controllano sia `jlms.quiz.manage` sul corso sia il permesso sulle domande selezionate, oltre ai diritti di modifica del corso. Categoria/domanda devono essere attive e la lingua della domanda deve essere universale o corrispondere al corso. Un'estrazione dinamica direttamente da categorie non è implementata: il pool è l'insieme esplicitamente approvato dall'autore. L’estrazione riproducibile e gli snapshot per tentativo sono applicati nel flusso studente.

Le transazioni bloccano corso, versione e lezione in questo ordine e ricontrollano revisioni/checkout. Le versioni pubblicate sono immutabili. Per cambiare modulo usare Sposta nelle Azioni della lezione del programma in bozza; l’editor del quiz mantiene il parent. Il riordino della lezione usa i controlli del programma; l'ordine delle domande è quello del sottoform nativo. I punti del pool possono differire dal valore predefinito della banca.

La rimozione di un quiz in bozza è un'azione dedicata, con token, conferma, permessi di cancellazione e controllo delle dipendenze. Rimuove lezione/configurazione/collegamenti, conserva la banca e rifiuta tentativi o dipendenze esistenti. Non viene implementata una cancellazione dello storico dello studente.

## MVC, endpoint e confini

Viste backend `questionbank` e `quiz`, FormModel, form XML e subform Joomla, QuestionTable con asset e checkout/checkin core; servizi `QuestionBankService` e `QuizAuthoringService` registrati nel container. Non sono endpoint Web Services API né viste studenti.

Task amministrativi POST: `questionbank.save`, `questionbank.checkout`, `questionbank.checkin`, `quiz.save`, `quiz.delete`. Tutti richiedono sessione Joomla, token CSRF e controlli server; task generici non previsti sono rifiutati. I form sono allowlistati e i servizi non accettano chiavi di correzione arbitrarie nella composizione di un quiz: la revisione proviene dalla banca autorizzata.

Nessuna domanda o spiegazione viene aggiunta al catalogo pubblico o al GET dello studente. La pubblicazione valida il quiz e congela il pool; il runtime applica timer, tentativi e feedback dallo snapshot autorizzato.

## Collaudo e attività successive

Test dominio sui tre formati, insiemi esatti, opzioni/ID/punti e hash. Test su Joomla/database per asset/categorie, revisioni immutabili, checkout/checkin, autorizzazioni per oggetto, composizione, estrazioni e cancellazione controllata. Test HTTP su form/subform reali, CSRF, confini delle risposte corrette, conflitti e navigazione dal programma. La CI corrente verifica upgrade e conservazione delle revisioni, dei quiz e dei tentativi.

Restano: rivalutazioni, disponibilità e anomalie complete, reporting, integrazioni privacy/audit generali, accessibilità e carico. Questo documento descrive il perimetro implementato; non sostituisce i criteri completi di accettazione.
