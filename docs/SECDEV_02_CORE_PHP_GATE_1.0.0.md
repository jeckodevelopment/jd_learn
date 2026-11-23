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
