# Annullamento tracciato dei tentativi

Il gestore può annullare un tentativo aperto, consegnato o scaduto, conservando domande, risposte, regole e punteggio originario. L'annullamento è distinto dalla rivalutazione: esclude il tentativo dal risultato valido, senza riscrivere la correzione originale. Non è una cancellazione fisica né una riapertura.

## Gestione e conferma

Dall'editor del quiz pubblicato aprire «Tempo aggiuntivo quiz», selezionare l'iscrizione e seguire «Gestione tentativi quiz». Sono necessari `core.manage` sul componente e, sul corso, entrambi `jlms.enrollments.manage` e `jlms.assessment.correct`. I controlli nativi rispettano ereditarietà e negazioni per categoria/corso e vengono ricaricati anche sui POST diretti e sui replay. Il ruolo di autore quiz da solo non basta.

La vista amministrativa `quizreview` elenca le iscrizioni della stessa versione e i relativi tentativi, con pagine a cursore da20. Selezionare un tentativo e scegliere se restituire il tentativo consumato: il default è **No**. La scelta aggiorna un'anteprima di sola lettura, senza annullare nulla. Sono mostrati completamento della lezione, conteggio delle lezioni obbligatorie, data di completamento del corso, miglior risultato valido, consumo successivo e dipendenze bloccanti.

L'applicazione richiede una motivazione operativa, senza diagnosi o dati sanitari, e una conferma esplicita dell'impatto. Il motivo è UTF-8, su una riga, massimo500byte, senza HTML. Una modifica dello studente o di un altro gestore successiva all'anteprima rende il comando obsoleto:409, nuova anteprima richiesta. Il browser non decide il risultato del ricalcolo.

Un tentativo già annullato mostra la decisione, l'autore e la data UTC nel backend e non può ricevere un secondo annullamento differente. Nessun campo consente di cambiare retroattivamente motivo o restituzione. Un invio identico può essere ritentato in modo idempotente.

## Effetti didattici

Lo stato diventa `invalidated`; lo slot attivo viene rimosso. `counted_for_limit` diventa0 se si restituisce il tentativo, altrimenti rimane1. Il nuovo numero di tentativo è sempre maggiore di tutti quelli storici, anche degli annullati restituiti: mai riciclare un numero.

I tentativi annullati non contribuiscono a migliore risultato, superamento, completamento o pausa fra tentativi. La pausa eventualmente derivante da altri tentativi validi rimane applicabile. L'annullamento non introduce una nuova pausa propria. La restituzione non concede un nuovo tentativo quando un'altra regola lo impedisce, per esempio livello Joomla revocato o finestra didattica chiusa.

La proiezione della lezione viene ricostruita dai tentativi conclusi non annullati: migliore rapporto punti/massimo confrontato con prodotti interi, senza arrotondamenti; in parità si conserva il tentativo più recente. Una lezione valutativa rimane completata se esiste un superamento valido, un'esercitazione facoltativa se esiste una consegna valida. La data della lezione è la prima consegna ancora qualificante.

Se viene annullato l'unico superamento, si rimuove il completamento effettivo della lezione e si ricalcola il corso. Se rimane un altro superamento, il completamento rimane valido sulla sua evidenza. Il totale deriva dalle lezioni obbligatorie effettivamente completate; la data del corso è il massimo delle loro date quando sono tutte complete, altrimenti NULL. Queste sono proiezioni correggibili: i valori precedenti sono riportati nell'anteprima e nel log nativo. Non si cancellano le evidenze originali né si cambia lo stato di accesso dell'iscrizione.

Domande estratte, ordine, opzioni, risposte salvate, regole, deadline, punteggio, esito e data della consegna originaria restano invariati nel tentativo. L'annullamento di un tentativo aperto non inventa una valutazione sulle risposte parziali. Anche il worker della scadenza ignora un tentativo ormai annullato.

## Studente e feedback

La pagina studente distingue esplicitamente il tentativo annullato; un eventuale punteggio è storico e non valido. Non restituisce motivo interno, autore o dati di controllo. Le risposte corrette e le spiegazioni di un tentativo annullato rimangono nascoste, indipendentemente da esaurimento o precedenti superamenti. Gli altri tentativi seguono la politica congelata, calcolata sui soli risultati validi.

Il salvataggio, la consegna e i replay delle mutazioni dello studente verso un tentativo annullato sono rifiutati con409 dopo la verifica dell'accesso corrente. Ripetere la vecchia chiave di avvio restituisce il vecchio tentativo annullato; per iniziarne uno nuovo occorre una nuova azione esplicita e restano validi limiti e autorizzazioni. Il client rileva l'annullamento alla successiva richiesta o ricarica; non viene promesso un canale push.

