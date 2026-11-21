# SEC-P2 — Hardening runtime e supply chain per JD Learn 1.0.0

**Build di riferimento:** 0.64.18-dev, 21 novembre 2026. **Stato: SEC-P2/criterion_01 e criterion_02 implementati e riesaminati sul perimetro del software JD Learn; gate di accettazione e pubblicazione sull'hosting aperti.** Il progetto non considera sufficienti un test statico o una CI verde per attestare la sicurezza di un'installazione reale.

## Superfici ed evidenze disponibili

| Controllo | Evidenza implementativa | Rischio residuo / condizione di chiusura |
|---|---|---|
| Dipendenze incluse e inventario | `tools/sec_p2_dependency_gate.py` + fixture `tests/sec-p2-dependency-gate.py`: inventario deterministico dei 12 child ZIP, blocco di folder vendor/node_modules/third-party, lockfile non recensiti e librerie binarie (PHP PHAR, JAR, WASM ecc.), gate nel builder dev e stabile | La distribuzione Joomla resta codice primo-party; dipendenze **di piattaforma** PHP/Joomla/DB/ClamAV/ffprobe e SDK remoti non sono contenute nel ZIP e richiedono revisioni versionate separate |
| Package e segreti distribuiti | `tools/sec_p2_package_scan.py`: ZIP Joomla e 12 costituenti, traversal, symlink, collisioni nomi case-insensitive, drive Windows, chiavi e pattern token, budget cumulativo di decompressione/entry anche negli archivi annidati; `tests/sec-p2-package-scan.py` negative fixtures | Il confronto con advisory delle librerie e uno scanner completo esterno restano necessari sulla release candidata |
| Sorgente runtime e workflow | `tools/sec_p2_source_gate.py` e fixture test: PHP/XML/JS/INI/SQL/CSS/JSON distribuiti; firme chiavi/token; GitHub Actions vincolate a SHA; `requirements-csv.yml` corretto | Il gate a pattern non prova assenza di segreti non standard, rotazione di credenziali o sicurezza dell'intera storia Git |
| Execution e remote sinks | `tests/sec-p1-systematic-source-gate.py` censisce process runner ffprobe e trasporto HTTPS Cloudflare; controller e plugin con gate statici | Eseguire prove network/TLS e negative test reali per host provider su versioni supportate |
| Dati sensibili e logging | Errori provider genericizzati, coda email minimizzata e redatta nelle PR precedenti; token di consenso con anti-referrer/no-store | Provare log PHP, Joomla, SMTP, reverse proxy e backup in ambiente reale, con account di prova |
| Storage locali | `ProtectedVideoStorage` richiede root privata; `tools/audit_legacy_video_storage.py` conta file legacy e symlink senza modificarli | Il titolare deve verificare file legacy su hosting effettivo, mitigare accesso HTTP e documentare eventuale migrazione |
| CSP, cookie, iframe video | Guide `VIDEO.md`, `CLOUDFLARE_STREAM.md`, `PRIVACY_COOKIES.md` | La policy CSP finale è di sito Joomla e template/CMP: verificare `frame-src`, `connect-src` e `script-src` solo per provider abilitati, non sostituire globalmente la policy del sito con header statici della componente |
| Webhook | Nessun listener webhook provider richiesto dalla baseline 1.0.0; Cloudflare usa API client/server e un endpoint fisso, senza URL remoto configurabile dall'utente | Confermare che la build candidata non aggiunga nuovi handler webhook, che richiederebbero autenticazione, anti-replay e test separati |

## Inventario dipendenze e confini di fiducia

JD Learn distribuisce **codice proprio e provider Joomla**, senza Composer/npm runtime installati lato Joomla dall'estensione. Questo non esclude dipendenze del sito: core Joomla, PHP, MariaDB/MySQL, librerie di sistema, ClamAV/ffprobe facoltativi e SDK/iframe esterni YouTube, Vimeo e Cloudflare. La CI utilizza dipendenze *di test* separate dalla ZIP Joomla, per esempio Playwright, pacchetti del runner e immagini container. Versioni e advisory vanno verificati a ogni release, anche quando una dipendenza non è bundlata. Non dedurre l'assenza di CVE dall'assenza di `composer.lock`.

