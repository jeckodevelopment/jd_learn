# JD Learn

**Build di sviluppo documentata: `0.64.9-dev`** (non release stabile). L’audit funzionale 1.0.0 ha riesaminato tutti i 757 criteri dei 214 requisiti: 454 implementati, 35 mancanti e 268 non verificabili. La percentuale globale rigorosa resta non determinabile finché permangono esiti non verificabili. Il dashboard di progetto/release mostra separatamente sviluppo documentato e collaudo manuale; consulta il [riepilogo progetto/release](docs/RELEASE_DASHBOARD.md), i [dati del dashboard di progetto](docs/REQUIREMENTS_RECONCILIATION.json) e il [report corrente](docs/FUNCTIONAL_AUDIT_CURRENT.md) per stati, lacune ed evidenze. Pacchetti installabili e checksum vanno verificati per il commit specifico nel [registro delle evidenze](docs/IMPLEMENTATION_EVIDENCE.json); non si assume che un artefatto storico sia la build più recente.

**JD Learn** è un componente e-learning per Joomla 6 che integra corsi, lezioni, percorsi, iscrizioni e fruizione didattica nell'ecosistema nativo Joomla. È in sviluppo verso la **prima release stabile 1.0.0**: le build `-dev` sono destinate a sviluppo e collaudo, non alla produzione.

## Funzionalità e perimetro della 1.0.0

- Gestione amministrativa dei corsi e del programma didattico, docenti, versioni e pubblicazione.
- Fruizione di lezioni testo, documento, audio e video, con controllo degli accessi e tracciamento di base.
- Provider YouTube, Vimeo, Cloudflare Stream e HTML5 locale; materiali privati e allegati.
- Iscrizioni e roster essenziali, catalogo e percorsi, integrazione con ACL, categorie, lingue e servizi Joomla.
- Comunicazioni operative tramite **Modelli email Joomla**, coda e Scheduler, preferenze di invio; annunci di nuovi corsi soltanto con adesione esplicita.
- Attestati PDF essenziali di completamento. Open Badges/Credentials, reporting avanzato e quiz avanzati non fanno parte della prima release stabile.

L'implementazione non equivale al collaudo: la prima stabile sarà pubblicata soltanto dopo i gate tecnici, di sicurezza e di accettazione documentati.

## Stato dello sviluppo e ultime integrazioni

Il [report dell’audit funzionale](docs/FUNCTIONAL_AUDIT_CURRENT.md) riporta i criteri implementati, le lacune confermate e le verifiche residue. Il report corrente registra 446 criteri implementati, 43 mancanti e 268 non verificabili; nessun criterio resta da esaminare. Il minimo di avanzamento comprovato è 56,57%, distinto dalla percentuale complessiva rigorosa non determinabile. La percentuale globale 1.0.0 resta non determinabile. Lo [stato di implementazione](docs/IMPLEMENTATION_STATUS.md) documenta le funzionalità correnti. Le modifiche versionate sono nel [Changelog](CHANGELOG.md).

## Installazione e sviluppo

Schema InnoDB: **47 tabelle / 66 FK**.

Requisiti documentati: **Joomla 6**, PHP 8.3+ a 64 bit, MySQL 8.0.13+ oppure MariaDB 10.6+, InnoDB e utf8mb4. Hosting compatibile con gli ordinari requisiti Joomla e ambienti Apache/LiteSpeed; nessun requisito Nginx aggiuntivo.

Per preparare una build di sviluppo:

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

Il pacchetto da installare è il **`pkg_jlms-<versione>-dev.zip` interno**; quando distribuito tramite GitHub Actions, estrarlo dall'eventuale contenitore dell'artefatto prima di usare il gestore estensioni Joomla. Installare sempre l'intero pacchetto su un sito di prova; non eseguire test di database su siti di produzione. Configurare i provider video e i relativi consensi prima di abilitarli. Per dettagli su upgrade e rilascio vedere [Politica versioni](docs/VERSIONING.md) e [Testbook Joomla](docs/QA_TESTBOOK.md).

## Documentazione

| Argomento | Documento |
|---|---|
| Ricognizione dei merge fino alla PR #578 | [POST_MERGE_578_RECONCILIATION.md](docs/POST_MERGE_578_RECONCILIATION.md) |
| Stato corrente e lavoro residuo | [IMPLEMENTATION_STATUS.md](docs/IMPLEMENTATION_STATUS.md) |
| Piano delle PR verso la stabile | [STABLE_1.0.0_PR_PLAN.md](docs/STABLE_1.0.0_PR_PLAN.md) |
| Specifica UX frontend approvata (target) e contratto CSS corrente | [FRONTEND_UX_SPECIFICATION.md](docs/FRONTEND_UX_SPECIFICATION.md) · [FRONTEND_CSS.md](docs/FRONTEND_CSS.md) · [FRONTEND_CONTRACT_INVENTORY.md](docs/FRONTEND_CONTRACT_INVENTORY.md) |
| Requisiti, tracciabilità e decisioni | [REQUIREMENTS_STATUS.csv](docs/REQUIREMENTS_STATUS.csv) · [REQUIREMENTS_CHANGES.md](docs/REQUIREMENTS_CHANGES.md) |
| Test e accettazione | [QA_TESTBOOK.md](docs/QA_TESTBOOK.md) |
| Gate CI e revisione dei merge | [CI_MERGE_REVIEW.md](docs/CI_MERGE_REVIEW.md) |
| Modelli e notifiche email | [MAIL_TEMPLATES.md](docs/MAIL_TEMPLATES.md) |
| Corsi e pubblicazione | [COURSE_EDITOR.md](docs/COURSE_EDITOR.md) · [PUBLICATION.md](docs/PUBLICATION.md) |
| Didattica, video e materiali | [LEARNING.md](docs/LEARNING.md) · [VIDEO.md](docs/VIDEO.md) · [MATERIALS.md](docs/MATERIALS.md) |
| Iscrizioni, docenti e catalogo | [ENROLLMENT_ROSTER.md](docs/ENROLLMENT_ROSTER.md) · [TEACHERS.md](docs/TEACHERS.md) · [CATEGORIES.md](docs/CATEGORIES.md) |
| Sicurezza, privacy e test release | [SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md](docs/SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md) |
| Changelog delle versioni | [CHANGELOG.md](CHANGELOG.md) |
| Evidenze e cronologia tecnica | [IMPLEMENTATION_EVIDENCE.json](docs/IMPLEMENTATION_EVIDENCE.json) · [IMPLEMENTATION_HISTORY.md](docs/IMPLEMENTATION_HISTORY.md) |

## Sviluppi successivi

JD Learn Credentials / Open Badges è pianificato separatamente e non rientra nel gate 1.0.0; vedere il [piano Credentials](docs/CREDENTIALS_IMPLEMENTATION_PLAN.md). La vendita di corsi e la gestione di abbonamenti rimangono esterne al componente.

## Regole per contribuire

Ogni PR deve indicare requisiti e issue interessati, descrivere i **Comportamenti da testare** in Joomla, aggiornare tracciabilità e guide pertinenti e verificare la CI. Il **README non è un diario delle PR**: lo storico delle modifiche va nel changelog, le evidenze operative e i test nei rispettivi documenti. Una PR esclusivamente documentale non aumenta la percentuale di implementazione.

