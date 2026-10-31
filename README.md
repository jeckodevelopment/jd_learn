# JD Learn

Sistema e-learning per Joomla 6 di Jecko Development. Questo repository contiene lo sviluppo e le specifiche concordate.

**Stato: incremento 0.14.0-dev, non adatto alla produzione.** Sono implementate le fondamenta, i corsi in bozza e il programma con moduli e lezioni di testo/video in bozza, pubblicazione dei corsi di testo, catalogo pubblico e primo flusso studente con iscrizione automatica/aula/completamento e materiali extra protetti. Il sistema e-learning completo descritto nelle specifiche è ancora in sviluppo.

## Funzionalità attuali

- Pacchetto Joomla composto da libreria contratti, componente MVC e quattro plugin video dedicati, più un plugin Joomla Scheduler.
- Schema InnoDB di 39 tabelle con 56 vincoli referenziali; installer a passi riprendibili e conservazione dei dati alla disinstallazione.
- Backend con dashboard, creazione/modifica di corsi in bozza, categorie native, livelli Joomla separati per catalogo e contenuto, lingua, asset ACL e check-in/out.
- Controllo della revisione durante il salvataggio e allowlist dei campi; azioni generiche non implementate restano chiuse.
- Prima versione didattica in bozza, moduli e lezioni: creazione/modifica, obbligatorietà, riordino e rimozione controllata; editor Joomla e filtro HTML nativi.
- Pubblicazione validata tramite workflow e plugin Publishing nativi Joomla; versione immutabile, hash del programma, edizione iniziale, rimozione/ripubblicazione nel catalogo senza alterare lo storico.
- Catalogo paginato e scheda pubblica con programma, filtri categoria/sottocategorie, livelli correnti e controlli sugli antenati; nessun testo delle lezioni esposto.
- Iscrizione automatica e ripresa, aula testo responsive, completamento manuale persistente e idempotente; accesso riletto dal core anche dopo revoca dei gruppi con sessione aperta.
- Materiali del corso e della lezione: upload validato, quarantena/scansione opzionale, versioni immutabili e download protetto con Range. Storage privato e consegna PHP richiedono configurazione esplicita.
- Contratto video v1, registro lazy e normalizzazione sicura di sorgenti YouTube, Vimeo, Cloudflare Stream e asset HTML5 locali. Riproduzione YouTube/Vimeo opt-in, consenso e salvataggio della copertura video; Cloudflare Stream tramite API con verifica remota e rinnovo; HTML5 con upload MP4 e consegna privata PHP limitata/Nginx. Vedere [limiti e protocollo](docs/VIDEO.md).
- Banca domande con categorie/asset/check-in nativi, tre formati e revisioni immutabili; composizione quiz in bozza, pubblicazione, tentativi con snapshot, autosave/CAS, timer server, risultati e feedback protetto.
- Tempo aggiuntivo individuale per tentativi futuri, con storico, permessi per corso e Action Logs Joomla; avvisi accessibili del timer.
- Annullamento motivato dei tentativi con anteprima, conferma, scelta di restituzione, proiezioni ricalcolate e audit Joomla.
- Proroghe motivate dei tentativi aperti con anteprima, storia e audit; sincronizzazione compatta del timer senza riscrivere regole o risposte.
- Regole dominio iniziali per accesso corrente, unione intervalli video e scoring esatto dei quiz, con test runtime.
- Traduzioni it-IT/en-GB, prime regioni CSS pubbliche e generazione riproducibile dello ZIP.

## Specifiche e avanzamento

