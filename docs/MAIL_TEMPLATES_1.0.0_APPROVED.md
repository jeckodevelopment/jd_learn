> **Decisione integrativa, 14 novembre 2026:** poiché JD Learn non è ancora rilasciato, il contratto prevede un solo nome canonico per variabile, **nessun alias legacy**. Le clausole precedenti di mantenimento alias e retrocompatibilità riportate sotto sono superate da questa decisione. Le installazioni di test esistenti possono richiedere il ripristino dei template personalizzati o una migrazione editoriale; preservare comunque le modifiche amministrative salvo scelta esplicita dell'operatore.\n\n# JD Learn 1.0.0 — catalogo email, dizionario variabili e completamento notifiche

**Decisione approvata:** 14 novembre 2026. **Stato:** specifica funzionale approvata; implementazioni aggiuntive e accettazione Joomla/SMTP da eseguire. Questa specifica integra, senza sostituire, [MAIL_TEMPLATES.md](MAIL_TEMPLATES.md) e [STABLE_1.0.0_PR_PLAN.md](STABLE_1.0.0_PR_PLAN.md). Non considerare queste decisioni come codice già implementato.

## Principi vincolanti

- Usare esclusivamente i Mail Templates e il mailer del core Joomla; nessun editor/SMTP/servizio newsletter parallelo.
- Registro **centralizzato e tipizzato** delle variabili: canonical names in inglese, `UPPER_SNAKE_CASE`, documentati per template e per contesto. Non esporre indiscriminatamente informazioni di altri utenti o di corsi non autorizzati.
- Mantenere gli ID template `com_jlms.*` ma **non creare alias** per i vecchi placeholder; usare soltanto nomi canonici nei template e nei payload dei nuovi eventi. Non sovrascrivere modelli personalizzati durante l'upgrade. Verificare la risoluzione sia nell'oggetto sia nel testo/HTML.
- Escaping contestuale: valori testuali HTML escaped; frammenti HTML generati e sanitizzati dal componente; URL validati e codificati per attributi; testo semplice privo di markup. Mai HTML grezzo preso da campi utente senza sanitizzazione. Non inserire segreti, token di accesso o dati riservati in URL di anteprima.
- Le variabili mancanti hanno fallback documentati (nessun valore `{TOKEN}` lasciato nel messaggio). **Validare** i segnaposto in fase di modifica/anteprima e prima dell'accodamento/invio; errore esplicito e diagnostica senza dati personali o segreti per token sconosciuti. Le personalizzazioni di installazioni di prova basate su vecchi nomi devono essere aggiornate dall'amministratore o ripristinate consapevolmente.
- Una variabile è esposta solo se dati e permessi la rendono applicabile all'evento. Lingua del destinatario Joomla, date/timezone coerenti con configurazione del sito; anteprima e prova di invio per IT/EN.
- Preferenze globali e override per corso, autorizzazioni Joomla, invii evento idempotenti via outbox/Scheduler, retry limitati e audit minimizzato. Messaggi didattici/operativi separati dall'annuncio nuovi corsi con consenso double opt-in; nessun tracking apertura/click, marketing generico o notifiche ricorrenti di inattività.

## Dizionario canonico approvato

La seguente tabella è il **contratto richiesto**, non un elenco di variabili già implementate. La disponibilità puntuale va indicata nel catalogo amministrativo per ogni template.

