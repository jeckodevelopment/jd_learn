# JD Learn 0.64.21-dev — riferimento tecnico per manutentori (DOC-04)

> Ambito: implementazione della **build di sviluppo 0.64.21-dev** letta nel repository. Non è un verbale di accettazione della 1.0.0. I comandi che modificano un sito sono indicati come operazioni amministrative da eseguire solo dopo un backup; non eseguire SQL di esempio su produzione.

## 1. Pacchetto e punto d'ingresso

Il manifest canonico [`packages/pkg_jlms.xml`](../packages/pkg_jlms.xml) definisce il package Joomla `pkg_jlms` e la versione numerica `0.64.21`. La build di sviluppo usa il suffisso **di distribuzione** `-dev`, non un diverso numero di manifest ([policy](VERSIONING.md)). Il generatore è [`tools/build.py`](../tools/build.py); il test strutturale è [`tools/check.py`](../tools/check.py).

| Artefatto child del package | Origine nel repository | Responsabilità |
| --- | --- | --- |
| `lib_jlms.zip` | [`libraries/jlms`](../libraries/jlms) | Contratti PHP condivisi, eventi, provider e registry video |
| `com_jlms.zip` | [`component`](../component) | Frontend, amministrazione, servizi applicativi, schema e script upgrade |
| `plg_jlmsvideo_{youtube,vimeo,cloudflarestream,html5}.zip` | [`plugins/jlmsvideo`](../plugins/jlmsvideo) | Adattatori playback, uno per provider |
| `plg_privacy_jdlearn.zip` | [`plugins/privacy/jdlearn`](../plugins/privacy/jdlearn) | Integrazione Joomla Privacy |
| `plg_task_jlms.zip` | [`plugins/task/jlms`](../plugins/task/jlms) | Task Scheduler nativo |
| `plg_system_jlmsmenu.zip` | [`plugins/system/jlmsmenu`](../plugins/system/jlmsmenu) | Integrazione menu di sistema |
| `plg_finder_jlms.zip` | [`plugins/finder/jlms`](../plugins/finder/jlms) | Indicizzazione Joomla Finder |
| `plg_user_jdlearnconsent.zip` | [`plugins/user/jdlearnconsent`](../plugins/user/jdlearnconsent) | Consenso dell'utente |
| `plg_actionlog_jdlearn.zip` | [`plugins/actionlog/jdlearn`](../plugins/actionlog/jdlearn) | Log delle azioni |

I nomi effettivi dei **12 archivi child** di questa versione sono: `lib_jlms.zip`, `com_jlms.zip`, `plg_jlmsvideo_youtube.zip`, `plg_jlmsvideo_vimeo.zip`, `plg_jlmsvideo_cloudflarestream.zip`, `plg_jlmsvideo_html5.zip`, `plg_privacy_jdlearn.zip`, `plg_task_jlms.zip`, `plg_system_jlmsmenu.zip`, `plg_finder_jlms.zip`, `plg_user_jdlearnconsent.zip` e `plg_actionlog_jdlearn.zip`.

**Non installare solo `com_jlms`** come upgrade completo. Scaricare l'artefatto Actions della build, estrarlo e scegliere **lo ZIP interno `pkg_jlms-0.64.21-dev.zip`**; l'archivio Actions esterno non è installabile in Joomla. Verificare `SHA256SUMS.txt` incluso nella distribuzione come descritto nella [procedura di rilascio](RELEASE_PROCEDURE.md). Nessuna pubblicazione stable 1.0.0 è implicata da una CI verde.

## 2. Servizi, eventi e plugin video

Il service provider del componente [`component/administrator/services/provider.php`](../component/administrator/services/provider.php) registra i servizi applicativi e coordina i provider; la logica della pubblicazione è in [`PublicationService.php`](../component/administrator/src/Application/PublicationService.php), l'accesso al catalogo in [`CatalogService.php`](../component/administrator/src/Application/CatalogService.php), il ciclo delle lezioni in [`LearningService.php`](../component/administrator/src/Application/LearningService.php). Per accesso diretto a controller e modelli usare le convenzioni Joomla, non invocare metodi applicativi come endpoint non autorizzati.

