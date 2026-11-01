# Avanzamento di JD Learn

Aggiornamento: 1 novembre 2026. Incremento0.18.0-dev: sospensione e riattivazione con anteprima e audit.

Le specifiche funzionali 1.2 e tecniche 1.0 sono la baseline dello scope. Il suffisso 1.0 nei nomi di alcuni file è storico: la versione riportata nel loro contenuto è autorevole. Le regole di sviluppo sicuro valgono dall’inizio, non soltanto al rilascio.

| Area | Stato di questo incremento | Da completare |
|---|---|---|
| Package, libreria, MVC, SQL, build | Implementazione iniziale e CI predisposte | Tutte le matrici install/upgrade/recovery, patch corrente Joomla, release signing |
| Core utenti e ACL | Identità/permessi core per editor; policy dominio testata | Entitlement fresco runtime studente, revoca e tutte le policy per oggetto |
| Categorie | Contesti core, servizi, asset di sezione e form corso | Lifecycle completo di rimozione/ripristino con dati storici |
| Corsi | Scheda, prima versione in bozza, moduli e lezioni testo/video, revisioni; pubblicazione testo con workflow nativo | Workflow con transizioni avanzate, clonazione completa, altri tipi lezione, equivalenze/migrazioni |
| Editor Joomla | Form e check-in/out nativi, livelli, lingua | Tags, fields/snapshot, Content History e copertura multilingua completa |
| Frontend | Catalogo paginato, filtro categorie/sottocategorie, scheda pubblica e programma con WAM/override e ID CSS | Ricerca/ordinamento avanzati e accessibilità completa |
| Plugin video | Quattro plugin installati; YouTube/Vimeo, Cloudflare API e HTML5 opt-in; progressi comuni, upload locale e Nginx | Firma locale Cloudflare, cache/diagnostica, altri codec/storage, collaudo browser/provider e rinnovo senza interruzione |
| Materiali e storage | Upload validato, storage locale privato, quarantena, protocollo ClamAV opzionale, collegamenti in bozza e download autorizzato/Range | Offload server/object storage, job cleanup, riuso asset, preview e ulteriori strumenti di gestione |
| Iscrizioni e progressi | Avvio automatico, aula testo/video e completamento manuale/copertura con idempotenza/lock per iscrizione e accesso corrente | Approvazioni/manuale/import, dashboard amministrativa, UI avanzata e gestione calendario |
| Quiz | Banca/bozze native, pubblicazione con pool congelato, tentativi atomici, snapshot/estrazione, start/save/submit/CAS/timer, scoring, risultati, feedback protetto e tempi individuali preventivi | Rivalutazioni, disponibilità specifica, audit/privacy generali, UI/accessibilità e carico |
| Attestati | Schema emissione/documenti/sostituzione | PDF, verifica pubblica, anomalie, revoca e notifica |
| Job e outbox | Schema e plugin Scheduler limitato alla scadenza quiz | Worker generali/CLI, lease, retry, mail templates e delivery_unknown |
| Privacy, Action Logs, Finder | Specifiche conservate | Plugin funzionanti e collaudo lifecycle/export/erasure |
| Report/import/export | Specifiche e schema | Servizi, CSV, job, scope e protezione formule |
| Prestazioni e sicurezza | Controlli iniziali e test negativi limitati | Static analysis/scan dipendenze e segreti, E2E completi e carico300/500 |

## Ordine dei prossimi incrementi

1. Completare T1/T2: installazione e lifecycle riproducibili, servizi authoring/DI, strumenti core e tracciabilità.
2. Versioni/moduli/lezioni/prerequisiti e pubblicazione validata; materiali protetti.
3. Iscrizioni, aula, accesso corrente e persistenza concorrente dei progressi.
4. Player dei quattro provider e relativi scenari di sicurezza/consenso.
5. Quiz persistenti, timer/snapshot e revisione valutazioni.
6. Attestati, job/outbox, report/import e integrazioni privacy/Finder/actionlog complete.
7. Test E2E, install/upgrade/recovery, audit e carico con dataset e infrastruttura dichiarati.