**Gate di release da eseguire:** (1) build riproducibile e scansione dello ZIP; (2) scan dell'intera cronologia Git o verifica privata equivalente per segreti, con rotazione credenziali se trovati; (3) verifica di advisory e severità per runtime/runner/provider/versioni effettive; (4) prova sul sito Joomla di headers, CSP, log, webroot e video legacy; (5) inventario findings critical/high, motivazioni e approvazione proprietario; (6) registrazione delle prove in QA, senza dati personali o credenziali.

## Consolidamento del builder (21/11/2026)

La scansione SEC-P2 è ora un **vincolo del builder**, eseguito su `data` prima della scrittura del file ZIP e del checksum, sia con `python3 tools/build.py` sia con `--release-version`. Il preflight conserva fixture negative per link simbolici ZIP, nomi duplicati o case-fold equivalenti, drive Windows e directory traversal; i limiti byte/entry sono cumulativi fra ZIP e costituenti, per ridurre il rischio di ZIP bomb nidificate. Il validator non espone nomi di file o dati che contengano segreti. Un package non conforme blocca la build senza produrre uno ZIP nuovo.

**Non equivale a SEC-P2 di rilascio chiuso:** la suite riconosce pattern noti, non tutti i segreti; non sono state ancora acquisite evidence di scansione della cronologia Git, SCA advisory del runtime effettivamente installato, CSP/log/header/storage sul sito di prova, né il registro finale di severità e risk acceptance. Il rilascio stabile resta bloccato. Per PHP 8.3/8.4, le versioni corrette del 19 novembre sono 8.3.37 e 8.4.28, già controllate in CI; va verificata anche la versione effettiva sull'hosting.

## Verifica automatizzata della storia Git (21/11/2026)

La CI della PR esegue `tests/sec-p2-history-scan.py` e `tools/sec_p2_history_scan.py` sull'intera cronologia raggiungibile dai riferimenti fetchati (`fetch-depth: 0`). Ispeziona i blob storici, anche eliminati, sotto `component/`, `plugins/`, `libraries/`, `packages/` e `.github/workflows/`; rileva firme note di chiavi private, credenziali AWS/GitHub e impedisce la prosecuzione se compaiono finding o blob non ispezionabili. I log espongono soltanto conteggi, mai nomi o payload. I test includono una credenziale fittizia prima committata, poi sovrascritta ed eliminata, e una fixture workflow. Questa è una scansione di firme note: non prova l'assenza di credenziali di formato arbitrario, né sostituisce l'eventuale rotazione dei segreti compromessi. La verifica non richiede interventi sul sito Joomla.

**Separazione dei gate:** le verifiche su hosting, CSP e logs reali rimangono nel collaudo e nel gate di rilascio. Quelle verifiche non impediscono di completare separatamente l'implementazione dei controlli automatici già sviluppati, quando il criterio ha evidenze sufficienti.

## Inventario verificabile del package (21/11/2026)

Il controllo automatizzato `tools/sec_p2_dependency_gate.py` legge il package Joomla **in memoria**, enumera esattamente i 12 costituenti previsti, ispeziona ogni archivio figlio e rifiuta automaticamente dipendenze vendorizzate non censite, manifest/lockfile package-manager e moduli binari aggiunti senza modifica esplicita del gate. È richiamato da `tools/build.py` prima della scrittura dell'artefatto e dal test strutturale `tools/check.py`. Le fixture negative includono pacchetti aggiuntivi, file vendor, node_modules, composer.lock, package-lock.json e binari WASM.

La policy attuale è **first-party-only**: ogni nuova dipendenza vendorizzata deve motivare una modifica specifica dell'inventario e la review degli advisory prima di essere distribuita. Il package non include dipendenze Composer/npm runtime, ma Joomla, PHP, database, ffprobe, ClamAV e gli SDK esterni non sono compresi in questa garanzia; la versione effettiva dell'hosting e le relative CVE restano parte del gate di accettazione. Le versioni pubblicate PHP 8.3.37 e 8.4.28 del 19/11/2026 sono già applicate come soglia in CI. Una CI verde non prova assenza di finding sull'infrastruttura dell'utente.

## Cosa collaudare su Joomla

