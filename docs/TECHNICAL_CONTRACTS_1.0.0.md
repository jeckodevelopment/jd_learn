# JD Learn 1.0.0 — contratti tecnici e procedure riproducibili (DOC-04)

> Documento tecnico versionato relativo al sorgente di sviluppo 0.64.17. Non costituisce accettazione della release 1.0.0 né collaudo dell'installazione Joomla.

## Confini architetturali

Il package Joomla è identificato da `packages/pkg_jlms.xml` (`pkg_jlms`); il manifest dichiara un componente `com_jlms`, la libreria `lib_jlms` e dieci plugin: quattro video (`jlmsvideo`: youtube, vimeo, cloudflarestream, html5), privacy, task, system, finder, user e actionlog. Il package contiene **12** costituenti (libreria, componente, dieci plugin), oltre al manifest esterno. Gli identificativi Joomla (group/element) sono contratti di installazione e aggiornamento: non rinominarli per modificare il nome di visualizzazione.

Il componente contiene la parte amministrativa sotto `component/administrator/` (MVC, servizi applicativi, dominio e gestione di stato) e la parte pubblica sotto `component/site/` (controller/modelli, layout e navigazione). Gli asset condivisi e il codice browser sono in `component/media/`. I plugin di integrazione Joomla sono distribuiti separatamente all'interno del package e devono conservare i propri controlli ACL, token e confini di responsabilità. I provider video implementano contratti specifici documentati nel rispettivo pacchetto: non confondere eventi del player con avanzamento autorevole persistito sul server.

## Contratti da considerare stabili alla 1.0.0

| Superficie | Sorgente di verità | Invariante e rischio |
| --- | --- | --- |
| Identità package e children | `packages/pkg_jlms.xml`; `tools/build.py` | `pkg_jlms`, `com_jlms` e gli element/group dei plugin restano invariati tra upgrade |
| Versione e data di rilascio | `packages/pkg_jlms.xml`; `component/jlms.xml`; `tools/build.py` | manifest del package/componente coerenti; metadati child validati durante la build |
| Provider video | manifest e guide locali dei quattro plugin `jlmsvideo`; `docs/VIDEO.md` | consenso e accesso verificati; playback non concede privilegi né modifica autonomamente il completamento |
| Endpoint e autorizzazioni | `docs/specifications/Servizi_endpoint_LMS_Joomla_1.0.md`; controller amministrativi e pubblici | POST e token per mutazioni, ACL rivalutate, risposta senza dati riservati |
| Dati e persistenza | install/upgrade SQL nel componente; modelli e servizi applicativi | lo schema installato si modifica tramite migrazioni idempotenti; non inventare colonne a partire da documentazione non verificata |
| Dom e override frontend | contratto UI-07 e viste in `component/site/tmpl/` | classi e ID pubblici versionati; override Joomla possono richiedere adeguamento documentato |
| Email Joomla | `docs/MAIL_TEMPLATES_1.0.0_APPROVED.md` | dizionario dei token canonico; nessun alias legacy introdotto in nuove integrazioni |
| Attestati PDF | `docs/CERTIFICATES_1.0.0.md` | regole della versione pubblicata e ACL applicate all'emissione e al download |
| Sicurezza e privacy | `docs/SECURITY_PRIVACY_RELEASE_GATE_1.0.0.md` | nessuna prova automatica sostituisce verifiche manuali o audit runtime completi |

### Architettura dei dati: come ottenere evidenza verificabile

La struttura effettiva del database dipende dall'ultima migrazione applicata sul sito. Per un inventario attendibile, usare esclusivamente file SQL di installazione/upgrade e query in **sola lettura** su installazione di test; documentare tabella, colonna, indice, chiave/relazione applicativa e file di migrazione che l'ha creata. Non dedurre chiavi esterne SQL da semplici riferimenti PHP.