Nessun requisito completo del prodotto è considerato soddisfatto dal solo schema o da un’interfaccia. I118scenari funzionali e40provider restano pianificati finché non vengono eseguiti sul flusso completo. I test runtime iniziali verificano soltanto il comportamento realmente presente in questo incremento.

## Differenze attuative rispetto allo schema progettuale

L’installer applica la DDL tramite uno script controllato anziché ripetere gli ALTER al semplice reinstall con dati conservati. Ogni passo è ricontrollato e interrompe l’installazione in errore; non si pretende una transazione DDL globale. Il primo incremento non esegue backfill o migrazioni di versioni già pubblicate.

Nessun plugin di integrazione privacy/Finder/task viene distribuito come involucro vuoto: saranno aggiunti quando avranno un comportamento verificabile. I quattro provider richiesti sono già costituenti del pacchetto, ma non dichiarano capacità di riproduzione non implementate.

La gestione del programma è descritta in AUTHORING.md. Il nuovo servizio applicativo centralizza autorizzazioni/transazioni; i test integrativi includono versioni immutabili, ID fuori dal parent, dati concorrenti, ordinamento e rimozione. Gli esiti effettivi restano nelle Actions.

Pubblicazione e catalogo: vedere PUBLICATION.md. Le transizioni sono native Joomla e il plugin Workflow - Publishing deve essere attivo. Non sono ancora implementati transizioni editoriali senza cambio stato, clonazione di versioni, snapshot dei campi personalizzati e altri tipi di contenuto. La pubblicazione di questi casi è rifiutata. I test di carico restano da eseguire.

Incremento aula testo: vedere LEARNING.md. I task HTML enrollment.start e lesson.complete non costituiscono ancora le API JSON pianificate. Il completamento manuale non prova tempo di lettura/presenza. Nessun attestato o email viene generato. Collaudo su sito effimero e concorrenza reale; carico 300/500 ancora da eseguire.

Materiali: vedere MATERIALS.md. Il gestore deve configurare storage fuori webroot e abilitare la consegna PHP. Scanner assente dichiarato esplicitamente; se obbligatorio ma indisponibile, nessuna promozione a ready. Prove di protocollo con simulatore ClamAV, non attestazione antivirus dei contenuti. Nessuna purga automatica di blob.

## Incremento0.6

Protocollo, configurazione e limiti in [VIDEO.md](VIDEO.md). Test e risultati effettivi nella PR #6. Prove provider reali/browser, carico e patch corrente Joomla ancora da completare. Nessuna dichiarazione di completezza dell’intero LMS.

## Incremento0.7

Configurazione e perimetro effettivo in [CLOUDFLARE_STREAM.md](CLOUDFLARE_STREAM.md). Verifiche remote fuori dalle transazioni, accesso nuovamente controllato dopo la rete; nessuna chiamata Cloudflare sul salvataggio progresso. Test API/SDK con fixture, non collaudo live. Risultati effettivi nella PR #7. L’intero LMS rimane incompleto.

## Incremento0.8

Guida operativa inclusa nel pacchetto: [HTML5_VIDEO.md](HTML5_VIDEO.md). MP4 privati per lezione, metadata server, rescan e immutabilità; consegna PHP esplicitamente limitata oppure Nginx interno. Risultati effettivi nella PR #8; test con ffprobe/Nginx reali non equivalgono al collaudo del decoder browser o al benchmark di centinaia di studenti. L’intero LMS resta in sviluppo.

## Incremento0.9

Guida operativa inclusa: [QUIZ_AUTHORING.md](QUIZ_AUTHORING.md). L’editor configura quiz in bozza ma non attiva tentativi o pubblicazione. Le revisioni della banca sono immutabili; il pool usa revisioni esplicite e controlla ACL su ogni oggetto. Risultati effettivi e limiti nella PR #9. L’intero LMS resta in sviluppo.

## Incremento0.10

