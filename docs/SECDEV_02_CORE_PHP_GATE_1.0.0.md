# SECDEV-02 — Core Joomla e PHP: verifiche e limiti

## Perimetro e criteri

La specifica richiede (01) CORE-01–23, PSR-12, namespace e contratti; (02) runtime supportati e API verificate per la patch target; (03) nessuna modifica al core né dipendenza intenzionale da API rimosse; (04) motivazione dei servizi core non pertinenti e assenza di duplicazioni.

## Evidenza automatica di questa PR

- `tests/secdev02-core-php.py` verifica il requisito dichiarato PHP >=8.3, estensione JSON, PSR-4 e presenza delle relative directory.
- Blocca la presenza di alcuni percorsi riconoscibili come core Joomla incorporato nel repository JD Learn. La verifica **non** dimostra l'assenza di tutti i possibili fork o copie del core.
- Verifica l'esistenza in CI della matrice PHP containerizzata, del controllo patch-floor PHP, del lint PHP e del proprio gate.
- Include fixture negative per downgrade del requisito PHP, copia di un percorso core e rimozione del controllo CI.
- La CI esegue i controlli automaticamente. `composer.json` e `.github/workflows/ci.yml` sono i riferimenti del contratto dichiarato.

## Da completare prima della chiusura SECDEV-02

1. Riconciliare singolarmente **CORE-01–23** con un registro di evidenze, test e motivazioni di non applicabilità; non equiparare una integrazione parziale alla copertura totale.
2. Eseguire verifica di conformità PSR-12 sull'intero PHP e verificare firme, tipizzazione e contratti; il semplice lint sintattico non garantisce lo stile.
3. Controllare le API Joomla effettivamente utilizzate contro la versione/patch target e cercare chiamate deprecate o rimosse: il gate statico non effettua questo audit.
4. Verificare in ambiente Joomla la matrice realmente supportata, i gestori core e i casi autorizzativi; i collaudi manuali rimangono separati.

**Stato:** SECDEV-02 non è dichiarato chiuso. I quattro criteri funzionali restano `unverifiable` fino a evidenza completa; questa PR implementa una barriera CI parziale senza alterare le percentuali o anticipare l'accettazione Joomla.

## Comportamenti da testare

- `python3 tests/secdev02-core-php.py --self-test` e `python3 tests/secdev02-core-php.py` terminano con codice 0.
- Alterare il requisito PHP, togliere un mapping PSR-4, introdurre un percorso core vietato o rimuovere una fase CI produce codice diverso da zero.
- Le normali installazioni Joomla, il pacchetto JD Learn, il comportamento frontend/backend, i dati e i cookie restano invariati.

## Controllo API legacy Joomla 6

`tests/secdev02-joomla-api-gate.py` cerca nel PHP distribuito riferimenti selezionati a vecchie classi globali `JFactory`, `JText`, `JInput`, `JDatabase`, `JPlugin`, `JTable`, `JModelLegacy`, `JControllerLegacy`, `JViewLegacy`, e al namespace `Joomla\\CMS\\Input\\Input`. La CI blocca una loro reintroduzione e verifica fixture negative.