Il contratto video non è una stringa libera:
- [`VideoProviderInterface`](../libraries/jlms/src/Video/VideoProviderInterface.php) definisce il descrittore e le capacità dichiarate del provider.
- [`VideoProviderRegistryInterface`](../libraries/jlms/src/Video/VideoProviderRegistryInterface.php) espone `register(ProviderDescriptor, Closure)`, `get(providerKey)` e l'inventario descrittori. Le factory sono *lazy*: la registrazione non deve effettuare chiamate remote.
- [`VideoProviderRegistry`](../libraries/jlms/src/Video/VideoProviderRegistry.php) impone unicità della chiave e contratto major coerente; una chiave non registrata genera un errore esplicito. Non sostituire l'errore con un altro provider.
- L'evento applicativo di registrazione è `onJlmsRegisterVideoProviders`, definito in [`RegisterVideoProvidersEvent`](../libraries/jlms/src/Event/RegisterVideoProvidersEvent.php). I plugin Joomla si iscrivono via `SubscriberInterface`, ad esempio [YouTube](../plugins/jlmsvideo/youtube/src/Extension/Youtube.php). Il bootstrap Joomla importa il gruppo `jlmsvideo`.
- I quattro manifest di provider supportati nella distribuzione sono YouTube, Vimeo, Cloudflare Stream e HTML5; **un plugin non presente nel manifest non fa parte della baseline**.

Il controllo di accesso, l'eventuale consenso e il salvataggio del progresso non vanno delegati alla pagina remota del provider. I payload non devono includere API key, token server o segreti. Per aggiungere un adattatore seguire il [contratto e i ruoli documentati](VIDEO_PROVIDER_ROLES_1.0.0.md), mantenere il major supportato e aggiungere test per chiavi duplicate, provider mancanti e registrazione senza rete.

Gli altri gruppi di plugin (`task`, `privacy`, `system`, `finder`, `user`, `actionlog`) sono integrazioni Joomla *distinte* dal contratto video; aggiornare i rispettivi manifest, service provider e prove senza presupporre che si registrino nell'evento video.

## 3. Schema, installazione e integrità

La fonte per il DDL iniziale è [`component/administrator/sql/install.mysql.utf8mb4.sql`](../component/administrator/sql/install.mysql.utf8mb4.sql); le installazioni/upgrade passano per [`component/script.php`](../component/script.php) e [`SchemaInstaller`](../component/administrator/src/Infrastructure/SchemaInstaller.php). Il prefisso `#__` è sostituito dal driver Joomla: non usare nomi di tabella fissi e non eseguire il file SQL manualmente sul sito.

| Dominio dati | Tabelle principali nel DDL | Invariante di manutenzione |
| --- | --- | --- |
| Catalogo/editor | `#__jlms_courses`, `#__jlms_course_versions` | Il corso punta a una versione corrente; una bozza non sovrascrive lo snapshot pubblicato |
| Programma | `#__jlms_modules`, `#__jlms_lessons` | Le lezioni e le regole appartengono a una specifica versione |
| Iscrizioni/progresso | `#__jlms_enrollments` e tabelle di progressi | L'accesso richiede proprietario, stato di iscrizione e regole correnti; la cancellazione non deve alterare lo storico |
| File e attestati | Tabelle materiali, file e certificati nel medesimo DDL | L'accesso ai byte è controllato lato server; file privati non sono URL pubblici |
| Comunicazioni | Tabelle outbox/consensi nel DDL | Non duplicare eventi di invio e rispettare consenso, ACL e stato attuale |

Per il **diagramma completo** di ogni tabella e indice leggere sempre il DDL corrispondente al **commit e manifest installati**. Gli script di integrazione testano schema, FKs e upgrade su MySQL 8.4/MariaDB 10.6; non inferire da questi un restore reale già provato. Il DDL usa InnoDB, UTC, `utf8mb4_unicode_ci`; le tabelle versionate e le chiavi esterne preservano i riferimenti didattici. Nessuno script di manutenzione deve cancellare direttamente dati di utenti, attestati o progressi per “riparare” l'interfaccia.

## 4. Procedure riproducibili (versionate dalla commit SHA)

Per ciascuna operazione registrare **SHA del commit, versione manifest, run CI, ambiente Joomla/PHP/DB, evidenza prima/dopo e risultato osservato**. Le procedure sotto sono controlli di manutenzione, non test manuali già eseguiti.

### A. Controllo sorgenti, package e regressioni prima di una PR

Da una checkout locale *pulita* sul commit da verificare, con Python 3 e le dipendenze dichiarate dal progetto:

