# JD Learn

Sistema e-learning per Joomla 6 di Jecko Development. **Versione 0.41.3-dev: sviluppo in corso, non pronta per la produzione.**

**Per sapere a che punto siamo: [stato attuale dello sviluppo](docs/IMPLEMENTATION_STATUS.md).** La gestione di base dei corsi e la fruizione testo/video/quiz sono disponibili. Restano incompleti il ciclo editoriale avanzato, le regole didattiche, le iscrizioni complete, gli attestati, le notifiche, i report e il collaudo di rilascio.

## Orientarsi nella documentazione

| Documento | A cosa serve |
|---|---|
| [Stato attuale](docs/IMPLEMENTATION_STATUS.md) | Cosa funziona, cosa manca e quali prove abbiamo |
| [Prossime attività](docs/NEXT_STEPS.md) | Ordine del lavoro residuo e condizioni per chiuderlo |
| [Checklist gestione corsi](docs/M01_ACCEPTANCE.md) | Funzioni presenti, implementazioni mancanti e collaudi M01 |
| [Roadmap](docs/ROADMAP_TO_STABLE.md) | Perimetro delle fasi fino al rilascio stabile |
| [Matrice requisiti](docs/REQUIREMENTS_STATUS.csv) | Tracciabilità dettagliata dei 210 requisiti |
| [Problemi specifici](docs/IMPLEMENTATION_GAPS.md) | Differenze aperte e segnalazioni risolte |
| [Backend: piano UX](docs/ADMIN_UX_PLAN.md) | Restyling ingresso e menu amministrativo, ancora da implementare |
| [Politica CI](docs/CI_POLICY.md) | Bozze leggere e integrazioni sul candidato prima del merge |
| [Prove del build](docs/IMPLEMENTATION_EVIDENCE.json) | Commit, CI e pacchetto effettivamente verificati |
| [Cronologia](docs/IMPLEMENTATION_HISTORY.md) e [changelog](CHANGELOG.md) | Come siamo arrivati allo stato attuale |

## Usare e configurare JD Learn

- Corsi: [editor](docs/COURSE_EDITOR.md), [programma](docs/AUTHORING.md), [pubblicazione](docs/PUBLICATION.md), [permessi](docs/EDITOR_PERMISSIONS.md), [docenti](docs/TEACHERS.md), [calendario](docs/CALENDAR.md).
- Studenti: [aula](docs/LEARNING.md), [area personale](docs/LEARNER_DASHBOARD.md), [categorie](docs/CATEGORIES.md), [multilingua](docs/MULTILINGUAL.md).
- Media: [video](docs/VIDEO.md), [Cloudflare Stream](docs/CLOUDFLARE_STREAM.md), [HTML5 privato](docs/HTML5_VIDEO.md), [materiali](docs/MATERIALS.md).
- Iscrizioni: [elenco iscritti](docs/ENROLLMENT_ROSTER.md), [ammissione manuale](docs/MANUAL_ENROLLMENT.md), [preassegnazione](docs/PREASSIGNMENT.md), [sospensione e riattivazione](docs/ENROLLMENT_STATE.md).
- Quiz: [composizione](docs/QUIZ_AUTHORING.md), [tentativi](docs/QUIZ_RUNTIME.md), [tempi individuali](docs/QUIZ_EXTRA_TIME.md), [annullamenti](docs/QUIZ_CANCELLATION.md), [proroghe](docs/QUIZ_DEADLINE_EXTENSION.md).
- Personalizzazioni: [contratto CSS e override](docs/FRONTEND_CSS.md).

## Specifiche e perimetro

La [specifica funzionale 1.5](docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md), il [progetto tecnico](docs/specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md), le [specifiche di sicurezza e provider](docs/specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md) e i [servizi progettati](docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md) definiscono il risultato da raggiungere. Il suffisso `1.0` nei nomi è storico: la versione interna è autorevole. Le specifiche descrivono anche funzioni non ancora implementate; il loro aggiornamento è registrato in [REQUIREMENTS_CHANGES.md](docs/REQUIREMENTS_CHANGES.md).

Identificativi installati: `com_jlms`, `pkg_jlms`, `lib_jlms`, gruppo `jlmsvideo`. Account, gruppi e livelli sono quelli Joomla. Vendita e gestione degli abbonamenti sono esterne; l'iscrizione non assegna gruppi. Compiti, live e interoperabilità seguono le fasi differite della specifica.

## Ambiente, build e installazione

Joomla 6, PHP 8.3+ a 64 bit, MySQL 8.0.13+ oppure MariaDB 10.6+, InnoDB e utf8mb4. La CI usa Joomla 6.0.0 come minimo di compatibilità, non come raccomandazione di patch per la produzione.

Schema InnoDB: **41 tabelle / 58 FK**. Il pacchetto contiene 7 estensioni: componente, libreria, quattro provider video e plugin Scheduler.

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

La build genera `dist/pkg_jlms-0.41.3-dev.zip` e `SHA256SUMS.txt`. Non richiede Composer o NPM sul sito. Installare il pacchetto intero su un Joomla di prova tramite il gestore estensioni. Database e smoke test richiedono un sito usa e getta e `JLMS_TEST_DISPOSABLE=1`; non eseguirli su un sito cliente.

I provider sono installati disabilitati: configurare plugin e consenso secondo le guide prima dell'uso. Stream e HTML5 richiedono anche configurazione server/storage. I campi delle credenziali accettano riferimenti, non segreti. La disinstallazione conserva tabelle e file: non equivale alla cancellazione privacy.




La gestione corsi comprende editor e programma, versioni, video con durata automatica e menu amministrativo nativo. Plugin video apre il gestore Joomla filtrato sul gruppo jlmsvideo. La resa editoriale pubblica dei campi corso è disponibile e verificata nella CI standard0.41; snapshot didattici e campi lezione restano aperti.

[Pacchetto0.41.3 verificato](https://github.com/jeckodevelopment/jd_learn/actions/runs/42402189584/artifacts/13585861657); commit, esiti e limiti nel registro delle prove. M01 resta aperto.
