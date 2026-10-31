# JD Learn

Sistema e-learning per Joomla 6 di Jecko Development. Questo repository contiene lo sviluppo e le specifiche concordate.

**Stato: incremento 0.1.0-dev, non adatto alla produzione.** Sono implementate le fondamenta del pacchetto e la gestione dei corsi in bozza. Il sistema e-learning completo descritto nelle specifiche è ancora in sviluppo.

## Funzionalità del primo incremento

- Pacchetto Joomla composto da libreria contratti, componente MVC e quattro plugin video dedicati.
- Schema InnoDB di 37 tabelle con 53 vincoli referenziali; installer a passi riprendibili e conservazione dei dati alla disinstallazione.
- Backend con dashboard, creazione/modifica di corsi in bozza, categorie native, livelli Joomla separati per catalogo e contenuto, lingua, asset ACL e check-in/out.
- Controllo della revisione durante il salvataggio e allowlist dei campi; pubblicazione e azioni generiche non implementate restano chiuse.
- Contratto video v1, registro lazy e normalizzazione sicura di sorgenti YouTube, Vimeo, Cloudflare Stream e asset HTML5 locali. **Riproduzione e SDK non ancora integrati.**
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

Lo ZIP generato è `dist/pkg_jlms-0.1.0-dev.zip`, accompagnato da SHA256SUMS. Non richiede Composer o NPM sul sito del cliente. La pipeline esegue lint PHP, test dominio, schema su MySQL/MariaDB, doppia build riproducibile e installazione/smoke test su Joomla reale. I risultati effettivi sono visibili nelle Actions, non dedotti dall’esistenza del workflow.

`tests/database.php` e `tests/joomla-smoke.php` richiedono un database/sito usa e getta e opt-in `JLMS_TEST_DISPOSABLE=1`. Non eseguirli su siti reali. Le credenziali nei test sono esclusivamente fixture effimere della CI.

## Installazione di sviluppo

Generare lo ZIP e installarlo intero su un Joomla di prova mediante il gestore estensioni. Non installare separatamente il componente: dipende dalla libreria. Aprire Componenti → JD Learn, creare una categoria e quindi un corso in bozza. I provider vengono installati disabilitati; abilitarli serve al momento solo a verificarne la registrazione. Non inserire credenziali API nei parametri: i campi predisposti accettano riferimenti, non segreti.

La disinstallazione conserva le tabelle e non cancella file del gestore; non equivale alla cancellazione privacy. La revisione dello schema e la ricostruzione delle dipendenze core al reinstall sono ancora oggetto del collaudo previsto. Nessuna procedura distruttiva automatica è presente.

Lo ZIP di sviluppo non abilita iscrizioni, fruizione o attestati. Non usarlo con studenti o dati personali reali finché le integrazioni e il collaudo non saranno completi.