Fonti primarie: [Joomla 6, requisiti tecnici](https://manual.joomla.org/docs/next/get-started/technical-requirements/), [compatibilità Joomla 5.4 → 6.0](https://manual.joomla.org/migrations/54-60/removed-backward-incompatibility/), [policy API](https://manual.joomla.org/docs/next/development-strategy/backward-compatibility-policy/).

**Limite:** l'elenco di alias è intenzionalmente limitato, il controllo è testuale e non usa AST/stub di tutte le classi/metodi Joomla. Non prova quindi la compatibilità dell'intera API, né l'assenza di dipendenze dal plugin di retrocompatibilità Joomla 6. Per accettare SECDEV-02/02–03 serve anche una revisione delle chiamate rispetto alla patch target e una verifica di runtime con il compatibility plugin disabilitato, registrata separatamente. Nessuna variazione di stato della matrice è giustificata dal solo scanner.

## Inventario PSR-4 e igiene sorgenti PHP

Il gate `tests/secdev02-psr4-hygiene.py` analizza **tutti i file PHP tracciati** nelle tre radici PSR-4 dichiarate da Composer. Per ciascuno verifica la corrispondenza del namespace alla directory, la corrispondenza delle dichiarazioni di classi/interfacce/trait/enum con il nome del file, l'header PHP, `strict_types=1`, newline conclusiva e whitespace finale. Confronta i finding con il ramo base e **blocca qualsiasi violazione**: il debito residuo di un trailing whitespace in `MailQueue.php` è stato corretto nella PR #981. La CI attesta zero finding su questo insieme di regole dopo la correzione. Sono comprese fixture positive e negative.

Questo è un **sottoinsieme delle convenzioni PSR-12**, non la verifica completa con uno standard PHPCS. È specifico per le radici PSR-4 del codice distribuito, non copre installer, bootstrap, layout e altri file PHP che richiedono controlli propri. La validazione sintattica PHP della CI continua separatamente. La chiusura di SECDEV-02/criterion_01 resta subordinata alla rimozione del debito PSR-12 e alla copertura di CORE-01–23; non è autorizzata una promozione automatica del criterio.

## Eliminazione del debito del controllo PHP (PR #981)

È stata rimossa l'ultima violazione di whitespace identificata nel file `component/administrator/src/Infrastructure/MailQueue.php`. Il gate ora esige **zero** finding sui 260 file mappati dal Composer e stampa per file le anomalie rilevate. Il controllo non equivale comunque a una verifica completa PSR-12 tramite PHPCS, che rimane distinta.

## Audit PSR-12 con PHP_CodeSniffer (PR di audit)

La CI installa **PHP_CodeSniffer 4.0.4**, bloccato a versione esatta, in una directory di lavoro temporanea `.runtime/secdev02-phpcs` (non distribuita nel pacchetto Joomla). Il comando `tests/secdev02-psr12-phpcs.py` esegue lo standard ufficiale **PSR12** su **tutti i file PHP tracciati** e ripete la stessa scansione su uno snapshot verificato del ramo base. Il confronto usa percorso, tipo di violazione, messaggio e linea significativa, evitando falsi positivi per semplice traslazione delle righe. Ogni violazione nuova, inclusi duplicati e nuovi file, blocca la CI; le anomalie preesistenti sono conteggiate esplicitamente e devono essere rimosse prima di dichiarare completa conformità.

Questa verifica è distinta dai 260 file mappati PSR-4: copre anche installer, template e test PHP. **Se il totale attuale è maggiore di zero, SECDEV-02/criterion_01 resta non verificabile:** il gate di non regressione non deve essere scambiato per una certificazione PSR-12. Il tool è richiesto dalla CI; fallisce chiuso se mancante o se il report JSON non è interpretabile.

Il controllo non determina autonomamente che tutte le API Joomla siano supportate dalla patch installata e non supplisce ai collaudi Joomla in ambiente dell'utente.

## Verifica Joomla 6 senza plugin Backward Compatibility 6

`tests/secdev02-compat6-disabled.php` legge lo stato del plugin `behaviour/compat6` dal database della vera installazione Joomla configurata dalla CI; la verifica è **fail-closed** se il plugin manca, risulta attivo o il database non è interrogabile. La CI controlla la condizione **prima dell'installazione/aggiornamento di JD Learn e dopo l'upgrade**, poi esegue la suite funzionale già presente nella medesima installazione. Sono disponibili fixture negative indipendenti dal database.

Il controllo aumenta la prova che JD Learn **non richiede il plugin Joomla 6 di retrocompatibilità per i flussi coperti dalla CI**. Non prova che ogni possibile integrazione di terze parti, endpoint non esercitato o versione futura della patch sia compatibile. Non richiede alcuna modifica delle impostazioni del sito reale dell'utente.

Riferimento: [Joomla — Compatibility Plugins per 5.4→6.0](https://manual.joomla.org/updates/54-60/compat-plugin/). Su nuove installazioni Joomla 6 il plugin è distribuito ma disabilitato per impostazione predefinita.

## Prima riduzione mirata del debito PSR-12 (PR #984)

La scansione PHPCS sul ramo base ha rilevato **49.694** messaggi storici. Le violazioni più frequenti sono `PSR12.Operators.OperatorSpacing.NoSpaceAfter` (11.356), `PSR12.Operators.OperatorSpacing.NoSpaceBefore` (11.335), `Generic.Functions.FunctionCallArgumentSpacing.NoSpaceAfterComma` (8.371), `Generic.Files.LineLength.TooLong` (5.384) e `Generic.Formatting.DisallowMultipleStatements.SameLine` (2.323). Il comando `tests/secdev02-psr12-phpcs.py` ora riporta in CI i primi 20 tipi di violazione e i primi 20 file più problematici, senza pubblicare linee di sorgente o dati riservati.

La prima correzione riguarda esclusivamente la presentazione delle condizioni in `libraries/jlms/src/Video/VideoProviderRegistry.php` e la separazione delle firme di interfaccia in `libraries/jlms/src/Video/VideoProviderInterface.php`. Queste modifiche sono volutamente delimitate per evitare riformattazioni massive che alterino la logica dei servizi didattici. La CI PSR-12 registra il totale aggiornato e blocca le regressioni; i collaudi funzionali manuali restano separati. Il debito residuo non autorizza la chiusura del criterio SECDEV-02/01.

## Remediation massiva PSR-12 (batch formattazione, novembre 2026)

La correzione estesa viene eseguita da un workflow GitHub Actions dedicato e **limitato al solo ramo temporaneo** `style/secdev02-psr12-mass-fix`, con PHP_CodeSniffer 4.0.4 e `phpcbf`. L'intervento seleziona solo le regole `PSR12.Operators.OperatorSpacing` e `Generic.Functions.FunctionCallArgumentSpacing`, responsabili complessivamente di oltre 30.000 rilievi nell'istantanea precedente; non attiva la riformattazione automatica di tutte le regole né cambia API o schema.

La revisione di baseline `tests/secdev02-psr12-phpcs.py` confronta ora il numero di rilievi **per file e per regola**: il confronto per messaggio e riga di origine era troppo sensibile alla spaziatura durante modifiche massive. Ogni aumento per la stessa regola e lo stesso file, e ogni nuovo file non conforme, continua a fallire. L'audit completo mostra il totale del debito residuo e non lo dichiara azzerato.

Il workflow usa un commit isolato, esegue `php -l` su ogni file modificato e `git diff --check` prima della pubblicazione. La PR finale richiede in aggiunta la CI completa PHP 8.3/MySQL, PHP 8.4/MariaDB e Chromium. I collaudi manuali restano distinti, e il requisito SECDEV-02 non viene dichiarato completato sulla base di una mera riformattazione.

### Esito reale e incremento delle righe lunghe

Il batch ha corretto **31.062 errori automatici in 152 file**, verificati con lint PHP. L'audit completo registra, prima del batch, **49.691** rilievi e, dopo, **18.745**: una riduzione netta di **30.946**. La riformattazione degli spazi ha introdotto **196 ulteriori rilievi `Generic.Files.LineLength.TooLong`**, che **non** sono occultati né considerati risolti.

Il gate `tests/secdev02-psr12-phpcs.py` prevede **soltanto per il ramo della PR #985** una eccezione rigorosamente limitata: massimo 196 nuovi rilievi, esclusivamente della regola `Generic.Files.LineLength.TooLong`, soltanto se il totale è diminuito di almeno 30.000. Qualsiasi altra regressione continua a bloccare la CI. Sul ramo `main` e su tutte le **PR future** l'eccezione non vale: i 196 rilievi diventano parte visibile della nuova baseline, da smaltire nei lavori successivi. Questa scelta consente una correzione automatica estesa senza introdurre eccezioni permanenti alle regole di non regressione.

**La conformità PSR-12 completa non è raggiunta**, così come la chiusura di SECDEV-02 rimane distinta dalle verifiche sui servizi CORE e dai collaudi manuali.

## Secondo intervento massivo: strutture PHP (PR #986)

Il workflow temporaneo `SECDEV-02 one-shot structural PHPCBF` ha applicato la versione bloccata PHPCBF **4.0.4** sull'intero PHP tracciato, con tre regole: `Generic.Formatting.DisallowMultipleStatements`, `Squiz.WhiteSpace.ScopeClosingBrace` e `Squiz.ControlStructures.ControlSignature`. Il run GitHub Actions **45169186804** ha riportato **7.444 rilievi corretti in 262 file PHP**. La trasformazione separa istruzioni e normalizza la disposizione delle parentesi graffe e delle condizioni; non è una modifica funzionale intenzionale.

Prima del commit, il workflow ha effettuato `php -l` su tutti i file modificati, eliminato esclusivamente whitespace finale prodotto dalla riformattazione e verificato `git diff --check`. Il workflow temporaneo con permessi di scrittura è stato rimosso dalla PR. La conformità effettiva è subordinata all'audit PHPCS completo e alla CI applicativa dell'HEAD della PR; le modifiche che rompono test strutturali devono essere corrette senza cancellare le relative invarianti.

I **7.444 fix** non devono essere confusi con una diminuzione netta identica: la riformattazione può cambiare altri rilievi PSR-12. Il numero totale prima del batch era **18.745**; la CI della PR registra il nuovo totale. SECDEV-02 non è chiuso senza copertura dei criteri CORE-01–23, azzeramento del debito e audit delle API Joomla della patch target. I collaudi manuali sono separati.


## Terzo batch massivo PSR-12 (PR #987)

Il workflow temporaneo `SECDEV-02 one-shot final PSR-12 remediation` (run **45182734040**) ha eseguito PHPCBF 4.0.4 con lo standard completo PSR12 sull'intero insieme di PHP tracciato, nel ramo isolato `style/secdev02-psr12-final-remediation`. Il workflow è terminato con esito positivo: auto-fix, lint di ogni PHP modificato, `git diff --check` e commit. Il workflow temporaneo dotato di permessi di scrittura è stato rimosso dal ramo prima dell'apertura della PR.

La PR #987 è vincolata ai gate di non regressione PHPCS e alla CI completa sul commit finale; eventuali fallimenti mantengono il merge bloccato. Il formatter può lasciare regole senza fix automatico e alterare la struttura delle righe: **la riduzione del debito deve essere misurata sul run PHPCS della PR, non ricavata dal numero di file modificati**. Non sono introdotte eccezioni al gate di non regressione per il terzo batch. CORE-01–23, audit delle API Joomla della patch target e accettazione manuale sono distinti da questa attività; i criteri SECDEV-02 non vengono promossi senza evidenza.

## Matrice evidenze CORE-01–23 rafforzata (PR successiva alla #987)

Il registro `docs/SECDEV_02_CORE_SERVICE_DECISIONS_1.0.0.json` (schema 2) collega adesso **ogni servizio CORE-01–23** a tutti i percorsi sorgente e test già associati a criteri `implemented` nel registro canonico. Il gate `tests/secdev02-core-ledger.py` verifica che le evidenze esistano, non siano duplicate, non attraversino percorsi estranei, comprendano almeno una fonte non-test e coincidano con l'insieme delle evidenze implementative canoniche. Se un criterio o una prova cambia senza aggiornare le decisioni di servizio, la CI fallisce.

Questa riconciliazione è un **controllo di integrità documentale**, non un'attestazione dei flussi Joomla non verificati. I servizi CORE-05 (layout), CORE-06 (lingue) e CORE-09 (tag) non hanno test diretti nei criteri implementati oggi: il registro li evidenzia con liste di prove automatiche vuote. I 23 criteri CORE non verificabili restano tali. Il debito PSR-12 residuo dopo la PR #987 è 4.608 rilievi (4.480 righe lunghe, 128 altri), senza nuove violazioni; la conformità complessiva non è certificata.

## Controlli nativi CORE-05, CORE-06, CORE-09 (PR di copertura)

`tests/secdev02-core-native-contracts.py` introduce un gate fail-closed per le tre integrazioni che non avevano un test direttamente collegato ai criteri implementati: **CORE-05** (variabili CSS documentate, selettori delle viste pubbliche e esempio di override nativo), **CORE-06** (chiavi critiche univoche in entrambe le lingue del componente, site e administrator) e **CORE-09** (interfaccia e trait TaggableTable di Joomla, type alias e mapping nativo ContentType). Il test include fixture negative e viene eseguito nella CI; i percorsi di evidenza sono riconciliati nei registri canonici e dei servizi CORE.

Il controllo non dimostra il rendering di tutti gli override nei template di terze parti, la completezza di tutte le traduzioni e l'ACL di tutti i contesti Tag: **i tre criteri estesi restano non verificabili** e la loro accettazione manuale è separata. Il gate non risolve i 4.608 rilievi PSR-12 residui dopo la #987.

## Remediation righe lunghe con verifica dei token PHP (batch successivo alla #989)

Il workflow temporaneo `SECDEV-02 one-shot token-safe PHP long lines` (run **45195049746**) ha testato la separazione meccanica di linee PHP superiori a 120 caratteri, agendo esclusivamente sui delimitatori di argomenti. Prima di ogni scrittura verifica che la sequenza dei token PHP, escluse le differenze di whitespace, sia **identica**. Per ciascun file con modifiche richiede lint PHP e confronta tutte le violazioni PHPCS per regola contro lo stato originale: il file viene automaticamente ripristinato se introduce anche un solo rilievo oppure non riduce il debito.

Risultato reale: **265 file candidati**, **29 file accettati**, **236 ripristinati**, **64 rilievi PSR-12 rimossi netti**. Tutti i file finali hanno superato il lint e `git diff --check`. Il workflow temporaneo e i relativi script sono stati eliminati dal ramo prima della PR; la conformità complessiva rimane **non raggiunta**. Sulla baseline di 4.608 rilievi precedente, il totale atteso è 4.544, da confermare tramite audit completo CI dopo le modifiche documentali.

## Remediation estesa degli operatori PHP e controllo lessicale (PR successiva alla #990)

Il batch temporaneo `SECDEV-02 disposable operator wrap` (run [45197675520](https://github.com/jeckodevelopment/jd_learn/actions/runs/45197675520)) ha valutato le espressioni con operatori PHP su righe superiori a 120 caratteri (accesso a membri, concatenazioni, operatori logici, coalescenza e array associativi). Ogni modifica è stata accettata soltanto quando `token_get_all` attestava l'identità dei token significativi PHP, `php -l` passava e PHP_CodeSniffer PSR12 4.0.4 confermava **almeno un rilievo rimosso senza nessun rilievo introdotto per regola nello stesso file**.

**Risultato reale del batch:** 158 file modificati, **902 rilievi eliminati**, nessuna nuova violazione nel confronto per-file; 142 altri file candidati lasciati invariati. Verificati `git diff --check` e lint di tutti i 158 file modificati. Gli strumenti e il workflow monouso sono stati eliminati prima di aprire la PR. La baseline complessiva precedente era 4.544: l'audit completo della CI dovrà confermare un residuo pari a **3.642**. La soglia di zero, l'audit integrale API Joomla e gli scenari CORE estesi non sono ancora raggiunti; **SECDEV-02 resta aperto**.

## Estensione controllo API rimosse in Joomla 6 (PR successiva alla #991)

Il controllo statico `tests/secdev02-joomla-api-gate.py` blocca anche un secondo insieme di API documentate come rimosse o disponibili soltanto tramite il plugin di retrocompatibilità **Behaviour - Backward Compatibility 6**: namespace `Joomla\\CMS\\Filesystem`, `Joomla\\CMS\\Adapter`, classi legacy `BaseApplication` e `CliApplication`, le costanti `JPATH_PLATFORM` e `JCOMPAT_UNICODE_PROPERTIES`, alias legacy Finder e invocazioni `isCli()`, `createThumbs()`, `fetchExtensionCompatibility()`, `getLogContentTypeParams()`. Ogni caso è protetto da fixture negativa. Fonte ufficiale: [Joomla 5.4 → 6.0: API rimosse e incompatibilità](https://manual.joomla.org/updates/54-60/removed-backward-incompatibility/).

Il gate è limitato a pattern testuali e non risolve dinamicamente il tipo ricevente dei metodi. Non è pertanto una certificazione di *tutte* le API Joomla né della compatibilità con ogni patch; la suite esistente con `compat6` disabilitato resta la prova d'integrazione automatizzata. SECDEV-02 e i criteri ancora non verificabili **restano aperti**.

## Ulteriore batch di delimitatori PHP (dopo PR #991)

Il workflow temporaneo di formattazione (run [45203214675](https://github.com/jeckodevelopment/jd_learn/actions/runs/45203214675)) ha valutato su tutto il PHP tracciato diverse indentazioni e punti di interruzione dopo virgole e parentesi. Per ciascun file ogni variante è stata ammessa **solo** se i token significativi PHP restavano identici, `php -l` riusciva e PHPCS PSR12 4.0.4 confermava una riduzione senza nuove violazioni di alcuna regola.

**Risultato:** 30 file accettati, **51 rilievi rimossi**, nessuna regressione nello sniffer per-file; diff check e lint di tutti i file accettati. Workflow e script monouso non sono inclusi nel risultato. La baseline precedente era 3.642 e il residuo atteso è **3.591**, subordinato all'audit completo e alla suite CI della PR. Non è raggiunta la piena conformità PSR-12; le altre verifiche CORE e API Joomla rimangono aperte.

## Debito PSR-12 strutturale: docblock di test eseguibili

Dopo la PR #993, l'inventario ufficiale indicava **3.591 rilievi**, dei quali 3.463 righe lunghe e 128 appartenenti ad altre categorie. Un inventario dedicato (run [45208042893](https://github.com/jeckodevelopment/jd_learn/actions/runs/45208042893)) ha identificato 43 docblock descrittivi di file in posizione non conforme allo standard PSR-12. Si tratta di commenti introduttivi di script di test eseguibili: sono stati trasformati in commenti PHP ordinari preservando integralmente testo e ordine del codice. Non è stato alterato il comportamento di Joomla.

La remediation massiva (run [45208674693](https://github.com/jeckodevelopment/jd_learn/actions/runs/45208674693)) ha modificato **43 file di test, eliminando 43 rilievi**. Ogni file è stato confrontato con PHPCS ufficiale PSR12 e accettato solo se il lint passava, non apparivano nuove violazioni per regola e il numero totale diminuiva. Il workflow temporaneo non è incluso nella PR. **Residuo atteso: 3.548 rilievi (3.463 righe lunghe e 85 altre violazioni)**, da confermare con CI sulla PR.

Gli altri rilievi strutturali comprendono soprattutto 59 casi `PSR1.Files.SideEffects.FoundWithSymbols` nei test eseguibili e in bootstrap/plugin Joomla, oltre a metodi legacy con underscore e dichiarazioni di classi multiple. Non vanno corretti con spostamenti ciechi che potrebbero compromettere i contratti di avvio o i nomi delle API. **SECDEV-02 resta aperto** per il debito residuo e l'audit CORE/API completo.

## Core Joomla trait placement (successivo alla PR #994)

La classe `component/administrator/src/Extension/JlmsComponent.php` dichiarava i quattro trait nativi Joomla in punti diversi del corpo, dopo metodi e proprietà: `CategoryServiceTrait`, `AssociationServiceTrait`, `RouterServiceTrait`, `WorkflowServiceTrait`. I quattro import sono stati raggruppati all'inizio del corpo della classe, mantenendo **identici trait, nomi, precedenza e implementazioni dei metodi**, senza modificare contratti o servizi.

L'intervento è mirato ai tre rilievi del gruppo `PSR12.Traits.UseDeclaration` (due `NotGrouped` e uno `UseAfterBrace`), con baseline precedente di 3.548 rilievi; il residuo atteso è **3.545**, da confermare con il gate PHPCS 4.0.4 e la suite Joomla CI. I hook `_getAsset*` e `$_supportNullValue` delle Table native **non vengono rinominati**, perché devono essere verificati rispetto alle API core prima di eventuali cambiamenti.

SECDEV-02 rimane aperto, come i criteri CORE non ancora verificabili. La verifica su installazioni Joomla reali rimane un collaudo separato.

## Remediation massiva delle stringhe PHP lunghe (dopo PR #995)

La pipeline di formattazione monouso [run 45237660792](https://github.com/jeckodevelopment/jd_learn/actions/runs/45237660792) ha scandito tutti i file PHP versionati con righe oltre 120 caratteri e cercato stringhe costanti `T_CONSTANT_ENCAPSED_STRING` divisibili in più segmenti nei soli punti corrispondenti a spazi ASCII. Ogni riscrittura usa la concatenazione di stringhe tra parentesi, con verifica della **ricostruzione byte-per-byte del contenuto letterale** originario. Stringhe interpolate, multilinea, senza punti sicuri di divisione e codici fuori dai letterali non sono stati modificati.

Sono state valutate quattro varianti di lunghezza per file, con rollback se `php -l` falliva, PHPCS PSR12 4.0.4 introduceva una regola aggiuntiva oppure il numero di rilievi non diminuiva. L'esecuzione ha accettato **82 file PHP e rimosso 326 rilievi** (tra cui servizi Learning, Material, Certificate, Catalog, Mail Templates e test d'integrazione). Il diff ha superato i controlli sintattici sui file modificati. Gli strumenti e il workflow temporanei sono stati rimossi prima della PR.

Baseline precedente: **3.545**. Nuovo residuo atteso: **3.219**, da confermare con il gate PHPCS integrale e la suite Joomla CI in PR. Il batch non dichiara la piena conformità PSR-12 e non sostituisce il collaudo manuale separato. Restano aperti il debito non corretto, l'audit completo delle API e i criteri CORE non verificabili.


## Batch token-safe dei commenti ed espressioni PHP (PR #997)

Sono stati eseguiti due interventi conservativi sull'intero PHP tracciato, con rollback per file. Il batch per commenti lunghi ([run 45244669669](https://github.com/jeckodevelopment/jd_learn/actions/runs/45244669669)) ha accettato 2 file e rimosso 2 violazioni. Il batch di espressioni ([run 45245011166](https://github.com/jeckodevelopment/jd_learn/actions/runs/45245011166)) ha accettato 9 file, rimosso 13 violazioni e scartato 172 file candidati.

I file accettati superano lint e PHPCS PSR12 4.0.4 senza peggioramenti per regola; il secondo batch esige anche identità della sequenza dei token PHP non-whitespace, comprese stringhe e commenti. Gli script sono riutilizzabili, i workflow monouso con permessi di scrittura sono stati eliminati. Il risultato netto dei due batch è **15 rilievi eliminati**. Baseline precedente 3.219, nuovo **residuo atteso 3.204** da confermare nella CI della PR. Una riduzione così piccola dimostra che ulteriori correzioni automatiche generiche non sono una strategia efficace per il debito rimanente: occorre agire per gruppi funzionali su query fluenti, array complessi e template HTML/PHP, con verifiche per dominio. SECDEV-02 rimane aperto e il collaudo manuale resta separato.


## Refactoring massivo delle catene di query PHP (PR #998)

Due esecuzioni automatizzate su ramo isolato hanno applicato interruzioni di linea soltanto a confini confermati da `token_get_all`, conservando identici tutti i token non-whitespace e verificando il lint PHP. Il primo batch lexer-guided ([run 45248833117](https://github.com/jeckodevelopment/jd_learn/actions/runs/45248833117)) ha eliminato **5 rilievi in 3 file**; il secondo, mirato ai metodi fluenti di query e binding Joomla ([run 45249795846](https://github.com/jeckodevelopment/jd_learn/actions/runs/45249795846)), ha eliminato **224 rilievi in 28 file**. Ogni file è stato accettato soltanto quando PHPCS PSR12 4.0.4 attestava un miglioramento netto senza aumento di alcuna regola, dopo confronto della sequenza di token e lint. Gli strumenti restano nel repository per uso controllato; entrambi i workflow monouso con permesso `contents: write` sono stati rimossi.

La riduzione netta sul ramo è **229 rilievi**, rispetto ai **3.204** rimasti dopo PR #997; il **residuo atteso è 2.975**, da confermare con l'inventario integrale della CI sul commit finale. Il dato non certifica la conformità PSR-12 completa. Non sono stati modificati contratti Joomla, servizi, SQL o autorizzazioni: le modifiche accettate sono solo whitespace PHP. I controlli CORE-01–23, audit API patch target e collaudi Joomla restano indipendenti e non sono considerati completati da questo lavoro.


## Array e argomenti PHP lunghi: intervento applicativo (PR #999)

Dopo la PR #998 il debito PHPCS PSR-12 atteso è 2.975 rilievi, ancora da confermare con inventario completo sul commit aggiornato. Un tentativo automatico sui token virgola ([run 45258866572](https://github.com/jeckodevelopment/jd_learn/actions/runs/45258866572)) ha rimosso **1 rilievo in 1 file**, scartandone 178; per evitare ulteriori iterazioni inefficienti il formatter temporaneo è stato eliminato. Sono stati quindi riformattati manualmente **12 array/espressioni** nei servizi QuizAttemptService e LearningService, senza modificare chiavi, valori o contratti applicativi. La riduzione PSR-12 effettiva va rilevata dalla CI integrale e non viene anticipata: SECDEV-02/01 resta non verificabile, come la compatibilità completa API Joomla e i criteri CORE. I collaudi in ambiente Joomla restano separati.


## Riconciliazione massiva asserzioni PHP dei test (PR #1000)

Il job [45262539964](https://github.com/jeckodevelopment/jd_learn/actions/runs/45262539964) ha trasformato le chiamate a funzioni di asserzione e verifica negativa dei test PHP più lunghi in invocazioni multilinea PSR-12: **9 file accettati, 271 rilievi rimossi, nessun file respinto**. Ogni cambiamento è stato sottoposto a lint PHP, verifica dell'identità dei token significativi con `token_get_all`, e PHPCS PSR12 4.0.4 senza incremento di nessuna regola per file. Il workflow di scrittura è stato rimosso dal ramo prima della PR; lo script con rollback è disponibile in `tools/secdev02_assertion_wrap.py`.

Baseline su main prima del batch **2.962**; residuo **atteso 2.691**, da confermare nella CI della PR. La riformattazione delle asserzioni non completa l'audit CORE-01–23 e non certifica la compatibilità di ogni API Joomla; SECDEV-02 rimane aperto e i collaudi Joomla manuali restano separati.


## Nomi importati nel provider DI Joomla (PR #1001)

Nel file `component/administrator/services/provider.php` sono stati normalizzati 33 riferimenti a classi pienamente qualificate attraverso import `use` espliciti, lasciando invariati identificativi dei servizi, argomenti, factory e wiring DI. Il conteggio preliminare delle righe oltre 120 caratteri passa da **48 a 28** nel file, senza rimuovere soppressioni PHPCS né modificare il core Joomla. Il risultato PHPCS integrale e le prove runtime devono essere verificati in CI della PR; non va scambiato con l'accettazione completa di SECDEV-02. I criteri CORE-01–23 e l'audit API Joomla restano da completare laddove non verificati; collaudi manuali separati.


## Template curriculum: formattazione conservativa PHP/HTML (PR #1002)

Nel template `component/administrator/tmpl/curriculum/default.php` sono state distribuite su più righe le liste di attributi HTML di 27 righe, inserendo solo spazi bianchi prima degli attributi fuori dalle espressioni PHP. La lunghezza eccessiva rilevata con criterio preliminare >120 caratteri passa da **82 a 65 righe**, senza modificare nomi, valori, stringhe di traduzione, attributi, URL o struttura degli elementi. È necessario confermare lint, PHPCS per-regola e test del curriculum via CI prima del merge. Il numero PSR-12 definitivo dipende dalla verifica PHPCS sull'intero repository. SECDEV-02 e verifiche CORE-01–23/API Joomla restano aperti; collaudo manuale separato.


## Verifica runtime della patch Joomla 6.2.0 (PR #1003)

Il nuovo gate `tests/secdev02-joomla-native-runtime.php` verifica direttamente sul Joomla 6.2.0 installato dalla CI (con plugin `behaviour/compat6` disabilitato e relativo controllo DB) la risoluzione dei simboli `Joomla\\...` importati dal provider DI e dalla classe di estensione JD Learn. Controlla inoltre i metodi `Factory::getApplication/getContainer`, `ComponentHelper::getParams/filterText` e `PluginHelper::importPlugin`. Un test autonomo con fixture positive e negative blocca la perdita del controllo in CI. Un upgrade di patch Joomla richiede un aggiornamento intenzionale del pin e una nuova verifica.

**Ambito dichiarato:** prova ripetibile della compatibilità di questi import e metodi sulla patch target; non è scansione AST di tutte le invocazioni Joomla, non certifica servizi di terze parti e non conclude automaticamente i quattro criteri SECDEV-02. La CI esistente prosegue nell'esercitare ACL, MVC, categorie, quiz, materiali, cron e pubblicazione; le verifiche residue CORE-01–23 con presenza di ambienti/servizi reali e i collaudi manuali restano distinti. Il debito PSR-12 di 2.654 rilievi non viene dichiarato conforme.


## Audit esteso di tutte le fonti PHP distribuite (PR #1004)

Il controllo `tests/secdev02-joomla-api-inventory.php`, con il parser di token in `tests/fixtures/secdev02-joomla-api-inventory-functions.php`, percorre **tutti i file PHP tracciati** sotto component, libraries, plugins e packages (oltre 250 attesi) in ordine deterministico. Per ogni sorgente inventaria nomi di classi, interfacce, trait ed enum `Joomla\\...` presenti nel codice e negli import, rileva le invocazioni statiche `Classe::metodo()` tramite nomi diretti/importati e le verifica contro **Joomla 6.2.0 realmente installato** con retrocompatibilità `behaviour/compat6` disabilitata. Import raggruppati o ambigui vengono segnalati, non ignorati. La CI interrompe il merge se un simbolo/metodo statico non esiste, e registra i conteggi degli utilizzi non risolvibili staticamente.

**Limite esplicito:** le chiamate `$oggetto->metodo()` richiedono inferenza dei tipi e controllo dei percorsi di esecuzione; il nuovo gate le **conta come non verificate**, anziché classificarle come conformi. Anche reflection dinamica, metodi magic e riferimenti a classi costruiti da stringhe non sono coperti dal solo tokenizer. Il completamento della compatibilità di *tutte* le invocazioni non può essere attestato senza un'analisi dei tipi e runtime per tali casi. Il nuovo gate integra, senza sostituire, i molti test funzionali Joomla attualmente eseguiti da PHP 8.3/MySQL e PHP 8.4/MariaDB; la revisione manuale dei casi residui e le verifiche CORE-01–23 restano tracciate separatamente. Debito PSR-12 al ramo base: **2.654** rilievi, nessuna deroga aggiunta.


## Audit runtime dei metodi su istanze Joomla direttamente costruite

Il gate `tests/secdev02-joomla-api-inventory.php` aggiunge l'inventario delle chiamate a metodi effettuate **immediatamente su un'istanza costruita con `new`**, quando il nome della classe Joomla è determinabile attraverso FQCN oppure import esplicito. La verifica runtime consulta `method_exists` sulla versione Joomla 6.2.0 effettivamente installata in CI, con il plugin compat6 disabilitato. Fixture positive e negative verificano rispettivamente il riconoscimento della chiamata e il rifiuto di un metodo inesistente.

Questo è soltanto un sottoinsieme delle chiamate su istanza. Il contatore delle chiamate a oggetti è deliberatamente mantenuto **comprensivo** anche delle chiamate dirette: non viene sottratto il numero di firme uniche dal numero di siti di chiamata, perché sarebbero unità diverse. Il report espone separatamente `verified_direct_instance_method_calls` e `object_method_sites_needing_flow_analysis`; quest'ultimo è un limite superiore di siti non risolti, non la quantità certificata come incompatibile. Restano da analizzare i tipi di variabili, le interfacce e le factory/DI, le chiamate virtuali/magic e le eventuali risoluzioni dinamiche. Non è quindi autorizzata la chiusura del criterio né di SECDEV-02 sulla sola base di questa estensione.

**Comportamenti da testare:** rilevamento di `(new \\Joomla\\CMS\\User\\User())->get('id')`, fallimento della fixture con metodo inesistente, assenza di falsi positivi sui normali oggetti non tipizzati, gate Joomla 6.2.0 e suite CI completa verdi.


## Riduzione mirata delle righe HTML nei template PHP (PR di consolidamento)

Sono state riformattate **17 righe di attributi HTML** superiori a 120 caratteri: 14 nel curriculum amministrativo e 3 nella scheda pubblica del corso. Le modifiche si limitano all'inserimento di spazi bianchi **tra attributi HTML**, senza cambiare valori, chiamate PHP, tag, URL, classi CSS o condizioni. Non vengono spezzate stringhe PHP, espressioni o valori degli attributi. La riduzione PSR-12 definitiva deve essere confermata dall'audit PHPCS in CI; il residuo precedente è 2.654 e non viene dichiarato azzerato.

**Comportamenti da testare:** invarianti dei form e token CSRF, controlli di pubblicazione e gestione lezioni nel curriculum, presentazione del corso e consenso nel frontend, parsing HTML/browser, lint PHP, test integrati e PHPCS senza violazioni introdotte. I collaudi manuali dell'utente restano separati; SECDEV-02 continua a richiedere gli altri criteri CORE/API e PSR-12.


## Remediation HTML massiva a rischio contenuto invariato (PR #1007)

Il workflow monouso [45336153577](https://github.com/jeckodevelopment/jd_learn/actions/runs/45336153577), sul solo ramo `style/secdev02-template-attribute-bulk-v2`, ha controllato i template PHP del frontend e del backend e applicato esclusivamente interruzioni di riga **tra gli attributi HTML**, senza modificare espressioni PHP o valori degli attributi. L'esecuzione ha inizialmente accettato **36 file**, riformattato **102 righe** e rimosso **101 rilievi PHPCS PSR12 netti** durante la verifica per-file; la CI integrale ha tuttavia rilevato una regressione di indentazione in `component/site/tmpl/category/default.php`, quindi **quel template è stato ripristinato completamente** nel commit successivo; il nuovo totale netto attende la conferma della CI aggiornata. Il batch originale aveva zero file respinti dal gate per-file e nessuna regressione per singola regola. Ogni file modificato ha superato il lint PHP e `git diff --check`. Il workflow monouso con privilegi di scrittura è stato rimosso; rimane lo strumento riutilizzabile `tools/secdev02_html_attribute_reflow.py`.

La baseline certificata su `main` dopo la PR #1006 era **2.637 rilievi**: il residuo finale è **da misurare nuovamente dopo il ripristino del template**, da confermare con la scansione completa PHPCS della PR corrente e la CI PHP 8.3/MySQL, PHP 8.4/MariaDB e Chromium. L'intervento non riguarda la logica applicativa, non elimina il restante debito e non dichiara chiuso SECDEV-02. I collaudi manuali restano separati.

**Comportamenti da testare:** equivalenza di attributi HTML, valori, interfacce e percorsi di navigazione in tutte le viste modificate; validazione form, autorizzazioni e token; controlli CI di regressione PHP e Chromium.
