# JD Learn

Sistema e-learning per Joomla 6 di Jecko Development. **Main 0.41.30-dev**; la PR candidata 0.41.31-dev completa issue #113 filtrando la sidebar nativa per permessi specifici oltre ai riquadri Dashboard. CI e collaudo Joomla del candidato sono pendenti. La CI standard è superata nel run [42589362926](https://github.com/jeckodevelopment/jd_learn/actions/runs/42589362926); collaudo Joomla manuale nel testbook. PR #124 integra issue #115 / AC-134; PR #122 integra issue #117 / AC-133. Include la correzione Privacy (PR #102), l’accesso centralizzato ai campi Corsi/Docenti/Lezioni (issue #99, PR #104), la dashboard amministrativa (issue #105, PR #108/#110/#112), la vista Docenti standard Joomla (AC-130, PR #116) e la gestione banca domande riorganizzata (AC-131, PR #120). Il collaudo manuale della dashboard è stato confermato superato dall’utente l’8 novembre 2026; per AC-131 il collaudo Joomla resta da eseguire. La 0.41.28-dev aggiunge conteggio corsi per docente, link filtrato, filtro docente nella lista Corsi e titolo Docenti tradotto; la CI è superata e il collaudo manuale Joomla è nel testbook. La 0.41.29-dev corregge le traduzioni del gruppo Cronologia versioni e del titolo pagina Opzioni in IT/EN; CI standard superata, collaudo manuale nel testbook. Statistiche, attestati e altre voci future restano fuori dalla dashboard.

**Per sapere a che punto siamo: [stato attuale dello sviluppo](docs/IMPLEMENTATION_STATUS.md).** La gestione di base dei corsi e la fruizione testo/video/quiz sono disponibili. Restano incompleti il ciclo editoriale avanzato, le regole didattiche, le iscrizioni complete, gli attestati, le notifiche, i report e il collaudo di rilascio.

## Orientarsi nella documentazione

| Documento | A cosa serve |
|---|---|
| [Stato attuale](docs/IMPLEMENTATION_STATUS.md) | Cosa funziona, cosa manca e quali prove abbiamo |
| [Prossime attività](docs/NEXT_STEPS.md) | Ordine del lavoro residuo e condizioni per chiuderlo |
| [Checklist gestione corsi](docs/M01_ACCEPTANCE.md) | Funzioni presenti, implementazioni mancanti e collaudi M01 |
| [Roadmap](docs/ROADMAP_TO_STABLE.md) | Perimetro delle fasi fino al rilascio stabile |
| [Matrice requisiti](docs/REQUIREMENTS_STATUS.csv) | Tracciabilità dettagliata dei 210 requisiti |
| [Modifiche ai requisiti](docs/REQUIREMENTS_CHANGES.md) | Decisioni approvate e scenari AC aggiornati |
| [Come leggere lo stato requisiti](docs/REQUIREMENTS_STATUS_GUIDE.md) | Significato di implementazione parziale, criteri completi e accettazione |
| [Testbook collaudo Joomla](docs/QA_TESTBOOK.md) | Percorsi manuali per PR, registrazione esiti e prove ancora da ricostruire |
| [Problemi specifici](docs/IMPLEMENTATION_GAPS.md) | Differenze aperte e segnalazioni risolte |
| [Backend: piano UX](docs/ADMIN_UX_PLAN.md) | Menu e dashboard amministrativa aggiornati; restano collaudo ruoli, accessibilità e navigazione in Joomla reale |
| [Politica CI](docs/CI_POLICY.md) | Bozze leggere e integrazioni sul candidato prima del merge |
| [Prove del build](docs/IMPLEMENTATION_EVIDENCE.json) | Commit, CI e pacchetto effettivamente verificati |
| [Cronologia](docs/IMPLEMENTATION_HISTORY.md) e [changelog](CHANGELOG.md) | Come siamo arrivati allo stato attuale |

## Usare e configurare JD Learn

