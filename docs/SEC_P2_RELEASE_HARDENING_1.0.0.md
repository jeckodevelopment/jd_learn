# SEC-P2 — Hardening runtime e supply chain per JD Learn 1.0.0

**Build di riferimento:** 0.64.17-dev, 20 novembre 2026. **Stato del gate: aperto, revisione documentata senza certificazione.** Il progetto non considera sufficienti un test statico o una CI verde per attestare la sicurezza di un'installazione reale.

## Superfici ed evidenze disponibili

| Controllo | Evidenza implementativa | Rischio residuo / condizione di chiusura |
|---|---|---|
| Package e segreti distribuiti | `tools/sec_p2_package_scan.py`: ZIP Joomla e 12 estensioni, traversal, chiavi e pattern token, limiti decompressione; `tests/sec-p2-package-scan.py` negative fixtures | Il confronto con advisory delle librerie e uno scanner completo esterno restano necessari sulla release candidata |
| Sorgente runtime e workflow | `tools/sec_p2_source_gate.py` e fixture test: PHP/XML/JS/INI/SQL/CSS/JSON distribuiti; firme chiavi/token; GitHub Actions vincolate a SHA; `requirements-csv.yml` corretto | Il gate a pattern non prova assenza di segreti non standard, rotazione di credenziali o sicurezza dell'intera storia Git |
| Execution e remote sinks | `tests/sec-p1-systematic-source-gate.py` censisce process runner ffprobe e trasporto HTTPS Cloudflare; controller e plugin con gate statici | Eseguire prove network/TLS e negative test reali per host provider su versioni supportate |
| Dati sensibili e logging | Errori provider genericizzati, coda email minimizzata e redatta nelle PR precedenti; token di consenso con anti-referrer/no-store | Provare log PHP, Joomla, SMTP, reverse proxy e backup in ambiente reale, con account di prova |
| Storage locali | `ProtectedVideoStorage` richiede root privata; `tools/audit_legacy_video_storage.py` conta file legacy e symlink senza modificarli | Il titolare deve verificare file legacy su hosting effettivo, mitigare accesso HTTP e documentare eventuale migrazione |
| CSP, cookie, iframe video | Guide `VIDEO.md`, `CLOUDFLARE_STREAM.md`, `PRIVACY_COOKIES.md` | La policy CSP finale è di sito Joomla e template/CMP: verificare `frame-src`, `connect-src` e `script-src` solo per provider abilitati, non sostituire globalmente la policy del sito con header statici della componente |
| Webhook | Nessun listener webhook provider richiesto dalla baseline 1.0.0; Cloudflare usa API client/server e un endpoint fisso, senza URL remoto configurabile dall'utente | Confermare che la build candidata non aggiunga nuovi handler webhook, che richiederebbero autenticazione, anti-replay e test separati |

## Inventario dipendenze e confini di fiducia

JD Learn distribuisce **codice proprio e provider Joomla**, senza Composer/npm runtime installati lato Joomla dall'estensione. Questo non esclude dipendenze del sito: core Joomla, PHP, MariaDB/MySQL, librerie di sistema, ClamAV/ffprobe facoltativi e SDK/iframe esterni YouTube, Vimeo e Cloudflare. La CI utilizza dipendenze *di test* separate dalla ZIP Joomla, per esempio Playwright, pacchetti del runner e immagini container. Versioni e advisory vanno verificati a ogni release, anche quando una dipendenza non è bundlata. Non dedurre l'assenza di CVE dall'assenza di `composer.lock`.

**Gate di release da eseguire:** (1) build riproducibile e scansione dello ZIP; (2) scan dell'intera cronologia Git o verifica privata equivalente per segreti, con rotazione credenziali se trovati; (3) verifica di advisory e severità per runtime/runner/provider/versioni effettive; (4) prova sul sito Joomla di headers, CSP, log, webroot e video legacy; (5) inventario findings critical/high, motivazioni e approvazione proprietario; (6) registrazione delle prove in QA, senza dati personali o credenziali.

## Cosa collaudare su Joomla

1. Installare o aggiornare lo ZIP della CI su hosting di prova: HTTPS, cookie di sessione, MIME, CSP del template, player YouTube/Vimeo/Cloudflare e player HTML5, includendo rifiuto del consenso.
2. Simulare risposte di errore provider e SMTP; verificare che browser, `logs`, webserver e coda amministrativa non includano Bearer token, JWT, URL di revoca o indirizzi nelle eccezioni tecniche.
3. Eseguire audit read-only di storage legacy e verificare che nessun oggetto video privato sia raggiungibile con GET/HEAD/Range diretto su Apache e Nginx; documentare solo conteggi ed esito, non nomi o percorsi.
4. Rivedere segreti/protezioni di GitHub Actions, permessi minimi, integrazione FTPS, pin delle action e ambiente di pubblicazione senza stampare credenziali.
5. Documentare per **ogni** finding pertinente severità, componente e versione, decisione (corretto/accettato motivatamente), test e riferimento alla release candidate. Il gate non può chiudersi se mancano queste evidenze.

**Decisione per la PR:** la presenza dei controlli automatici conferma una mitigazione software specifica, non la chiusura definitiva di SEC-P2. Le prove live e l'analisi di vulnerabilità esterna non sono state eseguite da questa PR. Il contatore dei requisiti non viene aumentato senza criterio e prova ammissibili.
