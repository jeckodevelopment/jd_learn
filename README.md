# JD Learn

Sistema e-learning per Joomla6 di Jecko Development, riutilizzabile e indipendente dal sito/template. **Versione0.20.0-dev: sviluppo in corso, non pronta per la produzione.**

Schema InnoDB: **41 tabelle / 58 FK**. Pacchetto con7costituenti: componente MVC, libreria, quattro plugin video e plugin Scheduler. Né lo schema né il numero degli incrementi indicano completezza del prodotto.

## Funzioni operative e limiti

- Authoring di corsi/moduli/lezioni testo, video e quiz; pubblicazione validata tramite workflow Joomla e versioni/snapshot immutabili. Clonazione/versionamento avanzato, audio/documenti autonomi, prerequisiti e migrazioni restano da completare.
- Catalogo e categorie paginati, controllo degli antenati, menu/Pathway/SEF e prime associazioni multilingua native. Ricerca/SEO/metadati e lifecycle completi rimangono parziali.
- Aula, progressi persistenti e area personale con storico del proprietario, filtri e ripresa autorizzata. Fallback di ripresa e tutte le viste/attività della baseline non sono ancora completi.
- Quattro provider base YouTube, Vimeo, Cloudflare Stream e HTML5: opt-in/consenso, copertura video, token API Stream e MP4 privati con consegna PHP limitata/Nginx. Firma locale/cache/configurazioni complete e provider/browser reali da collaudare. **Un quinto plugin non è ancora integrabile senza patch al componente.**
- Materiali extra di corso/lezione con upload validato, quarantena/scanner opzionale, storage privato e download autorizzato/Range. Riuso, pulizia/offload e lifecycle completi pendenti.
- Avvio automatico, elenco iscritti per corso, sospensione/riattivazione e ammissione manuale singola con anteprima/conferma/motivo/audit. Preassegnazione senza livello con opt-in/avviso disponibile dalla0.20; CSV, approvazioni, annullamenti ed edizioni aggiuntive restano pendenti.
- Banca domande a revisioni, tentativi/snapshot, autosave/CAS, grading, feedback e scadenza server; tempo individuale, annullamento e proroga motivati. Rivalutazioni e integrazione attestati da implementare.
- Utenti, ACL/livelli, categorie, form, sessioni/CSRF, WAM, override e lingue Joomla nei flussi presenti. Audit nativo su quiz/iscrizioni e task scadenze quiz; **Privacy, Finder, Tags, Custom Fields, Content History, Mail Templates e copertura core completa ancora pendenti**.

Attestati PDF, notifiche/outbox e report/export completi non sono disponibili. I test CI positivi riguardano i flussi implementati, non il collaudo integrale di tutti i requisiti, la patch Joomla corrente, WCAG/ASVS o il carico300/500.

## Stato verificato e prossimi passaggi

- [Riepilogo corrente](docs/IMPLEMENTATION_STATUS.md)
- [Matrice dei210requisiti con sorgenti, prove parziali e residui](docs/REQUIREMENTS_STATUS.csv)
- [Build e prove effettive](docs/IMPLEMENTATION_EVIDENCE.json)
- [Differenze aperte rispetto alla specifica](docs/IMPLEMENTATION_GAPS.md)
- [Piano esecutivo residuo](docs/NEXT_STEPS.md)
- [Cronologia degli incrementi](docs/IMPLEMENTATION_HISTORY.md) e [changelog](CHANGELOG.md)

La matrice baseline dei test resta il piano di accettazione. I test di singoli incrementi non ne promuovono automaticamente gli scenari ad accettati, e non si ricava una percentuale dalle righe «In corso».

## Specifiche e guide