- Corsi: [editor](docs/COURSE_EDITOR.md), [programma](docs/AUTHORING.md), [pubblicazione](docs/PUBLICATION.md), [permessi](docs/EDITOR_PERMISSIONS.md), [docenti](docs/TEACHERS.md), [calendario](docs/CALENDAR.md).
- Studenti: [aula](docs/LEARNING.md), [area personale](docs/LEARNER_DASHBOARD.md), [categorie](docs/CATEGORIES.md), [multilingua](docs/MULTILINGUAL.md).
- Media: [video](docs/VIDEO.md), [Cloudflare Stream](docs/CLOUDFLARE_STREAM.md), [HTML5 privato](docs/HTML5_VIDEO.md), [materiali](docs/MATERIALS.md).
- Iscrizioni: [elenco iscritti](docs/ENROLLMENT_ROSTER.md), [migrazione tra versioni](component/administrator/docs/ENROLLMENT_MIGRATION.md), [ammissione manuale](docs/MANUAL_ENROLLMENT.md), [preassegnazione](docs/PREASSIGNMENT.md), [stato della singola iscrizione](docs/ENROLLMENT_STATE.md), [disponibilità operativa del corso](docs/COURSE_OPERATIONS.md).
- Quiz: [composizione](docs/QUIZ_AUTHORING.md), [tentativi](docs/QUIZ_RUNTIME.md), [tempi individuali](docs/QUIZ_EXTRA_TIME.md), [annullamenti](docs/QUIZ_CANCELLATION.md), [proroghe](docs/QUIZ_DEADLINE_EXTENSION.md).
- Amministrazione: [dashboard JD Learn](component/administrator/docs/ADMIN_DASHBOARD.md).
- Personalizzazioni: [contratto CSS e override](docs/FRONTEND_CSS.md).

## Specifiche e perimetro

La [specifica funzionale 1.5 con addendum approvato l’8 novembre](docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md), il [progetto tecnico](docs/specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md), le [specifiche di sicurezza e provider](docs/specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md) e i [servizi progettati](docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md) definiscono il risultato da raggiungere. Il suffisso `1.0` nei nomi è storico: la versione interna è autorevole. Le specifiche descrivono anche funzioni non ancora implementate; le decisioni sono registrate in [REQUIREMENTS_CHANGES.md](docs/REQUIREMENTS_CHANGES.md).

Identificativi installati: `com_jlms`, `pkg_jlms`, `lib_jlms`, gruppo `jlmsvideo`. Account, gruppi e livelli sono quelli Joomla. Vendita e gestione degli abbonamenti sono esterne; l'iscrizione non assegna gruppi. Compiti, live e interoperabilità seguono le fasi differite della specifica.

## Ambiente, build e installazione

Joomla 6, PHP 8.3+ a 64 bit, MySQL 8.0.13+ oppure MariaDB 10.6+, InnoDB e utf8mb4. La CI usa Joomla 6.0.0 come minimo di compatibilità, non come raccomandazione di patch per la produzione.

Schema InnoDB: **42 tabelle / 59 FK**. Il pacchetto candidato 0.41.31 contiene 9 estensioni: componente, libreria, quattro provider video, plugin Scheduler, Privacy e filtro ACL della sidebar.

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

La build genera `dist/pkg_jlms-<versione>-dev.zip` e `SHA256SUMS.txt`; la versione è letta dai manifest del pacchetto. Non richiede Composer o NPM sul sito. Installare il pacchetto intero su un Joomla di prova tramite il gestore estensioni. Database e smoke test richiedono un sito usa e getta e `JLMS_TEST_DISPOSABLE=1`; non eseguirli su un sito cliente.

I provider video sono installati disabilitati: configurare plugin e consenso secondo le guide prima dell'uso. **JD Learn - Privacy** viene abilitato alla prima installazione; gli aggiornamenti preservano la scelta amministrativa. Le stringhe sono caricate nel contesto lingua usato da Joomla Privacy. Stream e HTML5 richiedono anche configurazione server/storage. I campi delle credenziali accettano riferimenti, non segreti. La disinstallazione conserva tabelle e file: non equivale alla cancellazione privacy.




La gestione corsi comprende editor e programma, versioni, video con durata automatica e menu amministrativo nativo. Plugin video apre il gestore Joomla filtrato sul gruppo jlmsvideo. La resa editoriale dei campi corso e i campi didattici delle lezioni sono disponibili; la CI standard della 0.41.7 è superata (PR #65, run 42420998171). La 0.41.8 aggiorna la dashboard amministrativa. La 0.41.9 integra i controlli R-03 per ammissioni ed erogazione (PR #70, merge [70f6ed7](https://github.com/jeckodevelopment/jd_learn/commit/70f6ed7f2a1f4386b943bcb834f57e8d31cf935f)); CI standard superata (run [42428496835](https://github.com/jeckodevelopment/jd_learn/actions/runs/42428496835)); collaudo manuale Joomla ancora aperto. M01 resta aperto per il collaudo sul sito Joomla e gli altri requisiti indicati nello stato attuale.

Pacchetto verificato per il runtime main **0.41.26-dev**: [artefatto CI 42524671164](https://github.com/jeckodevelopment/jd_learn/actions/runs/42524671164/artifacts/13634039771), disponibile fino al 22 novembre 2026. Lo ZIP scaricato è un contenitore da estrarre una volta; il pacchetto interno installabile ha versione 0.41.26-dev e SHA-256 `869c40a705602af9c1c7e2bd1562e407fce75d38038cefcf67085156c3230ab5` (503665 byte).