Una correzione già vista prima dell'annullamento non può diventare sconosciuta allo studente. L'annullamento non rende nuovamente riservato lo stesso pool: se necessario, predisporre una nuova versione didattica con domande differenti. Nessun proctoring o garanzia di identità è introdotto.

## Limiti intenzionali

Se l'iscrizione ha attestati, oppure esistono revisioni amministrative dell'esito per lo stesso quiz/iscrizione, l'anteprima segnala il vincolo e non mostra il pulsante di applicazione. Anche un POST diretto viene bloccato con409. Il flusso descritto non modifica documenti né simula una gestione delle anomalie: occorre il futuro flusso dedicato previsto da LIFE-08/EDGE-10 e dalla rivalutazione. Il blocco include anche emissioni/documenti preparatori, per evitare transizioni non gestite.

Per una coppia quiz/iscrizione con oltre10.000tentativi il ricalcolo sincrono è rifiutato; servirà un job dedicato. Non si processa una porzione producendo risultati incompleti. La rivalutazione dei punteggi, l'invalidazione in massa, il ripristino di un annullamento, le notifiche e i flussi di revisione degli attestati non sono implementati da questa funzione.

## Servizi e task

`QuizReviewService::editor` restituisce elenco, selezione e impatto autorizzati. `cancel` blocca l'iscrizione prima dei nuovi controlli core, poi il tentativo; ricostruisce l'impatto corrente, controlla revisioni e hash, applica annullamento/proiezioni/log/ledger nella stessa transazione. L'ordine dei lock è condiviso con avvio, autosave, consegna, scadenza e gestione del tempo.

Task amministrativo `quizreview.cancel`, POST con sessione Joomla, token CSRF, form XML e limite corpo16KiB. Campi:

| Campo | Significato |
|---|---|
| `course_id`, `lesson_id`, `enrollment_id`, `attempt_id` | ID positivi; tutte le relazioni ricontrollate |
| `enrollment_revision`, `attempt_revision` | Revisioni dell'anteprima |
| `return_attempt` | Esattamente0 oppure1 |
| `impact_hash` | SHA256 canonico dell'impatto corrente, non autorizzazione autonoma |
| `mutation_id` | UUIDv4 canonico minuscolo |
| `reason` | Motivo operativo obbligatorio, massimo500byte |
| `confirmation` | Esattamente1 |

La chiave di idempotenza è vincolata ad autore e operazione; il suo hash comprende parent, revisioni, scelta, motivo e hash d'impatto. Stesso UUID con contenuto diverso:409. Il replay ricontrolla permessi e parent, restituisce la decisione originale e non ripete ricalcolo o audit, anche se nel frattempo sono stati creati altri tentativi.

Si usano campi e tabelle già presenti: nessuna migrazione aggiuntiva,38tabelle e55FK. Il ricalcolo legge al massimo100righe per batch, senza prompt, opzioni o chiavi di correzione; i record sono limitati prima della scansione. SQL bindato, escape dei layout e risposte `private, no-store`.

`AssessmentAudit` riusa il modello Action Logs Joomla nel contesto `com_jlms.quizcancel`, senza email nella transazione. Registra oggetto, autore, stato/conteggio/completamento prima e dopo, riferimento alla motivazione nel tentativo e correlazione. Non duplica motivo libero o risposte. La verifica del log usa il suo ID primario e controlla oggetto, contesto, autore e correlazione; se fallisce, l'intera operazione viene annullata. Non esiste un audit parallelo.

## Verifica e stato

La CI verifica rimborso/consumo, numerazione monotona, snapshot e risposte intatti, risultati residui, rimozione dell'ultimo completamento valido, pratica facoltativa, permessi per corso e revoche sui replay, anteprime obsolete, rollback dell'audit e blocchi attestati/revisioni. Due processi sono accodati sullo stesso lock per consegna e annullamento: una sola transizione deve riuscire. I test HTTP esercitano form Joomla, CSRF, conferma, scelta vincolata all'anteprima e stato dello studente. Reinstallazione e aggiornamento dalla0.11 conservano decisioni, evidenze e log. Gli esiti effettivi sono quelli delle Actions della PR.

Il componente completo resta in sviluppo: questa funzione non completa la rivalutazione, il lifecycle degli attestati, l'integrazione privacy generale, il collaudo della patch Joomla corrente o i benchmark con centinaia di studenti.

Le proroghe autorizzate dei tentativi ancora aperti e non scaduti sono disponibili nel flusso distinto [QUIZ_DEADLINE_EXTENSION.md](QUIZ_DEADLINE_EXTENSION.md). Non modificano lo snapshot originario né riaprono tentativi.