- [Specifica funzionale consolidata1.2](docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md)
- [Progettazione tecnica esecutiva](docs/specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md)
- [Sicurezza e specifiche dei quattro provider](docs/specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md)
- [Servizi/endpoint progettati](docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md)
- [Personalizzazione frontend e override](docs/FRONTEND_CSS.md)
- [Authoring](docs/AUTHORING.md), [pubblicazione](docs/PUBLICATION.md), [categorie](docs/CATEGORIES.md), [multilingua](docs/MULTILINGUAL.md)
- [Aula e iscrizione automatica](docs/LEARNING.md), [area personale](docs/LEARNER_DASHBOARD.md)
- [Elenco amministrativo iscritti](docs/ENROLLMENT_ROSTER.md), [sospensione/riattivazione](docs/ENROLLMENT_STATE.md), [ammissione manuale](docs/MANUAL_ENROLLMENT.md)
- [Materiali](docs/MATERIALS.md), [video/contratto e limiti](docs/VIDEO.md), [Cloudflare Stream](docs/CLOUDFLARE_STREAM.md), [HTML5 privato](docs/HTML5_VIDEO.md)
- [Banca e composizione quiz](docs/QUIZ_AUTHORING.md), [tentativi e task](docs/QUIZ_RUNTIME.md), [tempi individuali](docs/QUIZ_EXTRA_TIME.md), [annullamenti](docs/QUIZ_CANCELLATION.md), [proroghe](docs/QUIZ_DEADLINE_EXTENSION.md)

Gli identificativi restano `com_jlms`, `pkg_jlms`, `lib_jlms`, gruppo `jlmsvideo`. Vendita/abbonamenti e aggiornamento dei gruppi commerciali sono esterni; nessun gateway o autenticazione parallela. Compiti, live e interoperabilità mantengono le fasi successive della baseline.

## Requisiti

Joomla 6, PHP 8.3+ a 64 bit, MySQL 8.0.13+ o MariaDB 10.6+, InnoDB e utf8mb4. Le patch esatte e la minor corrente saranno aggiunte alla matrice prima della release. Il tag Joomla 6.0.0 nella CI verifica esclusivamente il minimo di compatibilità; non è una raccomandazione a usare quella patch in produzione.

## Build e verifiche

Con Python 3 e PHP 8.3+:

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

Lo ZIP generato è `dist/pkg_jlms-0.20.0-dev.zip`, accompagnato da SHA256SUMS. Non richiede Composer o NPM sul sito del cliente. La pipeline esegue lint PHP, test dominio, schema su MySQL/MariaDB, doppia build riproducibile e installazione/smoke test su Joomla reale. I risultati effettivi sono visibili nelle Actions, non dedotti dall’esistenza del workflow.

`tests/database.php` e `tests/joomla-smoke.php` richiedono un database/sito usa e getta e opt-in `JLMS_TEST_DISPOSABLE=1`. Non eseguirli su siti reali. Le credenziali nei test sono esclusivamente fixture effimere della CI.

## Installazione di sviluppo

Generare lo ZIP e installarlo intero su un Joomla di prova mediante il gestore estensioni. Non installare separatamente il componente: dipende dalla libreria. Aprire Componenti → JD Learn, creare una categoria e quindi un corso in bozza. I provider vengono installati disabilitati; YouTube/Vimeo richiedono anche playback_enabled e policy consenso nelle opzioni del componente (vedere docs/VIDEO.md); Cloudflare richiede anche configurazione server e verifica remota (docs/CLOUDFLARE_STREAM.md); HTML5 richiede ffprobe e storage/consegna privata (docs/HTML5_VIDEO.md). Non inserire credenziali API nei parametri: i campi predisposti accettano riferimenti, non segreti.

La disinstallazione conserva le tabelle e non cancella file del gestore; non equivale alla cancellazione privacy. La revisione dello schema e la ricostruzione delle dipendenze core al reinstall sono ancora oggetto del collaudo previsto. Nessuna procedura distruttiva automatica è presente.

Lo ZIP include iscrizione automatica, testo, player video opt-in, materiali protetti e primo flusso quiz. Approvazioni, attestati, rivalutazioni e integrazioni restanti sono ancora in sviluppo. Non usarlo con studenti o dati personali reali finché le integrazioni e il collaudo non saranno completi.

Associazioni dei corsi multilingua: [guida e limiti](docs/MULTILINGUAL.md). Configurare i collegamenti fra bozze prima della pubblicazione; selettore lingua e fallback sono nativi Joomla.

Categorie native e navigazione pubblica: [guida](docs/CATEGORIES.md), con accesso degli antenati, paginazione e traduzioni per la sezione corsi.

Area personale I miei corsi: [guida](docs/LEARNER_DASHBOARD.md), con storico privato, avanzamento e disponibilità separati e ripresa nuovamente autorizzata.

- [Preassegnazione0.20](docs/PREASSIGNMENT.md): assegnazione senza livelli correnti, nessun gruppo o diritto di fruizione aggiunto.
