# Iscrizione automatica e aula testo

Per provare il flusso: pubblicare un corso contenente solo lezioni di testo, modalità di iscrizione **automatica**, con almeno una lezione obbligatoria. Un utente Joomla con il livello contenuto corretto apre la scheda e preme **Inizia / Riprendi**. Il login usa com_users e la sessione nativa; il LMS non gestisce password, gruppi commerciali o pagamenti. Su una copia di prova il risultato è verificabile ricaricando e riprendendo con lo stesso account.

## Superficie MVC

| Azione | Metodo / campi | Risultato |
|---|---|---|
| `enrollment.start` | POST HTML, token Joomla, `jform[course_id]`, `jform[idempotency_key]` | Crea o ritrova la frequenza nell’edizione default della versione corrente; redirect interno all’aula |
| `view=classroom&id=ENROLLMENT_ID&lesson_id=LESSON_ID` | GET HTML, sessione Joomla | Programma e un solo corpo lezione, dopo controllo proprietario/parent/accesso |
| `lesson.complete` | POST HTML, token, `jform[enrollment_id]`, `jform[lesson_id]`, `jform[idempotency_key]` | Completa una lezione di testo/manuale; aggiorna contatori e conclusione; redirect interno |

Sono task HTML interni; gli endpoint JSON del catalogo dei servizi restano da implementare. Key: 32–64 caratteri alfanumerici/trattino, generata con random_bytes nelle view. Metodo errato 405, token non valido 403, ospite 401, proprietario/parent estraneo 404, vincolo di accesso/finestra 403, conflitto key/payload 409. Non sono accettati user_id, stato, punteggio o percentuali dal browser. Redirect sempre costruiti dal server.

## Controlli di accesso

Il servizio ricarica l’utente dal core e chiama `User::clearAccessRights()` per eliminare i livelli/gruppi obsoleti della sessione e le cache statiche Joomla. Verifica account abilitato/attivato, proprietario dell’iscrizione, livello contenuto corrente, catalogo/categoria/antenati/lingua/date, versione pubblicata assegnata ed edizione aperta, stato e finestre dell’iscrizione. Le stesse verifiche precedono ogni replay idempotente. Anche il catalogo aggiorna i livelli prima delle query.

In questo incremento il ritiro editoriale o l’indisponibilità del catalogo blocca anche gli iscritti esistenti; storico conservato. Non è ancora presente l’azione distinta «chiudi solo nuove iscrizioni». Il ripristino di un gruppo restituisce gli stessi progressi; Inizia non riattiva iscrizioni sospese/scadute/annullate e non concede gruppi. Modalità esplicita o approvazione rifiutata finché il flusso non sarà implementato.

## Persistenza, idempotenza e concorrenza

Subject pseudonimo univoco per user_id, risolto tramite upsert prima della transazione didattica. Avvio: lock edizione, unicità subject/edition, eventuale capienza, contatore richiesti derivato dalla versione pubblicata. Nessuna scelta di versione/edizione da parte del client. Lezioni e titoli didattici sono quelli della versione assegnata.

Completamento: primo lock sull’iscrizione, poi ledger di idempotenza; nessun lock sul corso per registrare i progressi. La stessa lezione completata da più schede conta una volta. Tutte le modifiche di progresso/contatori/data e la deduplicazione sono nello stesso commit. La key è legata a utente, azione e hash del payload: riuso con altri dati dà 409. Risposte deduplicate non contengono corpi o dati personali. Il ledger ha scadenza predisposta di 24 ore; la pulizia schedulata non è ancora distribuita, quindi le key non vengono automaticamente riutilizzate.

Percentuale = completate obbligatorie / totale obbligatorie, arrotondata per difetto; opzionali escluse. Il 100% compare con tutti i requisiti soddisfatti e data server di completamento. Lo stato di accesso resta distinto: `status=active`, `completed_at` valorizzata. Replay non modifica la data di conclusione. La prima conferma manuale valorizza `started_at`; il semplice GET non attribuisce attività, tempo o credito e non genera scritture. Il completamento manuale attesta una conferma dello studente, non la lettura o la presenza. Stati «in corso» persistenti su apertura e conteggio del tempo sono ancora da progettare nel relativo incremento di telemetria.

La lettura dell’aula usa una query di programma/progressi e un solo corpo selezionato, con limite 5.000 lezioni. HTML filtrato dal core sia nell’authoring sia con filtro anti-XSS alla consegna. Nessun content plugin viene eseguito automaticamente nel testo. View con `private, no-store` e robots noindex/nofollow; configurare Page Cache/CDN esterni per escludere l’aula.

## Collaudo e limiti

`tests/joomla-learning.php`: processo reale Joomla, utenti fixture core, ownership, parent, retry, revoca/ripristino, account bloccato, sospensione, finestre e capienza/edizione, precisione percentuali e due processi PHP sincronizzati per completamenti duplicati/distinti. `tests/learning-http.py`: login com_users, token, avvio, consultazione, completamento, revoca di gruppo dopo login mantenendo la stessa sessione, ripristino e accesso di altro utente. I risultati effettivi sono nelle Actions della PR; l’esistenza dei test non è un esito.

Dashboard personale, richiesta/approvazione/manuale/import, gestione amministrativa di sospensioni/proroghe, notifiche, privacy/actionlog/Finder, player video/materiali, quiz e attestati restano da completare. Questa build è destinata a siti effimeri di collaudo, senza dati reali. Nessuna misura di carico 300/500 è ancora dichiarata superata.
