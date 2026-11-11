# JD Learn — Procedura di rilascio e Update Server Joomla

Stato: **workflow predisposto, NON autorizzato a pubblicare 1.0.0 fino al gate IMP-10**. Requisiti: CORE-22, JOO-01/03, SECDEV-08, REL-01–03, PBASE-04; US-01–US-06 descritti in [JOOMLA_UPDATE_SERVER_1.0.md](JOOMLA_UPDATE_SERVER_1.0.md).

## Operazioni prima della prima pubblicazione

1. Validare la compatibilità della release con la matrice Joomla/PHP approvata; attualmente il generatore usa **Joomla 6.x e PHP minimo 8.3** come default tecnico da verificare rispetto ai target effettivi. Se serve una matrice diversa modificarla esplicitamente, non aumentare arbitrariamente il supporto.
2. Preparare il percorso HTTPS pubblico `https://www.jeckodevelopment.com/updates/jdlearn.xml`, con directory FTP/SFTP remota **già creata**, configurazione del server web Apache/LiteSpeed standard e corretta esposizione di XML statico.
3. Configurare in GitHub **Settings → Environments → jdlearn-release-production**: required reviewers, restrizione alla branch `main`, protezione dagli accessi non autorizzati. **Senza protezioni di environment il flag publish=true comporta la pubblicazione reale**.
4. Inserire i seguenti *environment secrets* (mai committare valori o stamparli nei log): `JDLEARN_SFTP_HOST`, `JDLEARN_SFTP_USER`, `JDLEARN_SFTP_PORT` (facoltativo, valore 22), `JDLEARN_SFTP_DIRECTORY` (percorso assoluto remoto della directory updates), `JDLEARN_SFTP_PRIVATE_KEY` (chiave privata senza passphrase per un utente dedicato con accesso minimo) e `JDLEARN_SFTP_KNOWN_HOSTS` (riga di host key verificata fuori banda; non usare StrictHostKeyChecking=no).
5. L'hosting SFTP deve implementare l'estensione **posix-rename** per la sostituzione atomica: se assente, il deploy fallisce senza cancellare il vecchio feed. Verificare capacità e permessi sul server reale.
6. Verificare con prove Joomla reali il package name `pkg_jlms`, il feed, la compatibilità e l'update. Registrare i risultati in `QA_TESTBOOK.md` e `IMPLEMENTATION_EVIDENCE.json`. Prima di aprire la produzione, eseguire il CI completo e l'upgrade/restore manuale previsti da IMP-10.

## Avvio del workflow

Da GitHub → Actions → **Publish JD Learn release** → Run workflow, selezionare branch `main`, versione (default `1.0.0`), canale (`stable` o `prerelease`), note di rilascio (default `Prima versione stabile di JD Learn`) e flag **publish**.

- **publish=false (default, sicuro):** viene creato il candidato ZIP stabile con versione incorporata nel manifest package, SHA-256 e file `jdlearn.xml` da esaminare come artifact GitHub Actions. **Non** crea tag, GitHub Release né deploy.
- **publish=true + channel=stable:** dopo il gate dell'environment, verifica l'artifact e l'assenza di una release con lo stesso tag, pubblica su GitHub Releases `vX.Y.Z`, controlla che l'asset ZIP sia scaricabile e coerente con il checksum, quindi carica via SFTP il feed generato, usando rename atomico. Infine rilegge il feed sull'endpoint HTTPS pubblico e verifica la versione.
- **channel=prerelease:** soltanto build/artifact, anche se si è selezionato publish=true. Questo workflow non distribuisce prerelease; non modifica il canale Joomla stabile.
- **branch diversa da main:** il workflow non esegue la build. Il CI della PR verifica il codice normalmente.

Il builder originale `python3 tools/build.py` continua a produrre i pacchetti di sviluppo `*-dev.zip`. Il nuovo comando `python3 tools/build.py --release-version 1.0.0` produce `dist/pkg_jlms-1.0.0.zip` e inserisce `1.0.0` nel manifest **dentro il package** senza fare commit o alterare `packages/pkg_jlms.xml` nel sorgente. Lo ZIP comprende componente, libreria e plugin esistenti.

**Limitazione esplicita:** il workflow prepara e verifica la distribuzione, ma non sostituisce i collaudi Joomla né la decisione umana di rilascio. I checksum sono verificati anche dal feed Joomla; non è stata introdotta una firma crittografica Joomla (da valutare separatamente). Le versioni dei singoli plugin interni possono evolvere indipendentemente dalla versione del package.

## Gestione errori e recupero

- Build/validazione fallita: nessuna pubblicazione, correggere e ripetere.
- GitHub Release riuscita ma asset non verificabile: interrompere; feed pubblico invariato. Investigare URL e permessi del repository. **Non** ritentare un tag esistente senza valutazione manuale.
- SFTP o verifica finale falliti: fermarsi, controllare lo stato reale remoto prima di qualsiasi retry. Il feed può essere già stato pubblicato se fallisce la sola verifica HTTPS, quindi controllare cache/redirect e stato effettivo.
- Release pubblicata erroneamente: interrompere ulteriori distribuzioni, verificare i siti aggiornati; per conservare lo storico delle versioni fare un nuovo rilascio correttivo piuttosto che sovrascrivere ZIP/tag già pubblicati. Il ripristino del precedente feed **non è un rollback dei siti già aggiornati**: usare backup/ripristino verificati.
- Non memorizzare segreti nel repository, nel feed o nelle release pubbliche. I pacchetti esistenti devono restare disponibili per gli installati.
- Misurare tempi e risultati su ambiente di test e registrare esplicitamente chi ha approvato, tag, checksum, run e versione del sito Joomla.

## Comportamenti da testare

Nel sito Joomla di prova: installazione iniziale e registrazione dell'Update Site; rilevamento nuova versione; upgrade dell'intero package con quattro provider e altri plugin; preservazione di corsi, lezioni, iscrizioni, progressi, ACL, configurazioni, file e stati plugin; XML o ZIP 404; versione uguale/inferiore; versione Joomla/PHP non supportata; restore; controllo HTTPS e checksum. Confrontare URL e dati restituiti da Joomla col feed pubblico, non considerare superati questi test sulla sola base della CI.

## Riferimenti

- Workflow: `.github/workflows/release.yml`
- Script: `tools/build.py`, `tools/release_feed.py`, `tools/publish_update_feed.py`
- Specifica: `docs/JOOMLA_UPDATE_SERVER_1.0.md`
- Piano: `docs/STABLE_1.0.0_PR_PLAN.md`
