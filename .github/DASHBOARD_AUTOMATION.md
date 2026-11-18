# Automazione della dashboard di sviluppo JD Learn

> Documentazione operativa dei workflow GitHub Actions, **non** documentazione del prodotto Joomla JD Learn. Questa integrazione riguarda esclusivamente il cruscotto pubblico di monitoraggio dello sviluppo.

## Repository e responsabilità

- Sorgente di riferimento: `jeckodevelopment/jd_learn` (questo repository).
- Dashboard e dati: `jeckodevelopment/jd-learn-dashboard`.
- Frontend pubblicato: `jeckodevelopment/jeckodevsite`, percorso `/dev/jdlearn/`.
- I dati visualizzati nel browser provengono direttamente dai JSON nel repository `jd-learn-dashboard`, con fallback locale sul sito.

## Trigger e sequenza

1. Ogni push su `main` di JD Learn, inclusi i merge delle PR, avvia `.github/workflows/notify-dashboard.yml`.
2. Il job invia un `repository_dispatch` (`event_type: jdlearn-main-updated`) alla repository `jd-learn-dashboard`.
3. Il workflow `Refresh JD Learn dashboard data` esegue `scripts/sync.py`, valida i JSON, controlla la freschezza del timestamp e pubblica il commit dei dati.
4. La dashboard legge il nuovo `data/snapshot.json`.
5. Il cron UTC `7,37 * * * *` nella repository dashboard rimane attivo come aggiornamento periodico; `workflow_dispatch` consente recuperi manuali.

Le esecuzioni `schedule` di GitHub Actions sono best effort: possono partire in ritardo oppure venire saltate. Il trigger su `main` è complementare, non un meccanismo esterno indipendente.

## Configurazione una tantum (segreto)

Creare un **fine-grained personal access token** GitHub per l'account titolare, limitando **Repository access** a `jeckodevelopment/jd-learn-dashboard` e impostando **Repository permissions → Contents: Read and write** (serve per l'API `POST /repos/jeckodevelopment/jd-learn-dashboard/dispatches`). Usare la scadenza più breve praticabile e prevedere la rotazione; se si preferisce una credenziale non personale, si può utilizzare un GitHub App installation token con permessi equivalenti, gestendo il provisioning.

Nel repository **JD Learn**, aprire **Settings → Secrets and variables → Actions → New repository secret**, nome esatto `DASHBOARD_DISPATCH_TOKEN`, valore del token. Non inserire il token in issue, PR, commenti, codice o log. In assenza del secret il job annota un warning ed esce senza bloccare la CI del componente; l'aggiornamento pianificato resta attivo.

## Verifiche dopo configurazione

1. Controllare in **JD Learn → Actions → Notify JD Learn release dashboard** una run dopo un nuovo push su `main`. Deve registrare HTTP **204** dall'API di dispatch.
2. Controllare in **jd-learn-dashboard → Actions → Refresh JD Learn dashboard data** una run con evento `repository_dispatch` e conclusione `success`.
3. Controllare il valore `generatedAt` in `jd-learn-dashboard/data/snapshot.json`: deve essere successivo al push su `main`.
4. Verificare sul sito che la dashboard mostri l'orario nuovo e nessun avviso di dati obsoleti. Se serve aggiornare anche la pagina statica, il deploy SFTP rimane gestito separatamente da `jeckodevsite`.

## Diagnostica

- **Run di notifica assente:** verificare che la modifica sia stata integrata in `main` e che le Actions siano abilitate.
- **Warning secret mancante:** configurare `DASHBOARD_DISPATCH_TOKEN` come sopra.
- **HTTP 401/403/404:** verificare repository selezionato, scadenza e permessi del token.
- **HTTP 204 ma refresh assente:** controllare che il workflow dashboard su `main` abbia `repository_dispatch` con il tipo `jdlearn-main-updated` e sia abilitato.
- **Refresh fallito:** consultare i job e i log; la produzione dei dati è fail-closed, non pubblica intenzionalmente snapshot parziali.
- **Snapshot aggiornato ma frontend vecchio:** verificare la disponibilità del JSON raw GitHub e ricaricare la pagina; il deploy SFTP riguarda i file statici.

## Impatto sul componente

Nessuna modifica al codice Joomla, alla versione del pacchetto, ai requisiti funzionali, alle migrazioni o ai test applicativi. Questa automazione è infrastruttura di progetto, documentata soltanto sotto `.github/`.
