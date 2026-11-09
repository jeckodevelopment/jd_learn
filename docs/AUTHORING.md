# Programma didattico

Dal backend aprire **JD Learn → Corsi → Programma**, oppure **Salva e apri programma** nell’editor corso. La scaletta mostra moduli espandibili e lezioni con tipo e obbligatorietà. Selezionare il titolo di una lezione per modificarla; usare **Modifica modulo** per il titolo del modulo. L’editor si apre solo su richiesta, uno alla volta; in un programma vuoto si apre il primo modulo. Salva riporta alla scaletta; Annulla chiude l’editor. Un errore di validazione conserva gli input della sessione nello stesso corso/versione/elemento.

Ogni modulo offre **Aggiungi lezione**, **Video**, **Documento**, **Audio** e, con permesso, **Nuovo quiz** con il modulo preselezionato. L’editor della lezione collega ai suoi materiali; per una lezione **Documento** usare almeno un allegato pronto, attivo e non di anteprima; per una lezione **Audio** caricare almeno un MP3 o OGG pronto e attivo (WAV non è ammesso); upload e scansione MP4 compaiono solo per la lezione locale selezionata. Le configurazioni dei provider sono in una sezione secondaria; il programma non carica player o richieste remote.

**Azioni** raccoglie duplica, sposta in un altro modulo, Su/Giù e, con permesso, elimina. Le azioni GET aprono soltanto il relativo form. Eliminare richiede conferma: il modulo deve essere vuoto e non si rimuovono implicitamente quiz, allegati o prerequisiti. Il quiz mantiene il proprio editor e i propri controlli. Lo spostamento è limitato alla stessa bozza e mantiene ID, sorgente, quiz e materiali, con nuova revisione dell’elemento.

Il trascinamento è disponibile nella scaletta chiusa, senza editor aperto: il ricaricamento non deve perdere input non salvati. Le maniglie si attivano solo con JS; senza JS restano i comandi nativi. Per riordinare trascinare la maniglia con le frecce sul fratello desiderato, sopra o sotto la metà della riga. Per le lezioni il trascinamento resta nel modulo; per cambiare modulo usare Sposta. Da tastiera scegliere Su/Giù in Azioni. Senza JS lo stesso collegamento apre un form di conferma POST. Il drag salva l’intera permutazione di fratelli con controllo di revisione; su successo ricarica tutte le revisioni server. Su errore ripristina l’ordine visivo precedente e invita a ricaricare, senza ritentare o sovrascrivere automaticamente.

Pubblicazione/stato e conteggi sono nel riepilogo laterale; versioni e duplicazione corso sono sezioni secondarie. Modifica programma prepara o riapre una bozza completa del programma pubblicato. Le modifiche restano nella bozza fino a Pubblica aggiornamento; gli iscritti già assegnati conservano lo storico. Vedere [pubblicazione](PUBLICATION.md), [editor](COURSE_EDITOR.md), [materiali](MATERIALS.md) e [video](VIDEO.md).

## Sicurezza e limiti

I comandi MVC richiedono POST, sessione/token Joomla, ACL correnti per corso, scope, checkout e revisioni. `curriculum.saveOrder` restituisce JSON: insieme esatto dei fratelli, transazione atomica, 409 su conflitto/storico immutabile, 403 su token/ACL, 404 fuori scope, 422 input invalido. `lesson.move` è un comando HTML con gli stessi controlli; i quiz richiedono anche jlms.quiz.manage. Le mutazioni bloccano corso e versione nello stesso ordine. Nessuna nuova API pubblica del catalogo e nessuna modifica dei gruppi o dei dati di apprendimento.

Limiti della scaletta:1000moduli e5000lezioni per versione. Il riordino non modifica prerequisiti, regole didattiche, timer o attestati. M01/AC-127 restano aperti: browser, mobile, accessibilità reale e i residui dell’editor restano nella [checklist](M01_ACCEPTANCE.md). Le prove JS con DOM/trasporto controllati non sono un collaudo visivo.

## Modifiche non salvate

Il modulo o la lezione aperti nel programma avvertono tramite conferma nativa del browser prima di abbandonare modifiche non salvate. La conferma usa la lingua e il testo del browser; richiede JavaScript e interazione utente, e non sostituisce il salvataggio. TinyMCE viene controllato tramite API pubblica anche quando la textarea non è sincronizzata. Gli altri editor usano il valore esposto dalla API Joomla. Salva valido prosegue; validazioni annullate mantengono la protezione. Nessun contenuto viene scritto in localStorage/sessionStorage.

Per gli override vedere il [contratto CSS](FRONTEND_CSS.md). Le prove e i collaudi residui sono riepilogati nello [stato attuale](IMPLEMENTATION_STATUS.md).

## Recuperare un programma storico

Dal Programma → Versioni selezionare una versione storica e usare **Recupera come nuova bozza**. Le altre bozze, la scheda e la pubblicazione corrente e i dati studente restano invariati. La nuova pubblicazione richiede il normale riepilogo/conferma e i controlli dei provider. Contratto, permessi e procedura nella [guida editor](COURSE_EDITOR.md#recuperare-un-programma-storico).

## Regole del programma

Dal Programma → Regole didattiche si modifica la soglia video nella bozza. Le regole pubblicate rimangono leggibili e immutabili; il normale riepilogo/conferma governa la nuova pubblicazione. La scheda corso offre anche il salvataggio prima dell’apertura dell’editor. Procedura e limiti nella [guida editor](COURSE_EDITOR.md#regole-didattiche-e-soglia-video).