1. Localizzare il DDL nel repository, ad esempio `find component -type f \( -name '*.sql' -o -name '*.php' \) | sort`, poi isolare i file SQL e gli eventuali step di migrazione.
2. Su database **di prova**, con prefisso Joomla corrente, usare `SHOW TABLES LIKE '<prefix>%';`, `SHOW CREATE TABLE <tabella>;` e `SHOW INDEX FROM <tabella>;` soltanto dopo aver verificato il nome della tabella. Non eseguire DDL durante la ricognizione.
3. Per ogni cambiamento di schema confrontare installazione nuova e upgrade da versione precedente; acquisire i diff schema e verificare che non vengano eliminate iscrizioni, progressi, attestati o coda email.
4. Registrare risultati e ambiente in `docs/QA_TESTBOOK.md` o nell'evidenza di accettazione; se non sono stati eseguiti, riportare esplicitamente **pendente**.

Questo paragrafo è una procedura di verifica, non un inventario dei dati già accettato.

## Procedure tecniche riproducibili

Prerequisiti: checkout esatto del commit candidato, Python 3, PHP e ambienti database Joomla conformi alla matrice della CI. Registrare sempre `git rev-parse HEAD`, versione PHP/Joomla e checksum del package; non inserire credenziali in log o commit.

1. Verificare il candidato: `python3 tools/check.py`. In una PR la politica vigente è in `docs/CI_POLICY.md`: workflow leggero in bozza, suite completa sugli ambienti standard e browser per candidato runtime pronto; per gate di rilascio usare anche `matrix=full` quando richiesto.
2. Produrre un pacchetto sviluppo: `python3 tools/build.py`. In un checkout Git la data della build deriva dal commit, per mantenere la riproducibilità. Il pacchetto da installare è il file interno `pkg_jlms-<VERSION>-dev.zip`, **non** l'archivio wrapper GitHub Actions.
3. Verificare integrità: confrontare SHA-256 del file ZIP con `SHA256SUMS.txt` estratto dallo stesso artefatto. Non confondere l'hash dell'archivio Actions con quello dello ZIP Joomla.
4. Installare/aggiornare soltanto su un'istanza di prova con backup; controllare nell'elenco Estensioni il package e tutti i costituenti, poi eseguire scenari ACL, iscrizioni, ripresa, provider, coda email, attestati e rollback documentati in `docs/QA_TESTBOOK.md`.
5. Preparare, **senza pubblicare**, una candidata stabile: `python3 tools/build.py --release-version 1.0.0` solo quando il gate lo consente. La procedura di produzione, il feed HTTPS e il trasporto FTPS esplicito sono disciplinati da `docs/RELEASE_PROCEDURE.md`; il comando locale non autorizza un rilascio.
6. Prima del merge controllare l'head verificato dalla CI. Dopo il merge registrare commit SHA, run, risultato e checksum in `docs/IMPLEMENTATION_HISTORY.md`. Ricalcolare i contatori canonici con `python3 tools/functional_audit.py --sync` soltanto quando i criteri sono sostenuti da evidenze, quindi eseguire `--check`.

## Prove di accettazione tecniche ancora necessarie

- Installazione pulita e upgrade con dati preesistenti; backup e ripristino effettivo.
- Verifica dei manifest e di tutte le dodici estensioni figlie; mantenimento delle impostazioni degli adapter/plugin.
- Controllo dello schema reale delle due modalità di installazione (nuova e aggiornata), degli indici e dei vincoli applicativi.
- Validazione API/ACL/CSRF di controller e plugin con ruoli diversi e sessioni revocate.
- Prove reali di YouTube, Vimeo, Cloudflare Stream e HTML5, consenso e regressioni.
- Accessibilità delle viste, compatibilità degli override, coda email/SMTP e attestati PDF.
- Integrità dei pacchetti, update feed, FTPS e gate finale prima del rilascio.

**Tracciamento DOC-04:** questo documento fornisce una mappa tecnica e procedure eseguibili; le verifiche runtime e l'accettazione manuale rimangono distinte. Non promuovere criteri del ledger o percentuali soltanto perché il documento è presente.
