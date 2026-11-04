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

## Copertina e metadati —0.22 (M01/b1)

Presentazione include il campo Media Manager Joomla, testo alternativo e scelta esplicita decorativa. Metadati aggiunge titolo SEO (255caratteri) e descrizione SEO (500), facoltativi: se assente il titolo resta quello del corso; la descrizione vuota lascia il comportamento Joomla/menu esistente. Questi campi non cambiano il titolo visibile del corso o i livelli di accesso.

Il primo percorso supportato usa cartelle del plugin FileSystem–Local configurate in Joomla. Solo file pubblici PNG/JPEG/GIF/WebP/AVIF, fino a10MiB, dimensioni massime20000px e40milioni di pixel. Il riferimento nativo con suffisso `#joomlaImage://…` viene normalizzato al percorso relativo. Non vengono recuperati URL remoti; provider remoti, SVG, file mancanti, MIME discordante, traversal, percent encoding e symlink fuori dalla cartella pubblica sono rifiutati. La selezione non carica file riservati nel Media Manager né sostituisce il flusso materiali protetti. Le autorizzazioni di caricamento/selezione sono quelle native Joomla.

Se è presente una copertina informativa il testo alternativo è obbligatorio; se decorativa il valore congelato è vuoto e il layout emette `alt=""`. È possibile rimuovere la copertina svuotando il campo. Gli altri campi omessi da vecchi client restano conservati. Il testo alternativo è testo, mai HTML eseguibile; i metadati sono testo senza markup o caratteri di controllo.

Salvataggio e prima pubblicazione verificano il file; la scheda pubblica usa solo il riferimento e i metadati congelati, dopo autorizzazione del catalogo. Gli snapshot0.21 senza i nuovi campi ricevono soltanto default vuoti in lettura, senza aggiornamento del database/hash. Il file pubblico rimane gestito dal Media Manager: lo snapshot congela il riferimento, non una copia dei byte. Sostituire/cancellare quel file può quindi cambiare/rimuovere l’immagine di una versione storica; la gestione degli asset versionati resta da completare. Non eliminare file ancora utilizzati dai corsi.

Campo interno: `jform[presentation_image]`, `presentation_image_alt`, `presentation_image_decorative`, `presentation_seo_title`, `presentation_seo_description`. Stesso percorso `course.apply/save`, token, revisione e ACL dell’editor; nessun nuovo endpoint. Campo media e switcher radio nativi, tab `jlms-course-metadata`; nessun permesso amministrativo o gruppo attribuito.

Prove aggiunte a `tests/joomla-course-presentation.php` e `tests/http-smoke.py`: MIME/path/alt/SEO, escaping e head nativo, round-trip, decorazione, controlli alla pubblicazione, upgrade reale da0.21 e regressioni. Gli esiti sono registrati nella PR del build; non certificano ancora il collaudo manuale della finestra Media Manager, i provider remoti o l’intera M01. Docenti, calendario e altre opzioni restano nei blocchi successivi.

## Docenti0.23

La scheda Docenti del corso salvato usa una selezione multipla nativa. [Gestione, disponibilità e limiti](TEACHERS.md). Il collegamento conserva lo storico e non attribuisce permessi. Calendario e altri blocchi M01 ancora da completare.

## Calendario0.24

Date facoltative di pubblicazione e fruizione, con fuso esplicito, UTC e snapshot. [Guida, intervalli, ora legale e limiti](CALENDAR.md). Salvataggio disponibile soltanto in bozza; l’edizione viene inizializzata alla prima pubblicazione.

## Permessi effettivi e stato0.25

[Guida al nuovo riepilogo, alle deleghe categoriali e ai blocchi](EDITOR_PERMISSIONS.md). Il riepilogo non sostituisce il workflow e non modifica i livelli degli studenti; le opzioni sono quelle native Joomla.

La descrizione breve accetta fino a 500 caratteri. Un testo più lungo già salvato può essere mantenuto quando si aggiornano gli altri campi oppure abbreviato. In caso di alias duplicato, scegliere un alias diverso per la stessa categoria e lingua.

Per pubblicare servono il permesso di cambiare stato sul corso (anche ereditato dalla categoria) e il permesso di eseguire la transizione Joomla assegnata.

In Programma, Anteprima editoriale apre la versione selezionata. Gli editor possono sfogliare il programma e leggere il testo salvato. Video, quiz e materiali rimangono nell’aula, con i controlli di fruizione.

Prima della prima pubblicazione, compilare la descrizione estesa nella scheda Presentazione e salvare il corso. Un contenuto composto soltanto da spazi o formattazione vuota viene rifiutato. Anche le bozze create prima della 0.29 devono avere una descrizione; è sempre possibile salvarle incomplete. Le versioni già pubblicate mantengono la presentazione congelata.

Gli amministratori con core.admin sul corso possono gestirne i permessi nella scheda Permessi effettivi. Il campo Rules salva ogni modifica immediatamente tramite Joomla. Vedere EDITOR_PERMISSIONS per ereditarietà e comportamento di Annulla.
