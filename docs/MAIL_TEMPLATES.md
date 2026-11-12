# Template email di JD Learn

La PR [#288](https://github.com/jeckodevelopment/jd_learn/pull/288), inclusa in 0.56.0-dev, registra i modelli nel sistema nativo **Joomla → Sistema → Modelli email** e usa il mailer e la configurazione SMTP globali del sito. Non esistono credenziali SMTP separate in JD Learn.

## Modelli forniti

| ID | Evento | Variabili disponibili |
|---|---|---|
| `com_jlms.enrollment.requested` | Richiesta di iscrizione ricevuta | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.confirmed` | Iscrizione confermata | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{COURSE_URL}` |
| `com_jlms.course.completed` | Corso completato | `{NAME}`, `{COURSE}`, `{SITENAME}` |

I modelli iniziali includono testo semplice e HTML. Il gestore Joomla consente di modificarli e creare le relative traduzioni. L'installer registra solo gli ID mancanti: non sostituisce modifiche editoriali o traduzioni esistenti quando il componente viene aggiornato.

## Perimetro e stato

Questa integrazione prepara i modelli e il loro punto di gestione nativo. La PR in sviluppo collega la conferma dell'iscrizione automatica e il completamento ai template mediante una coda persistente. Il task Joomla `JD Learn: send queued emails` usa il mailer globale del sito, ritenta errori transitori fino a cinque tentativi con attesa progressiva e registra l'esito. La chiave univoca dell'evento impedisce inserimenti duplicati. Se il processo si interrompe dopo la presa in carico o dopo l'accettazione SMTP ma prima della conferma DB, il messaggio resta `sending` per evitare un reinvio cieco; richiede riconciliazione operativa. Restano fuori dal collegamento corrente le richieste in approvazione, attestati e promemoria avanzati. La funzionalità di attestati e i promemoria avanzati restano fuori dal primo rilascio secondo il perimetro approvato.

Per il collaudo manuale vedere [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09--core-18-template-email-joomla).


## Requisiti approvati per la 1.0.0 (12 novembre 2026)

La registrazione iniziale dei tre template nella PR #288 è **solo la base implementata**, non l'intero sistema di notifiche. Sono approvati i requisiti MAIL-01–MAIL-10 e la sequenza IMP-09-MAIL-A/B/C/D del [piano PR](STABLE_1.0.0_PR_PLAN.md#approvazione-funzionale-12-novembre-2026--mail-templates-e-notifiche-100). La presente sezione è una specifica, non dichiara implementate le capacità ulteriori.

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

