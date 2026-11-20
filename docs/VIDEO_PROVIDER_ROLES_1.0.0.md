# Provider video — guida per amministratore, autore e sviluppatore (1.0.0)

Questa guida descrive le **funzioni implementate**, non opzioni pianificate. Fonti: `docs/VIDEO.md`, `docs/CLOUDFLARE_STREAM.md`, `docs/HTML5_VIDEO.md`, i manifest di `plugins/jlmsvideo/{youtube,vimeo,cloudflarestream,html5}` e il contratto `libraries/jlms/src/Video/VideoProviderInterface.php`.

## Amministratore — installazione, policy, sicurezza e ripristino

1. Installare/aggiornare **l'intero pacchetto JD Learn** con il gestore estensioni Joomla; verificare la versione comune del pacchetto e le quattro estensioni nel gruppo `jlmsvideo`. Non sovrascrivere i plugin a mano. Usare un sito di prova e un backup verificabile prima di un upgrade.
2. Aprire JD Learn → Dashboard → Stato dei provider e Gestione plugin Joomla (solo con autorizzazione `core.manage` per `com_plugins`). Un plugin installato non è necessariamente abilitato: attivare l'estensione solo dopo avere verificato l'impostazione `playback_enabled` e la policy di consenso del componente. I nuovi plugin non avviano da soli il video.
3. Per video esterni scegliere nelle opzioni JD Learn `blocked`, `embed` oppure `site_consent`. In modalità `site_consent` il sito deve fornire l'integrazione descritta in `docs/VIDEO.md` (`window.JlmsConsent.isAllowed(providerKey)` e evento `jlms:consent-changed`). Definire CSP compatibile con gli SDK effettivamente usati e verificare la privacy policy: non disabilitare CSP o consenso per aggirare un errore.
4. Per **Cloudflare Stream** leggere la guida dedicata prima di impostare le credenziali API **solo server-side**, con token temporanei e controllo di scadenza. Non inserire token o segreti nel riferimento del video, negli screenshot o nella documentazione.
5. Per **HTML5** configurare una directory privata fuori dalla web root, consentire la modalità di consegna implementata e verificare upload, scansione, autorizzazioni, Range e cancellazione secondo `docs/HTML5_VIDEO.md`. Non rendere accessibili i file mediante URL pubblici.
6. In caso di plugin non installato, disabilitato, non registrato o non configurato leggere la diagnostica in Dashboard: le istruzioni sono **in sola lettura**. Lo stato `ready` indica solo prerequisiti locali, **non** prova di riproduzione sul servizio remoto.
7. **Backup e ripristino:** prima delle modifiche salvare DB, directory privata dei materiali e configurazione Joomla, più una copia sicura dei segreti fuori dalla document root. Su istanza disposable eseguire un ripristino e verificare ruoli, riferimenti video, progressi già presenti, credenziali server-side e task. Ripristinare un plugin non equivale a riemettere attestati, ricreare completamenti o cancellare code. Evitare disinstallazione di componenti con iscritti attivi; per disattivare un provider controllare prima lezioni pubblicate e riproduzioni.
8. Dopo ogni aggiornamento provare almeno una lezione autorizzata per ogni provider utilizzato, entrambe le lingue e un utente senza diritto di visualizzazione. La CI non sostituisce questi test Joomla.

## Autore — configurazione delle lezioni

