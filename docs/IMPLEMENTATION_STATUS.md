# Avanzamento di JD Learn

Aggiornamento: 31 ottobre 2026. Incremento0.7.0-dev: Cloudflare Stream API, protezione remota e rinnovo.

Le specifiche funzionali 1.2 e tecniche 1.0 sono la baseline dello scope. Il suffisso 1.0 nei nomi di alcuni file è storico: la versione riportata nel loro contenuto è autorevole. Le regole di sviluppo sicuro valgono dall’inizio, non soltanto al rilascio.

| Area | Stato di questo incremento | Da completare |
|---|---|---|
| Package, libreria, MVC, SQL, build | Implementazione iniziale e CI predisposte | Tutte le matrici install/upgrade/recovery, patch corrente Joomla, release signing |
| Core utenti e ACL | Identità/permessi core per editor; policy dominio testata | Entitlement fresco runtime studente, revoca e tutte le policy per oggetto |
| Categorie | Contesti core, servizi, asset di sezione e form corso | Lifecycle completo di rimozione/ripristino con dati storici |
| Corsi | Scheda, prima versione in bozza, moduli e lezioni testo/video, revisioni; pubblicazione testo con workflow nativo | Workflow con transizioni avanzate, clonazione completa, altri tipi lezione, equivalenze/migrazioni |
| Editor Joomla | Form e check-in/out nativi, livelli, lingua | Tags, fields/snapshot, Content History e associazioni multilingua |
| Frontend | Catalogo paginato, filtro categorie/sottocategorie, scheda pubblica e programma con WAM/override e ID CSS | Albero categorie dedicato, ricerca/ordinamento avanzati, dashboard personale e accessibilità completa |
| Plugin video | Quattro plugin installati; YouTube/Vimeo e Cloudflare API opt-in con SDK, consenso e progressi | HTML5, firma locale, cache/diagnostica, collaudo provider reale e rinnovo senza interruzione |
| Materiali e storage | Upload validato, storage locale privato, quarantena, protocollo ClamAV opzionale, collegamenti in bozza e download autorizzato/Range | Offload server/object storage, job cleanup, riuso asset, preview e ulteriori strumenti di gestione |
| Iscrizioni e progressi | Avvio automatico, aula testo/video e completamento manuale/copertura con idempotenza/lock per iscrizione e accesso corrente | Approvazioni/manuale/import, dashboard, UI avanzata e gestione calendario |
| Quiz | Schema e scoring dominio iniziale | Banca domande/revisioni, snapshot, start/save/submit/timer e feedback |
| Attestati | Schema emissione/documenti/sostituzione | PDF, verifica pubblica, anomalie, revoca e notifica |
| Job e outbox | Schema | Worker Scheduler/CLI, lease, retry, mail templates e delivery_unknown |
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
