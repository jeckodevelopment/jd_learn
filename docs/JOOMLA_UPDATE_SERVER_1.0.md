# JD Learn 1.0.0 — Joomla Update Server e distribuzione JED

Stato (12 novembre 2026): **infrastruttura e funzionalità di aggiornamento implementate e verificate automaticamente; pubblicazione stabile non abilitata, collaudo end-to-end Joomla rinviato**. Ambito: **CORE-22**, **JOO-01/JOO-03**, **SECDEV-08**, **REL-01–03**, **PBASE-04**; piano **IMP-09 (implementazione) → IMP-10 (validazione finale)**. Nessuna funzionalità di licenza commerciale viene aggiunta alla 1.0.0.

## Obiettivi funzionali e criteri di accettazione

- **US-01 — Rilevamento nativo.** Installando lo ZIP ufficiale `pkg_jlms`, Joomla registra un update site HTTPS; da Sistema → Aggiornamenti → Estensioni l'amministratore rileva una nuova release stabile pubblicata e compatibile, senza installare un updater proprietario.
- **US-02 — Aggiornamento dell'intero prodotto.** Il download/upgrade usa il package `pkg_jlms` (non il solo `com_jlms`): libreria, componente e plugin inclusi seguono gli stessi vincoli di compatibilità e conservano configurazioni, dati, permessi, provider e stati di attivazione.
- **US-03 — Sicurezza e integrità.** Rilascio tramite HTTPS, pacchetto ufficiale immutabile con SHA-256 pubblicato e verificato in CI; nessuna credenziale o token nel feed, negli URL o nel pacchetto pubblico. Se si adotta una firma di aggiornamento Joomla, seguire il formato realmente supportato dalla versione target, documentando chiavi e rotazione; la firma non si considera implementata sulla sola presenza del checksum.
- **US-04 — Compatibilità.** Il feed espone solo release dichiarate compatibili con le versioni Joomla/PHP effettivamente supportate dalla matrice ufficiale del progetto; niente prerelease/dev nel canale stabile. Non sono introdotti vincoli Nginx: installazione e upgrade funzionano su hosting LAMP standard Apache o LiteSpeed, senza regole server specifiche.
- **US-05 — Gestione errori.** Feed irraggiungibile, ZIP non disponibile, release incompatibile o download fallito non devono corrompere l'installazione o dichiarare completato un upgrade. Backup/ripristino e rollback operativo sono documentati e verificati.
- **US-06 — Operatività.** La procedura di release pubblica in ordine ZIP definitivo e checksum, valida il feed, quindi aggiorna atomicamente il feed pubblico; verifica URL, risposta HTTP, XML, versione, compatibilità e riferimenti; conserva i rilasci precedenti per la manutenzione.

## Architettura tecnica

- **Endpoint previsto:** `https://www.jeckodevelopment.com/updates/jdlearn.xml`. La directory `updates` esiste sullo hosting e il deploy FTPS è stato verificato con file temporanei; **il feed `jdlearn.xml` non è ancora pubblicato**. Verificare host/canonical URL, HTTPS, redirect, MIME/XML, disponibilità e caching solo quando sarà autorizzata la pubblicazione.
- **Hosting:** semplice file XML statico su hosting web condiviso, nessuna applicazione residente, database dedicato o cron richiesto. Pubblicazione via deploy già autorizzato o procedura equivalente.
- **Artefatti:** ZIP versionati di `pkg_jlms` su GitHub Releases, collegamenti stabili HTTPS verificati con richieste reali (non supporre che l'URL del browser sia l'asset scaricabile). Eventuale mirror sul dominio ufficiale se la distribuzione diretta fallisce con Joomla/PHP hosting.
- **Manifest:** dichiarazione `<updateservers><server type="extension" priority="1" name="JD Learn">...</server></updateservers>` nel manifest del **package** `packages/pkg_jlms.xml` o manifest generato equivalente; garantire che nome tecnico e identificatore corrispondano al pacchetto realmente distribuito.
- **Feed Joomla:** XML conforme al formato Update Server per il package, con `<updates><update>`, `<name>`, `<element>pkg_jlms</element>`, `<type>package</type>`, `<version>`, `<downloads><downloadurl type="full" format="zip">…</downloadurl></downloads>`, `<targetplatform>` e tag stable; campi e modalità di piattaforma verificati contro la documentazione della release Joomla target. Valutare eventuali sezioni separabili per più famiglie Joomla senza segnalare release incompatibili.
- **CI/CD:** generazione pacchetto con builder esistente `tools/build.py`; test di consistenza tra package manifest, versione, ZIP, checksum e feed; job di publish solo per tag/release approvati, permessi minimi, protezione contro rilascio duplicato, fallimento se un URL restituisce HTML/404 o XML invalido; deploy dopo la pubblicazione verificata dell'asset.
- **Versionamento:** versioni di release monotone e coerenti con il manifest distribuito, file ZIP e feed. Versioni `-dev`/PR non devono sostituire il canale stable o attivare aggiornamenti sui siti clienti.
- **Licenze:** nessun license server, download key o sistema commerciale obbligatorio nella 1.0.0. Eventuale distribuzione a pagamento è decisione distinta, senza cambiare il feed pubblico se non necessario.