| Canonical | Significato / fallback e disponibilità |
|---|---|
| `{USER_FULLNAME}` | Nome completo destinatario; fallback appellativo neutro; non esporre dati di terzi |
| `{USER_FIRSTNAME}` | Nome proprio se disponibile/ricavabile in modo affidabile, altrimenti saluto neutro (non assumere che il primo token di un nome Joomla sia sempre il nome proprio) |
| `{COURSE_TITLE}` | Titolo del corso; escape per output |
| `{COURSE_DESCRIPTION}` | Descrizione editoriale pubblicabile per l'evento; fallback vuoto o sintesi sicura |
| `{COURSE_IMAGE_HTML}` | Frammento immagine opzionale con URL pubblico HTTPS, alt e fallback senza immagine; **non** URL privati |
| `{COURSE_DURATION}` | Durata prevista, solo se calcolata/disponibile; altrimenti vuoto |
| `{COURSE_URL}` | URL assoluto alla scheda/area corso autorizzata |
| `{COURSE_START_DATE}` | Data d'inizio se configurata, altrimenti vuoto |
| `{COURSE_ENDS_AT}` | Data/ora di scadenza per eventi pertinenti |
| `{LESSON_TITLE}` | Titolo della lezione interessata, solo quando l'evento riguarda una lezione |
| `{LESSON_DESCRIPTION}` | Descrizione visibile al destinatario, altrimenti vuoto |
| `{LESSON_URL}` | Link autorizzato alla lezione, non a contenuto riservato a destinatari non ammessi |
| `{TEACHER_NAME}` | Docente pertinente; fallback neutro, senza elenco arbitrario di persone |
| `{ENROLLMENT_DATE}` | Data effettiva dell'iscrizione, ove disponibile |
| `{COMPLETION_DATE}` | Data del completamento effettivo, ove disponibile |
| `{DASHBOARD_URL}` | URL assoluto area personale autenticata |
| `{CONTACT_EMAIL}` | Contatto di supporto configurato e valido; nessun indirizzo inventato |
| `{SITE_NAME}` | Nome del sito Joomla |
| `{CERTIFICATE_URL}` | URL alla dashboard/accesso autenticato dell'attestato; nessun link diretto pubblico a PDF privato |
| `{COURSE_INTRO_TEXT}`, `{COURSE_INTRO_HTML}` | Introduzione sicura per annuncio; fallback descrizione pubblica |
| `{COURSE_LESSONS_TEXT}`, `{COURSE_LESSONS_HTML}` | Elenco delle sole lezioni pubbliche per annuncio; fallback elenco vuoto/indicazione neutra |
| `{UNSUBSCRIBE_URL}` | Revoca consenso tramite link sicuro, valido per il solo contesto MAIL-11 |
| `{CONFIRM_URL}` | Conferma double opt-in, soltanto email consenso |
| `{MESSAGE_SUBJECT}`, `{MESSAGE_BODY_TEXT}`, `{MESSAGE_BODY_HTML}` | Comunicazione operativa manuale autorizzata, sanitizzata |

Per ogni placeholder che compare in HTML distinguere il valore testuale escaped dalla versione frammento `*_HTML` generata da un renderer fidato; le varianti `*_HTML` sono segnaposto distinti e tipizzati, non alias. `{COURSE_IMAGE}` della precedente specifica è **denominazione obsoleta**, non una promessa di markup arbitrario.

### Nomi storici eliminati prima della release\n\nI segnaposto `{NAME}`, `{COURSE}`, `{SITENAME}`, `{NAME_HTML}`, `{COURSE_HTML}`, `{SITENAME_HTML}` non costituiscono alias supportati. Devono essere sostituiti rispettivamente da `{USER_FULLNAME}`, `{COURSE_TITLE}`, `{SITE_NAME}`, `{USER_FULLNAME_HTML}`, `{COURSE_TITLE_HTML}`, `{SITE_NAME_HTML}` nei template e nel codice. I dati già presenti nelle installazioni di test possono richiedere pulizia, senza promessa di compatibilità pre-release.\n\n## Eventi richiesti e destinatari

| Evento/template | Destinatari | Regola / priorità | Stato alla decisione |
|---|---|---|---|
| Richiesta iscrizione ricevuta `enrollment.requested` | Studente | Notifica contestuale, P0 | Template presente; wiring da completare |
| Iscrizione confermata `enrollment.confirmed` | Studente | Transizione effettiva, P0 | Collegato |
| Richiesta approvata/rifiutata | Studente | Distinguere esito e motivazione sicura; evitare duplicati con conferma, P0 | Da progettare/implementare |
| Iscrizione annullata/revocata | Studente | Solo transizione reale; non inviare per reset tecnico, P0 | Mancante |
| Iscrizione sospesa/riattivata | Studente | Transizione reale, P0 | Collegato |
| Completamento corso `course.completed` | Studente | Una sola notifica per completamento effettivo, P0 | Collegato |
| Attestato disponibile `certificate.available` | Studente | Nuova emissione, P0 | Collegato |
| Promemoria scadenza `course.expiring` | Studente | Opzionale, una volta, default OFF, P0 | Collegato |
| Comunicazione operativa `course.message` | Iscritti attivi selezionati | Operatore autorizzato, anteprima/test/conferma, P0 | Collegato |
| Annuncio nuovo corso `course.published` | Solo utenti con consenso confermato | Scelta esplicita admin, opt-in/revoca, P0 | Collegato |
| Conferma consenso `course.consent` | Richiedente opt-in | Link a scadenza, P0 | Collegato |
| Nuova lezione pubblicata | Iscritti interessati | Opzionale, no duplicati, evitare contenuto riservato in mail, P1 | Mancante |
| Modifica didattica significativa | Iscritti interessati | Opzionale, definire cambi significativi, no email su ogni salvataggio, P1 | Mancante |
| Richiesta iscrizione da approvare | Responsabile/amministratori autorizzati | Solo per flussi con approvazione, no divulgazione roster, P1 | Mancante |
| Docente assegnato a un corso | Docente interessato | Solo nuova assegnazione effettiva, P1 | Mancante |

