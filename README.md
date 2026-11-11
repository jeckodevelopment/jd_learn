Aggiornamento in preparazione PR: candidato **0.41.63-dev** introduce vincoli referenziali per il lifecycle delle categorie. La versione integrata resta 0.41.62-dev; l’ultimo pacchetto e i collaudi sono nel changelog. Avanzamento precedente 75% (524/7).


**Per sapere a che punto siamo: [stato attuale dello sviluppo](docs/IMPLEMENTATION_STATUS.md).** La gestione di base dei corsi e la fruizione testo/documento/audio/video/quiz sono disponibili nei flussi implementati; collaudi manuali di release ancora aperti, inclusi il rilascio relativo della 0.41.38-dev, l’authoring Percorsi della 0.41.39-dev e la dashboard provider 0.41.40-dev e il workflow editoriale e anteprima accessi 0.41.43-dev, default del fuso sito 0.41.44-dev e toolbar del programma 0.41.45-dev. La preview editoriale video read-only è integrata; PR #179/0.41.47-dev integra documenti/audio, mentre le interazioni quiz restano escluse; le regole didattiche, le iscrizioni complete, gli attestati, le notifiche, i report e il collaudo di rilascio.

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

## Evoluzioni successive alla prima stabile

- **JD Learn Credentials (Open Badges 3.0)**: modulo futuro **nativo in `com_jlms`**, senza installazione di un secondo componente, [specifica funzionale](docs/specifications/JD_LEARN_CREDENTIALS_FUNCTIONAL_SPEC.md) e [progettazione tecnica T-001–T-007](docs/specifications/JD_LEARN_CREDENTIALS_TECHNICAL_DESIGN.md) e [piano 21 PR / milestone](docs/CREDENTIALS_IMPLEMENTATION_PLAN.md) e [decisioni approvate T-008–T-015](docs/CREDENTIALS_DECISIONS_T008_T015.md) per emissione, firma, verifica pubblica sul dominio dell'emittente e condivisione LinkedIn. **Esclusa dalla versione 1.0.0**; sviluppo e avanzamento separati dalla baseline dei 210 requisiti. Nessuna funzionalità Credentials è attualmente dichiarata implementata.

## Specifiche e perimetro

La [specifica funzionale 1.5 con addendum approvato l’8 novembre](docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md), il [progetto tecnico](docs/specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md), le [specifiche di sicurezza e provider](docs/specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md) e i [servizi progettati](docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md) definiscono il risultato da raggiungere. Il suffisso `1.0` nei nomi è storico: la versione interna è autorevole. Le specifiche descrivono anche funzioni non ancora implementate; le decisioni sono registrate in [REQUIREMENTS_CHANGES.md](docs/REQUIREMENTS_CHANGES.md).

Identificativi installati: `com_jlms`, `pkg_jlms`, `lib_jlms`, gruppo `jlmsvideo`. Account, gruppi e livelli sono quelli Joomla. Vendita e gestione degli abbonamenti sono esterne; l'iscrizione non assegna gruppi. Compiti, live e interoperabilità seguono le fasi differite della specifica.

## Ambiente, build e installazione

Joomla 6, PHP 8.3+ a 64 bit, MySQL 8.0.13+ oppure MariaDB 10.6+, InnoDB e utf8mb4. La CI usa Joomla 6.0.0 come minimo di compatibilità, non come raccomandazione di patch per la produzione.

Schema InnoDB: **42 tabelle / 63 FK**. Il pacchetto 0.41.63-dev contiene 9 estensioni: componente, libreria, quattro provider video, plugin Scheduler, Privacy e filtro ACL della sidebar.

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

La build genera `dist/pkg_jlms-<versione>-dev.zip` e `SHA256SUMS.txt`; la versione è letta dai manifest del pacchetto. Non richiede Composer o NPM sul sito. Installare il pacchetto intero su un Joomla di prova tramite il gestore estensioni. Database e smoke test richiedono un sito usa e getta e `JLMS_TEST_DISPOSABLE=1`; non eseguirli su un sito cliente.

I provider video sono installati disabilitati: configurare plugin e consenso secondo le guide prima dell'uso. **JD Learn - Privacy** viene abilitato alla prima installazione; gli aggiornamenti preservano la scelta amministrativa. Le stringhe sono caricate nel contesto lingua usato da Joomla Privacy. Stream e HTML5 richiedono anche configurazione server/storage. I campi delle credenziali accettano riferimenti, non segreti. La disinstallazione conserva tabelle e file: non equivale alla cancellazione privacy.




La gestione corsi comprende editor e programma, versioni, video con durata automatica e menu amministrativo nativo. Plugin video apre il gestore Joomla filtrato sul gruppo jlmsvideo. La resa editoriale dei campi corso e i campi didattici delle lezioni sono disponibili; la CI standard della 0.41.7 è superata (PR #65, run 42420998171). La 0.41.8 aggiorna la dashboard amministrativa. La 0.41.9 integra i controlli R-03 per ammissioni ed erogazione (PR #70, merge [70f6ed7](https://github.com/jeckodevelopment/jd_learn/commit/70f6ed7f2a1f4386b943bcb834f57e8d31cf935f)); CI standard superata (run [42428496835](https://github.com/jeckodevelopment/jd_learn/actions/runs/42428496835)); collaudo manuale Joomla ancora aperto. M01 resta aperto per il collaudo sul sito Joomla e gli altri requisiti indicati nello stato attuale.
