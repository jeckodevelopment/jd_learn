# Attestati essenziali — perimetro JD Learn 1.0.0

**Decisione approvata: 12 novembre 2026.** La prima versione stabile include l'emissione di attestati PDF di completamento del corso. Il perimetro è distinto da **JD Learn Credentials / Open Badges**, che rimane successivo alla 1.0.0. Requisiti approvati il 12 novembre 2026 per la prima stabile. CERT-A (PR #300), CERT-B (PR #302) e CERT-C (PR #304) sono integrate; l'implementazione del macroambito è stimata al 62,5%. L'accettazione Joomla resta distinta dalla presenza del codice.

## Requisiti CERT-01–CERT-08

| ID | Priorità | Requisito e accettazione |
|---|---|---|
| CERT-01 | P0 | Opzione globale e per corso per abilitare gli attestati; per default nessuna emissione su corsi non abilitati. I permessi sono applicati server-side. |
| CERT-02 | P0 | Emissione automatica di un attestato solo dopo il completamento effettivo e persistito di un corso in modalità apprendimento, secondo le regole di completamento core. Mai su salvataggio editoriale, anteprima, corso non completato o sola consultazione. |
| CERT-03 | P0 | Generazione PDF con template standard personalizzabile da amministratore: logo, intestazione, nome studente, titolo corso, data di completamento e codice attestato. Escape dei contenuti e layout utilizzabile in IT/EN. |
| CERT-04 | P0 | Codice univoco, stabile, non riutilizzato; emissione idempotente anche per eventi/richieste concorrenti. Persistenza di identità corso, studente, versione didattica e data, senza rigenerazioni spurie. |
| CERT-05 | P0 | Download autenticato dall'area personale dello studente esclusivamente dei propri attestati; ACL e protezione accesso ai PDF, senza URL pubblici non autorizzati. |
| CERT-06 | P0 | Vista amministrativa per ricerca, consultazione e download degli attestati emessi, con ACL coerenti con Joomla e limitazione dei dati visibili. |
| CERT-07 | P1 | Notifica operativa di disponibilità attestato usando i Mail Templates nativi Joomla e l'outbox approvata (MAIL-01/02/09). Il fallimento della mail non annulla il completamento né l'emissione; nessun doppio invio su replay. |
| CERT-08 | P1 | Lifecycle affidabile: upgrade non elimina attestati; rigenerazione del PDF, se necessaria per problemi tecnici, non muta codice o dati certificati; audit minimo ed error handling con protezione dei dati. Definire e collaudare backup/ripristino. |

## Confini e decisioni

- Incluso: attestato **di completamento**, per singolo corso, generato come PDF; opzione di rilascio configurabile.
- Escluso: Open Badges, firme digitali qualificate, verifica pubblica della credenziale, badge di percorso, editor grafico avanzato, regole complesse, emissione manuale arbitraria, scadenza/revoca avanzata. Tali capacità restano nel piano JD Learn Credentials.
- Il PDF è un attestato prodotto dalla piattaforma, **non** una certificazione legale o un titolo con validità riconosciuta automaticamente.
- Per eventuali ri-iscrizioni/ripetizioni definire nella PR tecnica una chiave di emissione per singola istanza di completamento, senza duplicati da retry.

## Sequenza PR approvata prima di IMP-10

| ID | Priorità | Contenuto | Requisiti | Criteri uscita |
|---|---|---|---|---|
| IMP-09-CERT-A | P0 | Modello dati, configurazioni globale/per corso, permessi e servizio di emissione idempotente collegato al completamento core | CERT-01/02/04 | Test di transizione, concorrenza, controllo modalità corso e upgrade |
| IMP-09-CERT-B | P0 | Generatore PDF e template base configurabile, storage privato, download studente | CERT-03/05/08 | PDF IT/EN e ACL autenticazione/download; nessuna fuga di dati |
| IMP-09-CERT-C | P0 | Backend elenco/dettaglio/download attestati, ACL amministrative e audit minimo | CERT-06/08 | Roster, filtri, accessi non autorizzati negati |
| IMP-09-CERT-D | P1 | Mail Template Joomla, notifica disponibilità, integrazione outbox, documentazione e test end-to-end | CERT-07, MAIL-01/02/09 | Invio singolo, retry senza duplicati, mailer non disponibile non blocca attestato |

Ogni PR di implementazione: milestone `1.0.0`, elenco requisiti, sezione **Comportamenti da testare** nel sito Joomla, aggiornamento `docs/REQUIREMENTS_STATUS.csv` e testbook, changelog, guide e pacchetto verificato dopo merge. Le PR soltanto documentali non contano nella percentuale implementativa.

## Prove di accettazione minime

1. Corsi con attestati disabilitati e abilitati; completamento reale/non completamento; anteprima e sola consultazione.
2. Ripetizione della stessa transizione e concorrenza: un solo attestato per completamento valido, codice invariato.
3. PDF con contenuti lunghi, diacritici, IT/EN, logo e dati reali corretti.
4. Download studente A consentito, studente B negato, visitatore anonimo negato; ACL backend differenziate.
5. Upgrade e backup/ripristino non perdono attestati o metadati.
6. SMTP indisponibile, retry, deduplica e preferenze comunicazioni Joomla.
7. Verificare che gli attestati non espongano URL o informazioni private tramite indicizzazione o API.

Questi scenari vanno aggiunti a `docs/QA_TESTBOOK.md`; l'esecuzione manuale non è dichiarata superata dall'esito CI.


**Aggiornamento CERT-C (13 novembre 2026, PR #304 integrata):** elenco, dettaglio e download amministrativo protetto con ACL Joomla; audit minimo per consultazione, ricerca e download. CI standard 4/4 nel [run 1031](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364); collaudo Joomla pendente. Pacchetto [JD-Learn-0.56.7-dev](https://github.com/jeckodevelopment/jd_learn/actions/runs/43421028364/artifacts/14034621388), ZIP interno SHA-256 `28c462f2eae0b795cf02574408ac9bfa830e8e4a95bd5ad634bc011700e14bc4`, wrapper `f0467b1a4e96ab784283090e48377d400fbb69f944579ffd444934cdc41175e2` (scadenza 27 novembre 2026 alle 12:00 UTC). CERT-A/B/C coprono il 62,5% stimato dei criteri attestati; prossima PR CERT-D per notifica tramite Mail Templates/outbox.
