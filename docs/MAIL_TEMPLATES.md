# Template email di JD Learn

JD Learn registra i modelli nel sistema nativo **Joomla → Sistema → Modelli email** e usa il mailer e la configurazione SMTP globali del sito. Non esistono credenziali SMTP separate in JD Learn.

## Modelli forniti

| ID | Evento | Variabili disponibili |
|---|---|---|
| `com_jlms.enrollment.requested` | Richiesta di iscrizione ricevuta | `{NAME}`, `{COURSE}`, `{SITENAME}` |
| `com_jlms.enrollment.confirmed` | Iscrizione confermata | `{NAME}`, `{COURSE}`, `{SITENAME}`, `{COURSE_URL}` |
| `com_jlms.course.completed` | Corso completato | `{NAME}`, `{COURSE}`, `{SITENAME}` |

I modelli iniziali includono testo semplice e HTML. Il gestore Joomla consente di modificarli e creare le relative traduzioni. L'installer registra solo gli ID mancanti: non sostituisce modifiche editoriali o traduzioni esistenti quando il componente viene aggiornato.

## Perimetro e stato

Questa integrazione prepara i modelli e il loro punto di gestione nativo. La spedizione automatica collegata agli eventi, la registrazione persistente delle consegne fallite e il retry idempotente richiesti da AC-13/AC-57 restano attività implementative separate prima della release 1.0.0. La funzionalità di attestati e i promemoria avanzati restano fuori dal primo rilascio secondo il perimetro approvato.

Per il collaudo manuale vedere [QA_TESTBOOK.md](QA_TESTBOOK.md#imp-09--core-18-template-email-joomla).
