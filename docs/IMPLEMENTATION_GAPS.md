# Differenze e accettazioni aperte — baseline0.19

Rilevazione:1novembre2026, codice0.19 identificato in [IMPLEMENTATION_EVIDENCE.json](IMPLEMENTATION_EVIDENCE.json). Questo registro non modifica la specifica né introduce nuove funzionalità. Tutte le righe sotto sono **aperte**; i test della soluzione attuale non le chiudono automaticamente.

| ID | Requisiti | Riscontro nel codice/prove | Differenza e prossimo criterio di verifica |
|---|---|---|---|
| R-01 | ISC-01, EDGE-13, LIFE-04/05 | `ManualEnrollmentService::context` richiede il livello corrente del destinatario; la CI verifica il rifiuto. La guida della0.19 lo dichiara | EDGE-13 consente preassegnazione senza livello con avviso e senza fruizione. La0.19 implementa una politica più restrittiva. Estendere il contratto esplicitamente senza assegnare gruppi; provare preassegnazione, fruizione negata e ripresa dopo attribuzione esterna del livello; nessun aggiornamento implicito di iscrizioni esistenti |
| R-02 | PVD-01/06, CORE-23, VID-01, PBASE-03 | Registry/evento PHP presenti; `PublicationService` ammette quattro chiavi e `component/media/js/video.js` usa una mappa di quattro adapter | Installare un quinto plugin non basta ancora. Definire registro di asset/capacità e caricamento estensibile, con validazione/WAM/allowlist di sicurezza. Collaudare un quinto provider di prova senza patch al componente e senza aggirare l’autorizzazione/completamento centrale |
| R-03 | EDGE-09, AUT-05, ISC-04 | Stato/catalogo e disponibilità letti correntemente; ritiro della scheda può rendere non disponibile anche l’aula | Mancano «chiudi nuove iscrizioni» e «sospendi erogazione» come comandi distinti con motivo/anteprima. Provare la continuità degli iscritti nel primo caso e il blocco nel secondo, inclusi quiz aperti |
| R-04 | UI-02, ACC-01, REL-02 | Test frontend/ID propri; `tests/roster-http.py` riporta il duplicato nativo `guidedtours-popup-content109` nella pagina backend della CI | L’assert sui prefissi del componente non verifica tutta la pagina. Registrare il contesto/core/template e verificare l’intero documento su patch corrente e installazione target, senza modificare il core. Non dichiarare accettazione globale o WCAG sulla base di questi test |
| R-05 | SCR-06, STU-03 | `LearningService::resumeTarget` usa ultima attività valida, altrimenti prima lezione per ordinamento, senza filtro required/progress | La specifica preferisce la prima attività obbligatoria non completata. Implementare il fallback preservando versione, lingua e accessi; provare prima attività opzionale, obbligatoria già completata e programma concluso/incoerente |

## Residui maggiori già noti

Attestati, notifiche/outbox, Privacy, Finder, Tags/Fields/Content History, import/export, prerequisiti/sequenze, audio/documenti come lezioni, versionamento/migrazioni avanzate, report completi e collaudo di rilascio sono ancora incompleti o assenti. La [matrice](REQUIREMENTS_STATUS.csv) distingue l’assenza del percorso dall’esistenza di un sottoinsieme: nessuna tabella/schema o test di una funzione vicina ne dimostra il completamento.

## Regola di chiusura

Per chiudere una riga occorrono comportamento conforme, guida/contratti aggiornati, test positivi e negativi e riferimento al build e al risultato. Se coinvolge sicurezza, privacy, browser o provider, includere il relativo collaudo effettivo. Una scelta più restrittiva o una limitazione del primo incremento non riscrive implicitamente la baseline: rimane residuo finché il requisito è soddisfatto o il perimetro viene modificato esplicitamente dall’utente.
