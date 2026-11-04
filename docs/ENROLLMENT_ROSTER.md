# Elenco amministrativo iscritti

Dal backend **Corsi → Iscritti al corso** si apre `administrator/index.php?option=com_jlms&view=enrollments&id=ID_CORSO`. È consultazione in sola lettura: nessuna sospensione, riattivazione, proroga, iscrizione manuale o assegnazione di gruppi viene eseguita.

## Permessi e dati

Ogni lettura ricarica l’account Joomla e i diritti correnti. Servono account attivo, `core.manage` sul componente e `jlms.enrollments.manage` sul singolo asset `com_jlms.course.ID`. Non basta essere autore o visualizzare la scheda pubblica. Le eredità e i dinieghi sono quelli core. Un tutor/operatore con permesso su un corso non può scegliere un altro corso o una versione esterna modificando l’URL. Anche gli accessi al servizio fuori dal controller verificano i permessi; le cache locali ListModel non li sostituiscono.

Il servizio seleziona soltanto lo storico del corso autorizzato: nome e username nativi, ID iscrizione/versione/edizione, titolo/versione congelati, stato amministrativo, contatori e date. Niente email, password, chiave soggetto, gruppi commerciali, regole di valutazione, domande/risposte, corpi delle lezioni o URL media. Account mancanti/anonymizzati mantengono la riga storica con etichetta neutra; un account bloccato viene segnalato senza sbloccarlo.

La tabella mostra stato iscrizione e completamento separatamente: una frequenza completata può essere scaduta o sospesa. Lo stato dell’iscrizione **non è una certificazione dell’accesso effettivo**: per fruire valgono sempre i controlli runtime correnti del LMS. Non si eseguono verifiche di aula o query per studente nel riepilogo. Le date sono indicate esplicitamente in UTC; la presentazione con fuso utente rimane da completare. `started_at` rappresenta l’inizio persistente dell’attività, non il login o il primo accesso visuale.

## Filtri e paginazione

Form GET nativo Joomla con `filter[version]` (ID versione), `filter[user]` (ID account), `filter[status]` e `filter[progress]`. ID0/vuoti significano tutti, ID negativi/non interi sono rifiutati. La versione deve appartenere al corso autorizzato. Stati ammessi: requested, active, completed (compatibilità storica), suspended, expired, cancelled, rejected; avanzamento: not_started, in_progress, completed. Campi e ordinamento SQL sono fissati dal servizio, valori bindati; il filtro non accetta SQL o nomi di colonna arbitrari.

Paginazione ListModel/Pagination con massimo50 righe, ordine ID iscrizione decrescente, totale filtrato. Il form riporta a pagina0 quando applicato. Il totale comprende le righe storiche, senza filtrare sui diritti di fruizione attuali degli studenti. Nessuna cache condivisa contenente dati personali. Gli indici esistenti `ix_reports` coprono il corso/versione/stato; i test di carico e piani su dataset reali restano pendenti.

Il servizio `EnrollmentRosterService::course/query`, registrato nella DI del componente, alimenta modello, HtmlView e layout nativi. Header `private, no-store`; letture GET senza token CSRF perché non mutano dati. Anonimi negati dal servizio401 (il backend nativo può presentare il login), permesso sul corso negato403 (la revoca dell’accesso al backend può invece presentare il login nativo), versione esterna o corso inesistente autorizzato404, filtri errati400. Errori tecnici diventano messaggi generici500 senza SQL o parametri.

## Personalizzazione e collaudo

Override `html/com_jlms/enrollments/default.php` del template amministrativo. ID stabili: `jlms-admin-enrollments`, `jlms-roster-policy`, `jlms-roster-filters`, `jlms-roster-total`, `jlms-roster-table`, `jlms-roster-enrollment-{ID}`, `jlms-roster-empty`, `jlms-roster-pagination`. Stili tabella/form/responsive nativi del template Joomla; nessun CSS globale aggiunto. Gli override devono conservare escaping, etichette, intestazioni e confine del servizio.

Test su sito effimero: operatori con scope diverso, account studente/anonimo, revoca gruppo e diniego per corso su identità già caricata, totale/pagine native, filtri e input ostili, nomi HTML escapati, nessuna mutazione dello storico e conservazione al reinstall. Esiti effettivi nelle Actions della PR di il flusso descritto, non implicita attestazione di completamento dei requisiti REP-01/02 o ISC-02.

Restano da implementare ricerca per nome, selettori versione/utente ricercabili, risultati quiz/attestati nel riepilogo, dashboard aggregata, export e dettaglio studente. Ammissione singola e sospensione/riattivazione con motivo/anteprima/audit sono disponibili nei flussi collegati, non nella sola tabella in lettura. Nessuna nuova tabella o migrazione; pacchetto di sviluppo, browser/accessibilità completi, patch corrente Joomla e carico300/500 ancora da collaudare.

## Gestione stato dalla0.18

Il collegamento per iscrizione apre il flusso separato [Sospensione e riattivazione](ENROLLMENT_STATE.md), con anteprima/conferma/motivo/audit. La pagina elenco rimane in sola lettura; le operazioni si applicano esclusivamente attraverso il task POST autorizzato.

## Iscrizione manuale dalla0.19

Il link [Iscrizione manuale singola](MANUAL_ENROLLMENT.md) apre un flusso separato di selezione/anteprima/conferma con audit. L’elenco rimane in sola lettura e non assegna automaticamente utenti o gruppi.