[QUIZ_RUNTIME.md](QUIZ_RUNTIME.md) documenta servizi, task, stato/limiti, criteri di accesso, scadenza e configurazione dell’attività pianificata. Il pacchetto ha sette costituenti; quattro provider video più task/jlms. Nessun nuovo schema necessario: usate le tabelle preesistenti. Risultati effettivi nella PR #10; non è una certificazione del LMS completo, del carico o della patch Joomla corrente.

## Incremento0.11

[QUIZ_EXTRA_TIME.md](QUIZ_EXTRA_TIME.md): autorizzazione preventiva individuale, ritiri futuri, storico e primo audit tramite modello Action Logs Joomla. Nuova tabella additiva:38tabelle/55FK. Nessun cambiamento agli snapshot precedenti. I controlli effettivi sono nelle Actions della PR #11; QUI-08 rimane parziale in attesa del collaudo completo di accessibilità.

## Incremento0.12

[QUIZ_CANCELLATION.md](QUIZ_CANCELLATION.md): annullamento con anteprima e conferma, consumo/resto, proiezioni valide, permessi e audit core. Non è rivalutazione: presenza di attestati o correzioni amministrative blocca l’applicazione. Nessuna migrazione schema; aggiornamento dalla0.11 e conservazione verificati nella PR #12. Carico, browser/accessibilità completi e patch corrente restano da certificare.

## Incremento0.13

[QUIZ_DEADLINE_EXTENSION.md](QUIZ_DEADLINE_EXTENSION.md): proroga preventiva della deadline corrente di un tentativo ancora aperto, con anteprima, autorizzazione, storia e audit nativo; snapshot originari conservati e sincronizzazione compatta. Nuova tabella additiva,39tabelle/56FK. Test effettivi nella PR #13, senza certificazione implicita di carico/browser/accessibilità o patch corrente.

## Incremento0.14

[MULTILINGUAL.md](MULTILINGUAL.md): associazioni native dei corsi in bozza, lettura tramite selettore lingua Joomla e visibilità corrente. Gruppi pubblicati in sola lettura; nessun trasferimento dei progressi. Nessuna nuova tabella,39tabelle/56FK. Categorie, editor pubblicato, selettore ricercabile e copertura completa restano da completare. Esiti effettivi nella PR #14.

## Incremento0.15

[CATEGORIES.md](CATEGORIES.md): viste elenco/categoria, sottocategorie e corsi paginati, verifica corrente degli antenati, Pathway e associazioni native della sezione corsi. Nessuna modifica allo schema39tabelle/56FK. Test effettivi nella PR #15. Eliminazione con dipendenze, alias/SEO completi, browser/accessibilità e carico restano da completare.

## Incremento0.16

[LEARNER_DASHBOARD.md](LEARNER_DASHBOARD.md): storico del solo proprietario con filtri/paginazione, stato didattico distinto dalla disponibilità e ripresa in lettura nuovamente autorizzata. Lingua originale e storico conservati dopo revoca/scadenza, nessun corpo lezione o tentativo nel riepilogo. Nessuna modifica allo schema39tabelle/56FK. Attestati e attività da correggere/ripetere restano da completare; esiti effettivi nella PR #16.

## Incremento0.17

[ENROLLMENT_ROSTER.md](ENROLLMENT_ROSTER.md): elenco amministrativo in sola lettura, con ACL dedicate al corso, filtri e paginazione Joomla. Stato iscrizione/completamento distinti, dati minimi e date UTC. Nessuna modifica allo schema39tabelle/56FK; operazioni di gestione, risultati/attestati, aggregati ed export ancora pendenti. Esiti effettivi nella PR di incremento.

## Incremento0.18

[ENROLLMENT_STATE.md](ENROLLMENT_STATE.md): transizioni esplicite active/completed→suspended→active, anteprima/CAS, motivo/conferma, replay con nuova autorizzazione e audit core atomico. Storico e deadline conservati, nessun gruppo o proroga. Schema additivo40tabelle/57FK; creazione manuale, approvazioni, annullamenti, proroghe formative e privacy generale restano pendenti. Esiti effettivi nella PR di incremento.
