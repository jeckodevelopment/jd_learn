# JD Learn — Procedura di rilascio e Update Server Joomla

Stato: **workflow predisposto, NON autorizzato a pubblicare 1.0.0 fino al gate IMP-10**. Requisiti: CORE-22, JOO-01/03, SECDEV-08, REL-01–03, PBASE-04; US-01–US-06 descritti in [JOOMLA_UPDATE_SERVER_1.0.md](JOOMLA_UPDATE_SERVER_1.0.md).

## Operazioni prima della prima pubblicazione

1. Validare la compatibilità della release con la matrice Joomla/PHP approvata; attualmente il generatore usa **Joomla 6.x e PHP minimo 8.3** come default tecnico da verificare rispetto ai target effettivi. Se serve una matrice diversa modificarla esplicitamente, non aumentare arbitrariamente il supporto.
2. Preparare il percorso HTTPS pubblico `https://www.jeckodevelopment.com/updates/jdlearn.xml`, con directory FTPS remota **già creata**, configurazione del server web Apache/LiteSpeed standard e corretta esposizione di XML statico.
3. Configurare in GitHub **Settings → Environments → jdlearn-release-production**: required reviewers, restrizione alla branch `main`, protezione dagli accessi non autorizzati. **Senza protezioni di environment il flag publish=true comporta la pubblicazione reale**.
4. Configurare i seguenti **environment secrets** in `jdlearn-release-production` (mai in chat o nel repository): `JDLEARN_FTPS_HOST` (host FTP senza schema `ftp://`), `JDLEARN_FTPS_USER`, `JDLEARN_FTPS_PASSWORD`, `JDLEARN_FTPS_PORT` (facoltativo; 21), `JDLEARN_FTPS_DIRECTORY` (percorso remoto; usare `/` quando l'utente entra già nella directory `updates`). Nessuna chiave SSH è richiesta. Gli ex secret `JDLEARN_SFTP_*` **non sono utilizzati** dal nuovo workflow e possono essere eliminati dopo la verifica.
5. Il trasporto è **FTPS esplicito (AUTH TLS)**: verifica obbligatoria del certificato CA e del nome dell'host; login soltanto dopo TLS; `PROT P` per cifrare il canale dati, senza fallback a FTP semplice. Il feed viene caricato con nome temporaneo, riletto per verificarne i byte, poi rinominato `jdlearn.xml` e riletto. **Limitazione:** FTP `RNFR/RNTO` non garantisce sostituzione atomica su ogni server; se il server rifiuta il rename sopra un file esistente il workflow **fallisce senza cancellare il vecchio feed**. Prima del primo vero rilascio verificare la capacità di sostituzione sul server con un nome di prova innocuo. Non eliminare in produzione il vecchio XML per forzare un rename.
6. Verificare con prove Joomla reali il package name `pkg_jlms`, il feed, la compatibilità e l'update. Registrare i risultati in `QA_TESTBOOK.md` e `IMPLEMENTATION_EVIDENCE.json`. Prima di aprire la produzione, eseguire il CI completo e l'upgrade/restore manuale previsti da IMP-10.

## Avvio del workflow

Da GitHub → Actions → **Publish JD Learn release** → Run workflow, selezionare branch `main`, versione (default `1.0.0`), canale (`stable` o `prerelease`), note di rilascio (default `Prima versione stabile di JD Learn`) e flag **publish** e campo di conferma **confirmation**. La pubblicazione reale richiede `JDLEARN_RELEASE_ENABLED=YES` e il testo esatto `PUBLISH JD LEARN X.Y.Z`.

- **publish=false (default, sicuro):** viene creato il candidato ZIP stabile con versione incorporata nel manifest package, SHA-256 e file `jdlearn.xml` da esaminare come artifact GitHub Actions. **Non** crea tag, GitHub Release né deploy.
- **publish=true + channel=stable:** dopo il gate dell'environment, verifica l'artifact e l'assenza di una release con lo stesso tag, pubblica su GitHub Releases `vX.Y.Z`, controlla che l'asset ZIP sia scaricabile e coerente con il checksum, quindi carica via FTPS esplicito/TLS il feed generato, con upload temporaneo e rename (sostituzione dipendente dal server). Infine rilegge il feed sull'endpoint HTTPS pubblico e verifica la versione.
- **channel=prerelease:** soltanto build/artifact, anche se si è selezionato publish=true. Questo workflow non distribuisce prerelease; non modifica il canale Joomla stabile.
- **branch diversa da main:** il workflow non esegue la build. Il CI della PR verifica il codice normalmente.

Il builder originale `python3 tools/build.py` continua a produrre i pacchetti di sviluppo `*-dev.zip`. Il nuovo comando `python3 tools/build.py --release-version 1.0.0` produce `dist/pkg_jlms-1.0.0.zip` e inserisce `1.0.0` nel manifest **dentro il package** senza fare commit o alterare `packages/pkg_jlms.xml` nel sorgente. Lo ZIP comprende componente, libreria e plugin esistenti.

**Limitazione esplicita:** il workflow prepara e verifica la distribuzione, ma non sostituisce i collaudi Joomla né la decisione umana di rilascio. I checksum sono verificati anche dal feed Joomla; non è stata introdotta una firma crittografica Joomla (da valutare separatamente). Le versioni dei singoli plugin interni possono evolvere indipendentemente dalla versione del package.

## Gestione errori e recupero

- Build/validazione fallita: nessuna pubblicazione, correggere e ripetere.
- GitHub Release riuscita ma asset non verificabile: interrompere; feed pubblico invariato. Investigare URL e permessi del repository. **Non** ritentare un tag esistente senza valutazione manuale.
- FTPS o verifica finale falliti: fermarsi, controllare lo stato reale remoto prima di qualsiasi retry. Il feed può essere già stato pubblicato se fallisce la sola verifica HTTPS, quindi controllare cache/redirect e stato effettivo.
- Release pubblicata erroneamente: interrompere ulteriori distribuzioni, verificare i siti aggiornati; per conservare lo storico delle versioni fare un nuovo rilascio correttivo piuttosto che sovrascrivere ZIP/tag già pubblicati. Il ripristino del precedente feed **non è un rollback dei siti già aggiornati**: usare backup/ripristino verificati.
- Non memorizzare segreti nel repository, nel feed o nelle release pubbliche. I pacchetti esistenti devono restare disponibili per gli installati.
- Misurare tempi e risultati su ambiente di test e registrare esplicitamente chi ha approvato, tag, checksum, run e versione del sito Joomla.

## Comportamenti da testare

Nel sito Joomla di prova: installazione iniziale e registrazione dell'Update Site; rilevamento nuova versione; upgrade dell'intero package con quattro provider e altri plugin; preservazione di corsi, lezioni, iscrizioni, progressi, ACL, configurazioni, file e stati plugin; XML o ZIP 404; versione uguale/inferiore; versione Joomla/PHP non supportata; restore; controllo HTTPS e checksum. Confrontare URL e dati restituiti da Joomla col feed pubblico, non considerare superati questi test sulla sola base della CI.

## Riferimenti

- Workflow: `.github/workflows/release.yml`
- Script: `tools/build.py`, `tools/release_feed.py`, `tools/publish_update_feed_ftps.py`
- Specifica: `docs/JOOMLA_UPDATE_SERVER_1.0.md`
- Piano: `docs/STABLE_1.0.0_PR_PLAN.md`

## Migrazione operativa SFTP → FTPS

L'account FTP secondario dell'hosting non consente autenticazione SSH. In FileZilla è stato verificato il supporto per **FTP esplicito su TLS**. La pipeline usa perciò FTPS, **non FTP semplice**.

1. Nel GitHub Environment `jdlearn-release-production`, sostituire i tre secret già creati con i nuovi nomi `JDLEARN_FTPS_HOST`, `JDLEARN_FTPS_USER`, `JDLEARN_FTPS_DIRECTORY`; se il client FTP visualizza direttamente la cartella `updates` come directory radice, `JDLEARN_FTPS_DIRECTORY=/`.
2. Aggiungere `JDLEARN_FTPS_PASSWORD` con la password dell'utente FTP dedicato; `JDLEARN_FTPS_PORT` può essere omesso se il servizio usa la porta 21. Le credenziali sono disponibili soltanto al job di produzione protetto.
3. Verificare che il certificato FTPS presentato dall'host configurato sia valido per quel **nome host**. Se FileZilla chiede eccezioni o mostra un certificato non valido, non disabilitare la verifica nel workflow: utilizzare un hostname fornito dal provider con certificato corretto.
4. Testare prima `publish=false`: crea soltanto un candidato e non tenta connessioni FTP. Una vera prova della connessione va effettuata in modo controllato **senza pubblicare la 1.0.0**, verificando preliminarmente autenticazione FTPS, accesso alla directory, caricamento e rename di file di test, privilegi e comportamento sul rename in presenza di file esistente.
5. Il feed XML della 1.0.0 non va pubblicato finché le verifiche IMP-10 e l'autorizzazione al rilascio non sono completate.


## Verifica di connessione FTPS non distruttiva (pre-release, IMP-09)

Prima di autorizzare la prima release, aprire **Actions → Check JD Learn FTPS access (safe) → Run workflow**, scegliendo la branch `main`. Il job usa i secret già configurati nell'Environment protetto `jdlearn-release-production` e **non** usa `JDLEARN_RELEASE_ENABLED`.

Questa prova:
- stabilisce una connessione FTP **esplicita** con verifica completa del certificato TLS/hostname, effettua `AUTH TLS` prima del login e richiede `PROT P` per le connessioni dati;
- accede a `JDLEARN_FTPS_DIRECTORY` (il valore `/` è corretto per un account confinato alla directory web `updates`);
- crea due file temporanei con nomi casuali `.jdlearn-ftps-probe-*`, rilegge i contenuti, prova a **rinominare sopra un altro file temporaneo già esistente**, verifica il contenuto sostituito e pulisce i file creati;
- **non crea o modifica** `jdlearn.xml`, non genera release GitHub, non abilita `publish=true`, non modifica i dati Joomla;
- segnala esito **KO** se il server nega il rename-overwrite (in quel caso la procedura di pubblicazione resta da adeguare: mai cancellare `jdlearn.xml` per aggirare il problema).

L'esito va registrato con URL della run in `QA_TESTBOOK.md`/evidenze di release prima del gate IMP-10. Un risultato positivo verifica connessione e operazioni sul filesystem, **non** dimostra che l'URL pubblico HTTPS ospiti un feed valido né che Joomla installi un update.

Se il job è bloccato per l'approvazione dell'Environment, approvarlo da GitHub: non equivale ad autorizzare la release stabile. In caso di errore certificato, **non** disabilitare la verifica TLS, ma correggere `JDLEARN_FTPS_HOST` usando il nome host effettivamente coperto dal certificato del provider.

**Condizione operativa:** le credenziali dell'utente FTP dedicato devono consentire scrittura e cancellazione *solo* in `updates`. Il job non deve essere usato prima di aver verificato la corretta segregazione dell'account.