- [Specifica funzionale consolidata](docs/specifications/Specifica_funzionale_LMS_Joomla_1.0.md)
- [Progettazione tecnica esecutiva](docs/specifications/Progettazione_tecnica_esecutiva_LMS_1.0.md)
- [Sicurezza e specifiche complete dei quattro provider](docs/specifications/Sviluppo_sicuro_e_specifiche_plugin_video_1.0.md)
- [Stato di implementazione e roadmap](docs/IMPLEMENTATION_STATUS.md)
- [Stato dei 210 requisiti](docs/REQUIREMENTS_STATUS.csv)
- [Servizi ed endpoint](docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md)
- [Personalizzazione frontend e override](docs/FRONTEND_CSS.md)
- [Mockup funzionali concordati](docs/specifications/Mockup_LMS_Joomla_1.0.pdf)
- [Gestione del programma didattico](docs/AUTHORING.md)
- [Aula e iscrizione automatica](docs/LEARNING.md)
- [Materiali e storage protetto](docs/MATERIALS.md)
- [Cloudflare Stream: configurazione e limiti](docs/CLOUDFLARE_STREAM.md)
- [Video HTML5 locali: configurazione e limiti](docs/HTML5_VIDEO.md)
- [Banca domande e quiz in bozza](docs/QUIZ_AUTHORING.md)
- [Tentativi quiz e attività pianificata](docs/QUIZ_RUNTIME.md)
- [Tempi individuali e audit nativo](docs/QUIZ_EXTRA_TIME.md)
- [Annullamento dei tentativi](docs/QUIZ_CANCELLATION.md)
- [Proroga dei tentativi aperti](docs/QUIZ_DEADLINE_EXTENSION.md)
- [Changelog](CHANGELOG.md)

La denominazione di prodotto è **JD Learn**. Gli identificativi tecnici restano `com_jlms`, `pkg_jlms`, `lib_jlms` e gruppo `jlmsvideo`, coerenti con la baseline; non rinominare estensioni già installate senza migrazione. Non sono inclusi pagamenti o abbonamenti: un’estensione esterna attribuirà i gruppi Joomla.

## Requisiti

Joomla 6, PHP 8.3+ a 64 bit, MySQL 8.0.13+ o MariaDB 10.6+, InnoDB e utf8mb4. Le patch esatte e la minor corrente saranno aggiunte alla matrice prima della release. Il tag Joomla 6.0.0 nella CI verifica esclusivamente il minimo di compatibilità; non è una raccomandazione a usare quella patch in produzione.

## Build e verifiche

Con Python 3 e PHP 8.3+:

```sh
python3 tools/build.py
python3 tools/check.py
php tests/run.php
```

Lo ZIP generato è `dist/pkg_jlms-0.14.0-dev.zip`, accompagnato da SHA256SUMS. Non richiede Composer o NPM sul sito del cliente. La pipeline esegue lint PHP, test dominio, schema su MySQL/MariaDB, doppia build riproducibile e installazione/smoke test su Joomla reale. I risultati effettivi sono visibili nelle Actions, non dedotti dall’esistenza del workflow.

`tests/database.php` e `tests/joomla-smoke.php` richiedono un database/sito usa e getta e opt-in `JLMS_TEST_DISPOSABLE=1`. Non eseguirli su siti reali. Le credenziali nei test sono esclusivamente fixture effimere della CI.

## Installazione di sviluppo

Generare lo ZIP e installarlo intero su un Joomla di prova mediante il gestore estensioni. Non installare separatamente il componente: dipende dalla libreria. Aprire Componenti → JD Learn, creare una categoria e quindi un corso in bozza. I provider vengono installati disabilitati; YouTube/Vimeo richiedono anche playback_enabled e policy consenso nelle opzioni del componente (vedere docs/VIDEO.md); Cloudflare richiede anche configurazione server e verifica remota (docs/CLOUDFLARE_STREAM.md); HTML5 richiede ffprobe e storage/consegna privata (docs/HTML5_VIDEO.md). Non inserire credenziali API nei parametri: i campi predisposti accettano riferimenti, non segreti.

La disinstallazione conserva le tabelle e non cancella file del gestore; non equivale alla cancellazione privacy. La revisione dello schema e la ricostruzione delle dipendenze core al reinstall sono ancora oggetto del collaudo previsto. Nessuna procedura distruttiva automatica è presente.

Lo ZIP include iscrizione automatica, testo, player video opt-in, materiali protetti e primo flusso quiz. Approvazioni, attestati, rivalutazioni e integrazioni restanti sono ancora in sviluppo. Non usarlo con studenti o dati personali reali finché le integrazioni e il collaudo non saranno completi.

Associazioni dei corsi multilingua: [guida e limiti](docs/MULTILINGUAL.md). Configurare i collegamenti fra bozze prima della pubblicazione; selettore lingua e fallback sono nativi Joomla.
