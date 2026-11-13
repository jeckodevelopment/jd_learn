# Template email di JD Learn

La PR [#288](https://github.com/jeckodevelopment/jd_learn/pull/288), inclusa in 0.56.0-dev, registra i modelli nel sistema nativo **Joomla → Sistema → Modelli email** e usa il mailer e la configurazione SMTP globali del sito. Non esistono credenziali SMTP separate in JD Learn.

## Modelli forniti

| ID | Evento | Variabili disponibili |
|---|---|---|
| `com_jlms.enrollment.requested` | Richiesta di iscrizione ricevuta | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.confirmed` | Iscrizione confermata | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{COURSE_URL}` |
| `com_jlms.course.completed` | Corso completato | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.suspended` | Accesso al corso sospeso | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.reactivated` | Accesso al corso riattivato | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.certificate.available` | Attestato emesso e disponibile nell’area personale | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{CERTIFICATE_URL}` |

I modelli iniziali includono testo semplice e HTML. Il gestore Joomla consente di modificarli e creare le relative traduzioni. L'installer registra solo gli ID mancanti: non sostituisce modifiche editoriali o traduzioni esistenti quando il componente viene aggiornato.

## Perimetro e stato

La PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291), integrata in 0.56.1-dev, collega la conferma dell'iscrizione automatica e il completamento ai template mediante una coda persistente. Il task Joomla `JD Learn: send queued emails` usa il mailer globale del sito, ritenta errori transitori fino a cinque tentativi con attesa progressiva e registra l'esito. La chiave univoca impedisce di accodare due volte la stessa transizione. Se il processo si interrompe dopo la presa in carico o dopo l'accettazione SMTP ma prima della conferma DB, il messaggio resta `sending` per evitare un reinvio cieco; richiede riconciliazione operativa. Un'eccezione durante l'invio SMTP può avere esito ambiguo: SMTP non offre una garanzia exactly-once. Le preferenze globali/per corso coprono cinque eventi includendo la disponibilità dell’attestato (CERT-D); restano da collegare altri eventi approvati, comunicazione manuale, audit consultabile e retention. La notifica CERT-D entra nell’outbox solo per una nuova emissione e usa la lingua del profilo Joomla. Il task Scheduler consegna tramite MailTemplate; la posta non viene inviata durante il completamento e un errore di accodamento non annulla l’attestato. Credentials/Open Badges, newsletter e tracking restano fuori dal perimetro.

Per il collaudo manuale vedere [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09--core-18-template-email-joomla).


## Preferenze per le notifiche attualmente collegate

La PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295), integrata in 0.56.2-dev, aggiunge due impostazioni globali in configurazione JD Learn (conferma iscrizione automatica e completamento) e override per corso con tre opzioni: usa impostazione globale, abilita, disabilita. L'override del corso prevale sul valore globale; se impostato su eredita, vale il valore del sito. La decisione è applicata quando si verifica la transizione e prima di inserire l'email in coda. CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698); il collaudo manuale resta pendente. Pacchetto [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`.

Le preferenze globali e per corso coprono quattro eventi dopo #297. La PR #299 collega la conferma per l’iscrizione amministrativa singola, riusando le preferenze di conferma. Richieste/approvazioni, annullamenti, modifiche lezioni e scadenze restano non collegati.

## Sospensione, riattivazione e iscrizione amministrativa (0.56.4-dev, #297/#299)

La PR #297 registra due nuovi template nel gestore Mail Templates Joomla e accoda le email quando un amministratore sospende o riattiva un'iscrizione. La scrittura usa la stessa transazione della modifica stato; un errore dell'outbox non deve lasciare stato e notifica disallineati. Ogni evento ha idempotency key legata alla riga di audit della transizione. Le impostazioni globali e gli override per corso seguono le preferenze introdotte in #295.

La PR #299 collega anche l’iscrizione amministrativa singola al template `com_jlms.enrollment.confirmed`. La notifica entra nell’outbox nella stessa transazione dell’iscrizione e usa la chiave evento deduplicata. Un preassegnamento senza accesso corrente non invia una conferma che possa far credere allo studente di poter entrare nel corso. L’evento eredita preferenze globali/per corso dell’iscrizione. Richieste/approvazioni e annullamenti restano da collegare.

## Requisiti approvati per la 1.0.0 (12 novembre 2026)

La registrazione dei template nella PR #288 e la coda dei due eventi core nella #291 sono **solo una parte implementata**, non l'intero sistema di notifiche. Sono approvati i requisiti MAIL-01–MAIL-10 e la sequenza IMP-09-MAIL-A/B/C/D del [piano PR](STABLE_1.0.0_PR_PLAN.md#approvazione-funzionale-12-novembre-2026--mail-templates-e-notifiche-100). Copertura stimata dopo PR #297: MAIL-01 100%; MAIL-02 aggiornata al 45%; MAIL-03/04 0%; MAIL-05 45%; MAIL-06/07/08 0%; MAIL-09 70%; MAIL-10 25%. Media area aggiornata a 28,5%. Il collegamento della conferma per assegnazione amministrativa è candidato 0.56.4-dev e non è conteggiato prima del merge. Le stime misurano l'implementazione, non l'accettazione Joomla.

- **Joomla come unica fonte per i Mail Templates:** registrazione di ID `com_jlms.*` e tag nei componenti, modifica di oggetto/corpo HTML e testo, traduzioni e invio tramite mailer Joomla; nessun editor template o trasporto SMTP proprietario.
- **Modello B controllato:** regole di notifica attive/disattive globali, override per corso che eredita le impostazioni globali, nessun evento custom arbitrario. Il contenuto variabile delle comunicazioni operative manuali può essere composto dentro JD Learn e inserito in un Mail Template Joomla dedicato.
- **Eventi iscrizione:** richiesta, conferma, iscrizione manuale, sospensione, riattivazione e annullamento; **corso:** completamento; **facoltativi:** nuova lezione, modifica didattica significativa, avviso singolo per scadenza. Generare notifiche solo su cambiamento rilevante e per destinatari interessati. L'email non deve determinare il successo dell'azione didattica.
- **Comunicazione operativa manuale:** dalla scheda del corso, amministratore o docente con permesso Joomla specifico e associazione al corso, a tutti gli iscritti attivi o a destinatari selezionati all'interno del corso. Oggetto e testo redazionale, anteprima, conteggio, conferma esplicita, test autorizzato; nessuna lista arbitraria, newsletter o invii promozionali.
- **Consegna affidabile:** outbox persistente, elaborazione per piccoli lotti tramite Scheduler Joomla su hosting ordinario Apache/LiteSpeed, retry limitato con backoff, identificativo idempotente per evento e destinatario, prevenzione doppi click; log degli esiti ed errori minimizzato con retention. I permessi e i filtri sono verificati server-side.
- **Esclusioni:** invii massivi non contestuali a un corso, newsletter, segmentazioni marketing, tracking aperture/click e solleciti ricorrenti di inattività; restano fuori anche attestati e Credentials.

### Criteri di collaudo previsti (non ancora eseguiti)

1. Installazione e upgrade preservano modelli Joomla, contenuti personalizzati e varianti IT/EN; invio usa configurazione globale Joomla.
2. Iscrizione, sospensione, riattivazione, annullamento e completamento generano al massimo una notifica per effettiva transizione e destinatario, anche con richieste ripetute.
3. Opzioni globali e override del corso disattivano gli eventi attesi; una modifica editoriale ordinaria non genera una notifica.
4. Docente autorizzato può comunicare solo agli iscritti attivi dei propri corsi; URL manipolati, destinatari esterni e studenti di corsi diversi sono respinti.
5. Anteprima, conteggio e test non inviano al roster reale; il comando invia soltanto dopo conferma esplicita, con audit e gestione del doppio click.
6. Con SMTP temporaneamente indisponibile le azioni sul corso rimangono persistite; la coda registra l'errore, ritenta nei limiti e non duplica consegne già riuscite.
7. I job gestiscono centinaia di iscritti senza invio HTTP monolitico; verificare permessi e privacy dei log, pulizia/retention e comportamento multi-lingua.



## Notifica attestato (CERT-D)

Alla prima emissione del documento, JD Learn aggiunge all’outbox una sola notifica con chiave stabile legata a certificato e documento. Un replay del completamento trova l’emissione esistente e non accoda di nuovo. Il template nativo `com_jlms.certificate.available` rimane modificabile e offre link all’area personale; la consegna usa il task Joomla già esistente. L’impostazione globale è attiva per default e il corso può ereditarla, abilitarla o disabilitarla. Un errore SMTP è gestito dal worker con retry; l’emissione non dipende dall’esito di consegna. Il link porta alla dashboard autenticata e non espone il PDF.


## MAIL-11 — Annuncio disponibilità nuovo corso (approvato 13 novembre 2026)

**Stato: parzialmente implementato; MAIL-11.1 è candidata nella PR #315.** La notifica serve a presentare un corso appena pubblicato agli utenti che ne hanno fatto richiesta; non è un invio marketing generalista né una newsletter. Il piano e i criteri MAIL-11.1–11.5 sono in [STABLE_1.0.0_PR_PLAN.md](STABLE_1.0.0_PR_PLAN.md#estensione-approvata-13-novembre-2026--mail-11-nuovo-corso-disponibile).

### Template nativo Joomla

Proposto ID `com_jlms.course.published`. Il default HTML presenta in quest'ordine: immagine anteprima del corso (URL HTTPS pubblico con testo alternativo), titolo, introduzione, elenco ordinato delle lezioni pubbliche, pulsante/link verso la scheda pubblica del corso, spiegazione del motivo dell'invio e collegamento di revoca. L'amministratore può togliere o riordinare i blocchi modificando il modello in **Sistema → Modelli email** di Joomla; non introdurre editor dei modelli separato. Il plain text deve rimanere leggibile e contenere link e programma. Variabili previste, da rendere compatibili con i placeholder già usati dall'estensione:

| Placeholder | Contenuto/fallback |
|---|---|
| `{NAME}` | Nome destinatario, con fallback neutro |
| `{COURSE_TITLE}` | Titolo del corso, escapato |
| `{COURSE_IMAGE}` | Immagine HTML pubblica opzionale, URL assoluto HTTPS, alt e fallback senza immagine |
| `{COURSE_INTRO}` | Introduzione sanitizzata, con fallback descrizione sintetica |
| `{COURSE_LESSONS}` | Elenco HTML ordinato dei **soli titoli e descrizioni pubblici** delle lezioni, senza materiale protetto; fallback appropriato |
| `{COURSE_URL}` | URL pubblico completo della scheda del corso |
| `{COURSE_DURATION}` | Durata prevista se disponibile; altrimenti blocco vuoto |
| `{SITE_NAME}` | Nome configurato del sito |
| `{UNSUBSCRIBE_URL}` | Link sicuro di revoca, senza credenziali o dati identificativi nell'URL |

### Preferenze privacy e invio

- Una sola preferenza utente per `nuovi corsi`, distinta dalle notifiche operative di iscrizione, tracciata al livello utente. Default **non selezionato**.
- Offrire l'adesione in registrazione utente Joomla, iscrizione al corso e profilo personale, senza forzare il consenso né duplicare lo stato; revoca disponibile in qualunque momento.
- Richiedere double opt-in prima dell'idoneità all'invio e conservare evidenza minimizzata di opt-in/opt-out, data, origine, versione informativa e conferma; definire retention coerente con la policy del sito.
- Solo amministratori con permesso adeguato possono scegliere esplicitamente di notificare, vedere anteprima e platea eleggibile e confermare l'invio **dopo** la prima pubblicazione effettiva del corso; non inviare automaticamente alla modifica o ripubblicazione.
- Invio batch tramite outbox/Scheduler Joomla esistenti, deduplica per corso+destinatario+annuncio e controllo nuovamente dell'opt-in confermato e della visibilità del corso all'atto del recapito; non esporre gli indirizzi agli altri destinatari.
- Nessuna profilazione automatica, mailing list esterna, tracciamento apertura/click o invio a utenti non opt-in; mai includere titoli di lezioni riservate o collegamenti a contenuti privati.

**Collaudo sul sito Joomla:** confronto email HTML/testo IT/EN, rendering con immagini bloccate, corsi senza foto/intro/lezioni pubbliche, template admin preservato su upgrade, opt-in/pendente/revocato, conferma e revoca, nuovo corso vs aggiornamento, corso privato, ACL e link sicuro, SMTP indisponibile, retry, coda con centinaia di utenti, deduplicazione, variazione preferenze prima dell'invio. Non segnare come superato fino a prova eseguita.


## Comunicazione operativa per corso — MAIL-C

La PR [#313](https://github.com/jeckodevelopment/jd_learn/pull/313) integrata nella 0.56.9-dev aggiunge il template Joomla com_jlms.course.message, componibile dal roster per gli iscritti attivi selezionati. L'invio di prova è riservato all'operatore corrente; l'invio reale richiede preview e conferma, crea un job idempotente e usa il task Scheduler/outbox. Preferenze: mail_communication_enabled globale e mail_communication per corso. I destinatari e le autorizzazioni vengono rivalidati all'elaborazione. Variabili supportate: NAME, NAME_HTML, COURSE, COURSE_HTML, MESSAGE_SUBJECT, MESSAGE_BODY_TEXT, MESSAGE_BODY_HTML, SITENAME, SITENAME_HTML. MAIL-11 (notifica facoltativa nuovo corso con opt-in) è separata e resta da implementare.


### MAIL-11.1 — template annuncio nuovo corso

La candidata PR #315 registra `com_jlms.course.published` in Joomla → Sistema → Modelli email. Il default HTML include immagine opzionale, titolo, introduzione, elenco delle sole lezioni pubbliche, CTA e revoca; il plain text riporta URL e programma. Placeholders HTML (`COURSE_IMAGE_HTML`, `COURSE_TITLE_HTML`, `COURSE_INTRO_HTML`, `COURSE_LESSONS_HTML`, `SITE_NAME_HTML`) dovranno essere costruiti con escaping e URL HTTPS validati dal futuro servizio di invio. L’installer non sovrascrive personalizzazioni. Questa PR non invia il template e non abilita destinatari; opt-in/double opt-in, revoca e trigger restano implementazioni successive MAIL-D.
