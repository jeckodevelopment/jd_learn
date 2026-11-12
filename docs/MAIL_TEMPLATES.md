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

I modelli iniziali includono testo semplice e HTML. Il gestore Joomla consente di modificarli e creare le relative traduzioni. L'installer registra solo gli ID mancanti: non sostituisce modifiche editoriali o traduzioni esistenti quando il componente viene aggiornato.

## Perimetro e stato

La PR [#291](https://github.com/jeckodevelopment/jd_learn/pull/291), integrata in 0.56.1-dev, collega la conferma dell'iscrizione automatica e il completamento ai template mediante una coda persistente. Il task Joomla `JD Learn: send queued emails` usa il mailer globale del sito, ritenta errori transitori fino a cinque tentativi con attesa progressiva e registra l'esito. La chiave univoca impedisce di accodare due volte la stessa transizione. Se il processo si interrompe dopo la presa in carico o dopo l'accettazione SMTP ma prima della conferma DB, il messaggio resta `sending` per evitare un reinvio cieco; richiede riconciliazione operativa. Un'eccezione durante l'invio SMTP può avere esito ambiguo: SMTP non offre una garanzia exactly-once. Le preferenze per i due eventi attuali sono state aggiunte con PR #295; restano da collegare altri eventi approvati, comunicazione manuale, audit consultabile e retention. Gli attestati PDF essenziali sono nel perimetro 1.0.0 con requisiti CERT-01–08, ma l’integrazione della notifica CERT-07 è ancora da realizzare. Credentials/Open Badges, newsletter e tracking restano fuori dal perimetro.

Per il collaudo manuale vedere [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09--core-18-template-email-joomla).


## Preferenze per le notifiche attualmente collegate

La PR [#295](https://github.com/jeckodevelopment/jd_learn/pull/295), integrata in 0.56.2-dev, aggiunge due impostazioni globali in configurazione JD Learn (conferma iscrizione automatica e completamento) e override per corso con tre opzioni: usa impostazione globale, abilita, disabilita. L'override del corso prevale sul valore globale; se impostato su eredita, vale il valore del sito. La decisione è applicata quando si verifica la transizione e prima di inserire l'email in coda. CI standard 4/4 nel [run 43305020698](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698); il collaudo manuale resta pendente. Pacchetto [JD-Learn-0.56.2-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43305020698/artifacts/13983557253), SHA-256 ZIP `9923d41d5d342ac1b854090f6956b9c9fff745c2b71f939a446a6b7494849ad7`.

Queste preferenze coprono soltanto i due eventi già collegati. Non attivano eventi mancanti e non forniscono ancora impostazioni per richieste, sospensione/riattivazione o modifiche delle lezioni.

## Sospensione e riattivazione iscrizione (candidata 0.56.3-dev)

La candidata registra due nuovi template nel gestore Mail Templates Joomla e accoda le email quando un amministratore sospende o riattiva un'iscrizione. La scrittura usa la stessa transazione della modifica stato; un errore dell'outbox non deve lasciare stato e notifica disallineati. Ogni evento ha idempotency key legata alla riga di audit della transizione. Le impostazioni globali e gli override per corso seguono le preferenze introdotte in #295.

Questa estensione non copre ancora iscrizioni amministrative iniziali, richieste/approvazioni o annullamenti.

## Requisiti approvati per la 1.0.0 (12 novembre 2026)

La registrazione dei template nella PR #288 e la coda dei due eventi core nella #291 sono **solo una parte implementata**, non l'intero sistema di notifiche. Sono approvati i requisiti MAIL-01–MAIL-10 e la sequenza IMP-09-MAIL-A/B/C/D del [piano PR](STABLE_1.0.0_PR_PLAN.md#approvazione-funzionale-12-novembre-2026--mail-templates-e-notifiche-100). Copertura stimata dopo PR #295: MAIL-01 100%; MAIL-02 25%; MAIL-03/04 0%; MAIL-05 35%; MAIL-06/07/08 0%; MAIL-09 70%; MAIL-10 25%. Media dell'area: 25,5%. Le stime misurano l'implementazione, non l'accettazione Joomla.

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