1. Installare o aggiornare lo ZIP della CI su hosting di prova: HTTPS, cookie di sessione, MIME, CSP del template, player YouTube/Vimeo/Cloudflare e player HTML5, includendo rifiuto del consenso.
2. Simulare risposte di errore provider e SMTP; verificare che browser, `logs`, webserver e coda amministrativa non includano Bearer token, JWT, URL di revoca o indirizzi nelle eccezioni tecniche.
3. Eseguire audit read-only di storage legacy e verificare che nessun oggetto video privato sia raggiungibile con GET/HEAD/Range diretto su Apache e Nginx; documentare solo conteggi ed esito, non nomi o percorsi.
4. Rivedere segreti/protezioni di GitHub Actions, permessi minimi, integrazione FTPS, pin delle action e ambiente di pubblicazione senza stampare credenziali.
5. Documentare per **ogni** finding pertinente severità, componente e versione, decisione (corretto/accettato motivatamente), test e riferimento alla release candidate. Il gate non può chiudersi se mancano queste evidenze.

**Decisione per la PR:** la presenza dei controlli automatici conferma una mitigazione software specifica, non la chiusura definitiva di SEC-P2. Le prove live e l'analisi di vulnerabilità esterna non sono state eseguite da questa PR. Il contatore dei requisiti non viene aumentato senza criterio e prova ammissibili.

## Aggiornamento advisory 20/11/2026 — PHP patch floor

La consultazione delle fonti pubbliche sul candidato ha rilevato il bollettino del 19/11/2026: per i due minor utilizzati dalla CI sono disponibili PHP **8.3.37** e **8.4.28**, con correzioni di sicurezza. Fonte primaria: [archivio PHP 2026](https://www.php.net/archive/2026.php); [versioni supportate](https://www.php.net/supported-versions.php). Il controllo `tests/sec-p2-php-security-floor.php` fa fallire le matrici Joomla qualora il runner sia fermo a una patch precedente; non modifica l'intervallo di compatibilità generale dell'estensione né dimostra quale PHP sia installato sul sito di prova.

**Esito e limite:** questo è un controllo puntuale dei livelli patch pubblicati, non una dichiarazione che tutte le CVE correnti di PHP, Joomla, componenti OS o provider siano state esaminate. Il responsabile dell'hosting deve verificare le versioni *effettivamente installate* (incluse distribuzioni con backport) e allegare un riscontro redatto. In caso di backport formalmente documentato, registrare eccezione e prova senza disabilitare il gate dei runner CI.

In data 20/11/2026 la fonte ufficiale di [roadmap Joomla](https://developer.joomla.org/roadmap.html) pianifica 6.2.1 per il 24/11/2026; non anticipare la disponibilità di una versione futura. Joomla 6.2.0 rimane la fixture CI, e il sito reale deve essere controllato separatamente alla data del rilascio stabile.

## Decisione audit implementazione 21/11/2026

Il criterio SEC-P2/criterion_01 riguarda la predisposizione e l'effettiva implementazione dell'hardening software (dipendenze, segreti, package, logging, CSP/provider) ed è ora classificato **implemented** nella matrice, sulla base di builder fail-closed, test negativi, source sink gate, history-scan, floor PHP CI e inventario dipendenze. Il controllo degli header/CSP configurati dal template Joomla, di log esterni e di versioni del provider installato su hosting resta nel **collaudo separato**, non nel completamento di questo requisito di codice. Il criterio SEC-P2/criterion_02 è **implemented** per la review conclusiva dei finding critici/alti confermati nel software distribuito, con registro verificato dalla CI e contromisure tracciate; i rischi esterni non verificati restano release blocker e non vengono dichiarati mitigati. Nessuna di queste decisioni attesta la sicurezza di un'installazione non esaminata.

## Registro strutturato dei finding (21/11/2026)

`SEC_P2_FINDINGS_1.0.0.json` e `SEC_P2_FINDINGS_1.0.0.md` riportano la revisione dei finding di codice con mitigazioni e test, i rischi non accertati sulle installazioni preesistenti e gli advisory del core Joomla di novembre 2026. Il gate `tests/sec-p2-findings-gate.py` rifiuta l'assenza di prove e la falsa promozione dei finding high/critical esterni. SEC-P2/criterion_02 è completato nell'audit dell'implementazione del codice distribuito con finding noti mitigati. La verifica del sito effettivo, degli upstream e dell'eventuale webroot legacy rimane vincolo separato di accettazione e pubblicazione, e un finding confermato su tali sistemi impedisce la release.
