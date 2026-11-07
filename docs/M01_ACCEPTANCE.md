# M01 — completamento della gestione corsi

Stato al **7 novembre 2026**, incremento **0.36.0-dev**: **M01 aperto**. Requisiti primari AUT-01, AUT-05, SCR-13; le dipendenze da M02/M03/M08/M10 restano parte del lavoro richiesto.

Questa checklist distingue ciò che è già utilizzabile da implementazioni mancanti e collaudi. Non ripianificare pubblicazione, duplicazioni e riordino come funzioni assenti. Le regressioni implementate e i nuovi controlli di impatto passano nella [CI0.36](https://github.com/jeckodevelopment/jd_learn/actions/runs/42382758865); gli esiti non sostituiscono l'accettazione completa degli scenari.

| Ambito | Già disponibile | Implementazione mancante | Collaudo ancora richiesto |
|---|---|---|---|
| Elenco ed editor | Toolbar/ricerca/filtri/ordinamenti nativi, contenuto/sidebar, Salva e apri programma | Parti specifiche elencate sotto; correggere eventuali difetti emersi dal collaudo | Browser/mobile/tastiera, override e combinazioni AC-126 |
| Dati corso | Descrizioni e validazioni, copertina/alt/SEO, accessi, calendario e ACL per corso | Nessuna ulteriore funzione dichiarata assente in questo sottoinsieme | Picker Media interattivo, combinazioni di date/lingue/ACL e compatibilità degli override |
| Modifica pubblicato | Salva e Salva e chiudi, storico editoriale e ripristino nel modello | Recupero di una struttura didattica storica come nuova bozza (M02) | Confronto/ripristino dalla UI Content History, checkout e combinazioni AC-119/125 |
| Aggiornamento programma | Copia completa della versione attiva, modifica e pubblicazione per nuove iscrizioni; storici esistenti conservati; riepilogo/conferma dell’impatto (0.36, CI42382758865/PR46 superata) | Migrazioni esplicite (M02) | Tutti i tipi di risorsa/ruoli e percorso completo di aggiornamento |
| Duplicazioni | Nuovo corso in bozza, modulo con sottoalbero, lezione con destinazione; quiz/materiali/campi secondo ACL, dati studente esclusi | Nessun comando di duplicazione di base mancante | AC-96 completo, browser e combinazioni di ruoli/contenuti |
| Scaletta e riordino | Editor singolo, aggiunta contestuale, spostamento lezioni, drag atomico e Su/Giù con fallback senza JS | Nessun comando di riordino di base mancante | Drag/tastiera reali, mobile, accessibilità e conflitti nel contesto completo AC-127 |
| Modifiche non salvate | Avviso browser, controllo TinyMCE e API Joomla; salvataggio valido consentito | Nessun ulteriore meccanismo di recupero dichiarato disponibile | Editor Joomla reali e navigazione sul sito; i 12 test Chromium usano una fixture controllata |
| Regole, attestato e campi corso | Sottoinsiemi di servizi e campi; scheda Campi personalizzati nativa per i corsi, salvataggio via plugin Fields (0.35, CI42379941609/PR45 superata) | Form/servizi di regole didattiche e attestato; campi lezione, snapshot e resa pertinenti (M03/M08/M10) | Pubblicazione dei valori effettivamente supportati e tutti i criteri collegati |
| Ciclo editoriale | Bozza e pubblicazione; anteprima di struttura/testo in sola lettura | Invio in revisione, archivio completo, anteprima interattiva e dei livelli senza effetti, errori bloccanti separati dagli avvisi (M02) | Transizioni configurabili e scenari SCR-13 |
| Permessi e associazioni | Rules nativo, checkout, livelli e prime associazioni multilingua | Lifecycle completo delle associazioni pubblicate e funzioni collegate M10 | AUT-05 completo, revoche, traduzioni e frequenze distinte |
| Docenti | Associazione senza categoria nel form, campi Joomla, modifica propria, pagina e lista corsi da menu | Integrazioni residue del profilo previste dalla matrice | AC-121–125: menu/SEF/lingue, tipi/gruppi/ACL campi, altro account, revoche e profili preesistenti |

## Condizione di chiusura

M01 si chiude quando le implementazioni mancanti sono operative, tutti i criteri obbligatori della baseline hanno un esito riferito al build e il flusso completo è collaudato attraverso l'interfaccia. Nessuna nuova conferma generica dell'utente sostituisce questi controlli. L'ordine del lavoro è mantenuto solo in [NEXT_STEPS.md](NEXT_STEPS.md).