```sh
python3 tools/build.py
python3 tools/check.py
python3 tools/functional_audit.py --check
python3 tests/requirements-status.py
```

Verificare che il nome e la versione nel report di build corrispondano a `packages/pkg_jlms.xml`, che i 12 componenti siano presenti e che il gate `functional_audit.py --check` non segnali drift nel CSV/JSON. Per un aggiornamento della **classificazione funzionale**, modificare prima il registro dei criteri con motivazione e sorgenti, poi eseguire `python3 tools/functional_audit.py --sync`, esaminare il diff dei file generati e infine `--check`. Non editare a mano un report generato.

### B. Verifica integrata e download del package

Aprire una PR con milestone 1.0.0, label di priorità e sezioni obbligatorie, inclusi i comportamenti Joomla da provare. Su candidato runtime, attendere preflight, Chromium e le due coppie di integrazione CI documentate in [`CI_POLICY.md`](CI_POLICY.md); su PR solo documentale la suite può essere ridotta. L'esito va riferito al **commit HEAD verificato**, non a una PR successivamente modificata.

Dall'interfaccia Actions scaricare `JD-Learn-<version>-dev`, estrarre il contenitore, verificare `SHA256SUMS.txt` contro lo ZIP interno e installare soltanto il package `pkg_jlms` in un sito Joomla di prova. Non pubblicare nel feed stabile le versioni di sviluppo.

### C. Backup, upgrade e rollback in ambiente di prova

1. Registrare versione e stato dei plugin, esportare **database e file**, incluse le directory private gestite; custodire i backup fuori dalla directory servita dal web. Definire un punto di ripristino prima di iniziare.
2. Installare il **package completo**, mai singoli child ZIP in ordine arbitrario. Verificare manifest, schemi, task Scheduler, privacy, provider attivi e configurazioni senza riabilitare automaticamente plugin disattivati.
3. Provare corso pubblicato, iscrizione, aula, progressi, anteprima e attestato contro i dati preesistenti. Annotare risultati ed errori in `docs/QA_TESTBOOK.md` e nel gate release.
4. Se l'upgrade non è recuperabile, ripristinare **database e filesystem come coppia consistente** dal backup, quindi verificare un campione di iscrizioni, contenuti e attestati. Non tentare un downgrade selettivo dello schema, né cancellare tabelle manualmente.

Consultare [procedura di rilascio](RELEASE_PROCEDURE.md) e [update server](JOOMLA_UPDATE_SERVER_1.0.md) per le verifiche FTPS/TLS, il feed e il gate stabile **non ancora autorizzato**.

### D. Scheduler e troubleshooting senza perdita di dati

In Joomla amministrazione controllare il plugin `task/jlms`, il task preconfigurato, la sua abilitazione, le date ultima/prossima esecuzione e l'invocazione del Scheduler nativo. Seguire [`MAIL_SCHEDULER_SETUP.md`](MAIL_SCHEDULER_SETUP.md) per test di recapito, code, errori e riconciliazione. Non dichiarare completato un invio dal solo trigger; verificare il risultato dei worker e il template core.

Per errori 403/404 su corsi o materiali: controllare identità, ACL Joomla, stato corso e versione, finestra, iscrizione e proprietario; **non modificare** gli ACL per “risolvere” un record inesistente. Per player video: verificare provider abilitato, consensi, policy iframe, configurazione CSP e disponibilità remota senza inserire token nei log. Per attestato PDF in attesa: controllare worker, stato elaborazione, logo e formato supportato, output protetto e errori di filesystem; non esporre il PDF in una directory pubblica. Per upgrade fallito: preservare log/backup e ripristinare la coppia DB/file come sopra.

## 5. Controllo di versione del riferimento

Questo documento rappresenta **0.64.21-dev**, non un contratto universale per ogni futura versione. A ogni modifica di manifest, contratto provider, nomi di tabella, command-line, scheduler o formato di backup, aggiornare qui le sezioni interessate nella **stessa PR** e aggiungere un test del collegamento, del contratto o del comando. I risultati di CI non sono accettazione manuale Joomla né evidenza di pubblicazione 1.0.0.

**Guardia amministrativa per tutte le procedure:** verificare ACL, token CSRF dei form Joomla e stato del componente prima di ripetere una mutazione; non riprodurre automaticamente richieste POST che potrebbero essere state completate.
