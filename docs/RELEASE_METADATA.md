# Metadati di rilascio JD Learn — issue #379

## Regola vincolante

**Un solo numero di versione per l'intero pacchetto distribuito.** `tools/build.py` legge la versione sviluppo da `packages/pkg_jlms.xml` (attualmente `0.63.3`) oppure riceve `--release-version 1.0.0` per produrre la candidata stabile. All'interno dello ZIP produce il manifest del pacchetto principale e i manifest di **12 estensioni figlie** con la medesima versione. Non modifica i manifest del repository; la versione delle singole parti nel codice sorgente è un identificativo storico, non la versione installata del pacchetto.

Per tutte le parti distribuite il nome visibile in Gestione estensioni è `JD Learn` per il pacchetto/componente e `JD Learn - <Funzionalità>` per libreria e plugin. I plugin conservano i propri codici `<name>PLG_...</name>` nei manifest per compatibilità dell'aggiornamento; i file `*.sys.ini` (IT/EN) e, dove presenti, `*.ini` traducono tali codici nei nomi uniformi, verificati sui pacchetti ZIP dalla CI. Il componente conserva il nome chiave Joomla `COM_JLMS`, visualizzato come JD Learn tramite le traduzioni, per non alterare il riconoscimento dell'estensione durante l'upgrade. I campi tecnici `type`, `group`, `element`, `libraryname`, nomi ZIP e cartelle restano invariati: questo mantiene la continuità negli upgrade Joomla.

## Data di rilascio

Ogni manifest distribuito include `<creationDate>YYYY-MM-DD</creationDate>` uniforme. Nelle build di sviluppo il valore predefinito è la data del commit Git (oppure `SOURCE_DATE_EPOCH` se fornito) quando il checkout Git è disponibile; nel job PHP container della CI la data UTC viene fissata una sola volta e passata a ogni invocazione di build tramite `--release-date`. È **deterministico** a parità di data passata e contenuto del checkout. Durante un rilascio ufficiale, il workflow passa esplicitamente `--release-date` con la data UTC dell'effettiva preparazione del rilascio. Non confondere la data con quella di installazione o di compilazione degli ZIP, che rimane fissa per la riproducibilità.

## Verifiche automatiche

`tests/extension-manifest-metadata.py` apre lo ZIP del pacchetto, quindi i suoi 12 ZIP inclusi, e verifica per ciascuno versione, data, nome pubblico e tipo/gruppo; il workflow CI esegue il controllo su build sviluppo e stabile. Il controllo fallisce per ogni divergenza. La CI esegue gli smoke esistenti sull'installazione e l'aggiornamento Joomla con dati conservati. **La conferma dei valori effettivamente mostrati in Gestione estensioni dopo l'aggiornamento sul sito di prova resta un test manuale obbligatorio**: la fixture dell'installer non è una prova sufficiente dell'aggiornamento dei manifest cache in un ambiente esistente.

Esempi:

```sh
python3 tools/build.py --release-date 2026-11-17
python3 tests/extension-manifest-metadata.py --zip dist/pkg_jlms-0.57.7-dev.zip --version 0.57.7 --date 2026-11-17
python3 tools/build.py --release-version 1.0.0 --release-date 2026-11-17
python3 tests/extension-manifest-metadata.py --zip dist/pkg_jlms-1.0.0.zip --version 1.0.0 --date 2026-11-17
```

### Collaudo manuale Joomla

1. Eseguire un aggiornamento installando l'archivio Joomla estratto dall'artifact della PR, senza disinstallare la versione precedente.
2. In **Sistema → Gestisci → Estensioni**, filtrare per JD Learn e verificare pacchetto, componente, libreria e plugin: medesima versione e data, denominazioni `JD Learn - ...` uniformi e nessuna estensione duplicata.
3. Verificare che le estensioni e i provider restino abilitati/disabilitati come prima e che i corsi, gli attestati e le configurazioni rimangano funzionanti.
4. Ripetere il controllo su un'installazione pulita e con interfaccia in italiano e in inglese.

## Convenzioni di gestione PR

Ogni nuova PR JD Learn (funzionalità, bugfix o documentazione) deve avere la milestone **1.0.0** finché è destinata alla baseline stabile. Assegnare esattamente una label di priorità **P0/P1/P2** quando pertinente, in base all'impatto e non all'anzianità. Usare **documentation** sulle PR di sola documentazione. La descrizione PR deve contenere **Comportamenti da testare**; aggiornare matrice requisiti e documentazione pertinente, mantenendo changelog e README nei rispettivi ruoli. Non integrare con controlli CI falliti.

