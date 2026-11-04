# M01 — criteri di chiusura e prossimo incremento

Aggiornato il4novembre2026. Fonte: specifica funzionale1.3 e RF-2026-11-04. Requisiti primari M01: AUT-01, AUT-05, SCR-13. Le dipendenze M02, M03, M08 e M10 non eliminano i criteri da soddisfare. M01 **non è chiuso**: mancano ancora funzioni e collaudi, non soltanto la conferma dell’utente.

Il build0.31 è integrato con PR38 e verificato nei4ambienti della [CI41819674307](https://github.com/jeckodevelopment/jd_learn/actions/runs/41819674307). La tabella distingue le prove di quel sottoinsieme dalla copertura completa dei criteri.

| Criterio | Stato riscontrato0.31 | Residuo prima della chiusura |
|---|---|---|
| Dati generali/promozionali, descrizioni, immagine/alt/SEO, accessi, calendario | Form native, validazioni e persistenza nelle suite course-presentation/calendar/http-smoke; conferma utente sulla prima pubblicazione | Collaudo dell’editor completo e delle combinazioni previste, picker media interattivo e compatibilità degli override |
| Modificare un corso pubblicato senza ritirarlo | Salvataggio normale e aggiornamento della scheda, storico/ripristino nel modello, controlli nativi/HTTP0.31 | Confronto e ripristino tramite interfaccia Content History core, autorizzazioni/checkout e combinazioni complete di AC-119/125 |
| Aggiornare programma, lezioni, quiz e materiali | Copia completa e pubblicazione aggiornata, asset riutilizzati senza copiare risultati;34controlli editoriali nativi | Verifica completa del percorso editoriale e dei tipi di risorsa; riepilogo/conferma dell’impatto, recupero di strutture storiche e criteri M02 ancora mancanti |
| Duplicare autonomamente corso, modulo e lezione | **Da implementare**; la copia del programma per aggiornare lo stesso corso non soddisfa tutte queste azioni | Comandi propri con copie in bozza, nuovi ID, parent corretti, riferimenti quiz/media/materiali e nessuna copia di iscrizioni/risultati; AC-96 |
| Ordinare moduli/lezioni con trascinamento e alternativa tastiera | Ordinamento e parent nel servizio/form; non equivale al collaudo del trascinamento accessibile | Completare e verificare entrambe le modalità, conflitti concorrenti e persistenza senza cambiare lo storico pubblicato |
| Regole didattiche, attestato e campi Joomla nell’editor corso | Servizi di validazione e sottoinsiemi dei campi; attestati non operativi e nessun comando fittizio da considerare completo | Parti mancanti del form/servizi secondo M03/M08/M10, dipendenze esplicite, valori e pubblicazione realmente utilizzabili |
| Bozza, anteprima, revisione, pubblicazione, errori e avvisi | Bozza/pubblicazione supportate, anteprima editoriale iniziale in sola lettura | Invio in revisione e archivio completi; anteprima interattiva e livelli senza effetti; separazione errori bloccanti/avvisi e AC previsti da SCR-13 |
| Permessi e associazioni | Campo Rules e controller Joomla, checkout e lingue/associazioni verificati nei sottoinsiemi delle suite esistenti | Collaudo complessivo AUT-05/SCR-13, lingue, traduzioni con frequenze distinte, revoche e associazioni pubblicate |
| Docenti senza categorie nel flusso ordinario | Creazione/associazione, campi nativi, pagina e lista corsi, modifica propria dal sito;6controlli HTTP0.31 | AC-121–125 completi: menu creati nel gestore Joomla, SEF/lingue, tipi/gruppi/ACL dei campi, altro account/revoche, upgrade e profili preesistenti |
| Compatibilità e presentazione | Guide COURSE_EDITOR/TEACHERS/FRONTEND_CSS; build/upgrade conservano dati verificati | Browser desktop/mobile, tastiera, accessibilità e personalizzazioni indipendenti; i mockup1.2 non sostituiscono i nuovi criteri testuali |

## Prossimo incremento M01/g — duplicazioni autonome

Implementare i comandi Duplica corso, Duplica modulo e Duplica lezione separatamente da Modifica programma. Il corso copiato è una nuova identità in bozza; moduli/lezioni si copiano soltanto in una versione di lavoro autorizzata, con nuovi ID e relazioni coerenti. Conservare i contenuti richiesti e riusare gli asset immutabili tramite associazioni corrette, senza copiare iscrizioni, tentativi, avanzamenti, attestati o gruppi. Rendere espliciti destinazione, ACL e comportamento sui quiz/materiali; niente pubblicazione automatica.

Il percorso richiede comandi MVC/CSRF, controlli su sorgente e destinazione, revisione/checkout e transazioni compatibili con asset Joomla. Prove native e HTTP positive/negative e upgrade: sorgente pubblicata invariata, destinazione non autorizzata rifiutata, nessuna copia dei dati studente, conflitti e richieste modificate gestiti. La stessa copia può fornire basi tecniche al riordino, ma non ne certifica l’accessibilità.

Dopo questo incremento proseguire sulle righe residue sopra, rispettando le dipendenze. L’import CSV rimane successivo alla gestione corsi. Nessuna riga o intera fase va marcata completa soltanto perché è stata aggiunta alla documentazione.