1. Usare il programma di una **bozza** del corso e la propria autorizzazione Joomla. Selezionare un provider disponibile e inserire solo la sorgente prevista: ID/URL ammessi per YouTube e Vimeo, identificativo Cloudflare Stream o risorsa MP4 locale caricata e verificata. Non digitare token in URL o descrizioni.
2. Richiedere **Rileva automaticamente** per la durata. Se il provider non risponde, riprovare oppure utilizzare il ripiego manuale nei limiti documentati (da 1 secondo a 8 ore) con sorgente controllata. La durata manuale non risolve blocchi di embed, CSP o consenso.
3. Verificare nell'anteprima i prerequisiti del sito, gli eventuali permessi, consenso e metadati. Un plugin registrato o localmente configurato non garantisce che il video sia incorporabile o accessibile. Il controllo editoriale non attribuisce crediti agli studenti.
4. Per lezioni tracciate la pubblicazione richiede capacità di posizione, durata e seek; un adapter che non le fornisce non deve consentire completamenti fittizi. Per lezioni di consultazione valgono i diversi requisiti documentati.
5. Quando cambia la sorgente di una lezione pubblicata usare il percorso di **nuova versione** del corso. Non sostituire un file o un riferimento per riscrivere retroattivamente progressi esistenti.
6. Provare da studente autorizzato l'apertura, la ripresa e la fine del video; provare anche accesso negato, sessione scaduta e revoca consenso su provider esterno. Registrare le prove nel testbook, non solo come verifica visiva del player.

## Sviluppatore — contratto v1 e integrazione senza patch al componente

**Contratto verificato nel codice:** `VideoProviderRegistry::register(ProviderDescriptor, Closure)` usa chiave `[a-z][a-z0-9_]{1,63}`, `contractMajor=1` e chiavi uniche; la factory lazy deve restituire `VideoProviderInterface`, con descrittore della stessa chiave e major version. Evento `RegisterVideoProvidersEvent::NAME` = `onJlmsRegisterVideoProviders`. Il plugin si iscrive all'evento tramite `SubscriberInterface` e Joomla carica il gruppo `jlmsvideo`. I provider base mostrano il modello in `plugins/jlmsvideo/youtube/src/Extension/Youtube.php`.

L'interfaccia `VideoProviderInterface` richiede `getDescriptor`, `getCapabilities`, `normaliseSource`, `validateSource`, `fetchMetadata`, `createPlaybackDescriptor` e `getFrontendAdapterAsset`. Il componente conserva ACL, consenso, sessioni, durata e calcolo di progresso: **il plugin non deve accreditare autonomamente progressi**. Non dichiarare capacità false né esporre credenziali in payload browser. Il JS adapter richiede una risorsa WAM registrata e compatibile con il contratto player; una sola registrazione PHP non fornisce un player funzionante.

### Esempio di estensione per il contratto (senza player)

La directory `docs/examples/video-provider-contract/` contiene `ExampleProvider.php` e `register.php`. È un **esempio di registrazione compatibile** che riceve `VideoProviderRegistryInterface` (nel vero subscriber Joomla si passa `$event->getRegistry()`), eseguibile nei test del repository e volutamente *non utilizzabile come quinto provider in produzione*: `AbstractFoundationProvider` rifiuta riproduzione e metadata, e `SourceNormalizer` accetta soltanto le chiavi già implementate. Per un provider reale occorre implementare validazione della sorgente, metadata, autorizzazione, asset frontend, consenso, test HTTP, casi negativi e documentazione completa del plugin, senza modificare JD Learn. Non distribuire la fixture di esempio nel pacchetto Joomla.

Verifiche dell'esempio: `php tests/provider-contract-example.php` testa registrazione lazy, matching della chiave e rifiuto di chiavi duplicate o major version errate. Per test del nuovo provider vero aggiungere prove server, JavaScript browser e collaudo Joomla. Nessun provider esterno è certificato dalla presenza di questa guida.

## Checklist di manutenzione delle guide

Prima di distribuire una modifica: mantenere documentazione dei quattro provider base sincronizzata con i manifest, rieseguire `python3 tests/provider-doc-contract.py` e `python3 tests/provider-package-gate.py`, verificare PR e build con i controlli della CI, testare installazione/upgrade con credenziali e file privati preservati. Il gate del builder **blocca** documentazione base mancante o campi non descritti ma non verifica la qualità di tutte le procedure.