Per gli eventi verso docenti/amministratori definire permessi, destinatari, opt-out dove applicabile e protezioni contro la duplicazione, senza assumere che ogni docente abbia i privilegi amministrativi.

## Esperienza amministrativa e documentazione obbligatoria

- In **Joomla → Sistema → Modelli email**, garantire titolo e descrizione tradotti (issue #312), elenco completo filtrabile `com_jlms`, aiuti per i placeholder disponibili per ogni modello, esempi e preview IT/EN; mantenere l'editor nativo.
- Pubblicare una guida nella documentazione JD Learn per ogni variabile: nome canonico, alias, tipo, contesto/template autorizzati, esempio, HTML/plain-text, fallback, privacy, origine dati e regola di formattazione. Integrare un collegamento dalla dashboard/guida amministratore.
- Allineare il registro template a filtri coda/audit e agli smoke test, evitando elenchi hard-coded divergenti.
- Aggiornare testo/HTML dei modelli default (titoli, CTA, link contestuali e tono) **senza** sovrascrivere personalizzazioni esistenti; conservare entrambe le lingue e il fallback.

## Piano incrementale proposto per la 1.0.0

| Blocco | Priorità | Deliverable |
|---|---|---|
| IMP-09-MAIL-E | P0 | Dizionario centralizzato + aliases + escaping/fallback/validator + documentazione completa + regression test |
| IMP-09-MAIL-F | P0 | Completamento esiti richieste, annullamenti/revoche, trigger/preferenze/outbox/test |
| IMP-09-MAIL-G | P1 | Notifiche facoltative lezioni/cambi significativi e docenti/amministratori, autorizzazioni e destinatari |
| IMP-09-MAIL-H | P0 | Revisione editoriale IT/EN, correzione issue #312, consistenza viste coda/template e collaudi end-to-end |

Le lettere E–H identificano **blocchi pianificati**, non PR già aperte. Assegnare le future PR alla milestone 1.0.0 e riportare ID requisiti, modifiche alla documentazione, sezione **Comportamenti da testare sul sito Joomla**, esiti CI, pacchetto installabile dopo merge e aggiornamento percentuale **solo se giustificato dall'implementazione**.

## Criteri minimi di accettazione

1. Upgrade preserva custom template e traduzioni; tutti i placeholder legacy funzionano in subject, text e HTML.
2. Variabili documentate corrispondono a quelle dichiarate e risolte runtime; variabili ignote non sono inviate letteralmente; escaping XSS, URL e fallbacks verificati.
3. Nuovi eventi notificano solo su transizioni reali, secondo preferenze, idempotenza e autorizzazioni; non si invia su modifiche ordinarie.
4. Docenti e amministratori ricevono solo messaggi dei corsi e flussi autorizzati; nessuna esposizione dati di studenti estranei.
5. Preview/test non inviano a destinatari reali; SMTP down/retry, stato ambiguo, audit e retention provati su Joomla.
6. Gestore Joomla mostra nomi/descrizioni IT/EN, template e tag disponibili; accessibilità e link da dashboard verificati.

**Avanzamento:** questa decisione documentale non incrementa la percentuale di sviluppo. La baseline storica precedente resta una stima implementativa, distinta dall'accettazione manuale.