## Collaudo (IMP-09 / IMP-10) — comportamenti da testare

1. Installare una release di test di `pkg_jlms` su un sito Joomla supportato con Apache (e, dove disponibile, LiteSpeed): verificare la registrazione automatica dell'update site nel pannello Joomla.
2. Pubblicare una release test successiva su endpoint di staging controllato; verificare che Joomla rilevi la versione corretta, mostri l'aggiornamento e scarichi lo ZIP del **package** completo.
3. Preparare corsi, lezioni, iscritti, progressi, materiali, ACL, lingue, configurazioni e quattro provider; aggiornare da Joomla e confrontare i dati pre/post. Confermare che le migrazioni siano idempotenti e non alterino contenuti pubblicati o segreti/configurazioni.
4. Provare versione incompatibile, feed non raggiungibile, XML malformato, ZIP mancante, download interrotto e versione uguale/inferiore: nessun falso aggiornamento o perdita dati; registrare log ed esito.
5. Verificare che l'aggiornamento non abiliti plugin disattivati, non registri credenziali nel feed e che nessun `-dev` sia visibile dal canale stable.
6. Verificare SHA-256 dell'asset, integrità del package e installazione/upgrade in ambiente standard Joomla su stack PHP/DB della matrice; registrare build, versione target, URL feed, link CI e prove manuali reali in `docs/QA_TESTBOOK.md`.
7. Simulare fallimento significativo e ripristinare da backup; descrivere la procedura e l'esito prima del gate release.

## Deliverable e gate

**IMP-09:** manifest package e feed di esempio/template validato, test automatici, pipeline publish, istruzioni operatore e implementazione endpoint. **IMP-10:** endpoint produttivo funzionante, rilascio immutabile e checksum, prova end-to-end del Joomla updater, evidenze di regressione/backup/ripristino, link JED e guida pubblicazione. Non considerare US-01–06 completati prima di codice, CI ed evidenze manuali. Dopo il merge delle PR attuative, aggiornare `docs/REQUIREMENTS_STATUS.csv`, testbook, changelog, `docs/IMPLEMENTATION_EVIDENCE.json` e percentuale di avanzamento secondo la metodologia vigente.

Documentazione di riferimento: https://manual.joomla.org/docs/building-extensions/install-update/update-server/ e requisiti JED su https://extensions.joomla.org/support/knowledgebase/submission-requirements/joomla-update-system-requirement/ ; verificare documentazione della versione target al momento dell'implementazione.


## Stato attuativo tecnico — 12 novembre 2026

**Implementazione disponibile, separata dal collaudo finale:**

- Il manifest package `packages/pkg_jlms.xml` contiene `<updateservers>` con endpoint HTTPS ufficiale per `pkg_jlms`; builder `tools/build.py` genera il package completo dei 10 componenti con versione coerente.
- `tools/release_feed.py` genera il feed Joomla `jdlearn.xml` con versione, URL HTTPS del release asset, compatibilità Joomla/PHP e digest SHA-256.
- Il workflow `.github/workflows/release.yml` supporta dry-run `publish=false`, candidato scaricabile, checksum, vincoli di autorizzazione e pubblicazione protetta GitHub Release → feed FTPS → verifica via HTTPS. La release produttiva resta intenzionalmente bloccata.
- `tools/publish_update_feed_ftps.py` e `tools/ftps_trust.py` eseguono FTPS esplicito, `PROT P`, riutilizzo della sessione TLS e verifica PKI o pin SHA-256; upload su nome temporaneo, readback, rename e controllo dei contenuti senza eliminare il feed esistente in caso di errore.
- Il [test FTPS reale 43131327572](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131327572) ha superato autenticazione, upload, readback, rename-overwrite e cleanup su file temporanei. Il [dry-run release 43131931497](https://github.com/jeckodevelopment/jd_learn/actions/runs/43131931497) ha generato con successo la candidata 1.0.0 con pubblicazione SKIPPED. L'utente ha confermato installazione e funzionamento generale del candidato su Joomla di prova.

**Distinzione degli stati:** la funzionalità e l'infrastruttura del sistema di aggiornamento sono **sviluppate**; la distribuzione produttiva non è ancora **attivata** e l'accettazione completa IMP-10 (upgrade con dati preesistenti, updater nativo, failure/rollback) non è ancora **collaudata**. Non considerare il feed reale o il meccanismo di update Joomla end-to-end già verificati. Il 78% documentato rappresenta il complessivo sviluppo JD Learn verso 1.0.0, **non** il completamento del solo updater.
