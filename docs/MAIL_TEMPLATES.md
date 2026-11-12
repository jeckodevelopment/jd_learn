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
