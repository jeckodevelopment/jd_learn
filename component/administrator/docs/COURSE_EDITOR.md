# Editor del corso —0.21 (M01/a)

## Operazioni disponibili

Corsi → Nuovo/Modifica usa le schede native Joomla: Dati generali, Presentazione, Accessi e catalogo, Pubblicazione. Associazioni appare quando il Language Filter lo consente. Programma e pubblicazione rimane un collegamento evidente sopra le schede: salvare/chiudere prima di lasciare l'editor. Lo stato non si modifica come un semplice campo: la transizione valida il programma e congela la versione.

Presentazione aggiunge descrizione estesa, obiettivi, destinatari, prerequisiti dichiarativi, difficoltà (principiante/intermedio/avanzato) e durata stimata in minuti. Sono dati promozionali visibili al livello della scheda, anche se il livello lezioni manca. Non inserire informazioni o materiali riservati. Prerequisiti dichiarativi non implementa vincoli didattici; la durata è una stima, non frequenza registrata.

La descrizione usa l'editor Joomla. Filtro HTML del ruolo e successiva allowlist: p/br, liste, strong/em/b/i, h2/h3/h4, blockquote, a e span; attributi href/title/lang/dir con pulizia XSS Joomla. Niente immagini, script, iframe, stili, handler o richieste remote automatiche da questo campo. I campi informativi restano testo e sono escapati in output. Link consentiti non comportano fetch server o caricamento automatico.

Limiti espliciti server: descrizione1MiB UTF-8, altri testi65535byte UTF-8; minuti facoltativi, interi positivi rappresentabili da PHP64bit. Valori malformati, array, decimali, esponenti e overflow sono rifiutati senza troncare. Immagine/alt, docenti e metadati completi sono un incremento successivo; descrizione obbligatoria alla pubblicazione e limite breve500caratteri richiedono migrazione esplicita dei contenuti precedenti, non sono applicati retroattivamente.

## Persistenza e integrità

Campi POST interni `jform[presentation_description]`, `presentation_objectives`, `presentation_audience`, `presentation_prerequisite_text`, `presentation_difficulty`, `presentation_estimated_minutes`, tramite gli esistenti task `course.apply/save` e token. ACL native, identità corrente e revisione del corso obbligatorie. Nessun nuovo endpoint pubblico.

`CourseModel` valida e costruisce `params_json.presentation` schema1, conservando le altre chiavi; JSON grezzo, puntatori/stato e proprietà non autorizzate non sono input accettati. Un client che omette i nuovi campi conserva quelli esistenti; il form nativo invia i valori completi e permette lo svuotamento esplicito. Il salvataggio della presentazione incrementa la revisione, senza creare iscrizioni né progressi.

`CoursePresentation::snapshot` congela la presentazione salvata al momento della pubblicazione, anche se la bozza didattica era stata creata prima. `presentation_json.details` entra nell'hash strutturale; ritiro/ripubblicazione non lo riscrivono. Un corso pubblicato rimane non modificabile da questo editor: correzioni editoriali/versioni future seguono la relativa fase, non un aggiornamento silenzioso dello storico.

La scheda autorizzata legge i dettagli dal solo snapshot della versione pubblicata. Vecchi snapshot schema1 senza details restituiscono nessun nuovo blocco e non sono modificati. Nessuna nuova tabella o backfill. Liste del catalogo e verifiche di ammissione non selezionano la descrizione estesa. Resta separato il livello promozionale dal diritto di fruizione.

## Layout e collaudo

Override amministrativo `course/edit.php`: `jlms-course-tabs`, pannelli `jlms-course-general`, `jlms-course-presentation`, `jlms-course-access-settings`, `jlms-course-publishing`, `jlms-course-translations` (condizionale), oltre ai precedenti ID di aiuto/pubblicazione. Conservare form/token/revisioni e controlli nativi. Scheda frontend e ID in FRONTEND_CSS.md della repository.

`tests/joomla-course-presentation.php`: persistenza, input invalidi, politica HTML, aggiornamenti parziali/vecchi client, permessi/revisioni, snapshot, immutabilità e conservazione dopo upgrade. `tests/http-smoke.py`: form/riapertura/salva-chiudi, prima pubblicazione e resa dei dati promozionali con escaping e ID. La CI deve essere eseguita prima di attribuire risultati; non sostituisce collaudo browser/accessibilità o verifica sul sito utente.
